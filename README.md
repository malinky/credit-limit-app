# xero-php sample app

This is a sample app for the Xero Developer Roadshow and XDHax 2018

<description of app>

### Xero App
This sample app uses a Xero [Public](http://developer.xero.com/documentation/auth-and-limits/public-applications/) App.

Go to [http://app.xero.com](http://app.xero.com) and login with your Xero user account to create a Xero Public app. You'll set the callback URL and get your consumer key & secret.

### Setup App
Download this repo and place in your webroot.

Open your terminal and download dependencies with Composer.

	composer update

### Environment Variables

We set env vars on AWS: 
* by going to the environment in AWS, 
* then config, 
* then software > modify, 
* then adding the following:
```
apptype private
region us-east-1
client_id 3b1ulnbt2qfccvcr75f3onucpo
userpool_id eu-west-1_hHpUeHeX6
Callback http://creditlimits-env.ezfg7z42ma.eu-west-1.elasticbeanstalk.com/callback.php
consumer_key AV0VZJQ3AYKPRVEK8T5HCCEANVMMDW
consumer_secret QACGKM3ONYMHXDRZD8XOXUDVUCJBEM
webhook_key ItqYapFcNhtaKt4cFv9fxkcLHnf9RChtiQDlrO6k81Y6eifq2d5sqZgjzwlt3ZmEOWxidoN71Lq9OWnhIQlNTw==
```

We set env vars on local by adding the following to httpd.conf, which can be found in the MAMP/LAMP application folder. Simply paste this at the end of the file and restart MAMP/LAMP:

```
SetEnv apptype private
SetEnv environment local
SetEnv region us-east-1
SetEnv client_id 4flm8lpptrevvbrnstdifmjur9
SetEnv userpool_id us-east-1_8vdTdIYXB
SetEnv credentials_key AKIAJIAKTLKFXTIJ5XGQ
SetEnv credentials_secret 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
SetEnv callback http://localhost:8888/CreditLimits/callback.php
SetEnv consumer_key AV0VZJQ3AYKPRVEK8T5HCCEANVMMDW
SetEnv consumer_secret QACGKM3ONYMHXDRZD8XOXUDVUCJBEM
SetEnv webhook_key ItqYapFcNhtaKt4cFv9fxkcLHnf9RChtiQDlrO6k81Y6eifq2d5sqZgjzwlt3ZmEOWxidoN71Lq9OWnhIQlNTw==
```

### Configure
You'll need to set the `Config` values in the following files.

* request_token.php
* callback.php
* get.php

```php
	$config = [
		'oauth' => [
			'callback'        => 'http://localhost/myapp/callback.php',
			'consumer_key'    => 'YOUR_CONSUMER_KEY',
			'consumer_secret' => 'YOUR_CONSUMER_SECRET',
			'signature_location'  => \XeroPHP\Remote\OAuth\Client::SIGN_LOCATION_QUERY,
			],
		'curl' => [
			CURLOPT_USERAGENT   => 'xero-php sample app',
			CURLOPT_CAINFO => __DIR__.'/certs/ca-bundle.crt',
		],
	];
```

## Acknowledgement

Special thanks to [Michael Calcinai](https://github.com/calcinai) for all his work on the [xero-php](https://github.com/calcinai/xero-php) SDK
  

## License

This software is published under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).

	Copyright (c) 2018 Xero Limited

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
