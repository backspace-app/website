<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if (($_POST['session'] == $info['session']) && $_POST['search'] <> "") { //looks to see if the session is current and if there is a search term
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
		echo '{"success":9,"error_message":"You have been logged out. Please log back in."}';
	}
}
else {
	echo '{"success":0,"error_message":"An error occurred. Please try again."}';
}
$db = null;
?>