<?
include 'top_php.php';

$clone = $_GET['clone'];
$result = mysql_query("SELECT * FROM red WHERE clone = '".$clone."'");
$row = mysql_fetch_array($result);
$institute = $row['institute'];
$purple_fodder = 0;
$required = find_ingredients();

$background = "img/craft.jpg";

if (isset($_POST['evolve'])){
	$clone = $_POST['clone'];
	$background = "img/blue_screen.jpg";
}
$red_clone = $clone;

if (isset($_POST['remove'])){
	for ($i=0; $i <= 21; $i++){
		$clone = "clone_".$i;
		$color = "color_".$i;
		$qty = "qty_".$i;
		if (isset($_POST[$clone])){
			$result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$_POST[$clone]."'");
			$row = mysql_fetch_array($result);
			if ($_POST[$color] == "purple"){
				$new_qty = $row['purple_qty'] - $_POST[$qty];
				mysql_query("UPDATE inventory SET purple_qty = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$_POST[$clone]."'");	
			} else {
				$new_qty = $row['gold_qty'] - $_POST[$qty];	
				mysql_query("UPDATE inventory SET gold_qty = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$_POST[$clone]."'");	
			}
		}
	}
	
	// Chest removal 
	for ($i=0; $i <=7; $i++){
		$remove_chest = "chest_".$i;
		$remove_chest_qty = "chest_qty_".$i;
		if (isset($_POST[$remove_chest])){
			$result = mysql_query("SELECT * FROM chests WHERE user = '".$user."'");
			$row = mysql_fetch_array($result);
			$new_qty = $row[$_POST[$remove_chest]] - $_POST[$remove_chest_qty];
			mysql_query("UPDATE chests SET ".$_POST[$remove_chest]." = '".$new_qty."' WHERE user = '".$user."'");
		}
	}
	
	$result = mysql_query("SELECT * FROM red_inventory WHERE user = '".$user."' AND clone='".$red_clone."'");
	$row = mysql_fetch_array($result);
	$new_qty = $row['red_qty'] + 1;
	mysql_query("UPDATE red_inventory SET red_qty = '".$new_qty."' WHERE user = '".$user."' AND clone='".$red_clone."'");
	
	gold_missing($user);
	red_missing($user);
}


$gold_fodder = find_gold_fodder();
$js_clones = "const clones = [";
$js_qty = "const qty = [";
$cnt = 1;
foreach ($gold_fodder as $key=>$qty){
	if ($cnt == count($gold_fodder)){
		$js_clones = $js_clones."'".$key."'];";
		$js_qty = $js_qty."'".$qty."'];";	
	} else {
		$js_clones = $js_clones."'".$key."',";
		$js_qty = $js_qty."'".$qty."',";		
	}
	$cnt++;
}

$fodder = find_purple_fodder();
$js_purple_clones = "const purple_clones = [";
$js_purple_qty = "const purple_qty = [";
$cnt = 1;
foreach ($fodder as $key=>$qty){
	if ($cnt == count($fodder)){
		$js_purple_clones = $js_purple_clones."'".$key."'];";
		$js_purple_qty = $js_purple_qty."'".$qty."'];";	
	} else {
		$js_purple_clones = $js_purple_clones."'".$key."',";
		$js_purple_qty = $js_purple_qty."'".$qty."',";		
	}
	$cnt++;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Craft <? echo $clone;?></title>
<style>
	body{
		font-family:"Courier New", Courier, monospace;
		color:#0F0;
		font-weight: bold;
	}
	.bg {
		display: flex;
		align-items: center;   /* vertical centering */
		justify-content: center; /* optional: horizontal centering */
		max-width: 400px;
		height: 600px;
		background-image: url(img/craft.jpg);
		background-repeat: no-repeat;
		background-attachment: fixed;
		background-size: 100% 100%;
		position: relative;
	}

	.content {
		max-width: 400px;
		height: auto; /* or let it grow naturally */
	}
	.content table{
		vertical-align: middle;
	}
	.content td{
		font-weight: bold;
		/*max-width: 200px;*/
	}
	.purple{
		background-color: purple;
		color: black;
		padding: 5px;
	}
	.gold{
		background-color: yellow;
		color: black;
		padding: 5px;
	}
	#clock {
		display: none;
		position: fixed; /* KEY: allows overlay */
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		z-index: 9999; /* KEY: ensures it’s on top */
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
	function fodder_check(field){
		<? 
		echo $js_clones."\r\n";
		echo $js_qty."\r\n";
		?>
		fodder1 = document.getElementById("fodder1").value;
		fodder2 = document.getElementById("fodder2").value;
		cnt = 0;
		if (fodder1 == fodder2){
			index = clones.indexOf(fodder1);
			num = qty[index];
			if (num < 2){
				alert("You only have 1 "+fodder1);
				document.getElementById(field).value = "";
			}
		}
	}
	
	function purple_fodder_check(field){
		<? 
		echo $js_purple_clones."\r\n";
		echo $js_purple_qty."\r\n";
		?>
		const fodder = [];
		const qty = [];
		<?
		for ($i=0; $i < $purple_fodder; $i++){
			?>
			f<? echo $i;?> = document.getElementById("purple_fodder<? echo $i;?>").value;
			if (f<? echo $i;?> !== ""){
				const test = fodder.indexOf(f<? echo $i;?>);
				if (test >= 0){
					qty[test] = qty[test] + 1;
				} else {
					const ind = fodder.length;
					fodder[ind] = f<? echo $i;?>;
					qty[ind] = 1;
				}
			}
			<?
		}
		?>
		
		for (let index = 0; index < fodder.length; ++index) {
			const loc = purple_clones.indexOf(fodder[index]);
			const own = purple_qty[loc];
			const need = qty[index]
			if (need > own){
				alert("You don't have enough "+fodder[index]);
				document.getElementById(field).value = "";
				return
			}
			
		}

	}
</script>
</head>

<body>
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
<div class="bg">
	<div class='content'> 	
    	<?
		if (isset($_POST['evolve'])){
			$result = mysql_query("SELECT * FROM red_missing WHERE user = '".$user."' AND clone = '".$clone."'");
			$row = mysql_fetch_array($result);
			$chest_cnt = 0;
			if ($row[chests_used]){
				$previous_chest = "";
				$chests_used_database = array_filter(explode(";",$row['chests_used']));
				foreach ($chests_used_database as $chest){
					$chest_details = explode(",",$chest);
					if ($chest_details[0] !== $previous_chest){
						$chests_remove[$chest_details[0]] = $chest_details[2];
						$chest_cnt++;
					} else {
						$chests_remove[$chest_details[0]] = $chests_remove[$chest_details[0]] + $chest_details[2];
					}
					$clones_remove[$chest_details[1]] = $chest_details[2];
					$previous_chest = $chest_details[0];
				}		
			}
			if ($chest_cnt > 0){ $chest_cnt++; }

			foreach ($required as $key=>$arr){
				$this_clone = $required[$key]['clone'];
				if ($clones_remove[$this_clone] and $required[$key]['color'] == "purple"){				
					$qty = $required[$key]['qty'] - $clones_remove[$this_clone];
					if ($qty == 0){
						unset($required[$key]);
					} else {
						$required[$key]['qty'] = $qty;
					}
				}  
			}

			$found = "no";
			if (isset($_POST['fodder1']) and $_POST['fodder1'] !== ""){
				foreach ($required as $key=>$arr){
					$this_clone = $required[$key]['clone'];
					if ($required[$key]['clone'] == $_POST['fodder1'] and $required[$key]['color'] == "gold"){				
						$required[$key]['qty']++;
						$found = "yes";
						break;
					}
				}	
				if ($found == "no"){
					$required[] = array("clone"=>$_POST['fodder1'],"color"=>"gold", "qty"=>"1");
				}			
			}
			$found = "no";
			if (isset($_POST['fodder2']) and $_POST['fodder2'] !== ""){
				foreach ($required as $key=>$arr){
					$this_clone = $required[$key]['clone'];
					if ($required[$key]['clone'] == $_POST['fodder2'] and $required[$key]['color'] == "gold"){				
						$required[$key]['qty']++;
						$found = "yes";
						break;
					}
				}	
				if ($found == "no"){
					$required[] = array("clone"=>$_POST['fodder2'],"color"=>"gold", "qty"=>"1");
				}			
			}
			
			for ($i=0; $i < 6; $i++){
				$found = "no";
				$pf = "purple_fodder".$i;
				if (isset($_POST[$pf]) and $_POST[$pf] !== ""){
					foreach ($required as $key=>$arr){
						$this_clone = $required[$key]['clone'];
						if ($required[$key]['clone'] == $_POST[$pf] and $required[$key]['color'] == "purple"){				
							$required[$key]['qty']++;
							$found = "yes";
							break;
						}
					}	
					if ($found == "no"){
						$required[] = array("clone"=>$_POST[$pf],"color"=>"purple", "qty"=>"1");
					}			
				}			
			}
			?>
            <form name="inventory_remove" method="post">
                <center>
                <br />
                <font color="red">Inventory Removal Warning!</font><br /><br />
                <table>
                    <tr><td align="center" colspan="2" style="background-color: black;"><font color="white">Clones Removed:</font></td></tr>
                <?
                $cnt = 0;
                foreach ($required as $key=>$arr){
                    ?>
                    <input type='hidden' name='clone' value='<? echo $clone;?>' />
                    <input type='hidden' name='clone_<? echo $cnt;?>' value='<? echo $required[$key]['clone'];?>' />
                    <input type='hidden' name='color_<? echo $cnt;?>' value='<? echo $required[$key]['color'];?>' />
                    <input type='hidden' name='qty_<? echo $cnt;?>' value='<? echo $required[$key]['qty'];?>' />
                    <tr>
                        <td align="left" style="padding-right: 8px; padding-left: 5px;background-color: <? echo $required[$key]['color']?>; max-width: 200px;"><font color="black"><? echo $required[$key]['clone'];?></font></td>
                        <td align="left" style="padding-right: 5px; padding-left: 5px; background-color: <? echo $required[$key]['color'];?>; max-width: 200px;"><font color="black"><? echo $required[$key]['color']." x".$required[$key]['qty'];?></font></td>
                    </tr>
                    <?
                    $cnt++;
                }
                if ($chests_remove){
                    ?>
                    <tr><td align="center" colspan="2" style="background-color: black;"><font color="white">Chests Removed:</font></td></tr>
                    <?
                }
                $cnt = 0;
                foreach ($chests_remove as $chest=>$chest_qty){
					$chest_name = chest_name($chest)
                    ?>
                    <input type='hidden' name='chest_<? echo $cnt;?>' value='<? echo $chest;?>' />
                    <input type='hidden' name='chest_qty_<? echo $cnt;?>' value='<? echo $chest_qty;?>' />
                    <tr>
                        <td align="left" style="padding-right: 8px; padding-left: 5px;background-color: red; max-width: 200px;">
                        	<?
							if (strlen($chest_name) > 20){
								$font_size = 15;
							} else {
								$font_size = 16;	
							}
							?>
                            <font color="black" style="font-size: <? echo $font_size;?>px;">
								<? echo $chest_name;?>
                            </font>
                        </td>
                        <td align="left" style="padding-right: 5px; padding-left: 5px; background-color: red; max-width: 200px;"><font color="black"><? echo " x".$chest_qty;?></font></td>
                    </tr>				
                    <?
					$cnt++;
                }
                ?>                 
            	<tr>
                	<td align="center" style="padding-top: 8px;"><input type="submit" name="remove" value="Adjust Inventory" class="update-inventory-button" /></td>
                    <td align="center" style="padding-top: 8px;"><button name="cancel" onClick="javascript:parent.jQuery.fancybox.close();">Cancel</button></td>
                </tr>
            </table>
            </center>
            </form>
            <?
		} elseif (isset($_POST['remove'])){
			?>
            <center>
            Inventory Updated!<br /><br />
            <button name="cancel" onClick="javascript:parent.window.location='https://olfactoryhues.com/clone-evolution/red.php';">Close Window</button>
            </center>
            <?
		} else {
			?>
            <center>Evolving:<br /><? echo $clone;?><br />
            <br />
            <font color="black">
            <table>
            <?
			$result = mysql_query("SELECT * FROM red_missing WHERE user = '".$user."' AND clone = '".$clone."'");
			$row = mysql_fetch_array($result);
			$chests_used_database = array_filter(explode(";",$row['chests_used']));
			foreach ($chests_used_database as $chest){
				$chest_details = explode(",",$chest);
				?>
                <tr>
					<td bgcolor="red" align="right" style="padding: 3px;" nowrap>Chest:</td>
					<td bgcolor="red" style="padding: 3px;" nowrap><? echo $chest_details[1]. " x".$chest_details[2];?></td>
				</tr>
                <?
			}
			
            foreach ($report as $key=>$arr){
				if ($report[$key]['status'] == "have"){
					?>
					<tr>
						<td bgcolor="yellow" align="right" style="padding: 3px;" nowrap>Have:</td>
						<td bgcolor="yellow" style="padding: 3px;" nowrap><? echo $report[$key]['clone'];?></td>
					</tr>
					<?
				} else {
					?>
                    <tr>
                    	<td bgcolor="purple" align="right" style="padding: 3px;" nowrap>Make:</td>
                        <td bgcolor="purple" style="padding: 3px;" nowrap><? echo $report[$key]['clone'];?></td>
                    </tr>
					<?
				}
			}
            ?>
            </table>
            </font>
            <br />
            <mark class="gold">Select GOLD sacrifices:</mark><br /><br />
            <form name='fodder' method='post'>
                <input type="hidden" name="clone" value="<? echo $clone;?>" />
                <select name = 'fodder1' ID='fodder1' onChange="fodder_check('fodder1')">
                    <option value = "">Select Gold Fodder (optional)</option>
                    <?
                    foreach ($gold_fodder as $key=>$qty){
                        ?>
                        <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                        <?
                    }
                    ?>
                </select>
                <br />
                <select name='fodder2' ID='fodder2' onChange="fodder_check('fodder2')">
                    <option value = "">Select Gold Fodder (optional)</option>
                    <?
                    foreach ($gold_fodder as $key=>$qty){
                        ?>
                        <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                        <?
                    }
                    ?>
                </select>
                <br />
				<? 		
				if ($purple_fodder > 0){
					?> 
                    <br /><br />
                    <mark class="purple">Select PURPLE sacrifices:</mark><br /><br />
					<?
					for ($i = 0; $i < $purple_fodder; $i++){
						?>
                        <select name='purple_fodder<? echo $i;?>' ID='purple_fodder<? echo $i;?>' onChange="purple_fodder_check('purple_fodder<? echo $i;?>')"> 
                            <option value = "">Select <? echo $institute;?> Fodder (optional)</option>
                            <?
                            foreach ($fodder as $key=>$qty){
                                ?>
                                <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                                <?
                            }
                            ?>
                        </select>
						<?
					}
				}
                ?>
                <br /><br />
                <input type='submit' name='evolve' value='Evolve' /> &nbsp;
                <button name="cancel" onClick="javascript:parent.jQuery.fancybox.close();">Cancel</button>
            </form>
            </center>
        	<?			
		}
	?>
	</div>
</div>
<script>
	document.querySelectorAll('.update-inventory-button').forEach(button => {
	  button.addEventListener('click', function (e) {
		e.preventDefault();
	
		const clock = document.getElementById('clock');
		clock.style.display = 'block';
	
		// Restart video for iOS
		const video = document.getElementById('loading-video');
		if (video) {
		  video.pause();
		  video.currentTime = 0;
		  video.play();
		}
	
		// Preserve submit button name/value
		const tempInput = document.createElement('input');
		tempInput.type = 'hidden';
		tempInput.name = button.name;
		tempInput.value = button.value;
	
		const form = button.closest('form');
		form.appendChild(tempInput);
	
		// Submit after a short delay
		setTimeout(() => form.submit(), 100);
	  });
	});
</script>
</body>
</html>

<?
function find_ingredients(){
	global $user, $clone, $purple_fodder, $report;
	$result = mysql_query("SELECT * FROM red WHERE clone = '".$clone."'");
	$row = mysql_fetch_array($result);
	$required = array();  // array for storing required golds
	$santa = "off";
	for ($i=1; $i<=3; $i++){  // Do for all 3 ingredients
		$ing = "ing".$i;  // name of ingredient field in database
		$gold_inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row[$ing]."'");
		$gold_inv_row = mysql_fetch_array($gold_inv_result);
		if ($gold_inv_row['gold_qty'] == 0){  // user does not have the required gold - get ingredients list to make it.
			$report[] = array("clone"=>$row[$ing],"status"=>"make");
			$purple_fodder = $purple_fodder + 2;
			$gold_result = mysql_query("SELECT * FROM gold WHERE clone = '".$row[$ing]."'");
			$gold_row = mysql_fetch_array($gold_result);	
			for ($j=1; $j<=5; $j++){  // gather all 5 ingredients
				$purp_ing = "ing".$j;		
				$found = "no";
				foreach ($required as $key=>$arr){
					if ($required[$key]['clone'] == $gold_row[$purp_ing] and $required[$key]['color'] == "purple"){
						$found = $key;
						break;
					}
				}
				if ($found === "no"){
					$required[] = array("clone"=>$gold_row[$purp_ing],"color"=>"purple", "qty"=>"1");
				} else {			
					$required[$found]['qty']++;
				}
			}
		} else {
			$required[] = array("clone"=>$row[$ing],"color"=>"gold", "qty"=>"1");
			$report[] = array("clone"=>$row[$ing],"status"=>"have");
		}
		
	}
	return $required;
}


function find_gold_fodder(){
	global $user, $clone;
	$result = mysql_query("SELECT * FROM red WHERE clone='".$clone."'");
	$row = mysql_fetch_array($result);
	$inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND gold_qty > 0"); 
	$gold_fodder = array();
	while ($inv_row = mysql_fetch_array($inv_result)){
		if ($inv_row['clone'] == $row['ing1'] or $inv_row['clone'] == $row['ing2'] or $inv_row['clone'] == $row['ing3']){ 
			if ($inv_row['gold_qty'] > 1){
				$gold_fodder[$inv_row['clone']] = $inv_row['gold_qty'] - 1;
			}
		} else {
			$gold_fodder[$inv_row['clone']] = $inv_row['gold_qty'];
		}
	}
	return $gold_fodder;
}

function find_purple_fodder(){
	global $user, $institute, $required;
	$fodder = array();
	
	$result = mysql_query("SELECT * FROM gold WHERE institute = '".$institute."'");
	while ($row = mysql_fetch_array($result)){
		$inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$row['clone']."'"); 
		$inv_row = mysql_fetch_array($inv_result);
		if ($inv_row['purple_qty'] > 0){
			$fodder[$inv_row['clone']] = $inv_row['purple_qty'];
		}
	}

	foreach ($required as $key=>$arr){
		$this_clone = $required[$key]['clone'];
		if ($fodder[$this_clone] and $required[$key]['color'] == "purple"){
			$qty = $fodder[$this_clone] - $required[$key]['qty'];
			if ($qty > 0){
				$fodder[$this_clone] = $qty;
			} else {
				unset($fodder[$this_clone]); 
			}
		}
	}		
	return $fodder;
}
?>