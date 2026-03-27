<?
error_reporting(0);
include 'missing.php';

if ($_POST['switch_to_acct']){
	setcookie("HT_user", "", time() - 3600);
	$user = $_POST['switch_to_acct'];
	$cookielife = time() + 31556926;
	setcookie('HT_user', $user, $cookielife);
	?>
	<script>window.location = window.location.href;</script>
	<?	
} elseif (!isset($_COOKIE["HT_user"])) {
	?>
	<script>window.location='https://olfactoryhues.com/clone-evolution/login.php';</script>
    <?
} else {
	$user = $_COOKIE['HT_user'];
}

$link = @mysql_connect('localhost', olfactor_jack, Dice_1234) or die('Error connecting to mysql');
mysql_select_db(olfactor_clone_evolution) or die("Error connecting to database");

$result = mysql_query ("SELECT * FROM `update` WHERE user = '".$user."'");
$row = mysql_fetch_array($result);
if ($row['user'] == $user){
	
} else {
	gold_missing($user);
	red_missing($user);
	mysql_query("INSERT INTO `update` (`user`) VALUES ('".$user."')");	
}

$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
$row = mysql_fetch_array($result);

if ($_POST['account_to_link']){
	if ($_POST['account_to_link'] == $user){
		$message = "<font color='red'>Link attempt failed: You're on the $user account!</font>";
	} else {
		$link_result = mysql_query("SELECT * FROM users WHERE name = '".$_POST['account_to_link']."'");
		if ($link_row = mysql_fetch_array($link_result)){
			if ($row['password'] == $link_row['password']){		
				if ($row[linked_accounts] !== ""){
					$accts = explode(";",$row[linked_accounts]);
					$test = "";
					foreach ($accts as $key => $value) {
						if (strtolower($value) == strtolower($_POST['account_to_link'])){
							$message = "<font color='red'>Link attempt failed: Account already linked.</font>";
							$test = "fail";
						}
					}
					if ($test !== "fail"){
						$new_val = $row['linked_accounts'] . ";" . $link_row['name'];
						mysql_query("UPDATE `users` SET `linked_accounts` = '".$new_val."' WHERE `name` = '".$row['name']."'");
						$accts = explode(";",$new_val);
						foreach ($accts as $key => $value) {			
							$this_val = str_replace($value, $row[name], $new_val);
							mysql_query("UPDATE `users` SET `linked_accounts` = '".$this_val."' WHERE `name` = '".$value."'");
							$message = "<font color='green'>Link added!.</font>";
						}
					}
				} else {
					mysql_query("UPDATE `users` SET `linked_accounts` = '".$link_row['name']."' WHERE `name` = '".$row['name']."'");
					mysql_query("UPDATE `users` SET `linked_accounts` = '".$row['name']."' WHERE `name` = '".$link_row['name']."'");
					$message = "<font color='green'>Link added!.</font>";				
				}			
			} else {
				$message = "<font color='red'>Link attempt failed: Passwords do not match.</font>";	
			}
		} else {
			$message = "<font color='red'>Link attempt failed: Account not found.</font>";
		}
	}
}

if ($_POST['remove_link']){
	$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
	$row = mysql_fetch_array($result);
	$accts = explode(";",$row['linked_accounts']);
	
	$links = str_replace($_POST['remove_link'],"", $row['linked_accounts']);
	$links = clean_semicolons($links);
	mysql_query("UPDATE `users` SET `linked_accounts` = '".$links."' WHERE `name` = '".$user."'");
	
	foreach ($accts as $key => $value) {
		if ($value == $_POST['remove_link']){
			mysql_query("UPDATE `users` SET `linked_accounts` = '' WHERE `name` = '".$value."'");
		} else {
		$this_result = mysql_query("SELECT * FROM users WHERE name = '".$value."'");
		$this_row = mysql_fetch_array($this_result);
		$links = str_replace($_POST['remove_link'],"",$this_row['linked_accounts']); 
		$links = clean_semicolons($links);
		mysql_query("UPDATE `users` SET `linked_accounts` = '".$links."' WHERE `name` = '".$value."'");
		}
	}
	$message = $_POST['remove_link'] . " has been unlinked.";
}

$result = mysql_query("SELECT * FROM users WHERE name = '".$user."'");
$row = mysql_fetch_array($result);
$linked_accounts = $row['linked_accounts'];
$mode = $row['mode'];

$max_missing = $row['missing'];
if ($row['sort1'] == "institute"){
	$orderby = $row['sort1'].", clone";
} else {
	$orderby = "clone";	
}

$goals = array();
for ($i=1; $i<=6; $i++){
	$field = "goal".$i;
	if ($row[$field]){
		$goals[] = $row[$field];
	}
}


function clean_semicolons($links){
	$links = str_replace(";;",";",$links);
	if (substr($links, 0,1) == ";"){
		$links = substr($links, 1, strlen($links)-2);
		echo $links . "<br>";
	}
	if (substr($links, strlen($links)-1,1) == ";"){
		$links = substr($links, 0, strlen($links)-1);
	}
	return $links;
}

function chest_name($chest){
	switch ($chest) {
		case 'four_systems':
			return "4 Systems Precious Hero Option";
		case 'royal_casino':
			return "Royal Casino Bag";
		case 'co_ordinary':
			return "Chaos & Order Ordinary Hero Selection";
		case 'co_reg':
			return "Chaos & Order Heroes Chest";
		case 'co_reg2':
			return "Chaos & Order Heroes Chest No.2";
		case 'rare_co':
			return "Rare Order & Chaos Hero Option Box";
		case 'precious_light':
			return "Light Precious Hero Option";	
		case 'precious_dark':
			return "Dark Precious Hero Option";
		case 'dragon':
			return "Dragon Chest Selection";
		case 'anniversary':
			return "Anniversary Options Box";
		case '8th_anni':
			return "8th Anniversary Chest";	
		case 'drag_options':
			return "Dragon Options Box";					
	}	
}
?>