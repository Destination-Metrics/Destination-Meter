<?php
	CLASS Dm{
		var $location_mac;
		var $location_name;
		var $address;
		var $port;
		var $last_down;
		var $status;
		var $active;

		function __construct($mac, $name, $address, $port, $down, $status, $active){
			$this->location_mac = $mac;
			$this->location_name = $name;
			$this->address = $address;
			$this->port = $port;
			$this->last_down = $down;
			$this->status = $status;
			$this->active = $active;
		}

		function gLocationMac(){
			return $this->location_mac;
		}
		function gLocationName(){
			return $this->location_name;
		}
		function gAddress(){
			return $this->address;
		}
		function gPort(){
			return $this->port;
		}
		function gLastDown(){
			return $this->last_down;
		}
		function gStatus(){
			return $this->status;
		}
		function gActive(){
			return $this->active;
		}
	}

?>