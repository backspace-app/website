<?
	try {
    $db = new PDO('mysql:host=173.194.253.146:3306;dbname=bspace;charset=utf8mb4', 'app', '84Qz4Cg3PTKe4P68LZ*d8my)oAf9', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
} catch(PDOException $ex) {
    echo '{"success":0,"error_message":"An error occurred."}';
    $ex->getMessage();
}
?>
<!DOCTYPE html>
<!--[if IE 9 ]><html class="ie ie9" lang="en" class="no-js"> <![endif]-->
<!--[if !(IE)]><!--><html lang="en" class="no-js"> <!--<![endif]-->
<head>
	<title>Backspace™ | Password Reset</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta name="description" content="Backspace is a new way to socialize with your friends. Share everything, regret nothing.">

	<!-- CSS -->
	<link href="assets/css/bootstrap.css" rel="stylesheet" type="text/css">
	<link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/simple-line-icons.css" rel="stylesheet" type="text/css">
	<link href="assets/css/main2.css" rel="stylesheet" type="text/css">

	<!-- GOOGLE FONTS -->
	<link href='http://fonts.googleapis.com/css?family=Dosis:200,300,600' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>
	
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">

</head>
<body>
	<!-- PAGE PRELOADER -->
	<div id="preloader">
		<div class="loader-wrapper">
			<img src="assets/img/bx_loader.gif" alt="Backspace" />
			<span>Loading ...</span>
		</div>
	</div>
	<!-- END PAGE PRELOADER -->

	<!-- WRAPPER -->
	<div class="wrapper">
		
		<!-- HERO SECTION -->
		<section id="top" class="hero-unit fullscreen-image-bg clearfix">
			<div class="container">
				<div class="left">
					<img src="assets/img/zi-logo.png" class="logo" alt="Backspace Logo">
					<h1><span class="sr-only">backspace</span> Reset your password</h1>
					<? 	
                		if (($_GET['username'] == NULL) || ($_GET['token'] == NULL)) {
	                		echo '<p>Invalid username and/or reset token.</p>';
                		}
                		else {
                			$stmt = $db->prepare("SELECT * FROM spwp_appforgot WHERE username=? AND token=?");
							$stmt->execute(array($_GET['username'],$_GET['token']));
							$validate = $stmt->fetch(PDO::FETCH_ASSOC);
							$count = $stmt->rowCount();	                	
	                		if ($count == 0) {
		                		echo '<p>Invalid username and/or reset token.</p>';
							}
							
	                		elseif ($validate['expire'] < time()) {
		                		echo '<p>Reset link expired!</p>';
							}
	                		else {
                	?>
                	<noscript>
						<style>#java-form .col-md-6 { display: none;}</style>
						<p>Javascript must be enabled!</p>
					</noscript>
					<div class="col-md-8">
					<form class="password-form" method="POST">
						<div class="input-group input-group-lg">
							<input type="hidden" class="form-control" name="username" value="<?php echo $_GET['username']; ?>">
							<input type="hidden" class="form-control" name="token" value="<?php echo $_GET['token']; ?>">
							<input type="password" class="form-control" name="password" placeholder="new password">
							<input type="password" class="form-control" name="verify" placeholder="repeat password">
							<span class="input-group-btn"><button class="btn btn-primary" type="button"><i class="fa fa-spinner fa-spin"></i><span>SUBMIT</span></button></span>
						</div>
						<div class="alert"></div>
						</form>
						<?
							}
						}
					?>
					</div>
				</div>
			</div>
		</section>
		<!-- END HERO SECTION -->

		<!-- FOOTER -->
		<footer>
			<div id="footer" class="container">
				<div class="row">
					<div class="col-md-4">
						<ul class="list-unstyled footer-links pull-left">
							<li><a href="http://blog.backspaceapp.co">Blog</a></li>
							<li><a href="http://backspaceapp.co/privacy">Terms/Privacy Policy</a></li>
						</ul>
						<ul class="list-unstyled footer-links">
							<li><a href="mailto:info@backspaceapp.co">Contact</a></li>
						</ul>
						<div class="clearfix"></div>
					</div>
					<div class="col-md-4">
						<h3>SPREAD THE NEWS</h3>
						<p>Share Backspace to your friends</p>
						<ul class="list-inline social-icons">
							<li><a href="http://facebook.com/backspaceapp" class="facebook-bg"><i class="fa fa-facebook"></i></a></li>
							<li><a href="http://twitter.com/backspaceapp" class="twitter-bg"><i class="fa fa-twitter"></i></a></li>
						</ul>
					</div>
					<div class="col-md-4">
						<h3>BACKSPACE WAS BUILT IN</h3>
						<p>Chicago &amp; St. Louis</p>
					</div>
				</div>
				<p class="copyright">&copy;2015 Backspace by WR Industries, LLC. All Rights Reserved</p>
			</div>
		</footer>
		<!-- END FOOTER -->
	</div>
	<!-- END WRAPPER -->

	<div class="back-to-top">
		<a href="#top"><i class="fa fa-angle-up"></i></a>
	</div>

	<!-- JAVASCRIPTS -->
	<script src="assets/js/jquery-2.1.1.min.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/jquery.easing.min.js"></script>
	<script src="assets/js/zi-script.js"></script>
	<script src="assets/js/zi-password.js"></script>

</body>
</html>