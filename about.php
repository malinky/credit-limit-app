<?php
	session_start();
?>

<html>
<head>
	<title>XD18 | About</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/png" href="favicon.ico">
	<link rel="stylesheet" type="text/css" href="UI/styles/normalize.css">
	<link rel="stylesheet" type="text/css" href="UI/styles/main.css">
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">
</head>
	<body>
		<nav>
        <?php
				if($_SESSION['user'] === NULL)
				{
					echo '<ul>';
					echo '	<li><img src="UI/styles/logo-xero-blue.svg"></li>';
					echo '	<li><a href="index.php">Home</a></li>';
					echo '	<li style="float:right"><a href="about.php">About</a></li>';
					echo '</ul>'; 					
				}
				else
				{
					echo '<ul>';
					echo '	<li><img src="UI/styles/logo-xero-blue.svg"></li>';
					echo '	<li><a href="limits.php">Credit Limits</a></li>';
					echo '	<li><a href="history.php">History</a></li>';
					echo '	<li><a href="settings.php">Settings</a></li>';
					echo '	<li style="float:right"><a href="about.php">About</a></li>';
					echo '	<li style="float:right"><a href="settings.php">User: '.$_SESSION['user'].'</a> </li> ';
					echo '</ul> ';
				}
            ?> 
		</nav>
		<h1>About</h1>
		<iframe width="560" height="315" src="https://www.youtube.com/embed/bU1nU7rpz_8?autoplay=1&controls=0&playlist=bU1nU7rpz_8&loop=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
		<p>This Credit Limits App has been built as an API integration demo for the <a href="https://developer.xero.com/xd18" target="_blank">Xero Developer Roadshow 2018 (XD18)</a>. Once connected to Xero, the App returns a list of Xero Customers for you to apply a Credit Limit to. The credit limit value you set is stored in a database for later cross referenicing. When an Invoice is rasied in the Xero, the amount is checked against the applied credit limit and the Invoice is either Approved or Declined.</p>
		<p>The App use the Contacts endpoint, Invoice Webhooks, Invoices endpoint and the Email endpoint. This App has been built by James Coleman, Ben Glazier &amp; Robin Blackstone.</p>
	</body>
</html>
