<?php
require_once 'c.php';
require_once 'sp.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session, username FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && ($_POST['content'] || $_FILES['pic']['name'])) { //looks to see if the session is current and if all required information is there
		if ($_POST['type'] == 0) { //text post
			$time = time() + 86400; //adjust time for post	
			$stmt = $db->prepare("INSERT INTO spwp_appposts (group_id, poster, type, content, length) VALUES(:group,:username,:type,:content,:length)");
			$stmt->execute(array(':group' => $_POST['group'],':username' => $info['id'],':type' => $_POST['type'],':content' => substr(json_encode($_POST['content']),1,-1),':length' => $time)); //adds post information to table
			$post = $db->lastInsertId(); //gets post ID
			$stmt = $db->prepare("UPDATE spwp_appprofile SET post_count = post_count + 1 WHERE user_id=:id"); //updates post count
			$stmt->execute(array(':id' => $info['id']));
			//hashtags
			$postLength = strlen($_POST['content']);
			$end = 0;
			for ($i=0; $i<=$postLength; $i++) {
				if (strpos($_POST['content'],'#',$i) > -1) {
					$start = strpos($_POST['content'],'#',$end);
					$end = strpos($_POST['content'],' ',$start);
					if ($end == ""){
						$end = strlen($_POST['content']);
					}
					$hashtag = substr($_POST['content'], $start, ($end - $start));
					if (strlen($hashtag) > 1) {
						$stmt = $db->prepare("INSERT INTO spwp_apphashtags (post_id, hashtag) VALUES(:id,:hash)");
						$stmt->execute(array(':id' => $post,':hash' => $hashtag));
					}
					$i = $end;
				}
			}
			//end hashtags
			//tagging user
			if (!empty($_POST['tagged_users'])) {
				$array = explode(',',$_POST['tagged_users']);
				for ($i=0; $i<=count($array)-1; $i++) {
					if (is_string($array[$i]) && !is_numeric($array[$i])) { //gets user ID if a string is sent
						if ($array[$i] == $info['username']) {
							$array[$i] = $info['id'];
						}
						else {
							$stmt = $db->prepare("SELECT user_id FROM spwp_appprofile JOIN spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_two WHERE spwp_appprofile.username=? AND spwp_appfriends.user_one=? AND spwp_appfriends.pending=0");
							$stmt->execute(array($array[$i], $info['id']));
							$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
							$num_ids = $stmt->rowCount();
							if ($num_ids == 0) { //go to the next item if username is not found							
								continue 1;
							}
							$array[$i] = $search[0]['user_id'];
						}
					}
					$stmt = $db->prepare("INSERT INTO spwp_apptags (post_id, user_id) VALUES(:post_id, :user)"); //sends the poster a notification
					$stmt->execute(array(':post_id' => $post, ':user' => $array[$i]));
					if ($array[$i] != $info['id']) {
						$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 5, 0)"); //sends the poster a notification
						$stmt->execute(array(':post_id' => $post, ':two' => $array[$i], ':one' => $info['id']));
						//Push Notifications
						$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
						$stmt->execute(array($array[$i]));
						$badge = $stmt->fetch(PDO::FETCH_ASSOC);
						$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
						$stmt->execute(array($array[$i]));
						$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
						$num_tokens = $stmt->rowCount();
						$message = $info['username'].' tagged you in their post';
						for ($z = 0; $z < $num_tokens; $z++) {
							pushNotification($token[$z]['device_token'],$message,$badge['notif']);
						}
						//End Push Notifications
					}	
				}
			}
			//end tagging user
			echo '{"success":1,"error_message":"Posted."}';
		}
		elseif ($_POST['type'] == 1) { //photo post
			$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/p/'; //folder to store all photo uploads
			$file = basename($_FILES['pic']['name']);
			$imageinfo = getimagesize($_FILES['pic']['tmp_name']);
			$ext = findexts ($_FILES['pic']['name']) ;
			$blacklist = array(".php",".phtml",".php3", ".php4"); //Files to disallow
			foreach($blacklist as $item) {
				if(preg_match("/$item\$/i", $_FILES['pic']['name'])) {
					echo '{"success":0,"error_message":"Invalid file. Please try again."}';
					exit;
				}
			}
			if ($imageinfo['mime'] != 'image/gif' && $imageinfo['mime'] != 'image/png' && $imageinfo['mime'] != 'image/jpeg' && $imageinfo['mime'] != 'image/jpg' && $imageinfo['mime'] != 'image/pjpeg') { //double-check for image file
				echo '{"success":0,"error_message":"Invalid file. Please try again."}';
			}
			else {
				$new_name = generateUniqueId(5)."_".generateUniqueId(25); //create a random name for the picture
				$i=0;
				while ($i == 0) { //checks to see if the file name already exists, if it does, generate a new random picture name
					if (file_exists($uploaddir.$new_name.".".$ext)) {
						$new_name = generateUniqueId(5)."_".generateUniqueId(25);
					}
					else {
						$i=1;
						$uploadfile = $uploaddir.$new_name.".".$ext;
						$uploadname = $new_name.".".$ext;
					}
			    }
				if (move_uploaded_file($_FILES['pic']['tmp_name'], $uploadfile)) { //verifies picture was uploaded successfully
					$time = time() + 86400; //adjust time for post	
					if ($_POST['content'] == "" || !isset($_POST['content'])) {
						$content = "";
					}
					else {
						$content = substr(json_encode($_POST['content']),1,-1);
					}
					$stmt = $db->prepare("INSERT INTO spwp_appposts (group_id, poster, type, content, pic, length) VALUES(:group,:username,:type,:content, :pic, :length)");
					$stmt->execute(array(':group' => $_POST['group'],':username' => $info['id'],':type' => $_POST['type'],':content' => $content,':pic' => $uploadname,':length' => $time)); //adds post information to table
					$post = $db->lastInsertId(); //gets post ID
					$stmt = $db->prepare("UPDATE spwp_appprofile SET post_count = post_count + 1 WHERE user_id=:id"); //updates post count
					$stmt->execute(array(':id' => $info['id']));
					//hashtags
					$postLength = strlen($_POST['content']);
					$end = 0;
					for ($i=0; $i<=$postLength; $i++) {
						if (strpos($_POST['content'],'#',$i) > -1) {
							$start = strpos($_POST['content'],'#',$end);
							$end = strpos($_POST['content'],' ',$start);
							if ($end == ""){
								$end = strlen($_POST['content']);
							}
							$hashtag = substr($_POST['content'], $start, ($end - $start));
							if (strlen($hashtag) > 1) {
								$stmt = $db->prepare("INSERT INTO spwp_apphashtags (post_id, hashtag) VALUES(:id,:hash)");
								$stmt->execute(array(':id' => $post,':hash' => $hashtag));
							}
							$i = $end;
						}
					}
					//end hashtags
					//tagging user
					if (!empty($_POST['tagged_users'])) {
						$array = explode(',',$_POST['tagged_users']);
						for ($i=0; $i<=count($array)-1; $i++) {
							if (is_string($array[$i]) && !is_numeric($array[$i])) { //gets user ID if a string is sent
								if ($array[$i] == $info['username']) {
									$array[$i] = $info['id'];
								}
								else {
									$stmt = $db->prepare("SELECT user_id FROM spwp_appprofile JOIN spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_two WHERE spwp_appprofile.username=? AND spwp_appfriends.user_one=? AND spwp_appfriends.pending=0");
									$stmt->execute(array($array[$i], $info['id']));
									$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$num_ids = $stmt->rowCount();
									if ($num_ids == 0) { //go to the next item if username is not found							
										continue 1;
									}
									$array[$i] = $search[0]['user_id'];
								}
							}
							$stmt = $db->prepare("INSERT INTO spwp_apptags (post_id, user_id) VALUES(:post_id, :user)"); //sends the poster a notification
							$stmt->execute(array(':post_id' => $post, ':user' => $array[$i]));
							if ($array[$i] != $info['id']) {
								$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 5, 0)"); //sends the poster a notification
								$stmt->execute(array(':post_id' => $post, ':two' => $array[$i], ':one' => $info['id']));
								//Push Notifications
								$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
								$stmt->execute(array($array[$i]));
								$badge = $stmt->fetch(PDO::FETCH_ASSOC);
								$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
								$stmt->execute(array($array[$i]));
								$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
								$num_tokens = $stmt->rowCount();
								$message = $info['username'].' tagged you in their post';
								for ($z = 0; $z < $num_tokens; $z++) {
									pushNotification($token[$z]['device_token'],$message,$badge['notif']);
								}
								//End Push Notifications
							}	
						}
					}
					//end tagging user
					echo '{"success":1,"error_message":"Posted."}';
							
				}
				else {
					echo '{"success":0,"error_message":"An error occurred. Please try again."}';
				}
			}
		}
		elseif ($_POST['type'] == 2) { //VIDEOS!!!!!!	
			$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'; //folder to store all video uploads
			$file = basename($_FILES['pic']['name']); //video
			$ext = findexts ($_FILES['pic']['name']) ; //video
			$imageinfo = getimagesize($_FILES['thumb']['tmp_name']); //pic
			$ext2 = findexts ($_FILES['thumb']['name']) ; //pic
			$blacklist = array(".php",".phtml",".php3", ".php4"); //Files to disallow
			foreach($blacklist as $item) {
				if(preg_match("/$item\$/i", $_FILES['pic']['name']) || preg_match("/$item\$/i", $_FILES['thumb']['name'])) {
					echo '{"success":0,"error_message":"Invalid file. Please try again."}';
					exit;
				}
			}
			if ($_FILES['pic']['type'] != 'video/quicktime' && $_FILES['pic']['type'] != 'video/mov' && $_FILES['pic']['type'] != 'video/mp4' && $_FILES['pic']['type']  != 'video/3gp' && $_FILES['pic']['type']  != 'video/webm' && $_FILES['pic']['type']  != 'video/Matroska' && $_FILES['pic']['type'] != 'video/mkv' && $imageinfo['mime'] != 'image/gif' && $imageinfo['mime'] != 'image/png' && $imageinfo['mime'] != 'image/jpeg' && $imageinfo['mime'] != 'image/jpg' && $imageinfo['mime'] != 'image/pjpeg') { //double-check for video file
				echo '{"success":0,"error_message":"Invalid file. Please try again."}';
			}
			else {
				$new_name = generateUniqueId(5)."_".generateUniqueId(25); //create a random name for the video
				$i=0;
				while ($i == 0) { //checks to see if the file name already exists, if it does, generate a new random video name
					if (file_exists($uploaddir.$new_name.".".$ext)) {
						$new_name = generateUniqueId(5)."_".generateUniqueId(25);
					}
					else {
						$i=1;
						$uploadfile = $uploaddir.$new_name.".".$ext;
						$uploadname = $new_name.".".$ext;
						$thumb = $new_name."_t.".$ext2;
						$thumbfile = $uploaddir.$thumb;
					}
			    }
				if (move_uploaded_file($_FILES['pic']['tmp_name'], $uploadfile) && move_uploaded_file($_FILES['thumb']['tmp_name'], $thumbfile)) { //verifies video was uploaded successfully
					$time = time() + 86400; //adjust time for post	
					if ($_POST['content'] == "" || !isset($_POST['content'])) {
						$content = "";
					}
					else {
						$content = substr(json_encode($_POST['content']),1,-1);
					}
					
					$stmt = $db->prepare("INSERT INTO spwp_appposts (group_id, poster, type, content, pic, thumb, length) VALUES(:group,:username,:type,:content, :pic, :thumb, :length)");
					$stmt->execute(array(':group' => $_POST['group'],':username' => $info['id'],':type' => $_POST['type'],':content' => $content,':pic' => $uploadname, ':thumb' => $thumb,':length' => $time)); //adds post information to table
					$post = $db->lastInsertId(); //gets post ID
					$stmt = $db->prepare("UPDATE spwp_appprofile SET post_count = post_count + 1 WHERE user_id=:id"); //updates post count
					$stmt->execute(array(':id' => $info['id']));
					//hashtags
					$postLength = strlen($_POST['content']);
					$end = 0;
					for ($i=0; $i<=$postLength; $i++) {
						if (strpos($_POST['content'],'#',$i) > -1) {
							$start = strpos($_POST['content'],'#',$end);
							$end = strpos($_POST['content'],' ',$start);
							if ($end == ""){
								$end = strlen($_POST['content']);
							}
							$hashtag = substr($_POST['content'], $start, ($end - $start));
							if (strlen($hashtag) > 1) {
								$stmt = $db->prepare("INSERT INTO spwp_apphashtags (post_id, hashtag) VALUES(:id,:hash)");
								$stmt->execute(array(':id' => $post,':hash' => $hashtag));
							}
							$i = $end;
						}
					}
					//end hashtags
					//tagging user
					if (!empty($_POST['tagged_users'])) {
						$array = explode(',',$_POST['tagged_users']);
						for ($i=0; $i<=count($array)-1; $i++) {
							if (is_string($array[$i]) && !is_numeric($array[$i])) { //gets user ID if a string is sent
								if ($array[$i] == $info['username']) {
									$array[$i] = $info['id'];
								}
								else {
									$stmt = $db->prepare("SELECT user_id FROM spwp_appprofile JOIN spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_two WHERE spwp_appprofile.username=? AND spwp_appfriends.user_one=? AND spwp_appfriends.pending=0");
									$stmt->execute(array($array[$i], $info['id']));
									$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
									$num_ids = $stmt->rowCount();
									if ($num_ids == 0) { //go to the next item if username is not found							
										continue 1;
									}
									$array[$i] = $search[0]['user_id'];
								}
							}
							$stmt = $db->prepare("INSERT INTO spwp_apptags (post_id, user_id) VALUES(:post_id, :user)"); //sends the poster a notification
							$stmt->execute(array(':post_id' => $post, ':user' => $array[$i]));
							if ($array[$i] != $info['id']) {
								$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 5, 0)"); //sends the poster a notification
								$stmt->execute(array(':post_id' => $post, ':two' => $array[$i], ':one' => $info['id']));
								//Push Notifications
								$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
								$stmt->execute(array($array[$i]));
								$badge = $stmt->fetch(PDO::FETCH_ASSOC);
								$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
								$stmt->execute(array($array[$i]));
								$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
								$num_tokens = $stmt->rowCount();
								$message = $info['username'].' tagged you in their post';
								for ($z = 0; $z < $num_tokens; $z++) {
									pushNotification($token[$z]['device_token'],$message,$badge['notif']);
								}
								//End Push Notifications
							}	
						}
					}
					//end tagging user
					echo '{"success":1,"error_message":"Posted."}';
							
				}
				else {
					echo '{"success":0,"error_message":"An error occurred. Please try again."}';
				}
			}
		}
		else {
			echo '{"success":0,"error_message":"An error occurred. Please try again."}';
		}
		
	}
	else {
		echo '{"success":9,"error_message":"You have been logged out. Please log back in."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;

function generateUniqueId($maxLength = null) {
    $entropy = '';
    if (function_exists('openssl_random_pseudo_bytes')) {
        $entropy = openssl_random_pseudo_bytes(64, $strong);
        if($strong !== true) {
            $entropy = '';
        }
    }
    $entropy .= uniqid(mt_rand(), true);
    if (class_exists('COM')) {
        try {
            $com = new COM('CAPICOM.Utilities.1');
            $entropy .= base64_decode($com->GetRandom(64, 0));
        } catch (Exception $ex) {
        }
    }
    if (is_readable('/dev/urandom')) {
        $h = fopen('/dev/urandom', 'rb');
        $entropy .= fread($h, 64);
        fclose($h);
    }
    $hash = hash('whirlpool', $entropy);
    if ($maxLength) {
        return substr($hash, 0, $maxLength);
    }
    return $hash;
}

function findexts ($filename) { 
	$filename = strtolower($filename) ; 
	$exts = split("[/\\.]", $filename) ; 
	$n = count($exts)-1; 
	$exts = $exts[$n]; 
	return $exts; 
}
?>