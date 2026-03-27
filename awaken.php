<?
include 'top_php.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $changed = isset($_POST['changed_field']) ? $_POST['changed_field'] : null;

    if ($changed) {
        $value = isset($_POST[$changed]) ? $_POST[$changed] : null;
        
        if (strpos($changed, '_fodder') !== false) {
            // Handle fodder dropdown
            $clone = str_replace('_fodder', '', $changed);
            $clone = str_replace("_"," ",$clone);
            mysql_query("UPDATE red_inventory SET fodder = '".mysql_real_escape_string($value)."' WHERE user = '".mysql_real_escape_string($user)."' AND clone = '".mysql_real_escape_string($clone)."'");
        } elseif (strpos($changed, '_goal') !== false) {
            // NEW: Handle goal toggle
            $clone = str_replace('_goal', '', $changed);
            $clone = str_replace("_"," ",$clone);
            mysql_query("UPDATE red_inventory SET is_goal = '".mysql_real_escape_string($value)."' WHERE user = '".mysql_real_escape_string($user)."' AND clone = '".mysql_real_escape_string($clone)."'");
        } else {
            // Handle awaken dropdown
            $clone = str_replace("_"," ",$changed);
            mysql_query("UPDATE red_inventory SET awaken = '".mysql_real_escape_string($value)."' WHERE user = '".mysql_real_escape_string($user)."' AND clone = '".mysql_real_escape_string($clone)."'");
        }
    }
}


function get_initial_resources($clones_required) {
    global $user;
    
    // --- 1. Map Gold Clones to their Purple Ingredients & Identify unique purples ---
    $all_required_purples = array();
    $gold_to_purple_map = array(); 
    
    foreach ($clones_required as $gold_clone) {
        $gold_clone_safe = mysql_real_escape_string($gold_clone);
        $required_result = mysql_query("SELECT * FROM gold WHERE clone = '".$gold_clone_safe."'");
        $required_row = mysql_fetch_array($required_result);
        
        $gold_to_purple_map[$gold_clone] = array();
        
        for ($i = 1; $i <= 5; $i++) {
            $purple_name_col = "ing" . $i;
            if (!empty($required_row[$purple_name_col])) {
                $purple_name = $required_row[$purple_name_col];
                
                if (!isset($gold_to_purple_map[$gold_clone][$purple_name])) {
                    $gold_to_purple_map[$gold_clone][$purple_name] = 1;
                } else {
                    $gold_to_purple_map[$gold_clone][$purple_name] += 1;
                }
                
                $all_required_purples[$purple_name] = 1;
            }
        }
    }
    
    // --- 2. Initialize Working Purple Inventory ---
    $working_purple_inventory = array();
    
    if (!empty($all_required_purples)) {
        $purple_list = "'" . implode("','", array_keys($all_required_purples)) . "'";
        $inv_result = mysql_query("SELECT clone, purple_qty FROM inventory WHERE user='".mysql_real_escape_string($user)."' AND clone IN ($purple_list)");
        
        while ($inv_row = mysql_fetch_array($inv_result)) {
            $working_purple_inventory[$inv_row['clone']] = (int)$inv_row['purple_qty'];
        }
    }

    // --- 3. Initialize Working Chests Inventory ---
    $working_chests_avail = array();
    $chests_result = mysql_query("SELECT * FROM chests WHERE user = '".mysql_real_escape_string($user)."'");
    $user_chests = mysql_fetch_array($chests_result);
    
    $c_result = mysql_query('SHOW COLUMNS FROM `chests`'); 
    while ($array = mysql_fetch_array($c_result)) {
        if ($array['Field'] !== 'user') {
            $chest_name = $array['Field'];
            $working_chests_avail[$chest_name] = (int)$user_chests[$chest_name];
        }
    }
    
    // --- 4. Map Purple Clones to Available Chests (Purple_to_Chest_Map) ---
    $purple_to_chest_map = array();
    if (!empty($all_required_purples)) {
        $purple_list = "'" . implode("','", array_keys($all_required_purples)) . "'";
        $chest_map_result = mysql_query("SELECT clone, chest FROM gold WHERE clone IN ($purple_list) AND chest IS NOT NULL");
        
        while ($row = mysql_fetch_array($chest_map_result)) {
            $purple_name = $row['clone'];
            // Ensure trim() is applied to all elements after explode
            $purple_to_chest_map[$purple_name] = array_map('trim', explode(";", $row['chest'])); 
        }
    }
    
    // --- 5. Fetch User Chest Priorities ---
    $priorities = array();
    $prio_res = mysql_query("SELECT * FROM user_chest_priorities WHERE user = '".mysql_real_escape_string($user)."'");
    while ($p_row = mysql_fetch_array($prio_res)) {
        $priorities[$p_row['chest']] = json_decode($p_row['priority_data'], true);
    }
    
    return array(
        'gold_to_purple_map' => $gold_to_purple_map,
        'inventory' => $working_purple_inventory,
        'chests_avail' => $working_chests_avail,
        'purple_to_chest_map' => $purple_to_chest_map,
        'priorities' => $priorities
    );
}

function check_awakening_status($clones_required) {
    global $user;
    
    // --- 1. Initialize Resources and Requirements ---
    $resources = get_initial_resources($clones_required);

    $gold_to_purple_map    = $resources['gold_to_purple_map'];
    $working_purple_inventory = $resources['inventory'];
    $working_chests_avail  = $resources['chests_avail'];
    $purple_to_chest_map   = $resources['purple_to_chest_map'];
    $priorities            = $resources['priorities'];
    
    // --- 2. Check which gold clones are already owned ---
    $gold_owned = array();
    $gold_needed = array();
    
    foreach ($clones_required as $gold_clone) {
        $gold_clone_safe = mysql_real_escape_string($gold_clone);
        $inv_result = mysql_query("SELECT gold_qty FROM inventory WHERE user='".mysql_real_escape_string($user)."' AND clone='".$gold_clone_safe."'");
        $inv_row = mysql_fetch_array($inv_result);
        
        if ($inv_row['gold_qty'] > 0) {
            $gold_owned[$gold_clone] = true;
        } else {
            $gold_needed[] = $gold_clone;
        }
    }
    
    // --- 3. FIRST PASS: Use inventory only, process clones in order ---
    $needs_after_inventory = array(); // What each gold clone still needs after using inventory
    
    foreach ($clones_required as $gold_clone) {
        if (isset($gold_owned[$gold_clone])) {
            continue; // Skip owned clones
        }
        
        $needs_after_inventory[$gold_clone] = array();
        
        foreach ($gold_to_purple_map[$gold_clone] as $purple_clone => $qty_needed) {
            // Use inventory first
            if (isset($working_purple_inventory[$purple_clone]) && $working_purple_inventory[$purple_clone] > 0) {
                $qty_from_inventory = min($qty_needed, $working_purple_inventory[$purple_clone]);
                $working_purple_inventory[$purple_clone] -= $qty_from_inventory;
                $qty_needed -= $qty_from_inventory;
            }
            
            // Track what's still needed after inventory
            if ($qty_needed > 0) {
                $needs_after_inventory[$gold_clone][$purple_clone] = $qty_needed;
            }
        }
    }
    
    // --- 4. Aggregate remaining needs across all gold clones ---
    $all_remaining_purples = array();
    foreach ($needs_after_inventory as $gold_clone => $purples) {
        foreach ($purples as $purple_clone => $qty) {
            if (!isset($all_remaining_purples[$purple_clone])) {
                $all_remaining_purples[$purple_clone] = 0;
            }
            $all_remaining_purples[$purple_clone] += $qty;
        }
    }
    
    // --- 5. Sort remaining purples by priority ---
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
    
    // --- 6. SECOND PASS: Allocate chests to remaining needs (by priority) ---
    $chest_allocations = array(); // Which purples get satisfied by chests
    
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
                
                if (isset($working_chests_avail[$chest_name]) && $working_chests_avail[$chest_name] > 0) {
                    $qty_from_chest = min($qty_needed, $working_chests_avail[$chest_name]);
                    $working_chests_avail[$chest_name] -= $qty_from_chest;
                    
                    // Track chest usage for this purple
                    if (!isset($chest_allocations[$purple_clone])) {
                        $chest_allocations[$purple_clone] = array();
                    }
                    if (!isset($chest_allocations[$purple_clone][$chest_name])) {
                        $chest_allocations[$purple_clone][$chest_name] = 0;
                    }
                    $chest_allocations[$purple_clone][$chest_name] += $qty_from_chest;
                    
                    $qty_needed -= $qty_from_chest;
                    if ($qty_needed <= 0) {
                        break;
                    }
                }
            }
        }
    }
    
    // --- 7. Build final report for each gold clone ---
    $final_report = array();
    
    foreach ($clones_required as $gold_clone) {
        $clone_status = array(
            'status' => 'unknown',
            'chests_used' => array(),
            'missing' => array()
        );
        
        // Check if already owned
        if (isset($gold_owned[$gold_clone])) {
            $clone_status['status'] = 'have gold';
            $final_report[$gold_clone] = $clone_status;
            continue;
        }
        
        // Process needs for this clone
        $can_make = true;
        
        if (isset($needs_after_inventory[$gold_clone])) {
            foreach ($needs_after_inventory[$gold_clone] as $purple_clone => $qty_needed) {
                // Check if chests were allocated for this purple
                if (isset($chest_allocations[$purple_clone])) {
                    // Deduct what chests can provide
                    foreach ($chest_allocations[$purple_clone] as $chest_name => $chest_qty) {
                        $qty_from_chest = min($qty_needed, $chest_qty);
                        
                        if ($qty_from_chest > 0) {
                            // Assign chest usage to this clone
                            if (!isset($clone_status['chests_used'][$purple_clone])) {
                                $clone_status['chests_used'][$purple_clone] = array();
                            }
                            $clone_status['chests_used'][$purple_clone][$chest_name] = $qty_from_chest;
                            
                            // Consume from allocation
                            $chest_allocations[$purple_clone][$chest_name] -= $qty_from_chest;
                            $qty_needed -= $qty_from_chest;
                        }
                    }
                }
                
                // Any remaining need is missing
                if ($qty_needed > 0) {
                    $clone_status['missing'][$purple_clone] = $qty_needed;
                    $can_make = false;
                }
            }
        }
        
        // Set status
        if ($can_make) {
            $clone_status['status'] = 'can make';
            if (!empty($clone_status['chests_used'])) {
                $clone_status['status'] = 'can make (with chests)';
            }
        } else {
            $clone_status['status'] = 'need clones';
        }
        
        $final_report[$gold_clone] = $clone_status;
    }
    
    return $final_report;
}

$colors['Lightning'] = '#b4c6e7';
$colors['Fire'] = '#f2aaaa';
$colors['Earth'] = '#92d050';
$colors['Water'] = '#00b0f0';
$colors['Chaos'] = '#cc99e0';
$colors['Order'] = '#ffff00';

$inc = 0;
$i = 0;
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
            <form name="awaken_values" method="post">
            <input type="hidden" id="changed_field" name="changed_field" value="">
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br /><br /><br /><br /><br />
                        <div class="sec-title text-center"  style="padding-top: 30px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;"><font color="yellow" <? if ($mode !== "dark"){ ?>style="-webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black;"<? }?>>Awaken Clones</font></h2>
                        </div>
					</div>
                    <div class="row"> 
                    <?
                    $i = 0;
					$result = mysql_query("SELECT * FROM red WHERE awaken = 'y' ORDER BY clone ASC");				
					while($row = mysql_fetch_array($result)){
						$bg_color = $colors[$row['institute']];
                        $clone_temp = str_replace(".","9",$row['clone']);	
						$clone_temp = str_replace(" ","_",$clone_temp);					
                        $inv_result = mysql_query("SELECT * FROM red_inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'");
                        $inv_row = mysql_fetch_array($inv_result);
                        $awaken_no = $inv_row['awaken'];				
                        
                        if ($inv_row[red_qty] > 0){		              
                            if ($row['clone'] == "Aisha"){
                                $tmp_clone = "Eva";
                            } else {
                                $tmp_clone = $row['clone'];
                            }
                            $tmp_result = mysql_query("SELECT * FROM red WHERE clone = '".$tmp_clone."'");
                            $tmp_row = mysql_fetch_array($tmp_result);
                            
                            switch ($awaken_no) {
                                case 0:
                                    $clone_1 = $tmp_row['ing2'];
                                    $clone_2 = $tmp_row['ing3']; 
                                    break;
                                case 1:
                                    $clone_1 = $tmp_row['ing1'];
                                    $clone_2 = $tmp_row['ing3']; 
                                    break;
                                case 2:
                                    $clone_1 = $tmp_row['ing1'];
                                    $clone_2 = $tmp_row['ing2']; 
                                    break;
                                case 3:
                                    $clone_1 = $tmp_row['ing1'];
                                    $clone_2 = $tmp_row['ing3']; 
                                    break;
                                case 4:
                                    $clone_1 = $tmp_row['ing1'];
                                    $clone_2 = $tmp_row['ing2']; 
                                    break;
                                case 5:
                                    // Maxed
                                    break;
                            }

                            if ($row['institute'] == "Fire" or $row['institute'] == "Lightning" or $row['institute'] == "Chaos" or $row['clone'] == "Ganesha"){
                                $fodder_type = "Chaos";
                                $clone_3 = "Bathory";
                            } else {
                                $fodder_type = "Order";
                                $clone_3 = "Merlin";
                            }
                                            
                            if ($inv_row['fodder'] !== ""){
                                $clone_3 = $inv_row['fodder'];
                            }
							
                            $clones_required = array($clone_1, $clone_2, $clone_3);
                            $results = check_awakening_status($clones_required);
							$golds_owned_count = 0;
                            $total_missing_quantity = 0;
                            foreach ($clones_required as $clone_name) {
                                if (!empty($results[$clone_name]['missing'])) {
                                    $total_missing_quantity += array_sum($results[$clone_name]['missing']);
                                }
                            }
						
							?>
							
								<div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
									<center>
                                    <h3 class="wow animated bounceInLeft"><? echo $row['clone'];?></h3>
									<table class="summary">
										<tr>
											<td colspan="3" style="background-color: <? echo $bg_color;?>">
												<center>
                                                <br />
												<img src="img/reds/<? echo $row['clone'];?>.jpg" height="80" style="padding-bottom: 5px;"><br />
												<div style="width: 120px; height: 51px; background-image: url(img/awaken.png); margin-top: -20px; z-index: 99; position: relative;">
													<select name="<? echo $clone_temp;?>" style="background-color: transparent; font-weight: bold; color: #e72b81; margin-top: 13px; border: 0;" onchange="document.getElementById('changed_field').value=this.name; this.form.submit();">                                                                                                       <?
													for ($c=0; $c<6; $c++){
														?>
														<option value="<? echo $c;?>" <? if ($c == $awaken_no){ ?> selected <? }?>><? echo $c;?></option>
														<?	
													}
													?>
													</select>                                                 
												</div>
												<?
                                                // 1. Prepare the variables (ensure $inv_row has your red_inventory data)
                                                $is_goal = (int)$inv_row['is_goal']; 
                                                $new_goal_state = ($is_goal === 1) ? 0 : 1;
                                                $btn_text = ($is_goal === 1) ? "Remove Goal" : "Set as Goal";
                                                // Using a simple style toggle; feel free to swap with your CSS classes
                                                $btn_style = ($is_goal === 1) ? "background-color: #ffd700; color: #000; font-weight: bold;" : "background-color: #444; color: #fff;";
                                                
                                                $goal_key = $clone_temp . "_goal";
                                                
                                                // 2. The Button HTML
                                                echo '<div style="margin-top: 10px;">';
                                                echo '  <button type="button" style="padding: 5px 10px; cursor: pointer; border-radius: 4px; border: none; '.$btn_style.'" 
                                                            onclick="document.getElementById(\'changed_field\').value=\''.$goal_key.'\'; 
                                                                     document.getElementById(\''.$goal_key.'_val\').value=\''.$new_goal_state.'\'; 
                                                                     this.closest(\'form\').submit();">
                                                            '.$btn_text.'
                                                        </button>';
                                                echo '</div>';
                                                
                                                // 3. The Hidden Input
                                                echo '<input type="hidden" id="'.$goal_key.'_val" name="'.$goal_key.'" value="'.$is_goal.'">';
                                                ?>
												</center>                                        
											</td>
										</tr>
                                        <?
										if ($awaken_no < 5){
											?>
                                            <tr>
                                                <?
                                                $color_result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone_1."'");
                                                $color_row = mysql_fetch_array($color_result);
                                                $color = $colors[$color_row['institute']];
												$font_shrink = "";
												if (strlen($clone_1) >= 19) {
													$font_shrink = " font-size: 11px;";
												}
                                                ?>
                                                <td style="background-color: <? echo $color;?>; border-right: none; width: 45px;">
                                                    <center>
                                                        <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $clone_1;?>" data-fancybox-type="iframe" title="<? echo $clone_1;?>" style="text-decoration: none; color: black;">
                                                            <img src="img/golds/<? echo $clone_1;?>.jpg" height="40" style="padding-bottom: 5px;"> 
                                                        </a>    
                                                    </center>                                            
                                                </td>
                                                <td style="background-color: <? echo $color;?>; border-left: none;" align="left">
                                                	<font style=" <? echo $font_shrink;?>">
                                                    	<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $clone_1;?>" data-fancybox-type="iframe" title="<? echo $clone_1;?>" style="text-decoration: none; color: black;"><? echo $clone_1;?></a>
                                                    </font>
													<?
                                                    // CHECK: If the current clone used chests
                                                    if (!empty($results[$clone_1]['chests_used'])) { // Use $clone_1, $clone_2, or $clone_3
                                                        
                                                        $chests_by_name = array(); 
                                                        $total_chests_used = 0;
                                                        
                                                        // --- 1. REFORMAT DATA: Group usage by Chest Name ---
                                                        // The data structure changed, access is now cleaner
                                                        foreach ($results[$clone_1]['chests_used'] as $purple_clone => $chests) {
                                                            foreach ($chests as $chest_name => $qty) {
                                                                $total_chests_used += $qty;
                                                                
                                                                if (!isset($chests_by_name[$chest_name])) {
                                                                    $chests_by_name[$chest_name] = array();
                                                                }
                                                                $chests_by_name[$chest_name][$purple_clone] = $qty;
                                                            }
                                                        }
                                                    
                                                        // --- 2. GENERATE OUTPUT STRING (Using <br /> for line counting compatibility) ---
                                                        $detail_text = '';
                                                        foreach ($chests_by_name as $chest_name => $purples_used) {
                                                            $this_chest_name = "{$chest_name}";
															$this_chest_name = chest_name($this_chest_name);
                                                            $detail_text .= $this_chest_name.": <br />";
                                                            foreach ($purples_used as $purple_clone => $qty) {
                                                                 $detail_text .= "{$qty}x {$purple_clone}<br />";
                                                            }
                                                        }
                                                        ?>
                                                        <style>
                                                            /* Tooltip container */
                                                            .tooltipp<? echo $inc;?> {
                                                              position: relative;
                                                              display: inline-block;
                                                              z-index: 9999;
                                                            }
                                                            
                                                            /* Tooltip text */
                                                            .tooltipp<? echo $inc;?> .tooltiptext<? echo $inc;?> {
                                                              visibility: hidden;
                                                              background-color: black;
                                                              color: #fff;
                                                              text-align: left;
                                                              padding: 10px;
                                                              border-radius: 6px;
                                                              /* Position the tooltip text - see examples below! */
                                                              position: absolute;
                                                            }
                                                            
                                                            /* Show the tooltip text when you mouse over the tooltip container */
                                                            .tooltipp<? echo $inc;?>:hover .tooltiptext<? echo $inc;?> {
                                                              visibility: visible;
                                                              margin-top: <? echo $margin;?>px;
                                                              /* Lines: <? echo $detail_text;?> */
                                                            }
                                                        
                                                        </style>
                                                        <div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                                        <font style="font-size: 10px;">
                                                        <div class="tooltipp<? echo $inc;?>"><font color='red'><? echo $total_chests_used;?> chests used.</font>&nbsp;&nbsp;
                                                            <span class="tooltiptext<? echo $inc;?>">
                                                                <?
                                                                echo $detail_text;
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
                                                    $status = $results[$clone_1]['status']; 
                                                    
                                                    if ($status == 'have gold') {
														$golds_owned_count++
                                                        ?>
                                                        <img src="img/check.png" height="24" title="Have Gold"> Have Gold 
                                                        <?
                                                    } elseif (strpos($status, 'can make') !== false) {
                                                        ?>
                                                        <center><a class="fancybox btn btn-primary" href="https://olfactoryhues.com/clone-evolution/craft_gold_awaken.php?clone=<? echo $clone_1;?>" data-fancybox-type="iframe" title="<? echo $clone_1;?>">Make It!</a></center>
                                                        <?
                                                    } else { // 'need clones'
                                                        if (!empty($results[$clone_1]['missing'])) { 
                                                            foreach ($results[$clone_1]['missing'] as $purple_clone => $qty) {
																if (strlen($purple_clone) >= 19){
																	echo "<font style='font-size: 11px;'>";	
																}
                                                                echo "{$qty}x {$purple_clone}<br />";
																if (strlen($purple_clone) >= 19){
																	echo "</font>";	
																}
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <?
                                                $color_result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone_2."'");
                                                $color_row = mysql_fetch_array($color_result);
                                                $color = $colors[$color_row['institute']];
												$font_shrink = "";
												if (strlen($clone_2) >= 19) {
													$font_shrink = " font-size: 11px;";
												}
                                                ?>
                                                <td style="background-color: <? echo $color;?>; border-right: none; width: 45px;">
                                                    <center>
                                                        <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $clone_2;?>" data-fancybox-type="iframe" title="<? echo $clone_2;?>" style="text-decoration: none; color: black;">
                                                            <img src="img/golds/<? echo $clone_2;?>.jpg" height="40" style="padding-bottom: 5px;"> 
                                                        </a>    
                                                    </center>                                            
                                                </td>
                                                <td style="background-color: <? echo $color;?>; border-left: none;" align="left">
                                                	<font style=" <? echo $font_shrink;?>">
                                                    	<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $clone_2;?>" data-fancybox-type="iframe" title="<? echo $clone_2;?>" style="text-decoration: none; color: black;"><? echo $clone_2;?></a>
                                                    </font>
													<?
                                                    if (!empty($results[$clone_2]['chests_used'])) { 
                                                        
                                                        $chests_by_name = array(); 
                                                        $total_chests_used = 0;
                                                        
                                                        foreach ($results[$clone_2]['chests_used'] as $purple_clone => $chests) {
                                                            foreach ($chests as $chest_name => $qty) {
                                                                $total_chests_used += $qty;
                                                                
                                                                if (!isset($chests_by_name[$chest_name])) {
                                                                    $chests_by_name[$chest_name] = array();
                                                                }
                                                                $chests_by_name[$chest_name][$purple_clone] = $qty;
                                                            }
                                                        }
                                                    
                                                        $detail_text = '';
                                                        foreach ($chests_by_name as $chest_name => $purples_used) {
                                                            $this_chest_name = "{$chest_name}";
															$this_chest_name = chest_name($this_chest_name);
                                                            $detail_text .= $this_chest_name.": <br />";
                                                            foreach ($purples_used as $purple_clone => $qty) {
                                                                 $detail_text .= "{$qty}x {$purple_clone}<br />";
                                                            }
                                                        }
                                                        ?>
                                                        <style>
                                                            /* Tooltip container */
                                                            .tooltipp<? echo $inc;?> {
                                                              position: relative;
                                                              display: inline-block;
                                                              z-index: 9999;
                                                            }
                                                            
                                                            /* Tooltip text */
                                                            .tooltipp<? echo $inc;?> .tooltiptext<? echo $inc;?> {
                                                              visibility: hidden;
                                                              background-color: black;
                                                              color: #fff;
                                                              text-align: left;
                                                              padding: 10px;
                                                              border-radius: 6px;
                                                              /* Position the tooltip text - see examples below! */
                                                              position: absolute;
                                                              z-index: 9999;
                                                            }
                                                            
                                                            /* Show the tooltip text when you mouse over the tooltip container */
                                                            .tooltipp<? echo $inc;?>:hover .tooltiptext<? echo $inc;?> {
                                                              visibility: visible;
                                                              margin-top: <? echo $margin;?>px;
                                                              /* Lines: <? echo $detail_text;?> */
                                                            }
                                                        
                                                        </style>
                                                        <div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                                        <font style="font-size: 10px;">
                                                        <div class="tooltipp<? echo $inc;?>"><font color='red'><? echo $total_chests_used;?> chests used.</font>&nbsp;&nbsp;
                                                            <span class="tooltiptext<? echo $inc;?>">
                                                                <?
                                                                echo $detail_text;
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
                                                    $status = $results[$clone_2]['status']; 
                                                    
                                                    if ($status == 'have gold') {
														$golds_owned_count++
                                                        ?>
                                                        <img src="img/check.png" height="24" title="Have Gold"> Have Gold 
                                                        <?
                                                    } elseif (strpos($status, 'can make') !== false) {
                                                        ?>
                                                        <center><a class="fancybox btn btn-primary" href="https://olfactoryhues.com/clone-evolution/craft_gold_awaken.php?clone=<? echo $clone_2;?>" data-fancybox-type="iframe" title="<? echo $clone_2;?>">Make It!</a></center>
                                                        <?
                                                    } else { 													
                                                        if (!empty($results[$clone_2]['missing'])) { 
                                                            foreach ($results[$clone_2]['missing'] as $purple_clone => $qty) {
																if (strlen($purple_clone) >= 19){
																	echo "<font style='font-size: 11px;'>";	
																}
                                                                echo "{$qty}x {$purple_clone}<br />";
																if (strlen($purple_clone) >= 19){
																	echo "</font>";	
																}
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <?
                                                $color_result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone_3."'");
                                                $color_row = mysql_fetch_array($color_result);
                                                $color = $colors[$color_row['institute']];
												$font_shrink = "";
												if (strlen($clone_3) >= 19) {
													$font_shrink = " font-size: 11px;";
												}
                                                ?>
                                                <td style="background-color: <? echo $color;?>; border-right: none; width: 45px;">
                                                    <center>
                                                        <a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $clone_3;?>" data-fancybox-type="iframe" title="<? echo $clone_3;?>" style="text-decoration: none; color: black;">
                                                            <img src="img/golds/<? echo $clone_3;?>.jpg" height="40" style="padding-bottom: 5px;"> 
                                                        </a>    
                                                    </center>                                            
                                                </td>
                                                <td style="background-color: <? echo $color;?>; border-left: none;" align="left">
                                                    <select name="<? echo $clone_temp . '_fodder';?>" onchange="document.getElementById('changed_field').value=this.name; this.form.submit();"> 
                                                            <?
                                                            $fodder_result = mysql_query("SELECT * FROM gold WHERE awaken = 'y' AND institute = '$fodder_type'");
                                                            while ($fodder_row = mysql_fetch_array($fodder_result)){
                                                                ?>
                                                                <option value="<? echo $fodder_row['clone'];?>"	<? if ($fodder_row['clone'] == $clone_3){ echo "selected"; }?>><? echo $fodder_row['clone'];?></option>
                                                                <?	
                                                            }
                                                            ?>
                                                    </select>
                                                    <?
                                                    if (!empty($results[$clone_3]['chests_used'])) {
                                                        
                                                        $chests_by_name = array(); 
                                                        $total_chests_used = 0;
    
                                                        foreach ($results[$clone_3]['chests_used'] as $purple_clone => $chests) {
                                                            foreach ($chests as $chest_name => $qty) {
                                                                $total_chests_used += $qty;
                                                                
                                                                if (!isset($chests_by_name[$chest_name])) {
                                                                    $chests_by_name[$chest_name] = array();
                                                                }
                                                                $chests_by_name[$chest_name][$purple_clone] = $qty;
                                                            }
                                                        }
                                                    
                                                        $detail_text = '';
                                                        foreach ($chests_by_name as $chest_name => $purples_used) {
															$this_chest_name = "{$chest_name}";
															$this_chest_name = chest_name($this_chest_name);
                                                            $detail_text .= $this_chest_name.": <br />";
                                                            foreach ($purples_used as $purple_clone => $qty) {
                                                                 $detail_text .= "{$qty}x {$purple_clone}<br />";
                                                            }
                                                        }					
                                                        ?>
                                                        <style>
                                                            /* Tooltip container */
                                                            .tooltipp<? echo $inc;?> {
                                                              position: relative;
                                                              display: inline-block;
                                                              z-index: 9999;
                                                            }
                                                            
                                                            /* Tooltip text */
                                                            .tooltipp<? echo $inc;?> .tooltiptext<? echo $inc;?> {
                                                              visibility: hidden;
                                                              background-color: black;
                                                              color: #fff;
                                                              text-align: left;
                                                              padding: 10px;
                                                              border-radius: 6px;
                                                              position: absolute;
                                                              z-index: 9999; 
                                                            }
                                                            
                                                            /* Show the tooltip text when you mouse over the tooltip container */
                                                            .tooltipp<? echo $inc;?>:hover .tooltiptext<? echo $inc;?> {
                                                              visibility: visible;
                                                              bottom: 100%;
                                                            }
                                                        </style>
                                                        <div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                                        <font style="font-size: 10px;">
                                                        <div class="tooltipp<? echo $inc;?>" style="margin-top: 5px;"><font color='red'><? echo $total_chests_used;?> chests used.</font>&nbsp;&nbsp;
                                                            <span class="tooltiptext<? echo $inc;?>">
                                                                <?
                                                                echo $detail_text;
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
                                                    $status = $results[$clone_3]['status'];
                                                    
                                                    if ($status == 'have gold') {
														$golds_owned_count++
                                                        ?>
                                                        <img src="img/check.png" height="24" title="Have Gold"> Have Gold 
                                                        <?
                                                    } elseif (strpos($status, 'can make') !== false) {
                                                        ?>
                                                        <center><a class="fancybox btn btn-primary" href="https://olfactoryhues.com/clone-evolution/craft_gold_awaken.php?clone=<? echo $clone_3;?>" data-fancybox-type="iframe" title="<? echo $clone_3;?>">Make It!</a></center>
                                                        <?
                                                    } else { 													
                                                        if (!empty($results[$clone_3]['missing'])) {
                                                            foreach ($results[$clone_3]['missing'] as $purple_clone => $qty) {
																if (strlen($purple_clone) >= 19){
																	echo "<font style='font-size: 11px;'>";	
																}
                                                                echo "{$qty}x {$purple_clone}<br />";
																if (strlen($purple_clone) >= 19){
																	echo "</font>";	
																}
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                            	<td colspan="3" style="background-color: <? echo $bg_color;?>; height: 58px;">
                                                    <center>
                                                    <? if ($total_missing_quantity > 0) { ?>
                                                        Missing: <? echo $total_missing_quantity; ?> Clones
                                                    <? } else if ($golds_owned_count == 3) { ?>
                                                        <center><a class="fancybox btn btn-primary" href="https://olfactoryhues.com/clone-evolution/awaken_clone.php?awaken=<? echo $row['clone'];?>&clone_1=<? echo $clone_1;?>&clone_2=<? echo $clone_2;?>&clone_3=<? echo $clone_3;?>" data-fancybox-type="iframe" title="Awaken!">Awaken!</a></center>
                                                    <? } else { 
                                                        $qty = 3 - $golds_owned_count
                                                        ?>	
                                                        Craft <? echo $qty;?> gold clone(s) to awaken.
                                                    <? }?>
                                                    </center>
                                                </td>
                                            </tr>
                                            <?
											} else {
												?>
                                                <td align="center" colspan="2">
                                                    <div style="background-image: url(img/max_stars.png); background-size: cover; height: 210px; margin-bottom: -6px;">
                                                        <center><br /><br /><img src="img/reds/<? echo $row['clone'];?>.jpg" height="94"></center>
                                                    </div>
                                                </td>
                                                <tr><td colspan="3" style="border-bottom: 0px; border-left: 0px; border-right: 0px;"><br /><br /><br /></td></tr>
                                           		<?
											}
											?>
									</table>
									</center>
								</div>        
								  
							  <?
							  $i++;
							  if ($i == 3){
								$i = 0;
								?>
                                </div><div class="row"> 
                                <?
							  }
						  }
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