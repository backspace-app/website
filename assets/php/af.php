<?php
require_once 'c.php';
require_once 'sp.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, username, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //verifies current session ID is active
		if ($_POST['userid'] == NULL) {
			echo '{"success":0,"error_message":"An error occurred. Please try again."}';
		}
		else {
			if ($_POST['type'] == 1) { //follow
				$stmt = $db->prepare("SELECT is_private FROM spwp_appprofile WHERE user_id=?"); //gets the current session ID of the user
				$stmt->execute(array($_POST['userid']));
				$user = $stmt->fetch(PDO::FETCH_ASSOC);
				if($info['id'] == $_POST['userid']){ //check to make sure you aren't trying to follow yourself
					echo '{"success":0,"error_message":"You cannot follow yourself!","status":0}';
					exit();
				}
				elseif ($user['is_private'] == 1) {
					echo '{"success":0,"error_message":"You cannot follow a private account!","status":0}';
					exit();
				}
				else {
					$stmt = $db->prepare("INSERT INTO spwp_appfollowing (user_one, user_two) VALUES(:one, :two)"); //inserts user to following table
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(0, :two, :one, 4, 0)"); //sends the poster a notification
					$stmt->execute(array(':two' => $_POST['userid'], ':one' => $info['id']));
					
					//Push Notifications
					$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
					$stmt->execute(array($_POST['userid']));
					$badge = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
					$stmt->execute(array($_POST['userid']));
					$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$num_tokens = $stmt->rowCount();
					$message = $info['username'].' is now following you';
					for ($i = 0; $i < $num_tokens; $i++) {
						pushNotification($token[$i]['device_token'],$message,$badge['notif']);
					}
					//End Push Notifications
					
					echo '{"success":1,"error_message":"Following!","status":1}';
					exit();
				}
			}
			elseif ($_POST['type'] == 2) { //friend
				if($info['id'] == $_POST['userid']){ //check to make sure you aren't trying to friend yourself
					echo '{"success":0,"error_message":"You cannot friend yourself!","status":0}';
					exit();
				}
				else {
					$stmt = $db->prepare("INSERT INTO spwp_appfriends (user_one, user_two, pending) VALUES(:one, :two, 1)"); //friend require request is a two way relationship
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("INSERT INTO spwp_appfriends (user_one, user_two, pending) VALUES(:two, :one, 1)"); //friend require request is a two way relationship
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(0, :two, :one, 3, 0)"); //sends friend request notification
					$stmt->execute(array(':two' => $_POST['userid'], ':one' => $info['id']));
					
					//Push Notifications
					$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
					$stmt->execute(array($_POST['userid']));
					$badge = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
					$stmt->execute(array($_POST['userid']));
					$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$num_tokens = $stmt->rowCount();
					$message = $info['username'].' sent you a friend request';
					for ($i = 0; $i < $num_tokens; $i++) {
						pushNotification($token[$i]['device_token'],$message,$badge['notif']);
					}
					//End Push Notifications
					
					echo '{"success":1,"error_message":"Friend request sent!","status":1}';	
					exit();
				}
			}
			elseif ($_POST['type'] == 3) { //unfriend/unfollow
				$stmt = $db->prepare("DELETE FROM spwp_appfollowing WHERE user_one=:one AND user_two=:two"); //delete user from following table
				$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
				$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE (user_one=:one AND user_two=:two) OR (user_one=:two2 AND user_two=:one2)"); //delete user from friend table
				$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid'],':one2' => $info['id'], ':two2' => $_POST['userid']));
				$stmt = $db->prepare("DELETE FROM spwp_appgroups WHERE group_creator=:one AND group_member=:two"); //delete user from group table
				$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
				echo '{"success":1,"error_message":"No longer connected...","status":0}';
				exit();
			}
			else { //responding to friend request
				if ($_POST['accept'] == 1) { //accept request
					$stmt = $db->prepare("DELETE FROM spwp_appfollowing WHERE user_one=:one AND user_two=:two"); //remove user if following
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appfollowing WHERE user_one=:three AND user_two=:four"); //remove user if following
					$stmt->execute(array(':three' => $_POST['userid'], ':four' => $info['id']));
					$stmt = $db->prepare("UPDATE spwp_appfriends SET pending = 0 WHERE (user_one=:one AND user_two=:two) OR (user_one=:two2 AND user_two=:one2)"); //set pending=0
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid'],':one2' => $info['id'], ':two2' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE poster=:one AND sender=:two AND type=3"); //deletes friend request notification
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(0, :two, :one, 7, 0)"); //sends friend request notification
					$stmt->execute(array(':two' => $_POST['userid'], ':one' => $info['id']));
					$stmt = $db->prepare("SELECT spwp_appprofile.user_id, spwp_appnotifications.post_id, spwp_appprofile.username, spwp_appprofile.profile_pic, CASE WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' liked your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' liked your post') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' commented on your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' commented on your post') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' also commented on: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' also commented on a post you commented on') WHEN spwp_appnotifications.type=3 THEN CONCAT(spwp_appprofile.username,' sent you a friend request') WHEN spwp_appnotifications.type=4 THEN CONCAT(spwp_appprofile.username,' is now following you') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment') WHEN spwp_appnotifications.type=7 THEN CONCAT(spwp_appprofile.username,' accepted your friend request') ELSE 0 END AS notif_msg, CASE WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP()  > 3600) THEN 0 WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600) THEN 1 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() > 3600) THEN 2 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600) THEN 3  WHEN spwp_appnotifications.type=3 THEN 5 ELSE 4 END as notif_type FROM spwp_appnotifications JOIN spwp_appprofile ON spwp_appnotifications.sender = spwp_appprofile.user_id LEFT JOIN spwp_appposts ON spwp_appnotifications.post_id = spwp_appposts.post_id WHERE spwp_appnotifications.poster=:id GROUP BY user_id, spwp_appnotifications.post_id, username, profile_pic, viewed ORDER BY spwp_appnotifications.notif_id DESC");
					$stmt->execute(array(':id' => $info['id']));
					$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC); //gets notification information
					$num_notifications = $stmt->rowCount(); //counts number of notifications
					
					//Push Notifications
					$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
					$stmt->execute(array($_POST['userid']));
					$badge = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
					$stmt->execute(array($_POST['userid']));
					$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$num_tokens = $stmt->rowCount();
					$message = $info['username'].' accepted your friend request';
					for ($i = 0; $i < $num_tokens; $i++) {
						pushNotification($token[$i]['device_token'],$message,$badge['notif']);
					}
					//End Push Notifications
					
					echo '{"success":1,"error_message":"Friend request accepted!","status":1,"items":[';
					for ($i = 0; $i < $num_notifications; $i++) {
						
						$read_out = $read_out.'{"user_id":'.$notifications[$i]['user_id'].',"post":'.$notifications[$i]['post_id'].',"sender":"'.$notifications[$i]['username'].'","profile_pic":"'.$notifications[$i]['profile_pic'].'","notif_msg":"'.$notifications[$i]['notif_msg'].'","notif_type":'.$notifications[$i]['notif_type'];
						if ($i == ($num_notifications-1)) {
							$read_out = $read_out.'}';
						}
						else {
							$read_out = $read_out.'},';
						}
					}
					$read_out = $read_out.']}';
					echo $read_out;
					exit();
				}
				elseif($_POST['accept'] == 2) { //deny request
					$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE user_one=:one AND user_two=:two AND pending=1");
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE user_one=:two AND user_two=:one AND pending=1");
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE poster=:one AND sender=:two AND type=3");
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("SELECT spwp_appprofile.user_id, spwp_appnotifications.post_id, spwp_appprofile.username, spwp_appprofile.profile_pic, CASE WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' liked your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' liked your post') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' commented on your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' commented on your post') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' also commented on: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' also commented on a post you commented on') WHEN spwp_appnotifications.type=3 THEN CONCAT(spwp_appprofile.username,' sent you a friend request') WHEN spwp_appnotifications.type=4 THEN CONCAT(spwp_appprofile.username,' is now following you') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment') WHEN spwp_appnotifications.type=7 THEN CONCAT(spwp_appprofile.username,' accepted your friend request') ELSE 0 END AS notif_msg, CASE WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP()  > 3600) THEN 0 WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600) THEN 1 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() > 3600) THEN 2 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600) THEN 3  WHEN spwp_appnotifications.type=3 THEN 5 ELSE 4 END as notif_type FROM spwp_appnotifications JOIN spwp_appprofile ON spwp_appnotifications.sender = spwp_appprofile.user_id LEFT JOIN spwp_appposts ON spwp_appnotifications.post_id = spwp_appposts.post_id WHERE spwp_appnotifications.poster=:id GROUP BY user_id, spwp_appnotifications.post_id, username, profile_pic, viewed ORDER BY spwp_appnotifications.notif_id DESC");
					$stmt->execute(array(':id' => $info['id']));
					$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC); //gets notification information
					$num_notifications = $stmt->rowCount(); //counts number of notifications
					echo '{"success":1,"error_message":"Friend request denied.","status":0,"items":[';
					for ($i = 0; $i < $num_notifications; $i++) {
						$read_out = $read_out.'{"user_id":'.$notifications[$i]['user_id'].',"post":'.$notifications[$i]['post_id'].',"sender":"'.$notifications[$i]['username'].'","profile_pic":"'.$notifications[$i]['profile_pic'].'","notif_msg":"'.$notifications[$i]['notif_msg'].'","notif_type":'.$notifications[$i]['notif_type'];
						if ($i == ($num_notifications-1)) {
							$read_out = $read_out.'}';
						}
						else {
							$read_out = $read_out.'},';
						}
					}
					$read_out = $read_out.']}';
					echo $read_out;
					exit();
				}
				elseif($_POST['accept'] == 3) { //cancel request
					$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE user_one=:one AND user_two=:two AND pending=1");
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE user_one=:two AND user_two=:one AND pending=1");
					$stmt->execute(array(':one' => $info['id'], ':two' => $_POST['userid']));
					$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE poster=:two AND sender=:one AND type=3");
					$stmt->execute(array(':two' => $_POST['userid'], ':one' => $info['id']));
					echo '{"success":1,"error_message":"Friend request cancelled."}';
					exit();
				}
				else {
					echo '{"success":0,"error_message":"An error occurred. Please try again."}';
				}
			}
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