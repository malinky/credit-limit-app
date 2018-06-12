<?php
require '../vendor/autoload.php';

date_default_timezone_set('UTC');

//setup Dynamo stuff
require_once('../database.php');

//session data stuff here:
require_once('../storage.php');

$storage = new StorageClass();

//setup Xero stuff here
use XeroPHP\Application\PrivateApplication;
use XeroPHP\Application\PartnerApplication;
use XeroPHP\Remote\Request;
use XeroPHP\Remote\URL;

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
			   'rsa_private_key'       => 'file://../certs/private/privatekey.pem',
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
			   'rsa_private_key'       => 'file://../certs/partner/privatekey.pem',
			   'signature_location'  => \XeroPHP\Remote\OAuth\Client::SIGN_LOCATION_QUERY,
			   ],
			   'curl' => [CURLOPT_USERAGENT   => 'CreditLimits', 'file://../certs/partner/ca-bundle.crt']
	   ];
	   $xero = new PartnerApplication($config);
   }
	

//does the necessary table already exist?
$tables = $dynamodb->getIterator('ListTables');
$tableexists = false;
foreach ($tables as $table) {
	if ($table == 'TenantsToCheck') {
		$tableexists = true;
	}
}

// ensure History table exists
$storage->createTable($dynamodb, "History", "orgid", "invoiceid_timestamp");

//display list of orgs
if ($tableexists) {
	error_log("Running Invoice Status check",0);
	print ('</br>Table Found<ul>');
	
	foreach (getTenantsToCheck($dynamodb, $marshaler) as $tenant) {
		print ('<li><b>'.$tenant.'</b>');

		// remove from the ToCheck table to ensure we clear down to allow the webhook to add the tenant again
		clearTenantsToCheck($dynamodb, $marshaler, $tenant);
		
		if ($storage->getCurrentOrgToken($tenant,$dynamodb,$marshaler)) {
			print ('</br>Found org details in database');

			// if we're running in partner app type do some token checks
			if($apptype=="partner")
			{
				//Get session data
				$oauth_session = $storage->getSession();
				
				$xero->getOAuthClient()
					->setToken($oauth_session['token'])
					->setTokenSecret($oauth_session['token_secret']);
				
				if ($storage->checkToken($xero)) {
					print('</br>Token has expired, we need to renew.');
					// this bit should be in storage.php but can't get it to work
					$url = new URL($xero, URL::OAUTH_ACCESS_TOKEN);
					$request = new Request($xero, $url);
					$request->setParameter("oauth_session_handle",$oauth_session['session_handle']);
					$request->setParameter("oauth_token",$oauth_session['token']);
					$request->send();
					$oauth_response = $request->getResponse()->getOAuthResponse();
					
					$storage->setOAuthTokenSession(
						$oauth_response['oauth_token'],
							$oauth_response['oauth_token_secret'],
							$oauth_response['oauth_expires_in'],
							$oauth_response['oauth_session_handle'],
						(new \DateTime())->format('Y-m-d H:i:s')
					);
					
					// get a fresh xero connection
					$oauth_session = $storage->getSession();
					
					$xero->getOAuthClient()
						->setToken($oauth_session['token'])
						->setTokenSecret($oauth_session['token_secret']);
									
					// get the orgid
					$orgId = $storage->getOrgId($xero);
					// update the database
					$storage->setOAuthTokenTenantsTable(NULL,
						$orgId, 
						$_SESSION['oauth']['token'], 
						$_SESSION['oauth']['token_secret'], 
						$_SESSION['oauth']['expires'],
						$_SESSION['oauth']['session_handle'],
						$_SESSION['oauth']['token_timestamp'], 
						$dynamodb,
						$marshaler);
				} else {
					print('</br>Token good');
				}
			}

			//print some diagnostic info to check everything is ok.....
			print('</br>Getting org name: ');
			$organisations = $xero->load('Accounting\\Organisation')->execute();
			foreach ($organisations as $organisation) {
				print($organisation->Name);		
				$shortcode = $organisation->ShortCode;
				print($shortcode);
			}	
			
			//get the invoices we need to examine
			$invoices = $xero->load('Accounting\\Invoice')
				->where('Status', \XeroPHP\Models\Accounting\Invoice::INVOICE_STATUS_SUBMITTED)
				->where('Type', \XeroPHP\Models\Accounting\Invoice::INVOICE_TYPE_ACCREC)
				->execute();
			print ('</br>Found '.count($invoices).' Invoices');
			
			//get a list of the contacts on those invoices
			$uniqueContacts = array();
			foreach ($invoices as $invoice) {
				$uniqueContacts[$invoice->Contact->ContactID] = $invoice->Contact->ContactID;
			}
			print (' with '.count($uniqueContacts).' unique contacts');
			
			//transform this into comma separated string
			$contactString = implode (",",$uniqueContacts);

			//get detail on these contacts
			$contacts = $xero->loadByGUIDs('Accounting\\Contact',$contactString);
			$contactBalances = array();
			$contactEmails = array();
			foreach ($contacts as $contact) {
				if (isset($contact->Balances)) {
					$contactBalances[$contact->ContactID]=$contact->Balances->AccountsReceivable['Outstanding'];
				} else {
					$contactBalances[$contact->ContactID]=0;
				}
				if (isset($contact->EmailAddress)) {
					$contactEmails[$contact->ContactID]=$contact->EmailAddress;
				}
			}
			print ('<ul>');
			$invoicesToUpdate = array();
			$approvedInvoices = array();
			foreach ($invoices as $invoice) {
				//work out if these will bring them over the limit
				$limit = $storage->getLimit($invoice->Contact->ContactID, $tenant, $dynamodb, $marshaler);
				if(is_null($limit)){$limit=0;}
				$balance = $contactBalances[$invoice->Contact->ContactID];
				$valueOfInvoice = $invoice->Total;
				$potentialNewBalance = $balance + $valueOfInvoice;
				print ('<li>');
				$limitbreached = "false";
				$outcome = "";
				$outcome .= $invoice->InvoiceNumber.' to '.$invoice->Contact->Name.' for '.$invoice->Total.'. Current balance is: '.$balance.', Credit limit is set at: '.$limit.', New balance will be: '.$potentialNewBalance;
				if ($potentialNewBalance > $limit AND $limit > 0) {
					$limitbreached = "true";
					$outcome .= ', Limit will be breached <b>Send to Drafts</b>';
					$invoice->setStatus('DRAFT');
					$invoicesToUpdate[] = $invoice;
				} else {
					$limitbreached = "false";
					$outcome .= ', Customer is within limits <b>Approve and send</b>';
					//increase the balance - as we're approving the invoice
					$contactBalances[$invoice->Contact->ContactID] = $potentialNewBalance;
					$invoice->setStatus('AUTHORISED');
					$invoicesToUpdate[] = $invoice;
					$approvedInvoices[] = $invoice;
				}
				print($outcome);
				print ('</li>');
				// add the result to the history table
				addToHistoryTable($dynamodb, $marshaler, $tenant, $invoice->InvoiceID, $invoice->InvoiceNumber, $invoice->Contact->Name, $outcome, $limitbreached, $shortcode);
			}
			print ('</ul>');

			//update status to AUTHORISED or DRAFT as necessary

			if (count($invoicesToUpdate)>0) {
				$xero->saveAll($invoicesToUpdate);
			}
			//now to send the approved ones	
			if (count($approvedInvoices)>0) {
				foreach ($approvedInvoices as $invoice) {
					$email = $contactEmails[$invoice->Contact->ContactID];
					if(is_null($email) || $email=="")
					{
						print ('</br>No Email');
					}
					else
					{
						print ('</br>Email: '.$email);
						$invoice->sendEmail();
					}
				}
			}

		} else {
			print ('no details found for org');
		}
		print ('</li>');
	}
} else {
	print ('Table not found, please run webhook first');
} 


//function to do the dirty dynamodb work
function getTenantsToCheck($dynamodb,$marshaler) {
	$params = ['TableName' => 'TenantsToCheck'];
	$tenants = array();
	try {
		while (true) {
			$result = $dynamodb->scan($params);
			foreach ($result['Items'] as $i) {
				$tenant = $marshaler->unmarshalItem($i);
				$tenants[$tenant['orgid']] = $tenant['orgid'];
            		}
			if (isset($result['LastEvaluatedKey'])) {
            			$params['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
        		} else {
				return $tenants;
				break;
			}
		}
	} catch (DynamoDbException $e) {
		error_log( "Unable to scan:",0);
		error_log( $e->getMessage(),0);
	}
}

function clearTenantsToCheck($dynamodb, $marshaler, $tenant) {
	$key = $marshaler->marshalJson('
		{
			"orgid": "' . $tenant . '"
		}
	');

	$params = [
		'TableName' => 'TenantsToCheck',
		'Key' => $key
	];

	try {
		$result = $dynamodb->deleteItem($params);
	} catch (DynamoDbException $e) {
		error_log( "Unable to delete item from TenantsToCheck:",0);
		error_log( $e->getMessage(), 0);
	}
}

function addToHistoryTable($dynamodb, $marshaler, $tenant, $invoiceid, $invoicenumber, $contactname, $outcome, $limitbreached, $shortcode) {
	error_log("orgid".$tenant, 0);
	error_log("invoiceid".$invoiceid, 0);
	error_log("invoicenumber".$invoicenumber, 0);
	error_log("contactname".$contactname, 0);
	error_log("outcome".$outcome, 0);
	error_log("limitbreached".$limitbreached, 0);
	error_log("shortcode".$shortcode, 0);
	$item = $marshaler->marshalJson('
		{
			"orgid": "' . $tenant . '",
			"invoiceid_timestamp": "' . time() . '",
			"info": {
				"invoiceid": "' . $invoiceid . '",
				"invoicenumber": "' . $invoicenumber . '",
				"contactname": "' . $contactname . '",
				"outcome": "' . $outcome . '",
				"limitbreached": "' . $limitbreached . '",
				"shortcode": "' . $shortcode . '",
				"timestamp": "' . date('m/d/Y h:i:s a', time()) . '"			
			}
		}
	');

	$params = [
		'TableName' => 'History',
		'Item' => $item
	];

	try {
		$result = $dynamodb->putItem($params);
		error_log( "Added item to History",0);

	} catch (DynamoDbException $e) {
		error_log( "Unable to add item to History:",0);
		error_log( $e->getMessage(), 0);
	}
}
?>
