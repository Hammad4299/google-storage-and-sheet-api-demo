<?php
	require_once('common.php');
	$accessToken = null;

	function setAccessToken(){
		global $accessToken;
		global $client;
		global $dbh;
		
		$accessToken = $_SESSION['token'];
		$stmt = $dbh->prepare('select * from tokens where email = ?;');
		$stmt->execute([$_SESSION['email']]);

		while(($row = $stmt->fetch())!=null){
			$dd = json_decode($row['refresh_token'],true);
			$accessToken = $client->refreshToken($dd['refresh_token']);
			$client->setAccessToken($accessToken);
			break;
		}
	}

	// $dd = json_decode($dd,true);	
	// $accessToken = $client->refreshToken($dd['refresh_token']);
	// $accessToken = $_SESSION['token'];//$client->getAccessToken();
	// $client->setAccessToken($accessToken);
?>