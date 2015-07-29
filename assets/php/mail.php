<?php

if ( isset($_POST['email']) && $_POST['email'] != '' ) {
	try {
	    $db = new PDO('mysql:host=173.194.253.146:3306;dbname=bspacewpdb;charset=utf8', 'app', '84Qz4Cg3PTKe4P68LZ*d8my)oAf9', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
	} catch(PDOException $ex) {
	}

    // form field values
    $semail = $_POST['email']; // required

    // form validation
    $error_message = "";
    
    //Set result
    $result = false;

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
    
    
    $stmt = $db->prepare("SELECT * FROM bs_email WHERE address=?");
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

    $stmt = $db->prepare("INSERT INTO bs_email (address, sent) VALUES (?,'N')");
    if (@$stmt->execute(array($semail)))
    {
	   $mailchimp_result = 'Success! Your email has been added.';
	   $result = true;
	   header("Content-type: application/json");
	   echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
	   die();
    }
    else 
    {
		$mailchimp_result = 'An error occurred.';
		$mailchimp_result = 'Error. ' . $errorMessage;
		header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }

}else {

  header("Content-type: application/json");
  echo json_encode( array( 'message' => 'Please provide a valid email.', 'result' => false ));
  die();
  
}

?>