<?php
require_once 'c.php';
require_once 'sp.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, username, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['post_id']) { //looks to see if the session is current and if all required information is there
		if ($_POST['type'] == 0) { //submit like
			$stmt = $db->prepare("SELECT * FROM spwp_applikes WHERE post_id=:post_id AND poster=:username");
			$stmt->execute(array(':post_id' => $_POST['post_id'], ':username' => $info['id']));
			$like_count = $stmt->rowCount(); //checks to see if the user has already liked the item
			if ($like_count > 0) {
				$stmt = $db->prepare("DELETE FROM spwp_applikes WHERE post_id=:post_id AND poster=:username"); //remove the current users like
				$stmt->execute(array(':post_id' => $_POST['post_id'], ':username' => $info['id']));
				$stmt = $db->prepare("SELECT * FROM spwp_applikes WHERE post_id=:post_id");
				$stmt->execute(array(':post_id' => $_POST['post_id']));
				$total_like_count = $stmt->rowCount(); //recount the number of likes
				
				$stmt = $db->prepare("SELECT poster, length FROM spwp_appposts WHERE post_id=?");
				$stmt->execute(array($_POST['post_id']));
				$post = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE post_id=:post_id AND sender=:one AND type=0"); //removes the notification if unliked
				$stmt->execute(array(':post_id' => $_POST['post_id'], ':one' => $info['id']));
				
				if ($info['id'] != $post['poster']) { //remove clout for unliking the post; keeps users from liking/unliking to gain clout
					$stmt = $db->prepare("SELECT clout FROM spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster  WHERE spwp_appposts.post_id=?");
					$stmt->execute(array($_POST['post_id'])); //gets the posting users current clout
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$clout = $user['clout'] - .5; //adjust clout for disliking in SECONDS
					$stmt = $db->prepare("UPDATE spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster SET clout=:clout WHERE spwp_appposts.post_id=:post_id"); //updates posting user's clout
					$stmt->execute(array(':clout' => $clout,':post_id' => $_POST['post_id']));
					
					$stmt = $db->prepare("SELECT clout FROM spwp_appprofile WHERE user_id=?"); //gets the commenting user's current clout
					$stmt->execute(array($info['id']));
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("UPDATE spwp_appposts SET length=:time WHERE post_id=:post_id");
					$newtime = $post['length'] - $user['clout']; //adds the current user's clout to the post time
					$stmt->execute(array(':time' => $newtime,':post_id' => $_POST['post_id']));
				}
				
				echo '{"success":1,"likes":'.$total_like_count.',"error_message":"Unliked"}';
			}
			else {
				$stmt = $db->prepare("INSERT INTO spwp_applikes (post_id, poster) VALUES(:post_id,:username)");
				$stmt->execute(array(':post_id' => $_POST['post_id'], ':username' => $info['id']));
				
				$stmt = $db->prepare("SELECT * FROM spwp_applikes WHERE post_id=:post_id");
				$stmt->execute(array(':post_id' => $_POST['post_id']));
				$total_like_count = $stmt->rowCount();
				
				$stmt = $db->prepare("SELECT poster, length FROM spwp_appposts WHERE post_id=?");
				$stmt->execute(array($_POST['post_id']));
				$post = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if ($post['poster'] != $info['id']) { //don't send notification to self
					$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 0, 0)"); //sends the poster a notification
					$stmt->execute(array(':post_id' => $_POST['post_id'], ':two' => $post['poster'], ':one' => $info['id']));
					
					//Push Notifications
					$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
					$stmt->execute(array($post['poster']));
					$badge = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
					$stmt->execute(array($post['poster']));
					$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$num_tokens = $stmt->rowCount();
					$message = $info['username'].' liked your post';
					for ($i = 0; $i < $num_tokens; $i++) {
						pushNotification($token[$i]['device_token'],$message,$badge['notif']);
					}
					//End Push Notifications
					
				}
								
				if ($info['id'] != $post['poster']) { //don't adjust clout if liking own item
					$stmt = $db->prepare("SELECT clout FROM spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster  WHERE spwp_appposts.post_id=?");
					$stmt->execute(array($_POST['post_id'])); //gets the posting users current clout
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$clout = $user['clout'] + .5; //adjust clout for liking in SECONDS
					$stmt = $db->prepare("UPDATE spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster SET clout=:clout  WHERE spwp_appposts.post_id=:post_id"); //updates posting user's clout
					$stmt->execute(array(':clout' => $clout,':post_id' => $_POST['post_id']));
					
					$stmt = $db->prepare("SELECT clout FROM spwp_appprofile WHERE user_id=?"); //gets the commenting user's current clout
					$stmt->execute(array($info['id']));
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("UPDATE spwp_appposts SET length=:time  WHERE post_id=:post_id");
					$newtime = $post['length'] + $user['clout']; //adds the current user's clout to the post time
					$stmt->execute(array(':time' => $newtime,':post_id' => $_POST['post_id']));
				}
				echo '{"success":1,"likes":'.$total_like_count.',"error_message":"Liked!"}';
			}
		}
		elseif ($_POST['type'] == 1) { //view likes
			$stmt = $db->prepare("SELECT user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_applikes ON spwp_applikes.poster = spwp_appprofile.user_id WHERE spwp_applikes.post_id=? GROUP BY user_id"); //gets comment information on the current post
			$stmt->execute(array($_POST['post_id']));
			$like_user = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$user_count = $stmt->rowCount(); //counts the number of likes
			echo '{"success":1,"items":[';
			for ($i = 0; $i < $user_count; $i++) {
				$read_out = $read_out.'{"user_id":'.$like_user[$i]['user_id'].',"username":"'.$like_user[$i]['username'].'","profile_pic":"'.$like_user[$i]['profile_pic'].'"';
				if ($i == ($user_count-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			echo $read_out.']}';
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