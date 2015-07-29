<?php
require_once 'c.php';
require_once 'sp.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, username, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['post_id'] && $_POST['content'] <> '') { //looks to see if the session is current and if all required information is there
		$stmt = $db->prepare("INSERT INTO spwp_appcomments (post_id, poster, content) VALUES(:post_id,:username,:content)");
		$stmt->execute(array(':post_id' => $_POST['post_id'], ':username' => $info['id'], ':content' => substr(json_encode($_POST['content']),1,-1))); //inserts comment to the comment table
		$stmt = $db->prepare("SELECT poster, length FROM spwp_appposts WHERE spwp_appposts.post_id=:id");
		$stmt->execute(array(':id' => $_POST['post_id'])); //gets user id of the user who posted the content
		$post = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = $db->prepare("SELECT id, user_id, username, profile_pic, content FROM spwp_appprofile JOIN spwp_appcomments ON spwp_appcomments.poster = spwp_appprofile.user_id WHERE spwp_appcomments.post_id=? ORDER BY id"); //gets comment information on the current post
		$stmt->execute(array($_POST['post_id']));
		$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$comments_count = $stmt->rowCount(); //counts the number of comments
		
		$stmt = $db->prepare("SELECT poster FROM spwp_appcomments WHERE post_id=:post_id AND poster=:poster");
		$stmt->execute(array(':post_id' => $_POST['post_id'],':poster' => $info['id']));
		$already_commented = $stmt->rowCount(); //checks to see if the user commenting has already commented on the post before
		
		if ($already_commented == 0 && $info['id'] != $post['poster']) { //if commenting user hasn't commented before, increase posting user's clout
			$stmt = $db->prepare("SELECT clout FROM spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster  WHERE spwp_appposts.post_id=?");
			$stmt->execute(array($_POST['post_id'])); //gets the posting users current clout
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			$clout = $user['clout'] + 2; //adjust clout for liking in SECONDS
			$stmt = $db->prepare("UPDATE spwp_appprofile JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster SET clout=:clout  WHERE spwp_appposts.post_id=:post_id"); //updates posting user's clout
			$stmt->execute(array(':clout' => $clout,':post_id' => $_POST['post_id']));
			
			$stmt = $db->prepare("SELECT clout FROM spwp_appprofile WHERE user_id=?"); //gets the commenting user's current clout
			$stmt->execute(array($info['id']));
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt = $db->prepare("UPDATE spwp_appposts SET length=:time  WHERE post_id=:post_id");
			$newtime = $post['length'] + $user['clout']; //adds the current user's clout to the post time
			$stmt->execute(array(':time' => $newtime,':post_id' => $_POST['post_id']));
		}
		
		if ($post['poster'] != $info['id']) { //don't send notification to self
			$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 1, 0)"); //sends the poster a notification
			$stmt->execute(array(':post_id' => $_POST['post_id'], ':two' => $post['poster'], ':one' => $info['id']));
			
			//Push Notifications
			$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
			$stmt->execute(array($post['poster']));
			$badge = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
			$stmt->execute(array($post['poster']));
			$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_tokens = $stmt->rowCount();
			$message = $info['username'].' commented on your post';
			for ($i = 0; $i < $num_tokens; $i++) {
				pushNotification($token[$i]['device_token'],$message,$badge['notif']);
			}
			//End Push Notifications
				
		}
		
		$stmt = $db->prepare("SELECT user_id FROM spwp_appprofile JOIN spwp_appcomments ON spwp_appcomments.poster = spwp_appprofile.user_id WHERE spwp_appcomments.post_id=? GROUP BY user_id"); //gets comment information on the current post
		$stmt->execute(array($_POST['post_id']));
		$comment_user = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$user_count = $stmt->rowCount(); //counts the number of comments
		for ($i = 0; $i < $user_count; $i++) { //don't send notification to self or content creator
			if (($comment_user[$i]['user_id'] != $info['id']) && ($comment_user[$i]['user_id'] != $post['poster'])) { 
				$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 2, 0)"); //sends the commentor a new notification
				$stmt->execute(array(':post_id' => $_POST['post_id'], ':two' => $comment_user[$i]['user_id'], ':one' => $info['id']));
				
				//Push Notifications
				$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
				$stmt->execute(array($comment_user[$i]['user_id']));
				$badge = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
				$stmt->execute(array($comment_user[$i]['user_id']));
				$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$num_tokens = $stmt->rowCount();
				$message = $info['username'].' commented on a post you commented on';
				for ($z = 0; $z < $num_tokens; $z++) {
					pushNotification($token[$z]['device_token'],$message,$badge['notif']);
				}
				//End Push Notifications
				
			}
		}
		
		//tagging user
		if (!empty($_POST['tagged_users'])) {
			$array = explode(',',$_POST['tagged_users']);
			for ($i=0; $i<=count($array)-1; $i++) {
				if (is_string($array[$i]) && !is_numeric($array[$i])) { //gets user ID if a string is sent
					if ($array[$i] == $info['username']) {
						$array[$i] = $info['id'];
					}
					else {
						$stmt = $db->prepare("SELECT user_id FROM spwp_appprofile JOIN spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_two WHERE spwp_appprofile.username=? AND spwp_appfriends.user_one=? AND spwp_appfriends.pending=0");
						$stmt->execute(array($array[$i], $info['id']));
						$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
						$num_ids = $stmt->rowCount();
						if ($num_ids == 0) { //go to the next item if username is not found							
							continue 1;
						}
						$array[$i] = $search[0]['user_id'];
					}
				}
				$stmt = $db->prepare("INSERT INTO spwp_apptags (post_id, user_id) VALUES(:post_id, :user)"); //sends the poster a notification
				$stmt->execute(array(':post_id' => $_POST['post_id'], ':user' => $array[$i]));
				if ($array[$i] != $info['id']) {
					$stmt = $db->prepare("INSERT INTO spwp_appnotifications (post_id, poster, sender, type, viewed) VALUES(:post_id, :two, :one, 5, 0)"); //sends the poster a notification
					$stmt->execute(array(':post_id' => $_POST['post_id'], ':two' => $array[$i], ':one' => $info['id']));
					//Push Notifications
					$stmt = $db->prepare("SELECT COUNT(notif_id) AS notif FROM spwp_appnotifications WHERE poster=? AND viewed=0"); //gets the badge count
					$stmt->execute(array($array[$i]));
					$badge = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("SELECT device_token FROM spwp_apptokens WHERE user_id=?"); //gets the current session ID of the user
					$stmt->execute(array($array[$i]));
					$token = $stmt->fetchAll(PDO::FETCH_ASSOC);
					$num_tokens = $stmt->rowCount();
					$message = $info['username'].' tagged you in their post';
					for ($z = 0; $z < $num_tokens; $z++) {
						pushNotification($token[$z]['device_token'],$message,$badge['notif']);
					}
					//End Push Notifications
				}	
			}
		}
		//end tagging user
		
		$read_out = '{"success":1,"error_message":"Comment posted.","items":[';
		for ($i = 0; $i < $comments_count; $i++) {
			$read_out = $read_out.'{"comment_id":'.$comments[$i]['id'].',"username":"'.$comments[$i]['username'].'","profile_pic":"'.$comments[$i]['profile_pic'].'","comment":"'.$comments[$i]['content'].'"';
			if ($i == ($comments_count-1)) {
				$read_out = $read_out.'}';
			}
			else {
				$read_out = $read_out.'},';
			}
		}
		echo $read_out.']}';
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