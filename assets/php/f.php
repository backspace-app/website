<?php
require_once 'c.php';

if($_POST) {
    if($_POST['username'] != '') {
    	$stmt = $db->prepare("SELECT username, email FROM spwp_appusers WHERE username=?");
		$stmt->execute(array($_POST['username']));
		$info = $stmt->fetch(PDO::FETCH_ASSOC);
		$username_count = $stmt->rowCount(); //verifies username is an active user
		if ($username_count == 1) {
			$reset_id = generateUniqueId(64); //generates reset link
			$reset_link = "https://backspaceapp.co/pwreset?username=".$info['username']."&token=".$reset_id;
			$stmt = $db->prepare("DELETE FROM spwp_appforgot WHERE username=?");
			$stmt->execute(array($_POST['username']));
			
			$expire = time()+900; //creates expire time
			$stmt = $db->prepare("INSERT INTO spwp_appforgot (username, token, expire) VALUES(:username,:token,:expire)"); //inserts reset information into table
			$stmt->execute(array(':username' => $_POST['username'], ':token' => $reset_id, ':expire' => $expire));
			
			$headers = "From: Backspace <noreply@backspaceapp.co>\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			$email_message = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' 'http://www.w3.org/TR/REC-html40/loose.dtd'><html><body style='font-family: 'Open Sans Condensed', sans-serif; font-size: 20px;' bgcolor='#FB4731'><style type='text/css'>.btn:hover { text-decoration: none !important; }</style><div id='container' style='background-color: #FFF; width: 70%; min-width: 600px; margin: 20px auto 5px;'><div id='main' style='padding: 30px;'><img src='https://backspaceapp.co/assets/img/logo.png' width='250px' style='margin: 5px auto;'><hr><h1 style='font-size: 30px; font-weight: 600;'>Forgot Your Password?</h1>That's ok ".$info['username']."! We can't always remember everything!\n\n<br><br><a href='".$reset_link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>RESET PASSWORD</a><br><br>After clicking the link, you'll be able to change your password to something easier to remember. This link will remain active for 15 minutes. If you did not request a password reset, please contact us at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a>.<br><br>< Backspace Team<br><br><hr><div id='footer' style='font-size: 12px; text-align: center;' align='center'>Backspace by WR Industries, LLC | Built in Chicago and St. Louis</div></div></div></body></html>";
  										
			if (@mail($_POST['username']." <".$info['email'].">", "Backspace Password Reset Link", $email_message, $headers)) { //verifies email was sent
				echo '{"success":1,"error_message":"Reset link sent!"}';
			}
			else {
				echo '{"success":0,"error_message":"Please try again."}';
			}
		}
		else {
			echo '{"success":0,"error_message":"Username not found!"}';
		}
 } else {
    	echo '{"success":0,"error_message":"Username invalid."}';
}
}else {    echo '{"success":0,"error_message":"Username invalid."}';}
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