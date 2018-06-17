<?php
// As we can't return any content to Xero, this PHP script outputs the request to a text file this helps us debug any issues
// Based on the script found here:
// https://gist.github.com/magnetikonline/650e30e485c0f91f2f40
//everything we want to see gets written to $data
require '../vendor/autoload.php';
date_default_timezone_set('UTC');
use Aws\DynamoDb\Exception\DynamoDbException;
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
	$webhook_key = getenv('webhook_key');
}
else
{
	// we're running on aws, get settings from server variables
	$sdk = new Aws\Sdk([
		'region'   => $_SERVER['region'],
		'version'  => 'latest',
	]);
	$webhook_key = $_SERVER['webhook_key'];
}	

$dynamodb = $sdk->createDynamoDb();
		
//headers
$data = sprintf(
	"%s %s %s\n\nHTTP headers:\n",
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	$_SERVER['SERVER_PROTOCOL']
);
foreach (getHeaderList() as $name => $value) {
	$data .= $name . ': ' . $value . "\n";
}
//get the payload
$payload = file_get_contents('php://input');
$jsonpayload = json_decode($payload);
$data .= "\nRequest body:\n";
$data .= $payload . "\n";
//calculate our signature
$data .= "\nOur Signature:\n";
$calculatedhash = base64_encode(hash_hmac('sha256',$payload,$webhook_key,true));
$data .= $calculatedhash;
//display what Xero has sent
$data .= "\nXero Sig:\n";
$xerohash = $_SERVER['HTTP_X_XERO_SIGNATURE'];
$data .= $xerohash;
		
//see if they match
$data .= "\nMatch?:\n";
if (hash_equals($calculatedhash,$xerohash)) {
	$data .= "Yes";
	http_response_code(200);
	//extract the org ids from the payload
	$tenants = array();
	foreach ($jsonpayload->events as $event) {
		//check it is related to an Invoice - weed out any Contact updates
		if ($event->eventCategory == "INVOICE") {
			//add to array
			$tenants[$event->tenantId] = $event->tenantId;
		}
	}
	//output list of orgs to our debug file
	$data .= "\n\nOrganisations (no duplicates):";
	foreach ($tenants as $tenant) {
		$data .= "\n".$tenant;
	}
	//does table exist?
	if (checkForTable($dynamodb)) {
		$data .= "\n\nTable Exists";
		//add items to table
		foreach ($tenants as $tenant) {
			addToTable($tenant,$dynamodb);
		}
	} else {
		$data .= "\n\nTable Error";
	}
	
	
} else {
	$data .= "No";
	http_response_code(401);
}
//format filename
$fn = microtime();
$fn = substr($fn,11) . substr($fn,2,8);
		
//output to file
//file_put_contents('./'.$fn.'.txt',$data);
			
function getHeaderList() {
	$headerList = [];
	foreach ($_SERVER as $name => $value) {
		if (preg_match('/^HTTP_/',$name)) {
			$headerList[$name] = $value;
		}
	}
	return $headerList;
}
function checkForTable($dynamodb) {
	$tables = $dynamodb->getIterator('ListTables');
	$tableexists = false;
	foreach ($tables as $table) {
		if ($table == 'TenantsToCheck') {
			$tableexists = true;
		}
	}
	if ($tableexists == false) {
		$params = [
		    'TableName' => 'TenantsToCheck',
		    'KeySchema' => [
			[
			    'AttributeName' => 'orgid',
			    'KeyType' => 'HASH'  //Partition key
			]
		    ],
		    'AttributeDefinitions' => [
			[
			    'AttributeName' => 'orgid',
			    'AttributeType' => 'S'
			]
		
		    ],
		    'ProvisionedThroughput' => [
			'ReadCapacityUnits' => 10,
			'WriteCapacityUnits' => 10
		    ]
		];
		try {
		    $result = $dynamodb->createTable($params);
			return true;
		} catch (DynamoDbException $e) {
			return false;
		}
	} else {
		return true;
	}
}
function addToTable($tenant,$dynamodb) {
	global $data;
	$marshaler = new Marshaler();
	$json = json_encode(['orgid' => $tenant]);
	$data .= $tenant;
	$params = [
		'TableName' => 'TenantsToCheck',
		'Item' => $marshaler->marshalJson($json)
	];
	
	try {
        	$result = $dynamodb->putItem($params); 	
    	} catch (DynamoDbException $e) {
	        //break;
    	}
	
}
?>
