<?php
	require_once('common.php');
	require_once('set-access-token.php');
	set_time_limit(0);

	$cols = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U",'V','W','X','Y','Z'];
	$colsInd = [];
	for($x=0;$x<count($cols);++$x){
		$colsInd[$cols[$x]] = $x;
	}
	$msgs = [];
	$msgs['messages'] = [];
	$msgs['processedSuccessfully'] = [];

	// $service = new Google_Service_Sheets($client);
	// $sheetID = '1CaPxv3E8ua7y5oV_G4M_5JjxZB0JXYIe_oFOKHA7RlU';
	// $resp = $service->spreadsheets->get($sheetID,[
	// 	'includeGridData' => true
	// ]);
	// $rowCount = 1000;
	// print_r($resp->getSheets());
	// die();
	$spreadSheets = [];
	$folderPaths = [];
	if(isset($_REQUEST['data'])){
		$spreadSheets = json_decode($_REQUEST['data'],true);
		$service = new Google_Service_Sheets($client);
		foreach ($spreadSheets as $sheet) {
			setAccessToken();
			updateGoogleSheetLinks($sheet);
			sleep(3);
		}
		echo json_encode($msgs);
		die();
	} else if(isset($_REQUEST['folder'])){
		$service = new Google_Service_Drive($client);
		$folderPaths[null] = 'My Drive';
		//getDriveSpreadSheets("application/vnd.ms-excel");
		//getDriveSpreadSheets("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		setAccessToken();
		getDriveSpreadSheets("application/vnd.google-apps.spreadsheet");
		setAccessToken();
		autoDetectSheetsColumns();
	}

	function updateGoogleSheetLinks($sheet){
		global $service;
		global $client;
		global $msgs;
		global $cols;
		global $colsInd;

		$sheetID = $sheet['id'];
		$resp = $service->spreadsheets->get($sheetID,[
			'includeGridData' => true
		]);
		$rowCount = 1000;
		$sid = null;
		
		try{
			$props=$resp->getSheets()[0]->getProperties();
			$sid = $props->getSheetId();
			$rowCount = $props->getGridProperties()->getRowCount();
		}catch(Exception $e){
			$rowCount = 1000;
			$msgs['messages'][$sheetID] = $e->getMessage();
		}

		$requests = [];
		$imgCol = $sheet['imgCol'];
		$rexCol = $sheet['rexCol'];
		if(strlen($imgCol)==0 || strlen($rexCol)==0)
			return;

		try{
			$requests = [];

				$requests[] = new Google_Service_Sheets_Request(array(
					'repeatCell' => array(
						'range' => array(
							'sheetId' => $sid,
							'startRowIndex' => 1,
							'startColumnIndex' => $colsInd[$imgCol],
							'endRowIndex' => $rowCount,
							'endColumnIndex' => $colsInd[$imgCol]+1
						),
						'cell' => array('userEnteredValue' => array('formulaValue' => '=image("https://storage.googleapis.com/tct-test-mages/" &  '.$rexCol.'2 & "--1.jpg")')),
						'fields' => 'userEnteredValue'
					)
				));

					$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
						'requests' => $requests
					));

					$service->spreadsheets->batchUpdate($sheetID, $batchUpdateRequest);
					$requests = [];

			array_push($msgs['processedSuccessfully'],$sheetID);
		}
		catch(Exception $e){
			$msgs['messages'][$sheetID] = $e->getMessage();
		}
	}

	function autoDetectSheetsColumns(){
		global $cols;
		global $service;
		global $client;
		global $spreadSheets;
		global $folderPaths;
		$service = new Google_Service_Sheets($client);
		
		foreach($spreadSheets as $index => $sheet){	
			$id = $sheet['id'];
			$range = "2:20";
			try{
				$response = $service->spreadsheets_values->get($id, $range,['valueRenderOption'=>'FORMULA']);
			
				$values = $response->getValues();
				$spreadSheets[$index]['imgCol'] = null;
				$spreadSheets[$index]['rexCol'] = null;

				$found = false;
				foreach ($values as $row) {
					$x=0;
					foreach($row as $col){
						if($x>=count($cols)){
							break;
						}
						
						preg_match_all ("/^=image.*&[ ]*([a-zA-Z]*)[0-9][ ]*&/i", $col, $pat_array);
						if(count($pat_array[1])!=0){
							$spreadSheets[$index]['imgCol'] = $cols[$x];
							$spreadSheets[$index]['rexCol'] = $pat_array[1][0];
							$found = true;
							break;
						}
						$x++;
					}
					if($found)
						break;
				}
			}catch(Exception $e){
				echo $e->getMessage()."<br/>";
			}
		}
	}

	function getDriveSpreadSheets($mimeType){
		global $service;
		global $client;
		global $spreadSheets;
		global $folderPaths;
		$folderID = $_REQUEST['folder'];
		$pageToken = null;
		$foldersQueue = [$folderID];
		$fullAcc = false;
		do{
			$fid = array_pop($foldersQueue);

			do {
				try {
					$parameters = array();
					
					if ($pageToken != null) {
						$parameters['pageToken'] = $pageToken;
					}else{
						$parameters['pageSize'] = "10";
						$parameters['q'] = '';
						if(strlen($fid)!=0){
							$parameters['q'].= "'$fid' in parents and (mimeType='application/vnd.google-apps.folder' or ";
						}else{
							$fullAcc = true;
						}

						$parameters['q'] .= "mimeType='$mimeType'";
						if(strlen($fid)!=0){
							$parameters['q'] .= ")";
						}
						$parameters['q'] .= " and trashed=false";
						
							
						$parameters['fields'] = "nextPageToken, files(id, name,parents,mimeType,webViewLink)";
					}
					
					

					$files = $service->files->listFiles($parameters);
					$filesList = $files->getFiles();
					foreach ($filesList as $file) {
						if($file->getMimeType() == 'application/vnd.google-apps.folder'){
							if(!$fullAcc)
								array_push($foldersQueue,$file->getId());
						}else if($file->getMimeType() == $mimeType){
							$spreadSheets[] = [
								'id' => $file->getId(),
								'name' => getFolderPath($file).'/'.$file->getName(),
								'webViewLink' => $file->getWebViewLink()
							];
						}
					}

					$pageToken = $files->getNextPageToken();
				} catch (Exception $e) {
					print "An error occurred: " . $e->getMessage();
					$pageToken = null;
				}
			} while ($pageToken != null);
		} while(count($foldersQueue)!=0);
	} 

	function getFolderPath($file){
		//return '';
		global $service;
		global $folderPaths;
		$folders = [];
      	$parent = $file->getParents();
		$pID = null;

		while ($parent!=null) {
			$id = $parent[0];
			if($pID == null){
				$pID = $id;
			}

			if(isset($folderPaths[$id])){
				array_push($folders,$folderPaths[$id]); 
				break;
			}
			$parent = $files = $service->files->get($parent[0],['fields'=>'parents,name']);
			array_push($folders,$parent->getName());
			$parent = $parent->getParents();
		}

		if (count($folders)!=0) {
			$folders = array_reverse($folders);
			$folderPaths[$pID] = implode('/',$folders);
		}

		return $folderPaths[$pID];
	}
?>

<style>
	tr th{
		font-weight: bold;
		text-align: left;
	}

	tr>*:not(:nth-child(2)){
		width: 10%;
	}
</style>

<script   src="https://code.jquery.com/jquery-2.2.4.min.js"   integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="   crossorigin="anonymous"></script>
<script>
	$(document).ready(function(){
		$('.chk-all').click(function(){
			var isChecked = $(this).is(':checked');
			
				$('.chk-box').prop('checked',isChecked);
			
		})

		$('tr[data-id]').each(function(){
			if($(this).find('.img-col').val().length==0){
				$(this).find('input[type=checkbox]').prop('checked',false);
			}
		})

		$('.sub-btn').click(function(){
			var formData = new FormData();
			var toSend = [];
			$('.chk-box:checked').each(function(){
				var parent = $(this).parents('tr');
				var toAdd = {
					id: parent.attr('data-id'),
					imgCol: parent.find('.img-col').val(),
					rexCol: parent.find('.sku-col').val()
				}
				toSend.push(toAdd);
			});

			var json = JSON.stringify(toSend);
			alert("Updating Links. Please wait. This can take long time");
			formData.append('data',json);
			$.ajax({
				url: 'update-file.php',
				type: 'post',
				contentType: false,
				processData: false,
				data: formData
			}).success(function(data){
				data = JSON.parse(data);
				if(data.messages.length == 0){
					alert("Updated Successfully");
				}else{
					console.log(data);
				}
			});
		});
	});

</script>
<form method="post" action="update-file.php">
	<input type="text" name="folder" />
	<button>Show Files</button>
</form>
<table style='width: 100%;'>
	<tr>
		<th><input type='checkbox' class='chk-all' checked/></th>
		<th>Spreadsheet Path</th>
		<th>Image Column</th>
		<th>SKU Column</th>
	<th>
	<?php foreach($spreadSheets as $sheet) { ?>
		<tr data-id="<?php echo $sheet['id']; ?>">
			<td><input type='checkbox' class='chk-box' checked /></td>
			<td><a href="<?php echo $sheet['webViewLink']; ?>"><?php echo $sheet['name']; ?></a></td>
			<td><input type='text' class='img-col' value="<?php echo $sheet['imgCol']; ?>" /></td>
			<td><input type='text' class='sku-col' value="<?php echo $sheet['rexCol']; ?>" /></td>
		<th>
	<?php } ?>
</table>
<button type='button' class='sub-btn'>Update file links</button> 