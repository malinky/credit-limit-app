<?php

use XeroPHP\Remote\Request;
use XeroPHP\Remote\URL;

class StorageClass
{
	function __construct() {
		if( !isset($_SESSION) ){
        	$this->init_session();
		}
   	}

   	public function init_session(){
    	session_start();
	}

	public function getSession() {
		return $_SESSION['oauth'];
	}

	public function startSession($token, $secret, $expires = null)
	{
	session_start();
	}

    public function setOAuthSession($token, $secret, $expires = null)
	{

		// expires sends back an int
	    if ($expires !== null) {
	        $expires = time() + intval($expires);
	    }
		
	    $_SESSION['oauth'] = [
	        'token' => $token,
	        'token_secret' => $secret,
	        'expires' => $expires
	    ];
	}

	public function setOAuthTokenSession($token, $secret, $expires = null,$session_handle,$token_timestamp)
	{
	    if ($expires !== null) {
	        $expires = time() + intval($expires);
	    }
	    $_SESSION['oauth'] = [
	        'token' => $token,
	        'token_secret' => $secret,
	        'expires' => $expires,
	        'session_handle' => $session_handle,
	        'token_timestamp' => $token_timestamp
		];
	}

	public function getOAuthSession()
	{
	    //If it doesn't exist or is expired, return null
	    if (!empty($this->getSession())
	        || ($_SESSION['oauth']['token_timestamp'] !== null
	        && $_SESSION['oauth']['token_timestamp'] <= time())
	    ) {
	        return null;
	    }
	    return $this->getSession();
	}

	public function checkToken($xero) 
	{

		if (!empty($this->getSession()) || ($_SESSION['oauth']['token_timestamp'] !== null)) 
		{
			$expire = date("Y-m-d H:i:s", strtotime("-30 minutes"));
			$tokenTimestamp = $_SESSION['oauth']['token_timestamp'];

			if ($expire > $tokenTimestamp) {
		  		return true;
			} else {
				return false;
			}

				
		} else {
			return true;
		}
			
	}

	public function refreshToken($xero)
	{					
		//$xero->getOAuthClient();
		$url = new URL($xero, URL::OAUTH_ACCESS_TOKEN);
        	$request = new Request($xero, $url);
        	$request->setParameter("oauth_session_handle",$oauth_session['session_handle']);
        	$request->setParameter("oauth_token",$oauth_session['token']);

			try{
				$request->send();
				$oauth_response = $request->getResponse()->getOAuthResponse();
				$this->setOAuthTokenSession(
            		$oauth_response['oauth_token'],
					$oauth_response['oauth_token_secret'],
					$oauth_response['oauth_expires_in'],
					$oauth_response['oauth_session_handle'],
					(new \DateTime())->format('Y-m-d H:i:s')
				);
			} catch (Exception $e)
			{
				// didn't work, clear down token and let the user start again
				header("Location: disconnect.php");
			}

	}

	public function setOAuthTokenTenantsTable($user = NULL, $orgId, $token, $secret, $expires, $session_handle, $token_timestamp, $dynamodb, $marshaler)
	{	
		if (!is_null($user)) {		
			// add to UserTenants
			error_log("Adding item to UserTenants...", 0);
			$item = $marshaler->marshalJson('
				{
					"username": "' . $user . '",
					"info": {
						"orgid": "' . $orgId . '"
					}
				}
			');

			$params = [
				'TableName' => 'UserTenants',
				'Item' => $item
			];

			try {
				$result = $dynamodb->putItem($params);
				error_log("Added item to UserTenants", 0);

			} catch (DynamoDbException $e) {
				error_log("Unable to add item to UserTenants:", 0);
				error_log($e->getMessage(), 0);
			}
		}

		// add to Tenants
		error_log("Adding item to Tenants...", 0);
		error_log("OrgID: " . $orgId, 0);
		error_log("token: " . $token, 0);
		error_log("token_secret: " . $secret, 0);
		error_log("expires: " . $expires, 0);
		error_log("session_handle: " . $session_handle, 0);
		error_log("token_timestamp: " . $token_timestamp, 0);

		$item = $marshaler->marshalJson('
			{
				"orgid": "' . $orgId . '",
				"info": {
					"token": "' . $token . '",
					"token_secret": "' . $secret . '",
					"expires": "' . $expires . '",
					"session_handle": "' . $session_handle . '",
					"token_timestamp": "' . $token_timestamp . '"
				}
			}
		');

		$params = [
			'TableName' => 'Tenants',
			'Item' => $item
		];

		try {
			$result = $dynamodb->putItem($params);
			error_log("Added item to Tenants", 0);

		} catch (DynamoDbException $e) {
			error_log("Unable to add item to Tenants:", 0);
			error_log($e->getMessage(), 0);
		}
	}

	public function deleteOAuthTokenTenantsTable($user, $orgid, $dynamodb, $marshaler)
	{			
		error_log("Deleting user and token Limits table...", 0);
		error_log("user: " . $user, 0);
		error_log("orgid: " . $orgid, 0);

		// delete from UserTenants
		error_log("Deleting item from UserTenants...", 0);
		$key = $marshaler->marshalJson('
			{
				"username": "' . $user . '"
			}
		');

		$params = [
			'TableName' => 'UserTenants',
			'Key' => $key
		];

		try {
			$result = $dynamodb->deleteItem($params);
			error_log("Deleted item from UserTenants", 0);

		} catch (DynamoDbException $e) {
			error_log("Unable to delete item from UserTenants:", 0);
			error_log($e->getMessage(), 0);
		}	

		// delete from Tentants
		error_log("Deleting item from Tenants...", 0);
		$key = $marshaler->marshalJson('
			{
				"orgid": "' . $orgid . '"
			}
		');

		$params = [
			'TableName' => 'Tenants',
			'Key' => $key
		];

		try {
			$result = $dynamodb->deleteItem($params);
			error_log( "Deleted item from Tenants", 0);

		} catch (DynamoDbException $e) {
			error_log( "Unable to delete item from Tenants:", 0);
			error_log( $e->getMessage(), 0);
		}		
	}

	public function getOrgId($xero)
	{
		$xero->getOAuthClient();
		$organisations = $xero->load('Accounting\\Organisation')->execute();
		//error_log("orgid:".$organisations[0]["OrganisationID"], 0);
		return $organisations[0]["OrganisationID"];
	}
	public function getOrgName($xero)
	{
		$xero->getOAuthClient();
		$organisations = $xero->load('Accounting\\Organisation')->execute();
		//error_log("orgid:".$organisations[0]["OrganisationID"], 0);
		return $organisations[0]["Name"];
	}

	public function getOrgIdFromUserTenants($user, $dynamodb, $marshaler)
	{
		$key = $marshaler->marshalJson('
			{
				"username": "' . $user . '"
			}
		');
		
		$params = [
			'TableName' => "UserTenants",
			'Key' => $key,
			"AttributesToGet" => array("info")
		];
		
		try 
		{
			$result = $dynamodb->getItem($params);								
		} 
		catch (DynamoDbException $e) {
			error_log( "Unable to get item from UserTenants:",0);
			error_log( $e->getMessage(), 0);
		}		
				
		if($result["Item"] === NULL)
		{
			/// no record, no token, return false
			return NULL;
		}
		else
		{
			//success - retrieve orgid based on user
			$usertenant = $marshaler->unmarshalItem($result["Item"]);
			$orgid = $usertenant['info']['orgid'];
			return $orgid;
		}
	}

	public function getCurrentUserToken($user, $dynamodb, $marshaler)
	{		
		$key = $marshaler->marshalJson('
			{
				"username": "' . $user . '"
			}
		');
		
		$params = [
			'TableName' => "UserTenants",
			'Key' => $key,
			"AttributesToGet" => array("info")
		];
		
		try 
		{
			$result = $dynamodb->getItem($params);								
		} 
		catch (DynamoDbException $e) {
			error_log( "Unable to get item from UserTenants:",0);
			error_log( $e->getMessage(),0);
		}		
				
		if($result["Item"] === NULL)
		{
			/// no record, no token, return false
			return false;
		}
		else
		{
			//success - retrieve token based on org id
			$usertenant = $marshaler->unmarshalItem($result["Item"]);
			$orgid = $usertenant['info']['orgid'];
			return $this->getCurrentOrgToken($orgid, $dynamodb, $marshaler);
		}
	}

	public function getCurrentOrgToken($orgid, $dynamodb, $marshaler)
	{						
		// call the db for the token
		$key = $marshaler->marshalJson('
			{
				"orgid": "' . $orgid . '"
			}
		');		
		
		$params = [
			'TableName' => "Tenants",
			'Key' => $key,
			"AttributesToGet" => array("info")
		];

		try 
		{
			$result = $dynamodb->getItem($params);								
		} 
		catch (DynamoDbException $e) {
			error_log( "Unable to get item from Tenants:",0);
			error_log( $e->getMessage(), 0);
		}
		
		if($result["Item"] === NULL)
		{
			/// no token, return false
			return false;
		}
		else
		{
			$usertenant = $marshaler->unmarshalItem($result["Item"]);
			$token = $usertenant['info']['token'];
			$secret = $usertenant['info']['token_secret'];
			$expires = $usertenant['info']['expires'];
			$session_handle = $usertenant['info']['session_handle'];
			$token_timestamp = $usertenant['info']['token_timestamp'];

			$this->setOAuthTokenSession($token, $secret, $expires, $session_handle, $token_timestamp);
			return true;
		}	

		return false;
	}

	public function createTable($dynamodb, $tablename, $primarykey, $sortkey)
	{
		$iterator = $dynamodb->getIterator('ListTables');
		$tableExists = 0;
		foreach ($iterator as $iteratorTableName) {
			if($tablename == $iteratorTableName)
			{
				error_log($tablename . " table exists", 0);
				$tableExists = 1;
			}
		}
		
		if($tableExists == 0)
		{
			error_log("Creating " . $tablename . " table \n", 0);
			if($sortkey === NULL)
			{
				$params = [
					'TableName' => $tablename,
					'KeySchema' => [
						[
							'AttributeName' => $primarykey,
							'KeyType' => 'HASH'  //Partition key
						]
					],
					'AttributeDefinitions' => [
						[
							'AttributeName' => $primarykey,
							'AttributeType' => 'S'
						]
				
					],
					'ProvisionedThroughput' => [
						'ReadCapacityUnits' => 10,
						'WriteCapacityUnits' => 10
					]
				];
			}
			else
			{
				$params = [
					'TableName' => $tablename,
					'KeySchema' => [
						[
							'AttributeName' => $primarykey,
							'KeyType' => 'HASH'  //Partition key
						],
						[
							'AttributeName' => $sortkey,
							'KeyType' => 'RANGE'  //Sort key
						]
					],
					'AttributeDefinitions' => [
						[
							'AttributeName' => $primarykey,
							'AttributeType' => 'S'
						],
						[
							'AttributeName' => $sortkey,
							'AttributeType' => 'S'
						]				
					],
					'ProvisionedThroughput' => [
						'ReadCapacityUnits' => 10,
						'WriteCapacityUnits' => 10
					]
				];
			}
		
			try {
				$result = $dynamodb->createTable($params);
				error_log("Created " . $tablename . " table. Status: " . 
					$result['TableDescription']['TableStatus'] ."\n", 0);
		
			} catch (DynamoDbException $e) {
				error_log("Unable to create " . $tablename . " table:\n", 0);
				error_log( $e->getMessage(), 0);
			}
		}	
	}
	
	public function setLimit($contactid, $orgid, $limit, $dynamodb, $marshaler)
	{
		error_log("Adding item to Limits table...", 0);
		error_log("contactid: " . $contactid, 0);
		error_log("orgid: " . $orgid, 0);
		error_log("limit: " . $limit, 0);
		$item = $marshaler->marshalJson('
			{
				"contactid": "' . $contactid . '",
				"orgid": "' . $orgid . '",
				"info": {
					"limit": "' . $limit . '"
				}
			}
		');

		$params = [
			'TableName' => 'Limits',
			'Item' => $item
		];

		try {
			$result = $dynamodb->putItem($params);
			error_log( "Added item to Limits",0);

		} catch (DynamoDbException $e) {
			error_log( "Unable to add item to Limits:",0);
			error_log( $e->getMessage(), 0);
		}
	}
	
	public function getLimit($contactid, $orgid, $dynamodb, $marshaler)
	{						
		// call the db for the token
		$key = $marshaler->marshalJson('
			{
				"contactid": "' . $contactid . '",
				"orgid": "' . $orgid . '"
			}
		');		
		
		$params = [
			'TableName' => "Limits",
			'Key' => $key,
			"AttributesToGet" => array("info")
		];

		try 
		{
			$result = $dynamodb->getItem($params);								
		} 
		catch (DynamoDbException $e) {
			error_log("Unable to get item from Limits:", 0);
			error_log($e->getMessage() , 0);
		}
		
		if($result["Item"] === NULL)
		{
			/// no record, return 0
			return null;
		}
		else
		{
			$limitItem = $marshaler->unmarshalItem($result["Item"]);
			$limit = $limitItem['info']['limit'];
			return $limit;
		}	

		return null;
	}

	public function getHistory($orgid, $dynamodb, $marshaler)
	{			
		error_log("Getting history for...", 0);
		error_log("orgid: " . $orgid, 0);			
		// call the db for the token
		$eav = $marshaler->marshalJson('
			{
				":orgid": "'.$orgid.'"
			}
		');
	
		$params = [
			'TableName' => "History",
			'KeyConditionExpression' => 'orgid = :orgid',
			'ExpressionAttributeValues'=> $eav,
			'ScanIndexForward' => false
		];

		try 
		{
			$result = $dynamodb->query($params);								
		} 
		catch (DynamoDbException $e) {
			error_log("Unable to get from History:", 0);
			error_log($e->getMessage() , 0);
		}
		
		if($result === NULL)
		{
			/// no record, return 0
			return null;
		}
		else
		{
			return $result;
		}	

		return null;
	}
}
?>
