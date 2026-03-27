<?
error_reporting(0);

$link = @mysql_connect('localhost', olfactor_jack, Dice_1234) or die('Error connecting to mysql');
mysql_select_db(olfactor_clone_evolution) or die("Error connecting to database");

include 'missing.php';

if ($_GET['func'] == "logout"){
	setcookie("HT_user", "", time() - 3600);
	//unset($_COOKIE['HT_user']); 

}


if (isset($_POST["login_submit"])) {
	$result = mysql_query("SELECT * FROM users WHERE name = '".$_POST["name"]."'");
	if ($row = mysql_fetch_array($result)){
		if ($_POST["password"] == $row[password]){
			$user = $row[name];
			$cookielife = time() + 31556926;
			setcookie('HT_user', $user, $cookielife);
			?>
			<script>window.location='https://olfactoryhues.com/clone-evolution/';</script>
            <?
		} else {
			$error = "Password incorrect.";	
		}
	} else {
		$error = "User not found.";		
	}
}
if (isset($_POST["register_submit"])) {
	if ($_POST["name"] == ""){
		$error = "Name cannot be left blank.";
	} elseif ($_POST["password"] == ""){
		$error = "Password cannot be left blank.";
	} else {
		$result = mysql_query("SELECT * FROM users WHERE name = '".$_POST['name']."'");
		if (mysql_num_rows($result) > 0 ){
			$error = "User name already exists.";
		} else {
			$today = date('Y-m-d H:i:s');
			$result = mysql_query("INSERT INTO `users` (`index`, `name`, `password`, `goal1`, `goal2`, `goal3`, `goal4`, `goal5`, `goal6`, `sort1`, `sort2`, `missing`,`mode`,`joined`,`linked_accounts`) VALUES (NULL, '".$_POST['name']."', '".$_POST['password']."', '', '', '', '', '', '', 'institute', 'alphabetical', '15','light','".$today."','')");
			if (!$result){ 
				$error = "Error adding user to database.";
			} else {
				$user = $_POST['name'];
				$cookielife = time() + 31556926;
				setcookie('HT_user', $user, $cookielife);
				
				$result = mysql_query("SELECT * FROM gold WHERE age <> '-1' ORDER BY clone ASC");
				while($row = mysql_fetch_array($result)){
					mysql_query("INSERT INTO `inventory` (`index`, `user`, `clone`, `purple_qty`, `gold_qty`) VALUES (NULL, '".$user."', '".$row['clone']."', '0', '0')");
				}
				mysql_query("INSERT INTO `chests` (`user`, `four_systems`, `royal_casino`, `co_ordinary`, `co_reg`, `co_reg2`, `rare_co`, `precious_light`, `precious_dark`, `dragon`, `anniversary`, `8th_anni`, `drag_options`) VALUES ('".$user."', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0');"); 
				gold_missing($user);
				red_missing($user);
				?>
				<script>window.location='https://olfactoryhues.com/clone-evolution/';</script>
				<?
			}
		}
	}
}



?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
    <head>
		<? include 'header.php';?>

    </head>
	
    <body id="body">

		<!-- preloader -->
		<div id="preloader">
            <div class="loder-box">
            	<div class="battery"></div>
            </div>
		</div>
		<!-- end preloader -->

        <!--
        Fixed Navigation
        ==================================== -->
        <header id="navigation" class="navbar-inverse navbar-fixed-top animated-header">
            <div class="container">
                <div class="navbar-header">
                    <!-- responsive nav button -->
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
                    </button>
					<!-- /responsive nav button -->
					
					<!-- logo -->
					<h1 class="navbar-brand">
						<img src="img/ghoti.jpg" height="60" style="float: left; margin-top: -20px;"> &nbsp;&nbsp;&nbsp;<font color="#FFFFFF">Hero Tracker</font> by Ghoti
					</h1>
					<!-- /logo -->
                </div>

				<!-- main nav -->
                <nav class="collapse navbar-collapse navbar-right" role="navigation">
                    <ul id="nav" class="nav navbar-nav">
                    </ul>
                </nav>
				<!-- /main nav -->
				
            </div>
        </header>
        <!--
        End Fixed Navigation
        ==================================== -->
		
		<main class="site-content" role="main">
		<div id="clock">
          <video id="loading-video" class="clock" autoplay muted playsinline loop width="300" preload="auto">
            <source src="img/clock.mp4" type="video/mp4 ">
            Your browser does not support the video tag.
          </video>
          <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; color: yellow; font-size: 18px; font-weight: bold; text-shadow: -1px -1px 0 black, 1px -1px 0 black, -1px 1px 0 black, 1px 1px 0 black, -2px 2px 0 black, 2px 2px 0 black ;">
          	<center>
          		Working on it!
                <br /><br />
                Patience conquers all.
            </center>
          </div> 	
        </div>	        		
			<!-- Service section -->
			<section id="service">
				<div class="container">
                    <center>
                    <br /><br /><br /><br /><br />       	
                    <div class="sec-title text-center" style="padding-top: 30px;">
                        <h2 class="wow animated bounceInLeft">Please Log In or Register</h2>
                        Note: I am not requiring an email addresses to register, so if you forget your password,<br />you will need to contact Ghoti on Discord for assistance.
                    	<?
                        if ($error){
							?>
                        	<br /><br /><font color="red"><b><? echo $error;?></b></font>
                        	<?	
						}
						?>
                    </div>
                    <form name="login" method="post">
                        <table>
                            <tr>
                                <td colspan="2" align="center"><b><u>Log In</b></u></td>
                            <tr>
                                <td style="padding: 3px;" align="right">Name:</td>
                                <td style="padding: 3px;"><input type="text" name="name" /></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px;" align="right">Password:</td>
                                <td style="padding: 3px;"><input type="password" name="password" /></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px;"></td>
                                <td style="padding: 3px;" align="right"><input type="submit" name="login_submit" class="update-inventory-button" value="Log In" /></td>
                            </tr>
                        </table>
                    </form>
                    </center>				
				</div>
			</section>
			<section id="service">
				<div class="container">
                    <center>
                    <form name="register" method="post">
                        <table>
                            <tr>
                                <td colspan="2" align="center"><b><u>Register</b></u></td>
                            <tr>
                            <tr>
                                <td style="padding: 3px;" align="right">Name:</td>
                                <td style="padding: 3px;"><input type="text" name="name" /></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px;" align="right">Password:</td>
                                <td style="padding: 3px;"><input type="password" name="password" /></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px;"></td>
                                <td  style="padding: 3px;" align="right"><input type="submit" class="update-inventory-button" name="register_submit" value="Register" /></td>
                            </tr>
                        </table>
                    </form>
                    </center>
				</div>
			</section>
			<!-- end Service section -->
		</main>
		
		<!-- Essential jQuery Plugins
		================================================== -->
		<? include 'footer.php';?>
    </body>
</html>