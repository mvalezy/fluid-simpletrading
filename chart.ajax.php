<?php

//header('Access-Control-Allow-Methods: POST');

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
if(isset($_POST['debug']) && $_POST['debug'] > 0) { $debug = (int) $_POST['debug']; }
else $debug = 0;

if(isset($_POST['limit']) && $_POST['limit'] > 0) { $limit = (int) $_POST['limit']; }
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

                    /*data.addColumn('date', 'Time'); // date | timeofday
                data.addColumn('number', 'Price');*/
                //data.addColumn({type: 'string', role: 'tooltip'});

    $googleChartRows = $googleChartCols = array();  

    $objDate = new stdClass();
    $objDate->id    = 'date';
    $objDate->label = 'Time';
    $objDate->type  = 'datetime';
    $googleChartCols[] = $objDate;

    $objPrice = new stdClass();
    $objPrice->id    = 'price';
    $objPrice->label = 'Price';
    $objPrice->type  = 'number';
    $googleChartCols[] = $objPrice;

    foreach($googleChartData as $i => $data) {
        
        $objRow     = new stdClass();
        $objRow->c = array();

        $objDate   = new stdClass();
        $objDate->v = "Date(".date('Y,n,d,H,i,s', strtotime($data->addDate)).")";
        //$objDate->f = date('H:i', strtotime($data->addDate));
        $objDate->f = date('H:i', strtotime($data->addDate));
        $objRow->c[] = $objDate;

        $objPrice   = new stdClass();
        $objPrice->v = $data->price;
        $objRow->c[] = $objPrice;
        
        /*$objRow     = new stdClass();
        $objRow->c = array();

        $objRow->c[] = "{v: new Date (".date('Y,n,d,H,i,s', strtotime($data->addDate)).")}";
        $objRow->c[] = "{v: $data->price}";*/


        $googleChartRows[] = $objRow;
        
    }

    $googleChart = array('cols' => $googleChartCols, 'rows' => $googleChartRows);

    echo json_encode($googleChart, JSON_UNESCAPED_SLASHES); //JSON_PRETTY_PRINT JSON_UNESCAPED_SLASHES

}

?>