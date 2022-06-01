<html>
<head>
	<title>Home</title>
<?PHP
require __DIR__ . "/db_accessor.php";
require __DIR__ . "/session_manager.php";
require __DIR__ . "/stats_manager.php";

session_start();

if(mmm_check_session() == false){
	print_message("HAA HAA");
	redirect_to("/login.php");
	die();
}

function get_hashrate_key(){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	$key = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], "hashrate_key");

	unset($manager);
	return $key;
}

function gen_hashrate_key(){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	$new_key = gen_random_string(16); 

	$docs_written = mmm_generic_modify($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], ["hashrate_key" => $new_key], false);
	if(($docs_written -> getModifiedCount()) == 0){
		print_message("Error generating key: mmm_generic_modify() did not write data <br />");
		return;
	}
	
	print_message("Generated new access token. Please update your apps and/or their configurations to use this. <br />");

	unset($manager, $new_key);
	return get_hashrate_key();
}

function get_dev_key(){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	$key = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], "dev_key");

	unset($manager);
	return $key;
}

function gen_dev_key(){
	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	$new_key = "dev_";
	$new_key .= gen_random_string(28); 

	$docs_written = mmm_generic_modify($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], ["dev_key" => $new_key], false);
	if(($docs_written -> getModifiedCount()) == 0){
		print_message("Error generating key: mmm_generic_modify() did not write data <br />");
		return;
	}
	
	print_message("Generated new developer token. Please update your apps and/or their configurations to use this. <br />");

	unset($manager, $new_key);
	return get_dev_key();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
	if(isset($_POST["logout_button"])){
		redirect_to("/logout.php");
		die();
	} else if(isset($_POST["changepw_button"])){
		redirect_to("/change_password.php");
		die();
	} else if(isset($_POST["reveal_key_button"])){
		$key = get_hashrate_key();
		if($key == NULL){
			$key = gen_hashrate_key();
		}
		print_message("Access token: {$key} <br />");
	} else if(isset($_POST["hide_key_button"])){
       		#nothing lol
	} else if(isset($_POST["new_key_button"])){
		$key = gen_hashrate_key();
		print_message("Access token: {$key} <br />");
	} else if(isset($_POST["reveal_dev_key_button"])){
		$key = get_dev_key();
		if($key == NULL){
			$key = gen_dev_key();
		}
		print_message("Developer token: {$key} <br />");
	} else if(isset($_POST["hide_dev_key_button"])){
       		#nothing lol
	} else if(isset($_POST["new_dev_key_button"])){
		$key = gen_dev_key();
		print_message("Developer token: {$key} <br />");
	}
	unset($key);

}
?>
</head>
<body>
<noscript>Javascript needs to be enabled for this page to work!</noscript>
<script type="text/javascript">
	if(navigator.cookieEnabled != true) document.write("Cookies must be enabled for this page to work!");
</script>

<h2><u>Tasks</u></h2><br />
	
<form autocomplete="off" action="index.php" method="post">
	<input type="submit" id="logout_button" name="logout_button" value="Logout"><br />
	<input type="submit" id="changepw_button" name="changepw_button" value="Change Password"><br />
	<br />
	<input type="submit" id="reveal_key_button" name="reveal_key_button" value="Reveal access token"><br />
	<input type="submit" id="hide_key_button" name="hide_key_button" value="Hide access token"><br />
	<input type="submit" id="new_key_button" name="new_key_button" value="Reset access token"><br />
<?PHP
if($_SESSION["username"] == "root"){
	print_message("<br />");
	print_message("<input type=\\\"submit\\\" id=\\\"reveal_dev_key_button\\\" name=\\\"reveal_dev_key_button\\\" value=\\\"Reveal developer token\\\"><br />");
	print_message("<input type=\\\"submit\\\" id=\\\"hide_dev_key_button\\\" name=\\\"hide_dev_key_button\\\" value=\\\"Hide developer token\\\"><br />");
	print_message("<input type=\\\"submit\\\" id=\\\"new_dev_key_button\\\" name=\\\"new_dev_key_button\\\" value=\\\"Reset developer token\\\"><br />");
}
?>
</form>
<br />
<h2><u>Mining Stats</u></h2><br />
<?PHP
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
$access_key = mmm_generic_read($manager, "local.mmm_credentials", ["username" => $_SESSION["username"]], "hashrate_key");

#set_worker_data($manager, $_SESSION["username"], $access_key, "miningone", [
#	"reported_hashrate" => 243.35,
#	"realtime_hashrate" => 250.61,
#	"power_consumption" => 440
#]);

$data = get_worker_data($manager, $_SESSION["username"], $access_key);

#if($data != NULL){
#	foreach($data["workers"] as $key => $value){
#		if($value["last_seen"] <= (time() - 60)){
#			delete_worker_data($manager, $_SESSION["username"], $key);
#		}
#	}
#	
#	$data = get_worker_data($manager, $_SESSION["username"], $access_key);
#}	
print_array($data);

unset($manager, $access_key, $data);
?>
</body>
</html>
