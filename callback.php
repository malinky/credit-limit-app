<?php
	session_start();
	require __DIR__ . '/vendor/autoload.php';
	require_once('database.php');
	require_once('storage.php');

	use XeroPHP\Application\PartnerApplication;
	use XeroPHP\Remote\Request;
	use XeroPHP\Remote\URL;

	$storage = new StorageClass();	

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
		// we dont need to worry about private, we can only hit this
		// .php file if we're running in partner - there is no token to 
		// exchange with private
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
	
	$xero = new PartnerApplication($config);

	//get session data
	$oauth_session = $storage->getSession();

	// Check if current session has a token
	if (isset( $oauth_session['token'])) {
	    $xero->getOAuthClient()
	        ->setToken($oauth_session['token'])
	        ->setTokenSecret($oauth_session['token_secret']);
	    
	    if (isset($_REQUEST['oauth_verifier'])) {
	    	error_log("Verifier Found - Swap Request for Access token", 0);   
	        $xero->getOAuthClient()->setVerifier($_REQUEST['oauth_verifier']);
	        $url = new URL($xero, URL::OAUTH_ACCESS_TOKEN);
	        $request = new Request($xero, $url);
	        $request->send();
	        $oauth_response = $request->getResponse()->getOAuthResponse();
	     
	        $storage->setOAuthTokenSession(
		        $oauth_response['oauth_token'],
	            	$oauth_response['oauth_token_secret'],
	            	$oauth_response['oauth_expires_in'],
	            	$oauth_response['oauth_session_handle'],
				(new \DateTime())->format('Y-m-d H:i:s')
		    );  
		}
	} else {
		// send to settings page for user to reconnect
	    header("Location: settings.php?error=true");
	}
	
	// get a fresh xero object now we've exchanged tokens
	$oauth_session = $storage->getSession();
	$xero->getOAuthClient()
		->setToken($oauth_session['token'])
		->setTokenSecret($oauth_session['token_secret']);

	// get the orgid
	$orgId = $storage->getOrgId($xero);
	$_SESSION['orgid'] = $orgId;	

	// store the token in the database
	$storage->setOAuthTokenTenantsTable($_SESSION['user'], $_SESSION['orgid'], $oauth_session['token'], $oauth_session['token_secret'], $oauth_session['expires'],$oauth_session['session_handle'],$oauth_session['token_timestamp'], $dynamodb, $marshaler);
  
	// take the user to the main app page
	header("Location: limits.php");
	exit;
?>
<html>
	<head>
	<title>CreditLimits app</title>
	</head>
	<body>		
		Opps! Should have redirected to <a href="limits.php">to this page</a>
	</body>
</html>
