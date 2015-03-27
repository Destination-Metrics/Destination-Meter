<?php

	// Server information/////////////
	$db_hostname = "localhost";
	$db_database = "wgutmann";
	$db_username = "root";
	$db_password = "bandages";
	/////////////////////////////////

	$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
	$query = "SELECT LOCATION_MAC FROM LOCATION_MAC WHERE LOCATION_MAC = '" . $mac . "' ";
	$result = mysqli_query($con, $query);
			
	if($result == FALSE)
	{
		echo" THAT SHIT DONT EXISTS";
	}else{
	
		$rows = mysqli_num_rows($result);
		for($i=0; $i<$rows; $i++){
			$row = mysqli_fetch_row($result); 
			$m = new Dm($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
			$results[] = $m;
		}
	}
?>