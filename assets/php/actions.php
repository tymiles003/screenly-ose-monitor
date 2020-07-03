<?php
/*
                            _
   ____                    | |
  / __ \__      _____  _ __| | __ ____
 / / _` \ \ /\ / / _ \| '__| |/ /|_  /
| | (_| |\ V  V / (_) | |  |   <  / /
 \ \__,_| \_/\_/ \___/|_|  |_|\_\/___|
  \____/

    http://www.atworkz.de
       info@atworkz.de
________________________________________
      Screenly OSE Monitor
         Actions Module
________________________________________
*/

	if(isset($_POST['newAsset'])){
		$id 				= array();
		$now				= strtotime("-10 minutes");
		$id[] 			= isset($_POST['id']) ? $_POST['id'] : '';
		$url 				= isset($_POST['url']) ? $_POST['url'] : '';
		$name 			= (isset($_POST['name']) ? $_POST['name'] : $_POST['url']);
		$mimetype		= $_POST['mimetype'];
		$start 			= date("Y-m-d", $now);
		$start_time	= "00:00";
		$end 				= date("Y-m-d", strtotime("+".$set['end_date']." week"));
		$end_time		= $start_time;
		$duration 	= $set['duration'];
		$cancel 		= FALSE;
		$output			= NULL;

		if($name == '') $name = $url;

		if(isset($_POST['multidrop'])){
			$images			= curl_file_create($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
			$images			= array('file_upload' => $images);
			$ids 			= $_POST['playerID'];
			$id				= explode(',', $ids);
		}

		for ($i=0; $i < count($id); $i++) {
			$playerSQL 	= $db->query("SELECT * FROM `player` WHERE playerID='".$id[$i]."'");
			$player 		= $playerSQL->fetchArray(SQLITE3_ASSOC);

			if(isset($_POST['multidrop'])){
				//print_r($images);
				$url = callURL('POST3', $player['address'].'/api/v1/file_asset', $images, $id[$i], false);
				if (strpos($url, '/home/pi/screenly_assets') === false) {
					$output .= $player['name'];
					continue;
				}
			}

			$data 										= array();
			$data['mimetype'] 				= $mimetype;
			$data['is_enabled'] 			= 1;
			$data['name'] 						= $name;
			$data['start_date'] 			= $start.'T'.$start_time.':00.000Z';
			$data['end_date'] 				= $end.'T'.$end_time.':00.000Z';
			$data['duration'] 				= $duration;
			$data['play_order']				= 0;
			$data['nocache'] 					= 0;
			$data['uri'] 							= $url;
			$data['skip_asset_check'] = 1;

			//print_r($data);
			//echo'<script>console.log("ID: '.$id[$i].'")</script>';

			if($out = callURL('POST', $player['address'].'/api/'.$apiVersion.'/assets', $data, $id[$i], false)){
				if(strpos($out, '201') === false){
					$output .= $player['name'].'
					';
				} else if(!isset($_POST['multidrop'])) echo 'Asset added successfully to Player: '.$player['name'];
			}
			else {
				header('HTTP/1.1 404 Not Found');
				$output .= 'Error! - Can \'t add the Asset';
			}
		}

		if($output == NULL){
			header('HTTP/1.1 200 OK');
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'Can\'t upload to:

			'.$output;
		}
		die();
	}

	if(isset($_POST['changeAssetState'])){
		$playerID 			= $_POST['id'];
		$asset					= $_POST['asset'];
		$playerSQL 			= $db->query("SELECT * FROM `player` WHERE playerID='".$playerID."'");
    $player 				= $playerSQL->fetchArray(SQLITE3_ASSOC);
		$playerAddress 	= $player['address'];
		$data = callURL('GET', $playerAddress.'/api/'.$apiVersion.'/assets/'.$asset, false, $playerID, false);
		if($data['is_enabled'] == 1){
			$data['is_enabled'] = '0';
			$data['is_active'] 	= '0';
		}
		else {
			$data['is_enabled'] = '1';
			$data['is_active'] 	= '1';
		}
		if($data['mimetype'] == 'video') $data['duration'] = 0;
		if(callURL('PUT', $playerAddress.'/api/'.$apiVersion.'/assets/'.$asset, $data, $playerID, false)){
			header('HTTP/1.1 200 OK');
			exit();
		} else {
			header('HTTP/1.1 404 Not Found');
			exit();
		}
	}

	if(isset($_POST['changeAsset'])){
		$playerID 			= $_POST['playerID'];
		$orderD 				= $_POST['order'];
		$playerSQL 			= $db->query("SELECT * FROM `player` WHERE playerID='".$playerID."'");
    $player 				= $playerSQL->fetchArray(SQLITE3_ASSOC);
		$playerAddress 	= $player['address'];
		$result = callURL('GET', $playerAddress.'/api/v1/assets/control/'.$orderD.'', false, $playerID, false);
		$db->exec("UPDATE `player` SET sync='".time()."' WHERE playerID='".$playerID."'");
		if($result != ''){
			header('HTTP/1.1 200 OK');
			echo $result;
		}
		else header('HTTP/1.1 404 Not Found');
	}

	if(isset($_POST['exec_reboot'])){
		$playerID 			= $_POST['playerID'];
		$playerSQL 			= $db->query("SELECT * FROM `player` WHERE playerID='".$playerID."'");
		$player 				= $playerSQL->fetchArray(SQLITE3_ASSOC);
		$playerAddress 	= $player['address'];
		$db->exec("UPDATE `player` SET sync='".time()."' WHERE playerID='".$playerID."'");
		header('HTTP/1.1 200 OK');
		echo 'Reboot command send!';
		$result = callURL('POST', $playerAddress.'/api/v1/reboot_screenly', false, $playerID, false);
	}

	if(isset($_POST['editInformation'])){
		$playerID 	= $_POST['playerID'];
    $playerSQL 	= $db->query("SELECT * FROM `player` WHERE playerID='".$playerID."'");
    $player 		= $playerSQL->fetchArray(SQLITE3_ASSOC);
		if($playerID != ''){
			header('HTTP/1.1 200 OK');
			header('Content-Type: application/json');
			$return_arr = array("player_name" => $player['name'], "player_address" => $player['address'], "player_location" => $player['location'], "player_user" => $player['player_user'], "player_password" => $player['player_password']);
			echo json_encode($return_arr);
		}
		else header('HTTP/1.1 404 Not Found');
	}

	if(isset($_POST['changeOrder'])){
		$playerID 			= $_POST['id'];
		$playerSQL 			= $db->query("SELECT * FROM `player` WHERE playerID='".$playerID."'");
		$player 				= $playerSQL->fetchArray(SQLITE3_ASSOC);
		$playerAddress 	= $player['address'];
		$data						= 'ids=';
		$i = 0;
		foreach ($_POST['order'] as $value) {
			$data .= $value.',';
		}
		$data = substr($data, 0, -1);
		$result = callURL('POST2', $playerAddress.'/api/v1/assets/order', $data, $playerID, false);
		$db->exec("UPDATE `player` SET sync='".time()."' WHERE playerID='".$playerID."'");
		if(json_encode($result) != ''){
			header('HTTP/1.1 200 OK');
			print_r(json_encode($result));
		}
		else header('HTTP/1.1 404 Not Found');
	}
