<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body>



<img src="images/login_background.jpg" id="login_body">
<div class="databox_error" id="login">
	<br>
	<h1 id="log">Problem with Pandora FMS database</h1>
	<br>
	<div style="width: 440px; margin: 0 auto auto;">
		<table cellpadding="4" cellspacing="1" width="440" class="table_error">
		<tr><td align="center">
			<a href="index.php"><img src="images/pandora_login.png" border="0" alt="Pandora FMS"></a>
		</td></tr><tr>
		<td valign="bottom">
			<div style="overflow-y: auto; overflow-x: visible; height: 120px; width: 300px; margin: 0 auto; padding-right: 20px;">
				Cannot connect to the database, please check your database setup in the <b>include/config.php</b> file.<i><br/><br/>
				Probably your database, hostname, user or password values are incorrect or 
				the database server is not running.</i><br /><br /><span style='line-height: 18px' class="error">
					<b>DB ERROR:</b>
					<?php
					echo db_get_last_error();
					?></span>
					<div class="msg">
					If you have modified auth system, this problem could be because Pandora cannot override authorization variables from the config database. Remove them from your database by executing:<br><pre>DELETE FROM tconfig WHERE token = "auth";</pre>
					</div>
				</div>	
			</div>
		</td></tr>
		</table>
	</div>
	<br>

</div>

<div id="bottom_logo">
	<img src='images/bottom_logo.png' "alt" = "logo"  "border" = "0">
</div>

</body>
</html>