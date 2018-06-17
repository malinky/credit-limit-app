<?php
	require __DIR__ . '/vendor/autoload.php';

    use AWSCognitoApp\AWSCognitoWrapper;

    $wrapper = new AWSCognitoWrapper();
    $wrapper->initialize();

    if(isset($_POST['action'])) {
        $username = $_POST['username'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        $error = $wrapper->confirmSignup($username, $confirmation);

        if(empty($error)) {
            header('Location: index.php');
        }
    }

    $username = $_GET['username'] ?? '';
?>

<html>
<head>
	<title>XD18 | Credit Limits</title>
	<meta charset="utf-8">
	<link rel="icon" type="image/png" href="UI/favicon.ico">
	<link rel="stylesheet" type="text/css" href="UI/styles/normalize.css">
	<link rel="stylesheet" type="text/css" href="UI/styles/main.css">
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">
	<body>
		<nav>
			<ul>
				<li><img src="UI/styles/logo-xero-blue.svg" alt="Xero Logo"></li>
				<li style="float:right"><a href="about.php">About</a></li>
			</ul> 
		</nav>
        <p style='color: red;'><?php echo $error;?></p>
        <h1>Confirm signup</h1>
        <form method='post' action=''>
            <p>
            <input type='text' placeholder='Username' name='username' value='<?php echo $username;?>' /><br />
            <input type='text' placeholder='Confirmation code' name='confirmation' /><br />
            <input type='hidden' name='action' value='confirm' />
            <input type='submit' value='Confirm' />
            </p<>
        </form>
    </body>
</html>
