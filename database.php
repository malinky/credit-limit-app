<?php
	use Aws\DynamoDb\Marshaler;

	$env = getenv('environment');
	if($env == "local") {
		// getting settings from httpd.conf
		$sdk = new Aws\Sdk([
			'region'   => getenv('region'),
			'version'  => 'latest',
			'endpoint' => 'http://localhost:8000',
			'credentials' => [
				'key' => 'not-a-real-key',
				'secret' => 'not-a-real-secret',
			],
		]);
	}
	else
	{
		// we're running on aws, get settings from server variables
		$sdk = new Aws\Sdk([
			'region'   => $_SERVER['region'],
			'version'  => 'latest',
		]);
	}	

	$dynamodb = $sdk->createDynamoDb();
	$marshaler = new Marshaler();
?>
