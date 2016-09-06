<?php
	require_once 'common.php';
	if(isset($_REQUEST['email'])){
		$_SESSION['email'] = $_REQUEST['email'];
		$auth_url = $client->createAuthUrl();
		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
	}
?>

<form method='post'>
	<label>Your Google Drive Account</label> : <input type='email' name='email' placeholder='abc@gmail.com' />
	<button>Continue</button>
</form>