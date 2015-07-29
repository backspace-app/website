<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //checks to make sure the session ID is active
		if ($_POST['type'] == 1) {
			$stmt = $db->prepare("UPDATE spwp_appprofile SET is_private=1  WHERE user_id=:id");
			$stmt->execute(array(':id' => $info['id']));
			echo '{"success":1,"error_message":"User profile set to private."}';
		}
		elseif ($_POST['type'] == 2) {
			$stmt = $db->prepare("UPDATE spwp_appprofile SET is_private=0  WHERE user_id=:id");
			$stmt->execute(array(':id' => $info['id']));
			echo '{"success":1,"error_message":"User profile set to public."}';
		}
	}
	else {
		echo '{"success":9,"error_message":"You have been logged out. Please log back in."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>