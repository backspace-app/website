<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //checks to see if current session is still active
		$stmt = $db->prepare("UPDATE spwp_appprofile SET last_active=CURRENT_TIMESTAMP WHERE username=?"); //marks when the user was last active
		$stmt->execute(array($_POST['username']));
		echo '{"success":1}';
	}
	else {
		echo '{"success":0,"error_message":"You have been logged out. Please log back in."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>

