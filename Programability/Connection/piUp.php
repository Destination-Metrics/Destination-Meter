<?php 

	require_once('contactClass.php');
	require_once('dmClass.php');

	$db_hostname = "50.87.80.83";
	$db_database = "leverinc_wifi";
	$db_username = "leverinc_wgut";
	$db_password = "bandages";

	function sendEmail($name, $status){

		$db_hostname = "50.87.80.83";
		$db_database = "leverinc_wifi";
		$db_username = "leverinc_wgut";
		$db_password = "bandages";
		$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database);
		$now = new DateTime(); //get the current time

		if($status == TRUE){
			$status = "UP";
		}else{
			$status = "DOWN";
		}

		$query = "SELECT * FROM CONTACTS";
		$result = mysqli_query($con, $query);
				
		if($result == FALSE)
		{
			echo" MySQL query error: " . mysqli_error();
		}else{
			$rows = mysqli_num_rows($result);
			for($i=0; $i<$rows; $i++){
				$row = mysqli_fetch_row($result);
				$m = new Contact($row[0], $row[1], $row[2], $row[3]);
				$contacts[] = $m;
			}
		}

		foreach($contacts as $q){
			$mac = $q->gContactId();
			$cName = $q->gFullName();
			$email = $q->gEmail();
			$active = $q->gActive();
			if($active == TRUE){
				$msg = "Destination meter " . $name . " " . $status . " " . $now->format('Y-m-d H:i:s') . "\n"; // create message
				mail($email, "Destination Meter: " . $status . " " . $name, $msg);// send email
				//echo "\n \n \n Email sent for: " . $name . " to: " . $cName . "\n";
				//echo $msg . "\n";
			}
		}
		echo "Email sent: " . $name . ". " . $status . ".\n";
		//echo $msg . "\n";
	}

	echo "starting while true loop\n";
	while(true){
		$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database); //attempting to make a connection to the database
		if (!$con) {
		    die("Connection failed: " . mysqli_connect_error());
		}else{
			//echo "connected";
		}

		$query = "SELECT LOCATION_MAC, LOCATION_NAME, ADDRESS, PORT, LAST_DOWN, STATUS, ACTIVE FROM LOCATIONS";
		$result = mysqli_query($con, $query);
				
		if($result == FALSE)
		{
			echo" MySQL query error: " . mysqli_error();
		}else{
		
			$rows = mysqli_num_rows($result);
			for($i=0; $i<$rows; $i++){
				$row = mysqli_fetch_row($result); 
				$m = new Dm($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
				$results[] = $m;
			}
		}

		foreach($results as $q){
			$mac = $q->gLocationMac();
			$name = $q->gLocationName();
			$address = $q->gAddress();
			$port = $q->gPort();
			$last_down = $q->gLastDown();
			$status = $q->gStatus();
			$active = $q->gActive();

			if($active == TRUE){
				echo $name . ". Current status: " . $status . ". ";
				$waitTimeoutInSeconds = 2; 
				if($fp = @fsockopen($address,$port,$errCode,$errStr,$waitTimeoutInSeconds)){   
				   // It worked 
					echo "Connection: up. \n";
					fclose($fp);

					if($status == FALSE){
						$query = "UPDATE LOCATIONS SET STATUS = '1' WHERE LOCATION_MAC = '" . $mac . "' ";
						$result = mysqli_query($con, $query);
						if($result == FALSE){
							echo" MySQL query error: " . mysqli_error();
						}else{
							//echo "" . $name . "Status set to: True. \n";
							$status = TRUE;
							sendEmail($name, $status);
						}
					}
				} else {

					if($status == TRUE){
						$now = new DateTime();
						$query = "UPDATE LOCATIONS SET LAST_DOWN = ' " . $now->format('Y-m-d H:i:s') . " ' WHERE LOCATION_MAC = '" . $mac . "' ";
						$result = mysqli_query($con, $query);

						if($result == FALSE){
							echo" MySQL query error: " . mysqli_error();
						}else{
							//echo "LAST_DOWN set \n";
						}

						$query = "UPDATE LOCATIONS SET STATUS = '0' WHERE LOCATION_MAC = '" . $mac . "' ";
						$result = mysqli_query($con, $query);
						if($result == FALSE){
							echo" MySQL query error: " . mysqli_error();
						}else{
							//echo "" . $name . " STATUS updated to FALSE. Device is offline. \n";
							$status = FALSE;
							sendEmail($name, $status);
						}
					}else{
						echo "Connection: Down.\n";
					}
				}
			}
		}
		echo "\n";
		unset($results);
		sleep(300);
	}
?>