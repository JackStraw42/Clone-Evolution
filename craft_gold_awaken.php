<?
include 'top_php.php';

$clone = $_GET['clone'];
$required = find_ingredients();

$result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone."'");
$row = mysql_fetch_array($result);
$institute = $row['institute'];
$background = "img/craft.jpg";
$margintop = "82px";
$marginleft = "60px";
$width = "225px";
$height = "150px";

if (isset($_POST['evolve'])){
	$clone = $_POST['clone'];
	$background = "img/blue_screen.jpg";
	$margintop = "5px";
	$marginleft = "5px";
	$width = "325px";
	$height = "330px";
}

if ($clone == "Pumpkin Monster"){
	$margintop = "70px";	
}

if (isset($_POST['remove'])){
	for ($i=0; $i <=7; $i++){
		$remove_clone = "clone_".$i;
		$remove_color = "color_".$i;
		$remove_qty = "qty_".$i;
		if (isset($_POST[$remove_clone])){
			$result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$_POST[$remove_clone]."'");
			$row = mysql_fetch_array($result);
			$new_qty = $row['purple_qty'] - $_POST[$remove_qty];
			mysql_query("UPDATE inventory SET purple_qty = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$_POST[$remove_clone]."'");
		}
	}
	$result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$clone."'");
	$row = mysql_fetch_array($result);
	$new_qty = $row['gold_qty'] + 1;
	mysql_query("UPDATE inventory SET gold_qty = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$clone."'");
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
	gold_missing($user);
	red_missing($user);
}

$fodder = find_fodder();

$js_clones = "const clones = [";
$js_qty = "const qty = [";
$cnt = 1;
foreach ($fodder as $key=>$qty){
	if ($cnt == count($fodder)){
		$js_clones = $js_clones."'".$key."'];";
		$js_qty = $js_qty."'".$qty."'];";	
	} else {
		$js_clones = $js_clones."'".$key."',";
		$js_qty = $js_qty."'".$qty."',";		
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
	.bg{
		background-image:url(<? echo $background;?>);
		width: 340px;
		height: 340px;
	}
	.content{
		position: absolute;
		margin-top: <? echo $margintop;?>;
		margin-left: <? echo $marginleft;?>;
		width: <? echo $width;?>;
		height: <? echo $height;?>;
	}
	.content td{
		font-weight: bold;
		max-width: 200px;	
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
		echo $js_qty
		?>
		fodder1 = document.getElementById("fodder1").value;
		fodder2 = document.getElementById("fodder2").value;
		cnt = 0;
		<?
		if ($clone == "Pumpkin Monster"){
			?>
			fodder3 = document.getElementById("fodder3").value;
			fodder4 = document.getElementById("fodder4").value;			
			<?	
		}
		
		
		if ($clone == "Pumpkin Monster"){
			?>
			test_clone = document.getElementById(field).value;
			for (i = 1; i <= 4; i++){
				this_field = "fodder"+i;
				if (test_clone == document.getElementById(this_field).value){
					cnt++	
				}
				index = clones.indexOf(test_clone);
				num = qty[index];
				if (num < cnt){
					alert("You only have "+num+" "+test_clone);
					document.getElementById(field).value = "";
					return;
				}
			}	
			<?	
		} else {
			?>
			if (fodder1 == fodder2){
				index = clones.indexOf(fodder1);
				num = qty[index];
				if (num < 2){
					alert("You only have 1 "+fodder1);
					document.getElementById(field).value = "";
				}
			}			
			<?
		}
		?>
		

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
			$clone = $_POST['clone'];
			$background = "img/blue_screen.jpg";
			$margintop = "5px";
			$marginleft = "5px";
			$width = "325px";
			$height = "330px";
		
			$result = mysql_query("SELECT * FROM gold_missing WHERE user = '".$user."' AND clone = '".$clone."'");
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
			
			foreach ($required as $this_clone=>$qty){ 
				if ($clones_remove[$this_clone]){
					$qty = $qty - $clones_remove[$this_clone];
					if ($qty == 0){
						unset($required[$this_clone]);
					} else {
						$required[$this_clone] = $qty;
					}
				}   
			}
			
			if (isset($_POST['fodder1']) and $_POST['fodder1'] !== "" and isset($required[$_POST['fodder1']])){
				$new_qty = $required[$_POST['fodder1']] + 1;
				$required[$_POST['fodder1']] = $new_qty;		
			} elseif (isset($_POST['fodder1']) and $_POST['fodder1'] !== "") {
				$required[$_POST['fodder1']] = 1;
			}
			if (isset($_POST['fodder2']) and $_POST['fodder2'] !== "" and isset($required[$_POST['fodder2']])){
				$new_qty = $required[$_POST['fodder2']] + 1;
				$required[$_POST['fodder2']] = $new_qty;		
			} elseif (isset($_POST['fodder2']) and $_POST['fodder2'] !== "") {
				$required[$_POST['fodder2']] = 1;
			}
			if (isset($_POST['fodder3']) and $_POST['fodder3'] !== "" and isset($required[$_POST['fodder3']])){
				$new_qty = $required[$_POST['fodder3']] + 1;
				$required[$_POST['fodder3']] = $new_qty;		
			} elseif (isset($_POST['fodder3']) and $_POST['fodder3'] !== "") {
				$required[$_POST['fodder3']] = 1;
			}
			if (isset($_POST['fodder4']) and $_POST['fodder4'] !== "" and isset($required[$_POST['fodder4']])){
				$new_qty = $required[$_POST['fodder4']] + 1;
				$required[$_POST['fodder4']] = $new_qty;		
			} elseif (isset($_POST['fodder4']) and $_POST['fodder4'] !== "") {
				$required[$_POST['fodder4']] = 1;
			}

			?>
            <form name="inventory_remove" method="post">
            <center>
            <br />
            <font color="red"><b>Inventory Removal Warning!</b></font><br /><br />
            <table>
	            <tr><td align="center" colspan="2" style="background-color: black;"><font color="white">Clones Removed:</font></td></tr>
				<?
                $cnt = 0;
                foreach ($required as $this_clone=>$qty){             
                    ?>
                    <input type='hidden' name='clone_<? echo $cnt;?>' value='<? echo $this_clone;?>' />
                    <input type='hidden' name='qty_<? echo $cnt;?>' value='<? echo $qty;?>' />
                    <tr>
                        <td align="left" style="padding-right: 8px; padding-left: 5px;background-color: purple;"><font color="black"><? echo $this_clone;?></font></td>
                        <td align="left" style="padding-right: 5px; padding-left: 5px; background-color: purple;"><font color="black"><? echo " x".$qty;?></font></td>
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
                        <td align="left" style="padding-right: 8px; padding-left: 5px;background-color: red;"><font color="black"><? echo $chest_name;?></font></td>
                        <td align="left" style="padding-right: 5px; padding-left: 5px; background-color: red;"><font color="black"><? echo " x".$chest_qty;?></font></td>
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
            <br /><br />
            Inventory Updated!<br /><br />
            <button name="cancel" onClick="javascript:parent.window.location='https://olfactoryhues.com/clone-evolution/awaken.php';">Close Window</button>
            </center>
            <?
		} else {
			?>
            <center>Evolving:<br /><? echo $clone;?><br />
            <br />
            Select your sacrifices:<br />
            <? if ($clone !== "Pumpkin Monster"){ ?><br /><? } ?>
            <form name='fodder' method='post'>
                <input type="hidden" name="clone" value="<? echo $clone;?>" />
                <select name = 'fodder1' ID='fodder1' onChange="fodder_check('fodder1')">
                    <option value = "">Select Fodder (optional)</option>
                    <?
                    foreach ($fodder as $key=>$qty){
                        ?>
                        <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                        <?
                    }
                    ?>
                </select>
                <br />
                <select name='fodder2' ID='fodder2' onChange="fodder_check('fodder2')">
                    <option value = "">Select Fodder (optional)</option>
                    <?
                    foreach ($fodder as $key=>$qty){
                        ?>
                        <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                        <?
                    }
                    ?>
                </select>
                                
                                
                <?
				if ($clone == "Pumpkin Monster"){
					?>                
                <select name = 'fodder3' ID='fodder3' onChange="fodder_check('fodder3')">
                    <option value = "">Select Fodder (optional)</option>
                    <?
                    foreach ($fodder as $key=>$qty){
                        ?>
                        <option value = "<? echo $key;?>"><? echo $key . " (" . $qty . ")";?></option>
                        <?
                    }
                    ?>
                </select>
                <br />
                <select name='fodder4' ID='fodder4' onChange="fodder_check('fodder4')">
                    <option value = "">Select Fodder (optional)</option>
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
				?>                
                <br /><br /><br />
                <? if ($clone !== "Pumpkin Monster"){ ?><br /><? } ?>
                <input type='submit' name='evolve' value='Evolve' <? if ($clone == "Pumpkin Monster"){ ?>style="margin-top: 10px;"<? } ?> /> 
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
	global $user, $clone;
	$result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone."'");
	$row = mysql_fetch_array($result);
	$required = array();  // array for storing required purples

	for ($i=1; $i<=5; $i++){  // Do for all 5 ingredients
		$purp_ing = "ing".$i;
		if ($row[$purp_ing]  == ""){ continue; }
		if (isset($required[$row[$purp_ing]])) {
			$required[$row[$purp_ing]]++;				
		} else {
			$required[$row[$purp_ing]] = 1;			
		}
	}
	return $required;
}


function find_fodder(){
	global $user, $clone, $institute;
	$result = mysql_query("SELECT * FROM gold WHERE clone='".$clone."'");
	$row = mysql_fetch_array($result);
	$inv_result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND purple_qty > 0"); 
	$fodder = array();
	while ($inv_row = mysql_fetch_array($inv_result)){
		$institute_result = mysql_query("SELECT * FROM gold WHERE clone = '".$inv_row['clone']."'");
		$institute_row = mysql_fetch_array($institute_result);
		if ($institute_row['institute'] == $institute){
			$fodder[$inv_row['clone']] = $inv_row['purple_qty'];
		}
	}
	$required = find_ingredients();
	foreach ($required as $this_clone=>$qty){
		$new_qty = $fodder[$this_clone] - $qty;
		if ($new_qty > 0){
			$fodder[$this_clone] = $new_qty;
		} else {
			unset($fodder[$this_clone]);
		}
	}
	return $fodder;
}
?>