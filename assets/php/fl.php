<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['user_id'] != '') { //verifies the current session ID is currently active
		if ($_POST['type'] == 0) { //friends list
			$stmt = $db->prepare("SELECT user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_two WHERE spwp_appfriends.user_one=:id AND spwp_appfriends.pending <> 1 ORDER BY spwp_appprofile.username ASC");
			$stmt->execute(array(':id' => $_POST['user_id'])); //gets friend information
			$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_friends = $stmt->rowCount(); //counts the number of friends
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_friends; $i++) {
				$read_out = $read_out.'{"user_id":'.$friends[$i]['user_id'].',"username":"'.$friends[$i]['username'].'","profile_pic":"'.$friends[$i]['profile_pic'].'"';
				if ($i == ($num_friends-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 1) { //who a certain user is following
			$stmt = $db->prepare("SELECT user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appfollowing ON spwp_appprofile.user_id = spwp_appfollowing.user_two WHERE spwp_appfollowing.user_one=:id ORDER BY spwp_appprofile.username ASC");
			$stmt->execute(array(':id' => $_POST['user_id'])); //gets follower information
			$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_friends = $stmt->rowCount(); //counts the number of followers
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_friends; $i++) {
				$read_out = $read_out.'{"user_id":'.$friends[$i]['user_id'].',"username":"'.$friends[$i]['username'].'","profile_pic":"'.$friends[$i]['profile_pic'].'"';
				if ($i == ($num_friends-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 2) { //who is following a certain user
			$stmt = $db->prepare("SELECT user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appfollowing ON spwp_appprofile.user_id = spwp_appfollowing.user_one WHERE spwp_appfollowing.user_two=:id ORDER BY spwp_appprofile.username ASC");
			$stmt->execute(array(':id' => $_POST['user_id'])); //gets follower information
			$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_friends = $stmt->rowCount(); //counts the number of followers
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_friends; $i++) {
				$read_out = $read_out.'{"user_id":'.$friends[$i]['user_id'].',"username":"'.$friends[$i]['username'].'","profile_pic":"'.$friends[$i]['profile_pic'].'"';
				if ($i == ($num_friends-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 3) { //contacts list
			$array = explode(',',$_POST['user_id']);
			$in = str_repeat('?,', count($array) - 1) . '?';
			$stmt = $db->prepare("SELECT user_id, username, profile_pic, phone FROM spwp_appprofile WHERE spwp_appprofile.phone IN ($in) ORDER BY spwp_appprofile.username ASC"); //needs fixing
			$stmt->execute($array); //gets contacts information
			$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_friends = $stmt->rowCount(); //counts the number of followers
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_friends; $i++) {
				$read_out = $read_out.'{"user_id":'.$friends[$i]['user_id'].',"username":"'.$friends[$i]['username'].'","profile_pic":"'.$friends[$i]['profile_pic'].'","phone":'.$friends[$i]['phone'];
				if ($i == ($num_friends-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		else {
			echo '{"success":0,"error_message":"An error occurred. Please try again."}';
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