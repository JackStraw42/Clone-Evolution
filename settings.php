<?
include 'top_php.php';

if (isset($_POST["goals"])) {
	mysql_query("UPDATE users set goal1='".$_POST['goal1']."', goal2='".$_POST['goal2']."', goal3='".$_POST['goal3']."', goal4='".$_POST['goal4']."', goal5='".$_POST['goal5']."', goal6='".$_POST['goal6']."' WHERE name = '".$user."'");
	$message = "Goals updated!";
}

if (isset($_POST["sort_submit"])) {
	mysql_query("UPDATE users set sort1='".$_POST['sort1']."', sort2='".$_POST['sort2']."' WHERE name = '".$user."'");
	$message = "Sort order updated!";
}

if (isset($_POST["change_password"])) {
	mysql_query("UPDATE users set password='".$_POST['password']."' WHERE name = '".$user."'");
	$message = "Password updated!";
}

if (isset($_POST["change_mode"])) {
	mysql_query("UPDATE users set mode='".$_POST['mode']."' WHERE name = '".$user."'");
	?>
	<script>window.location='https://olfactoryhues.com/clone-evolution/settings.php?mode=updated';</script>
	<?
}

if ($_GET['mode'] == "updated"){
	$message = "Mode updated!";
}

if ($_POST['confirm_reset'] == "yes"){
	mysql_query("DELETE FROM inventory WHERE user = '".$user."'");
	gold_missing($user);
	red_missing($user);
	$message = "Clones reset!";
}

if (isset($_POST["missing_submit"])) {
	mysql_query("UPDATE users set missing='".$_POST['missing']."' WHERE name = '".$user."'");
	$message = "Maximum Missing Clones updated!";
}

$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
$row = mysql_fetch_array($result);

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
		<!-- end preloader -->
        <? include 'menu.php';?>
		<main class="site-content" role="main">
        		
			<!-- Service section -->
			<section id="service">
				<div class="container">
                    <center>
                    <br /><br /><br />      	
                    <div class="sec-title text-center">
                        <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;">User Settings</h2>
                    </div>
                    	<?
						if ($message){
							?>
							<font color="green"><b><? echo $message;?></b></font><br /><br />
							<?
                        }
						?>
                        <table>
                        	<tr>
                                <td style="padding: 3px;" align="right" valign="top"><h4>Clone Goals:</h4></td>
                                <td>
                                	<form name="goals" method="post">
                                	<table>
                                    	<tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal1">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal1'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal2">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal2'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal3">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal3'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal4">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal4'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal5">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal5'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left">
                                                <select name="goal6">
                                                    <option value="">None</option>
                                                    <?
                                                    $clone_result = mysql_query("SELECT * FROM red ORDER BY clone");
                                                    while ($clone_row = mysql_fetch_array($clone_result)){
                                                        if ($clone_row['clone'] == "Aisha"){
                                                            continue;
                                                        }
                                                        ?>
                                                        <option value="<? echo $clone_row['clone'];?>" <? if ($row['goal6'] == $clone_row['clone']){ ?> selected <? }?>><? echo $clone_row['clone'];?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 3px;" align="left"><input type="submit" name="goals" value="Update Goals"></td>
                                        </tr>
                                    </table>
                                    </form>
														                                    
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right"><h4>Clone Sorting:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                	<script>
										function alph_check(){
											if (document.getElementById("alph1").value == "alphabetical"){
												document.getElementById("alph2").value = "alphabetical";
											}
										}
									</script>
                                	<form name="sorting1" method="post">
                                        <select name="sort1" id="alph1" onChange="alph_check()">
                                            <!--<option value="alphabetical" <? if ($row['sort1'] == "alphabetical"){ ?> selected <? } ?>>Alphabetical</option>-->
                                            <option value="institute" <? if ($row['sort1'] == "institute"){ ?> selected <? } ?>>Institute</option>
                                        </select>
                                        <select name="sort2" id="alph2" onChange="alph_check()">
                                            <option value="alphabetical" <? if ($row['sort2'] == "alphabetical"){ ?> selected <? } ?>>Alphabetical</option>
                                            <option value="age" <? if ($row['sort2'] == "age"){ ?> selected <? } ?>>Age</option>
                                        </select>
                                        <input type="submit" name="sort_submit" value="Update Sort Order">
                                    </form>
                                </td>
                            </tr>    
                              
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right"><h4>Max Missing Clones:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                	<form name="max_missing" method="post">
                                        <select name="missing">
                                            <?
											for ($i=1; $i <=15; $i++){
												?>
												<option value="<? echo $i;?>" <? if ($row['missing'] == $i){ ?> selected <? }?>><? echo $i;?></option>
												<?
											}
											?>
                                        </select>
                                            <input type="submit" name="missing_submit" value="Update Max Missing">
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right"><h4>Mode:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                	<form name="change_mode" method="post">
                                		<select name="mode">
                                    		<option value="light">Light</option>
                                        	<option value="dark" <? if ($row['mode'] == "dark"){ ?> selected <? } ?>>Dark</option>
                                    	</select>
                                    	<input type="submit" name="change_mode" value="Update Mode">
                                    </form>
                                </td>
                            </tr>      
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right"><h4>Change Password:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                	<form name="change_password" method="post">
                                		<input type="password" name="password" />
                                    	<input type="submit" name="change_password" value="Update Password">
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right" valign="top"><h4>Linked Accounts:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                    
                                    <h4>
                                    <?
                                    if ($row[linked_accounts] !== ""){
										?>
                                        <table>
                                        <?
                                        $accts = explode(";",$row[linked_accounts]);
										usort($accts, 'strnatcasecmp');
                                        foreach ($accts as $key => $value) {
											?>
                                            <tr>
                                            	<td><? echo $value;?></td>
                                                <td style="height: 26px;">
                                                    <form name="remove_<? echo $value;?>" method="post">
                                                    	<input type="hidden" name="remove_link" value="<? echo $value;?>">
                                                        <input type="submit" name="remove_submit" value="Unlink" style="color: #000; font-size: 12px; font-weight: 400; font-family: 'Open Sans', sans-serif; height: 23px; margin-left: 10px;">
                                                    </form>
                                                </td>
                                            </tr>
                                            <?
                                        }
                                        ?>
                                        </table>
                                        <?
                                    } else {
                                        echo "No accounts linked.<br />";	
                                    }
                                    ?>
                                    <br />
                                    <form name="linked_accounts" method="post">
                                        Account Name: <input type="text" name="account_to_link" style="color: #000; font-weight: 400; font-family: 'Open Sans', sans-serif;"> 
                                        <input type="submit" name="add_link" value="Add Link" style="color: #000; font-weight: 400; font-family: 'Open Sans', sans-serif;">
                                        </h4>
                                        <h5>
                                        Note: In order to link accounts, the password for the account<br />
                                        to be linked must be the same as the password for this account.<br />
                                        </h5>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 3px; padding-top: 20px;" align="right"><h4>Reset All Clones:</h4></td>
                                <td style="padding: 3px; padding-top: 20px;" align="left">
                                    <script>
										function confirm_reset(){
											if (confirm("Are you sure you want to reset all clones? This connot be reversed!") == true){
												document.getElementById("confirm_delete").submit();
											}
										}
									</script>
                                	<button onClick="confirm_reset()" class="update-inventory-button"/>Start Over</button>
                                </td>
                            </tr>
                            
                        </table>
                    </form>
                    <form name="confirmation" id="confirm_delete" method="post">
                    	<input type="hidden" name="confirm_reset" value="yes">
                    </form>
                    </center>				
				</div>
			</section>
		</main>
		
		<!-- Essential jQuery Plugins
		================================================== -->
		<? include 'footer.php';?>
    </body>
</html>