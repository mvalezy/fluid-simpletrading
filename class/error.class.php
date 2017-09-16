<?php

/*
 * Error messages
 * Store error messages
*/

class ErrorMessage {
   
    /* DB STRINGS */
    public $type;
    public $message;

    
    public function __construct($type = 'info', $message) {
        $this->type 	= $type;
		$this->message 	= $message;
		
		if($this->message)
			return $this;        
    }
}