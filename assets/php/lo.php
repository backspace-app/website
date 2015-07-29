<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //checks to see if current session is still active
		$stmt = $db->prepare("UPDATE spwp_appusers SET session=0 WHERE username=?"); //sets the users session to 0 so they are logged out
		$stmt->execute(array($_POST['username']));
		echo '{"success":1}';
	}
	else {
		echo '{"success":0,"error_message":"An error occurred. Please try again."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>

