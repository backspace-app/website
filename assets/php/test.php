<title>LeoTechnosoft Test Page</title>
<h2>API Test Page</h2>

<?
if ($_POST) {
	include('pwhsh.php');
	require_once 'c.php';
	$stmt = $db->prepare("SELECT password FROM spwp_appusers WHERE username=?"); //gets user data
	$stmt->execute(array($_POST['username']));
	$info = $stmt->fetch(PDO::FETCH_ASSOC);
	if($_POST['username'] != '' && validate_password($_POST['password'],$info['password'])) {
		$stmt = $db->prepare("SELECT session FROM spwp_appusers WHERE username=?"); //gets the current session ID of the user
		$stmt->execute(array($_POST['username']));
		$info = $stmt->fetch(PDO::FETCH_ASSOC);
		$url = $_SERVER['PHP_SELF'].'?username='.$_POST['username'].'&session='.$info['session'];
		?>
		<script language="javascript" type="text/javascript">
		window.location = '<? echo $url; ?>';
		</script>
		<?
	}
	else {
		$url = $_SERVER['PHP_SELF'];
		?>
		<script language="javascript" type="text/javascript">
		window.location = '<? echo $url; ?>';
		</script>
		<?
	}
} 
if ($_GET['username'] == "") {
?>
<h3>Login</h3>
<form action="<? echo($_SERVER['PHP_SELF']) ?>" method="post" enctype="multipart/form-data">
Username:<input name="username"><br>
Password:<input name="password" type="password"><br>
<input type="submit" value="Submit"></form>
<br>

<?
}
if ($_GET['username'] != "") {
?>
<h3>Forgot Password</h3>
<form action="http://backspaceapp.co/assets/php/f.php" method="post" enctype="multipart/form-data">
<input name="username" value="username">
<input type="submit" value="Submit"></form>
<br>

<h3>Invite List</h3>
<form action="wl.php" method="post" enctype="multipart/form-data">
<input name="email" value="email">
<input type="submit" value="Submit"></form>
<br>

<h3>Feed</h3>
<form action="fd.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="page" value="page">
<input type="submit" value="Submit"></form>
<br>

<h3>Likes Feed</h3>
<form action="lks.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="id" value="user_id">
<input  name="page" value="page">
<input type="submit" value="Submit"></form>
<br>

<h3>Tags Feed</h3>
<form action="tgs.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="id" value="user_id">
<input  name="page" value="page">
<input type="submit" value="Submit"></form>
<br>

<h3>Feed Item Detail</h3>
<form action="fid.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="post_id" value="post_id">
<input type="submit" value="Submit"></form>
<br>

<h3>New Post</h3>
<form action="np2.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="group" value="group_id">
<input  name="content" value="content">
Pic/Vid:<input type="file" name="pic" id="pic" size="50"/> 
Thumb:<input type="file" name="thumb" id="thumb" size="50"/> 
<input  name="tagged_users" value="tagged_users">
<input type="submit" value="Submit"></form>
<br>

<h3>Like Item</h3>
<form action="lk.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="post_id" value="post_id">
<input type="submit" value="Submit"></form>
<br>

<h3>Post Comment</h3>
<form action="pt.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="post_id" value="post_id">
<input  name="content" value="content">
<input  name="tagged_users" value="tagged_users">
<input type="submit" value="Submit"></form>
<br>

<h3>Delete Post</h3>
<form action="dp.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="post_id" value="post_id">
<input  name="type" value="type">
<input  name="comment_id" value="comment_id">
<input type="submit" value="Submit"></form>
<br>

<h3>Notifications</h3>
<form action="ntf.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input type="submit" value="Submit"></form>
<br>

<h3>Username Search</h3>
<form action="srh.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="search" value="username_search">
<input type="submit" value="Submit"></form>
<br>

<h3>Tagging Test</h3>
<form action="tu2.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="search" value="username_search">
<input type="submit" value="Submit"></form>
<br>

<h3>Discover Search</h3>
<form action="dis.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="page" value="page">
<input  name="search" value="post_search">
<input type="submit" value="Submit"></form>
<br>

<h3>Discover</h3>
<form action="div.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input type="submit" value="Submit"></form>
<br>

<h3>User Profile</h3>
<form action="up.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="id" value="profile_id">
<input type="submit" value="Submit"></form>
<br>

<h3>Update Profile Photo</h3>
<form action="pp.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input type="file" name="content" id="content" size="50"/> 
<input type="submit" value="Submit"></form>
<br>

<h3>Make Profile Private</h3>
<form action="prv.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input type="submit" value="Submit"></form>
<br>

<h3>Friend's List</h3>
<form action="fl.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="user_id" value="user_id">
<input  name="type" value="type">
<input type="submit" value="Submit"></form>
<br>

<h3>Add Friends</h3>
<form action="af.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="accept" value="accept">
<input  name="userid" value="user_id">
<input type="submit" value="Submit"></form>
<br>

<h3>Friends Groups</h3>
<form action="grp.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="grp_id" value="group_id">
<input  name="grp_nme" value="group_name">
<input  name="user_id" value="user_id">
<input type="submit" value="Submit"></form>
<br>

<h3>Blocked Words</h3>
<form action="bwords.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input  name="type" value="type">
<input  name="word" value="blocked_word">
<input type="submit" value="Submit"></form>
<br>

<h3>Verify Login</h3>
<form action="v.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input type="submit" value="Submit"></form>
<br>

<h3>Logout</h3>
<form action="lo.php" method="post" enctype="multipart/form-data">
<input name="username" value="<? echo $_GET['username']; ?>" type="hidden">
<input  name="session" value="<? echo $_GET['session']; ?>" type="hidden">
<input type="submit" value="Submit"></form>
<br>

<h3>Send Notification</h3>
<form action="push.php" method="post" enctype="multipart/form-data">
<input  name="user_id" value="user_id">
<input  name="message" value="message">
<input type="submit" value="Submit"></form>
<br>
<? 
}
 ?>

