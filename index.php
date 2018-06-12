<?php
    session_start();
    require __DIR__ . '/vendor/autoload.php';

    use AWSCognitoApp\AWSCognitoWrapper;
    $wrapper = new AWSCognitoWrapper();
    $wrapper->initialize();

// env var print out
$env = getenv('environment');
error_log("Site using following env vars:",0);
if($env == "local") {
    // getting settings from httpd.conf
    error_log('apptype:'. getenv('apptype'),0);	
    error_log('callback:'. getenv('callback'),0);	
    error_log('consumer_key:'. getenv('consumer_key'),0);
    error_log('consumer_secret:'. getenv('consumer_secret'),0);
    error_log('region:'. getenv('region'),0);
    error_log('client_id:'.getenv('client_id'),0);
    error_log('userpool_id:'.getenv('userpool_id'),0);
    error_log('credentials_key:'. getenv('credentials_key'),0);
    error_log('credentials_secret:'. getenv('credentials_secret'),0);
    error_log('webhook_key:'. getenv('webhook_key'),0);
 }
 else
 {
     // we're running on aws, get settings from server variables
    error_log('apptype:'. $_SERVER['apptype'],0);	
    error_log('callback:'. $_SERVER['callback'],0);	
    error_log('consumer_key:'. $_SERVER['consumer_key'],0);
    error_log('consumer_secret:'. $_SERVER['consumer_secret'],0);
    error_log('region:'. $_SERVER['region'],0);
    error_log('client_id:'.$_SERVER['client_id'],0);
    error_log('userpool_id:'.$_SERVER['userpool_id'],0);
    error_log('webhook_key:'.$_SERVER['webhook_key'],0);
 }	
// env var print out

    if(isset($_POST['action'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if($_POST['action'] === 'register') {
            $email = $_POST['email'] ?? '';
            $error = $wrapper->signup($username, $email, $password);

            if(empty($error)) {
                header('Location: confirm.php?username=' . $username);
                exit;
            }
        }

        if($_POST['action'] === 'login') {
            $error = $wrapper->authenticate($username, $password);

            if(empty($error)) {
                header('Location: settings.php');
                exit;
            }
        }
    }

    $message = '';
    if(isset($_GET['reset'])) {
        $message = 'Your password has been reset. You can now login with your new password';
    }
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
        <?php 
        if(!is_null($error))
        {
            echo "<p style='color: red;'>";
            echo trim(trim(substr($error,strrpos($error, "message")+9),"}"),'"');
            echo "</p>";
        }      
        ?>       
        <p style='color: green;'><?php echo $message;?></p>

        <form method='post' action=''>
            <h1>Register</h1>
            <p>
            <input type='text' placeholder='Username' name='username' autocomplete="off" /><br />
            <input type='text' placeholder='Email' name='email' autocomplete="off"/><br />
            <input type='password' placeholder='Password' name='password' autocomplete="off"/><br />
            <input type='hidden' name='action' value='register' />
            <input type='submit' value='Register' />
            </p>
        </form>

        <form method='post' action=''>
        <h1>Login</h1>
            <p>
            <input type='text' placeholder='Username' name='username' autocomplete="off"/><br />
            <input type='password' placeholder='Password' name='password'autocomplete="off" /><br />
            <input type='hidden' name='action' value='login' />
            <input type='submit' value='Login' />
            </p>
        </form>
        <p><a href='/CreditLimits/forgotpassword.php'>Forgot password?</a></p>

		</div>
	</body>
</html>
