<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //verifies current session ID is active
		$stmt = $db->prepare("DELETE FROM spwp_appblockedwords WHERE user_id=?"); //deletes blocked words
		$stmt->execute(array($info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appcomments WHERE poster=?"); //deletes comments
		$stmt->execute(array($info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appfollowing WHERE user_one=? OR user_two=?"); //deletes following and followers
		$stmt->execute(array($info['id'],$info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appforgot WHERE username=?"); //deletes forgot password requests
		$stmt->execute(array($_POST['username']));
		$stmt = $db->prepare("DELETE FROM spwp_appfriends WHERE user_one=? OR user_two=?"); //deletes friends
		$stmt->execute(array($info['id'],$info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appgroups WHERE group_creator=? OR group_member=?"); //deletes groups and removes memberships
		$stmt->execute(array($info['id'],$info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_applikes WHERE poster=?"); //deletes likes
		$stmt->execute(array($info['id']));
		//$stmt = $db->prepare("DELETE FROM spwp_appmessages WHERE poster=? OR receiver=?"); //deletes private messages
		//$stmt->execute(array($info['id'],$info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE poster=? OR sender=?"); //deletes groups and removes memberships
		$stmt->execute(array($info['id'],$info['id']));
		//Deletes in for loop for post
		$stmt = $db->prepare("SELECT post_id FROM spwp_appposts WHERE poster=?"); //gets the count of post
		$stmt->execute(array($info['id']));
		$post = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$num_items = $stmt->rowCount();
		for ($i = 0; $i < $num_items; $i++) {
			$stmt = $db->prepare("DELETE FROM spwp_apphashtags WHERE post_id=?"); //deletes hashtags
			$stmt->execute(array($post[$i]['post_id']));
			$stmt = $db->prepare("DELETE FROM spwp_apptags WHERE post_id=?"); //deletes tags
			$stmt->execute(array($post[$i]['post_id']));
			$stmt = $db->prepare("SELECT type, pic, thumb FROM spwp_appposts WHERE post_id=?");
			$stmt->execute(array($post[$i]['post_id']));
			$delete = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($delete['type'] == 1) {
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/p/'.$delete['pic']); //delete photo
			}
			if ($delete['type'] == 2) {
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$delete['pic']); //delete video
				unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$delete['thumb']); //delete video thumbnail
			}
			$stmt = $db->prepare("DELETE FROM spwp_appposts WHERE post_id=?"); //deletes post content
			$stmt->execute(array($post[$i]['post_id']));
		}
		//Deletes in for loop for post
		$stmt = $db->prepare("SELECT profile_pic FROM spwp_appprofile WHERE user_id=:id"); //gets the current profile picture name
		$stmt->execute(array(':id' => $info['id']));
		$pic = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($pic['profilepic'] != "default_profile.png") {
			unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/pp/'.$delete['pic']); //if profile picture is not default, delete it
		}
		$stmt = $db->prepare("DELETE FROM spwp_appprofile WHERE user_id=?"); //deletes profile
		$stmt->execute(array($info['id']));
		$stmt = $db->prepare("DELETE FROM spwp_appusers WHERE id=?"); //deletes user information
		$stmt->execute(array($info['id']));
		echo '{"success":1,"error_message":"Account successfully deleted."}';
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