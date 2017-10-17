<?php

/*
 * Log utility class
 * Store logs of error messages in a file or in a var
*/

class Logger {
   
    /* DB STRINGS */
    public $message;
    public $file;
    public $display;
    public $format;

    public $css;
	
    private $rep;
    private $filename;
    private $fp;
    
    public function __construct($filename, $display = 0, $format = 'text', $rep = TRADE_LOG_REP) {
        
        $this->rep      = $rep;
        $this->filename = $filename;
        $this->file     = $this->rep . $this->filename.'.log';
        
        
        $this->display  = $display;
        $this->format   = $format;

        $this->archive();
    }
      
      
    public function open() {
        $this->fp = fopen($this->file, "a+");
    }

	
    public function close() {
        fclose($this->fp);
    }

    public function getMessage($message, $script) {
        if($script)
            $this->message = "$script:";
        else
            $this->message = '';

        if(is_array($message)) {
            foreach($message as $detail) {
                if(strlen($this->message > 0))
                    $this->message .= ' | ';
                $this->message .= $detail;
            }
        }
        else {
            $this->message .= $message;
        }

        $this->message .= "\r\n";
    }

	
    public function log($level, $message, $script = '', $css = 'info') {

        if(!is_resource($this->fp))
            $this->open();

        $this->getMessage($message, $script);

        fwrite($this->fp, date('Y-m-d H:i:s') . " - $level - $this->message");
        
        if($this->display) {
            return $this->display($css);
        }
    }

	
	public function display($css = 'info', $message = '', $script = '') {

        if($message)
            $this->getMessage($message, $script);

        if($this->message) {
            if($this->format == 'text') {
                return $this->message;
            }
            else {
                $obj = new stdClass();
                $obj->message = $this->message;
                $obj->css = $css;
                return $obj;
            }
        }
    }

    public function archive() {
        if(file_exists($this->file)) {
            if(filesize($this->file) >= 2097152) { // 2mo
                $this->open();
                $this->log('WARNING', 'archive log file', 'maintenance');
                $this->close();

                $logFile = $this->filename."_".date('Y-m-d').".log";
                rename($this->file, $this->rep.$logFile);


                $zip = new ZipArchive();
                $zipFile = $this->filename."_".date('Y-m-d').".zip";
                if ($zip->open($zipFile, ZipArchive::CREATE)===TRUE) {
                    $zip->addFile($logFile);
                    $this->log('WARNING', "Added log file to $zipFile", 'maintenance');
                    $zip->close();
                }
                else
                    $this->log('ERROR', "Zip file $zipFile impossible to open", 'maintenance');
            }
        }
    }

}

?>