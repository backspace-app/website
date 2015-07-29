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
	<title>Backspace™ | Socialize Better</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<meta name="description" content="Backspace is a new way to socialize with your friends. Share everything, regret nothing.">

	<!-- CSS -->
	<link href="assets/css/bootstrap.css" rel="stylesheet" type="text/css">
	<link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/simple-line-icons.css" rel="stylesheet" type="text/css">
	<link href="assets/css/main3.css" rel="stylesheet" type="text/css">

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
					<h1><span class="sr-only">backspace</span> Waitlist</h1>
					<div class="col-md-8">
					<? 	
                		if ($_GET['id'] == NULL) {
	                		echo '<p>Invalid link.</p>';
                		}
                		else {
                			$stmt = $db->prepare("SELECT link, signups, active FROM spwp_appwaitlist WHERE link=?");
							$stmt->execute(array($_GET['id']));
							$validate = $stmt->fetch(PDO::FETCH_ASSOC);
							$count = $stmt->rowCount();	                	
	                		if ($count == 0) {
		                		echo '<p>Invalid link.</p>';
							}
	                		else {
		                		
		                		$stmt = $db->prepare("SELECT link, FIND_IN_SET(signups, (SELECT GROUP_CONCAT(signups ORDER BY signups DESC) FROM spwp_appwaitlist)) AS rank FROM spwp_appwaitlist WHERE link=?");
		                		$stmt->execute(array($_GET['id']));
		                		$rank = $stmt->fetch(PDO::FETCH_ASSOC);
		                		$stmt = $db->prepare("SELECT wait_id FROM spwp_appwaitlist");
								$stmt->execute();
								$count = $stmt->rowCount();
                	?>
                	<p>User ID: <? echo $rank['link']; ?><br>Total Signups by this user: <? echo $validate['signups']; ?><br>Rank <? echo $rank['rank']; ?> of <? echo $count; ?></p><p>Enter your email so you too can experience Backspace.</p>
    				<noscript>
						<style>#form { display: none;}</style>
					</noscript>
					<form class="waitlist-form" method="POST">
						<div class="input-group input-group-lg">
							<input type="hidden" class="form-control" name="token" value="<?php echo $_GET['id']; ?>">
							<input type="email" class="form-control" name="email" placeholder="youremail@domain.com">
							<span class="input-group-btn"><button class="btn btn-primary" type="button"><i class="fa fa-spinner fa-spin"></i><span>ADD ME!</span></button></span>
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
	<script src="assets/js/zi-waitlist.js"></script>

</body>
</html>