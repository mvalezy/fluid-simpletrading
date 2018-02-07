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
 * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/client/simpletrading/pulse.ticker.php > /kunden/homepages/23/d202161969/htdocs/client/simpletrading/log/cron_pulse.log 2>&1
 * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/client/simpletrading/pulse.php > /kunden/homepages/23/d202161969/htdocs/client/simpletrading/log/cron_pulse.log 2>&1
 * * * * * (sleep 30; /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/client/simpletrading/pulse.findOrder.php > /kunden/homepages/23/d202161969/htdocs/client/simpletrading/log/cron_findOrder.log 2>&1)
 * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/client/simpletrading/pulse.findOrder.php > /kunden/homepages/23/d202161969/htdocs/client/simpletrading/log/cron_findOrder.log 2>&1
 */

$Logger = new Logger('pulse', 1);

/*
 * PULSE
 * Get Last Ticket Price
 */

$History = new History();
$price = $History->getLast();
echo $Logger->log('INFO', "Query last price at $price", 'Pulse');


/*
 * ALERT
 * SEND Notifications
 */

$Alert = new Alert();
$Alert->select($price);

if(is_array($Alert->List) && count($Alert->List) > 0) {
    foreach($Alert->List as $id => $detail) {
        echo $Logger->log('INFO', "send=$detail->price($id)", 'Alert');

        $Alert->send($id, $price);
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
        echo $Logger->log('INFO', "openOrders= $detail->reference($id)", 'Ledger');
        $ReferenceList[] = $detail->reference;
    }

    if(count($ReferenceList)) {

        $Exchange = new Exchange();
        if($Exchange->QueryOrders(0, $ReferenceList) === true) {

            if($debug)
                krumo($Exchange);

            foreach($Exchange->List as $reference => $detail) {
                echo $Logger->log('INFO', "updateByReference= $reference", 'Ledger');

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
 * LEDGER
 * BUY / SELL at Position
 */
$Ledger = new Ledger();
$Ledger->selectPosition($price);

if(is_array($Ledger->List) && count($Ledger->List) > 0) {
   foreach($Ledger->List as $id => $detail) {
        echo $Logger->log('WARNING', "position=sell $detail->volume-$price($id)", 'Ledger');

        $Ledger->reference       = 'position';
        $Ledger->description     = "$detail->orderAction $detail->volume $detail->pair @ position $detail->price";
        $Ledger->volume_executed = $detail->volume;
        $Ledger->price_executed  = $detail->price;
        $Ledger->cost            = $detail->total;
        $Ledger->update($id);

        $Ledger->close($id, $price);

        $Ledger->parentid       = $id;
        $Ledger->orderAction    = $detail->orderAction;
        $Ledger->type           = 'market';
        $Ledger->price          = $price;
        $Ledger->volume         = $detail->volume;
        $Ledger->total          = $Ledger->price * $Ledger->volume;

        $Ledger->round();
        $Ledger->add();

        $Exchange = new Exchange();
        if($Exchange->AddOrder($Ledger->id) === true) {
            echo $Logger->log('INFO', "createdOrder= (position) $detail->volume-$detail->price($id) - $Exchange->Success", 'Ledger');

            // STORE Reference of Last Order
            $Ledger->reference = $Exchange->reference;
            $Ledger->updateReference($Ledger->id);
        }
        else {
            echo $Logger->log('ERROR', "createdOrder= (position) $detail->volume-$detail->price($id) - $Exchange->Error", 'Ledger');
       }
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
       echo $Logger->log('INFO', "scalp=sell $detail->volume-$price($id)", 'Ledger');

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
            echo $Logger->log('INFO', "createdOrder= (scalp) $detail->volume-$detail->price($id) - $Exchange->Success", 'Ledger');

            // STORE Reference of Last Order
            $Ledger->reference = $Exchange->reference;
            $Ledger->updateReference($Ledger->id);
       }
       else {
            echo $Logger->log('ERROR', "createdOrder= (scalp) $detail->volume-$detail->price($id) - $Exchange->Error", 'Ledger');
       }
   }
}



/*
 * AUTOMATIC ALERT
 */
if(TRADE_ALERT && TRADE_ALERT_AUTOMATIC) {
    $sent = 0;
    $History = new History();
    $alertList = array('5m' => 300, '15m' => 900, '1h' => 3600, '2h' => 7200); // , '1d' => 86400
    foreach($alertList as $range => $time) {
        if(!$sent) {
            $History->getTick(date('Y-m-d H:i:s', time()-$time));
            $min=$History->price/TRADE_ALERT_THRESHOLD;
            $max=$History->price*TRADE_ALERT_THRESHOLD;
            //echo $Logger->log('INFO', "check= $range - ".round($min,4)."< >".round($max,4), 'Alert');

            $priceAlert = 0;
            if($price > $max)
                $priceAlert = $max;
            if($price < $min)
                $priceAlert = $min;

            if($priceAlert) {
                echo $Logger->log('WARNING', "send= $price vs $priceAlert", 'Alert');
                $Alert = new Alert();
                if($Alert->snooze('drop') === true) {
                    $Alert->add('drop', $priceAlert);
                    $Alert->send($Alert->id, $price, $range);
                }
                else
                    echo $Logger->log('INFO', "snooze $range - ".round($min,4)."< >".round($max,4), 'Alert');

                $sent = 1;
            }
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
