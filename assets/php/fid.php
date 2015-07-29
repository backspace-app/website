<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT session, id FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['post_id'] <> "") { //looks to see if the session is current and if all required information is there
		$stmt = $db->prepare("SELECT group_id, poster FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.post_id=?"); //verifies user should be allowed to view content
		$stmt->execute(array($_POST['post_id']));
		$verify = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt = $db->prepare("SELECT user_one FROM spwp_appfriends WHERE spwp_appfriends.user_one=:id AND spwp_appfriends.user_two=:id2 AND spwp_appfriends.pending <> 1"); //verifies user should be allowed to view content
		$stmt->execute(array(':id' => $info['id'],':id2' => $verify['poster']));
		$isfriend = $stmt->rowCount();
		$stmt = $db->prepare("SELECT group_id FROM spwp_appgroups WHERE spwp_appgroups.group_member=:id AND spwp_appgroups.group_id=:id2"); //verifies user should be allowed to view content
		$stmt->execute(array(':id' => $info['id'],':id2' => $verify['group_id']));
		$ingroup = $stmt->rowCount();
		if (($verify['group_id'] == 0) || ($isfriend > 0 && $verify['group_id'] == 1) || ($isfriend > 0 && $ingroup > 0) || ($verify['poster'] == $info['id'])) { //verifies user should be allowed to view content
			$stmt = $db->prepare("SELECT * FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.post_id=?");
			$stmt->execute(array($_POST['post_id'])); //gets post information
			$post = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($post['length'] < time()){
				echo '{"success":0,"error_message":"Post expired."}';
				exit;
			}
			
			$stmt = $db->prepare("SELECT poster FROM spwp_applikes WHERE spwp_applikes.poster=? AND spwp_applikes.post_id=?");
			$stmt->execute(array($info['id'],$_POST['post_id']));
			$is_liked = $stmt->rowCount(); //checks to see if user liked post
			if ($is_liked > 0) {
				$liked = 1;
			}
			else {
				$liked = 0;
			}
			
			if ($post['poster'] == $info['id']) { //determine if my own post
				$mine = 1;
			}
			else {
				$mine = 0;
			}
			
			$stmt = $db->prepare("SELECT username FROM spwp_appprofile JOIN spwp_applikes ON spwp_applikes.poster = spwp_appprofile.user_id WHERE spwp_applikes.post_id=?"); //gets usernames of all who liked content
			$stmt->execute(array($_POST['post_id']));
			$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$likes_count = getNumber($stmt->rowCount(),1); //counts number of likes
			//if ($likes_count > 0) {
			//	$ids = $likes[0]['username'];
			//}
			//for ($i = 1; $i < $likes_count; $i++) {
			//	$ids = $ids.','.$likes[$i]['username']; //creates array of usernames who like post
			//}
			
			$stmt = $db->prepare("SELECT user_id, username, profile_pic, content, id FROM spwp_appprofile JOIN spwp_appcomments ON spwp_appcomments.poster = spwp_appprofile.user_id WHERE spwp_appcomments.post_id=? ORDER BY id"); //gets comment information
			$stmt->execute(array($_POST['post_id']));
			$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$comments_count = getNumber($stmt->rowCount(),1); //simplifies the comment count
			$read_out = '{"success":1,"user_id":'.$post['user_id'].',"username":"'.$post['username'].'","mine":'.$mine.',"type":'.$post['type'].',"content":"'.$post['content'].'","pic":"'.$post['pic'].'","thumb":"'.$post['thumb'].'","comments":'.$comments_count.',"likes":'.$likes_count.',"is_liked":'.$liked.',"time":'.$post['length'].',"items":[';
			//$read_out = $read_out.'","likes":"'.$ids.'","time":'.$post['length'];
			for ($i = 0; $i < $comments_count; $i++) {
				$read_out = $read_out.'{"user_id":'.$comments[$i]['user_id'].',"username":"'.$comments[$i]['username'].'","profile_pic":"'.$comments[$i]['profile_pic'].'","comment_id":'.$comments[$i]['id'].',"comment":"'.$comments[$i]['content'].'"';
				if ($i == ($comments_count-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			echo $read_out.']}';
			//update notifications for this post to viewed
			$stmt = $db->prepare("UPDATE spwp_appnotifications SET viewed = 1 WHERE poster=:id AND post_id=:post AND viewed <> 1"); //sets all notifications to viewed
			$stmt->execute(array(':id' => $info['id'],':post' => $_POST['post_id']));
			//end notification updating
		}
		else {
			echo '{"success":0,"error_message":"You do not have permission to view this post."}';
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

function getNumber($number,$type){
	if ($number >= 100000000 && $type == 1) {
		$return = round($number/100000000,0)."M";
	}
	if ($number >= 1000000 && $type == 1) {
		$return = round($number/1000000,1)."M";
	}
	if ($number > 100000 && $type == 1) {
		$return = round($number/100000,0)."K";
	}
	if ($number > 1000 && $type == 1) {
		$return = round($number/1000,0)."K";
	}
	if ($number < 1000 && $type == 1) {
		$return = $number;
	}
	if ($number >= 3600 && $type == 2) {
		$return = round($number/3600,1)."h";
	}
	if ($number < 3600 && $type == 2) {
		$return = round($number/60,0)."m";
	}
	return $return;
}
?>