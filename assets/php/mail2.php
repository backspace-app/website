<?php
/* =====================================================
 * change this to the email you want the form to send to
 * ===================================================== */
require 'vendor/autoload.php';
use Mailgun\Mailgun;
require_once 'c.php';


if(isset($_POST['email']))
{

    // check for empty required fields
    if (!isset($_POST['email']))
    {
        $errorMessage = "Please enter a valid email address.";
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }

    // form field values
    $semail = $_POST['email']; // required

    // form validation
    $error_message = "";

    // name
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
    
    if (validEmail($semail)==false)
    {
	    $errorMessage = "Please enter a valid email address.";
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }
    
    
    $stmt = $db->prepare("SELECT email FROM spwp_appwaitlist WHERE email=?");
	$stmt->execute(array($semail));
	$email_count = $stmt->rowCount();
    
    if ($email_count > 0)
    {
	    $errorMessage = "Email address already submitted.";
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    } 

    function clean_string($string)
    {
        $bad = array("content-type", "bcc:", "to:", "cc:", "href");
        return str_replace($bad, "", $string);
    }
    
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
	$link = "http://backspaceapp.co/w?id=".$id;
	$email_message = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' 'http://www.w3.org/TR/REC-html40/loose.dtd'><html><body style='font-family: 'Open Sans Condensed', sans-serif; font-size: 20px;' bgcolor='#FB4731'><style type='text/css'>.btn:hover { text-decoration: none !important; }</style><div id='container' style='background-color: #FFF; width: 70%; min-width: 600px; margin: 20px auto 5px;'><div id='main' style='padding: 30px;'><img src='http://backspaceapp.co/assets/img/logo.png' width='250px' style='margin: 5px auto;'><hr><h1 style='font-size: 25px; font-weight: 600;'>Congratulations ".$_POST['email']."!</h1> You are one of the first in line on the Backspace app waiting list! You’ll be one of the first people to experience the next generation of social media. How could you not be excited?\n\n<br><br>Keep the excitement going with a chance to become a VIP member! How can you become a VIP member of Backspace? Share your unique link from this email with your Facebook and Twitter friends; every friend who signs up gets you closer to using the Backspace app as a VIP member! Get additional VIP perks if 10 or more of your friends sign up!\n\n<br><br><br><a href='https://www.facebook.com/sharer/sharer.php?u=".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Facebook</a> <a href='https://twitter.com/home?status=Check%20out%20the%20Backspace%20app%20on%20iOS!%20".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Twitter</a> <a href='".$link."' style='color: #ffffff; font-size: 20px; background-color: #FB4731; text-decoration: none; padding: 10px 20px;'>Link</a><br><br><br> Look out for our next newsletter for more info on the Backspace app launch!\n\n<br><br> Follow us on <a href='https://www.facebook.com/backspaceapp'>Facebook</a> and <a href='https://twitter.com/backspaceapp'>Twitter</a> for updates on Backspace! Want to help shape the future of social media? Give us your feedback and suggestions at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a>.<br><br>&#60; Backspace Team<br><br><hr><div id='footer' style='font-size: 12px; text-align: center;' align='center'>Backspace by WR Industries, LLC | Built in Chicago and St. Louis<br><a href='%unsubscribe_url%'>unsubscribe</a></div></div></div></body></html>";

    $stmt = $db->prepare("INSERT INTO spwp_appwaitlist (email,link,invited_by) VALUES(:email,:link,:invite)"); //adds email to waitlist table
    if (@$stmt->execute(array(':email' => $_POST['email'],':link' => $id,':invite' => $_POST['token'])))
    {
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
	    $stmt = $db->prepare("UPDATE spwp_appwaitlist SET signups=signups+1 WHERE link=?");
		$stmt->execute(array($_POST['token']));		
		
		$mailchimp_result = "You are now on the waitlist! Share the link to move up in line and access Backspace faster!.";
		$result = true;
		header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
		
    }
    else 
    {
	    $mailchimp_result = "An error occurred.";
		$mailchimp_result = 'Error. ' . $errorMessage;
		header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }
}
else
{	
	$errorMessage = "Please fill in all required fields.";
	$mailchimp_result = 'Error. ' . $errorMessage;
	header("Content-type: application/json");
	echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
	die();
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