<?php
	CLASS Contact{
		var $contact_id;
		var $full_name;
		var $email;

		function __construct($id, $name, $email, $active){
			$this->contact_id = $id;
			$this->full_name = $name;
			$this->email = $email;
			$this->active = $active;
		}

		function gContactId(){
			return $this->contact_id;
		}
		function gFullName(){
			return $this->full_name;
		}
		function gEmail(){
			return $this->email;
		}
		function gActive(){
			return $this->active;
		}

	}

?>