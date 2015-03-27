<?php

// Server information/////////////
$db_hostname = "50.87.80.83";
$db_database = "leverinc_wifi";
$db_username = "leverinc_wgut";
$db_password = "bandages";
/////////////////////////////////
//localhost connection
//$db_hostname = "localhost";
//$db_database = "DM";
//$db_username = "root";
//$db_password = "bandages";

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

function testFileSize(){

	$filesize = (filesize($file) * .0009765625) * .0009765625;

	if(filesize($file) >= 1000000000)// if our file is greater than 1GB we truncate the file
		{
			echo "need to delete file ********************************************** \n";
			$f = fopen($file, "w"); //open tshark.log file for truncation with writing priviliages 
			fclose($f); //close file
		}
}

while(true)
{
	testFileSize();
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

					$message = "ONE " . $mac . ", " . "1, " . $time . ", " . $db . " \n "; //concatinating string for query				
					$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database); //attempting to make a connection to the database

					while(!$con) //if we can't connect to the database (internet connection most likely dropped)
					{
						$con = mysqli_connect($db_hostname, $db_username, $db_password, $db_database); //keep re-trying to connect to database
						echo "failed to connect to MySQL: " . mysqli_connect_errno(); //echo out our error
						$logFile = "/var/www/log.log";

						$ff = fopen($logFile, "a"); // open log.log
						$current = get_time() . " Failed to Connect to MySQL: ". mysqli_connect_errno() . "\n";
						fwrite($ff, $current); //writing to log file
						fclose($ff);//lose log file
						sleep(30); //wait thirty seconds
					}

					if(!empty($visits_array)){
						if(!in_array($mac, $visits_array)){
							array_push($visits_array, $mac);
							$manufacture = get_manufacture($mac);
							echo $mac . ", " . $manufacture . " --" . count($visits_array) . "\n";
							
							if($manufacture != NULL){
								$okay = mysqli_query($con, "INSERT INTO VISITS (VISIT_ID, DEVICE_MAC, MAC_MANUFACTURE, LOCATION_ID, VISIT_TIME, VISIT_DB) VALUES('', '$mac', '$manufacture', '$DM_MAC', '$time', '$db')"); // $okay accepting the return value of insert statement (TRUE/FALSE). inserting values into table
							}else{
								$okay = mysqli_query($con, "INSERT INTO VISITS (VISIT_ID, DEVICE_MAC, MAC_MANUFACTURE, LOCATION_ID, VISIT_TIME, VISIT_DB) VALUES('', '$mac', '', '$DM_MAC', '$time', '$db')"); // $okay accepting the return value of insert statement (TRUE/FALSE). inserting values into table
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
						$manufacture = get_manufacture($mac);
						echo $mac . ", " . $manufacture . count($visits_array) . "\n";
						
						if($manufacture != NULL){
							$okay = mysqli_query($con, "INSERT INTO VISITS (VISIT_ID, DEVICE_MAC, MAC_MANUFACTURE, LOCATION_ID, VISIT_TIME, VISIT_DB) VALUES('', '$mac', '$manufacture', '$DM_MAC', '$time', '$db')"); // $okay accepting the return value of insert statement (TRUE/FALSE). inserting values into table
							
						}else{
							$okay = mysqli_query($con, "INSERT INTO VISITS (VISIT_ID, DEVICE_MAC, MAC_MANUFACTURE, LOCATION_ID, VISIT_TIME, VISIT_DB) VALUES('', '$mac', '', '$DM_MAC', '$time', '$db')"); // $okay accepting the return value of insert statement (TRUE/FALSE). inserting values into table
						}
					}

					if(!$okay) //if the query upload went well
					{
						echo "Query Error"; //well, there was an error in our query statement
					}		
				}
			}		
		}
		$lastpos = ftell($f); //assign the pointer to the last position that we read from
		fclose($f); //close the file

	}else{
		echo " nothing new in tshark.log \n"; //we didnt find anything new in the tshark.log file
		testFileSize();
		$nothingCount ++; //incrementing current mac address count
	}
}
?>