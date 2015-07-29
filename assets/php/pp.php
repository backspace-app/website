<?php
require_once 'c.php';

if ($_POST) {
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($_POST['session'] == $info['session']) { //checks to see if the current session ID is active
		if ($_POST['type'] == 1) { //change picture
		$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/pp/'; //directory of the profile pictures
		$file = basename($_FILES['content']['name']);
		$imageinfo = getimagesize($_FILES['content']['tmp_name']);
		$ext = findexts ($_FILES['content']['name']) ;
		$blacklist = array(".php",".phtml",".php3", ".php4"); //Files to disallow
		foreach($blacklist as $item) {
			if(preg_match("/$item\$/i", $_FILES['content']['name'])) {
				echo '{"success":0,"error_message":"Invalid file. Please try again."}';
				exit;
			}
		}
		if ($imageinfo['mime'] != 'image/gif' && $imageinfo['mime'] != 'image/png' && $imageinfo['mime'] != 'image/jpeg' && $imageinfo['mime'] != 'image/jpg' && $imageinfo['mime'] != 'image/pjpeg') { //double-check for image file
			echo '{"success":0,"error_message":"Invalid file. Please try again."}';
		}
		else {
			$new_name = generateUniqueId(5)."_".generateUniqueId(25); //create a random name for the profile picture
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
			if (move_uploaded_file($_FILES['content']['tmp_name'], $uploadfile)) { //verifies picture was uploaded successfully
				$stmt = $db->prepare("SELECT profile_pic FROM spwp_appprofile WHERE user_id=:id");
				$stmt->execute(array(':id' => $info['id']));
				$pic = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($pic['profilepic'] != "default_profile.png") {
					unlink($uploaddir.$pic['profile_pic']); //if profile picture is not default, delete it
				}
				$stmt = $db->prepare("UPDATE spwp_appprofile SET profile_pic=:pic  WHERE user_id=:id"); //change profile picture table reference
				$stmt->execute(array(':pic' => $uploadname,':id' => $info['id']));
				echo '{"success":1,"file":"'.$uploadname.'","error_message":"Profile picture successfully updated."}';
			}
			else {
				echo '{"success":0,"error_message":"An error occurred. Please try againddd."}';
			}	
		}
		}
		elseif ($_POST['type'] == 2) { //delete picture
			$uploaddir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/pp/';
			$stmt = $db->prepare("SELECT profile_pic FROM spwp_appprofile WHERE user_id=:id"); //gets the current profile picture name
			$stmt->execute(array(':id' => $info['id']));
			$pic = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($pic['profilepic'] != "default_profile.png") {
				unlink($uploaddir.$pic['profile_pic']); //if profile picture is not default, delete it
				$stmt = $db->prepare("UPDATE spwp_appprofile SET profile_pic='default_profile.png' WHERE user_id=:id"); //update profile picture reference in table
				$stmt->execute(array(':id' => $info['id']));
				echo '{"success":1,"error_message":"Profile picture has been deleted."}';
			}
			else {
				echo '{"success":0,"error_message":"Cannot delete the default profile picture."}';
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

