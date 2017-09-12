<?php

/**
 * Reference implementation for Notify my Android (NMA) REST API.
 *
 * See https://www.notifymyandroid.com/api.jsp for more info.
 */


class NotifyMyAndroid {

    public $description;
    public $priority=0;
    public $event;
    public $application;
    public $url;

    public $remaining;
    public $resettimer;

    protected $apikey=API_KRAKEN_KEY;


    public function post() {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.notifymyandroid.com/publicapi/notify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => 
                "apikey=".$this->apikey.
                "&application=".urlencode($this->application).
                "&priority=".$this->priority.
                "&event=".urlencode($this->event).
                "&description=".urlencode($this->description).
                "&url=".urlencode($this->url).
                "&content-type=text%2Fhtml",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "Accept: application/json",
            ),
        ));

        $i=0;
        while ($i++ < CURL_EXEC_RETRY_MAX) {
            $response = curl_exec($curl);
            
            if($response===false) {
                if ($i < CURL_EXEC_RETRY_MAX) { sleep($i+3); }
                else
                    die(curl_error($curl));
            }
            else {
                break;
            }
        }      

        curl_close($curl);

        $xml    = simplexml_load_string($response);
        $json   = json_encode($xml);
        $result = json_decode($json,TRUE);
  
        if(is_array($result)) {
            if(isset($result['success']) && isset($result['success']['@attributes'])) {
                $success = $result['success']['@attributes'];
                if($success['code'] == 200) {
                    $this->remaining    = $success['remaining'];
                    $this->resettimer   = $success['resettimer'];
                    return true;
                }
                else
                    return false;
            }
            else
                return false;
        }
        else
            return false;
    }


}