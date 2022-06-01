<?PHP
require_once __DIR__ . "/db_accessor.php";

$inputted_json_data = file_get_contents("php://input");
if( ($inputted_json_data != "") && ($_SERVER["CONTENT_TYPE"] == "application/json") ){
	$json_data_array = get_object_vars(json_decode($inputted_json_data));

	$inputted_username = $json_data_array["username"];
	$inputted_access_key = $json_data_array["hashrate_key"];
	$inputted_worker_name = $json_data_array["worker_name"];
	
	unset($json_data_array["username"], $json_data_array["hashrate_key"], $json_data_array["worker_name"]);

	$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
	$set_result = set_worker_data($manager, 
		$inputted_username, $inputted_access_key, 
		$inputted_worker_name, $json_data_array
	);
	
	if($set_result == 0){
		echo "Success\n";
	} else if ($set_result == 1){
		echo "Invalid access token\n";
	} else {
		echo "Failed (other error)\n";
	}
}
unset($inputted_json_data, $json_data_array, $inputted_username, $inputted_access_key, $inputted_worker_name, $manager, $set_result);


#Checks whether a given key is valid or not.
#
#Parameters:
#MongoDB\Driver\Manager - $mongodb_manager - Manager instance to the database.
#string                 - $username        - User that will be accessed.
#string                 - $key             - The key
#string                 - $key_type        - "developer" or "access" (optional)
#
#Return values:
#0 - Not a valid token
#1 - Valid access token
#2 - Valid developer token
function is_key_valid($mongodb_manager, string $username, $key, string $key_type = NULL){
	if( (gettype($key) != "string") || ($key == "") ){
		return 0;
	}
	
	if( ($key_type == NULL) || ($key_type == "access") ){
		$key_in_db = mmm_generic_read(
			$mongodb_manager, 
			"local.mmm_credentials", 
			["username" => $username], 
			"hashrate_key"
		);

		if($key == $key_in_db){
			return 1;
		}
	}

	if( ($key_type == NULL) || ($key_type == "developer") ){
		$key_in_db = mmm_generic_read(
			$mongodb_manager, 
			"local.mmm_credentials", 
			["username" => $username], 
			"dev_key"
		);

		if($key == $key_in_db){
			return 2;
		}
	}

	unset($key_in_db);
	return 0;
}

#Obtains and recalculates data from the database, cleans up inactive workers
function get_worker_data($mongodb_manager, $username, string $access_key = NULL, bool $override_check = false){
	$db_data = mmm_generic_read(
		$mongodb_manager, 
		"local.mmm_userstats", 
		["username" => $username], 
		"workers"
	);
	
	if($db_data == NULL){
		return NULL;
	}

	$stats_array = object_array_a( get_object_vars($db_data) );

	$most_recent = 0;
	$total_reported = 0.0;
	$total_realtime = 0.0;
	$total_power = 0;

	foreach($stats_array as $key => $value){
		if($value["last_seen"] < (time() - 60)){
			unset($stats_array[$key]);
		} else {
			if($value["last_seen"] > $most_recent){
				$most_recent = $value["last_seen"];
			}
			$total_reported += $value["reported_hashrate"];
			$total_realtime += $value["realtime_hashrate"];
			$total_power += $value["power_consumption"];
		}
	}
	
	$total_stats_array = ["most_recent" => $most_recent, 
		"total_reported" => $total_reported, 
		"total_realtime" => $total_realtime, 
		"total_power" => $total_power
	];


	$updated_data = array_merge(["workers" => ((object) $stats_array)], $total_stats_array);
	mmm_generic_modify($mongodb_manager, "local.mmm_userstats", ["username" => $username], $updated_data, false);


	if( (is_key_valid($mongodb_manager, $username, $access_key, "access") == 1) || ($override_check == true) ){
		unset($most_recent, $total_reported, $total_realtime, $total_power, $total_stats_array);
		return $updated_data;
	}

	unset($stats_array, $most_recent, $total_stats_array, $updated_data);
	return ["total_reported" => $total_reported, "total_realtime" => $total_realtime, "total_power" => $total_power];
}

#Return codes
#0 - Worker added
#1 - Invalid key
function set_worker_data($mongodb_manager, $username, string $access_key, $worker_name, array $worker_data){

	if(is_key_valid($mongodb_manager, $username, $access_key, "access") == 0){
		return 1;
	}

	$db_data = get_worker_data($mongodb_manager, $username, NULL, true);

	if(gettype($db_data["workers"]) == "object"){
		$db_data["workers"] = get_object_vars($db_data["workers"]);
		$worker_count = count($db_data["workers"]);
	}

	if( ($db_data == NULL) || ($db_data["workers"] == NULL) || ($worker_count == 0) ){
		$new_data = [
			"username" => $username, 
			"workers" => [ $worker_name => [
				"last_seen" => time(), 
				"reported_hashrate" => $worker_data["reported_hashrate"], 
				"realtime_hashrate" => $worker_data["realtime_hashrate"], 
				"power_consumption" => $worker_data["power_consumption"]
			] ]
		];

		if(mmm_generic_read($mongodb_manager, "local.mmm_userstats", ["username" => $username], "username") == NULL){
			mmm_generic_write($mongodb_manager, "local.mmm_userstats", $new_data);
		} else {
			mmm_generic_modify($mongodb_manager, "local.mmm_userstats", ["username" => $username], $new_data, false);
		}
	} else {
		$new_data = [ $worker_name => [
			"last_seen" => time(), 
			"reported_hashrate" => $worker_data["reported_hashrate"], 
			"realtime_hashrate" => $worker_data["realtime_hashrate"], 
			"power_consumption" => $worker_data["power_consumption"]
		] ];

		$new_workers_array = array_merge($db_data["workers"], $new_data);
		mmm_generic_modify($mongodb_manager, "local.mmm_userstats", ["username" => $username], ["workers" => $new_workers_array], false);
	}

	get_worker_data($mongodb_manager, $username, NULL, true);

	unset($db_data, $worker_count, $new_data, $new_workers_array);
	return 0;
}

#currently unused
#To do - rewrite for API use later
function delete_worker_data($mongodb_manager, $username, $worker_name){
	$db_workers_data = ( get_worker_data($mongodb_manager, $username, NULL, true) )["workers"];
	
	unset($db_workers_data[$worker_name]);
	mmm_generic_modify($mongodb_manager, "local.mmm_userstats", ["username" => $username], ["workers" => ((object) $db_workers_data)], false);

	unset($db_workers_data);
}

?>
