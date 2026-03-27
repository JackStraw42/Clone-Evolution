<?
include 'top_php.php';

// --- NEW COMBINED CALCULATION LOGIC ---

// 1. Localize Inventory and Chests so we don't affect the real database
$local_inv = array();
$inv_res = mysql_query("SELECT * FROM inventory WHERE user = '$user'");
while ($ir = mysql_fetch_array($inv_res)) {
    $local_inv[$ir['clone']] = array(
        'gold_qty' => $ir['gold_qty'],
        'purple_qty' => $ir['purple_qty']
    );
}

$local_chests = array();
$chest_res = mysql_query("SELECT * FROM chests WHERE user = '$user'");
$chest_data = mysql_fetch_array($chest_res);
$c_cols = mysql_query('SHOW COLUMNS FROM `chests`');
while ($col = mysql_fetch_array($c_cols)) {
    if ($col['Field'] != 'user') {
        $local_chests[$col['Field']] = $chest_data[$col['Field']];
    }
}

$inventory_backup = $local_inv;
$chests_backup = $local_chests;

// Fetch User Chest Priorities
$user_priorities = array();
$prio_res = mysql_query("SELECT * FROM user_chest_priorities WHERE user = '$user'");
while ($p_row = mysql_fetch_array($prio_res)) {
    $user_priorities[$p_row['chest']] = json_decode($p_row['priority_data'], true);
}

// Map Purple Clones to Available Chests
$purple_to_chest_map = array();
$chest_map_result = mysql_query("SELECT clone, chest FROM gold WHERE chest IS NOT NULL");
while ($row = mysql_fetch_array($chest_map_result)) {
    $purple_name = $row['clone'];
    $purple_to_chest_map[$purple_name] = array_map('trim', explode(";", $row['chest']));
}

// 2. Track combined requirements
$combined_missing = array();
$combined_chests_used = array();

// STEP 1: Aggregate ALL purple needs from BOTH red clone goals AND awakening goals WITHOUT consuming resources yet
$all_gold_needs = array(); // Track which gold clones are needed

// 1A. Process red clone goals - identify gold needs
foreach ($goals as $goal_clone) {
    $goal_res = mysql_query("SELECT * FROM red WHERE clone = '$goal_clone'");
    $goal_row = mysql_fetch_array($goal_res);

    if ($goal_row) {
        for ($i = 1; $i <= 3; $i++) {
            $ing = $goal_row['ing' . $i];
            if (!isset($all_gold_needs[$ing])) {
                $all_gold_needs[$ing] = 0;
            }
            $all_gold_needs[$ing]++;
        }
    }
}

// 1B. Process awakening goals - identify gold needs
$awaken_goals_query = "SELECT * FROM red_inventory WHERE user = '$user' AND is_goal = 1 AND awaken < 5";
$awaken_goals_res = mysql_query($awaken_goals_query);

while ($inv_row = mysql_fetch_array($awaken_goals_res)) {
    $clone_name = $inv_row['clone'];
    $current_lv = (int)$inv_row['awaken'];
    
    $lookup_name = ($clone_name == "Aisha") ? "Eva" : $clone_name;
    $recipe_res = mysql_query("SELECT * FROM red WHERE clone = '$lookup_name'");
    $recipe = mysql_fetch_array($recipe_res);

    if ($recipe) {
        switch ($current_lv) {
            case 0: 
                $n1 = $recipe['ing2']; 
                $n2 = $recipe['ing3']; 
                break;
            case 1:
            case 3: 
                $n1 = $recipe['ing1']; 
                $n2 = $recipe['ing3']; 
                break;
            case 2:
            case 4: 
                $n1 = $recipe['ing1']; 
                $n2 = $recipe['ing2']; 
                break;
        }

        if (!empty($inv_row['fodder'])) {
            $n3 = $inv_row['fodder'];
        } else {
            if ($recipe['institute'] == "Fire" || $recipe['institute'] == "Lightning" || $recipe['institute'] == "Chaos" || $clone_name == "Ganesha" || $clone_name == "Eva") {
                $n3 = "Bathory";
            } else {
                $n3 = "Merlin";
            }
        }

        foreach (array($n1, $n2, $n3) as $ing) {
            if (!isset($all_gold_needs[$ing])) {
                $all_gold_needs[$ing] = 0;
            }
            $all_gold_needs[$ing]++;
        }
    }
}

// STEP 2: Convert gold needs to purple needs
$all_purple_needs = array();

foreach ($all_gold_needs as $gold_clone => $qty_needed) {
    for ($g = 0; $g < $qty_needed; $g++) {
        // Check if we have this gold in inventory
        if ($local_inv[$gold_clone]['gold_qty'] > 0) {
            $local_inv[$gold_clone]['gold_qty']--;
        } else {
            // Need to craft - get purple ingredients
            if ($gold_clone == "Santa Claus") {
                if (!isset($all_purple_needs["Santa Claus"])) {
                    $all_purple_needs["Santa Claus"] = 0;
                }
                $all_purple_needs["Santa Claus"]++;
            } else {
                $g_res = mysql_query("SELECT * FROM gold WHERE clone = '$gold_clone'");
                $g_row = mysql_fetch_array($g_res);
                if ($g_row) {
                    for ($j = 1; $j <= 5; $j++) {
                        $p_ing = $g_row['ing' . $j];
                        if ($p_ing) {
                            if (!isset($all_purple_needs[$p_ing])) {
                                $all_purple_needs[$p_ing] = 0;
                            }
                            $all_purple_needs[$p_ing]++;
                        }
                    }
                }
            }
        }
    }
}

// STEP 3: Sort ALL purple needs by priority
uksort($all_purple_needs, function($a, $b) use ($user_priorities, $purple_to_chest_map) {
    $prio_a = 999;
    $prio_b = 999;
    
    $chests_a = isset($purple_to_chest_map[$a]) ? $purple_to_chest_map[$a] : array();
    foreach ($chests_a as $c) {
        $c = trim($c);
        $val = (isset($user_priorities[$c]) && isset($user_priorities[$c][$a])) ? (int)$user_priorities[$c][$a] : 999;
        if ($val < $prio_a) {
            $prio_a = $val;
        }
    }
    
    $chests_b = isset($purple_to_chest_map[$b]) ? $purple_to_chest_map[$b] : array();
    foreach ($chests_b as $c) {
        $c = trim($c);
        $val = (isset($user_priorities[$c]) && isset($user_priorities[$c][$b])) ? (int)$user_priorities[$c][$b] : 999;
        if ($val < $prio_b) {
            $prio_b = $val;
        }
    }
    return $prio_a - $prio_b;
});

// STEP 4: Allocate resources in priority order
foreach ($all_purple_needs as $p_name => $qty_needed) {
    // First, use existing purple inventory
    if (isset($local_inv[$p_name]) && $local_inv[$p_name]['purple_qty'] >= $qty_needed) {
        $local_inv[$p_name]['purple_qty'] -= $qty_needed;
        $qty_needed = 0;
    } else {
        if (isset($local_inv[$p_name])) {
            $qty_needed -= $local_inv[$p_name]['purple_qty'];
            $local_inv[$p_name]['purple_qty'] = 0;
        }
    }

    // Second, if still needed, try chests (respecting priorities)
    if ($qty_needed > 0 && isset($purple_to_chest_map[$p_name])) {
        $available_chests = $purple_to_chest_map[$p_name];
        
        foreach ($available_chests as $c_type) {
            $c_type = trim($c_type);
            
            // Check priority: Skip if blocked (priority = 0)
            $prio = (isset($user_priorities[$c_type]) && isset($user_priorities[$c_type][$p_name])) ? (int)$user_priorities[$c_type][$p_name] : 1;
            if ($prio === 0) {
                continue;
            }
            
            while ($qty_needed > 0 && isset($local_chests[$c_type]) && $local_chests[$c_type] > 0) {
                $local_chests[$c_type]--;
                $qty_needed--;
                if (!isset($combined_chests_used[$c_type][$p_name])) {
                    $combined_chests_used[$c_type][$p_name] = 0;
                }
                $combined_chests_used[$c_type][$p_name]++;
            }
        }
    }

    // Third, if still needed, it's officially missing
    if ($qty_needed > 0) {
        if (!isset($combined_missing[$p_name])) {
            $combined_missing[$p_name] = 0;
        }
        $combined_missing[$p_name] += $qty_needed;
    }
}

// Group the chest data for the new table format
$grouped_chests = array();
foreach ($combined_chests_used as $chest_type => $clones) {
    $total_qty = 0;
    foreach ($clones as $c_qty) {
        $total_qty += $c_qty;
    }
    $grouped_chests[$chest_type]['total'] = $total_qty;
    $grouped_chests[$chest_type]['details'] = $clones;
}


function get_awakening_status_with_priorities($needs, $temp_inv, $temp_chests, $priorities, $purple_to_chest_map) {
    // Get gold-to-purple mapping
    $gold_to_purple_map = array();
    
    foreach ($needs as $gold_clone) {
        $gold_to_purple_map[$gold_clone] = array();
        $g_res = mysql_query("SELECT * FROM gold WHERE clone = '".mysql_real_escape_string($gold_clone)."'");
        $g_row = mysql_fetch_array($g_res);
        
        if ($g_row) {
            for ($i = 1; $i <= 5; $i++) {
                $purple_name = $g_row['ing' . $i];
                if ($purple_name) {
                    if (!isset($gold_to_purple_map[$gold_clone][$purple_name])) {
                        $gold_to_purple_map[$gold_clone][$purple_name] = 0;
                    }
                    $gold_to_purple_map[$gold_clone][$purple_name]++;
                }
            }
        }
    }
    
    // PASS 1: Use inventory only, process clones in order
    $needs_after_inventory = array();
    
    foreach ($needs as $gold_clone) {
        // Check if gold is owned
        if ($temp_inv[$gold_clone]['gold_qty'] > 0) {
            $temp_inv[$gold_clone]['gold_qty']--;
            continue; // Skip to next gold clone
        }
        
        $needs_after_inventory[$gold_clone] = array();
        
        foreach ($gold_to_purple_map[$gold_clone] as $purple_clone => $qty_needed) {
            // Use inventory first
            if (isset($temp_inv[$purple_clone]) && $temp_inv[$purple_clone]['purple_qty'] > 0) {
                $qty_from_inventory = min($qty_needed, $temp_inv[$purple_clone]['purple_qty']);
                $temp_inv[$purple_clone]['purple_qty'] -= $qty_from_inventory;
                $qty_needed -= $qty_from_inventory;
            }
            
            // Track what's still needed after inventory
            if ($qty_needed > 0) {
                $needs_after_inventory[$gold_clone][$purple_clone] = $qty_needed;
            }
        }
    }
    
    // Aggregate remaining needs across all gold clones
    $all_remaining_purples = array();
    foreach ($needs_after_inventory as $gold_clone => $purples) {
        foreach ($purples as $purple_clone => $qty) {
            if (!isset($all_remaining_purples[$purple_clone])) {
                $all_remaining_purples[$purple_clone] = 0;
            }
            $all_remaining_purples[$purple_clone] += $qty;
        }
    }
    
    // Sort remaining purples by priority
    uksort($all_remaining_purples, function($a, $b) use ($priorities, $purple_to_chest_map) {
        $prio_a = 999;
        $prio_b = 999;
        
        $chests_a = isset($purple_to_chest_map[$a]) ? $purple_to_chest_map[$a] : array();
        foreach ($chests_a as $c) {
            $c = trim($c);
            $val = (isset($priorities[$c]) && isset($priorities[$c][$a])) ? (int)$priorities[$c][$a] : 999;
            if ($val < $prio_a) {
                $prio_a = $val;
            }
        }
        
        $chests_b = isset($purple_to_chest_map[$b]) ? $purple_to_chest_map[$b] : array();
        foreach ($chests_b as $c) {
            $c = trim($c);
            $val = (isset($priorities[$c]) && isset($priorities[$c][$b])) ? (int)$priorities[$c][$b] : 999;
            if ($val < $prio_b) {
                $prio_b = $val;
            }
        }
        return $prio_a - $prio_b;
    });
    
    // PASS 2: Allocate chests to remaining needs (by priority)
    $chest_allocations = array();
    
    foreach ($all_remaining_purples as $purple_clone => $qty_needed) {
        if ($qty_needed <= 0) continue;
        
        // Try to use chests for this purple
        if (isset($purple_to_chest_map[$purple_clone])) {
            $available_chests = $purple_to_chest_map[$purple_clone];
            
            foreach ($available_chests as $chest_name) {
                $chest_name = trim($chest_name);
                
                // Check priority: Skip if blocked (priority = 0)
                $prio = (isset($priorities[$chest_name]) && isset($priorities[$chest_name][$purple_clone])) ? (int)$priorities[$chest_name][$purple_clone] : 1;
                if ($prio === 0) {
                    continue;
                }
                
                if (isset($temp_chests[$chest_name]) && $temp_chests[$chest_name] > 0) {
                    $qty_from_chest = min($qty_needed, $temp_chests[$chest_name]);
                    $temp_chests[$chest_name] -= $qty_from_chest;
                    
                    // Track chest usage
                    if (!isset($chest_allocations[$chest_name][$purple_clone])) {
                        $chest_allocations[$chest_name][$purple_clone] = 0;
                    }
                    $chest_allocations[$chest_name][$purple_clone] += $qty_from_chest;
                    
                    $qty_needed -= $qty_from_chest;
                    if ($qty_needed <= 0) {
                        break;
                    }
                }
            }
        }
    }
    
    // Build final report
    $missing = array();
    $chests_used = array();
    
    foreach ($needs_after_inventory as $gold_clone => $purples_needed) {
        foreach ($purples_needed as $purple_clone => $qty_needed) {
            // Check if chests were allocated for this purple
            $satisfied = false;
            foreach ($chest_allocations as $chest_name => $purples) {
                if (isset($purples[$purple_clone]) && $purples[$purple_clone] > 0) {
                    $qty_from_chest = min($qty_needed, $purples[$purple_clone]);
                    
                    if ($qty_from_chest > 0) {
                        // Record chest usage
                        $chests_used[] = array('chest' => $chest_name, 'clone' => $purple_clone);
                        
                        // Consume from allocation
                        $chest_allocations[$chest_name][$purple_clone] -= $qty_from_chest;
                        $qty_needed -= $qty_from_chest;
                        
                        if ($qty_needed <= 0) {
                            $satisfied = true;
                            break;
                        }
                    }
                }
            }
            
            // Any remaining need is missing
            if ($qty_needed > 0) {
                if (!isset($missing[$purple_clone])) {
                    $missing[$purple_clone] = 0;
                }
                $missing[$purple_clone] += $qty_needed;
            }
        }
    }
    
    return array('missing' => $missing, 'chests' => $chests_used);
}


function get_missing_for_goal($needs, &$local_inv, &$local_chests) {
    $missing = array();
    $chests_used = array();
    $req_purple = array();

    // Step A: Check Gold Requirements
    foreach ($needs as $ing) {
        if ($local_inv[$ing]['gold_qty'] > 0) {
            $local_inv[$ing]['gold_qty']--;
        } else {
            if ($ing == "Santa Claus") {
                if (!isset($missing["Santa Claus"])) {
                    $missing["Santa Claus"] = 0;
                }
                $missing["Santa Claus"]++;
            } else {
                $g_res = mysql_query("SELECT * FROM gold WHERE clone = '$ing'");
                $g_row = mysql_fetch_array($g_res);
                for ($j = 1; $j <= 5; $j++) {
                    $p_ing = $g_row['ing' . $j];
                    if ($p_ing) {
                        if (!isset($req_purple[$p_ing])) {
                            $req_purple[$p_ing] = 0;
                        }
                        $req_purple[$p_ing]++;
                    }
                }
            }
        }
    }

    // Step B: Spend Purple or use Chests
    foreach ($req_purple as $p_name => $qty_needed) {
        if ($local_inv[$p_name]['purple_qty'] >= $qty_needed) {

            $local_inv[$p_name]['purple_qty'] -= $qty_needed;
            $qty_needed = 0;
        } else {
            $qty_needed -= $local_inv[$p_name]['purple_qty'];
            $local_inv[$p_name]['purple_qty'] = 0;
        }

        if ($qty_needed > 0) {
            $chest_lookup = mysql_query("SELECT chest FROM gold WHERE clone = '$p_name'");
            $cl = mysql_fetch_array($chest_lookup);
            if ($cl['chest']) {
                $available_options = explode(";", $cl['chest']);
                foreach ($available_options as $c_type) {
                    while ($qty_needed > 0 && $local_chests[$c_type] > 0) {
                        $local_chests[$c_type]--;
                        $qty_needed--;
                        $chests_used[] = array('chest' => $c_type, 'clone' => $p_name);
                    }
                }
            }
        }

        if ($qty_needed > 0) {
            if (!isset($missing[$p_name])) {
                $missing[$p_name] = 0;
            }
            $missing[$p_name] += $qty_needed;
        }
    }

    return array('missing' => $missing, 'chests' => $chests_used);
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
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br />  	
                        <div class="sec-title text-center" style="padding-top: 30px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;">Goals</h2>
                            <h5>Note: This program assumes you have the neccessary fodder.</h5>
                        </div>
					</div>
                    <div class="row"> 
                        <?
						if (count($goals) == 0) {
							?>
                            <center><font color='red'>You have not set any goals.  Go to <a href='https://olfactoryhues.com/clone-evolution/settings.php'>SETTINGS</a> to set goals.</font></center>
                            <?
						} else {
							for ($i=0; $i<count($goals); $i++) {
								if ($i == 3) {
									?>
                                    </div>
                                    <div class="row"> 
									<?	
								}
								?>
								<div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
									<center>
									<table id="need1" class="summary">
										<tr><td colspan="3" style="background-color: red;"><center><b><? echo $goals[$i];?></b></center></td></tr>
										<?
										$result = mysql_query("SELECT * FROM red_missing WHERE user = '".$user."' AND clone = '".$goals[$i]."'");
										while ($row = mysql_fetch_array($result)) {
											$color_result = mysql_query("SELECT * FROM red WHERE clone = '".$row['clone']."'");
											$color_row = mysql_fetch_array($color_result);
											switch ($color_row['institute']) {
												case 'Lightning':
													$color = "#b4c6e7";
													break;
												case 'Fire':
													$color = "#f2aaaa";
													break;
												case 'Earth':
													$color = "#92d050";
													break;
												case 'Water':
													$color = "#00b0f0";
													break;
												case 'Chaos':
													$color = "#cc99e0";
													break;
												case 'Order':
													$color = "#ffff00";
													break;
											}
											?>
                                            <tr>
                                                <td style="background-color: <? echo $color;?>; border-right: none; width: 30px;"><center><img src='img/reds/<? echo $row['clone'];?>.jpg' height='40' style="padding-right: 5px;"></center></td>
                                                <td style="background-color: <? echo $color;?>; border-left: none; width: 150px;">
                                                	<?
													$font_shrink = "";
													if (strlen($row['clone']) >= 19) {
														$font_shrink = " font-size: 11px;";
													}
													?>
                                                    <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/red_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="text-decoration: none; color: black; <? echo $font_shrink;?>"><? echo $row['clone'];?></a>
                                                    <?
                                                    $total_chests = 0;
                                                    $previous_chest = "";
                                                    $output = "";
                                                    if ($row['chests_used']) {
                                                        $chests_used_database = array_filter(explode(";",$row['chests_used']));
                                                        foreach ($chests_used_database as $chest) {
                                                            $chest_details = explode(",",$chest);
                                                            $total_chests = $total_chests + $chest_details[2];
                                                            if ($chest_details[0] !== $previous_chest) {
                                                                $chest_name = chest_name($chest_details[0]);
                                                                $output = $output."<font color='yellow'>".$chest_name.":</font><br />";
                                                            }
                                                            $output = $output.$chest_details[2]."x ".$chest_details[1]."<br />";
                                                            $previous_chest = $chest_details[0];
                                                        }
                                                        ?>
                                                        <div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                                        <font style="font-size: 10px;">
                                                        <div class="tooltipp" style="margin-top: -10px; padding-top: -5px;;">
                                                        	<font color='red'><? echo $total_chests;?> chests used.</font>&nbsp;&nbsp;
                                                            <span class="tooltiptext">
                                                                <?
                                                                echo $output;
                                                                ?>
                                                            </span>
                                                        </div>
                                                        <?						
                                                    }
                                                    ?>
                                                </font>                                            
                                                </td>                                        	
                                                <td style="background-color:#CCC;">
                                                    <?                                                											
													if ($row['total_missing'] > 0) {
														$cnt = 1;
														$m1 = 'missing'.$cnt;
														$m2 = 'missing'.$cnt.'_qty';
														$miss_qty = $row[$m2];
														while ($miss_qty !== "0") {
															$font_shrink = "";		
															if (strlen($row[$m1]) >= 19) {
																$font_shrink = " font-size: 11px;";
															}														
															echo "<font style='".$font_shrink."'>".$row[$m1]." (x".$row[$m2].")</font><br />";
															$cnt++;
															$m1 = 'missing'.$cnt;
															$m2 = 'missing'.$cnt.'_qty';													
															$miss_qty = $row[$m2];
														}
													} else {
														echo "Can make!";
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
							}
						}
                        ?>
                    </div>

                	<div class="row">
                    <?
					// 1. Fetch all clones the user has flagged as an awakening goal
					$awaken_goals_query = "SELECT * FROM red_inventory WHERE user = '$user' AND is_goal = 1 AND awaken < 5";
					$awaken_goals_result = mysql_query($awaken_goals_query);
					
					while ($inv_row = mysql_fetch_array($awaken_goals_result)) {
						$clone_name = $inv_row['clone'];
						$current_lv = (int)$inv_row['awaken'];
						
						// The Twin Swap logic
						if ($clone_name == "Aisha") {
							$lookup_name = "Eva";
						} else {
							$lookup_name = $clone_name;
						}
						
						$recipe_res = mysql_query("SELECT * FROM red WHERE clone = '".mysql_real_escape_string($lookup_name)."'");
						$recipe = mysql_fetch_array($recipe_res);
						
						switch ($current_lv) {
							case 0:
								$need1 = $recipe['ing2'];
								$need2 = $recipe['ing3']; 
								break;
							case 1:
							case 3:
								$need1 = $recipe['ing1'];
								$need2 = $recipe['ing3']; 
								break;
							case 2:
							case 4:
								$need1 = $recipe['ing1'];
								$need2 = $recipe['ing2']; 
								break;
						}
												
						// Fodder Logic (Slot 3) 
						if (!empty($inv_row['fodder'])) {
							$need3 = $inv_row['fodder'];
						} else {
							if ($recipe['institute'] == "Fire" || $recipe['institute'] == "Lightning" || $recipe['institute'] == "Chaos" || $clone_name == "Ganesha" || $clone_name == "Eva") {
								$need3 = "Bathory";
							} else {
								$need3 = "Merlin";
							}
						}										
						
						$needs = array($need1, $need2, $need3);
						
						$temp_inv = $inventory_backup;
						$temp_chests = $chests_backup;
						
						$goal_data = get_awakening_status_with_priorities($needs, $temp_inv, $temp_chests, $user_priorities, $purple_to_chest_map);
						
						$missing_clones = $goal_data['missing'];
						$chests_consumed = $goal_data['chests'];
						
						switch ($recipe['institute']) {
							case 'Lightning': $color = "#b4c6e7"; break;
							case 'Fire':      $color = "#f2aaaa"; break;
							case 'Earth':     $color = "#92d050"; break;
							case 'Water':     $color = "#00b0f0"; break;
							case 'Chaos':     $color = "#cc99e0"; break;
							case 'Order':     $color = "#ffff00"; break;
							default:          $color = "#CCC";    break;
						}
						
						?>
						<div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
							<center>
							<table class="summary">
								<tr>
									<td colspan="3" style="background-color: red;">
										<center><b><? echo $clone_name; ?> Awakening #<? echo ($current_lv + 1); ?></b></center>
									</td>
								</tr>
								<tr>
									<td style="background-color: <? echo $color; ?>; border-right: none; width: 30px;">
										<center><img src='img/reds/<? echo $clone_name; ?>.jpg' height='40' style="padding-right: 5px;"></center>
									</td>
									
									<td style="background-color: <? echo $color; ?>; border-left: none; width: 150px;">
										<a style="text-decoration: none; color: black;"><? echo $clone_name; ?></a>
										
										<?
										if (!empty($chests_consumed)) {
											$total_chests = count($chests_consumed);
											$summary_chests = array();
											foreach ($chests_consumed as $c) {
												$summary_chests[$c['chest']][$c['clone']]++;
											}
											?>
											<div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
											<font style="font-size: 10px;">
												<div class="tooltipp" style="margin-top: -10px;">
													<font color='red'><? echo $total_chests; ?> chests used.</font>
													<span class="tooltiptext">
														<?
														foreach ($summary_chests as $type => $clones) {
															echo "<font color='yellow'>" . chest_name($type) . ":</font><br />";
															foreach ($clones as $c_name => $qty) {
																echo $qty . "x " . $c_name . "<br />";
															}
														}
														?>
													</span>
												</div>
											</font>
											<?
										}
										?>
									</td>                                        	
									
									<td style="background-color:#CCC;">
										<?                                                											
										if (empty($missing_clones)) {
											echo "Can make!";
										} else {
											foreach ($missing_clones as $m_name => $m_qty) {
												$font_shrink = (strlen($m_name) >= 19) ? " font-size: 11px;" : "";														
												echo "<font style='".$font_shrink."'>".$m_name." (x".$m_qty.")</font><br />";
											}
										}
										?>
									</td>
								</tr>
							</table>
							<br />
							</center>
						</div>
						<?
					}
                    ?>
                    </div>
                    
                    
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <h2 class="wow animated bounceInLeft" style="color: #fff; font-size: 19px;">Combined Goals Summary</h2>
                            <br />
                            <center>
                            <table class="summary" style="width: 365px; border-collapse: collapse; border: 1px solid #000;">
                                <? 
                                $has_combined_data = false;
                    
                                // SECTION 1: MISSING CLONES
                                if (!empty($combined_missing)) {
                                    $has_combined_data = true;
                                    $sort_order = array('Lightning' => 1, 'Fire' => 2, 'Earth' => 3, 'Water' => 4, 'Chaos' => 5, 'Order' => 6);
                                    $sorted_missing = array();
                    
                                    foreach ($combined_missing as $clone => $qty) {
                                        $res = mysql_query("SELECT institute FROM gold WHERE clone = '$clone'");
                                        $row = mysql_fetch_array($res);
                                        $inst = $row['institute'];
                                        $clean_inst = preg_replace('/[0-9]+/', '', $inst);
                                        $rank = isset($sort_order[$clean_inst]) ? $sort_order[$clean_inst] : 99;
                    
                                        $sorted_missing[] = array(
                                            'clone' => $clone,
                                            'qty' => $qty,
                                            'inst' => $inst,
                                            'rank' => $rank
                                        );
                                    }
                    
                                    usort($sorted_missing, function($a, $b) {
                                        return $a['rank'] - $b['rank'];
                                    });
                                    ?>
                                    <tr style="background-color: red;">
                                        <td colspan="2" style="text-align: left; color: #000; padding: 5px; padding-left: 10px; border: 1px solid #000;"><b>Missing Clones:</b></th>
                                        <td style="text-align: center; color: #000; padding: 5px; border: 1px solid #000;"><b>Qty:</b></th>
                                    </tr>
                                    <?
                                    foreach ($sorted_missing as $item) {
                                        $clone = $item['clone'];
                                        $qty = $item['qty'];
                                        $inst = $item['inst'];
                    
                                        if (strpos($inst, 'Lightning') !== false) { $color = "#b4c6e7"; }
                                        elseif (strpos($inst, 'Fire') !== false) { $color = "#f2aaaa"; }
                                        elseif (strpos($inst, 'Earth') !== false) { $color = "#92d050"; }
                                        elseif (strpos($inst, 'Water') !== false) { $color = "#00b0f0"; }
                                        elseif (strpos($inst, 'Chaos') !== false) { $color = "#cc99e0"; }
                                        elseif (strpos($inst, 'Order') !== false) { $color = "#ffff00"; }
                                        else { $color = "#EEE"; }

                                        // SPECIAL CASE: Switch directory for Santa Claus images
                                        $img_dir = ($clone == "Santa Claus") ? "golds" : "purples";
                                        ?>
                                        <tr>
                                            <td style="background-color: <? echo $color; ?>; border: 1px solid #000; border-right: none; width: 40px; text-align: center; padding: 5px;">
                                                <img src='img/<? echo $img_dir; ?>/<? echo $clone; ?>.jpg' height='30' style="vertical-align: middle;">
                                            </td>
                                            <td style="background-color: <? echo $color; ?>; border: 1px solid #000; border-left: none; text-align: left; white-space: normal; padding: 5px; line-height: 1.2;">
                                                <? echo $clone; ?>
                                            </td>
                                            <td style="background-color: <? echo $color; ?>; border: 1px solid #000; text-align: center; padding: 5px; font-weight: bold;">
                                                x<? echo $qty; ?>
                                            </td>
                                        </tr>
                                        <?
                                    }
                                }
                    
                                // SECTION 2: CHESTS USED
                                if (!empty($grouped_chests)) {
                                    $has_combined_data = true;
                                    ?>
                                    <tr style="background-color: red;">
                                        <td colspan="2" style="text-align: left; color: #000; padding: 5px; padding-left: 10px; border: 1px solid #000;"><b>Chests Used:</b></th>
                                        <td style="text-align: left; color: #000; padding: 5px; padding-left: 10px; border: 1px solid #000; white-space: nowrap;"><b>Used For:</b></th>
                                    </tr>
                                    <?
                                    foreach ($grouped_chests as $type => $data) {
                                        reset($data['details']);
                                        $first_clone = key($data['details']);
                                        
                                        $c_color_res = mysql_query("SELECT institute FROM gold WHERE clone = '$first_clone'");
                                        $c_color_row = mysql_fetch_array($c_color_res);
                                        $c_inst = $c_color_row['institute'];
                    
                                        if (strpos($c_inst, 'Lightning') !== false) { $row_color = "#b4c6e7"; }
                                        elseif (strpos($c_inst, 'Fire') !== false) { $row_color = "#f2aaaa"; }
                                        elseif (strpos($c_inst, 'Earth') !== false) { $row_color = "#92d050"; }
                                        elseif (strpos($c_inst, 'Water') !== false) { $row_color = "#00b0f0"; }
                                        elseif (strpos($c_inst, 'Chaos') !== false) { $row_color = "#cc99e0"; }
                                        elseif (strpos($c_inst, 'Order') !== false) { $row_color = "#ffff00"; }
                                        else { $row_color = "#ffff00"; }
                    
                                        ?>
                                        <tr>
                                            <td style="background-color: <? echo $row_color; ?>; border: 1px solid #000; border-right: none; vertical-align: middle; width: 40px; text-align: center; padding: 5px;">
                                                <img src="img/chests/<? echo $type; ?>.jpg" height="30" style="vertical-align: middle;">
                                            </td>
                                            <td style="background-color: <? echo $row_color; ?>; border: 1px solid #000; border-left: none; vertical-align: middle; text-align: left; white-space: normal; padding: 5px; line-height: 1.1;">
                                                <b style="font-size: 11px;"><? echo chest_name($type); ?> x<? echo $data['total']; ?></b>
                                            </td>
                                            <td style="background-color: <? echo $row_color; ?>; border: 1px solid #000; text-align: left; white-space: nowrap; vertical-align: middle; padding: 5px; line-height: 1.1;">
                                                <? 
                                                foreach ($data['details'] as $clone_name => $qty) {
                                                    ?>
                                                    <div style="font-size: 11px;">
                                                        • <? echo $clone_name . " x" . $qty; ?>
                                                    </div>
                                                    <?
                                                } 
                                                ?>
                                            </td>
                                        </tr>
                                        <?
                                    }
                                }
                    
                                if (!$has_combined_data) {
                                    ?>
                                    <tr>
                                        <td colspan="3" style="background-color: #92d050; text-align: center; padding: 10px; border: 1px solid #000;">
                                            <b>All goals covered!</b>
                                        </td>
                                    </tr>
                                    <?
                                } 
                                ?>
                            </table>
                            </center>
                            <br /><br />
                        </div>
                    </div>
                    
                    
                </div>
            </section>
		</main>	
        <? include 'footer.php';?>
    </body>
</html>