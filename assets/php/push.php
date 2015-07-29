<?php
require_once 'c.php';
require_once 'sp.php';

if($_POST){			
	//Push Notifications
	$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
	$stmt->execute(array($_POST['user_id']));
	$badge = $stmt->fetch(PDO::FETCH_ASSOC);
	$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['user_id']));
	$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$num_tokens = $stmt->rowCount();
	$message = $_POST['message'];
	for ($i = 0; $i < $num_tokens; $i++) {
		pushNotification($token[$i]['device_token'],$message,$badge['nofif']);
	}
	//End Push Notifications
	echo 'Notification sent!';
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>