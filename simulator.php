<?php

require('depedencies.inc.php');

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 1;

if(isset($_GET['close']) && $_GET['close'] > 0) { $close = (int) $_GET['close']; }
else $close = 0;

if($close) {
    $Ledger = new Ledger();
    $Ledger->get($close);

    $query = "UPDATE trade_ledger SET status = 'closed', closeDate = NOW(), volume_executed = $Ledger->volume, price_executed = $Ledger->price, cost = $Ledger->total, fee = 2 WHERE id = ".$db->real_escape_string($close)." LIMIT 1;";
    $sql = $db->query($query);
    mysqlerr($db, $query);
}
else {

    // add fake transactions
    $dateFormat = 'Y-m-d H:m:i';
    $date1minute = 60;
    $date1hour = $date1minute*60;
    $date1day = $date1hour*24;
    $date1month = $date1day*30;
    $date1year = $date1month*12;

    $randday = rand(-90*$date1day, 90*$date1day);


    $array_action = array('buy', 'sell');


    $data = new stdClass();
    $data->randAction = rand(0, 1);
    $data->action = $array_action[$data->randAction];
    $data->volume = rand(1, 20);
    $data->price = 200+rand(-20, 20);
    $data->price_executed = $data->price + rand(-5, 5);
    $data->total = $data->price*$data->volume;
    $data->cost = $data->price_executed*$data->volume;
    $data->addDateTimestamp = time()-$randday;
    $data->closeDateTimestamp = time()-$randday+$date1minute;
    $data->addDate = date($dateFormat, $data->addDateTimestamp);
    $data->closeDate = date($dateFormat, $data->closeDateTimestamp);
    krumo($data);

    $query = "INSERT INTO trade_ledger SET 
        status = 'closed',
        reference = 'SIMULATOR',
        orderAction = '$data->action',
        type = 'limit',
        volume = $data->volume,
        volume_executed = $data->volume,
        price = $data->price,
        price_executed = $data->price_executed,
        total = $data->total,
        cost = $data->cost,
        fee = 2,
        addDate = '$data->addDate' - INTERVAL 1 YEAR,
        closeDate = '$data->closeDate' - INTERVAL 1 YEAR,
        description = 'test Simulator $data->action'
        ;";
    $sql = $db->query($query);
    mysqlerr($db, $query);

    $data->randAction = rand(0, 1);
    $data->action = $array_action[$data->randAction];
    $data->price = 200+rand(-20, 20);
    $data->price_executed = $data->price + rand(-5, 5);
    $data->total = $data->price*$data->volume;
    $data->cost = $data->price_executed*$data->volume;
    $data->addDateTimestamp = time()-$randday+$date1day;
    $data->closeDateTimestamp = time()-$randday+$date1minute+$date1day;
    $data->addDate = date($dateFormat, $data->addDateTimestamp);
    $data->closeDate = date($dateFormat, $data->closeDateTimestamp);
    krumo($data);

    $query = "INSERT INTO trade_ledger SET 
    status = 'closed',
    reference = 'SIMULATOR',
    orderAction = '$data->action',
    type = 'limit',
    volume = $data->volume,
    volume_executed = $data->volume,
    price = $data->price,
    price_executed = $data->price_executed,
    total = $data->total,
    cost = $data->cost,
    fee = 2,
    addDate = '$data->addDate' - INTERVAL 1 YEAR,
    closeDate = '$data->closeDate' - INTERVAL 1 YEAR,
    description = 'test Simulator $data->action'
    ;";
    $sql = $db->query($query);
    mysqlerr($db, $query);
}
?>