<?PHP
#any special characters inside given string need to be escaped
#WRONG: print_message("<a href=\"/index.php\">");
#CORRECT: print_message("<a href=\\\"index.php\\\">");
function print_message($message){
	echo "<script type=\"text/javascript\">",
		"document.write(\"",
		$message,
		"\");",
		"</script>";
}

#make sure to write die(); after calling this function
#to prevent web crawlers from bypassing redirects
function redirect_to($location_of_page){
	$destination = "Location: http://$_SERVER[HTTP_HOST]".$location_of_page;
	header($destination, TRUE, 302);
}

function gen_random_string($length){
	if($length <= 0){
		return NULL;
	}
	
	$all_characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
	$end_char_index = strlen($all_characters) - 1; #saves computational power in the for loop
	$result = "";
	
	for($i = 0; $i < $length; $i++){
		$result .= $all_characters[rand(0, $end_char_index)];
	}

	unset($all_characters, $end_char_index);
	return $result;
}

#for debug, this function is meant to be called from PHP in html body
function print_array($arr, int $recursive_times = 0){
	
	if(gettype($arr) != "array"){
		if($arr == NULL){
			echo "NULL <br />";
		} else {
			echo $arr . " <br />";
		}
		return;
	}

	#beginning
	echo "[<br />";

	#all the elements
	foreach($arr as $key => $value){
		for($i = 0; $i <= $recursive_times; $i++){
			echo "&nbsp; &nbsp; &nbsp; &nbsp;";
		}

		if(gettype($value) == "object"){
			$value = get_object_vars($value);
		}

		if(count($value) == 0){
			echo "{$key} => [NULL] <br />";
		} else if(gettype($value) == "array"){
			echo "{$key} => ";
			print_array($value, $recursive_times + 1);
		} else {
			echo $key . " => " . $value . " <br />";
		}
	}
	
	#end
	for($i = 0; $i < $recursive_times; $i++){
		echo "&nbsp; &nbsp; &nbsp; &nbsp;";
	}
	echo "]<br />";
}

#converts object elements inside a given array from object type to array type
function object_array_a(array $arr){
	if( ($arr == NULL) || (count($arr) == 0) ){
		return $arr;
	}

	foreach($arr as $key => $value){
		if(gettype($value) == "object"){
			$arr[$key] = get_object_vars($value);
		}
	}	

	return $arr;
}

function mmm_generic_write($mongodb_manager, $db_and_collection, $data_to_write){
	$temp_queue = new MongoDB\Driver\BulkWrite();
	$temp_queue -> insert($data_to_write);

	$write_concern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
	
	$result = $mongodb_manager -> executeBulkWrite($db_and_collection, $temp_queue, $write_concern);

	unset($temp_queue, $write_concern);
	return $result;
}

#caveat: only returns first result contents
#to resolve, generate an array with all the results or fields and return that. possibly add $num_of_results parameter
#wont need to be resolved currently as it does not bottleneck any functionality
function mmm_generic_read($mongodb_manager, $db_and_collection, $document_search_query, $field_to_grab){
	$options = ["projection" => ["_id" => 0]];
	if($field_to_grab != "ALL"){
		$options = ["projection" => [$field_to_grab => 1, "_id" => 0]];
	}
	
	$query_queue = new MongoDB\Driver\Query($document_search_query, $options);
	$mongo_cursor = $mongodb_manager -> executeQuery($db_and_collection, $query_queue);

	if($field_to_grab == "ALL"){
		$result = get_object_vars( ($mongo_cursor -> toArray())[0] );
	} else {
		$result = get_object_vars( ($mongo_cursor -> toArray())[0] )[$field_to_grab];
	}

	unset($query_queue, $options, $mongo_cursor);
	return $result; #TIL that if statements arent their own scope in PHP so we can use them outside the statement
}

#replace_instead_of_modify = true will OVERWRITE a document instead of just changing a field
#if true, $fields_to_update MUST be in a WHOLE document format.
#if false, $fields_to_update MUST be formatted like [set_just_this_field => value, and_also_this_one => value2]
function mmm_generic_modify($mongodb_manager, $db_and_collection, $document_search_query, $fields_to_update, $replace_instead_of_modify){
	$temp_queue = new MongoDB\Driver\BulkWrite();
	if($replace_instead_of_modify == false){
		$temp_queue -> update($document_search_query, ['$set' => $fields_to_update], ["multi" => false, "upsert" => false]);
	} else {
		$temp_queue -> update($document_search_query, $fields_to_update, ["multi" => false, "upsert" => false]);
	}

	$write_concern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
	$result = $mongodb_manager -> executeBulkWrite($db_and_collection, $temp_queue, $write_concern);

	unset($temp_queue, $write_concern);
	return $result;
}

#to do - modify all function
?>
