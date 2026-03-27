<?
include 'top_php.php';
if (isset($_POST['clone_to_trade'])){
	$result = mysql_query("SELECT * FROM red WHERE clone = '".$_POST['clone_to_trade']."'");
	$row = mysql_fetch_array($result);
	$color = $row[me_color];
	$value = $row[me_value];
	$multiplier = 1;
	if ($_POST['stars'] == 10){
		$multiplier = 2;
	} elseif ($_POST['stars'] == 11){
		$multiplier = 3;
	}
	if ($color == "green"){
		$result = mysql_query("SELECT * FROM red WHERE (me_color = 'green' or me_color = 'orange') and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}
	} elseif ($color  == "orange"){
		$result = mysql_query("SELECT * FROM red WHERE (me_color = 'orange' or me_color = 'blue') and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}
	} elseif ($color  == "blue"){
		$result = mysql_query("SELECT * FROM red WHERE (me_color = 'blue' or me_color='white') and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}		
	} elseif ($color  == "white"){
		$result = mysql_query("SELECT * FROM red WHERE (me_color = 'white' or me_color='red') and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}
	} elseif ($color  == "red"){
		if ($_POST['clone_to_trade'] == "Shutin Doji" OR $value >= 128){
			$result = mysql_query("SELECT * FROM red WHERE (me_color = 'red' or me_color='purple') and me_value > '".$value."' ORDER BY me_value");
		} else {
			$result = mysql_query("
				SELECT * FROM red 
				WHERE (me_color = 'red' or me_color='purple') and me_value > '".$value."'
				UNION
				SELECT * FROM red 
				WHERE clone = 'Shutin Doji'
				ORDER BY me_value
			");
		}
		while ($row = mysql_fetch_array($result)){
			$me_value = $row['me_value'];
			if ($row['clone'] == "Shutin Doji"){
				$me_value = $row['me_value'] + 20;
			}
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($me_value - $value) * $multiplier;
		}
		array_multisort($trade_prices, SORT_ASC, SORT_NUMERIC, $trade_options);
	} elseif ($color  == "purple"){
		$result = mysql_query("SELECT * FROM red WHERE me_color='purple' and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}
	} elseif ($color == "dragon"){
		$result = mysql_query("SELECT * FROM red WHERE me_color='dragon' and me_value > '".$value."' ORDER BY me_value");
		while ($row = mysql_fetch_array($result)){
			$trade_options[] = $row['clone'];
			$trade_prices[] = ($row['me_value'] - $value) * $multiplier;
		}
	}
}

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
			.chart{
			  border: 1px solid black; 
			}
			
			.chart td {
				padding-left: 6px;	
				padding-right: 6px;
			}
			.rules td{
				padding-left: 6px;	
				padding-right: 6px;				
			}
			
		</style>
		<main class="site-content" role="main">
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br />  	
                        <div class="sec-title text-center" style="padding-top: 30px; margin-bottom: -20px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -30px;">Magical Exchange</h2>
                            <h3>Values & Rules are subject to change.<br />
                            Wait for the event before crafting clones to trade.</h3>
                            <br />
                        </div>
					</div>
                    <div class="row"> 
                    	<center>
                        <br />
                        <h3>Quick Calculator:</h3>
                        <h4>
                        <form name="magical_exchange" method="post">
                        	<table>
                            	<tr>
                                	<td align='right'>Clone:</td>
                                    <td align='left' style="padding-left: 10px;">
                                        <select name="clone_to_trade" style="color: #000;" onchange="this.form.submit()">
                                            <option value="">Select A Clone</option>
                                            <?
                                            $result = mysql_query("SELECT * FROM red WHERE me_value != '0' ORDER BY clone");
                                            while ($row = mysql_fetch_array($result)){						
                                                ?>
                                                <option value="<? echo $row['clone'];?>" <? if ($_POST['clone_to_trade'] == $row['clone']){ echo "SELECTED"; }?>><? echo $row['clone'];?></option>
                                                <?
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
									<td align='right' style="padding-top: 10px;">Stars:</td>
                                    <td align='left' style="padding-top: 10px; padding-left: 10px;">
                                        <select name="stars" style="color: #000;" onchange="this.form.submit()">
                                            <option value="9">8/9</option>
                                            <option value="10" <? if ($_POST['stars'] == 10){ echo "SELECTED"; }?>>10</option>
                                            <option value="11" <? if ($_POST['stars'] == 11){ echo "SELECTED"; }?>>11</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                	<td align='right' style="padding-top: 10px;">Options:</td>
                                    <td align='left' style="padding-top: 10px; padding-left: 10px;">
                                        <select name="clone_to_receive" style="color: #000;">
                                            <?
                                            if ($_POST['clone_to_trade']){
                                                foreach ($trade_options as $key => $value){
                                                    $word = "tokens";
                                                    if ($trade_prices[$key] == 1){
                                                        $word = "token";
                                                    }
                                                    ?>
                                                    <option value="<? echo $value;?>"><? echo $value . " (" . $trade_prices[$key] . " " . $word . ")";?></option>
                                                    <?	
                                                }
                                            } else {
                                                ?>
                                                <option value="">Select A Clone First</option>
                                                <?	
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        </h4>
                        </center>
                        <br /><br />
                    </div>
                    <div class="row"> 
						<center>
                        <table>
                        	<tr>
                            	<td colspan="3">
                                	<center>
                                    <table class="rules">
                                        <tr>
                                            <td style="background-color: #FFF; border: 1px solid black;">To Calculate Price: Subtract the value of the clone you have from the one you want.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #FFF; border: 1px solid black;">Clones cannot be traded for clones with the same or lower value than themselves.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #FFF; border: 1px solid black;">Clones cannot be traded for clones that proceed them in the list.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #a9d08e; border: 1px solid black;">These clones can only trade up to Medusa or below.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #f4b084; border: 1px solid black;">These clones can only trade up to Thanatos or below.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #8ea9db; border: 1px solid black;">Clones in the left column can not be traded for the newly added clones in red or purple.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: red; border: 1px solid black;">Clones in red can only be traded for other clones in red or purple.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #c91dee; border: 1px solid black;">Only clones in red or purple can be traded for clones in purple.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #c91dee; border: 1px solid black;">Purple clones can only be traded for other purple clones.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #c91dee; border: 1px solid black; color: yellow;">>>> Value if trading Shutin Doji for another clone. >>></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #000000; border: 1px solid black; color: yellow;"><<< Value if trading a clone for Shutin Doji <<<</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: yellow; border: 1px solid black;">Dragons & Twins only trade among themselves, and can only trade up 1 space at a time.</td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: #FFF; border: 1px solid black;"><center><b>**Awakened clones do not follow the same rules or even the same clone order.**</b></center></td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                    </center>
                                </td>
                            </tr>
                        	<tr>
                            	<td valign="top" align="right">
                                    <table class="chart">
                                        <tr class="chart" style="background-color: #000;">
                                            <td class="chart" style="color: #FFF;" width="197">Clone</td>
                                            <td class="chart" style="color: #FFF;">*/**</td>
                                            <td class="chart" style="color: #FFF;">***</td>
                                            <td class="chart" style="color: #FFF;">****</td>
                                        </tr>
                                        <?
                                        $result = mysql_query("SELECT * FROM red WHERE me_color = 'green' AND me_value != '0' ORDER BY me_value");
                                        while ($row = mysql_fetch_array($result)){
                                            ?>
                                            <tr  class="chart" style="background-color: #a9d08e;">
                                                <td class="chart" align="left" width="197"><? echo $row['clone'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 2;?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 3;?></td>
                                            </tr>
                                            <?
                                        }
                                        $result = mysql_query("SELECT * FROM red WHERE me_color = 'orange' AND me_value != '0' ORDER BY me_value");
                                        while ($row = mysql_fetch_array($result)){
                                            ?>
                                            <tr  class="chart" style="background-color: #f4b084;">
                                                <td class="chart" align="left" width="197"><? echo $row['clone'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 2;?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 3;?></td>
                                            </tr>
                                            <?
                                        }
                                        $result = mysql_query("SELECT * FROM red WHERE me_color = 'blue' AND me_value != '0' ORDER BY me_value");
                                        while ($row = mysql_fetch_array($result)){
                                            ?>
                                            <tr  class="chart" style="background-color: #8ea9db;">
                                                <td class="chart" align="left" width="197"><? echo $row['clone'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 2;?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 3;?></td>
                                            </tr>
                                            <?
                                        }
                                        ?>
                                    </table>
                                </td>
                                <td lass="chart" style="background-color: #000;">&nbsp;</td>
                            	<td valign="top" align="left">
                                    <table class="chart">
                                        <tr class="chart" style="background-color: #000;">
                                            <td class="chart" style="color: #FFF;" width="197">Clone</td>
                                            <td class="chart" style="color: #FFF;">*/**</td>
                                            <td class="chart" style="color: #FFF;">***</td>
                                            <td class="chart" style="color: #FFF;">****</td>
                                        </tr>
                                        <?
                                        $result = mysql_query("SELECT * FROM red WHERE (me_color = 'white' OR me_color = 'red' OR me_color = 'purple') AND me_value != '0' ORDER BY me_value");
                                        while ($row = mysql_fetch_array($result)){
											if ($row['clone'] == "Ganesha"){
												$color = "black";
												?>
												<tr  class="chart" style="background-color: <? echo $color;?>;">
													<td class="chart" align="left" width="197"><font color="yellow"><<< Shutin Doji (In)</font color></td>
													<td class="chart" align="center"><font color="yellow">128</font color></td>
													<td class="chart" align="center"><font color="yellow">256</font color></td>
													<td class="chart" align="center"><font color="yellow">384</font color></td>
												</tr>
                                                <?									
											}
											
											if ($row['me_color'] !== ""){
												if ($row['me_color'] == "purple"){
													$color = "#c91dee";
												} else {
													$color = $row['me_color'];
												}
											} else {
												$color = "white";
											}
											?>
											<tr  class="chart" style="background-color: <? echo $color;?>;">
												<td class="chart" align="left" width="197">
													<?
													if ($row['clone'] == "Shutin Doji"){
														?>
														<font color="yellow">>>> 
														<?	
													}
													echo $row['clone'];
													if ($row['clone'] == "Shutin Doji"){
														?>
														 (Out)</font>
														<?	
													}
													?>
												</td>
												<td class="chart" align="center"><? echo $row['me_value'];?></td>
												<td class="chart" align="center"><? echo $row['me_value'] * 2;?></td>
												<td class="chart" align="center"><? echo $row['me_value'] * 3;?></td>
											</tr>
											<?
                                        }
                                        ?>
                                        <tr>
                                        	<td colspan="4" align="center" style="background-color: #000; color: #FFF;">Dragons/Twins</td>
                                        </tr>
                                        <?
                                        $result = mysql_query("SELECT * FROM red WHERE me_color = 'dragon' AND me_value != '0' ORDER BY me_value");
                                        while ($row = mysql_fetch_array($result)){
                                            ?>
                                            <tr  class="chart" style="background-color: yellow;">
                                                <td class="chart" align="left" width="197"><? echo $row['clone'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'];?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 2;?></td>
                                                <td class="chart" align="center"><? echo $row['me_value'] * 3;?></td>
                                            </tr>
                                            <?
                                        }
                                        ?>                
                                    </table>
                                </td>
                            </tr>
						</table>
                	</div>                
                </div>
            </section>
		</main>	
        <? include 'footer.php';?>
    </body>
</html>