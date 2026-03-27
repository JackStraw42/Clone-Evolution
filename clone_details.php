<?
include 'top_php.php';

$clone = $_GET['clone'];

$result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone."'");
if ($row = mysql_fetch_array($result)){
	if ($row[age] > 0){
		$gold[] = $row['clone'];	
	}
}

$result = mysql_query("SELECT * FROM gold WHERE ing3 = '".$clone."'");
while ($row = mysql_fetch_array($result)){
	if ($row['clone'] !== $clone){
		$gold[] = $row['clone'];	
	}
}

$result = mysql_query("SELECT * FROM gold WHERE ing4 = '".$clone."'");
while ($row = mysql_fetch_array($result)){
	$gold[] = $row['clone'];	
}

$result = mysql_query("SELECT * FROM gold WHERE ing5 = '".$clone."'");
while ($row = mysql_fetch_array($result)){
	$gold[] = $row['clone'];	
}

$result = mysql_query("SELECT * FROM red WHERE ing1 = '".$clone."' OR ing2 = '".$clone."' OR ing3 = '".$clone."'");
while ($row = mysql_fetch_array($result)){
	$red_direct[] = $row['clone'];	
}

foreach ($gold as $this_clone){
	if ($clone == $this_clone){
		continue;	
	} else {
		$result = mysql_query("SELECT * FROM red WHERE ing1 = '".$this_clone."' OR ing2 = '".$this_clone."' OR ing3 = '".$this_clone."'");
		while ($row = mysql_fetch_array($result)){
			$red_indirect[] = $row['clone'];
			$indirect_clone[] = $this_clone;
		}		
	}
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><? echo $clone;?> Details</title>
</head>

<body>
<center>
<h2 style="margin-top: 0px; margin-bottom: 0px;"><? echo $clone;?></h2>
<?
if ($clone == "Santa Claus"){
	?>
    <img src="img/golds/<? echo $clone;?>.jpg" height="80">
    <?
} else {
	?>
    <img src="img/purples/<? echo $clone;?>.jpg" height="80">
    <?
}
?>
<br />
<table>
    <tr>
        <td valign="top">
        	<table>
				<tr>
                	<td colspan="2">
            			<h3><b>Needed as purple for:</b></h3>
                    </td>
                </tr>       	
				<tr>
                	<td colspan="2">
            			<b>Directly:</b>
                    </td>
                </tr>
				<?
                foreach ($gold as $this_clone){
                    ?>
                    <tr>
                        <td><img src="img/purples/<? echo $this_clone;?>.jpg" height="40"></td>
                        <td style="background-color: yellow; padding-left: 5px; padding-right: 10px;"><?  echo $this_clone;?></td>
                    </tr>
                    <?
    
                }
                ?>
				<tr>
                	<td colspan="2">
			            <br />
            			<b>Indirectly:</b>
                    </td>
                </tr>
				<?
                for ($i=0; $i < count($red_indirect); $i++){
                    ?>
                    <tr>
                        <td><img src="img/reds/<? echo $red_indirect[$i];?>.jpg" height="40"></td>
                        <td style="background-color: red; padding-left: 5px; padding-right: 10px;"><? echo $red_indirect[$i]." (".$indirect_clone[$i].")";?></td>
                    </tr>
                    <?
    
                }
                ?>
                <tr>
                	<td colspan="2">&nbsp;</td>
                </tr>
                <?
				if (count($red_direct) > 0){
					?>
                    <tr>
                        <td colspan="2">
                            <h3 style="margin: 0;"><b>Needed as gold for:</b></h3>
                        </td>
                    </tr>  
                    <?
                    foreach ($red_direct as $this_clone){
                        ?>
                        <tr>
                            <td><img src="img/reds/<? echo $this_clone;?>.jpg" height="40"></td>
                            <td style="background-color: red;  padding-left: 5px; padding-right: 10px;"><?  echo $this_clone;?></td>
                        </tr>
                        <?
        
                    }
				}
                ?>
            </table>
        </td>
    </tr>
</table>
</center>
</body>
</html>