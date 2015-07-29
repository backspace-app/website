<?php
/* =====================================================
 * change this to the email you want the form to send to
 * ===================================================== */
require 'vendor/autoload.php';
use Mailgun\Mailgun;
try {
	    $db = new PDO('mysql:host=173.194.253.146:3306;dbname=bspacewpdb;charset=utf8', 'app', '84Qz4Cg3PTKe4P68LZ*d8my)oAf9', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
	} catch(PDOException $ex) {
	}

    $stmt = $db->prepare("SELECT address FROM bs_email WHERE sent='N'");
	$stmt->execute();
	$email = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$email_count = $stmt->rowCount();
	echo($email_count);
	for ($i = 0; $i < $email_count; $i++) {	
		$email_message = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' 'http://www.w3.org/TR/REC-html40/loose.dtd'><html><body style='font-family: 'Open Sans Condensed', sans-serif; font-size: 20px;' bgcolor='#FB4731'><style type='text/css'>.btn:hover { text-decoration: none !important; } a{color: #FB4731; text-decoration: none;}</style><div id='container' style='background-color: #FFF; width: 70%; min-width: 600px; margin: 20px auto 5px;'><div id='main' style='padding: 30px;'><img src='http://backspaceapp.co/assets/img/logo.png' width='250px' style='margin: 5px auto;'><hr><h1 style='font-size: 25px; font-weight: 600;'>Welcome ".$email[$i]['address']."!</h1> You and a select few social media pioneers have been chosen to be the first to experience Backspace, a new age of social media.\n\n<br><br>Within the next few days, you’ll be receiving an e-mail from <a href='https://itunes.apple.com/us/app/testflight/id899247664?mt=8'>TestFlight</a>. Click on the link provided in the e-mail and you’ll be able to download Backspace and give it a test drive. Once installed through <a href='https://itunes.apple.com/us/app/testflight/id899247664?mt=8'>TestFlight</a>, create your profile and dive right in; skip right over the waitlist!  If you get 3 of your friends to beta test Backspace for iPhone, we'll give you a <b>free $25 gift card</b> for restaurant.com. How cool is that? Just let us know who you invited!\n\n<br><br>You might ask yourself, “What should I expect from the Backspace preview?”. We want you to use Backspace how YOU want. There is no “right way” to “Backspace”. Tell us what you like and don’t like; we want to mold Backspace based on our users.\n\n<br><br>You’ll also be receiving an e-mail from the Backspace Beta Forum, where we would like to hear from you! We want your feedback on the entire Backspace experience. Let us know if you find any bugs, or something just didn’t work as you thought it should. You can also give us a shout at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a> and tell us your thoughts on the Backspace preview!\n\n<br><br>If you don't get an e-mail from TestFlight in the next few days, please check your spam folder or contact us at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a>.\n\n<br><br>Best,\n\n<br><br><font size='+1'><i>Bryan, Justin and the Backspace Team\n\n</i></font><br><br><br><a href='http://on.fb.me/1FJ5pRQ'>LIKE</a> us on Facebook and <a href='http://bit.ly/backspacetwitter'>FOLLOW</a> us on Twitter for updates on Backspace! Want to help shape the future of social media? Give us your feedback and suggestions at <a href='mailto:info@backspaceapp.co'>info@backspaceapp.co</a>.<hr><div id='footer' style='font-size: 12px; text-align: center;' align='center'>Backspace by WR Industries, LLC | Built in Chicago and St. Louis<br><a href='%unsubscribe_url%'>unsubscribe</a></div></div></div></body></html>";
	    $stmt = $db->prepare("UPDATE bs_email SET sent='Y' WHERE address=:email"); //adds email to waitlist table
	    if (@$stmt->execute(array(':email' => $email[$i]['address'])))
	    {
		    //using mailgun
			# Instantiate the client.
			$mgClient = new Mailgun('key-4f73ad99ca41c8425b5962b698c79a68');
			$domain = "backspaceapp.co";
			# Make the call to the client.
			$result = $mgClient->sendMessage($domain, array(
			    'from'    => 'Backspace <noreply@backspaceapp.co>',
			    'to'      => $email[$i]['address'],
			    'subject' => "Welcome to the Backspace Beta!",
			    'html'    => $email_message
			));
			//using mailgun
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
    echo ' E-mails sent!'; 
?>