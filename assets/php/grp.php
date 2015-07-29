<?php
require_once 'c.php';

if($_POST){
	$stmt = $db->prepare("SELECT id, session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($_POST['session'] == $info['session']) { //looks to see if the session is current
		if ($_POST['type'] == 1) { //display groups
			$stmt = $db->prepare("SELECT group_id, group_name FROM spwp_appgroups WHERE group_creator=? GROUP BY group_id ORDER BY group_name");
			$stmt->execute(array($info['id']));
			$group = $stmt->fetchall(PDO::FETCH_ASSOC); //get group information
			$num_groups = $stmt->rowCount(); //count the number of groups
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_groups; $i++) {
				$read_out = $read_out.'{"grp_id":"'.$group[$i]['group_id'].'","grp_nme":"'.$group[$i]['group_name'].'"';
				if ($i == ($num_groups-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 2) { //edit group
			if ($_POST['grp_nme'] != "" && $_POST['user_id'] == "") {  //change group name
				$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_name=? AND group_creator=?");
				$stmt->execute(array(substr(json_encode($_POST['grp_nme']),1,-1),$info['id']));
				$num_names = $stmt->rowCount(); //count the number of  group names
				if ($num_names == 0) { //verify no other group of user has the same name
					$stmt = $db->prepare("UPDATE spwp_appgroups SET group_name=? WHERE group_id=? AND group_creator=?");
					$stmt->execute(array(substr(json_encode($_POST['grp_nme']),1,-1),$_POST['grp_id'],$info['id']));
				}
				else {
					echo '{"success":0,"error_message":"A group by this name already exists. Please choose another name."}';
					exit;
				}
			}
			elseif ($_POST['user_id'] != "" && $_POST['grp_nme'] == "" && $info['id'] != $_POST['user_id']) { //add group member
				$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_id=? AND group_member=?");
				$stmt->execute(array($_POST['grp_id'],$_POST['user_id']));
				$member_check = $stmt->rowCount(); //checks to see if user is already in group
				
				$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_id=? AND group_creator=?");
				$stmt->execute(array($_POST['grp_id'],$info['id']));
				$num_groups = $stmt->rowCount(); //double check group still exists; not sure if needed
				
				if ($num_groups > 0 && $member_check == 0) { //add user if they aren't already in group
					$group_name = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt = $db->prepare("INSERT INTO spwp_appgroups (group_id,group_name,group_member,group_creator) VALUES (:id,:name,:member,:creator)");
					$stmt->execute(array(':id' => $_POST['grp_id'],':name' => $group_name['group_name'],':member' => $_POST['user_id'],':creator' => $info['id']));
				}
				else {
					echo '{"success":0,"error_message":"This user is already in the group."}';
					exit;
				}
			}
			else {
				echo '{"success":0,"error_message":"An error occurred. Please try again."}';
				exit;
			}
			$stmt = $db->prepare("SELECT group_name, user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appgroups ON spwp_appprofile.user_id = spwp_appgroups.group_member WHERE group_id=? ORDER BY group_name, username");
			$stmt->execute(array($_POST['grp_id']));
			$group = $stmt->fetchall(PDO::FETCH_ASSOC);
			$num_members = $stmt->rowCount(); //count the number of members
			$read_out = '{"success":1,"grp_nme":"'.$group[0]['group_name'].'","items":[';
			for ($i = 0; $i < $num_members; $i++) {
				$read_out = $read_out.'{"user_id":'.$group[$i]['user_id'].',"username":"'.$group[$i]['username'].'","profile_pic":"'.$group[$i]['profile_pic'].'"';
				if ($i == ($num_members-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.']}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 3) { //create group
			$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_name=? AND group_creator=?");
			$stmt->execute(array(substr(json_encode($_POST['grp_nme']),1,-1),$info['id']));
			$num_names = $stmt->rowCount(); //verifies no other group exists with the same name
			
			if ($num_names == 0) {
				if ($_POST['grp_nme'] != "" && $_POST['user_id'] != "") { //verifies all required information is there
					$grp_id = generateUniqueId(25); //generates a random group ID
					$i=0;
					while ($i == 0) { //if the group ID already exists, generate a new ID
						$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_id=?");
						$stmt->execute(array($grp_id));
						$num_groups = $stmt->rowCount();
						if ($num_groups > 0) {
							$grp_id = generateUniqueId(25);
						}
						else {
							$i=1;
						}
					}
					$stmt = $db->prepare("INSERT INTO spwp_appgroups (group_id,group_name,group_member,group_creator) VALUES (:id,:name,:member,:creator)");
					$stmt->execute(array(':id' => $grp_id,':name' => substr(json_encode($_POST['grp_nme']),1,-1),':member' => $_POST['user_id'],':creator' => $info['id'])); //add information to the group table
					$stmt = $db->prepare("SELECT group_name, user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appgroups ON spwp_appprofile.user_id = spwp_appgroups.group_member WHERE group_id=? ORDER BY group_name, username");
					$stmt->execute(array($grp_id));
					$group = $stmt->fetchall(PDO::FETCH_ASSOC);
					$num_members = $stmt->rowCount(); //count the number of group members
					$read_out = '{"success":1,"grp_id":"'.$grp_id.'","grp_nme":"'.$group[0]['group_name'].'","items":[';
					for ($i = 0; $i < $num_members; $i++) {
						$read_out = $read_out.'{"user_id":'.$group[$i]['user_id'].',"username":"'.$group[$i]['username'].'","profile_pic":"'.$group[$i]['profile_pic'].'"';
						if ($i == ($num_members-1)) {
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
					echo '{"success":0,"error_message":"A group name and an initial user are required to create a group."}';
				}
			}
			else {
				echo '{"success":0,"error_message":"A group by this name already exists. Please choose another name."}';
			}
		}
		elseif ($_POST['type'] == 4) { //delete group
			$stmt = $db->prepare("DELETE FROM spwp_appgroups WHERE group_id=? AND group_creator=?");
			$stmt->execute(array($_POST['grp_id'],$info['id']));
			$stmt = $db->prepare("SELECT group_id, group_name FROM spwp_appgroups WHERE group_creator=? GROUP BY group_id ORDER BY group_name");
			$stmt->execute(array($info['id']));
			$group = $stmt->fetchall(PDO::FETCH_ASSOC); //get group information
			$num_groups = $stmt->rowCount(); //count number of groups
			$read_out = '{"success":1,"items":[';
			for ($i = 0; $i < $num_groups; $i++) {
				$read_out = $read_out.'{"grp_id":"'.$group[$i]['group_id'].'","grp_nme":"'.$group[$i]['group_name'].'"';
				if ($i == ($num_groups-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.'],"error_message":"Group has been deleted."}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 5) { //delete user
			$stmt = $db->prepare("DELETE FROM spwp_appgroups WHERE group_id=? AND group_member=? AND group_creator=?");
			$stmt->execute(array($_POST['grp_id'],$_POST['user_id'],$info['id']));
			$stmt = $db->prepare("SELECT group_name, user_id, username, profile_pic FROM spwp_appprofile JOIN spwp_appgroups ON spwp_appprofile.user_id = spwp_appgroups.group_member WHERE group_id=? ORDER BY group_name, username");
			$stmt->execute(array($_POST['grp_id']));
			$group = $stmt->fetchall(PDO::FETCH_ASSOC); //get group information
			$num_members = $stmt->rowCount(); //count number of members in group
			$read_out = '{"success":1,"grp_nme":"'.$group[0]['group_name'].'","items":[';
			for ($i = 0; $i < $num_members; $i++) {
				$read_out = $read_out.'{"user_id":'.$group[$i]['user_id'].',"username":"'.$group[$i]['username'].'","profile_pic":"'.$group[$i]['profile_pic'].'"';
				if ($i == ($num_members-1)) {
					$read_out = $read_out.'}';
				}
				else {
					$read_out = $read_out.'},';
				}
			}
			$read_out = $read_out.'],"error_message":"User removed."}';
			echo $read_out;
		}
		elseif ($_POST['type'] == 6) { //view members
			if ($_POST['grp_id'] != "" && $_POST['user_id'] == "") {
				$stmt = $db->prepare("SELECT group_name FROM spwp_appgroups WHERE group_id=? AND group_creator=?");
				$stmt->execute(array($_POST['grp_id'],$info['id']));
				$num_groups = $stmt->rowCount(); 
				if ($num_groups > 0) {
					$stmt = $db->prepare("SELECT group_id, group_name, user_id, username, profile_pic FROM spwp_appgroups JOIN spwp_appprofile ON spwp_appgroups.group_member = spwp_appprofile.user_id WHERE group_id=? AND group_creator=?");
					$stmt->execute(array($_POST['grp_id'],$info['id']));
					$members = $stmt->fetchall(PDO::FETCH_ASSOC); 
					$num_members = $stmt->rowCount();
					$read_out = '{"success":1,"grp_id":"'.$members[0]['group_id'].'","grp_nme":"'.$members[0]['group_name'].'","items":[';
					for ($i = 0; $i < $num_members; $i++) {
						$read_out = $read_out.'{"user_id":'.$members[$i]['user_id'].',"username":"'.$members[$i]['username'].'","profile_pic":"'.$members[$i]['profile_pic'].'"';
						if ($i == ($num_members-1)) {
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
					echo '{"success":0,"error_message":"You cannot view members of this group."}';
				}
				
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

function generateUniqueId($maxLength = null) {
    $entropy = '';
    if (function_exists('openssl_random_pseudo_bytes')) {
        $entropy = openssl_random_pseudo_bytes(64, $strong);
        if($strong !== true) {
            $entropy = '';
        }
    }
    $entropy .= uniqid(mt_rand(), true);
    if (class_exists('COM')) {
        try {
            $com = new COM('CAPICOM.Utilities.1');
            $entropy .= base64_decode($com->GetRandom(64, 0));
        } catch (Exception $ex) {
        }
    }
    if (is_readable('/dev/urandom')) {
        $h = fopen('/dev/urandom', 'rb');
        $entropy .= fread($h, 64);
        fclose($h);
    }
    $hash = hash('whirlpool', $entropy);
    if ($maxLength) {
        return substr($hash, 0, $maxLength);
    }
    return $hash;
}
function findexts ($filename) { 
	$filename = strtolower($filename) ; 
	$exts = split("[/\\.]", $filename) ; 
	$n = count($exts)-1; 
	$exts = $exts[$n]; 
	return $exts; 
}
?>