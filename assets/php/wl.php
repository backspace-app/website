<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;
require_once 'c.php';

if($_POST) {
    if($_POST['email'] != '') {
	    
	    //remove after beta
	    echo '{"success":0,"error_message":"You are already in! No need to join the waitlist ;)."}';
	    exit();
	    //remove after beta
	    
		$stmt = $db->prepare("SELECT email FROM spwp_appwaitlist WHERE email=?");
		$stmt->execute(array($_POST['email']));
		$email_count = $stmt->rowCount(); //checks for email duplication in the table
		if ($email_count == 0) {
			if(validEmail($_POST['email'])) { //checks to see if the email is valid or not
				$id = generateUniqueId(10); //create the link id
				$i=0;
				while ($i == 0) { //if the link ID already exists, generate a new ID
					$stmt = $db->prepare("SELECT link FROM spwp_appwaitlist WHERE link=?");
					$stmt->execute(array($id));
					$num_groups = $stmt->rowCount();
					if ($num_groups > 0) {
						$id = generateUniqueId(10);
					}
					else {
						$i=1;
					}
				}
				$stmt = $db->prepare("INSERT INTO spwp_appwaitlist (email,link) VALUES(:email,:link)"); //adds email to waitlist table
				$stmt->execute(array(':email' => $_POST['email'],':link' => $id));
				$position = $db->lastInsertId(); //returns waitlist position
				$stmt = $db->prepare("SELECT wait_id FROM spwp_appwaitlist WHERE active=?");
				$stmt->execute(array("Y"));
				$active = $stmt->rowCount(); //determines number of users currently waiting for an invite
				$stmt = $db->prepare("SELECT wait_id FROM spwp_appwaitlist WHERE active=?");
				$stmt->execute(array("N"));
				$not_active = $stmt->rowCount(); //determines number of users who have already gotten an invite
				$in_front = $position['wait_id'] - $not_active - 1; //counts users in front of current user in waitlist
				
				$link = "https://backspaceapp.co/w?id=".$id;
				$email_message = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' 'http://www.w3.org/TR/REC-html40/loose.dtd'><html><body style='font-family: 'Open Sans Condensed', sans-serif; font-size: 20px;' bgcolor='#FB4731'><style type='text/css'>.btn:hover { text-decoration: none !important; }</style><div id='container' style='background-color: #FFF; width: 70%; min-width: 600px; margin: 20px auto 5px;'><div id='main' style='padding: 30px;'><img src='https://backspaceapp.co/assets/img/logo.png' width='250px' style='margin: 5px auto;'><hr><h1 style='font-size: 25px; font-weight: 600;'>Congratulations ".$_POST['email']."!</h1> You are one of the first in line on the Backspace app waiting list! You’ll be one of the first people to experience the next generation of social media. How could you not be excited?\n\n<br><br>Keep the excitement going with a chance to become a VIP member! How can you become a VIP member of Backspace? Share your unique link from this email with your Facebook and Twitter friends; every friend who signs up gets you closer to using the Backspace app as a VIP member! Get additional VIP perks if 10 or more of your friends sign up!\n\n<br><br><br><a href='https://www.facebook.com/sharer/sharer.php?u=".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Facebook</a> <a href='https://twitter.com/home?status=Check%20out%20the%20Backspace%20app%20on%20iOS!%20".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Twitter</a> <a href='".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Link</a><br><br><br> Look out for our next newsletter for more info on the Backspace app launch!\n\n<br><br> Follow us on <a href='https://www.facebook.com/backspaceapp'>Facebook</a> and <a href='https://twitter.com/backspaceapp'>Twitter</a> for updates on Backspace! Want to help shape the future of social media? Give us your feedback and suggestions at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a>.<br><br>&#60; Backspace Team<br><br><hr><div id='footer' style='font-size: 12px; text-align: center;' align='center'>Backspace by WR Industries, LLC | Built in Chicago and St. Louis<br><a href='%unsubscribe_url%'>unsubscribe</a></div></div></div></body></html>";

				//using mailgun
				# Instantiate the client.
				$mgClient = new Mailgun('key-4f73ad99ca41c8425b5962b698c79a68');
				$domain = "backspaceapp.co";
				# Make the call to the client.
				$result = $mgClient->sendMessage($domain, array(
				    'from'    => 'Backspace <noreply@backspaceapp.co>',
				    'to'      => $_POST['email'],
				    'subject' => "You're On The Backspace Waitlist!",
				    'html'    => $email_message
				));
				//using mailgun
				
				

				echo '{"success":1,"error_message":"Your email has been added to the waitlist. There are '.$in_front.' people in front of you. Check your email for a unique link to share so you can jump ahead in line!"}';
			}
			else {
				echo '{"success":0,"error_message":"Please enter a valid email."}';
			}
		}
		else {
			echo '{"success":0,"error_message":"Email already in use."}';
		}
 	} 
 	else {
    	echo '{"success":0,"error_message":"Please enter a valid email."}';
	}
}
else {
	echo '{"success":0,"error_message":"There was an error. Please try again."}';
}

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