<?php
	require __DIR__ . '/vendor/autoload.php';
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
		// request with private
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

	if (empty($storage->getOAuthSession())) {

	    $url = new URL($xero, URL::OAUTH_REQUEST_TOKEN);
	    $request = new Request($xero, $url);

	    try {
	        $request->send();
	    } catch (Exception $e) {
	        error_log($e,0);
	        if ($request->getResponse()) {
	            error_log($request->getResponse()->getOAuthResponse(),0);
	        }
	    }
	    $oauth_response = $request->getResponse()->getOAuthResponse();
	    $storage->setOAuthSession(
	        $oauth_response['oauth_token'],
	        $oauth_response['oauth_token_secret']
	    );

		$authorize_url = $xero->getAuthorizeURL($oauth_response['oauth_token']);
		header("Location: " .$authorize_url);
	}

	?>

	<html>
	<head>
		<title>My App</title>
	</head>
	<body>
		Opps! Problem redirecting .....
	</body>
</html>
