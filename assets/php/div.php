<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?");
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']){
		
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
			$stmt = $db->prepare("SELECT COUNT(spwp_apphashtags.post_id) AS hashtag_count, hashtag FROM spwp_apphashtags JOIN(SELECT post_id FROM (SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appprofile.user_id=? AND spwp_appposts.length>? UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_member=? AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."') UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.group_id IN ('0') AND spwp_appposts.length>? AND LOWER(spwp_appposts.content) NOT REGEXP ('".$words."'))posts GROUP BY post_id)posts ON spwp_apphashtags.post_id = posts.post_id WHERE LOWER(spwp_apphashtags.hashtag) NOT REGEXP ('".$words."') GROUP BY hashtag ORDER BY hashtag_count DESC, hashtag LIMIT 10"); //gets comment information on the current post
		}
		else {
			$stmt = $db->prepare("SELECT COUNT(spwp_apphashtags.post_id) AS hashtag_count, hashtag FROM spwp_apphashtags JOIN(SELECT post_id FROM (SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appprofile.user_id=? AND spwp_appposts.length>? UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfollowing ON spwp_appposts.poster = spwp_appfollowing.user_two WHERE spwp_appfollowing.user_one=? AND spwp_appposts.group_id='0' AND spwp_appposts.length>? UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appfriends ON spwp_appposts.poster = spwp_appfriends.user_two WHERE spwp_appfriends.user_one=? AND spwp_appfriends.pending <>1 AND spwp_appposts.group_id IN ('0','1') AND spwp_appposts.length>? UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id JOIN spwp_appgroups ON spwp_appposts.group_id = spwp_appgroups.group_id WHERE spwp_appgroups.group_member=? AND spwp_appposts.length>? UNION ALL SELECT post_id FROM spwp_appposts JOIN spwp_appprofile ON spwp_appposts.poster = spwp_appprofile.user_id WHERE spwp_appposts.group_id IN ('0') AND spwp_appposts.length>?)posts GROUP BY post_id)posts ON spwp_apphashtags.post_id = posts.post_id GROUP BY hashtag ORDER BY hashtag_count DESC, hashtag LIMIT 10"); //gets comment information on the current post
		}
		//end bad words
		
		$stmt->execute(array($info['id'],time(),$info['id'],time(),$info['id'],time(),$info['id'],time(),time()));
		$hashtag = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$hashtag_count = $stmt->rowCount(); //counts the number of hashtags
		$read_out = '{"success":1,"hashtag_items":[';
		for ($i = 0; $i < $hashtag_count; $i++) {
			$read_out = $read_out.'{"hashtag":"'.$hashtag[$i]['hashtag'].'"';
			if ($i == ($hashtag_count-1)) {
				$read_out = $read_out.'}';
			}
			else {
				$read_out = $read_out.'},';
			}
		}
		$stmt = $db->prepare("SELECT user_id, username, profile_pic, clout, COUNT(spwp_appposts.post_id) AS post_count FROM spwp_appprofile LEFT JOIN spwp_appposts ON spwp_appprofile.user_id = spwp_appposts.poster GROUP BY user_id ORDER BY post_count DESC, clout DESC, username LIMIT 10"); //gets comment information on the current post
		$stmt->execute();
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$user_count = $stmt->rowCount(); //counts the number of comments
		$read_out = $read_out.'],"user_items":[';
		for ($i = 0; $i < $user_count; $i++) { //don't send notification to self or content creator
			$read_out = $read_out.'{"user_id":'.$user[$i]['user_id'].',"username":"'.$user[$i]['username'].'","profile_pic":"'.$user[$i]['profile_pic'].'"';
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
		echo '{"success":9,"error_message":"You have been logged out. Please log back in."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>