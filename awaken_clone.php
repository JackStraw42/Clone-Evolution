<?
include 'top_php.php';

$awaken = $_GET['awaken'];
$clones = array($_GET['clone_1'],$_GET['clone_2'],$_GET['clone_3']);

$result = mysql_query("SELECT * FROM red WHERE clone = '".$awaken."'");
$row = mysql_fetch_array($result);
$institute = $row['institute'];
$background = "img/craft.jpg";
$margintop = "75px";
$marginleft = "60px";
$width = "225px";
$height = "150px";

if (isset($_POST['awaken_clone'])){
	foreach ($clones as $clone){
		$result = mysql_query("SELECT * FROM inventory WHERE user = '".$user."' AND clone = '".$clone."'");
		$row = mysql_fetch_array($result);
		$new_qty = $row['gold_qty'] - 1;
		mysql_query("UPDATE inventory SET gold_qty = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$clone."'");
	}
	$result = mysql_query("SELECT * FROM red_inventory WHERE user = '".$user."' AND clone = '".$awaken."'");
	$row = mysql_fetch_array($result);
	$new_qty = $row['awaken'] + 1;
	mysql_query("UPDATE red_inventory SET awaken = '".$new_qty."' WHERE user = '".$user."' AND clone = '".$awaken."'");
	gold_missing($user);
	red_missing($user);
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
		if (isset($_POST['awaken_clone'])){
			?>
            <center>
            <br />
            Inventory Updated!<br /><br />
            <button name="cancel" onClick="javascript:parent.window.location='https://olfactoryhues.com/clone-evolution/awaken.php';">Close Window</button>
            </center>
            <?
		} else {
			?>
            <center>
            <font style="font-size: 14px;">Awakening:<br /><? echo $awaken;?></font>
            <form name="inventory_remove" method="post">
                <input type="hidden" name="awaken" value="<? echo $awaken;?>" />
                <input type="hidden" name="clone_1" value="<? echo $clones[0];?>" />
                <input type="hidden" name="clone_2" value="<? echo $clones[1];?>" />
                <input type="hidden" name="clone_3" value="<? echo $clones[2];?>" />
                <font color="red" style="font-size: 14px;"><b>Inventory Removal Warning!</b><br /><br /></font>
                <table style="margin-top: -10px;">
                    <tr><td align="center" colspan="2" style="background-color: black;"><font color="white">Clones Removed:</font></td></tr>
                    <?
                    foreach ($clones as $clone){             
                        ?>
                        <tr>
                            <td align="left" style="padding-right: 8px; padding-left: 5px;background-color: yellow;"><font color="black"><? echo $clone;?></font></td>
                        </tr>
                        <?
                    }
                    ?>
                </table>
                <div style="margin-top: 63px;">
                    <input type='submit' name='awaken_clone' class="update-inventory-button" value='Awaken!' /> 
                    <button name="cancel" onClick="javascript:parent.jQuery.fancybox.close();">Cancel</button>   
                </div>  
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
