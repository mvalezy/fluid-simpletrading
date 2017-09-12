<?php
/**
* Method to connect to mysql database
*/

date_default_timezone_set('Europe/Paris');

include("class/krumo/class.krumo.php");

// Script Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_start = $loading_time;


function connect($db = SQL_DB) {
    $link = mysql_connect(SQL_HOST, SQL_USER, SQL_PASSWORD);
    if (!$link) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db($db, $link);
    return $link;
}

function connecti($db = SQL_DB) {
        $mysqli = new mysqli(SQL_HOST, SQL_USER, SQL_PASSWORD,  $db);
        if ($mysqli->connect_error) {
            die('Could not connect (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        }
        return $mysqli;
}

function mysqlerr($db, $query) {
    global $debug;

    if (@$db->errno) {
        $error = "MySQL error ".$db->errno.": <strong>".$db->error."</strong>\n<br>When executing: \n$query\n<br>";
        echo '<div style="font-family: Verdana,sans-serif;font-size: 11px;line-height: 1.5;padding: 5px;margin-bottom: 5px;border: 1px solid transparent;border-radius: 4px;color: #a94442;background-color: #f2dede;border-color: #ebccd1;">'.$error.'</div><br />';
    }
    elseif($debug) {
        $error = "MySQL query: \n$query\n<br>";
        echo '<div style="font-family: Verdana,sans-serif;font-size: 11px;line-height: 1.5;padding: 5px;margin-bottom: 5px;border: 1px solid transparent;border-radius: 4px;color: #31708f;background-color: #d9edf7;border-color: ##bce8f1;">'.$error.'</div><br />';
    }
}


function output($data, $style = '') {
    switch($style) {
        case 'warning':
             $data = "<div style=\"font-family: Verdana,sans-serif;font-size: 11px;line-height: 1.5;padding: 5px;margin-bottom: 5px;border: 1px solid transparent;border-radius: 4px;color: #333;background-color: #fcf8e3;border-color: #bce8f1;\"><strong>Output:</strong>\n$data<br /></div>";
        break;
    }
    echo $data;
}

function closejs($data) {
    echo json_encode($data);
    exit;
}

function close($data) {
    echo $data;
    exit;
}


function soapify(array $data) {
        foreach ($data as &$value) {
                if (is_array($value)) {
                        $value = soapify($value);
                }
        }

        return new SoapVar($data, SOAP_ENC_OBJECT);
}

function concat($text, $add, $separator = ", ") {
    if(strlen($text) > 1 && strlen($add) > 0) {
        $text .= $separator.$separator.$add;
        return $text;
    }
    else return $add;    
}


?>