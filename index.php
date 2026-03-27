<?
include 'top_php.php';

if (isset($_POST["submit_inventory"])) {
    // Group 1: Main Inventory (Purple & Gold)
    $group_main = array(); 
    // Group 2: Red Inventory
    $group_red = array();

    foreach ($_POST as $key => $value) {
        // Handle Purple
        if (strpos($key, "purple_") === 0) {
            $clone_name = str_replace(array("purple_", "9", "_"), array("", ".", " "), $key);
            if (!isset($group_main[$clone_name])) {
                $group_main[$clone_name] = array('purple_qty' => 0, 'gold_qty' => 0);
            }
            $group_main[$clone_name]['purple_qty'] = (int)$value;
        } 
        // Handle Gold
        elseif (strpos($key, "gold_") === 0) {
            $clone_name = str_replace(array("gold_", "9", "_"), array("", ".", " "), $key);
            if (!isset($group_main[$clone_name])) {
                $group_main[$clone_name] = array('purple_qty' => 0, 'gold_qty' => 0);
            }
            $group_main[$clone_name]['gold_qty'] = (int)$value;
        }
        // Handle Red
        elseif (strpos($key, "red_") === 0) {
            $clone_name = str_replace(array("red_", "9", "_"), array("", ".", " "), $key);
            $group_red[$clone_name] = (int)$value;
        }
    }

    // 1. Batch Update Main Inventory (Purple/Gold)
    if (!empty($group_main)) {
        $rows = array();
        foreach ($group_main as $clone => $data) {
            $rows[] = "('" . mysql_real_escape_string($user) . "', 
                        '" . mysql_real_escape_string($clone) . "', 
                        " . $data['purple_qty'] . ", 
                        " . $data['gold_qty'] . ")";
        }
        $sql = "INSERT INTO `inventory` (`user`, `clone`, `purple_qty`, `gold_qty`) 
                VALUES " . implode(',', $rows) . " 
                ON DUPLICATE KEY UPDATE 
                purple_qty = VALUES(purple_qty), 
                gold_qty = VALUES(gold_qty)";
        mysql_query($sql);
    }

    // 2. Batch Update Red Inventory
    if (!empty($group_red)) {
        $rows = array();
        foreach ($group_red as $clone => $qty) {
            $rows[] = "('" . mysql_real_escape_string($user) . "', 
                        '" . mysql_real_escape_string($clone) . "', 
                        " . $qty . ")";
        }
        $sql = "INSERT INTO `red_inventory` (`user`, `clone`, `red_qty`) 
                VALUES " . implode(',', $rows) . " 
                ON DUPLICATE KEY UPDATE 
                red_qty = VALUES(red_qty)";
        
        $result = mysql_query($sql);
        if (!$result) {
            echo "Red Inventory Error: " . mysql_error();
        }
    }

    // 3. Run calculations
    gold_missing($user);
    red_missing($user);
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js"> <head>
    <? include 'header.php';?>
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
        <main class="site-content" role="main">
            <?
            $result = mysql_query("SELECT * FROM users WHERE name = '".mysql_real_escape_string($user)."'");
            $row = mysql_fetch_array($result);
			if ($row['sort1'] == "alphabetical"){
				alphabetical();
			} else {
	            institute($row['sort2']);
			}
            ?>
        </main>
        <? include 'footer.php';?>
    </body>
</html>

<?
function institute($sort2) {
    global $user;
    if ($sort2 == "alphabetical") {
        $sort = "clone";
    } else {
        $sort = "age";
    }

    $main_inv_cache = array();
    $inv_res = mysql_query("SELECT clone, purple_qty, gold_qty FROM inventory WHERE user = '".mysql_real_escape_string($user)."'");
    while ($i_row = mysql_fetch_array($inv_res)) {
        $main_inv_cache[$i_row['clone']] = $i_row;
    }

    $red_inv_cache = array();
    $red_inv_res = mysql_query("SELECT clone, red_qty FROM red_inventory WHERE user = '".mysql_real_escape_string($user)."'");
    while ($ri_row = mysql_fetch_array($red_inv_res)) {
        $red_inv_cache[$ri_row['clone']] = $ri_row['red_qty'];
    }
    ?>
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
                for ($i=0; $i<count($institutes); $i++) {
                    if ($i == 4) {
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
                        while ($row = mysql_fetch_array($result)) {
                            ?>
                            <tr>
                                <td><img src="img/purples/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                <td align="left" valign="middle" style="padding-left: 5px;">
                                    <?
                                    if ($row['age'] == 0) {
                                        $fcolor = "blue";   
                                    } else {
                                        $fcolor = "purple";
                                    }
                                    $font_shrink = "";
                                    if (strlen($row['clone']) > 19) {
                                        $font_shrink = " font-size: 14px;";
                                    }
                                    ?>
                                    <font color="<? echo $fcolor;?>" style=" <? echo $font_shrink;?>"><b>
                                        <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/clone_details.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: <? echo $fcolor;?>;"><? echo $row['clone'];?></a>
                                    </b></font>
                                </td>
                                <td style="padding-left: 5px; min-width: 60px;">
                                    <?
                                    // Use cache instead of query
                                    $qty = 0;
                                    if (isset($main_inv_cache[$row['clone']])) {
                                        $qty = $main_inv_cache[$row['clone']]['purple_qty'];
                                    }
                                    $clone_temp = str_replace(".","9",$row['clone']);
                                    ?>
                                    <select name="purple_<? echo $clone_temp;?>" style="color: black;">
                                    <?
                                    for ($c=0; $c<21; $c++) {
                                        ?>
                                        <option value="<? echo $c;?>" <? if ($c == $qty) { ?> selected <? }?>><? echo $c;?></option>
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
                    for ($i=0; $i<count($institutes); $i++) {
                        if ($i == 4) {
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
                                while ($row = mysql_fetch_array($result)) {
                                    ?>
                                    <tr>
                                        <td><img src="img/golds/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                        <td align="left" valign="middle" style="padding-left: 5px; min-width: 180px;">
                                            <?
                                            $font_shrink = "";
                                            if (strlen($row['clone']) > 19) {
                                                $font_shrink = " font-size: 14px;";
                                            }
                                            ?>
                                            <font color="black" style=" <? echo $font_shrink;?>"><b>
                                            <? 
                                            if ($row['age'] > 0) {
                                                ?>
                                                <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: black;">
                                                <?
                                            } else {
												?>
                                                <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/clone_details.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: black;">
                                                <?	
											}
                                            echo $row['clone'];
                                            ?>
                                            </a>
                                            </b></font>
                                        </td>
                                        <td style="padding-left: 5px; min-width: 60px;">
                                            <?
                                            // Use cache instead of query
                                            $qty = 0;
                                            if (isset($main_inv_cache[$row['clone']])) {
                                                $qty = $main_inv_cache[$row['clone']]['gold_qty'];
                                            }
                                            $clone_temp = str_replace(".","9",$row['clone']);
                                            ?>
                                            <select name="gold_<? echo $clone_temp;?>" style="color: black;">
                                            <?
                                            for ($c=0; $c<21; $c++) {
                                                ?>
                                                <option value="<? echo $c;?>" <? if ($c == $qty) { ?> selected <? }?>><? echo $c;?></option>
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
            
    <section id="service">
        <div class="container">
            <div class="row">                   
                <div class="sec-title text-center" style="padding-top: 30px;">
                    <br />
                    <h2 class="wow animated bounceInLeft" style="color: red;">Red Clone Inventory</h2>
                </div>
                    <?
                    for ($i=0; $i<count($institutes); $i++) {
                        if ($i == 4) {
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
                                $result = mysql_query("SELECT * FROM red WHERE institute = '".$institutes[$i]."' AND age <> '0' ORDER BY ".$sort." ASC");
                                while ($row = mysql_fetch_array($result)) {
                                    ?>
                                    <tr>
                                        <td><img src="img/reds/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                        <td align="left" valign="middle" style="padding-left: 5px; min-width: 180px;">
                                            <?
                                            $font_shrink = "";
                                            if (strlen($row['clone']) > 19) {
                                                $font_shrink = " font-size: 14px;";
                                            }
                                            ?>
                                            <font color="black" style=" <? echo $font_shrink;?>"><b>
                                            <? 
                                            if ($row['age'] > 0) {
                                                ?>
                                                <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/red_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: black;">
                                                <?
                                            }
                                            echo $row['clone'];
                                            if ($row['age'] > 0) {
                                                ?>
                                                </a>
                                                <?
                                            }
                                            ?>
                                            </b></font>
                                        </td>
                                        <td style="padding-left: 5px; min-width: 60px;">
                                            <?
                                            // Use red cache instead of query
                                            $qty = 0;
                                            if (isset($red_inv_cache[$row['clone']])) {
                                                $qty = $red_inv_cache[$row['clone']];
                                            }
                                            $clone_temp = str_replace(".","9",$row['clone']);
                                            ?>
                                            <select name="red_<? echo $clone_temp;?>" style="color: black;">
                                            <?
                                            for ($c=0; $c<21; $c++) {
                                                ?>
                                                <option value="<? echo $c;?>" <? if ($c == $qty) { ?> selected <? }?>><? echo $c;?></option>
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
    </form> 
    <?
}

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
                        	<td style="background-color: <? echo $institutes[$row['institute']];?>;"><img src="img/purples/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                            <td align="left" valign="middle" width="180" style="background-color: <? echo $institutes[$row['institute']];?>; padding: 5px;">
                               	<?
								if ($row[age] == 0){
									$fcolor = "blue";	
								} else {
									$fcolor = "purple";
								}
								?>
								<font color="<? echo $fcolor;?>"><b>
								<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/clone_details.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: <? echo $fcolor;?>;">
									<? echo $row['clone'];?> 
                                </a>
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
                        	<td style="background-color: <? echo $institutes[$row['institute']];?>;"><img src="img/golds/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
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
    <section id="service">
        <div class="container">
            <div class="row">                	
                <div class="sec-title text-center" style="padding-top: 30px;">
                    <br />
                    <h2 class="wow animated bounceInLeft" style="color: red;">Red Clone Inventory</h2>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                    <table style="background-color: <? echo $colors[$i];?>; padding-bottom: 10px; min-height: 790px; width: 280px;">
                        <?
                        $result = mysql_query("SELECT * FROM red WHERE age <> '0' ORDER BY clone ASC");
                        $per_col = mysql_num_rows($result) / 4;
                        $next_col = $per_col;
                        $cnt = 1;
                        $institutes = array('Lightning'=>'#b4c6e7','Fire'=>'#f2aaaa','Earth'=>'#92d050','Water'=>'#00b0f0','Chaos'=>'#cc99e0','Order'=>'#ffff00');
                        while ($row=mysql_fetch_array($result)){
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
                            <tr>
                                <td style="background-color: <? echo $institutes[$row['institute']];?>;"><img src="img/reds/<? echo $row['clone'];?>.jpg" height="40" style="padding-left: 8px; padding-bottom: 5px;"></td>
                                <td align="left" valign="middle" style="padding-left: 5px; min-width: 180px; background-color: <? echo $institutes[$row['institute']];?>;">
                                    <?
                                    $font_shrink = "";
                                    if (strlen($row['clone']) > 19){
                                        $font_shrink = " font-size: 14px;";
                                    }
                                    ?>
                                    <font color="black" style=" <? echo $font_shrink;?>"><b>
                                    <? 
                                    if ($row[age] > 0){
                                        ?>
                                        <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/red_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="color: black;">
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
                                <td style="padding-left: 5px; min-width: 60px; background-color: <? echo $institutes[$row['institute']];?>;">
                                    <?
                                    $qty = 0;
                                    $inv_result = mysql_query("SELECT * FROM red_inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                                    if ($inv_row = mysql_fetch_array($inv_result)){
                                        $qty = $inv_row['red_qty'];	
                                    }
                                    $clone_temp = str_replace(".","9",$row['clone']);
                                    ?>
                                    <select name="red_<? echo $clone_temp;?>" style="color: black;">
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

?>