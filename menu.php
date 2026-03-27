        <header id="navigation" class="navbar-inverse navbar-fixed-top animated-header">
            <div class="container">
                <div class="navbar-header">
                    <!-- responsive nav button -->
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
                    </button>
					<!-- /responsive nav button -->
					
					<!-- logo -->
					<h1 class="navbar-brand">
						<img src="img/ghoti.jpg" height="60" style="float: left; margin-top: -20px;"> &nbsp;&nbsp;&nbsp;<font color="#FFFFFF">Hero Tracker</font> by Ghoti
					</h1>
					<!-- /logo -->
                </div>
				<!-- main nav -->
                <nav class="collapse navbar-collapse navbar-right" role="navigation">
                    <ul class="nav navbar-nav">
                        <li><a href="https://olfactoryhues.com/clone-evolution/">Inventory</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/chests.php">Chests</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/gold.php">Gold Clones</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/red.php">Red Clones</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/awaken.php">Awaken</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/goals.php">Goals</a></li>
                        <li><a href="https://olfactoryhues.com/clone-evolution/settings.php">Settings</a></li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                Other Tools <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">                           	
                            	<li><a href="https://olfactoryhues.com/clone-evolution/inventory_space.php">Inventory Space</a></li>
                        		<li><a href="https://olfactoryhues.com/clone-evolution/testing.php">Testing</a></li>
                                <li><a href="https://olfactoryhues.com/clone-evolution/chips.php">Chips</a></li>
                                <li><a href="https://olfactoryhues.com/clone-evolution/tier_list.php">Tier List</a></li>
                                <li><a href="https://olfactoryhues.com/clone-evolution/magical_exchange.php">Magical Exchange</a></li>
                            </ul>
                        </li>        
                    </ul>
                </nav>
				<!-- /main nav -->
            </div>
            <center>
            	<table>
                	<tr>
                    	<td valign="middle"><font color="white">
                        	<?
							if ($linked_accounts == ""){
								?>
	                        	You are logged in as: </font><font color="yellow"><? echo $user;?></font>
                                <?
							} else {
								?>
                                <form name="switch_accounts" method="post">
                                	You are logged in as: 
                                	<select name="switch_to_acct" style="color: #000;" onchange="this.form.submit()">
                                    	<option value="<? echo $user;?>" SELECTED><? echo $user;?></option>
                                        <?
                                        $accts_menu = explode(";",$linked_accounts);
										usort($accts_menu, 'strnatcasecmp');
										foreach ($accts_menu as $value) {
											?>
                                            <option value="<? echo $value;?>"><? echo $value;?></option>
                                            <?
										}
                                        ?>
                                    </select>
                                </form>
                               	<?
							}
							?>
                        </td>
                       	<td valign="middle" align="center" style="width: 100px;">
                            <form name="logout" method="get" action="https://olfactoryhues.com/clone-evolution/login.php">
                                <input type="hidden" name="func" value="logout" />
                                <button type="submit" style="font-size: 12px;">Log Out</button>
                            </form>
                        </td>
                    </tr>
				</table>
            </center>
        </header>