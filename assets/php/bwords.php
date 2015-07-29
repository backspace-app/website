<?php
require_once 'c.php';

if($_POST){
	$already_added = 0;
	$num_words = 0;
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the session ID
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //verifies the current session is active
		if ($_POST['type'] == 1) { //add word
			$stmt = $db->prepare("SELECT * FROM spwp_appblockedwords WHERE user_id=:id AND word=:word");
			$stmt->execute(array(':id' => $info['id'], ':word' => $_POST['word']));
			$already_added = $stmt->rowCount(); //checks to see if the word was already added
			if ($already_added > 0) {
				echo $already_added;
				echo '{"success":0,"error_message":"'.$_POST['word'].' is already blocked."}';
			}
			else {
				$stmt = $db->prepare("INSERT INTO spwp_appblockedwords (user_id, word) VALUES(:user_id,:word)"); //adds blocked word to database
				$stmt->execute(array(':user_id' => $info['id'], ':word' => $_POST['word']));
				$stmt = $db->prepare("SELECT word FROM spwp_appblockedwords WHERE user_id=:id ORDER BY word ASC"); //gets blocked words info
				$stmt->execute(array(':id' => $info['id']));
				$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$num_words = $stmt->rowCount(); //counts number of blocked words
				$read_out = '{"success":1,"error_message":"'.$_POST['word'].' has been added to the list. You will no longer see this content.","items":[';
				for ($i = 0; $i < $num_words; $i++) {
					$read_out = $read_out.'{"word":"'.$words[$i]['word'].'"';
					if ($i == ($num_words-1)) {
						$read_out = $read_out.'}';
					}
					else {
						$read_out = $read_out.'},';
					}
				}
				$read_out = $read_out.']}';
				echo $read_out;
			}
		}
		elseif ($_POST['type'] == 2) { //delete word
			$stmt = $db->prepare("DELETE FROM spwp_appblockedwords WHERE user_id=:id AND word=:word"); //deletes blocked word
			$stmt->execute(array(':id' => $info['id'], ':word' => $_POST['word']));
			$stmt->execute();
			$stmt = $db->prepare("SELECT word FROM spwp_appblockedwords WHERE user_id=:id ORDER BY word ASC"); //gets blocked words info
			$stmt->execute(array(':id' => $info['id'])); 
			$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_words = $stmt->rowCount(); //counts number of blocked words
			echo '{"success":1,"error_message":"'.$_POST['word'].' is no longer being blocked.","items":[';
			for ($i = 0; $i < $num_words; $i++) {
				$read_out = $read_out.'{"word":"'.$words[$i]['word'].'"';
				if ($i == ($num_words-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		else { //view word list
			$stmt = $db->prepare("SELECT word FROM spwp_appblockedwords WHERE user_id=:id ORDER BY word ASC"); //gets blocked words info
			$stmt->execute(array(':id' => $info['id']));
			$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$num_words = $stmt->rowCount(); //counts number of blocked words
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_words; $i++) {
				$read_out = $read_out.'{"word":"'.$words[$i]['word'].'"';
				if ($i == ($num_words-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
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