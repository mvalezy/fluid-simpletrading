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
 * * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/.../pulse.php > /kunden/homepages/23/d202161969/htdocs/.../log/cron.log 2>&1
 */

$Logger = new Logger('ticker', 1);

/*
 * PULSE
 * Recommended cycle : 1 min
 */

if(TRADE_SIMULATOR_ONLY) {
    $History = new History();
    $price = $History->getLast();
    echo $Logger->log('INFO', "Fixed price at $price", 'Simulator');
}

/*
 * TICKER
 * QUERY Current Price
 */
if(!$price) {
    $Exchange = new Exchange();
    if($Exchange->Ticker() === true) {
        $price = $Exchange->price;

        echo $Logger->log('INFO', TRADE_PAIR."=$price", 'Ticker');

        $History = new History();
        $History->add($price);

        //$lastTick = $History->id;
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
