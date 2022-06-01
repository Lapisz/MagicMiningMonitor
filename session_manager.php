<?PHP
function mmm_end_session(){
	if(session_status() != PHP_SESSION_ACTIVE){
		return;
	}

	unset($_SESSION["expire_time"]);
	unset($_SESSION["username"]);
	
	session_unset();
	session_destroy();
}

function mmm_check_session(){
	if(session_status() != PHP_SESSION_ACTIVE){
		return false;
	}

	if(isset($_SESSION["expire_time"])){
		if($_SESSION["expire_time"] <= time()){
			mmm_end_session();
			return false;
		}
	} else {
		mmm_end_session();
		return false;
	}

	print_message("Logged in as {$_SESSION["username"]} <br />");
	return true;
}

function mmm_start_session($username){
	if(session_status() == PHP_SESSION_ACTIVE){
		return;
	}

	session_start();
	
	$_SESSION["username"] = $username;	
	$_SESSION["expire_time"] = time() + 1209600;
}
?>
