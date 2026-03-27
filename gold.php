<?
include 'top_php.php';
$inc = 0;
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
		.summary{
			text-align: center;	
			width: 365px;
		}
		.summary td{
			padding: 5px;
			text-align: left;
			vertical-align: middle;
			color: #000;
			border: 1px solid #000;
			white-space: nowrap;
			font-size: 13px;
			font-weight: bold;
		}
		</style>
		<main class="site-content" role="main">
            <section id="service">
                <div class="container">
                    <div class="row">     
                        <br /><br />  	
                        <div class="sec-title text-center" style="padding-top: 30px;">
                            <h2 class="wow animated bounceInLeft" style="margin-bottom: -20px;"><font color="yellow" <? if ($mode !== "dark"){ ?>style="-webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black;"<? }?>>Gold Clones</font></h2>
                            <h5>Note: This program assumes you have the neccessary fodder.</h5>
                        </div>
					</div>
                    <div class="row"> 
                        <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
        					<h3 class="wow animated bounceInLeft">Able To Make</h3>
                            <center>
                            <table class="summary">
                            	<tr><td colspan="3" style="background-color: gold;"><center><b>Gold Clones</b></center></td></tr>
                                <?
								$result = mysql_query("SELECT * FROM gold_missing WHERE user = '".$user."' AND total_missing = '0' ORDER BY ".$orderby);
								while ($row = mysql_fetch_array($result)){
									$inc++;
									$color_result = mysql_query("SELECT * FROM gold WHERE clone = '".$row['clone']."'");
									$color_row = mysql_fetch_array($color_result);		
									switch ($color_row['institute']) {
										case 'Lightning':
											$color = "#b4c6e7";
											break;
										case 'Fire':
											$color = "#f2aaaa";
											break;
										case 'Earth':
											$color = "#92d050";
											break;
										case 'Water':
											$color = "#00b0f0";
											break;
										case 'Chaos':
											$color = "#cc99e0";
											break;
										case 'Order':
											$color = "#ffff00";
											break;
									}
									$font_shrink = "";
                                    if (strlen($row['clone']) >= 19) {
                                        $font_shrink = " font-size: 11px;";
                                    }			
									?>
									<tr>
										<td style="background-color: <? echo $color;?>; border-right: none;"><center><img src='img/purples/<? echo $row['clone'];?>.jpg' height='40' style="padding-right: 0px;"></center></td>
                                        <td style="background-color: <? echo $color;?>; border-left: none;">
                                        	<font style=" <? echo $font_shrink;?>">
				                            	<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="text-decoration: none; color: black;"><? echo $row['clone'];?></a>
                                            </font>
											<?
											$total_chests = 0;
											$previous_chest = "";
											$output = "";
											if ($row['chests_used']){
												$chests_used_database = array_filter(explode(";",$row['chests_used']));
												foreach ($chests_used_database as $chest){
													$chest_details = explode(",",$chest);
													$total_chests = $total_chests + $chest_details[2];
													if ($chest_details[0] !== $previous_chest){
														$chest_name = chest_name($chest_details[0]);
														$output = $output."<font color='yellow'>".$chest_name.":</font><br />";
													}
													$output = $output.$chest_details[2]."x ".$chest_details[1]."<br />";
													$previous_chest = $chest_details[0];
												}
												$lines = substr_count($output,"<br />");
												$margin = $lines * -25;
												?>
                                                <style>
													/* Tooltip container */
													.tooltipp<? echo $inc;?> {
													  position: relative;
													  display: inline-block;
													}
													
													/* Tooltip text */
													.tooltipp<? echo $inc;?> .tooltiptext<? echo $inc;?> {
													  visibility: hidden;
													  background-color: black;
													  color: #fff;
													  text-align: left;
													  padding: 10px;
													  border-radius: 6px;
													  /* Position the tooltip text - see examples below! */
													  position: absolute;
													  z-index: 1;
													}
													
													/* Show the tooltip text when you mouse over the tooltip container */
													.tooltipp<? echo $inc;?>:hover .tooltiptext<? echo $inc;?> {
													  visibility: visible;
													  margin-top: <? echo $margin;?>px;
													  /* Lines: <? echo $lines;?> */
													}
												
												</style>
												<div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                            	<font style="font-size: 10px;">
                                                <div class="tooltipp<? echo $inc;?>"><font color='red'><? echo $total_chests;?> chests used.</font>&nbsp;&nbsp;
  													<span class="tooltiptext<? echo $inc;?>">
														<?
														echo $output;
														?>
                                                    </span>
												</div>
                                                <?						
											}
											?>
                                            </font>
                                        </td>                                        	
										<td style="background-color:#CCC;"><center><a class="fancybox btn btn-primary" href="https://olfactoryhues.com/clone-evolution/craft_gold.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>">Make It!</a></td>
									</tr>
									<?
								}
                                ?>
                            </table>
                            </center>
                        </div>
                        <?
						if ($max_missing < 5){
							$miss_cnt = $max_missing;	
						} else {
							$miss_cnt = 5;
						}
                        for ($i=1; $i<=$miss_cnt; $i++){
							if ($i == 3 or $i == 6 or $i == 9 or $i == 12){
								?>
                    </div>
                    <div class="row"> 
                            	<?	
							}
							?>
                            <div class="col-md-4 col-sm-8 col-xs-16 text-center wow animated zoomIn" style="padding-bottom: 10px;">
                                <h3 class="wow animated bounceInLeft">Missing <? echo $i;?> Clone<? if ($i > 1){ echo "s"; }?></h3>
                                <center>
                                <table id="need1" class="summary">
                                    <tr><td colspan="3" style="background-color: gold;"><center><b>Gold Clones</b></center></td></tr>
                                    <?
									$result = mysql_query("SELECT * FROM gold_missing WHERE user = '".$user."' AND total_missing = '".$i."' ORDER BY ".$orderby);
									while ($row = mysql_fetch_array($result)){
										$inc++;
										$color_result = mysql_query("SELECT * FROM gold WHERE clone = '".$row['clone']."'");
										$color_row = mysql_fetch_array($color_result);
										switch ($color_row['institute']) {
											case 'Lightning':
												$color = "#b4c6e7";
												break;
											case 'Fire':
												$color = "#f2aaaa";
												break;
											case 'Earth':
												$color = "#92d050";
												break;
											case 'Water':
												$color = "#00b0f0";
												break;
											case 'Chaos':
												$color = "#cc99e0";
												break;
											case 'Order':
												$color = "#ffff00";
												break;
										}
										$font_shrink = "";
										if (strlen($row['clone']) >= 19) {
											$font_shrink = " font-size: 11px;";
										}
										?>
										<tr>
											<td style="background-color: <? echo $color;?>; border-right: none;"><center><img src='img/purples/<? echo $row['clone'];?>.jpg' height='40' style="padding-right: 0px;"></center></td>
                                            <td style="background-color: <? echo $color;?>; border-left: none;">
                                            	<font style=" <? echo $font_shrink;?>">
					                            	<a class="fancybox" href="https://olfactoryhues.com/clone-evolution/gold_recipe.php?clone=<? echo $row['clone'];?>" data-fancybox-type="iframe" title="<? echo $row['clone'];?>" style="text-decoration: none; color: black;"><? echo $row['clone'];?></a>
                                                </font>
												<?
                                                $total_chests = 0;
                                                $previous_chest = "";
                                                $output = "";
                                                if ($row['chests_used']){
                                                    $chests_used_database = array_filter(explode(";",$row['chests_used']));
                                                    foreach ($chests_used_database as $chest){
                                                        $chest_details = explode(",",$chest);
                                                        $total_chests = $total_chests + $chest_details[2];
                                                        if ($chest_details[0] !== $previous_chest){
                                                            $chest_name = chest_name($chest_details[0]);
                                                            $output = $output."<font color='yellow'>".$chest_name.":</font><br />";
                                                        }
                                                        $output = $output.$chest_details[2]."x ".$chest_details[1]."<br />";
                                                        $previous_chest = $chest_details[0];
                                                    }
													$lines = substr_count($output,"<br />");
													$margin = $lines * -25;
													?>
													<style>
														/* Tooltip container */
														.tooltipp<? echo $inc;?> {
														  position: relative;
														  display: inline-block;
														}
														
														/* Tooltip text */
														.tooltipp<? echo $inc;?> .tooltiptext<? echo $inc;?> {
														  visibility: hidden;
														  background-color: black;
														  color: #fff;
														  text-align: left;
														  padding: 10px;
														  border-radius: 6px;
														  /* Position the tooltip text - see examples below! */
														  position: absolute;
														  z-index: 1;
														}
														
														/* Show the tooltip text when you mouse over the tooltip container */
														.tooltipp<? echo $inc;?>:hover .tooltiptext<? echo $inc;?> {
														  visibility: visible;
														  margin-top: <? echo $margin;?>px;
														  /* Lines: <? echo $lines;?> */
														}
													
													</style>
                                                    <div style="max-height: 1px; height: 1px; margin-top: -10px;"><font style="font-size: 1px;">&nbsp;</font></div>
                                                	<font style="font-size: 10px;">
                                                    <div class="tooltipp<? echo $inc;?>"><font color='red'><? echo $total_chests;?> chests used.</font>&nbsp;&nbsp;
                                                        <span class="tooltiptext<? echo $inc;?>">
                                                            <?
                                                            echo $output;
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <?						
                                                }
                                                ?>
                                                </font>
                                            </td>                                        	
											<td style="background-color:#CCC;">
												<?
												$cnt = 1;
												$m1 = 'missing'.$cnt;
												$font_shrink = "off";
												if (strlen($row[$m1]) >= 19) {
													$font_shrink = "on";
												}
												$m2 = 'missing'.$cnt.'_qty';
												$miss_qty = $row[$m2];
												while ($miss_qty !== "0"){
													if ($font_shrink == "on"){
														echo "<font style='font-size: 11px;'>";
													}
													echo $row[$m1]." (x".$row[$m2].")<br />";
													if ($font_shring == "on"){
														echo "</font>";
													}
													$cnt++;
													$m1 = 'missing'.$cnt;
													$font_shrink = "off";
													if (strlen($row[$m1]) >= 19) {
														$font_shrink = "on";
													}
													$m2 = 'missing'.$cnt.'_qty';													
													$miss_qty = $row[$m2];
												}
												?>
											</td>
										</tr>
										<?
									}	
									?>
                                </table>
                                </center>
                            </div>							
							<?
						}
                        ?>
                    </div>
                </div>
            </section>
		</main>	
        
		<? include 'footer.php';?>
	
    </body>
</html>