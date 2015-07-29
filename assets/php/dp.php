<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //verifies current session ID is active
		if ($_POST['type'] == 1) { //remove post
			$stmt = $db->prepare("SELECT poster FROM spwp_appposts WHERE post_id=?"); //gets users ID from post
			$stmt->execute(array($_POST['post_id']));
			$post = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($info['id'] == $post['poster']) { //verifies user created the post
				$stmt = $db->prepare("SELECT type, pic, thumb FROM spwp_appposts WHERE post_id=?");
				$stmt->execute(array($_POST['post_id']));
				$delete = $stmt->fetch(PDO::FETCH_ASSOC);
				if ($delete['type'] == 1) {
					unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/p/'.$delete['pic']); //delete photo
				}
				if ($delete['type'] == 2) {
					unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$delete['pic']); //delete video
					unlink($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/v/'.$delete['thumb']); //delete video thumbnail
				}
				$stmt = $db->prepare("DELETE FROM spwp_appposts WHERE post_id=?"); //deletes post content
				$stmt->execute(array($_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_applikes WHERE post_id=?"); //deletes likes
				$stmt->execute(array($_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_appcomments WHERE post_id=?"); //deletes comments
				$stmt->execute(array($_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_apphashtags WHERE post_id=?"); //deletes hashtags
				$stmt->execute(array($_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE post_id=?"); //deletes notifications
				$stmt->execute(array($_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_apptags WHERE post_id=?"); //deletes tags
				$stmt->execute(array($_POST['post_id']));
				echo '{"success":1,"error_message":"Post Deleted."}';
			}
			else {
				echo '{"success":0,"error_message":"You cannot delete this post."}';
			}
		}
		elseif ($_POST['type'] == 2) { //remove comment
			
			$stmt = $db->prepare("SELECT poster FROM spwp_appposts WHERE post_id=?"); //gets users ID from post
			$stmt->execute(array($_POST['post_id']));
			$check = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt = $db->prepare("SELECT poster FROM spwp_appcomments WHERE poster=? AND id=?"); //gets users ID from post
			$stmt->execute(array($info['id'],$_POST['comment_id']));
			$post = $stmt->rowCount(); 
			
			if ($post > 0) { //verifies user created the post
				$stmt = $db->prepare("DELETE FROM spwp_appcomments WHERE poster=? AND id=?"); //deletes comments
				$stmt->execute(array($info['id'],$_POST['comment_id']));
				$stmt = $db->prepare("DELETE FROM spwp_appnotifications WHERE poster=? AND sender=? AND post_id=? AND type IN (1,2)"); //deletes comments
				$stmt->execute(array($post['poster'],$info['id'],$_POST['post_id']));
				$stmt = $db->prepare("DELETE FROM spwp_apptags WHERE post_id=? AND user_id=?"); //deletes tags
				$stmt->execute(array($_POST['post_id'],$info['id']));
				$stmt = $db->prepare("SELECT username, profile_pic, content, id FROM spwp_appprofile JOIN spwp_appcomments ON spwp_appcomments.poster = spwp_appprofile.user_id WHERE spwp_appcomments.post_id=? ORDER BY id"); //gets comment information
				$stmt->execute(array($_POST['post_id']));
				$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$comments_count = getNumber($stmt->rowCount(),1); //simplifies the comment count
				$read_out = '{"success":1,"error_message":"Comment Deleted.","comments":'.$comments_count.',"items":[';
				//$read_out = $read_out.'","likes":"'.$ids.'","time":'.$post['length'];
				for ($i = 0; $i < $comments_count; $i++) {
					$read_out = $read_out.'{"username":"'.$comments[$i]['username'].'","profile_pic":"'.$comments[$i]['profile_pic'].'","comment_id":'.$comments[$i]['id'].',"comment":"'.$comments[$i]['content'].'"';
					if ($i == ($comments_count-1)) {
						$read_out = $read_out.'}';
					}
					else {
						$read_out = $read_out.'},';
					}
				}
				echo $read_out.']}';
			}
			elseif ($check['poster'] == $info['id']){ //delete someone else's
				$stmt = $db->prepare("DELETE FROM spwp_appcomments WHERE id=?"); //deletes comments
				$stmt->execute(array($_POST['comment_id']));
				$stmt = $db->prepare("SELECT username, profile_pic, content, id FROM spwp_appprofile JOIN spwp_appcomments ON spwp_appcomments.poster = spwp_appprofile.user_id WHERE spwp_appcomments.post_id=? ORDER BY id"); //gets comment information
				$stmt->execute(array($_POST['post_id']));
				$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$comments_count = getNumber($stmt->rowCount(),1); //simplifies the comment count
				$read_out = '{"success":1,"error_message":"Comment Deleted.","comments":'.$comments_count.',"items":[';
				//$read_out = $read_out.'","likes":"'.$ids.'","time":'.$post['length'];
				for ($i = 0; $i < $comments_count; $i++) {
					$read_out = $read_out.'{"username":"'.$comments[$i]['username'].'","profile_pic":"'.$comments[$i]['profile_pic'].'","comment_id":'.$comments[$i]['id'].',"comment":"'.$comments[$i]['content'].'"';
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
				echo '{"success":0,"error_message":"You cannot delete this comment."}';
			}
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