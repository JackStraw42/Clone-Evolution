<?
include 'top_php.php';

if (isset($_POST["submit_inventory"])) {
	foreach ($_POST as $key=>$value){
		$pos1 = strpos($key, "purple"); 
		$pos2 = strpos($key, "gold"); 
		if ($pos1 !== false){
			$this_clone = str_replace("purple_","",$key);
			$this_clone = str_replace("9",".",$this_clone);
			$this_clone = str_replace("_"," ",$this_clone);
			$result = mysql_query("SELECT * FROM inventory WHERE user='".$user."' AND clone ='".$this_clone."'"); 
			if ($row = mysql_fetch_array($result)){
				$result = mysql_query("UPDATE inventory SET purple_qty = '".$value."' WHERE user = '".$user."' AND clone = '".$this_clone."'");
				if (!$result){ echo "Error updating purple inventory.<br />"; }
			} else {
				$result = mysql_query("INSERT INTO `inventory` (`index`, `user`, `clone`, `purple_qty`, `gold_qty`) VALUES (NULL, '".$user."', '".$this_clone."', '".$value."', '')");
				if (!$result){ echo "Error inserting purple inventory.<br />"; }
			}
		}
		if ($pos2 !== false){
			$this_clone = str_replace("gold_","",$key);
			$this_clone = str_replace("9",".",$this_clone);
			$this_clone = str_replace("_"," ",$this_clone);
			$result = mysql_query("SELECT * FROM inventory WHERE user='".$user."' AND clone ='".$this_clone."'"); 
			if ($row = mysql_fetch_array($result)){
				$result = mysql_query("UPDATE inventory SET gold_qty = '".$value."' WHERE user = '".$user."' AND clone = '".$this_clone."'");
				if (!$result){ echo "Error updating gold inventory.<br />"; }
			} else {
				$result = mysql_query("INSERT INTO `inventory` (`index`, `user`, `clone`, `purple_qty`, `gold_qty`) VALUES (NULL, '".$user."', '".$this_clone."', '', '".$value."')");
				if (!$result){ echo "Error inserting gold inventory.<br />"; }
			}
		}
	}
	gold_missing($user);
	red_missing($user);
}

?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
    <head>
	<? include 'header.php';?>
<!--    <script>
		function waiting(){
			document.getElementById('clock').style.display = 'block';
		}
	</script>-->  
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
        	<?
			$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
			$row = mysql_fetch_array($result);
			if ($row['sort1'] == "institute"){
				institute($row[sort2]);
			} else {
				alphabetical();
			}
			?>
		</main>
		<? include 'footer.php';?>
    </body>
</html>
<?
function alphabetical(){
	global $user;
	?>
    <!-- Service section -->
    <form name="purple_inventory" id="purple-inventory-form" method="post">
    <section id="service">
        <div class="container">
            <div class="row">
                <br /><br /><br /><br /><br />       	
                <div class="sec-title text-center" style="padding-top: 30px;">
                	<h2 class="wow animated bounceInLeft" style="color: purple; -webkit-text-stroke-width: 2px; -webkit-text-stroke-color: black;">Purple Clone Inventory</h2>
                    <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center><br \>
                    <a href="export_inventory.php?user=<? echo $user;?>">Export Inventory to CSV</a>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn">
                    <table>
                    <?
					$result = mysql_query("SELECT * FROM gold WHERE age <> '-1' ORDER BY clone ASC");
                    $per_col = mysql_num_rows($result) / 4;
                    $next_col = $per_col;
                    $cnt = 1;
					$institutes = array('Lightning'=>'#b4c6e7','Fire'=>'#f2aaaa','Earth'=>'#92d050','Water'=>'#00b0f0','Chaos'=>'#cc99e0','Order'=>'#ffff00');
                    while($row = mysql_fetch_array($result)){
                        if ($cnt > $next_col){
                            $next_col = $next_col + $per_col;
                            ?>
                            </table>
                            </div>
                            <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn">
                            <table>
                            <?	
                        }
                        ?>
                        <tr style="border: 1px solid #000;">
                            <td align="left" valign="middle" width="180" style="background-color: <? echo $institutes[$row['institute']];?>; padding: 5px;">
                               	<?
								if ($row[age] == 0){
									$fcolor = "blue";	
								} else {
									$fcolor = "purple";
								}
								?>
								<font color="<? echo $fcolor;?>"><b>
								<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/clone_details.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: <? echo $fcolor;?>;"><? echo $row['clone'];?></a>
                                </b></font>
                            </td>
                            <td style="background-color: <? echo $institutes[$row['institute']];?>;">
                                <?
                                $inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                                if ($inv_row = mysql_fetch_array($inv_result)){
                                    $qty = $inv_row['purple_qty'];	
                                } else {
                                    $qty = 0;	
                                }
                                $clone_temp = str_replace(".","9",$row['clone']);
                                ?>
                                <select name="purple_<? echo $clone_temp;?>" style="color: black;">
                                <?
                                for ($i=0; $i<21; $i++){
                                    ?>
                                    <option value="<? echo $i;?>" <? if ($i == $qty){ ?> selected <? }?>><? echo $i;?></option>
                                    <?	
                                }
                                ?>
                                </select>
                            </td>
                        </tr>
                        <?
                        $cnt++;
                    }
                    ?>
                    </table>
                </div>
            </div>
            <br /><br />
            <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center>
            <br /><br /><br />
        </div>
    </section>
    <section id="service" style="background-color: #434343;">
        <div class="container">
            <div class="row">                	
                <div class="sec-title text-center" style="padding-top: 30px;">
                    <br />
                    <h2 class="wow animated bounceInLeft" style="color: yellow; -webkit-text-stroke-width: 2px; -webkit-text-stroke-color: black;">Gold Clone Inventory</h2>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn">
                    <table>
                    <?
                    $result = mysql_query("SELECT * FROM gold WHERE age <> '0' ORDER BY clone ASC");
                    $per_col = ceil(mysql_num_rows($result) / 4);
                    $next_col = $per_col;
                    $cnt = 1;
                    while ($row = mysql_fetch_array($result)){
                        if ($cnt > $next_col){
                            $next_col = $next_col + $per_col;
                            ?>
                            </table>
                            </div>
                            <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn">
                            <table>
                            <?	
                        }
                        ?>
                        <tr style="border: 1px solid #000;">
                            <td align="left" valign="middle" width="180" style="background-color: <? echo $institutes[$row['institute']];?>; padding: 5px;">
                                <font color="yellow"><b>
	                            <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: #000;"><? echo $row['clone'];?></a>
                                </b></font>
                            </td>
                            <td style="background-color: <? echo $institutes[$row['institute']];?>;">
                                <?
                                $qty = 0;
                                $inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                                if ($inv_row = mysql_fetch_array($inv_result)){
                                    $qty = $inv_row['gold_qty'];	
                                }
                                $clone_temp = str_replace(".","9",$row['clone']);
                                ?>
                                <select name="gold_<? echo $clone_temp;?>" style="color: black;">
                                <?
                                for ($i=0; $i<21; $i++){
                                    ?>
                                    <option value="<? echo $i;?>" <? if ($i == $qty){ ?> selected <? }?>><? echo $i;?></option>
                                    <?	
                                }
                                ?>
                                </select>
                            </td>
                        </tr>
                        <?
                        $cnt++;
                    }
                    ?>
                    </table>
                </div>
            </div>
            <br /><br />
            <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center>
            <br /><br /><br />
        </div>
    </section>
    </form> 
    <!-- end Service section -->

	<?
}

function institute($sort2){
	global $user;
	if ($sort2 == "alphabetical"){
		$sort = "clone";
	} else {
		$sort = "age";
	}
	?>
    <!-- Service section -->
    <form name="purple_inventory" method="post">
    <section id="service">
        <div class="container">
            <div class="row">     
                <br /><br /><br /><br /><br />       	
                <div class="sec-title text-center" style="padding-top: 30px;">
                    <h2 class="wow animated bounceInLeft" style="color: purple;">Purple Clone Inventory</h2>
                    <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center><br \>
                    <a href="export_inventory.php?user=<? echo $user;?>">Export Inventory to CSV</a>
                </div>
                <?
				$institutes = array('Lightning','Fire','Earth','Water','Chaos','Order');
				$colors = array('#b4c6e7','#f2aaaa','#92d050','#00b0f0','#cc99e0','#ffff00');
				for ($i=0; $i<count($institutes); $i++){
					if ($i == 4){
						?>
                        </div>
                        <div class="row">
                        <?
					}
					?>
                    <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                        <table style="background-color: <? echo $colors[$i];?>; min-height: 870px; width: 280px;">
                        	<tr><td colspan="3"><font color="black"><b><u><? echo $institutes[$i];?></u></b></font></td></tr>
                        <?
                        $result = mysql_query("SELECT * FROM gold WHERE institute='".$institutes[$i]."' AND age <> '-1' ORDER BY ".$sort." ASC");				
                        while($row = mysql_fetch_array($result)){
                            ?>
                            <tr>
                            	<td><img src="img/purples/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                <td align="left" valign="middle" style="padding-left: 5px;">
                                	<?
									if ($row[age] == 0){
										$fcolor = "blue";	
									} else {
										$fcolor = "purple";
									}
									?>
                                    <font color="<? echo $fcolor;?>"><b>
										<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/clone_details.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: <? echo $fcolor;?>;"><? echo $row['clone'];?></a>
                                    </b></font>
                                </td>
                                <td style="padding-left: 5px; min-width: 60px;">
                                    <?
                                    $inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                                    if ($inv_row = mysql_fetch_array($inv_result)){
                                        $qty = $inv_row['purple_qty'];	
                                    } else {
                                        $qty = 0;	
                                    }
                                    $clone_temp = str_replace(".","9",$row['clone']);
                                    ?>
                                    <select name="purple_<? echo $clone_temp;?>" style="color: black;">
                                    <?
                                    for ($c=0; $c<21; $c++){
                                        ?>
                                        <option value="<? echo $c;?>" <? if ($c == $qty){ ?> selected <? }?>><? echo $c;?></option>
                                        <?	
                                    }
                                    ?>
                                    </select>
                                </td>
                            </tr>
                            <?
                        }
                        ?>
                        </table>
                    </div>
                    <?
				}
				?>
            </div>
            <br /><br />
            <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center>
            <br /><br /><br />
        </div>
    </section>
    
    <section id="service" style="background-color: #434343;">
        <div class="container">
            <div class="row">                	
                <div class="sec-title text-center" style="padding-top: 30px;">
                    <br />
                    <h2 class="wow animated bounceInLeft" style="color: yellow;">Gold Clone Inventory</h2>
                </div>
                    <?
					$institutes = array('Lightning','Fire','Earth','Water','Chaos','Order');
					$colors = array('#b4c6e7','#f2aaaa','#92d050','#00b0f0','#cc99e0','#ffff00');
					for ($i=0; $i<count($institutes); $i++){
						if ($i == 4){
							?>
                        	</div>
                        	<div class="row">
                        	<?
						}
						?>
                        <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                            <table style="background-color: <? echo $colors[$i];?>; padding-bottom: 10px; min-height: 790px; width: 280px;">
                                <tr><td colspan="3"><font color="black"><b><u><? echo $institutes[$i];?></u></b></font></td></tr>
                                <?
								$result = mysql_query("SELECT * FROM gold WHERE institute = '".$institutes[$i]."' AND age <> '0' ORDER BY ".$sort." ASC");
								while ($row=mysql_fetch_array($result)){
									?>
                                    <tr>
                                    	<td><img src="img/golds/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                        <td align="left" valign="middle" style="padding-left: 5px; min-width: 180px;">
                                            <font color="black"><b>
                                            <? 
											if ($row[age] > 0){
												?>
                                            	<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: black;">
                                            	<?
											}
											echo $row['clone'];
											if ($row['age'] > 0){
												?>
                                            	</a>
                                            	<?
											}
											?>
                                            </b></font>
                                        </td>
                                        <td style="padding-left: 5px; min-width: 60px;">
                                            <?
                                            $qty = 0;
                                            $inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                                            if ($inv_row = mysql_fetch_array($inv_result)){
                                                $qty = $inv_row['gold_qty'];	
                                            }
                                            $clone_temp = str_replace(".","9",$row['clone']);
                                            ?>
                                            <select name="gold_<? echo $clone_temp;?>" style="color: black;">
                                            <?
                                            for ($c=0; $c<21; $c++){
                                                ?>
                                                <option value="<? echo $c;?>" <? if ($c == $qty){ ?> selected <? }?>><? echo $c;?></option>
                                                <?	
                                            }
                                            ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?
								}
								?>
                            </table>
                        </div>
						<?
                        $cnt++;
                    }
                    ?>
            </div>
            <br /><br />
            <center><input type="submit" name="submit_inventory" value="Update Inventory" style="color: black; font-weight: bold;" class="update-inventory-button"></center>
            <br /><br /><br />
        </div>
    </section>
    </form> 
    <!-- end Service section -->
	<?
}
?>