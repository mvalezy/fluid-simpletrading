<?php

header('Access-Control-Allow-Methods: GET');

require('depedencies.inc.php');


// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

if(isset($_GET['limit']) && $_GET['limit'] > 0) { $limit = (int) $_GET['limit']; }
else $limit = 2;

if(isset($_GET['status']) && $_GET['status'] != '') { $status = $_GET['status']; }
else $status = 'closed';


// Google Table on Ledger
$Ledger = new Ledger();
/*if($status == 'open')
    $Ledger->selectOpen($limit);
else
    $Ledger->selectClosed($limit);*/

$Ledger->select($limit);

if($debug)
    krumo($Ledger->List);

// First INVERT SORT for Gain  / Loss calculation
$Ledger->List = array_reverse($Ledger->List, true);


if($debug) {
    echo "SORT";
    krumo($Ledger->List);
}

if(isset($Ledger->List) && is_array($Ledger->List) && count($Ledger->List) > 0) {

    $i=0; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'id';
    $googleTableCols[$i]->label  = 'id';
    $googleTableCols[$i]->type   = 'number';

    $i++; $googleTableCols[$i] = new stdClass();    
    $googleTableCols[$i]->id     = 'date';
    $googleTableCols[$i]->label  = 'Date';
    $googleTableCols[$i]->type   = 'datetime';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'status';
    $googleTableCols[$i]->label  = 'Status';
    $googleTableCols[$i]->type   = 'string';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'action';
    $googleTableCols[$i]->label  = 'Action';
    $googleTableCols[$i]->type   = 'string';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'volume';
    $googleTableCols[$i]->label  = 'Vol';
    $googleTableCols[$i]->type   = 'number';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'price';
    $googleTableCols[$i]->label  = 'Price';
    $googleTableCols[$i]->type   = 'number';


    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'total';
    $googleTableCols[$i]->label  = 'Total';
    $googleTableCols[$i]->type   = 'number';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'gain';
    $googleTableCols[$i]->label  = 'Gain';
    $googleTableCols[$i]->type   = 'string';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'reference';
    $googleTableCols[$i]->label  = 'Reference';
    $googleTableCols[$i]->type   = 'string';

    $i++; $googleTableCols[$i] = new stdClass();
    $googleTableCols[$i]->id     = 'info';
    $googleTableCols[$i]->label  = 'Info';
    $googleTableCols[$i]->type   = 'string';

    $i=0;
    $prev = new stdClass();
    $prev->cost = 0;
    $prev->fee = 0;
    foreach($Ledger->List as $data) {

        // ID
        $j=0; $googleTableRows[$i]->c[$j] = new stdClass();
        $googleTableRows[$i]->c[$j]->v = $data->id;

        // Date
        $j++; $googleTableRows[$i]->c[$j] = new stdClass();
        if($data->status == 'open') {
            $googleTableRows[$i]->c[$j]->v = "Date(".date('Y,n,d,H,i,s', strtotime($data->addDate)).")";
            $googleTableRows[$i]->c[$j]->f = date('d/m H:i:s', strtotime($data->addDate));
        }
        else {
            $googleTableRows[$i]->c[$j]->v = "Date(".date('Y,n,d,H,i,s', strtotime($data->closeDate)).")";
            $googleTableRows[$i]->c[$j]->f = date('d/m H:i:s', strtotime($data->closeDate));
        }

        // Status
        $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = $data->status;

        // Action
        $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = $data->orderAction;

        // Prices
        if($data->status == 'closed') {

            // Volume
            $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = $data->volume_executed;
            
            // Price
            $j++; $googleTableRows[$i]->c[$j] = new stdClass();
            $googleTableRows[$i]->c[$j]->v = $data->price_executed;
            $googleTableRows[$i]->c[$j]->f = money_format('%i', $data->price_executed);

            // Total
            $j++; $googleTableRows[$i]->c[$j] = new stdClass();
            $googleTableRows[$i]->c[$j]->v = $data->cost;
            $googleTableRows[$i]->c[$j]->f = money_format('%i', $data->cost);

            // Gain if limit / market
            if($data->type != 'position') {
                if($prev->cost && $data->orderAction == 'sell' && $prev->action == 'buy') {
                    $j++; $googleTableRows[$i]->c[$j] = new stdClass();
                    $gain = $data->cost - $prev->cost - $data->fee - $prev->fee;
                    $googleTableRows[$i]->c[$j]->v = $gain;
                    $googleTableRows[$i]->c[$j]->f = money_format('%i', $gain);
                    $prev->cost = 0;
                    $prev->fee  = 0;
                }
                elseif($data->orderAction == 'sell' && $prev->action == 'sell') {
                    $previ = $i-1;
                    $j++; $googleTableRows[$i]->c[$j] = new stdClass();
                    $gain = $data->cost - $prev->cost - $data->fee - $prev->fee + $googleTableRows[$previ]->c[$j]->v;
                    $googleTableRows[$i]->c[$j]->v = $gain;
                    $googleTableRows[$i]->c[$j]->f = money_format('%i', $gain);
                    $prev->cost = 0;
                    $prev->fee  = 0;
                
                    // reset prev gain
                    $googleTableRows[$previ]->c[$j]->v = '';
                    $googleTableRows[$previ]->c[$j]->f = '';
                }
                elseif($data->orderAction == 'buy') {
                    $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = '';
                    $prev->cost += $data->cost; 
                    $prev->fee  += $data->fee;
                }
                else {
                    $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = '';
                }
            }
            else {
                $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = '';
            }
        }
        else {
            // Volume
            $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = $data->volume;
            
            // Price
            $j++; $googleTableRows[$i]->c[$j] = new stdClass();
            $googleTableRows[$i]->c[$j]->v = $data->price;
            $googleTableRows[$i]->c[$j]->f = money_format('%i', $data->price);

            // Total
            $j++; $googleTableRows[$i]->c[$j] = new stdClass();
            $googleTableRows[$i]->c[$j]->v = $data->total;
            $googleTableRows[$i]->c[$j]->f = money_format('%i', $data->total);

            // Gain / Cancel
            if($data->status == 'open') {
                $j++; $googleTableRows[$i]->c[$j] = new stdClass();
                $googleTableRows[$i]->c[$j]->v = "<a href='index.php?cancel=$data->reference&id=$data->id' class='btn btn-danger btn-xs' role='button'><span class='glyphicon glyphicon-remove'></span> Cancel</a>";
            }
            else {
                $j++; $googleTableRows[$i]->c[$j] = new stdClass();
                $googleTableRows[$i]->c[$j]->v = '';
            }
        }

        // Reference
        $j++; $googleTableRows[$i]->c[$j] = new stdClass(); $googleTableRows[$i]->c[$j]->v = $data->reference;

        // Info
        if($data->status != 'canceled') {
            $j++; $googleTableRows[$i]->c[$j] = new stdClass();
            
            if($data->scalp != 'none') {
                $googleTableRows[$i]->c[$j]->v = "<a href='index.php?cancelScalp=$data->reference&id=$data->id'>";
                if($data->stopLoss)
                    $googleTableRows[$i]->c[$j]->v .= "stop-loss:".money_format('%i', $data->stopLoss);
                if($data->takeProfit)
                    $googleTableRows[$i]->c[$j]->v .= " take-profit:".money_format('%i', $data->takeProfit);

                $googleTableRows[$i]->c[$j]->v .= "</a>";
            }
        }

        // Gain calculation
        if($data->type != 'position') {
            $prev->status = $data->status;
            $prev->action = $data->orderAction;
            $prev->id = $data->id;
        }
        $i++;
        
    }

    if($debug)
        krumo($googleTableRows);

    // Second INVERT SORT for Gain  / Loss calculation
    $googleTableRows = array_reverse($googleTableRows);

    $googleTable = array('cols' => $googleTableCols, 'rows' => $googleTableRows);

    if($debug)
        krumo($googleTable);

    echo json_encode($googleTable, JSON_UNESCAPED_SLASHES); //JSON_PRETTY_PRINT JSON_UNESCAPED_SLASHES

}

?>