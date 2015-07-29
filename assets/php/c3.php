<?php
header('Content-type: application/json');
try {
    $db = new PDO('mysql:host=173.194.253.146:3306;dbname=bspace;charset=utf8mb4', 'app', '84Qz4Cg3PTKe4P68LZ*d8my)oAf9', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
    $stmt = $db->prepare("SET NAMES utf8mb4"); //gets the current session ID of the user
	$stmt->execute();
	$stmt = $db->prepare("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"); //gets the current session ID of the user
	$stmt->execute();
} catch(PDOException $ex) {
    echo '{"success":0,"error_message":"An error occurred."}';
    $ex->getMessage();
    echo $ex;
}	
?>