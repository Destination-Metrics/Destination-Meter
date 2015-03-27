<?php

// Server information/////////////
// $db_hostname = "localhost";
// $db_database = "wgutmann";
// $db_username = "root";
// $db_password = "bandages";
/////////////////////////////////

$con=mysqli_connect("localhost","root","bandages","wgutmann");
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }else{
  	echo "connected ok";
  }

$visits_array=array();

$file = '/var/www/tshark.log'; // location of tshark dump file
$DM_MAC_FILE = '/var/www/mac.log'; // location of device mac address 
$lastpos = 0; // pointer for file reading

$temp = ""; // variable for temporarily storing mac address to compare for upload
$nothingCount = 0; //ivaraible that count the number of times checked against a single mac address. Resets at 25.

$f = fopen($DM_MAC_FILE, "r");//open location of device mac address
$DM_MAC = fgets($f);// read from file
//$DM_MAC = dechex ($DM_MAC);
fclose($f);// close file
 
function get_data($url) {
  $ch = curl_init();
  $timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
  if($data == NULL){
    return NULL;
  }else{
	return $data;
  }
}

function get_manufacture($mac){
	$vendorString = "http://www.macvendorlookup.com/api/v2/" . $mac . "/pipe";
	$vendorJSON = get_data($vendorString);
	if($vendorJSON != NULL){
		list($startHex, $endHex, $startDec, $endDec, $company, $addressl1, $addressl2, $addressl3, $country,$type) = explode("|", $vendorJSON);
		
		return $company;
	}else{
		return null;
	}
}

function get_time() // returns the current time
{
	$nowTime = date("Y-m-d H:i:s");
	return $nowTime;
}

function parseMyDate($month_date, $year_time) //returns the properly formatted date for SQL database
{

	$parts = preg_split('/\s+/', $month_date); // get rid of the extra spaces
	$month = $parts[0];
	$day = $parts[1];

	if($month == 'Jan'){
			$month = '01'; 
		}else if ($month == 'Feb') {
			$month = '02'; 
		}elseif ($month == 'Mar') {
			$month = '03'; 
		}elseif ($month == 'Apr') {
			$month = '04'; 
		}elseif ($month == 'May') {
			$month = '05'; 
		}elseif ($month == 'Jun') {
			$month = '06'; 
		}elseif ($month == 'Jul') {
			$month = '07'; 
		}elseif ($month == 'Aug') {
			$month = '08'; 
		}elseif ($month == 'Sep') {
			$month = '09'; 
		}elseif ($month == 'Oct') {
			$month = '10'; 
		}elseif ($month == 'Nov') {
			$month = '11'; 
		}elseif ($month == 'Dec') {
			$month = '12'; 
		}


	list($blank, $year, $splitTime) = explode(' ', $year_time); //seperate year from time
	list($time, $microTime) = explode('.', $splitTime); //truncate the decimal point (no microseconds)
	$date = $year . "-" . $month . "-" . $day . " " . $time; //concatinate the new date string
	return "\n" . $date; //return the proper date format
}

function chck_blklst_req($mac, $time){

	$con=mysqli_connect("localhost","root","bandages","wgutmann");
	$results = mysqli_query($con, "SELECT (DEPARTURE - ARRIVAL) FROM VISITS WHERE MAC_ADDRESS = '$mac' ");
	
	if($results == NULL)
	{
		$results = 0;
		RETURN FALSE;
	}

	if($results >= 1060000)
	{
		mysqli_query($con, "INSERT INTO BLACKLIST(MAC_ADDRESS, DATE_ADDED) VALUES('$mac', '$time') ");
		mysqli_query($con, "DELETE FROM VISITS WHERE MAC_ADDRESS = '$mac' ");
		mysqli_query($con, "DELETE FROM MAC_LIST WHERE MAC_ADDRESS = '$mac' ");

		return TRUE;
	}
		

}
	
	


while(true)
{
	echo "Going to sleep for 30 seconds \n";
	sleep(30); // sleep 30 seconds inbetween file length check
	
	if(isset($visits_array)){
		unset($visits_array);
		$visits_array = array();
	}else{
		$visits_array = array();
	}
	echo "Clearing visits_array \n";

	clearstatcache();
	$len = filesize($file); //get the length of tshark dumpfile
	
	if($len > $lastpos) //if the length of the file is greater than the length of the pointer
	{
		echo "new stuff in tshark.log \n";
		$f = fopen($file, "r"); //open file with reading permissions only
		if($f == false) // if for whatever reason we cant open the file
		{
			die(); // The program will crash right here if we ant open the file.
			echo "File could not be opened \n";
		}else{
			fseek($f, $lastpos); //find the position of the pointer and start reading
			while (!feof($f)) // while we are not at the end of the file
			{
				$buffer = fgets($f); //gets each line, one by one
				if(!feof($f)) //also while not at the end of the file
				{
					list($month_date, $year_time, $mac, $db) = explode(",", $buffer); //exploding the line of the file we retrieved
					$time = parseMyDate($month_date, $year_time); //sends args to fx for proper formating	

					if(mysqli_query($con, "SELECT MAC_ADDRESS FROM BLACKLIST WHERE MAC_ADDRESS= '$mac' ") != NULL){
						$blk_lst_test = chck_blklst_req($mac, $time);
						
						if($blk_lst_test){
							echo "MAC address has been black listed \n";
						}

						if(!empty($visits_array)){
							if(!in_array($mac, $visits_array)){
								array_push($visits_array, $mac); //appending new mac address to the array

								$query = mysqli_query($con, "SELECT MAC_ADDRESS, LAST_SEEN, LOCATION FROM MAC_LIST WHERE MAC_ADDRESS = '$mac' ");
								if($query != NULL)
								{
									$result = mysql_query($query);
									$rows = mysql_num_rows($result);
									$q_mac = $row[0];
									$q_last_seen = $row[1];
									$q_last_location = $row[2];

									if( mysqli_query($con, "SELECT (LAST_SEEN - NOW()) FROM MAC_LIST WHERE MAC_ADDRESS = '$mac' ") > 2500 OR $q_last_location != $DEVICE_MAC)
									{
										$query = mysqli_query($con, "INSERT INTO VISITS(MAC_ADDRESS, ARRIVAL, DEPARTURE, LOCATION_ID, DB) VALUES ('$mac', '$time', '$time', '$DM_MAC', '$db') "); //INSERT NEW ENTRY INTO VISITS
									}else{
										$vist_last = mysqli_query($con, "SELECT VIST_ID FROM VISITS WHERE MAC_ADDRESS = '$mac' ORDER BY DEPARTURE DESC LIMIT 1) "); //getting the last entry in visits where our mac address was last seen
										$result = mysqli_query($con, "UPDATE VISITS SET DEPARTURE = $time WHERE VISIT_ID = '$visit_last' "); //setting departure time in VISITS
									}
								
									$query = mysqli_query($con, "UPDATE MAC_LIST SET LAST_SEEN = '$time', LOCATION_ID = '$DEVICE_MAC' WHERE MAC_ADDRESS = '$mac' "); //update the last time device was seen in MAC_LIST 
								}else{ //this means we have discovered a MAC we have never seen before

									$manufacture = get_manufacture($mac); // get the mac Manufacture from the PIP API
									// we only want to do this if the MAC has NOT been seen before
									echo $mac . ", " . $manufacture . " --" . count($visits_array) . "\n";

									$query = mysqli_query($con, "INSERT INTO VISITS(MAC_ADDRESS, LOCATION_ID, ARRIVAL, DEPARTURE) VALUES('$mac', '$DM_MAC', '$time', '$time') ");
									$query = mysqli_query($con, "INSERT INTO MAC_LIST(MAC_ADDRESS, LAST_SEEN, MANUFACTURE, LOCATION_ID) VALUES('$mac', '$time', '$manufacture', '$DM_MAC') ");
								}

								
							if(count($visits_array)>=20){
								unset($visits_array);
								$visits_array = array();
								echo " \n \n Had to unset visits_array ";
							}

							}else{
								echo $mac . " was already uploaded recently - " . $time ."\n";
							}
						}else{
							array_push($visits_array, $mac);
						}	
					}else{
						echo "mac address is found on the blacklist";
					}
				}
			}	
		}
		$lastpos = ftell($f); //assign the pointer to the last position that we read from
		fclose($f); //close the file

	}else{
		echo " nothing new in tshark.log \n"; //we didnt find anything new in the tshark.log file
		if(filesize($file) >= 5000000000)// if our file is greater than 5GB we truncate the file
		{
			echo "need to delete file *************************************************** \n";
			$f = fopen($file, "w"); //open tshark.log file for truncation with writing priviliages 
			fclose($f); //close file
		}
		$nothingCount ++; //incrementing current mac address count
	}
}
