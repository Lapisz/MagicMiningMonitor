<html>
<head>
	<title>Change password</title>
<?PHP
require __DIR__ . "/db_accessor.php";
require __DIR__ . "/session_manager.php";

session_start();

if(mmm_check_session() == false){
	redirect_to("/login.php");
	die();
}

function process_change_password($old_password, $new_password, $confirm_new_password){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

	if($old_password == "") {
		print_message("Error changing password: Please enter your current password");
		return;
	}

	$passhash_in_db = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], "password");
	$correct_pass = password_verify($old_password, $passhash_in_db);
	if(!$correct_pass){
		print_message("Error changing password: Incorrect current password");
		return;
	}

	if($old_password == $new_password){
		print_message("Error changing password: New password cannot be same as current password");
		return;
	}

	if(strlen($new_password) > 72){
		print_message("Error changing password: New password max length is 72 characters");
		return;
	}
	if(strlen($new_password) < 6){
		print_message("Error changing password: Please use a new password length of 6-72 characters");
		return;
	}

	if($confirm_new_password != $new_password){
		print_message("Error changing password: The confirmation does not match the new password");
		return;
	}

	$hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);

	$docs_written = mmm_generic_modify($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], ["password" => $hashed_new_password], false);
	if(($docs_written -> getModifiedCount()) == 0){
		print_message("Error changing password: Something failed in mmm_generic_modify, data not saved");
		return;
	}

	print_message("Success! Redirecting...");
	redirect_to("/index.php");
	die();
}

if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["submit_button"])){
	process_change_password($_POST["old_pass"], $_POST["new_pass"], $_POST["confirm_new_pass"]);	
}
?>
</head>
<body>
<noscript>Javascript needs to be enabled for this page to work!</noscript>
<script type="text/javascript">
	if(navigator.cookieEnabled != true) document.write("Cookies must be enabled for this page to work!");
</script>

<h2><u>Change password</u></h2><br />
	
<form autocomplete="off" action="change_password.php" method="post">
	<label for="old_pass">Current password: </label>
	<input type="password" id="old_pass" name="old_pass"><br />
	<label for="new_pass">New password: </label>
	<input type="password" id="new_pass" name="new_pass"><br />
	<label for="confirm_new_pass">Confirm new password: </label>
	<input type="password" id="confirm_new_pass" name="confirm_new_pass"><br /><br />
	<input type="submit" id="submit_button" name="submit_button" value="Submit">
</form>
<br />

<h4>
<a href="/index.php">Return to homepage</a></h4>
</body>
</html>
