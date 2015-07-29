<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['id'] <> "") { //looks to see if the session is current and if there is a profile ID
		$stmt = $db->prepare("SELECT user_id, username, clout, post_count, profile_pic, is_private, CASE WHEN friend_count <> 0 THEN friend_count ELSE 0 END AS friends, CASE WHEN follow_count <> 0 THEN follow_count ELSE 0 END AS followers FROM spwp_appprofile LEFT JOIN (SELECT spwp_appfriends.user_one, COUNT(spwp_appfriends.user_two) AS friend_count FROM spwp_appfriends WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <> 1)spwp_appfriends ON spwp_appprofile.user_id = spwp_appfriends.user_one LEFT JOIN (SELECT spwp_appfollowing.user_two, COUNT(spwp_appfollowing.user_two) AS follow_count FROM spwp_appfollowing WHERE spwp_appfollowing.user_two=?)spwp_appfollowing ON spwp_appprofile.user_id = spwp_appfollowing.user_two WHERE spwp_appprofile.user_id=?");
		$stmt->execute(array($_POST['id'],$_POST['id'],$_POST['id']));
		$user = $stmt->fetch(PDO::FETCH_ASSOC); //gets profile information from the database
		
		$post_count = getNumber($user['post_count'],1); //simplifies the post count
		$clout = getNumber($user['clout'],2); //simplifies the users clout
		$num_friends = getNumber($user['friends'],1); //simplifies the number of friends
		$num_followers = getNumber($user['followers'],1); //simplifies the number of followers
		
		$read_out = '{"success":1,"user_details":{"user_id":'.$user['user_id'].',"username":"'.$user['username'].'","profile_pic":"'.$user['profile_pic'].'","friends":"'.$num_friends.'","followers":"'.$num_followers.'","post_count":"'.$post_count.'","clout":"'.$clout.'"';
		if ($info['id'] == $_POST['id']) { //determines if the profile is the current user's
			$read_out = $read_out.',"me":1,"pending":0,"friend":0,"follower":0';
			$friend = 1;
		}
		else {
			$read_out = $read_out.',"me":0';
			
			$stmt = $db->prepare("SELECT user_two, pending FROM spwp_appfriends WHERE spwp_appfriends.user_one=:one AND spwp_appfriends.user_two=:two");
			$stmt->execute(array(':one' => $info['id'],':two' => $_POST['id']));
			$friend = $stmt->rowCount(); //look to see if the user is friends with this user's profile
			$friendinfo = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($friend > 0 AND $friendinfo['pending'] == 1) { //checks to see if friend request is pending
				$read_out = $read_out.',"pending":1';
			}
			else {
				$read_out = $read_out.',"pending":0';
			}
			if ($friend > 0 AND $friendinfo['pending'] <> 1) {
				$read_out = $read_out.',"friend":1,"follower":0';
			}
			else {
				$read_out = $read_out.',"friend":0';
				$stmt = $db->prepare("SELECT user_two FROM spwp_appfollowing WHERE spwp_appfollowing.user_one=:one AND spwp_appfollowing.user_two=:two");
				$stmt->execute(array(':one' => $info['id'],':two' => $_POST['id']));
				$follower = $stmt->rowCount(); //looks to see if the user is following this user's profile
				if ($follower > 0) {
					$read_out = $read_out.',"follower":1';
				}
				else {
					$read_out = $read_out.',"follower":0';
				}
				
			}
		}
		if ($user['is_private'] == 0) {
			$read_out = $read_out.',"is_private":0}';
		}
		else {
			$read_out = $read_out.',"is_private":1}';
		}
		
		//paging
		if ($_POST['page'] == '') {
			$page_start = 0;
		}
		else {
			$page_start = $_POST['page'] * 20;
		}
		$page_end = 20;
		//end paging
				
		if ($friend > 0 || $user['is_private'] == 0) { //determines what information to show based on user's security settings
			if ($info['id'] == $_POST['id']) { //show all posts if the current profile is the user's profile
				$stmt = $db->prepare("SELECT * FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.poster=? AND spwp_appposts.length>? ORDER BY spwp_appposts.length DESC LIMIT ?,?");
				$stmt->execute(array($_POST['id'],time(),$page_start,$page_end));
			}
			else {
				
				//check for blocked words
				$stmt = $db->prepare("SELECT word FROM spwp_appblockedwords WHERE spwp_appblockedwords.user_id=?");
				$stmt->execute(array($info['id']));
				$word = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$num_words = $stmt->rowCount(); //checks to see if user liked post
				
				if ($num_words > 0) {
					for ($i = 0; $i < $num_words; $i++) {
						if ($i == 0) {
							$words = strtolower($word[$i]['word']);
						}
						else {
							$words = "|".strtolower($word[$i]['word']);
						}
					}
					if ($friend > 0){ //show all posts the user is allowed to see...following, friend, friends group
						$stmt = $db->prepare("SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM (SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.poster=? AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_creator=? AND spwp_appgroups.group_member=? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."'))posts ORDER BY posts.length DESC LIMIT ?,?");
						$stmt->execute(array($_POST['id'],time(),$_POST['id'],$info['id'],time(),$page_start,$page_end));
					}
					else { //only show public posts
						$stmt = $db->prepare("SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.poster=? AND spwp_appposts.group_id IN ('0') AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') ORDER BY spwp_appposts.length DESC LIMIT ?,?");
						$stmt->execute(array($_POST['id'],time(),$page_start,$page_end));
					}
				}
				else {
					if ($friend > 0){ //show all posts the user is allowed to see...following, friend, friends group
						$stmt = $db->prepare("SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM (SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.poster=? AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_creator=? AND spwp_appgroups.group_member=? AND spwp_appposts.length>?)posts ORDER BY posts.length DESC LIMIT ?,?");
						$stmt->execute(array($_POST['id'],time(),$_POST['id'],$info['id'],time(),$page_start,$page_end));
					}
					else { //only show public posts
						$stmt = $db->prepare("SELECT post_id, type, username, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.poster=? AND spwp_appposts.group_id IN ('0') AND spwp_appposts.length>? ORDER BY spwp_appposts.length DESC LIMIT ?,?");
						$stmt->execute(array($_POST['id'],time(),$page_start,$page_end));
					}
				}
				//end bad words
				
				
			}
			$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_items = $stmt->rowCount(); //count the number of posts the user should be allowed to see
			$read_out = $read_out.',"items":[';
			for ($i = 0; $i < $num_items; $i++) {
				$stmt = $db->prepare("SELECT COUNT(DISTINCT spwp_appcomments.id) AS comments, COUNT(DISTINCT spwp_applikes.id) AS likes FROM spwp_appposts LEFT JOIN spwp_applikes ON spwp_appposts.post_id = spwp_applikes.post_id LEFT JOIN spwp_appcomments ON spwp_appposts.post_id = spwp_appcomments.post_id WHERE spwp_appposts.post_id=:id");
				$stmt->execute(array(':id' => $items[$i]['post_id']));
				$user = $stmt->fetch(PDO::FETCH_ASSOC);
				$num_comments = getNumber($user['comments'],1); //simplifies the comment count
				$num_likes = getNumber($user['likes'],1); //simplifies the like count
				$stmt = $db->prepare("SELECT poster FROM spwp_applikes WHERE spwp_applikes.poster=? AND spwp_applikes.post_id=?");
				$stmt->execute(array($info['id'],$items[$i]['post_id']));
				$is_liked = $stmt->rowCount(); //checks to see if user liked post
				if ($is_liked > 0) { $liked = 1;} else { $liked = 0;}
				$read_out = $read_out.'{"post_id":'.$items[$i]['post_id'].',"type":'.$items[$i]['type'].',"username":"'.$items[$i]['username'].'","profile_pic":"'.$items[$i]['profile_pic'].'","is_liked":'.$liked.',"content":"'.$items[$i]['content'].'","pic":"'.$items[$i]['pic'].'","thumb":"'.$items[$i]['thumb'].'","time":'.$items[$i]['length'].',"likes":"'.$num_likes.'","comments":"'.$num_comments.'"';
				if ($i == ($num_items-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			echo $read_out.']}';
		}
		else {
			echo $read_out.',"items":[]}';
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
		$return = round($number/3600,1)." hr";
	}
	if ($number < 3600 && $type == 2) {
		$return = round($number/60,0)." min";
	}
	return $return;
}
?>