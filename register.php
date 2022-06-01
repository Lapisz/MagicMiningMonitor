<html>
<head>
	<title>Register</title>
<?PHP
require __DIR__ . "/db_accessor.php";
require __DIR__ . "/session_manager.php";

session_start();

if(mmm_check_session()){
	redirect_to("/index.php");
	die();
}

function process_register($username, $password, $confirm){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

	if($username == "") {
		print_message("Error registering: Please enter a username");
		return;
	}

	$user_in_db = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $username], "username");
	if($user_in_db == $username){
		print_message("Error registering: Username already taken");
		return;
	}
	
	if(strlen($password) > 72){
		print_message("Error registering: Password max length is 72 characters");
		return;
	}
	if(strlen($password) < 6){
		print_message("Error registering: Please use a password length of 6-72 characters");
		return;
	}

	if($confirm != $password){
		print_message("Error registering: The password confirmation does not match the password");
		return;
	}

	$hashed_password = password_hash($password, PASSWORD_BCRYPT);

	$docs_written = mmm_generic_write($manager, "local.mmm_credentials", ["username" => $username, "password" => $hashed_password]);
	if(($docs_written -> getInsertedCount()) == 0){
		print_message("Error registering: Something failed in mmm_generic_write, data not saved");
		return;
	}

	mmm_start_session($username);
	print_message("Success! Redirecting...");
	redirect_to("/index.php");
	die();
}

if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["login_button"])){
	process_register($_POST["user"], $_POST["pass"], $_POST["confirm_pass"]);	
}
?>
</head>
<body>
<noscript>Javascript needs to be enabled for this page to work!</noscript>
<script type="text/javascript">
	if(navigator.cookieEnabled != true) document.write("Cookies must be enabled for this page to work!");
</script>

<h2><u>Register</u></h2><br />
	
<form autocomplete="off" action="register.php" method="post">
	<label for="user">Username: </label>
	<input type="text" id="user" name="user"><br />
	<label for="pass">Password: </label>
	<input type="password" id="pass" name="pass"><br />
	<label for="confirm_pass">Confirm Password: </label>
	<input type="password" id="confirm_pass" name="confirm_pass"><br /><br />
	<input type="submit" id="login_button" name="login_button" value="Login">
</form>
<br />

<h4>Already have an account? <a href="/login.php">Login</a><br />
<!-- <a href="/index.php">Return to homepage</a> --></h4>
</body>
</html>
