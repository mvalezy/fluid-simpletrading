<?php

header('Access-Control-Allow-Methods: GET');

require('depedencies.inc.php');

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

if(isset($_GET['unit']) && $_GET['unit'] != '') { $unit = $_GET['unit']; }
else $unit = '1m';



// Google Chart on History
$History = new History();
$History->selectChart($unit);

if($debug)
    krumo($History);

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
    $googleChartCols[$i] = new stdClass();
    $googleChartCols[$i]->id     = 'date';
    $googleChartCols[$i]->label  = 'Time';
    $googleChartCols[$i]->type   = 'datetime';
    
    $i++;
    $googleChartCols[$i] = new stdClass();
    $googleChartCols[$i]->id     = 'price';
    $googleChartCols[$i]->label  = 'Price';
    $googleChartCols[$i]->type   = 'number';

    $i=0;
    foreach($googleChartData as $data) {

        $addDate = strtotime($data->addDate);

        $googleChartRows[$i]->c[0] = new stdClass();
        $googleChartRows[$i]->c[0]->v = "Date(".date('Y,n,j,H,i,s', $addDate).")";
        $googleChartRows[$i]->c[0]->f = date('d/m/Y H:i', strtotime($addDate));

        /*switch($unit) {
            case '1m':
            default:
                $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
                break;
            case '5m':
                $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
                break;
            case '15m':
                $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
                break;
            case '30m':
                $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
                break;
            case '1h':
                $googleChartRows[$i]->c[0]->f = date('H:i', strtotime($data->addDate));
                break;
            case '4h':
                $googleChartRows[$i]->c[0]->f = date('d/m H:i', strtotime($data->addDate));
                break;
            case '1d':
                $googleChartRows[$i]->c[0]->f = date('d/m H:i', strtotime($data->addDate));
                break;
        } */  

        $googleChartRows[$i]->c[1] = new stdClass();
        $googleChartRows[$i]->c[1]->v = $data->price;

        $i++;

    }

    $googleChart = array('cols' => $googleChartCols, 'rows' => $googleChartRows);

    if($debug)
        krumo($googleChart);

    echo json_encode($googleChart, JSON_UNESCAPED_SLASHES); //JSON_PRETTY_PRINT JSON_UNESCAPED_SLASHES

}

?>