<?php
include('pwhsh.php');
require_once 'c.php';

if($_POST) {
    if($_POST['email'] != '' && $_POST['username'] != '' && $_POST['password'] != '' && $_POST['verify'] != '') { //verifies all required fields are filled out
		$stmt = $db->prepare("SELECT username FROM spwp_appusers WHERE username=?");
		$stmt->execute(array(strtolower($_POST['username'])));
		$username_count = $stmt->rowCount(); //double-checks the requested username is still available
		$stmt = $db->prepare("SELECT email FROM spwp_appusers WHERE email=?");
		$stmt->execute(array(strtolower($_POST['email'])));
		$email_count = $stmt->rowCount(); //double-checks the requested email is still available
		if ($username_count == 0 && $email_count == 0) {
			if(validEmail($_POST['email'])) { //makes sure the email entered is a valid email
				$pw = create_hash($_POST['password']); //creates the password hash
				$session = generateUniqueId(25); //create the session
				$stmt = $db->prepare("INSERT INTO spwp_appusers (email, username, password, session) VALUES(:email,:username,:password,:session)");
				$stmt->execute(array(':email' => $_POST['email'], ':username' => $_POST['username'], ':password' => $pw, ':session' => $session)); //inserts user information to the user table
				$id = $db->lastInsertId(); //gets the newly entered user ID
				
				$pic = "default_profile.png";
				$post_count = 0;
				$phone = $_POST['phone'];
				$stmt = $db->prepare("INSERT INTO spwp_appprofile (user_id, username, phone, post_count, profile_pic) VALUES(:id,:username,:phone,:post_count,:profile_pic)");
				$stmt->execute(array(':id' => $id, ':username' => $_POST['username'], ':phone' => $phone, ':post_count' => $post_count, ':profile_pic' => $pic)); //insters user information into the profile table; keeps login information separate
				
				//devicetoken
				$stmt = $db->prepare("DELETE FROM spwp_apptokens WHERE device_token=?"); //removes device token from all profiles
				$stmt->execute(array($_POST['device_token']));
				$stmt = $db->prepare("INSERT INTO spwp_apptokens (user_id, device_token) VALUES(:id,:token)"); //inserts device token in db
				$stmt->execute(array(':id' => $id, ':token' => $_POST['device_token']));
				//devicetoken
				
				//auto follow users
				$stmt = $db->prepare("INSERT INTO spwp_appfollowing (user_one, user_two) VALUES(:one, 3)"); //justin
				$stmt->execute(array(':one' => $id));
				$stmt = $db->prepare("INSERT INTO spwp_appfollowing (user_one, user_two) VALUES(:one, 1)"); //bryan
				$stmt->execute(array(':one' => $id));
				//auto follow users
				
				echo '{"success":1,"username":"'.$_POST['username'].'","session":"'.$session.'","my_id":'.$id.',"profile_pic":"'.$pic.'","is_private":0}';				
			}
			else {
				echo '{"success":0,"error_message":"Please enter a valid email."}';
			}
		}
		elseif($username_count > 0) {
			echo '{"success":0,"error_message":"Username already taken."}';
		}
		elseif($email_count > 0) {
			echo '{"success":0,"error_message":"Email already in use."}';
		}
 } else {
    	echo '{"success":0,"error_message":"Username and/or password is invalid."}';
}
}else {    echo '{"success":0,"error_message":"Username and/or password is invalid."}';}

function validEmail($email) {
	  	$isValid = true;
	  	$atIndex = strrpos($email, "@");
	  	if (is_bool($atIndex) && !$atIndex) {
		  	$isValid = false;
		  }
		else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				$isValid = false;
			}
			else if ($domainLen < 1 || $domainLen > 255) {
				$isValid = false;
			}
			else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $local)) {
				$isValid = false;
			}
			else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $domain)) {
				$isValid = false;
			}
			else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
					$isValid = false;
				}
			}
			if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
				$isValid = false;
			}
		}
		return $isValid;
	}
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