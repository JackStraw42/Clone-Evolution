<?
$link = @mysql_connect('localhost', olfactor_jack, Dice_1234) or die('Error connecting to mysql');
mysql_select_db(olfactor_clone_evolution) or die("Error connecting to database");

$user = $_GET['user'];
$filename = $user."_export.csv";

$clones[] = array("Clone" => "Clone", "Purple Qty" => "Purple Qty", "Gold Qty" => "Gold Qty");
$result = mysql_query("SELECT * FROM inventory WHERE user='".$user."' ORDER BY clone");
while ($row = mysql_fetch_array($result)){
	$clones[] = array("Clone" => $row['clone'], "Purple Qty" => $row['purple_qty'], "Gold Qty" => $row['gold_qty']);
}

array_to_csv_download($clones, $filename, ",");



function array_to_csv_download($array, $filename, $delimiter) {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }
} 
fclose($f);
?>
