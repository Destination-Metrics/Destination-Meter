<?php

	
	require_once('dmClass.php');
	// Server information/////////////
	$db_hostname = "50.87.80.83";
	$db_database = "leverinc_wifi";
	$db_username = "leverinc_wgut";
	$db_password = "bandages";
	/////////////////////////////////
	$mac = 202481595345319;
	$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
	$query = "SELECT LOCATION_MAC, LOCATION_NAME, ADDRESS, PORT, LAST_DOWN, STATUS, ACTIVE FROM LOCATIONS WHERE LOCATION_MAC = '" . $mac . "'";
	$result = mysqli_query($con, $query);
			
	if($result == FALSE)
	{
		echo" MySQL query error: " . mysqli_error();
		echo"\n THAT SHIT DONT EXISTS";
	}else{
		echo "query good \n";
		$rows = mysqli_num_rows($result);
		for($i=0; $i<$rows; $i++){
			$row = mysqli_fetch_row($result); 
			$m = new Dm($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
			$results[] = $m;
			echo "got that shit";
		}
		echo $results[0]->gLocationMac();
	}
?>