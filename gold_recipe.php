<?
include 'top_php.php';
$clone = $_GET['clone'];
$result = mysql_query("SELECT * FROM gold WHERE clone = '".$clone."'");
$row = mysql_fetch_array($result);

function get_color($this_clone){
	$result2 = mysql_query("SELECT * FROM gold WHERE clone ='".$this_clone."'");
	$row2 = mysql_fetch_array($result2);
	switch ($row2[institute]) {
		case 'Lightning':
			$bgcolor="#b4c6e7";
			break;
		case 'Fire':
			$bgcolor="#f2aaaa";
			break;
		case 'Earth':
			$bgcolor="#92d050";
			break;
		case 'Water':
			$bgcolor="#00b0f0";
			break;
		case 'Chaos':
			$bgcolor="#cc99e0";
			break;
		case 'Order':
			$bgcolor="#ffff00";
			break;
	}
	return $bgcolor;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><? echo $clone;?> Recipe</title>
<style>
	td{
		padding: 5px;
	}
</style>
</head>

<body>
    <center>
    <h2 style="margin-top: 0px; margin-bottom: 0px;"><? echo $clone;?></h2>
    <img src="img/golds/<? echo $clone;?>.jpg" height="80"><br />
    <br />
    <table>
    	<?
		$bgcolor = get_color($row[ing1]);
		?>
        <tr style="background-color: <? echo $bgcolor;?>;">
            <td><img src="img/purples/<? echo $clone;?>.jpg" height="40" /></td>
            <td>
                <?
                if ($row[ing3] == $row[ing1]){
                    echo $clone;
					$qty = "3";
                } else {
                    echo $clone;
                    $qty = "2";
                }
                ?>		
            </td>
            <td>
            	x<? echo $qty;?>
            </td>
        </tr>
        <?
		if ($row['clone'] == "Pumpkin Monster"){
			$bgcolor = get_color($row[ing1]);
			?>
			<tr style="background-color: <? echo $bgcolor;?>;">
				<td><img src="img/<? echo $row[institute];;?>.jpg" height="40" /></td>
				<td><? echo "Any ".$row[institute]." Clone";?></td>
				<td>x4</td>
			</tr>
			<?
		} else {
			if ($row[ing3] !== $row[ing1]){
				$bgcolor = get_color($row[ing3]);
				?>
				<tr style="background-color: <? echo $bgcolor;?>;">
					<td><img src="img/purples/<? echo $row[ing3];?>.jpg" height="40" /></td>
					<td><? echo $row[ing3];?></td>
					<td>x1</td>
				</tr>
				<?
			}
			$bgcolor = get_color($row[ing4]);
			?>      
			<tr style="background-color: <? echo $bgcolor;?>;">
				<td><img src="img/purples/<? echo $row[ing4];;?>.jpg" height="40" /></td>
				<td><? echo $row[ing4];?></td>
				<td>x1</td>
			</tr>
			<?
			$bgcolor = get_color($row[ing5]);
			?>
			<tr style="background-color: <? echo $bgcolor;?>;">
				<td><img src="img/purples/<? echo $row[ing5];;?>.jpg" height="40" /></td>
				<td><? echo $row[ing5];?></td>
				<td>x1</td>
			</tr>
			<?
			$bgcolor = get_color($row[ing1]);
			?>
			<tr style="background-color: <? echo $bgcolor;?>;">
				<td><img src="img/<? echo $row[institute];;?>.jpg" height="40" /></td>
				<td><? echo "Any ".$row[institute]." Clone";?></td>
				<td>x2</td>
			</tr>
			<?
		}
		?>
    </table>
    </center>
</body>
</html>