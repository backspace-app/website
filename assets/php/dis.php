<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //verifies the current session ID is active
		if ($_POST['search'] != '') {
			if ($_POST['type'] == 0) { //hashtag search
				//paging
				if ($_POST['page'] == '') {
					$page_start = 0;
				}
				else {
					$page_start = $_POST['page'] * 20;
				}
				$page_end = 20;
				//end paging
				
				
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
					$stmt = $db->prepare("SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM (SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appprofile.user_id=? AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appgroups.group_member=? AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appposts.group_id IN ('0') AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."'))posts GROUP BY post_id, type, username, poster, profile_pic, content, pic, thumb, length ORDER BY posts.length DESC LIMIT ?,?");
				}
				else {
					$stmt = $db->prepare("SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM (SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appprofile.user_id=? AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appgroups.group_member=? AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>? UNION ALL SELECT spwp_appposts.post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_apphashtags ON spwp_apphashtags.post_id = spwp_appposts.post_id WHERE spwp_appposts.group_id IN ('0') AND spwp_apphashtags.hashtag LIKE ? AND spwp_appposts.length>?)posts GROUP BY post_id, type, username, poster, profile_pic, content, pic, thumb, length ORDER BY posts.length DESC LIMIT ?,?");
				}
				//end blocked words
				
				$stmt->execute(array($info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),'%'.$_POST['search'].'%',time(),$page_start,$page_end)); //gets post information
				$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$num_items = $stmt->rowCount(); //counts the number of posts
				
				$read_out = '{"success":1,"items":[';
				for ($i = 0; $i < $num_items; $i++) {
					$stmt = $db->prepare("SELECT COUNT(DISTINCT spwp_appcomments.id) AS comments, COUNT(DISTINCT spwp_applikes.id) AS likes FROM spwp_appposts LEFT JOIN spwp_applikes ON spwp_appposts.post_id = spwp_applikes.post_id LEFT JOIN spwp_appcomments ON spwp_appposts.post_id = spwp_appcomments.post_id WHERE spwp_appposts.post_id=:id");
					$stmt->execute(array(':id' => $items[$i]['post_id']));
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$num_comments = getNumber($user['comments'],1); //simplifies comment count
					$num_likes = getNumber($user['likes'],1); //simplifies like count
					$stmt = $db->prepare("SELECT poster FROM spwp_applikes WHERE spwp_applikes.poster=? AND spwp_applikes.post_id=?");
					$stmt->execute(array($info['id'],$items[$i]['post_id']));
					$is_liked = $stmt->rowCount(); //checks to see if user liked post
					if ($is_liked > 0) { $liked = 1;} else { $liked = 0;}
					$read_out = $read_out.'{"post_id":'.$items[$i]['post_id'].',"type":'.$items[$i]['type'].',"username":"'.$items[$i]['username'].'","user_id":'.$items[$i]['poster'].',"profile_pic":"'.$items[$i]['profile_pic'].'","is_liked":'.$liked.',"content":"'.$items[$i]['content'].'","pic":"'.$items[$i]['pic'].'","thumb":"'.$items[$i]['thumb'].'","time":'.$items[$i]['length'].',"likes":"'.$num_likes.'","comments":"'.$num_comments.'"';
					if ($i == ($num_items-1)) {
						$read_out = $read_out.'}';
					}
					else {
						$read_out = $read_out.'},';
					}
				}
				$read_out = $read_out.']}';
				echo $read_out;
			}
			elseif ($_POST['type'] == 1) { //post search
				//paging
				if ($_POST['page'] == '') {
					$page_start = 0;
				}
				else {
					$page_start = $_POST['page'] * 20;
				}
				$page_end = 20;
				//end paging
				
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
					$stmt = $db->prepare("SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM (SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appprofile.user_id=? AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_member=? AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE  spwp_appposts.group_id IN ('0') AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."'))posts GROUP BY post_id, type, username, poster, profile_pic, content, pic, thumb, length ORDER BY posts.length DESC LIMIT ?,?");
				}
				else {
					$stmt = $db->prepare("SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM (SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appprofile.user_id=? AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_member=? AND spwp_appposts.content LIKE ? AND spwp_appposts.length>? UNION ALL SELECT post_id, type, username, poster, profile_pic, content, pic, thumb, length FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.group_id IN ('0') AND spwp_appposts.content LIKE ? AND spwp_appposts.length>?)posts GROUP BY post_id, type, username, poster, profile_pic, content, pic, thumb, length ORDER BY posts.length DESC LIMIT ?,?");
				}
				//end bad words
				
				$stmt->execute(array($info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),$info['id'],'%'.$_POST['search'].'%',time(),'%'.$_POST['search'].'%',time(),$page_start,$page_end)); //gets post information
				$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$num_items = $stmt->rowCount(); //counts the number of posts
				
				$read_out = '{"success":1,"items":[';
				for ($i = 0; $i < $num_items; $i++) {
					$stmt = $db->prepare("SELECT COUNT(DISTINCT spwp_appcomments.id) AS comments, COUNT(DISTINCT spwp_applikes.id) AS likes FROM spwp_appposts LEFT JOIN spwp_applikes ON spwp_appposts.post_id = spwp_applikes.post_id LEFT JOIN spwp_appcomments ON spwp_appposts.post_id = spwp_appcomments.post_id WHERE spwp_appposts.post_id=:id");
					$stmt->execute(array(':id' => $items[$i]['post_id']));
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$num_comments = getNumber($user['comments'],1); //simplifies comment count
					$num_likes = getNumber($user['likes'],1); //simplifies like count
					$read_out = $read_out.'{"post_id":'.$items[$i]['post_id'].',"type":'.$items[$i]['type'].',"username":"'.$items[$i]['username'].'","user_id":'.$items[$i]['poster'].',"profile_pic":"'.$items[$i]['profile_pic'].'","content":"'.$items[$i]['content'].'","pic":"'.$items[$i]['pic'].'","thumb":"'.$items[$i]['thumb'].'","time":'.$items[$i]['length'].',"likes":"'.$num_likes.'","comments":"'.$num_comments.'"';
					if ($i == ($num_items-1)) {
						$read_out = $read_out.'}';
					}
					else {
						$read_out = $read_out.'},';
					}
				}
				$read_out = $read_out.']}';
				echo $read_out;
			}
			elseif($_POST['type'] == 3) { //user search
				$stmt = $db->prepare("SELECT user_id, username, profile_pic FROM spwp_appprofile WHERE spwp_appprofile.username LIKE :search_term");
				$stmt->execute(array(':search_term' => '%'.$_POST['search'].'%'));
				$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$user_count = $stmt->rowCount(); //count the number of users who match the search term
				$read_out = '{"success":1,"items":[';
				for ($i = 0; $i < $user_count; $i++) {
					$read_out = $read_out.'{"user_id":'.$search[$i]['user_id'].',"username":"'.$search[$i]['username'].'","profile_pic":"'.$search[$i]['profile_pic'].'"';
					if ($i == ($user_count-1)) {
						$read_out = $read_out.'}';
					}
					else {
						$read_out = $read_out.'},';
					}
				}
				echo $read_out.']}';
			}
			else {
				echo '{"success":0,"error_message":"An error occurred. Please try again."}';
			}
		}
		else {
			echo '{"success":0,"error_message":"A search term is required."}';
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