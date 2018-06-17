<?php

	require __DIR__ . '/vendor/autoload.php';
    session_start();
    use AWSCognitoApp\AWSCognitoWrapper;
    use XeroPHP\Application\PrivateApplication;
    use XeroPHP\Application\PartnerApplication;
	use XeroPHP\Remote\Request;
    use XeroPHP\Remote\URL;
    require('database.php');
    require('storage.php');

    $wrapper = new AWSCognitoWrapper();
    $wrapper->initialize();	

    // load config variables
	$env = getenv('environment');
	if($env == "local") {
        // getting settings from httpd.conf        
        $apptype         = getenv('apptype');  
        $callback        = getenv('callback');
        $consumer_key    = getenv('consumer_key');
        $consumer_secret = getenv('consumer_secret');
	 }
	 else
	 {
		 // we're running on aws, get settings from server variables  
        $apptype         = $_SERVER['apptype']; 
        $callback        = $_SERVER['callback'];
        $consumer_key    = $_SERVER['consumer_key'];
        $consumer_secret = $_SERVER['consumer_secret'];
     }	
    if($apptype=="private")
    {
        // running in Private app mode
        $config = [
            'oauth' => [
                'callback'        => $callback,	
                'consumer_key'    => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'rsa_private_key'       => 'file://certs/private/privatekey.pem',
                ]
        ];
    }
    else
    {
        // running in Partner app mode
        $config = [
            'oauth' => [
                'callback'        => $callback,	
                'consumer_key'    => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'rsa_private_key'       => 'file://certs/partner/privatekey.pem',
                'signature_location'  => \XeroPHP\Remote\OAuth\Client::SIGN_LOCATION_QUERY,
                ],
            'curl' => [CURLOPT_USERAGENT   => 'CreditLimits', CURLOPT_CAINFO => __DIR__.'/certs/partner/ca-bundle.crt']
        ];
    }

    $user = $wrapper->getUser();
    $pool = $wrapper->getPoolMetadata();
    $users = $wrapper->getPoolUsers();
    $_SESSION['user'] = $user['Username'];
    // create db tables
    $storage = new StorageClass();
    // create the Tenants table
    $storage->createTable($dynamodb, "Tenants", "orgid", NULL);
    // create the UserTenants table
    $storage->createTable($dynamodb, "UserTenants", "username", NULL);
    // create the Limits table
    $storage->createTable($dynamodb, "Limits", "contactid", "orgid");
    // create the TenantsToCheck table
    $storage->createTable($dynamodb, "TenantsToCheck", "orgid", NULL);
    // check if we're running in Private app or Partner app type
    if($apptype=="private")
    {
        $xero = new PrivateApplication($config);
        // get the org name and id from Xero API
        $orgname = $storage->getOrgName($xero);   
        $orgId = $storage->getOrgId($xero);
        $_SESSION['orgid'] = $orgId;	            
         // update the database - even though we don't need to store and maintain a token in private app mode,
         // we need a record in the Tenants table for the webhook scripts to run
        $storage->setOAuthTokenTenantsTable($_SESSION['user'], $_SESSION['orgid'], "n/a", "n/a", "n/a", "n/a", "n/a", $dynamodb, $marshaler);   
    }
    else
    {
        //does the user have a current connection?
        $hasAToken = $storage->getCurrentUserToken($_SESSION['user'], $dynamodb, $marshaler);
        //set orgid and name
        if($hasAToken)
        {
            $_SESSION['orgid'] = $storage->getOrgIdFromUserTenants($_SESSION['user'], $dynamodb, $marshaler);

            $xero = new PartnerApplication($config);
        
            //Get session data
            $oauth_session = $storage->getSession();

            $xero->getOAuthClient()
                ->setToken($oauth_session['token'])
                ->setTokenSecret($oauth_session['token_secret']);

            // If token expired - renew
            if($storage->checkToken($xero))
            {
                error_log("Token has expired, we need to renew.", 0);
                $storage->refreshToken($xero);		
                // update the database
                $storage->setOAuthTokenTenantsTable($_SESSION['user'], $_SESSION['orgid'], $_SESSION['oauth']['token'], $_SESSION['oauth']['token_secret'], $_SESSION['oauth']['expires'],$_SESSION['oauth']['session_handle'],$_SESSION['oauth']['token_timestamp'], $dynamodb, $marshaler);
                // get a fresh xero connection for further use
                $oauth_session = $storage->getSession();
                $xero->getOAuthClient()
                    ->setToken($oauth_session['token'])
                    ->setTokenSecret($oauth_session['token_secret']);

            }
            // get the org name from Xero API
            $orgname = $storage->getOrgName($xero);
        }
    }
?>

<html>
<head>
	<title>XD18 | Settings</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/png" href="UI/favicon.ico">
	<link rel="stylesheet" type="text/css" href="UI/styles/normalize.css">
	<link rel="stylesheet" type="text/css" href="UI/styles/main.css">
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">
</head>
	<body>
		<nav>
			<ul>
				<li><img src="UI/styles/logo-xero-blue.svg"></li>
				<li><a href="limits.php">Credit Limits</a></li>
				<li><a href="history.php">History</a></li>
				<li><a href="settings.php">Settings</a></li>
                <li style="float:right"><a href="about.php">About</a></li>
				<li style="float:right"><a href="settings.php">User: <?php echo $_SESSION['user'];?></a> </li> 
			</ul> 
		</nav>
        <h1>Settings</h1>
        
        <?php
            if($apptype=="private")
            {
                echo "<p>You're currently conected to <strong>".$orgname."</strong>.<br>(orgid: ".$_SESSION['orgid'].")</p>";
            }
            else
            {
                if($hasAToken)
                {
                    echo "<p>You're currently conected to <strong>".$orgname."</strong>.<br>(orgid: ".$_SESSION['orgid'].")</p>";
                    echo "<p>To disconnect from this Xero Organisation, please click the disconnect button below.</p>";
                    echo "<br>";
                    echo "<form action='disconnect.php' method='POST'>";
                    echo "	<input type='submit' value='Disconnect'>";
                    echo "</form>";
                }
                else
                {
                    echo "<a href='request_token.php'><img src='UI/styles/connect_xero_button_blue_2x.png' class='center'></a>";              
                }
            }
            ?> 
	</body>
</html>