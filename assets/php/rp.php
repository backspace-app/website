<?php
require_once 'c.php';

if($_GET){
	if (($_GET['a'] == 'ZLpla1Gp1rfOXbLnQJ7T') && ($_GET['b'] == 'NLQORsfKMONPJxhi7My9') && ($_GET['c'] == 'sIRT7wSPEFb6R0euNFPC')) { //verifies current session ID is active
		$stmt = $db->prepare("DELETE spwp_appcomments FROM spwp_appcomments JOIN spwp_appposts ON spwp_appcomments.post_id = spwp_appposts.post_id WHERE length<?"); //deletes comments
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_appcomments FROM spwp_appcomments LEFT JOIN spwp_appposts ON spwp_appcomments.post_id = spwp_appposts.post_id WHERE spwp_appposts.length IS NULL"); //deletes ghost hashtags
		$stmt->execute();
		$stmt = $db->prepare("DELETE spwp_applikes FROM spwp_applikes JOIN spwp_appposts ON spwp_applikes.post_id = spwp_appposts.post_id WHERE length<?"); //deletes likes
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_applikes FROM spwp_applikes LEFT JOIN spwp_appposts ON spwp_applikes.post_id = spwp_appposts.post_id WHERE spwp_appposts.length IS NULL"); //deletes ghost hashtags
		$stmt->execute();
		$stmt = $db->prepare("DELETE spwp_appnotifications FROM spwp_appnotifications JOIN spwp_appposts ON spwp_appnotifications.post_id = spwp_appposts.post_id WHERE length<?"); //deletes groups and removes memberships
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_appnotifications FROM spwp_appnotifications LEFT JOIN spwp_appposts ON spwp_appnotifications.post_id = spwp_appposts.post_id WHERE spwp_appposts.length IS NULL"); //deletes ghost hashtags
		$stmt->execute();
		$stmt = $db->prepare("DELETE spwp_apphashtags FROM spwp_apphashtags JOIN spwp_appposts ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE length<?"); //deletes hashtags
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_apptags FROM spwp_apptags JOIN spwp_appposts ON spwp_apptags.post_id = spwp_appposts.post_id WHERE length<?"); //deletes post tags
		$stmt->execute(array(time()));
		$stmt = $db->prepare("DELETE spwp_apptags FROM spwp_apptags LEFT JOIN spwp_appposts ON spwp_apptags.post_id = spwp_appposts.post_id WHERE spwp_appposts.length IS NULL"); //deletes ghost post tags
		$stmt->execute();
		//Deletes in for loop for post
		$stmt = $db->prepare("SELECT post_id, type, pic, thumb FROM spwp_appposts WHERE length<?"); //gets the count of post
		$stmt->execute(array(time()));
		$post = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$num_items = $stmt->rowCount();
		for ($i = 0; $i < $num_items; $i++) {
			if ($post[$i]['type'] == 1) {
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/p/'.$post[$i]['pic']); //delete photo
			}
			if ($post[$i]['type'] == 2) {
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$post[$i]['pic']); //delete video
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$post[$i]['thumb']); //delete video thumbnail
			}
		}
		$stmt = $db->prepare("DELETE spwp_appposts FROM spwp_appposts WHERE length<?"); //deletes posts
		$stmt->execute(array(time()));
		
		//remove excess files on server
		if ($handle = opendir('../uploads/p')) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != ".." && $entry != "index.php") {
		            $stmt = $db->prepare("SELECT * FROM spwp_appposts WHERE pic=:pic");
					$stmt->execute(array(':pic' => $entry));
					$shouldDelete = $stmt->rowCount();
					if ($shouldDelete == 0) {
						unlink('../uploads/p/'.$entry);
					}
		        }
		    }
		    closedir($handle);
		}
		if ($handle = opendir('../uploads/v')) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != ".." && $entry != "index.php" && stristr($entry, 'jpeg')===FALSE) {
		            $stmt = $db->prepare("SELECT * FROM spwp_appposts WHERE pic=:pic");
					$stmt->execute(array(':pic' => $entry));
					$shouldDelete = $stmt->rowCount();
					if ($shouldDelete == 0) {
						unlink('../uploads/v/'.$entry);
						unlink('../uploads/v/'.left($entry,len($entry)-4).'_t.jpeg');
					}
		        }
		    }
		    closedir($handle);
		}
		//remove access files on server
		
	}
	echo '{"success":1}';
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>