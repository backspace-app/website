<?php
include('pwhsh.php');
require_once 'c.php';

if($_POST) {
	$stmt = $db->prepare("SELECT password, lockout_time, login_attempts FROM spwp_appusers WHERE username=?"); //gets user data
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if($_POST['username'] != '' && validate_password($_POST['password'],$info['password'])) { //verifies passwords match
    	if ($info['lockout_time']>time()){ //checks to see if there have been too many password tries
    		echo '{"success":0,"error_message":"User locked out for 5 min."}';
		}
		else {
    		$session = generateUniqueId(25); //create the session
			$stmt = $db->prepare("UPDATE spwp_appusers SET login_attempts=0, lockout_time=0, session=? WHERE username=?"); //logs user in
			$stmt->execute(array($session,$_POST['username']));
			$stmt = $db->prepare("UPDATE spwp_appprofile SET last_active=CURRENT_TIMESTAMP WHERE username=?"); //updates when user was last active
			$stmt->execute(array($_POST['username']));
			$stmt = $db->prepare("SELECT user_id, profile_pic, is_private FROM spwp_appprofile WHERE username=?"); //gets user data
			$stmt->execute(array($_POST['username']));
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			
			//devicetoken
			$stmt = $db->prepare("DELETE FROM spwp_apptokens WHERE device_token=?"); //removes device token from all profiles
			$stmt->execute(array($_POST['device_token']));
			$stmt = $db->prepare("INSERT INTO spwp_apptokens (user_id, device_token) VALUES(:id,:token)"); //inserts device token in db
			$stmt->execute(array(':id' => $user['user_id'], ':token' => $_POST['device_token']));
			//devicetoken
			
			echo '{"success":1,"username":"'.$_POST['username'].'","session":"'.$session.'","my_id":'.$user['user_id'].',"profile_pic":"'.$user['profile_pic'].'","is_private":'.$user['is_private'].'}';
		}
	} 
	else {
		if ($info['lockout_time']>time()){ //user is locked out
    		echo '{"success":0,"error_message":"User locked out for 5 min."}';
		}
		else {
			$login_attempts = $info['login_attempts'] + 1; //adds to the number of wrong passwords
			$stmt = $db->prepare("UPDATE spwp_appusers SET login_attempts=? WHERE username=?");
			$stmt->execute(array($login_attempts,$_POST['username'])); //updates password table
			if ($info['login_attempts'] > 2){
				$lockout = time()+300; //locks user out for 5 minutes
				$stmt = $db->prepare("UPDATE spwp_appusers SET login_attempts=0, lockout_time=? WHERE username=?");
				$stmt->execute(array($lockout,$_POST['username']));
				echo '{"success":0,"error_message":"Too many login attempts. You\'re locked out for 5 min."}';
			}
			else {
    			echo '{"success":0,"error_message":"Username and/or password is invalid."}';
			}
		}
	}
}
else {
	echo '{"success":0,"error_message":"Username and/or password is invalid."}';
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
?>