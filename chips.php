<?
include 'top_php.php';


$used_chips = array();
$unique_chests = array();
$alt_chests = array();
$result = mysql_query("SELECT * FROM `dual_chips`");
while ($row = mysql_fetch_array($result)){
	if (strpos($row['chest'],";")){
		$multi_chests = explode(";",$row['chest']);
		foreach ($multi_chests as $this_chest){
			if (!in_array($this_chest,$unique_chests)){
				$unique_chests[] = $this_chest;
			}
		}
	} else {
		if (!in_array($row['chest'],$unique_chests)){
			$unique_chests[] = $row['chest'];
		}
	}
}

$alt_result = mysql_query("SELECT * FROM `dual_chips`"); 
while ($alt_row = mysql_fetch_array($alt_result)){
  if (strpos($alt_row['chest'],";")){
	$multi_chests = explode(";",$alt_row['chest']);
	$alt_chests[$alt_row['name']] = $multi_chests[0];
  }
}
sort($unique_chests);


if ($_POST["chip_inventory_submit"] == "Update Inventory"){
	// chip chests:
	mysql_query("DELETE FROM `chip_chest_inventory` WHERE user='".$user."'");
	foreach($unique_chests as $this_chest){
		if ($this_chest == "0" or $this_chest == "Weekly Events"){ continue; }
		mysql_query("INSERT INTO `chip_chest_inventory` (`user`, `chest`, `qty`) VALUES ('".$user."', '".$this_chest."', '".$_POST[$this_chest."_chest_qty"]."')");
	}
	
	// Chips:
	mysql_query("DELETE FROM `chip_inventory` WHERE user='".$user."'");
	$result = mysql_query("SELECT * FROM `dual_chips`");
	while ($row = mysql_fetch_array($result)){
		mysql_query("INSERT INTO `chip_inventory` (`index`, `user`, `chip`, `gold`, `red`) VALUES (NULL, '".$user."', '".$row['name']."', '".$_POST[str_replace(' ', '_', $row['name']) . "_gold_qty"]."', '".$_POST[str_replace(' ', '_', $row['name']) . "_red_qty"]."')");	}
}


?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html lang="en" class="no-js"> <!--<![endif]-->
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
        <? include 'menu.php';?>	

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
		</style>
		<main class="site-content" role="main">
            <input type="hidden" id="changed_field" name="changed_field" value="">
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br /><br /><br /><br /><br />
                        <div class="sec-title text-center"  style="padding-top: 30px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;"><font color="yellow" <? if ($mode !== "dark"){ ?>style="-webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black;"<? }?>>Chips</font></h2>
                        	<h3 class="wow animated bounceInLeft">Chip Inventory</h3>
                        </div>
					</div>
                    <form name="chip_inventory" method="post">
                    <div class="row"> 
                        <center><input type="submit" name="chip_inventory_submit" value="Update Inventory"></center><br /><br />
                        <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                            <center>
								<?
								$col_cnt = 0;
								foreach ($unique_chests as $this_chest){
									?>
                                    <table style="background-color: #CF3; width: 350px;">
                                    <tr>
                                    	<td align="center" style="padding: 5px;">
                                        	<?
                                        	if ($this_chest == "0"){
												?><img src="img/chips/no_chest.png" width="50"><? 
											} else {
												?><img src="img/chips/<? echo $this_chest;?>.png" width="50"><?                                          	 	
											}
											?>
                                        	
                                        </td>
                                        <td style="padding: 5px;">
                                        	<b>
                                        	<?
											if ($this_chest == "0"){
                                        		echo "No Chest:";
											} elseif ($this_chest == "Weekly Events"){
												echo $this_chest . ":";
											} else {
                                        		echo $this_chest . " Chip Chest:";
                                            }
											?>
                                            </b>
                                        </td>
                                        <td align="right" style="padding: 5px;">
                                        	<?
											if ($this_chest  !== "0" and $this_chest  !=="Weekly Events"){
                                        		?><b>Qty:</b><?	
											}
											?>
                                        </td>
                                        <td align="center" style="padding: 5px;">
                                        	<?
											$user_result = mysql_query("SELECT * FROM `chip_chest_inventory` WHERE user = '".$user."' AND chest = '".$this_chest."'");
											$user_row = mysql_fetch_array($user_result);
											if ($this_chest  !== "0" and $this_chest  !=="Weekly Events"){
												?>
                                                <select name="<? echo $this_chest;?>_chest_qty">
                                                    <?
                                                    for ($i=0; $i < 21; $i++){
                                                        ?>
                                                        <option value="<? echo $i;?>" <? if ($user_row['qty'] == $i){ echo " SELECTED"; }?> ><? echo $i;?></option>
                                                        <?	
                                                    }
                                                    ?>
                                                </select>
                                                <?
											}
											?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">&nbsp;</td>
                                        <td align="center" style="padding-left: 5px; padding-right: 5px;"><b>Gold</b></td>
                                        <td align="center" style="padding-left: 5px; padding-right: 5px;"><b>Red</b></td>
                                    </tr>
                                    <?
									$ing_result = mysql_query ("SELECT * FROM `dual_chips` WHERE `chest` LIKE '%".$this_chest."%'");
									while ($ing_row = mysql_fetch_array($ing_result)){
										$display_qty = "on";
										if (in_array($ing_row['name'],$used_chips)){
											$display_qty = "off";
										} else {
											$used_chips[] = $ing_row['name'];
										}
										?>
										<tr>
											<td align="center" style="padding: 5px;"><img src="img/chips/<? echo htmlspecialchars($ing_row['name']);?>.png" width="50"></td>
											<td style="padding: 5px;">
												<b><? echo $ing_row['name'];?></b>
                                                <?
												if ($this_chest  == "Weekly Events"){
													if ($ing_row['name'] == "Resilience"){
														echo " (Tavern)";	
													} else {
														echo " (Bounty)";	
													}
												}
												?>
                                                <br />
												(<? echo $ing_row['attribute1'];?>/<? echo  $ing_row['attribute2'];?>)
											</td>

											<?
                                            if ($display_qty == "on"){
                                                ?>
                                                <td align="center" valign="top" style="padding: 5px;">
                                                    <select name="<? echo str_replace(' ', '_', $ing_row['name']) . '_gold_qty';?>">
                                                        <?
														$user_result = mysql_query("SELECT * FROM `chip_inventory` WHERE user = '".$user."' AND chip = '".$ing_row['name']."'");
														$user_row = mysql_fetch_array($user_result);
                                                        for ($i=0; $i < 21; $i++){
                                                            ?>
                                                            <option value="<? echo $i;?>" <? if ($user_row['gold'] == $i){ echo " SELECTED"; }?> ><? echo $i;?></option>
                                                            <?	
                                                        }
                                                        ?>
                                                    </select>
                                                </td>
                                                <?
                                            } else {								
                                                ?>
                                                <td align="center" valign="middle" colspan="2" style="padding: 5px;">
													<style>
                                                        .little_blue{
                                                            line-height: 12px;	
                                                        }
                                                    </style>
                                                    <span class="little_blue">
                                                    <center><font style="font-size: 12px; color: blue; font-weight:bold;">Change in<br \><? echo $alt_chests[$ing_row['name']];?> Chest.</font></center>
                                                    </span>
                                                </td>
                                                <?
                                            }
                                            ?>
											<td align="center" valign="top" style="padding: 5px;">
                                            	<?
												if ($display_qty == "on"){
													?>
                                                    <select name="<? echo str_replace(' ', '_', $ing_row['name']) . '_red_qty';?>">
                                                        <?
                                                        for ($i=0; $i < 21; $i++){
                                                            ?>
                                                            <option value="<? echo $i;?>" <? if ($user_row['red'] == $i){ echo " SELECTED"; }?> ><? echo $i;?></option>
                                                            <?	
                                                        }
                                                        ?>
                                                    </select>
                                                    <?
												}
												?>
												</select>
											</td>
										</tr>
										<?
                                	}
									$col_cnt++
									?>
                                    </table>
                                    <br />
                                    </center>
                                    </div>
                                    <?
									if ($col_cnt == 3){
										$col_cnt = 0;
										?>
                                        </div>
                                        <div class="row"> 
                                        <?
									}
									?>
                                    <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                                    <center>
                                    <?
								}
                                ?>
                            </center>                        
                        </div>
					</div>
                    <div class="row"> 
                    	<center><input type="submit" name="chip_inventory_submit" value="Update Inventory"></center><br /><br />
                    </div>
                    </form>
                    <div class="row"> 
                    	<center><h3 class="wow animated bounceInLeft">Quad Chips</h3><br /></center>
                    </div>
						<?
                        $result = mysql_query ("SELECT DISTINCT `set_name` FROM `quad_chips` ORDER BY age");
                        while ($row = mysql_fetch_array($result)){
							$set_result = mysql_query("SELECT * FROM `quad_chips` WHERE set_name = '".$row['set_name']."'");
							$set_row = mysql_fetch_array($set_result);
							$i = 0;
							?>
                            <div class="row"> 
                            	<center>
                                <h4 style="color: red;"><b><? echo $set_row['set_name'];?> Set</b></h4>
                                <table style="background-color: <? echo $set_row['bg_color'];?>; max-width: 89%;">
                                	<tr>
                                    	<td align="right" valign="top" style="padding: 5px;" nowrap><b>2x Equipped: </b></td>
										<td style="padding: 5px;"><? echo $set_row['set_bonus_2'];?></td>
                                    </tr>
                                    <tr>
                                    	<td align="right" valign="top" style="padding: 5px;" nowrap><b>3x Equipped: </b></td>
										<td style="padding: 5px;"><? echo $set_row['set_bonus_3'];?></td>
                                    </tr>
                                </table>
                                <br />
                                </center>
								<?
                                $chip_result = mysql_query("SELECT * FROM `quad_chips` WHERE set_name='".$row['set_name']."'");
                                while ($chip_row = mysql_fetch_array($chip_result)){
                                    $i++;								
									// First, fetch user inventory for the required chips
									$chip1 = $chip_row['required_chip1'];
									$chip2 = $chip_row['required_chip2'];
									
									$inv_query1 = mysql_query("SELECT gold, red FROM `chip_inventory` WHERE user='".$user."' AND chip='".$chip1."'");
									$inv_row1 = mysql_fetch_array($inv_query1);
									$gold1 = (int)$inv_row1['gold'];
									$red1 = (int)$inv_row1['red'];
									
									$inv_query2 = mysql_query("SELECT gold, red FROM `chip_inventory` WHERE user='".$user."' AND chip='".$chip2."'");
									$inv_row2 = mysql_fetch_array($inv_query2);
									$gold2 = (int)$inv_row2['gold'];
									$red2 = (int)$inv_row2['red'];
									
									// Calculate initial shortages
									$shortage1 = ($red1 >= 1) ? 0 : max(0, 3 - $gold1);
									$shortage2 = ($red2 >= 1) ? 0 : max(0, 3 - $gold2);
									
									// Fetch chest info for each chip
									$chest_query1 = mysql_query("SELECT chest FROM `dual_chips` WHERE name='".$chip1."'");
									$chest_row1 = mysql_fetch_array($chest_query1);
									$chests_str1 = $chest_row1['chest'];
									
									$chest_query2 = mysql_query("SELECT chest FROM `dual_chips` WHERE name='".$chip2."'");
									$chest_row2 = mysql_fetch_array($chest_query2);
									$chests_str2 = $chest_row2['chest'];
									
									// Determine usable chest types for each chip (filter out 0 and Weekly Events)
									$chest_types1 = array();
									foreach (explode(";", $chests_str1) as $ct) {
										if ($ct != "0" && $ct != "Weekly Events") {
											$chest_types1[] = $ct;
										}
									}
									$has_chest1 = !empty($chest_types1);
									// Override for Stone and Resilience
									if ($chip1 == "Stone" || $chip1 == "Resilience") {
										$has_chest1 = false;
										$chest_types1 = array();
									}
									
									$chest_types2 = array();
									foreach (explode(";", $chests_str2) as $ct) {
										if ($ct != "0" && $ct != "Weekly Events") {
											$chest_types2[] = $ct;
										}
									}
									$has_chest2 = !empty($chest_types2);
									// Override for Stone and Resilience
									if ($chip2 == "Stone" || $chip2 == "Resilience") {
										$has_chest2 = false;
										$chest_types2 = array();
									}
									
									// Fetch available chests (simulate a copy for this quad)
									$available = array();
									$chest_inv_result = mysql_query("SELECT chest, qty FROM `chip_chest_inventory` WHERE user='".$user."'");
									while ($chest_inv_row = mysql_fetch_array($chest_inv_result)) {
										$c = $chest_inv_row['chest'];
										if ($c != "0" && $c != "Weekly Events") {
											$available[$c] = (int)$chest_inv_row['qty'];
										}
									}
									
									// Process chip1 first
									$remaining_need1 = $shortage1;
									$used_for1 = array(); // chest => qty
									if ($shortage1 > 0 && $has_chest1) {
										$current_need = $shortage1;
										foreach ($chest_types1 as $ct) {
											if ($current_need <= 0) break;
											$use = min($current_need, $available[$ct]);
											if ($use > 0) {
												$used_for1[$ct] = $use;
												$available[$ct] -= $use;
											}
											$current_need -= $use;
										}
										$remaining_need1 = $current_need;
									} 
									
									// Then process chip2
									$remaining_need2 = $shortage2;
									$used_for2 = array(); // chest => qty
									if ($shortage2 > 0 && $has_chest2) {
										$current_need = $shortage2;
										foreach ($chest_types2 as $ct) {
											if ($current_need <= 0) break;
											$use = min($current_need, $available[$ct]);
											if ($use > 0) {
												$used_for2[$ct] = $use;
												$available[$ct] -= $use;
											}
											$current_need -= $use;
										}
										$remaining_need2 = $current_need;
									} 
									
									// Determine if can make the quad chip
									$can_make = ($remaining_need1 == 0 && $remaining_need2 == 0);
									
									// Now, compile used chests info
									$used_chests = array(); // chest => ['qty' => total, 'for' => array of chips]
									foreach (array_unique(array_merge(array_keys($used_for1), array_keys($used_for2))) as $ct) {
										$qty1 = isset($used_for1[$ct]) ? $used_for1[$ct] : 0;
										$qty2 = isset($used_for2[$ct]) ? $used_for2[$ct] : 0;
										$total_qty = $qty1 + $qty2;
										if ($total_qty > 0) {
											$for_chips = array();
											if ($qty1 > 0) $for_chips[] = $chip1;
											if ($qty2 > 0) $for_chips[] = $chip2;
											$used_chests[$ct] = array('qty' => $total_qty, 'for' => $for_chips);
										}
									}
									
									// At this point, you have:
									// - $can_make (bool)
									// - $remaining_need1 (int for chip1)
									// - $remaining_need2 (int for chip2)
									// - $used_chests (array as above)
									
									// if ($can_make) {
									//     echo "Can craft!";
									//     // add used if any
									// } else {
									//     // report needs
									//     // add used if any
									// }
									// For example, to report used: foreach $used_chests, echo $qty . " " . $ct . " for " . implode(" and/or ", $for)
									// For needs: if >0, echo $remaining_need1 . " " . $chip1

                                    ?>
                                    <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                                        <center>
                                        <table style="background-color: <? echo $set_row['bg_color'];?>; width: 350px;">
                                            <tr style="border: 1px solid #000;">
                                                <td align="center" style="padding: 5px;" colspan="2">
                                                	<br />
                                               		<img src="img/chips/<? echo $chip_row['name'];?>.png" width="50"><br />
                                                	<b><? echo $chip_row['name'];?></b><br \>
                                                    <?
                                                    echo $chip_row['attribute1'].", ".$chip_row['attribute2'].", ".$chip_row['attribute3'].", ".$chip_row['attribute4'];
													?>
                                                </td>
                                            </tr>
                                            <tr style="border: 1px solid #000;">
                                            	<td align="center" style="padding: 10px;" colspan="2">
                                                    <img src="img/chips/<? echo $chip_row['required_chip1'];?>_red.png" width="30"> <? echo $chip_row['required_chip1'];?> + 
                                                    <img src="img/chips/<? echo $chip_row['required_chip2'];?>_red.png" width="30"> <? echo $chip_row['required_chip2'];?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="right" valign="top" style="padding: 5px; width: 110px;">
                                                    <b>Progress:</b>
                                                </td>
                                                <td align="left" valign="top" style="padding: 5px;"> 
                                                    <?
                                                    $missing_total = $remaining_need1 + $remaining_need2;
                                                    if ($can_make) {
                                                        echo "Can craft!";
                                                    } else {
                                                        echo "Missing " . $missing_total . " chips.";
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?											
											if ($missing_total > 0){
												?>
                                                <tr>
                                                    <td align="right" valign="top" style="padding: 5px;"><b>Still Need: </b></td>
                                                    <td align="left" valign="top" style="padding: 5px;">
                                                        <?                                           
                                                        $needs = array();
                                                        if ($remaining_need1 > 0) {
                                                            $needs[] = $remaining_need1 . "x " . $chip1;
                                                        }
                                                        if ($remaining_need2 > 0) {
                                                            $needs[] = $remaining_need2 . "x " . $chip2;
                                                        }
                                                        foreach ($needs as $need){
                                                            echo $need."<br />";
                                                        }
                                                    ?>
                                                    </td>
                                                </tr>
                                            	<?
											}

											if (!empty($used_chests)) {
												?>
                                                <tr>
                                                    <td align="right" valign="top" style="padding: 5px;"><b>Chests used: </b></td>
                                                    <td align="left" valign="top" style="padding: 5px;">
                                                        <?
                                                        foreach ($used_chests as $chest_used => $info) {
                                                            echo $info['qty'] . "x " .$chest_used . " Chest: " . implode(" and/or ", $info['for']);
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?
											}
											?>
                                        </table>
                                        <br />
                                        </center>
                                    </div>
                                    <?
                                    if ($i == 3){
                                        $i = 0;
                                        ?>
                                        </div><div class="row">
                                        <?	
                                    }
                                }
                                ?>
                            </div>
                            <?
                        }
                        ?>
                    </div>
                </div>
        	</section>
		</main>	
        </form>
		<? include 'footer.php';?>
    </body>
</html>