<?
include 'top_php.php';

// ─────────────────────────────────────────────
// PARSE THE CLONES PARAMETER
// Format: CloneName:qty;CloneName:qty
// ─────────────────────────────────────────────
$craft_plan = array(); // clone => qty to craft

if (isset($_GET['clones']) && $_GET['clones'] !== '') {
    $entries = explode(';', $_GET['clones']);
    foreach ($entries as $entry) {
        $parts = explode(':', urldecode($entry), 2);
        if (count($parts) == 2 && (int)$parts[1] > 0) {
            $craft_plan[trim($parts[0])] = (int)$parts[1];
        }
    }
}

// Return URL — carries exclusion state back to inventory_space.php
$return_url = isset($_GET['return_url']) ? $_GET['return_url'] : 'https://olfactoryhues.com/clone-evolution/inventory_space.php';

// ─────────────────────────────────────────────
// FETCH RECIPES & INSTITUTES FOR ALL CLONES
// ─────────────────────────────────────────────
$clone_data = array(); // clone => array(institute, ings[])

if (!empty($craft_plan)) {
    $cp_escaped = array();
    foreach (array_keys($craft_plan) as $c) {
        $cp_escaped[] = "'" . mysql_real_escape_string($c) . "'";
    }
    $cp_clause = implode(',', $cp_escaped);
    $cd_res = mysql_query("SELECT clone, institute, ing1, ing2, ing3, ing4, ing5 FROM gold WHERE clone IN ($cp_clause)");
    while ($cd_row = mysql_fetch_array($cd_res)) {
        $clone_data[$cd_row['clone']] = array(
            'institute' => $cd_row['institute'],
            'ings'      => array($cd_row['ing1'], $cd_row['ing2'], $cd_row['ing3'], $cd_row['ing4'], $cd_row['ing5'])
        );
    }
}

// ─────────────────────────────────────────────
// BUILD TOTAL NAMED INGREDIENTS NEEDED
// Across all crafts, all clones
// ─────────────────────────────────────────────
$total_required = array(); // clone_name => total qty needed

foreach ($craft_plan as $clone => $craft_qty) {
    if (!isset($clone_data[$clone])) continue;
    $needed = array();
    foreach ($clone_data[$clone]['ings'] as $ing) {
        if ($ing == '') continue;
        if (!isset($needed[$ing])) $needed[$ing] = 0;
        $needed[$ing]++;
    }
    foreach ($needed as $ing => $per_craft) {
        if (!isset($total_required[$ing])) $total_required[$ing] = 0;
        $total_required[$ing] += $per_craft * $craft_qty;
    }
}

// ─────────────────────────────────────────────
// FETCH FULL PURPLE INVENTORY
// ─────────────────────────────────────────────
$full_inventory = array(); // clone => purple_qty
$inv_res = mysql_query("SELECT clone, purple_qty FROM inventory WHERE user = '" . mysql_real_escape_string($user) . "' AND purple_qty > 0");
while ($inv_row = mysql_fetch_array($inv_res)) {
    $full_inventory[$inv_row['clone']] = (int)$inv_row['purple_qty'];
}

// ─────────────────────────────────────────────
// BUILD AVAILABLE POOL FOR FODDER
// Subtract named ingredients from inventory
// ─────────────────────────────────────────────
$available = $full_inventory;
foreach ($total_required as $ing => $qty_needed) {
    if (isset($available[$ing])) {
        $available[$ing] -= $qty_needed;
        if ($available[$ing] <= 0) unset($available[$ing]);
    } else {
        // Not in inventory — still need to represent 0 remaining but nothing to subtract
    }
}

// ─────────────────────────────────────────────
// DETERMINE INSTITUTES & FODDER SLOT COUNTS
// 2 fodder slots per craft of each clone
// ─────────────────────────────────────────────
$institutes_needed = array(); // institute => total fodder slots
foreach ($craft_plan as $clone => $craft_qty) {
    if (!isset($clone_data[$clone])) continue;
    $inst = $clone_data[$clone]['institute'];
    if (!isset($institutes_needed[$inst])) $institutes_needed[$inst] = 0;
    $institutes_needed[$inst] += $craft_qty * 2;
}

// ─────────────────────────────────────────────
// BUILD FODDER POOLS PER INSTITUTE
// Any purple of the right institute with
// remaining qty > 0 after ingredient subtraction
// ─────────────────────────────────────────────
$fodder_pools = array(); // institute => array(clone => qty)

if (!empty($available)) {
    $avail_escaped = array();
    foreach (array_keys($available) as $c) {
        $avail_escaped[] = "'" . mysql_real_escape_string($c) . "'";
    }
    $avail_clause = implode(',', $avail_escaped);
    $fp_res = mysql_query("SELECT clone, institute FROM gold WHERE clone IN ($avail_clause)");
    while ($fp_row = mysql_fetch_array($fp_res)) {
        $inst = $fp_row['institute'];
        if (isset($institutes_needed[$inst])) {
            if (!isset($fodder_pools[$inst])) $fodder_pools[$inst] = array();
            $fodder_pools[$inst][$fp_row['clone']] = $available[$fp_row['clone']];
        }
    }
}

// ─────────────────────────────────────────────
// BUILD JAVASCRIPT FODDER DATA
// Per-institute arrays for dropdown validation
// ─────────────────────────────────────────────
$js_fodder_data = "const fodderData = {\n";
foreach ($fodder_pools as $inst => $pool) {
    $js_names = array();
    $js_qtys  = array();
    foreach ($pool as $clone => $qty) {
        $js_names[] = "'" . addslashes($clone) . "'";
        $js_qtys[]  = (int)$qty;
    }
    $js_fodder_data .= "  '" . addslashes($inst) . "': { clones: [" . implode(',', $js_names) . "], qty: [" . implode(',', $js_qtys) . "] },\n";
}
$js_fodder_data .= "};";

// ─────────────────────────────────────────────
// HANDLE POST: REMOVE (confirmed — do the work)
// ─────────────────────────────────────────────
if (isset($_POST['remove'])) {
    // Remove named ingredients
    $i = 0;
    while (isset($_POST['clone_' . $i])) {
        $rem_clone = $_POST['clone_' . $i];
        $rem_qty   = (int)$_POST['qty_' . $i];
        if ($rem_clone !== '' && $rem_qty > 0) {
            $r = mysql_query("SELECT purple_qty FROM inventory WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($rem_clone) . "'");
            $r_row = mysql_fetch_array($r);
            $new_qty = max(0, (int)$r_row['purple_qty'] - $rem_qty);
            mysql_query("UPDATE inventory SET purple_qty = '" . $new_qty . "' WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($rem_clone) . "'");
        }
        $i++;
    }
    // Remove selected fodder clones
    $j = 0;
    while (isset($_POST['fodder_clone_' . $j])) {
        $fod_clone = $_POST['fodder_clone_' . $j];
        $fod_qty   = (int)$_POST['fodder_qty_' . $j];
        if ($fod_clone !== '' && $fod_qty > 0) {
            $r = mysql_query("SELECT purple_qty FROM inventory WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($fod_clone) . "'");
            $r_row = mysql_fetch_array($r);
            $new_qty = max(0, (int)$r_row['purple_qty'] - $fod_qty);
            mysql_query("UPDATE inventory SET purple_qty = '" . $new_qty . "' WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($fod_clone) . "'");
        }
        $j++;
    }
    // Add crafted gold clones to inventory
    foreach ($craft_plan as $clone => $craft_qty) {
        $r = mysql_query("SELECT gold_qty FROM inventory WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($clone) . "'");
        $r_row = mysql_fetch_array($r);
        $new_gold = (int)$r_row['gold_qty'] + $craft_qty;
        mysql_query("UPDATE inventory SET gold_qty = '" . $new_gold . "' WHERE user = '" . mysql_real_escape_string($user) . "' AND clone = '" . mysql_real_escape_string($clone) . "'");
    }
    gold_missing($user);
    red_missing($user);
}

// ─────────────────────────────────────────────
// HANDLE POST: EVOLVE
// Tally fodder selections for confirmation screen
// ─────────────────────────────────────────────
$fodder_totals = array(); // clone => total qty selected as fodder

if (isset($_POST['evolve'])) {
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'fodder_') === 0 && $val !== '') {
            if (!isset($fodder_totals[$val])) $fodder_totals[$val] = 0;
            $fodder_totals[$val]++;
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Craft All Fodder Golds</title>
<style>
    body {
        font-family: "Courier New", Courier, monospace;
        color: #0F0;
        font-weight: bold;
    }
    .bg {
        background-image: url(img/blue_screen.jpg);
        width: 340px;
        min-height: 340px;
    }
    .content {
        position: relative;
        padding: 10px 12px 14px 12px;
    }
    .content td {
        font-weight: bold;
        max-width: 280px;
    }
    .inst-header {
        color: gold;
        font-size: 13px;
        margin-top: 8px;
        margin-bottom: 2px;
    }
    select {
        margin-bottom: 3px;
        max-width: 290px;
        width: 100%;
    }
    #clock {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        background-color: black;
        padding: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        border-radius: 10px;
    }
    .clock {
        width: 300px;
        height: auto;
        display: block;
    }
</style>
<script>
<?php echo $js_fodder_data; ?>

// Called onChange for any fodder dropdown.
// inst:    the institute this dropdown belongs to
// fieldId: the id of the dropdown that changed
// allIds:  array of all dropdown ids for this institute
function fodder_check(inst, fieldId, allIds) {
    if (!fodderData[inst]) return;
    var clones = fodderData[inst].clones;
    var qty    = fodderData[inst].qty;
    var selectedVal = document.getElementById(fieldId).value;
    if (selectedVal === '') return;

    // Count how many times this clone is selected across all dropdowns for this institute
    var cnt = 0;
    for (var i = 0; i < allIds.length; i++) {
        var el = document.getElementById(allIds[i]);
        if (el && el.value === selectedVal) cnt++;
    }

    // Find available qty for this clone
    var index = clones.indexOf(selectedVal);
    var available = (index >= 0) ? qty[index] : 0;

    if (cnt > available) {
        alert("You only have " + available + " " + selectedVal + " available for fodder.");
        document.getElementById(fieldId).value = '';
    }
}
</script>
</head>

<body>
<div id="clock">
    <video id="loading-video" class="clock" autoplay muted playsinline loop width="300" preload="auto">
        <source src="img/clock.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; color: yellow; font-size: 18px; font-weight: bold; text-shadow: -1px -1px 0 black, 1px -1px 0 black, -1px 1px 0 black, 1px 1px 0 black, -2px 2px 0 black, 2px 2px 0 black;">
        <center>Working on it!<br /><br />Patience conquers all.</center>
    </div>
</div>

<div class="bg">
    <div class="content">
    <?
    // ══════════════════════════════════════════
    // SCREEN 3: Done
    // ══════════════════════════════════════════
    if (isset($_POST['remove'])) {
    ?>
        <center><br /><br />
        Inventory Updated!<br /><br />
        <button type="button" onClick="javascript:parent.window.location='<?echo htmlspecialchars($return_url);?>';">Close Window</button>
        </center>
    <?
    // ══════════════════════════════════════════
    // SCREEN 2: Confirmation
    // ══════════════════════════════════════════
    } elseif (isset($_POST['evolve'])) {
    ?>
        <form name="inventory_remove" method="post">
        <center>
        <br />
        <font color="red"><b>Inventory Removal Warning!</b></font><br /><br />
        <table>
            <tr><td align="center" colspan="2" style="background-color:black;"><font color="white">Clones Removed:</font></td></tr>
            <?
            // Build hidden fields for remove handler (kept separate)
            $cnt = 0;
            foreach ($total_required as $this_clone => $qty) {
                ?>
                <input type="hidden" name="clone_<?echo $cnt;?>" value="<?echo htmlspecialchars($this_clone);?>" />
                <input type="hidden" name="qty_<?echo $cnt;?>" value="<?echo $qty;?>" />
                <?
                $cnt++;
            }
            $fj = 0;
            foreach ($fodder_totals as $fod_clone => $fod_qty) {
                ?>
                <input type="hidden" name="fodder_clone_<?echo $fj;?>" value="<?echo htmlspecialchars($fod_clone);?>" />
                <input type="hidden" name="fodder_qty_<?echo $fj;?>" value="<?echo $fod_qty;?>" />
                <?
                $fj++;
            }

            // Merge for display only
            $display_removals = $total_required;
            foreach ($fodder_totals as $fod_clone => $fod_qty) {
                if (isset($display_removals[$fod_clone])) {
                    $display_removals[$fod_clone] += $fod_qty;
                } else {
                    $display_removals[$fod_clone] = $fod_qty;
                }
            }
            foreach ($display_removals as $this_clone => $qty) {
                ?>
                <tr>
                    <td align="left" style="padding: 2px 8px 2px 5px; background-color:purple;"><font color="black"><?echo htmlspecialchars($this_clone);?></font></td>
                    <td align="left" style="padding: 2px 5px; background-color:purple;"><font color="black">x<?echo $qty;?></font></td>
                </tr>
                <?
            }
            ?>
            <tr>
                <td align="center" style="padding-top:8px;"><input type="submit" name="remove" value="Adjust Inventory" class="update-inventory-button" /></td>
                <td align="center" style="padding-top:8px;"><button type="button" onClick="javascript:parent.jQuery.fancybox.close();">Cancel</button></td>
            </tr>
        </table>
        </center>
        </form>

    <?
    // ══════════════════════════════════════════
    // SCREEN 1: Fodder selection
    // ══════════════════════════════════════════
    } else {
        if (empty($craft_plan)) {
            echo "<center><br />No clones to craft.<br /></center>";
        } else {
    ?>
        <center>
        Evolving:<br />
        <table style="margin: 4px auto;">
            <?
            foreach ($craft_plan as $clone => $craft_qty) {
                ?>
                <tr>
                    <td style="color:#0F0; padding: 1px 6px;"><?echo htmlspecialchars($clone);?></td>
                    <td style="color:gold; padding: 1px 6px;">x<?echo $craft_qty;?></td>
                </tr>
                <?
            }
            ?>
        </table>
        <br />
        Select your sacrifices:<br />
        <form name="fodder" method="post">
            <?
            // Define institute display order
            $inst_display_order = array('Lightning', 'Fire', 'Earth', 'Water', 'Chaos', 'Order');
            $institutes_sorted  = array();
            foreach ($inst_display_order as $io) {
                if (isset($institutes_needed[$io])) $institutes_sorted[$io] = $institutes_needed[$io];
            }
            foreach ($institutes_needed as $io => $slots) {
                if (!isset($institutes_sorted[$io])) $institutes_sorted[$io] = $slots;
            }

            foreach ($institutes_sorted as $inst => $total_slots) {
                $pool = isset($fodder_pools[$inst]) ? $fodder_pools[$inst] : array();

                // Pre-build the JS allIds array for this institute
                $inst_dropdown_ids = array();
                for ($s = 0; $s < $total_slots; $s++) {
                    $inst_dropdown_ids[] = 'fodder_' . $inst . '_' . $s;
                }
                $js_ids = "['" . implode("','", $inst_dropdown_ids) . "']";

                echo "<div class='inst-header'>" . htmlspecialchars($inst) . ":</div>";

                for ($s = 0; $s < $total_slots; $s++) {
                    $field_id = 'fodder_' . $inst . '_' . $s;
                    ?>
                    <select name="<?echo $field_id;?>" id="<?echo $field_id;?>" onChange="fodder_check('<?echo addslashes($inst);?>','<?echo $field_id;?>',<?echo $js_ids;?>)">
                        <option value="">Select Fodder (optional)</option>
                        <?
                        foreach ($pool as $fc => $fqty) {
                            ?>
                            <option value="<?echo htmlspecialchars($fc);?>"><?echo htmlspecialchars($fc) . " (" . $fqty . ")";?></option>
                            <?
                        }
                        ?>
                    </select><br />
                    <?
                }
            }
            ?>
            <br />
            <input type="submit" name="evolve" value="Evolve" />
            &nbsp;
            <button type="button" onClick="javascript:parent.jQuery.fancybox.close();">Cancel</button>
        </form>
        </center>
    <?
        } // end empty craft_plan check
    } // end screen 1
    ?>
    </div>
</div>

<script>
    document.querySelectorAll('.update-inventory-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const clock = document.getElementById('clock');
            clock.style.display = 'block';
            const video = document.getElementById('loading-video');
            if (video) {
                video.pause();
                video.currentTime = 0;
                video.play();
            }
            const tempInput = document.createElement('input');
            tempInput.type  = 'hidden';
            tempInput.name  = button.name;
            tempInput.value = button.value;
            const form = button.closest('form');
            form.appendChild(tempInput);
            setTimeout(() => form.submit(), 100);
        });
    });
</script>
</body>
</html>
