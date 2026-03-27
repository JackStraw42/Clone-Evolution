<?
include 'top_php.php';

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
                        <div class="sec-title text-center" style="padding-top: 30px; margin-bottom: -20px;">
                            <h2 class="wow animated bounceInLeft">Tier List</h2>
                        </div>
					</div>
                    <div class="row"> 
                        <?
						$institute = array("Order","Chaos","Lightning","Fire","Earth","Water");
						$tier = array("X","S","A","B","C","D","F");
						?>
                        <center>
                        <table>
                        	<?
							foreach($tier as $t){
								?>
								<tr>
									<td style="padding: 10px; vertical-align: middle; background-color: #FFF; border: 3px solid;"><center><b><? echo $t;?></b></center></td>
									<?
									foreach ($institute as $inst){
										$result = mysql_query("SELECT * FROM red WHERE tier = '".$t."' AND institute = '".$inst."' ORDER BY clone");
										while ($row = mysql_fetch_array($result)){
											switch ($row['institute']) {
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
											?>     
											<td style="background-color: <? echo $color;?>; width: 80px; padding: 5px; border: 3px solid; v-align: top; vertical-align: top; line-height: 10px;">
												<center>
												<img src='img/reds/<? echo $row['clone'];?>.jpg' height='60'><br />
												<font style="font-size: 10px; font-weight: bold;"><? echo $row['clone'];?></font>
												</center>
											</td>
											<?
										}
									}
								?>
								</tr>
                                <tr><td style="height: 5px;"></td></tr>
							<?
							}
							?>
                        </table>
                        </center>
                	</div>                
                </div>
            </section>
		</main>	
        <? include 'footer.php';?>
    </body>
</html>