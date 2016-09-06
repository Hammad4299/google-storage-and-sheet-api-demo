<?php
	require_once('common.php');
	require_once('set-access-token.php');
	set_time_limit(0);
	
	
	$storage = new Google_Service_Storage($client);
	$bucket = $storage->buckets->get($activeBucket, array('projection' => 'full'));
	$x = 0;
	foreach($storage->objects->listObjects($bucket['name'])['items'] as $obj){
		$acl = new Google_Service_Storage_ObjectAccessControl($client);
		$acl->setEntity('allUsers');
        $acl->setRole('READER');
		$storage->objectAccessControls->insert($bucket['name'],$obj['name'],$acl);
		++$x;
	}
?>