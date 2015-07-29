<?php
require_once 'c.php';
require_once 'sp.php';

/*
	Notification Messages:
		0 = Like post
		1 = Comment to comment creator
		2 = Comment to other commentors
		3 = Friend request
		4 = New follower
		5 = Tagged in a post
		6 = Tagged in a comment
		7 = Accepted friend request
		
		** Don't forget to add to the CASE statement in the first query to add a new notification message **
		** Don't forget to update the query below to adjust for viewed on non-post notifications (ex. friend request) **
		
	Notification Types:
		0 = Not viewed, not expiring soon
		1 = Not viewed, expiring soon
		2 = Viewed, not expiring soon
		3 = Viewed, expiring soon
		4 = Expired
		5 = Friend Request
*/

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //checks to see if current session ID is active
				
		$stmt = $db->prepare("SELECT spwp_appprofile.user_id, spwp_appnotifications.post_id, spwp_appprofile.username, spwp_appprofile.profile_pic, CASE WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' liked your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=0 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' liked your post') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' commented on your post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=1 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' commented on your post') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' also commented on: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=2 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' also commented on a post you commented on') WHEN spwp_appnotifications.type=3 THEN CONCAT(spwp_appprofile.username,' sent you a friend request') WHEN spwp_appnotifications.type=4 THEN CONCAT(spwp_appprofile.username,' is now following you') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=5 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in a post') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)>0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment: ', LEFT(spwp_appposts.content,25),'...') WHEN spwp_appnotifications.type=6 AND LENGTH(spwp_appposts.content)<=0 THEN CONCAT(spwp_appprofile.username,' tagged you in their comment') WHEN spwp_appnotifications.type=7 THEN CONCAT(spwp_appprofile.username,' accepted your friend request') ELSE 0 END AS notif_msg, CASE WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP()  > 3600) THEN 0 WHEN spwp_appnotifications.viewed=0 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600) THEN 1 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() > 3600) THEN 2 WHEN spwp_appnotifications.viewed=1 AND (spwp_appposts.length - UNIX_TIMESTAMP() <= 3600 AND spwp_appposts.length - UNIX_TIMESTAMP() >=0) THEN 3 WHEN spwp_appnotifications.type=3 THEN 5 ELSE 4 END as notif_type FROM spwp_appnotifications JOIN spwp_appprofile ON spwp_appnotifications.sender = spwp_appprofile.user_id LEFT JOIN spwp_appposts ON spwp_appnotifications.post_id = spwp_appposts.post_id WHERE spwp_appnotifications.poster=:id GROUP BY user_id, spwp_appnotifications.post_id, username, profile_pic, viewed ORDER BY spwp_appnotifications.notif_id DESC");
		$stmt->execute(array(':id' => $info['id']));
		$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$num_notifications = $stmt->rowCount(); //counts the number of notifications
				
		$read_out = '{"success":1,"items":[';
		for ($i = 0; $i < $num_notifications; $i++) {
			//fixing for broken emoji
			if (strpos($notifications[$i]['notif_msg'],'\u',strlen($notifications[$i]['notif_msg'])-16)>0){
				$end = strpos($notifications[$i]['notif_msg'],'\u',strlen($notifications[$i]['notif_msg'])-16);
				$notif = substr($notifications[$i]['notif_msg'],0,$end).'...';
			} 
			else { 
				$notif = $notifications[$i]['notif_msg'];
			}
			//end fixing for broken emoji
			
			$read_out = $read_out.'{"user_id":'.$notifications[$i]['user_id'].',"post":'.$notifications[$i]['post_id'].',"sender":"'.$notifications[$i]['username'].'","profile_pic":"'.$notifications[$i]['profile_pic'].'","notif_msg":"'.$notif.'","notif_type":'.$notifications[$i]['notif_type'];
			if ($i == ($num_notifications-1)) {
				$read_out = $read_out.'}';
			}
			else {
				$read_out = $read_out.'},';
			}
		}
		$read_out = $read_out.']}';
		echo $read_out;
		
		$stmt = $db->prepare("UPDATE spwp_appnotifications SET viewed = 1 WHERE poster=:id AND type IN(3,4,7) AND viewed <> 1"); //sets all notifications to viewed
		$stmt->execute(array(':id' => $info['id']));
		$stmt = $db->prepare("UPDATE spwp_appnotifications JOIN spwp_appposts ON spwp_appposts.post_id = spwp_appnotifications.post_id SET viewed = 1 WHERE spwp_appnotifications.poster=:id AND length<? AND viewed <> 1"); //sets all notifications to viewed
		$stmt->execute(array(':id' => $info['id'],time()));
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