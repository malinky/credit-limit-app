<?php
	require __DIR__ . '/vendor/autoload.php';
	require_once('database.php');
	require_once('storage.php');

	use XeroPHP\Application\PrivateApplication;
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
        // running in Private app mode
        $config = [
            'oauth' => [
                'callback'        => $callback,	
                'consumer_key'    => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'rsa_private_key'       => 'file://certs/private/privatekey.pem',
                ]
        ];
		$xero = new PrivateApplication($config);
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
		$xero = new PartnerApplication($config);

		//Get session data
		$oauth_session = $storage->getSession();

		$xero->getOAuthClient()
			->setToken($oauth_session['token'])
			->setTokenSecret($oauth_session['token_secret']);

		// If token has expired - renew
		if($storage->checkToken($xero))
		{
			error_log("Token has expired for user ".$_SESSION['user'].", we need to renew.", 0);
			$storage->refreshToken($xero);		
			// update the database
			$storage->setOAuthTokenTenantsTable($_SESSION['user'], $_SESSION['orgid'], $_SESSION['oauth']['token'], $_SESSION['oauth']['token_secret'], $_SESSION['oauth']['expires'],$_SESSION['oauth']['session_handle'],$_SESSION['oauth']['token_timestamp'], $dynamodb, $marshaler);
			// get a fresh xero connection for further use
			$oauth_session = $storage->getSession();
			$xero->getOAuthClient()
				->setToken($oauth_session['token'])
				->setTokenSecret($oauth_session['token_secret']);
		}

		if (!isset( $oauth_session['token'])) {
			// No token - redirect to settings page so that the user can reconnect
			header("Location: settings.php");
		}
    }		

	// get the org short code so we can do some deep linking
	$organisations = $xero->load('Accounting\\Organisation')->execute();
	$shortcode = $organisations[0]["ShortCode"];	

	// is this is form post back, i.e. did the user update a credit limit?
	if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')
	{
		// loop through the list of contacts, for each contact check the form post back and get the credit limit, and write to the database
		$contacts = $xero->load('Accounting\\Contact')->where('IsCustomer', True)->execute();
			foreach ($contacts as $contact) {
				$limit = $_POST['credit'.$contact->ContactID];
				if ($limit !== "" && $limit !== 0)
				{
					$storage->setLimit($contact->ContactID, $_SESSION['orgid'], $limit, $dynamodb, $marshaler);
				}		
			}
	}
?>

<html>
<head>
	<title>XD18 | Credit Limits</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/png" href="UI/favicon.ico">
	<link rel="stylesheet" type="text/css" href="UI/styles/normalize.css">
	<link rel="stylesheet" type="text/css" href="UI/styles/main.css">
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">

	<!-- return to top button -->	

	<script>
		// When the user scrolls down 20px from the top of the document, show the button
		window.onscroll = function() {scrollFunction()};

		function scrollFunction() {
		    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
		        document.getElementById("myBtn").style.display = "block";
		    } else {
		        document.getElementById("myBtn").style.display = "none";
		    }
		}

		// When the user clicks on the button, scroll to the top of the document
		function topFunction() {
		    document.body.scrollTop = 0;
		    document.documentElement.scrollTop = 0;
		}
	</script>

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

		<button onclick="topFunction()" id="myBtn" title="Go to top">Top</button>

		<h1>Credit Limits</h1>

		<form action="" method="POST">

		<table>
			<tr>
			    <th>Contact</th>
			    <th>Credit Limit (&pound;)</th> 
			 </tr>
			<?php
				$contacts = $xero->load('Accounting\\Contact')->where('IsCustomer', True)->execute();
				foreach ($contacts as $contact) {
					// get the current limit for this contact
					$limit = $storage->getLimit($contact->ContactID, $_SESSION['orgid'], $dynamodb, $marshaler);
					echo "
					<tr>
						<td><a href='https://go.xero.com/organisationlogin/default.aspx?shortcode=".$shortcode."&redirecturl=/Contacts/View/".$contact->ContactID."' target='_blank'>".$contact->Name."</a></td>
						<td>&pound; <input type='number' name='credit".$contact->ContactID."' value='".$limit."'></td>
					</tr>
					";	
				}
			?>
		</table>

		<input type="submit" value="Submit">
		</form>

	</body>
</html>
