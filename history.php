<?php
	require __DIR__ . '/vendor/autoload.php';
	require_once('database.php');
	require_once('storage.php');

	$storage = new StorageClass();

	// ensure History table exists
	$storage->createTable($dynamodb, "History", "orgid", "invoiceid_timestamp");

	// get history
	$history = $storage->getHistory($_SESSION['orgid'], $dynamodb, $marshaler);
?>

<html>
<head>
	<title>XD18 | History</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/png" href="UI/favicon.ico">
	<link rel="stylesheet" type="text/css" href="UI/styles/normalize.css">
	<link rel="stylesheet" type="text/css" href="UI/styles/main.css">
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">

	<!-- return to top button -->

	<script>
		// When the user scrolls down 20px from the top of the document, show the button
		window.onscroll = function() {scrollFunction()};

		function scrollFunction() {
		    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
		        document.getElementById("myBtn").style.display = "block";
		    } else {
		        document.getElementById("myBtn").style.display = "none";
		    }
		}

		// When the user clicks on the button, scroll to the top of the document
		function topFunction() {
		    document.body.scrollTop = 0;
		    document.documentElement.scrollTop = 0;
		}
	</script>

</head>
	<body>
		<nav>
			<ul>
				<li><img src="UI/styles/logo-xero-blue.svg"></li>
				<li><a href="limits.php">Credit Limits</a></li>
				<li><a href="history.php">History</a></li>
				<li><a href="settings.php">Settings</a></li>
                <li style="float:right"><a href="about.php">About</a></li>
				<li style="float:right"><a href="settings.php">User: <?php echo $_SESSION['user'];?></a> </li> 
			</ul> 
		</nav>

		<button onclick="topFunction()" id="myBtn" title="Go to top">Top</button>

		<h1>History</h1>

			<?php
				if(is_null($history))
				{
					echo "<p>No history yet! Set some limits and raise some invoices!</p>";
				}
				else
				{	
					echo "<table>";
					echo "<tr>";
					echo "	<th>Customer</th>";
					echo "	<th>Invoice</th>";
					echo "	<th>History</th>";
					echo "	<th>Limit Breached</th>";
					echo "	<th>Datetime</th>";
					echo "</tr>";	
					
					foreach ($history['Items'] as $record) {
						$recordItem = $marshaler->unmarshalItem($record);
						$invoiceid = $recordItem['info']['invoiceid'];
						$invoicenumber = $recordItem['info']['invoicenumber'];
						$contactname = $recordItem['info']['contactname'];
						$outcome = $recordItem['info']['outcome'];
						$limitbreached = $recordItem['info']['limitbreached'];
						$shortcode = $recordItem['info']['shortcode'];
						$timestamp = $recordItem['info']['timestamp'];
						echo "<tr>";
						echo "<td>".$contactname."</td>";
						echo "<td><a href='https://go.xero.com/organisationlogin/default.aspx?shortcode=".$shortcode."&redirecturl=/AccountsReceivable/view.aspx?InvoiceID=".$invoiceid."' target='_blank'>".$invoicenumber."</a></td>";
						echo "<td>".$outcome."</td>";
						if($limitbreached=="true"){echo "<td class='fail'>Yes</td>";}
						else{echo "<td class='success'>No</td>";}		
						echo "<td>".$timestamp."</td>";				
						echo "</tr>";
					}

					echo "</table>";
				}
			?>

	</body>
</html>
