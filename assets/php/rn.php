<?php
require_once 'c.php';

if($_GET){
	if (($_GET['a'] == 'NeqrfjJjs7vecFUULYXz') && ($_GET['b'] == 'OluwvzM6VBPyehmsW5OL') && ($_GET['c'] == 'lCKErQtrVEQouLrLNQUb')) { //verifies current session ID is active
		$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE date_sent <= DATE_SUB(NOW(), INTERVAL 15 DAY)"); //deletes blocked words
		$stmt->execute();
	}
	echo '{"success":1}';
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>