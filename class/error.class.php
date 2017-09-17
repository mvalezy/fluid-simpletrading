<?php

/*
 * Error messages
 * Store error messages
*/

class ErrorMessage {
   
    /* DB STRINGS */
    public $type;
    public $message;

    
    public function __construct($type = 'info', $message, $info = '') {

        $this->type 	= $type;
        
        if(is_array($message)) {
            $this->message = '';
            foreach($message as $detail) {
                if(strlen($this->message > 0))
                    $this->message .= ' | ';
                $this->message .= $detail;
            }
        }
        else {
            $this->message 	= $message;
        }       

        if($info)
            $this->message .= " ($info)";

        if($this->message)
            return $this; 
    }
}