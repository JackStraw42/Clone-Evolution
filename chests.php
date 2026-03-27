<?
include 'top_php.php';

if (isset($_POST['adjust_chest'])) {
    $adj_clone = mysql_real_escape_string($_POST['adjust_clone']);
    $adj_chest = mysql_real_escape_string($_POST['adjust_chest']);

    $result = mysql_query("SELECT * FROM inventory WHERE user = '$user' AND clone = '$adj_clone'");
    $row = mysql_fetch_array($result);
    $qty = $row['purple_qty'] + 1;
    mysql_query("UPDATE inventory SET purple_qty='$qty' WHERE user = '$user' AND clone = '$adj_clone'");
    
    $result = mysql_query("SELECT * FROM chests WHERE user = '$user'");
    $row = mysql_fetch_array($result);
    
    // Using backticks in case $adj_chest has spaces
    $qty = $row[$_POST['adjust_chest']] - 1;
    mysql_query("UPDATE chests SET `$adj_chest`='$qty' WHERE user = '$user'");
    
    gold_missing($user);
    red_missing($user);
} else {
    // No adjustment needed
}

$result = mysql_query('SHOW COLUMNS FROM `chests`');
while ($array = mysql_fetch_array($result)) {
    $chests[] = $array['Field'];
}
$chests = array_slice($chests, 1);

if (isset($_POST['submit_inventory'])) {
    // 1. Update Chest Quantities
    foreach ($chests as $chest) {
        // Backticks are required here if the column names in 'chests' have spaces
        $val = mysql_real_escape_string($_POST[$chest]);
        mysql_query("UPDATE chests SET `$chest` = '$val' WHERE user = '$user'");
    }

    // 2. Save Chest Clone Priorities
    if (isset($_POST['prio'])) {
        foreach ($_POST['prio'] as $target_chest => $clones_data) {
            $safe_chest = mysql_real_escape_string($target_chest);
            $json_prio = mysql_real_escape_string(json_encode($clones_data));
            
            // This REPLACE will now work for every chest once the DB index is fixed
            mysql_query("REPLACE INTO user_chest_priorities (user, chest, priority_data) 
                         VALUES ('$user', '$safe_chest', '$json_prio')");
        }
    } else {
        // No priorities submitted
    }

    gold_missing($user);
    red_missing($user);
} else {
    // Form not submitted
}

$result = mysql_query("SELECT * FROM chests WHERE user = '".$user."'");
$row = mysql_fetch_array($result);

?>
<!DOCTYPE html>
<html lang="en" class="no-js"> <head>
	<? include 'header.php';?>
	<script>
        function adjust_inventory(chest_name,chest, clone){			
            result = confirm("Remove: 1x " + chest_name + "\nAdd: 1x " + clone);
            if (result === true) {
				var element = document.getElementById('adj_chest');
				element.value = chest;
				var element = document.getElementById('adj_clone');
				element.value = clone;
				document.getElementById('adj_inventory').submit();
            }
        }
    </script>
    </head>
    <body id="body">

		<div id="preloader">
            <div class="loder-box">
            	<div class="battery"></div>
            </div>
		</div>
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
        <style>
			.summary{
				text-align: center;	
				width: 365px;
			}
			.summary td{
				padding: 5px;
				text-align: left;
				vertical-align: middle;
				color: #000;
				border: 1px solid #000;
				white-space: nowrap;
				font-size: 13px;
				font-weight: bold;
			}
			.summary tr td:nth-child(1) { border-right: none; }
			.summary tr td:nth-child(2) { border-left: none; border-right: none; }
			.summary tr td:nth-child(3) { border-left: none; }
		</style>
		
		<style>
			/* Priority Tooltip Styles */
			.priority-tooltip {
				position: relative;
				display: inline-block;
				cursor: help;
			}
			
			.priority-tooltip .priority-tooltiptext {
				visibility: hidden;
				width: 250px;
				background-color: #000;
				color: #fff;
				text-align: center;
				border-radius: 6px;
				padding: 10px;
				position: absolute;
				z-index: 1;
				bottom: 125%;
				left: 50%;
				margin-left: -140px;
				opacity: 0;
				transition: opacity 0.3s;
				font-size: 12px;
				font-weight: normal;
				line-height: 1.4;
				text-align: left;
			}
			
			.priority-tooltip .priority-tooltiptext::after {
				content: "";
				position: absolute;
				top: 100%;
				left: 50%;
				margin-left: -5px;
				border-width: 5px;
				border-style: solid;
				border-color: #333;
				
			}
			
			.priority-tooltip:hover .priority-tooltiptext {
				visibility: visible;
				opacity: 1;
			}
		</style>
		<main class="site-content" role="main">
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br />  	
                        <div class="sec-title text-center" style="padding-top: 30px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;"><font color="yellow" style="-webkit-text-stroke-width: 2px; -webkit-text-stroke-color: black;">Chest Inventory</font></h2>
                        </div>
					</div>
                    <form name="owned_chests" method="post">
                    <center><input type="submit" name="submit_inventory" value="Update Chest Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center><br />
                    <br />
                    <div class="row"> 
						<?
                        foreach ($chests as $chest) {
                            $chest_name = chest_name($chest);
                            
                            // FETCH SAVED PRIORITIES FOR THIS CHEST
							$prio_res = mysql_query("SELECT priority_data FROM user_chest_priorities WHERE user = '$user' AND chest = '$chest'");
                            $prio_row = mysql_fetch_array($prio_res);
                            $user_prio_map = $prio_row ? json_decode($prio_row['priority_data'], true) : array();
                            
                            ?>
                            <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                                <h4 class="wow animated bounceInLeft"><? echo $chest_name; ?></h4>
                                <center>
                                <table class="summary">
                                    <tr>
                                        <td style="background-color: gold; border: 1px solid #000;" colspan='3'>
                                        	<table style="padding: 0px; margin: 0; border: none; width: 100%;">
                                            	<tr style="border: none;">
                                                	<td style="border: none;"><center><img src="img/chests/<? echo $chest; ?>.jpg" width="50"></center></td>
                                                    <td style="text-align: right; border: none;">
                                                    	<div class="priority-tooltip">
                                                        	<img src="img/information.png" height="30">&nbsp;&nbsp;&nbsp;&nbsp;<br />
                                                        	Clone Priority:
                                                            <span class="priority-tooltiptext">Set clone priorities to control which<br />clones get used from chests first:<br />1 = highest priority, 20 = lowest priority.<br />&#128683; = Never use a chest for this clone.</span>
                                                        </div>

                                                    </td>
												</tr>
                                            </table>
                                        </td>
                                        <td style="background-color: gold;">
                                            <center>
                                            <b>Owned: </b>
                                            <select name="<? echo $chest; ?>">
                                                <? for ($i = 0; $i < 21; $i++) { ?>
                                                    <option value="<? echo $i; ?>" <? if ($i == $row[$chest]) { ?> selected <? } ?>><? echo $i; ?></option>
                                                <? } ?>
                                            </select>
                                            </center>
                                        </td>
                                    </tr>
                                    <?
                                    $clone_result = mysql_query("SELECT * FROM gold WHERE chest LIKE '%" . $chest . "%' ORDER BY institute, clone");
                                    while ($clone_row = mysql_fetch_array($clone_result)) {
                                        switch ($clone_row['institute']) {
                                            case 'Lightning': $color = "#b4c6e7"; break;
                                            case 'Fire':      $color = "#f2aaaa"; break;
                                            case 'Earth':     $color = "#92d050"; break;
                                            case 'Water':     $color = "#00b0f0"; break;
                                            case 'Chaos':     $color = "#cc99e0"; break;
                                            case 'Order':     $color = "#ffff00"; break;
                                        }
                                        ?>
                                        <tr>
                                            <td style="background-color: <? echo $color; ?>; width: 45px;">
                                                <img src='img/purples/<? echo $clone_row['clone']; ?>.jpg' height='40'>
                                            </td>
                                            
                                            <td style="background-color: <? echo $color; ?>; text-align: left; padding-left: 5px;">
                                                <? echo $clone_row['clone']; ?>
                                            </td>
                                            
                                            <td style="background-color: <? echo $color; ?>; text-align: right; padding-right: 5px;">
                                                <? 
                                                // Use the map we fetched at the start of the chest loop
                                                $current_prio = isset($user_prio_map[$clone_row['clone']]) ? $user_prio_map[$clone_row['clone']] : 1; 
                                                ?>
                                                <select name="prio[<? echo $chest; ?>][<? echo $clone_row['clone']; ?>]" style="font-size: 11px;">
                                                    <option value="0" <? if ($current_prio == 0) echo 'selected'; ?>>&#128683;</option>
                                                    <? for ($p = 1; $p <= 20; $p++): ?>
                                                        <option value="<? echo $p; ?>" <? if ($p == $current_prio) echo 'selected'; ?>><? echo $p; ?></option>
                                                    <? endfor; ?>
                                                </select>
                                            </td>
                                            
                                            <td style="background-color:#CCC;">
                                                <center>
                                                <? if ($row[$chest] > 0) { ?>
                                                    <a class="btn btn-primary" onclick="adjust_inventory('<? echo $chest_name; ?>','<? echo $chest; ?>','<? echo $clone_row['clone']; ?>')">Make It!</a>
                                                <? } else { ?>
                                                    <button class="btn btn-primary" type="button">No Chest</button>
                                                <? } ?>
                                                </center>
                                            </td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                </table>
                                </center>
                            </div>
                            <?
                        }
                        ?>        
                    </div>
                    <br \>
                    <center><input type="submit" name="submit_inventory" value="Update Chest Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center>
                    </form> 
                </div>
            </section>
            
		</main>	
        
        <form style="display: none" id="adj_inventory" name="adj_inventory" action="" method="POST">
        	<input type="hidden" id="adj_chest" name="adjust_chest" value=""/>
        	<input type="hidden" id="adj_clone" name="adjust_clone" value=""/>
        </form>

		<? include 'footer.php';?>
	
    </body>
</html>