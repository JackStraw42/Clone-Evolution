<?php

function red_missing($user) {
    mysql_query("DELETE FROM red_missing WHERE user = '" . $user . "'");

    // 1. Fetch Inventory into memory (Purple and Gold)
    $inventory = array();
    $inv_res = mysql_query("SELECT clone, purple_qty, gold_qty FROM inventory WHERE user = '" . $user . "'");
    while ($i_row = mysql_fetch_assoc($inv_res)) {
        $inventory[$i_row['clone']] = array(
            'purple' => (int)$i_row['purple_qty'],
            'gold'   => (int)$i_row['gold_qty']
        );
    }

    // 2. Fetch Chest Counts
    $c_res = mysql_query("SELECT * FROM chests WHERE user = '" . $user . "'");
    $user_chests_master = mysql_fetch_assoc($c_res);

    // 3. Fetch User Priorities
    $priorities = array();
    $prio_res = mysql_query("SELECT * FROM user_chest_priorities WHERE user = '" . $user . "'");
    while ($p_row = mysql_fetch_assoc($prio_res)) {
        $priorities[$p_row['chest']] = json_decode($p_row['priority_data'], true);
    }

    // 4. Map Clones to Chests
    $clone_chest_map = array();
    $g_res = mysql_query("SELECT clone, chest FROM gold");
    while ($g_row = mysql_fetch_assoc($g_res)) {
        if ($g_row['chest']) {
            $clone_chest_map[$g_row['clone']] = explode(";", $g_row['chest']);
        }
    }

    $clone_info = array();
    $g_info_res = mysql_query("SELECT * FROM gold");
    while ($gi_row = mysql_fetch_assoc($g_info_res)) {
        $clone_info[$gi_row['clone']] = $gi_row;
    }

    $institute_map = array(
        "Lightning" => "1",
        "Fire"      => "2",
        "Earth"     => "3",
        "Water"     => "4",
        "Chaos"     => "5",
        "Order"     => "6"
    );

    $res = mysql_query("SELECT * FROM red WHERE clone <> 'Aisha'");
    while ($row = mysql_fetch_assoc($res)) {
        $target_clone = $row['clone'];
        $chests_avil = $user_chests_master;
        $inv_temp = $inventory;
        
        $needed_purples = array();
        $total_missing = 0;
        $chests_used = array();
        $chests_used_database = "";
        $missing = array();

        for ($i = 1; $i <= 3; $i++) {
            $g_clone = $row['ing' . $i];

            if ($g_clone) {
                if (isset($inv_temp[$g_clone]) && $inv_temp[$g_clone]['gold'] > 0) {
                    $inv_temp[$g_clone]['gold']--;
                } else {
                    if (isset($inv_temp[$g_clone])) {
                        $inv_temp[$g_clone]['gold'] = 0;
                    }

                    // Special Case: Santa Claus cannot be crafted (no purple recipe)
                    if ($g_clone == "Santa Claus") {
                        if (!isset($missing["Santa Claus"])) {
                            $missing["Santa Claus"] = 0;
                        }
                        $missing["Santa Claus"]++;
                        $total_missing++;
                    } else if (isset($clone_info[$g_clone])) {
                        for ($j = 1; $j <= 5; $j++) {
                            $p_name = $clone_info[$g_clone]['ing' . $j];
                            if ($p_name) {
                                if (!isset($needed_purples[$p_name])) {
                                    $needed_purples[$p_name] = 0;
                                }
                                $needed_purples[$p_name]++;
                            }
                        }
                    }
                }
            }
        }

        uksort($needed_purples, function($a, $b) use ($priorities, $clone_chest_map) {
            $prio_a = 999;
            $prio_b = 999;

            $chests_a = isset($clone_chest_map[$a]) ? $clone_chest_map[$a] : array();
            foreach ($chests_a as $c) {
                $val = (isset($priorities[$c]) && isset($priorities[$c][$a])) ? (int)$priorities[$c][$a] : 999;
                if ($val < $prio_a) {
                    $prio_a = $val;
                }
            }
            
            $chests_b = isset($clone_chest_map[$b]) ? $clone_chest_map[$b] : array();
            foreach ($chests_b as $c) {
                $val = (isset($priorities[$c]) && isset($priorities[$c][$b])) ? (int)$priorities[$c][$b] : 999;
                if ($val < $prio_b) {
                    $prio_b = $val;
                }
            }
            return $prio_a - $prio_b;
        });

        foreach ($needed_purples as $p_clone => $count) {
            $needed = $count;

            if (isset($inv_temp[$p_clone]) && $inv_temp[$p_clone]['purple'] >= $needed) {
                $inv_temp[$p_clone]['purple'] -= $needed;
                $needed = 0;
            } else {
                $has_p = isset($inv_temp[$p_clone]) ? $inv_temp[$p_clone]['purple'] : 0;
                $needed -= $has_p;
                if (isset($inv_temp[$p_clone])) {
                    $inv_temp[$p_clone]['purple'] = 0;
                }
            }

            if ($needed > 0) {
                $possible_chests = isset($clone_chest_map[$p_clone]) ? $clone_chest_map[$p_clone] : array();
                foreach ($possible_chests as $this_chest) {
                    // Check if this clone is blocked in this chest
                    $prio = (isset($priorities[$this_chest]) && isset($priorities[$this_chest][$p_clone])) ? (int)$priorities[$this_chest][$p_clone] : 1;
                    if ($prio === 0) {
                        continue;
                    }

                    $chest_qty = isset($chests_avil[$this_chest]) ? (int)$chests_avil[$this_chest] : 0;
                    while ($needed > 0 && $chest_qty > 0) {
                        if (!isset($chests_used[$this_chest][$p_clone])) {
                            $chests_used[$this_chest][$p_clone] = 0;
                        }
                        $chests_used[$this_chest][$p_clone]++;
                        $needed--;
                        $chest_qty--;
                        $chests_avil[$this_chest] = $chest_qty;
                    }
                }
            }

            if ($needed > 0) {
                $missing[$p_clone] = $needed;
                $total_missing += $needed;
            }
        }

        $inst_prefix = isset($institute_map[$row['institute']]) ? $institute_map[$row['institute']] : "7";
        $institute = $inst_prefix . $row['institute'];

        foreach ($chests_used as $c_name => $clones) {
            foreach ($clones as $c_clone => $c_qty) {
                $chests_used_database .= ";" . $c_name . "," . $c_clone . "," . $c_qty;
            }
        }

        $sql = "INSERT INTO `red_missing` (`user`, `clone`, `total_missing`, `institute`, `chests_used`";
        $vals = " VALUES ('" . $user . "', '" . mysql_real_escape_string($target_clone) . "', '" . $total_missing . "', '" . $institute . "', '" . $chests_used_database . "'";
        
        $m_cnt = 1;
        foreach ($missing as $m_name => $m_qty) {
            if ($m_cnt <= 5) {
                $sql .= ", `missing" . $m_cnt . "`, `missing" . $m_cnt . "_qty`";
                $vals .= ", '" . mysql_real_escape_string($m_name) . "', '" . $m_qty . "'";
                $m_cnt++;
            }
        }
        $sql .= ") " . $vals . ")";
        mysql_query($sql);
    }
}

function gold_missing($user) {
    mysql_query("DELETE FROM gold_missing WHERE user = '" . $user . "'");

    $inventory = array();
    $inv_res = mysql_query("SELECT clone, purple_qty FROM inventory WHERE user = '" . $user . "'");
    while ($i_row = mysql_fetch_assoc($inv_res)) {
        $inventory[$i_row['clone']] = (int)$i_row['purple_qty'];
    }

    $c_res = mysql_query("SELECT * FROM chests WHERE user = '" . $user . "'");
    $user_chests_master = mysql_fetch_assoc($c_res);

    $priorities = array();
    $prio_res = mysql_query("SELECT * FROM user_chest_priorities WHERE user = '" . $user . "'");
    while ($p_row = mysql_fetch_assoc($prio_res)) {
        $priorities[$p_row['chest']] = json_decode($p_row['priority_data'], true);
    }

    $clone_chest_map = array();
    $g_res = mysql_query("SELECT clone, chest FROM gold");
    while ($g_row = mysql_fetch_assoc($g_res)) {
        if ($g_row['chest']) {
            $clone_chest_map[$g_row['clone']] = explode(";", $g_row['chest']);
        }
    }

    $institute_map = array(
        "Lightning" => "1",
        "Fire"      => "2",
        "Earth"     => "3",
        "Water"     => "4",
        "Chaos"     => "5",
        "Order"     => "6"
    );

    // Santa Claus exclusion updated
    $res = mysql_query("SELECT * FROM gold WHERE age > 0 AND clone <> 'Santa Claus'");
    while ($row = mysql_fetch_assoc($res)) {
        $target_clone = $row['clone'];
        $chests_avil = $user_chests_master;
        $inv_temp = $inventory;
        
        $needed_purples = array();
        $total_missing = 0;
        $chests_used = array();
        $chests_used_database = "";
        $missing = array();

        for ($i = 1; $i <= 5; $i++) {
            $p_name = $row['ing' . $i];
            if ($p_name) {
                if (!isset($needed_purples[$p_name])) {
                    $needed_purples[$p_name] = 0;
                }
                $needed_purples[$p_name]++;
            }
        }

        uksort($needed_purples, function($a, $b) use ($priorities, $clone_chest_map) {
            $prio_a = 999;
            $prio_b = 999;
            $chests_a = isset($clone_chest_map[$a]) ? $clone_chest_map[$a] : array();
            foreach ($chests_a as $c) {
                $val = (isset($priorities[$c]) && isset($priorities[$c][$a])) ? (int)$priorities[$c][$a] : 999;
                if ($val < $prio_a) {
                    $prio_a = $val;
                }
            }
            $chests_b = isset($clone_chest_map[$b]) ? $clone_chest_map[$b] : array();
            foreach ($chests_b as $c) {
                $val = (isset($priorities[$c]) && isset($priorities[$c][$b])) ? (int)$priorities[$c][$b] : 999;
                if ($val < $prio_b) {
                    $prio_b = $val;
                }
            }
            return $prio_a - $prio_b;
        });

        foreach ($needed_purples as $p_clone => $needed) {
            $has_inv = isset($inv_temp[$p_clone]) ? $inv_temp[$p_clone] : 0;
            if ($has_inv >= $needed) {
                $inv_temp[$p_clone] -= $needed;
                $needed = 0;
            } else {
                $needed -= $has_inv;
                $inv_temp[$p_clone] = 0;
            }

            if ($needed > 0) {
                $possible_chests = isset($clone_chest_map[$p_clone]) ? $clone_chest_map[$p_clone] : array();
                foreach ($possible_chests as $this_chest) {
                    // Check if this clone is blocked in this chest
                    $prio = (isset($priorities[$this_chest]) && isset($priorities[$this_chest][$p_clone])) ? (int)$priorities[$this_chest][$p_clone] : 1;
                    if ($prio === 0) {
                        continue;
                    }

                    $chest_qty = isset($chests_avil[$this_chest]) ? (int)$chests_avil[$this_chest] : 0;
                    while ($needed > 0 && $chest_qty > 0) {
                        if (!isset($chests_used[$this_chest][$p_clone])) {
                            $chests_used[$this_chest][$p_clone] = 0;
                        }
                        $chests_used[$this_chest][$p_clone]++;
                        $needed--;
                        $chest_qty--;
                        $chests_avil[$this_chest] = $chest_qty;
                    }
                }
            }

            if ($needed > 0) {
                $missing[$p_clone] = $needed;
                $total_missing += $needed;
            }
        }

        $inst_prefix = isset($institute_map[$row['institute']]) ? $institute_map[$row['institute']] : "7";
        $institute = $inst_prefix . $row['institute'];

        foreach ($chests_used as $c_name => $clones) {
            foreach ($clones as $c_clone => $c_qty) {
                $chests_used_database .= ";" . $c_name . "," . $c_clone . "," . $c_qty;
            }
        }

        $sql = "INSERT INTO `gold_missing` (`user`, `clone`, `total_missing`, `institute`, `chests_used`";
        $vals = " VALUES ('" . $user . "', '" . mysql_real_escape_string($target_clone) . "', '" . $total_missing . "', '" . $institute . "', '" . $chests_used_database . "'";
        
        $m_cnt = 1;
        foreach ($missing as $m_name => $m_qty) {
            if ($m_cnt <= 5) {
                $sql .= ", `missing" . $m_cnt . "`, `missing" . $m_cnt . "_qty`";
                $vals .= ", '" . mysql_real_escape_string($m_name) . "', '" . $m_qty . "'";
                $m_cnt++;
            }
        }
        $sql .= ") " . $vals . ")";
        mysql_query($sql);
    }
}
?>