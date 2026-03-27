<?
include 'top_php.php';

$done = "no";
$testing = "no";
$test_exists = "no";

if (strpos($user, "_test")){
	$testing = "yes";
} else {
	$new_user = $user . "_test";	
	$result = mysql_query("SELECT * FROM users WHERE name = '".$new_user."'");
	if ($row = mysql_fetch_array($result)){	
		$test_exists = "yes";
	}
}

if (isset($_POST['switch'])){
	setcookie("HT_user", "", time() - 3600);
	$user = str_replace("_test", "", $user);
	$cookielife = time() + 31556926;
	setcookie('HT_user', $user, $cookielife);
	?>
	<script>window.location = window.location.href;</script>
	<?	
}

if (isset($_POST['switch_to_test'])){
	setcookie("HT_user", "", time() - 3600);
	$cookielife = time() + 31556926;
	setcookie('HT_user', $new_user, $cookielife);
	?>
	<script>window.location = window.location.href;</script>
	<?	
}
	
if (isset($_POST['copy'])){
	$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
	$row = mysql_fetch_array($result);
	$password = $row[password];
	$mode = $row[mode];
	$sort1 = $row[sort1];
	$sort2 = $row[sort2];
	$missing = $row[missing];
	$goal1 = $row[goal1];
	$goal2 = $row[goal2];
	$goal3 = $row[goal3];
	$goal4 = $row[goal4];
	$goal5 = $row[goal5];
	$goal6 = $row[goal6];
	
	if ($test_exists == "yes"){
		// account already exists, delete 
		mysql_query("DELETE FROM users WHERE name = '".$new_user."'");
		mysql_query("DELETE FROM inventory WHERE user = '".$new_user."'");
		mysql_query("DELETE FROM chests WHERE user = '".$new_user."'");	
		mysql_query("DELETE FROM gold_missing WHERE user = '".$new_user."'");	
		mysql_query("DELETE FROM red_missing WHERE user = '".$new_user."'");
		mysql_query("DELETE FROM red_inventory WHERE user = '".$new_user."'");	
		mysql_query("DELETE FROM user_chest_priorities WHERE user = '".$new_user."'");		
	} 
	$today = date('Y-m-d H:i:s');
	$result = mysql_query("INSERT INTO `users` (`index`, `name`, `password`, `goal1`, `goal2`, `goal3`, `goal4`, `goal5`, `goal6`, `sort1`, `sort2`, `missing`,`mode`,`joined`) VALUES (NULL, '".$new_user."', '".$password."', '".$goal1."', '".$goal2."', '".$goal3."', '".$goal4."', '".$goal5."', '".$goal6."', '".$sort1."', '".$sort2."', '".$missing."','".$mode."','".$today."')");

	$result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."'");
	while($row = mysql_fetch_array($result)){
		mysql_query("INSERT INTO `inventory` (`index`, `user`, `clone`, `purple_qty`, `gold_qty`) VALUES (NULL, '".$new_user."', '".$row['clone']."', '".$row['purple_qty']."', '".$row['gold_qty']."')");
	}
	$result = mysql_query("SELECT * FROM red_inventory WHERE user = '".$user."'");
	while($row = mysql_fetch_array($result)){
		mysql_query("INSERT INTO `red_inventory` (`index`, `user`, `clone`, `red_qty`, `awaken`, `fodder`,`is_goal`) VALUES (NULL, '".$new_user."', '".$row['clone']."', '".$row['red_qty']."', '".$row['awaken']."', '".$row['fodder']."', '".$row['is_goal']."')");
	}
	
	$result = mysql_query("SELECT * FROM chests WHERE user = '".$user."'");
	$row = mysql_fetch_array($result);	
	$query = "INSERT INTO `chests` (`user`, `four_systems`, `royal_casino`, `co_ordinary`, `co_reg`, `co_reg2`, `rare_co`, `precious_light`, `precious_dark`, `dragon`, `anniversary`, `8th_anni`, `drag_options`) VALUES ('".$new_user."', '".$row['four_systems']."', '".$row['royal_casino']."', '".$row['co_ordinary']."', '".$row['co_reg']."', '".$row['co_reg2']."', '".$row['rare_co']."', '".$row['precious_light']."', '".$row['precious_dark']."', '".$row['dragon']."', '".$row['anniversary']."', '".$row['8th_anni']."', '".$row['drag_options']."')";
	mysql_query($query);
	
	$result = mysql_query("SELECT * FROM user_chest_priorities WHERE user = '".$user."'");
	while ($row = mysql_fetch_array($result)){	
		$query = "INSERT INTO `user_chest_priorities` (`user`, `chest`, `priority_data`) VALUES ('".$new_user."', '".$row['chest']."', '".$row['priority_data']."')";
		mysql_query($query);
	}


	gold_missing($new_user);
	red_missing($new_user);
	$done = "yes";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!--[if lt IE 7]>      <html lang="en" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Create Test Account</title>
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
          <? include 'menu.php';?>
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
          <main class="site-content" role="main">
                <!-- Service section -->
                <form name="copy-account" id="copy-account" method="post">
                <section id="service">
                    <div class="container">
                        <div class="row">
                            <br /><br /><br /><br /><br />       	
                            <div class="sec-title text-center" style="padding-top: 30px;">                  
                                <h1>Testing Environment:</h1>
                                <h3>Duplicate Your Account</h3><br />
                                <?
								if ($testing == "yes"){
									?>
									<h3><font color="red">You are currently in your testing environment.<br /><br /></font></h3> 
                                    <button type="submit" name="switch" value="yes" class="update-inventory-button">Load Regular Account</button>   
                                    <?
								} else {
									if ($done == "yes"){
										$test_exists = "yes"
										?>
										<h3><font color="red"><? echo $user;?>_test creation complete!<br /><br /></font></h3> 
                                        <h5>Testing account:</h5>  
                                        <button type="submit" name="switch_to_test" value="yes" class="update-inventory-button">Load <? echo $new_user; ?> Account</button>  <br /><br />	
										<?	
									}
									?>
									<h5>
									This will make a copy your account under the name: <b><? echo $user;?>_test.</b><br /><br />
									You can log into that account using your same password.<br /><br />
									Simulate making trades without messing up your inventory.<br /><br />
                                    </h5>
                                    <?
									if ($test_exists == "yes"){
										?>
                                        <h5>Using the Copy Account button will overwrite your current test account.</h5><br />
                                        <?	
									}
									?>
									<button type="submit" name="copy" value="yes" class="update-inventory-button">Copy Account</button>   	
                                    <?
									if ($test_exists == "yes"){
										?>
                                        <br /><br />
                                        <h5>Testing account:</h5>  
                                        <button type="submit" name="switch_to_test" value="yes" class="update-inventory-button">Load <? echo $new_user; ?> Account</button>  <br /><br />		
                                        <?	
									}
								}
								?>
                                                             
                            </div>
                        </div>
                    </div>
                </section>
                </form>
          </main>
          <? include 'footer.php';?>
    </body>
</html>