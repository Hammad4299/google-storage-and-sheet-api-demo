<?php
	require_once 'common.php';
	$client->authenticate($_GET['code']);
	$access_token = $client->getAccessToken();
	$client->setAccessToken($access_token);
	$dec = $access_token;

	if(isset($dec['refresh_token'])){
		$stmt = $dbh->prepare('delete from tokens where email = ?;');
		$stmt->execute([$_SESSION['email']]);
		$stmt = $dbh->prepare('insert into tokens values(?,?);');
		$stmt->execute([$_SESSION['email'],json_encode($access_token)]);
	}
	
	$_SESSION['token'] = $access_token;
	header('Location: update-file.php');
?>