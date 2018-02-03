<?php

/*header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 3600');
die("MAINTENANCE MODE");*/


require('depedencies.inc.php');

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

$Logger = new Logger('findOrder', 1);

/*
 * FIND ORDER
 * Fill reference on Ledger in case of timeout
 */
$Ledger = new Ledger();
if($Ledger->selectEmptyReference() === true) {
    foreach($Ledger->List as $id => $detail) {
        echo $Logger->log('WARNING', "emptyReference= $detail->orderAction-$detail->type-$detail->volume-$detail->price($id)", 'Ledger');

        // 1- ORDER FOUND on Exchange > Update Reference
        $Exchange = new Exchange();
        if($Exchange->searchOrder($detail->addDate, $detail->orderAction, $detail->type, $detail->volume, $detail->price) === true) {
            echo $Logger->log('WARNING', "foundOrder= $Exchange->reference", 'Exchange');
             // STORE Reference of Last Order
             $Ledger->reference = $Exchange->reference;
             $Ledger->updateReference($id);
        }

        // 2- ORDER NOT FOUND on Exchange > Retry Order
        elseif($detail->status == 'open') {

            // RETRY only if Order is at least 30 seconds Old
            $addDate = strtotime($detail->addDate);
            $time = time()-30;

            if($addDate < $time) {
                // RETRY ORDER
                if($Exchange->AddOrder($id) === true) {
                    echo $Logger->log('WARNING', "createdOrder= (retry) $detail->volume-$detail->price($id)", 'Ledger');
                    // STORE Reference of Last Order
                    $Ledger->reference = $Exchange->reference;
                    $Ledger->updateReference($id);
                }
            }
        }

        // 3- ARCHIVE ORDER
        elseif($detail->status == 'canceled') {
            echo $Logger->log('WARNING', "archiveOrder= $detail->updateDate($id)", 'Ledger');
            $Ledger->archive($id);
        }
    }
}


$Logger->close();


// Display Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_finish = $loading_time;
$loading_total_time = round(($loading_finish - $loading_start), 4);
?>

Last execution:<?php echo date('Y-m-d H:i:s'); ?> - Load:<?php echo $loading_total_time; ?>s
