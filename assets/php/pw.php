<?php
include('pwhsh.php');
require_once 'c.php';


if(isset($_POST['password']) && isset($_POST['verify']))
{

    // check for empty required fields
    if (($_POST['password']=="") || ($_POST['verify']==""))
    {
        $errorMessage = 'Please enter both fields.';
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }

    // form field values
    $username = $_POST['username']; // required
    $token = $_POST['token']; // required
    $pass = $_POST['password']; // required
    $verify = $_POST['verify']; // required

    // form validation
    $error_message = "";
    $stmt = $db->prepare("SELECT * FROM spwp_appforgot WHERE username=? AND token=?");
	$stmt->execute(array($username, $token));
	$validate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pass != $verify)
    {
        $errorMessage = 'The passwords do not match.';
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }
    if ($validate == 0) {
        $errorMessage = 'Please try again.';
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }

    // if there are validation errors	
	$new_pass = create_hash($pass);
	$stmt = $db->prepare("UPDATE spwp_appusers SET password=? WHERE username=?");
 	
    if (@$stmt->execute(array($new_pass, $username)))
    {
        $stmt = $db->prepare("DELETE FROM spwp_appforgot WHERE username=? AND token=?");
        $stmt->execute(array($username, $token));
        $mailchimp_result = 'Success! Your password has been changed!';
		$result = true;
		header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();
    }
    else 
    {
        $errorMessage = 'An error occurred. Please try again later.';
	    $mailchimp_result = 'Error. ' . $errorMessage;
	    header("Content-type: application/json");
		echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
		die();     
    }
}
else
{
    $errorMessage = 'Please fill in all fields.';
	$mailchimp_result = 'Error. ' . $errorMessage;
	header("Content-type: application/json");
	echo json_encode( array( 'message' => $mailchimp_result, 'result' => $result ));
	die(); 
}
?>