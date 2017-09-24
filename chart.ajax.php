<?php

header('Access-Control-Allow-Methods: GET');

// CONFIG
require_once('config/config.inc.php');

// UTILS AND THIRD PARTY
require_once('functions.inc.php'); 
//require_once('config/kraken.api.config.php'); 
//require_once('config/nma.api.config.php'); 

// Class
require_once('class/error.class.php'); 
require_once('class/history.class.php'); 
//require_once('class/ledger.class.php'); 
//require_once('class/alert.class.php');

// API
//require_once('api/kraken.api.php');
//require_once('api/nma.api.php');

// Open SQL connection
$db = connecti();

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

if(isset($_GET['limit']) && $_GET['limit'] > 0) { $limit = (int) $_GET['limit']; }
else $limit = 2;



// Google Chart on History
$History = new History();
$History->select($limit);

if(isset($History->List) && is_array($History->List) && count($History->List) > 0) {

    $i=0;
    $googleChartData = array();

    foreach($History->List as $id => $detail) {
        $googleChartData[$i] = new stdClass();
        $googleChartData[$i] = $detail;
        $i++;
    }

    krsort($googleChartData);
   
    $i=0;
    $googleChartCols[$i]->id     = 'date';
    $googleChartCols[$i]->label  = 'Time';
    $googleChartCols[$i]->type   = 'datetime';
    
    $i++;
    $googleChartCols[$i]->id     = 'price';
    $googleChartCols[$i]->label  = 'Price';
    $googleChartCols[$i]->type   = 'number';

    /*
    // If strict declaration needed
    $googleChartRows = $googleChartCols = array();
    $objCol = new stdClass();
    $objCol->id    = 'date';
    $objCol->label = 'Time';
    $objCol->type  = 'datetime';
    $googleChartCols[] = $objCol;

    $objCol = new stdClass();
    $objCol->id    = 'price';
    $objCol->label = 'Price';
    $objCol->type  = 'number';
    $googleChartCols[] = $objCol;
    */

    $i=0;
    foreach($googleChartData as $data) {
        
        $googleChartRows[$i]->c[0]->v = "Date(".date('Y,n,d,H,i,s', strtotime($data->addDate)).")";
        if($limit < 50)        
            $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
        elseif($limit < 1000)        
            $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
        else
            $googleChartRows[$i]->c[0]->f = date('d/m H:i', strtotime($data->addDate));

        $googleChartRows[$i]->c[1]->v = $data->price;

        $i++;

        /*
        // If strict declaration needed
        $objRow     = new stdClass();
        $objRow->c = array();

        $objData   = new stdClass();
        $objData->v = "Date(".date('Y,n,d,H,i,s', strtotime($data->addDate)).")";
        if($limit < 50)        
            $objData->f = date('H:i', strtotime($data->addDate));
        elseif($limit < 1000)        
            $objData->f = date('H:i', strtotime($data->addDate));
        else
            $objData->f = date('d/m H:i', strtotime($data->addDate));
        $objRow->c[] = $objData;

        $objData   = new stdClass();
        $objData->v = $data->price;
        $objRow->c[] = $objData;

        $googleChartRows[] = $objRow;
        */       
    }

    $googleChart = array('cols' => $googleChartCols, 'rows' => $googleChartRows);

    echo json_encode($googleChart, JSON_UNESCAPED_SLASHES); //JSON_PRETTY_PRINT JSON_UNESCAPED_SLASHES

}

?>