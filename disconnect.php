<?php
	session_start();
	
	require __DIR__ . '/vendor/autoload.php';
	require_once('database.php');
	require_once('storage.php');
	
	$storage = new StorageClass();	

	unset($_SESSION['oauth']['token']);
	unset($_SESSION['oauth']['token_secret']);
	$_SESSION['oauth']['expires'] = null;

	$storage->deleteOAuthTokenTenantsTable($_SESSION['user'], $_SESSION['orgid'], $dynamodb, $marshaler);	

	header("Location: settings.php");
	exit;
?>