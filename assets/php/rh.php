<?php
require_once 'c.php';

if($_GET){
	if (($_GET['a'] == '7v19w2CPFnyY34WepGLc') && ($_GET['b'] == '5S9iMVahFmHeJ8ZWtfC0') && ($_GET['c'] == 'LCLe1GuivTRUjZ0wuO97')) { //verifies current session ID is active
		$stmt = $db->prepare("DELETE spwp_apphashtags FROM spwp_apphashtags JOIN spwp_appposts ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE length<?"); //deletes blocked words
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_apphashtags FROM spwp_apphashtags LEFT JOIN spwp_appposts ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appposts.length IS NULL"); //deletes ghost hashtags
		$stmt->execute();
	}
	echo '{"success":1}';
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>