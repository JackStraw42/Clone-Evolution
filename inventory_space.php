<?
include 'top_php.php';

// ─────────────────────────────────────────────
// FODDER GOLD LIST
// To add new fodder golds in the future, simply
// add the clone name to this array.
// ─────────────────────────────────────────────
$all_fodder = array(
    'Social Pres',
    'Catholic Hero',
    'Rock God',
    'Wrestling King',
    'Wright Brothers',
    'Symphony.D',
    'Cleopatra',
    'Hanzo',
    'Panther Shooter',
    'Poet William',
    'Robin',
    'Harsh Assassin',
    'Qin Shi Huang',
    'Empress Wu',
    'Alexander',
    'Lady Lightholder',
    'Ragnar',
    'Crime Fighter'
);

// ─────────────────────────────────────────────
// HANDLE EXCLUSION FORM
// Accepts POST (form submit) or GET (redirect back)
// ─────────────────────────────────────────────
$excluded = array();
if (isset($_POST['excluded']) && is_array($_POST['excluded'])) {
    $excluded = $_POST['excluded'];
} elseif (isset($_GET['excluded']) && is_array($_GET['excluded'])) {
    $excluded = $_GET['excluded'];
}
$fodder_list = array_diff($all_fodder, $excluded);
$fodder_list = array_values($fodder_list);

// ─────────────────────────────────────────────
// FETCH RECIPES FROM DATABASE
// Only pull recipes for the active fodder list
// ─────────────────────────────────────────────
$recipes = array(); // clone => array of 5 ingredient names

if (!empty($fodder_list)) {
    $escaped = array();
    foreach ($fodder_list as $f) {
        $escaped[] = "'" . mysql_real_escape_string($f) . "'";
    }
    $in_clause = implode(',', $escaped);
    $res = mysql_query("SELECT clone, ing1, ing2, ing3, ing4, ing5 FROM gold WHERE clone IN ($in_clause)");
    while ($row = mysql_fetch_array($res)) {
        $recipes[$row['clone']] = array(
            $row['ing1'],
            $row['ing2'],
            $row['ing3'],
            $row['ing4'],
            $row['ing5']
        );
    }
}

// ─────────────────────────────────────────────
// FETCH FODDER GOLD INSTITUTES (for checkbox colors)
// Query ALL fodder golds, not just active ones,
// so that excluded clones still show their color.
// ─────────────────────────────────────────────
$fodder_inst_map = array(); // clone => institute
$all_fodder_escaped = array();
foreach ($all_fodder as $af) {
    $all_fodder_escaped[] = "'" . mysql_real_escape_string($af) . "'";
}
$all_fodder_clause = implode(',', $all_fodder_escaped);
$fi_res = mysql_query("SELECT clone, institute FROM gold WHERE clone IN ($all_fodder_clause)");
while ($fi_row = mysql_fetch_array($fi_res)) {
    $fodder_inst_map[$fi_row['clone']] = $fi_row['institute'];
}

// ─────────────────────────────────────────────
// BUILD EXCLUDABLE PURPLE INGREDIENT LIST
// Collect all unique ingredients from active
// fodder recipes, then filter to only those
// with age > 0 (i.e. not pure-purple fodders).
// ─────────────────────────────────────────────
$all_purple_ings = array(); // all unique ingredients across active recipes
foreach ($recipes as $clone => $ings) {
    foreach ($ings as $ing) {
        $all_purple_ings[$ing] = true;
    }
}
$all_purple_ings = array_keys($all_purple_ings);

// Query age for each ingredient; keep only age > 0
$excludable_purples = array(); // name => institute (for display grouping)
if (!empty($all_purple_ings)) {
    $ing_escaped = array();
    foreach ($all_purple_ings as $ing) {
        $ing_escaped[] = "'" . mysql_real_escape_string($ing) . "'";
    }
    $ing_list_clause = implode(',', $ing_escaped);
    $age_res = mysql_query("SELECT clone, institute FROM gold WHERE clone IN ($ing_list_clause) AND age > 0");
    while ($age_row = mysql_fetch_array($age_res)) {
        $excludable_purples[$age_row['clone']] = $age_row['institute'];
    }
    // Sort excludable purples by institute then name for display
    $inst_order_pre = array('Lightning'=>0,'Fire'=>1,'Earth'=>2,'Water'=>3,'Chaos'=>4,'Order'=>5);
    uksort($excludable_purples, function($a, $b) use ($excludable_purples, $inst_order_pre) {
        $oa = isset($inst_order_pre[$excludable_purples[$a]]) ? $inst_order_pre[$excludable_purples[$a]] : 6;
        $ob = isset($inst_order_pre[$excludable_purples[$b]]) ? $inst_order_pre[$excludable_purples[$b]] : 6;
        if ($oa !== $ob) return $oa - $ob;
        return strcmp($a, $b);
    });
}

// ─────────────────────────────────────────────
// HANDLE PURPLE EXCLUSION FORM
// Accepts POST (form submit) or GET (redirect back)
// ─────────────────────────────────────────────
$excluded_purples = array();
if (isset($_POST['excluded_purples']) && is_array($_POST['excluded_purples'])) {
    $excluded_purples = $_POST['excluded_purples'];
} elseif (isset($_GET['excluded_purples']) && is_array($_GET['excluded_purples'])) {
    $excluded_purples = $_GET['excluded_purples'];
}

// ─────────────────────────────────────────────
// FETCH PLAYER'S PURPLE INVENTORY
// ─────────────────────────────────────────────
$inventory = array(); // clone_name => qty available

$inv_res = mysql_query("SELECT clone, purple_qty FROM inventory WHERE user = '" . mysql_real_escape_string($user) . "' AND purple_qty > 0");
while ($inv_row = mysql_fetch_array($inv_res)) {
    $inventory[$inv_row['clone']] = (int)$inv_row['purple_qty'];
}

// Zero out any excluded purples so the optimizer ignores them
foreach ($excluded_purples as $ep) {
    $inventory[$ep] = 0;
}

// ─────────────────────────────────────────────
// OPTIMIZER: RECURSIVE BACKTRACKING
//
// Strategy:
//   For each fodder gold, calculate the max
//   number of times it can be crafted given
//   current inventory. Then recursively try
//   all counts from max down to 0, tracking
//   the best total crafts found.
//
// Pruning:
//   If even crafting the max of all remaining
//   recipes can't beat the current best, abandon
//   that branch early.
// ─────────────────────────────────────────────

$best_total   = 0;
$best_plan    = array(); // clone => count

// Pre-build ordered list of clones with their recipes
$craft_order = array_keys($recipes);
$num_clones  = count($craft_order);

/**
 * Calculate max crafts possible for a given clone
 * given the current inventory state.
 */
function max_crafts($clone, &$inv, &$recipes) {
    if (!isset($recipes[$clone])) return 0;
    $ings = $recipes[$clone];
    // Count how many of each ingredient are needed
    $needed = array();
    foreach ($ings as $ing) {
        if (!isset($needed[$ing])) $needed[$ing] = 0;
        $needed[$ing]++;
    }
    $max = PHP_INT_MAX;
    foreach ($needed as $ing => $qty_per_craft) {
        $available = isset($inv[$ing]) ? $inv[$ing] : 0;
        $possible  = (int)floor($available / $qty_per_craft);
        if ($possible < $max) $max = $possible;
    }
    return ($max === PHP_INT_MAX) ? 0 : $max;
}

/**
 * Apply or undo crafting a clone N times to inventory.
 * $direction: 1 to consume, -1 to restore.
 */
function apply_crafts($clone, $count, $direction, &$inv, &$recipes) {
    if ($count == 0) return;
    $ings = $recipes[$clone];
    $needed = array();
    foreach ($ings as $ing) {
        if (!isset($needed[$ing])) $needed[$ing] = 0;
        $needed[$ing]++;
    }
    foreach ($needed as $ing => $qty_per_craft) {
        if (!isset($inv[$ing])) $inv[$ing] = 0;
        $inv[$ing] -= $direction * $count * $qty_per_craft;
    }
}

/**
 * Calculate the upper bound on additional crafts possible
 * from clones at index $idx onwards, ignoring conflicts
 * between them (optimistic estimate for pruning).
 */
function upper_bound($idx, &$inv, &$craft_order, &$recipes) {
    $total = 0;
    $n = count($craft_order);
    for ($i = $idx; $i < $n; $i++) {
        $total += max_crafts($craft_order[$i], $inv, $recipes);
    }
    return $total;
}

/**
 * Backtracking search.
 */
function backtrack($idx, $current_total, &$current_plan, &$best_total, &$best_plan, &$inv, &$craft_order, &$recipes) {
    $n = count($craft_order);

    if ($idx == $n) {
        if ($current_total > $best_total) {
            $best_total = $current_total;
            $best_plan  = $current_plan;
        }
        return;
    }

    // Pruning: even if we max everything remaining, can we beat best?
    $ub = $current_total + upper_bound($idx, $inv, $craft_order, $recipes);
    if ($ub <= $best_total) {
        return;
    }

    $clone   = $craft_order[$idx];
    $max_now = max_crafts($clone, $inv, $recipes);

    // Try from max down to 0 (try higher counts first for faster best-finding)
    for ($count = $max_now; $count >= 0; $count--) {
        $current_plan[$clone] = $count;
        apply_crafts($clone, $count, 1, $inv, $recipes);

        backtrack($idx + 1, $current_total + $count, $current_plan, $best_total, $best_plan, $inv, $craft_order, $recipes);

        apply_crafts($clone, $count, -1, $inv, $recipes);
    }
    unset($current_plan[$clone]);
}

// Run the optimizer if we have recipes and inventory
$ran_optimizer = false;
if (!empty($recipes) && !empty($inventory)) {
    $ran_optimizer  = true;
    $current_plan   = array();
    backtrack(0, 0, $current_plan, $best_total, $best_plan, $inventory, $craft_order, $recipes);
}

// Calculate total purples consumed by best plan
$total_purples_consumed = $best_total * 7;

// Build per-clone ingredient consumption detail for display
$ingredient_usage = array(); // ingredient_name => qty consumed
foreach ($best_plan as $clone => $count) {
    if ($count == 0) continue;
    $ings = $recipes[$clone];
    $needed = array();
    foreach ($ings as $ing) {
        if (!isset($needed[$ing])) $needed[$ing] = 0;
        $needed[$ing]++;
    }
    foreach ($needed as $ing => $qty_per_craft) {
        if (!isset($ingredient_usage[$ing])) $ingredient_usage[$ing] = 0;
        $ingredient_usage[$ing] += $qty_per_craft * $count;
    }
}
arsort($ingredient_usage);

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
        .fodder-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px 15px 40px;
        }
        .fodder-section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #aaa;
            padding-bottom: 5px;
        }
        /* ── Exclusion checkboxes ── */
        .exclude-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 6px;
            margin-bottom: 15px;
            background-color: #666;
            padding: 10px;
            border-radius: 6px;
        }
        .exclude-grid label {
            display: flex;
            align-items: center;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.2);
            white-space: nowrap;
            overflow: hidden;
        }
        .exclude-grid label.inst-default {
            color: #fff;
        }
        .exclude-grid input[type=checkbox] {
            margin-right: 6px;
            flex-shrink: 0;
            cursor: pointer;
        }
        /* ── Results table ── */
        .fodder-table {
            border-collapse: collapse;
            width: 100%;
            max-width: 520px;
        }
        .fodder-table th, .fodder-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: bold;
            color: #000;
            vertical-align: middle;
        }
        .fodder-table th {
            background-color: gold;
            text-align: center;
        }
        .fodder-table td.clone-img {
            width: 50px;
            text-align: center;
            padding: 4px;
        }
        .fodder-table td.count-cell {
            text-align: center;
            background-color: #e8e8e8;
            width: 60px;
        }
        /* ── Summary box ── */
        .summary-box {
            display: block;
            width: fit-content;
            margin: 0 auto 24px auto;
            background: #222;
            color: #fff;
            border: 2px solid gold;
            border-radius: 8px;
            padding: 14px 24px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.8;
        }
        .summary-box span.big {
            font-size: 22px;
            color: gold;
            font-weight: bold;
        }
        /* ── Ingredient breakdown ── */
        .ing-table {
            border-collapse: collapse;
            width: 100%;
            max-width: 400px;
            margin-top: 6px;
        }
        .ing-table th, .ing-table td {
            border: 1px solid #999;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: bold;
            color: #000;
            vertical-align: middle;
        }
        .ing-table th {
            background-color: gold;
            text-align: center;
        }
        .ing-table td.ing-name {
            font-weight: bold;
        }
        .ing-table td.ing-qty {
            text-align: center;
            width: 60px;
        }
        /* ── No results notice ── */
        .no-results {
            font-size: 14px;
            color: #888;
            font-style: italic;
            margin-top: 10px;
        }
        /* ── institute colors (match gold.php) ── */
        .inst-Lightning { background-color: #b4c6e7; }
        .inst-Fire      { background-color: #f2aaaa; }
        .inst-Earth     { background-color: #92d050; }
        .inst-Water     { background-color: #00b0f0; }
        .inst-Chaos     { background-color: #cc99e0; }
        .inst-Order     { background-color: #ffff00; }
        .inst-default   { background-color: #ffffff; }
        </style>

        <main class="site-content" role="main">
            <section id="service">
                <div class="container">
                    <div class="fodder-wrap">

                        <div class="sec-title text-center" style="padding-top: 50px; margin-bottom: 20px;">
                            <h2 class="wow animated bounceInLeft">
                                <font color="yellow" <? if ($mode !== "dark"){?> style="-webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black;" <? } ?>>
                                    Inventory Space Optimizer
                                </font>
                            </h2>
                            <h5 style="margin-top: -30px;">
                            	Maximize inventory space with optimized gold fodder crafting.<br /><br />
                            	Note: This program assumes you have the neccessary fodder.<br /><br /><br />
                            </h5>
                        </div>

                        <!-- ══════════════════════════════════════ -->
                        <!-- EXCLUSION FORM                        -->
                        <!-- ══════════════════════════════════════ -->
                        <form method="post" action="inventory_space.php">
                            <h4 style="text-align:center;">Exclude Fodder Golds (optional)</h4>
                            <div class="exclude-grid">
                                <?
                                foreach ($all_fodder as $f) {
                                    $checked    = in_array($f, $excluded) ? ' checked' : '';
                                    $f_inst     = isset($fodder_inst_map[$f]) ? $fodder_inst_map[$f] : 'default';
                                    $f_class    = 'inst-' . $f_inst;
                                    echo "<label class='" . $f_class . "'><input type='checkbox' name='excluded[]' value='" . htmlspecialchars($f) . "'" . $checked . "> " . htmlspecialchars($f) . "</label>";
                                }
                                ?>
                            </div>

                            <h4 style="text-align:center;">Exclude Purple Clones (optional)</h4>
                            <div class="exclude-grid">
                                <?
                                if (empty($excludable_purples)) {
                                    echo "<span style='color:#fff; font-style:italic;'>No excludable ingredients available for the selected fodder golds.</span>";
                                } else {
                                    foreach ($excludable_purples as $ing => $inst) {
                                        $checked   = in_array($ing, $excluded_purples) ? ' checked' : '';
                                        $ing_class = 'inst-' . $inst;
                                        echo "<label class='" . $ing_class . "'><input type='checkbox' name='excluded_purples[]' value='" . htmlspecialchars($ing) . "'" . $checked . "> " . htmlspecialchars($ing) . "</label>";
                                    }
                                }
                                ?>
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-bottom: 30px;">Recalculate</button>
                        </form>

                        <?
                        if (!$ran_optimizer) {
                        ?>
                            <p class="no-results">Could not run optimizer — no inventory data found for your account.</p>
                        <?
                        } elseif ($best_total == 0) {
                        ?>
                            <p class="no-results">No fodder gold clones can be crafted with your current purple inventory.</p>
                        <?
                        } else {
                            // Fetch institute for gold fodder clones (for craft list coloring)
                            $inst_map = array();
                            $inst_res = mysql_query("SELECT clone, institute FROM gold WHERE clone IN ($in_clause)");
                            while ($ir = mysql_fetch_array($inst_res)) {
                                $inst_map[$ir['clone']] = $ir['institute'];
                            }
                            // Build purple ingredient → institute map.
                            // A purple clone's institute matches the gold clone of the same name.
                            // Collect all unique ingredient names used in the best plan.
                            $ing_names = array();
                            foreach ($ingredient_usage as $ing => $used) {
                                $ing_names[] = "'" . mysql_real_escape_string($ing) . "'";
                            }
                            $ing_inst_map = array();
                            if (!empty($ing_names)) {
                                $ing_in = implode(',', $ing_names);
                                $ing_inst_res = mysql_query("SELECT clone, institute FROM gold WHERE clone IN ($ing_in)");
                                while ($iir = mysql_fetch_array($ing_inst_res)) {
                                    $ing_inst_map[$iir['clone']] = $iir['institute'];
                                }
                            }

                            // ── Sort order for institutes ──
                            $inst_order = array('Lightning'=>0,'Fire'=>1,'Earth'=>2,'Water'=>3,'Chaos'=>4,'Order'=>5,'default'=>6);

                            // Sort best_plan by institute then clone name
                            $plan_keys = array_keys($best_plan);
                            usort($plan_keys, function($a, $b) use ($inst_map, $inst_order) {
                                $ia = isset($inst_map[$a]) ? $inst_map[$a] : 'default';
                                $ib = isset($inst_map[$b]) ? $inst_map[$b] : 'default';
                                $oa = isset($inst_order[$ia]) ? $inst_order[$ia] : 6;
                                $ob = isset($inst_order[$ib]) ? $inst_order[$ib] : 6;
                                if ($oa !== $ob) return $oa - $ob;
                                return strcmp($a, $b);
                            });
                            $sorted_plan = array();
                            foreach ($plan_keys as $k) { $sorted_plan[$k] = $best_plan[$k]; }

                            // Sort ingredient_usage by institute then ingredient name
                            $ing_keys = array_keys($ingredient_usage);
                            usort($ing_keys, function($a, $b) use ($ing_inst_map, $inst_order) {
                                $ia = isset($ing_inst_map[$a]) ? $ing_inst_map[$a] : 'default';
                                $ib = isset($ing_inst_map[$b]) ? $ing_inst_map[$b] : 'default';
                                $oa = isset($inst_order[$ia]) ? $inst_order[$ia] : 6;
                                $ob = isset($inst_order[$ib]) ? $inst_order[$ib] : 6;
                                if ($oa !== $ob) return $oa - $ob;
                                return strcmp($a, $b);
                            });
                            $sorted_ing = array();
                            foreach ($ing_keys as $k) { $sorted_ing[$k] = $ingredient_usage[$k]; }

                            // Build the clones parameter for the Craft All button
                            // Format: CloneName:qty;CloneName:qty (skipping zero-count entries)
                            $craft_all_parts = array();
                            foreach ($best_plan as $clone => $count) {
                                if ($count > 0) {
                                    $craft_all_parts[] = urlencode($clone) . ':' . $count;
                                }
                            }
                            $craft_all_param = implode(';', $craft_all_parts);

                            // Build exclusion params to pass through to the return URL
                            $return_url = 'https://olfactoryhues.com/clone-evolution/inventory_space.php';
                            $return_params = array();
                            foreach ($excluded as $ex) {
                                $return_params[] = 'excluded[]=' . urlencode($ex);
                            }
                            foreach ($excluded_purples as $exp) {
                                $return_params[] = 'excluded_purples[]=' . urlencode($exp);
                            }
                            if (!empty($return_params)) {
                                $return_url .= '?' . implode('&', $return_params);
                            }
                            $craft_all_href = 'https://olfactoryhues.com/clone-evolution/clean_inventory.php?clones=' . $craft_all_param . '&return_url=' . urlencode($return_url);
                        ?>

                        <!-- ══════════════════════════════════════ -->
                        <!-- SUMMARY BOX                           -->
                        <!-- ══════════════════════════════════════ -->
                        <div class="summary-box">
                            <div>Gold clones to craft:&nbsp; <span class="big"><?echo $best_total;?></span></div>
                            <div>Purple clones consumed:&nbsp; <span class="big"><?echo $total_purples_consumed;?></span></div>
                            <div>Inventory slots freed:&nbsp; <span class="big"><?echo ($total_purples_consumed - $best_total);?></span></div>
                        </div>
                        <div style="text-align:center; margin-bottom: 24px;">
                            <a class="fancybox btn btn-primary" href="<?echo $craft_all_href;?>" data-fancybox-type="iframe" title="Craft All Golds">Craft All Clones</a>
                        </div>

                        <div class="row">
                            <!-- ══════════════════════════════════════ -->
                            <!-- CRAFT LIST TABLE                      -->
                            <!-- ══════════════════════════════════════ -->
                            <div class="col-md-6 col-sm-12" style="padding-bottom: 20px;">
                                <h4 style="text-align:center;">Recommended Craft List</h4>
                                <center>
                                <table class="fodder-table">
                                    <tr>
                                        <th colspan="4">Gold Fodder Clones to Craft</th>
                                    </tr>
                                    <?
                                    foreach ($sorted_plan as $clone => $count) {
                                        if ($count == 0) continue;
                                        $inst      = isset($inst_map[$clone]) ? $inst_map[$clone] : 'default';
                                        $inst_class = 'inst-' . $inst;
                                        $font_shrink = (strlen($clone) >= 19) ? " font-size: 11px;" : "";
                                        ?>
                                        <tr>
                                            <td class="clone-img <?echo $inst_class;?>">
                                                <img src='img/purples/<?echo htmlspecialchars($clone);?>.jpg' height='40'>
                                            </td>
                                            <td class="<?echo $inst_class;?>" style="<?echo $font_shrink;?>">
                                                <?echo htmlspecialchars($clone);?>
                                            </td>
                                            <td class="count-cell">
                                                x<?echo $count;?>
                                            </td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                </table>
                                </center>
                            </div>

                            <!-- ══════════════════════════════════════ -->
                            <!-- INGREDIENT BREAKDOWN TABLE            -->
                            <!-- ══════════════════════════════════════ -->
                            <div class="col-md-6 col-sm-12" style="padding-bottom: 20px;">
                                <h4 style="text-align:center;">Purple Clones Consumed</h4>
                                <center>
                                <table class="ing-table">
                                    <tr>
                                        <th>Purple Clone</th>
                                        <th>Used</th>
                                        <th>Remaining</th>
                                    </tr>
                                    <?
                                    foreach ($sorted_ing as $ing => $used) {
                                        $had        = isset($inventory[$ing]) ? $inventory[$ing] : 0;
                                        $remaining  = $had - $used;
                                        $ing_inst   = isset($ing_inst_map[$ing]) ? $ing_inst_map[$ing] : 'default';
                                        $ing_class  = 'inst-' . $ing_inst;
                                        ?>
                                        <tr>
                                            <td class="ing-name <?echo $ing_class;?>"><?echo htmlspecialchars($ing);?></td>
                                            <td class="ing-qty <?echo $ing_class;?>"><?echo $used;?></td>
                                            <td class="ing-qty <?echo $ing_class;?>"><?echo $remaining;?></td>
                                        </tr>
                                        <?
                                    }
                                    ?>
                                </table>
                                </center>
                            </div>
                        </div>

                        <?
                        } // end results block
                        ?>

                    </div><!-- /fodder-wrap -->
                </div>
            </section>
        </main>

        <? include 'footer.php';?>

    </body>
</html>
