<?php

	require_once('dmClass.php');
	$db_hostname = "50.87.80.83";
	$db_database = "leverinc_wifi";
	$db_username = "leverinc_wgut";
	$db_password = "bandages";
	$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

	$found = FALSE;

$x=0;
while($x<=3){

	$query = "SELECT LOCATION_MAC, LOCATION_NAME, ADDRESS, PORT, LAST_DOWN, STATUS, ACTIVE FROM LOCATIONS";
	$result = mysqli_query($con, $query);

	if($result == FALSE)
	{
		echo" MySQL query error: " . mysqli_error();
	}else{
		$rows = mysqli_num_rows($result);
		echo "mysql rows: " . $rows;
		for($i=0; $i<$rows; $i++){
			$row = mysqli_fetch_row($result);
			$m = new Dm($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], TRUE);

			if(isset($results)){
				foreach($results as $r){
					if($r->gLocationMac() == $m->location_mac){
						echo "\nfound in array, skip";
						$found = TRUE;
						break;
					}
				}

				if(!$found){
					$results [] = $m;
					echo "\n not found in array, appending" . $m->location_mac;
				}else{
					echo "\n #";
				}

			}else{
				$results[] = $m;
				echo "\n initializing array, appending: " . $m->location_mac ;
			}
			$found = FALSE;
		}
	}
	$x++;
	sleep(5);
}
	var_dump($results);
?>