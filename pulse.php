<?php

/*header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 3600');
die("MAINTENANCE MODE");*/


require('depedencies.inc.php');

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

if(isset($_GET['price']) && $_GET['price'] > 0) { $price = (int) $_GET['price']; }
else $price = 0;

/*
 * CRON CONFIG
 * 30 seconds
 * * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/demo/trade/pulse.php > /kunden/homepages/23/d202161969/htdocs/demo/trade/log/cron.log 2>&1
/*

/*
 * PULSE
 * Recommended cycle : 1 min
 */

if(TRADE_SIMULATOR_ONLY) {
    $History = new History();
    $price = $History->getLast();
}

/*
 * TICKER
 * QUERY Current Price
 */
if(!$price) {
    $Exchange = new Exchange();
    if($Exchange->Ticker() === true) {
        $price = $Exchange->price;

        echo "Ticker:".TRADE_PAIR."=$price\n";

        $History = new History();
        $History->add($price);
        $lastTick = $History->id;
    }
}

/*
 * ALERT
 * SEND Notifications
 */

$Alert = new Alert();
$Alert->select($price);

if(is_array($Alert->List) && count($Alert->List) > 0) {
    foreach($Alert->List as $id => $detail) {
        echo "Alert:send=$detail->price($id)\n";
        
        $Alert->send($id, $price);
    }
}

/*
 * LEDGER
 * BUY / SELL at StopLoss or TakeProfit
 */
 $Ledger = new Ledger();
 $Ledger->selectScalp($price);
 
 if(is_array($Ledger->List) && count($Ledger->List) > 0) {
    foreach($Ledger->List as $id => $detail) {
        echo "Ledger:scalp=sell $detail->volume-$price($id)\n";

        $Ledger->closeScalp($id);

        $Ledger->parentid       = $id;
        $Ledger->orderAction    = 'sell';
        $Ledger->type           = 'market';
        $Ledger->price          = $price;
        $Ledger->volume         = $detail->volume;
        $Ledger->total          = $Ledger->price * $Ledger->volume;

        $Ledger->round();
        $Ledger->add();

        $Exchange = new Exchange();
        if($Exchange->AddOrder($Ledger->id) === true) {
            // STORE Reference of Last Order
            $Ledger->reference = $Exchange->reference;
            $Ledger->updateReference($Ledger->id);
        }
    }
}

/*
 * FIND ORDER
 * Fill reference on Ledger in case of timeout
 */
$Ledger = new Ledger();
if($Ledger->selectEmptyReference() === true) {
    foreach($Ledger->List as $id => $detail) {
        echo "Ledger:emptyReference= $detail->volume-$detail->price($id)\n";

        // 1- ORDER FOUND on Exchange > Update Reference
        $Exchange = new Exchange();
        if($Exchange->searchOrder($detail->addDate, $detail->volume, $detail->price) === true) {
            echo "Exchange:foundOrder= $Exchange->reference\n";
             // STORE Reference of Last Order
             $Ledger->reference = $Exchange->reference;
             $Ledger->updateReference($id);
        }

        // 2- ORDER NOT FOUND on Exchange > Retry Order
        else {
            // RETRY ORDER
            if($Exchange->AddOrder($id) === true) {
                echo "Ledger:createdOrder= $detail->volume-$detail->price($id)\n";
                // STORE Reference of Last Order
                $Ledger->reference = $Exchange->reference;
                $Ledger->updateReference($id);
            }
        }
    }
}

/*
 * CLOSE ORDER
 * Get Transaction details and fill interesting data (status, exec)
 */
$Ledger = new Ledger();
if($Ledger->selectRefresh(3) === true) {
    $ReferenceList = array();
    foreach($Ledger->List as $id => $detail) {
        echo "Ledger:openOrders= $detail->reference($id)\n";
        $ReferenceList[] = $detail->reference;
    }
  
    if(count($ReferenceList)) {
  
        $Exchange = new $Exchange();
        if($Exchange->QueryOrders(0, $ReferenceList) === true) {

            if($debug)
                krumo($Exchange);

            foreach($Exchange->List as $reference => $detail) {
                echo "Ledger:updateByReference= $reference\n";

                $Ledger->status       = $detail->status;
                $Ledger->description  = $detail->description;
                $Ledger->volume_executed = $detail->volume;
                $Ledger->price_executed = $detail->price;
                $Ledger->cost         = $detail->cost;
                $Ledger->fee          = $detail->fee;
                $Ledger->trades       = $detail->trades;

                $Ledger->updateByReference($reference);
            }
        } 
    }
}
 


/*
 * AUTOMATIC ORDER
 * STOP-LOSS / TAKE-PROFIT
 */



/*
 * AUTOMATIC ALERT
 */
if(TRADE_ALERT_AUTOMATIC) {
    $sent = 0;
    $History = new History();
    $alertList = array('15m' => 300, '1h' => 3600, '12h' => 43200, '1d' => 86400);
    foreach($alertList as $range => $time) {
        if(!$sent) {
            $History->getTick(date('Y-m-d H:i:s', time()-$time));
            $min=$History->price/TRADE_ALERT_THRESHOLD;
            $max=$History->price*TRADE_ALERT_THRESHOLD;
            echo "Alert:check= $time - ".round($min,4)."< >".round($max,4)."\n";

            $priceAlert = 0;
            if($price > $max)
                $priceAlert = $max;
            if($price < $min)
                $priceAlert = $min;

            if($priceAlert) {
                echo "Alert:send= $price vs $priceAlert\n";
                $Alert = new Alert();
                if($Alert->snooze('drop') === true) {
                    $Alert->add('drop', $priceAlert);
                    $Alert->send($Alert->id, $price, $range);
                }
                else
                    echo "Alert:snooze\n";
                    
                $sent = 1;
            }
        }
    }
}


// Display Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_finish = $loading_time;
$loading_total_time = round(($loading_finish - $loading_start), 4);
?>

Last execution:<?php echo date('Y-m-d H:i:s'); ?> - Load:<?php echo $loading_total_time; ?>s