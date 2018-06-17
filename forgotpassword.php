<?php
	require __DIR__ . '/vendor/autoload.php';

    use AWSCognitoApp\AWSCognitoWrapper;

    $wrapper = new AWSCognitoWrapper();
    $wrapper->initialize();

    $entercode = false;

    if(isset($_POST['action'])) {

        if($_POST['action'] === 'code') {
            $username = $_POST['username'] ?? '';

            $error = $wrapper->sendPasswordResetMail($username);

            if(empty($error)) {
                header('Location: forgotpassword.php?username=' . $username);
            }
        }

        if($_POST['action'] == 'reset') {

            $code = $_POST['code'] ?? '';
            $password = $_POST['password'] ?? '';
            $username = $_GET['username'] ?? '';

            $error = $wrapper->resetPassword($code, $password, $username);

            // TODO: show message on new page that password has been reset
            if(empty($error)) {
                header('Location: index.php?reset');
            }
        }
    }

    if(isset($_GET['username'])) {
        $entercode = true;
    }
?>

<!doctype html>
<html>
<head>
        <meta charset='utf-8'>
        <meta http-equiv='x-ua-compatible' content='ie=edge'>
	    <title>CreditLimits app</title>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script src="http://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js"  crossorigin="anonymous"></script>
    </head>
    <body>
		<div id="req" class="container">
        <p style='color: red;'><?php echo $error;?></p>
        <?php if($entercode) { ?>
        <h1>Reset password</h1>
        <p>If your account was found, an e-mail has been sent to the associated e-mailadres. Enter the code and your new password.</p>
        <form method='post' action=''>
            <input type='text' placeholder='Code' name='code' /><br />
            <input type='password' placeholder='Password' name='password' /><br />
            <input type='hidden' name='action' value='reset' />
            <input type='submit' value='Reset password' />
        </form>
        <?php } else { ?>
        <h1>Forgotten password</h1>
        <p>Enter your username and we will sent you a reset code to your e-mailadres.</p>
        <form method='post' action=''>
            <input type='text' placeholder='Username' name='username' /><br />
            <input type='hidden' name='action' value='register' />
            <input type='hidden' name='action' value='code' />
            <input type='submit' value='Receive code' />
        </form>
        <?php }?>
        </div>
    </body>
</html>
