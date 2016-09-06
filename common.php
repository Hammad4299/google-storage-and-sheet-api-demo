<?php
	require_once __DIR__ . '/vendor/autoload.php';
	require_once 'private/config.php';
	require_once 'private/db.php';
	ini_set('session.gc_maxlifetime', 36000);

	// each client should remember their session id for EXACTLY 1 hour
	session_set_cookie_params(36000);	
	session_start();
	
	$client = new Google_Client();
	$client->setAuthConfigFile(realpath(__DIR__.'/private/client_secret.json'));
	$client->setAccessType("offline");
	$guzzleClient = new \GuzzleHttp\Client(array( 'curl' => array( CURLOPT_SSL_VERIFYPEER => false ) ));
	$client->setHttpClient($guzzleClient);
	$client->addScope(Google_Service_Storage::DEVSTORAGE_FULL_CONTROL);
	$client->addScope("https://www.googleapis.com/auth/spreadsheets");
	$client->addScope("https://www.googleapis.com/auth/drive");
	$client->setRedirectUri($redirectUri);
?>