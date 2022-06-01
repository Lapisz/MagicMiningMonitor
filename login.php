<html>
<head>
	<title>Login</title>
<?PHP
require __DIR__ . "/db_accessor.php";
require __DIR__ . "/session_manager.php";

$root_register_manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$root_in_db = mmm_generic_read($root_register_manager, "local.mmm_credentials", ["username" => "root"], "username");
if($root_in_db == NULL){
	print_message("Backend: Attempting to create default root user <br />");

	$hashed_root_password = password_hash("mmm_administrator", PASSWORD_BCRYPT);

	$root_write_result = mmm_generic_write($root_register_manager, "local.mmm_credentials", ["username" => "root", "password" => $hashed_root_password]);
	if(($root_write_result -> getInsertedCount()) == 0){
		print_message("Backend error: Default root user creation failed <br />");
	} else {
		print_message("Backend: Default root user creation successful <br />");
	}
	sleep(1);
}
unset($root_register_manager, $root_in_db, $hashed_root_password, $root_write_result);

session_start();

if(mmm_check_session()){
	redirect_to("/index.php");
	die();
}

function process_login($username, $password){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

	if($username == "") {
		print_message("Error logging in: Please enter a username");
		return;
	}

	$user_in_db = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $username], "username");
	if($user_in_db == NULL){
		print_message("Error logging in: User does not exist");
		return;
	}

	$passhash_in_db = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $username], "password");
	$correct_pass = password_verify($password, $passhash_in_db);
	if(!$correct_pass){
		print_message("Error logging in: Incorrect password");
		return;
	}

	mmm_start_session($username);
	print_message("Success! Redirecting...");
	redirect_to("/index.php");
	die();
}

if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["login_button"])){
	process_login($_POST["user"], $_POST["pass"]);	
}
?>
</head>
<body>
<noscript>Javascript needs to be enabled for this page to work!</noscript>
<script type="text/javascript">
	if(navigator.cookieEnabled != true) document.write("Cookies must be enabled for this page to work!");
</script>

<h2><u>Login</u></h2><br />
	
<form autocomplete="off" action="login.php" method="post">
	<label for="user">Username: </label>
	<input type="text" id="user" name="user"><br />
	<label for="pass">Password: </label>
	<input type="password" id="pass" name="pass"><br /><br />
	<input type="submit" id="login_button" name="login_button" value="Login">
</form>
<br />

<h4>No account? <a href="/register.php">Register here!</a><br />
<!-- <a href="/index.php">Return to homepage</a> --></h4>
</body>
</html>
