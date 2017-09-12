<?php

ob_start();

require_once('config/config.inc.php');
require_once('functions.inc.php'); 
require_once('config/kraken.api.config.php'); 
require_once('config/nma.api.config.php'); 
require_once('class/alert.class.php'); 

// Open SQL connection
$db = connecti();

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = $_GET['debug']; }
else $debug = 0;

if(isset($_GET['purge']) && $_GET['purge'] == 1) {
    setcookie("SimpleKraken", '', 1);
    $purge = 1;
}
else $purge = 0;


/*
 * CALL BACK COOKIE
 */
if(!$purge && isset($_COOKIE["SimpleKraken"]) && $_COOKIE["SimpleKraken"]) {

    $cookie     = json_decode($_COOKIE["SimpleKraken"], true);
    $Last       = $cookie['Last'];
    $Balance    = $cookie['Balance'];
    $OpenOrders = $cookie['OpenOrders'];

    $cache = " - Cached: ";

}

/*
 * START SCENARIO
 * 1- IF POST = ORDER
 * 2- ELFIF POST = ALERT
 * 3- ELSE = DISPLAY
 */


if(isset($_POST['postOrder']) && $_POST['postOrder']) {

    /*
    * POST ORDER
    */

    if(!isset($_POST['volume']) || !$_POST['volume']) {
        $error = new stdClass();
        $error->type    = 'danger';
        $error->message = 'Volume is not set';
        $message[] = $error;
    }

    if(!isset($_POST['priceOrder']) || !$_POST['priceOrder']) {
        $error = new stdClass();
        $error->type    = 'danger';
        $error->message = 'Price is not set';
        $message[] = $error;
    }

    if(!isset($error->message)) {

        if($_POST['order'] == 'buy') {
            $oflags = 'fciq';

            $volume = round( $_POST['volume']-0.0001, 4, PHP_ROUND_HALF_DOWN);
            $price  = round($_POST['priceOrder'], 3);
        }
        else {
            $oflags = 'fcib';

            $volume = round( $_POST['volume']-0.0001, 4, PHP_ROUND_HALF_DOWN);
            $price  = round($_POST['priceOrder'], 3);
        }

        /*$res = $kraken->QueryPrivate('AddOrder', array(
            'pair'      => 'XETHZEUR',
            'order'      => $_POST['order'], 
            'oflags'    => $oflags,

            'ordertype' => $_POST['type'], 
            'volume'    => $volume,
            'price'     => $price,
        ));

        krumo($res);*/

        // Store Success Order & Create new Alert
        $Alert = new Alert();

        $orderReference = $res['result']['txid']['0'];
        $query_ins = "INSERT INTO trade_ledger SET exchange = '".TRADE_EXCHANGE."', pair = '".TRADE_PAIR."', action = '".$_POST['order']."', volume = '$volume', price = '$price', total = '".round($volume * $price, 3)."', reference = '$orderReference'";

        if($_POST['takeProfit_active'] == 1) {
            $query_ins .= ", takeProfit = '".round($price * (1 + $_POST['takeProfit'] / 100), 3)."'";
            $Alert->add(round($price * (1 + $_POST['takeProfit'] / 2 / 100), 3), 'more');
        }

        if($_POST['stopLoss_active'] == 1) {
            $query_ins .= ", stopLoss = '".round($price / (1 + $_POST['stopLoss'] / 100), 3)."'";
            $Alert->add(round($price / (1 + $_POST['stopLoss'] / 2 / 100), 3), 'less');
        }

        $query_ins .= ";";

        $sql_ins = $db->query($query_ins);
        mysqlerr($db, $query_ins);


        // IF Error
        if(isset($res['error']) && is_array($res['error']) && count($res['error']) > 0) {
            foreach($res['error'] as $resmessage) {
                $error = new stdClass();
                $error->type    = 'danger';
                $error->message = $resmessage;
                $message[] = $error;
                $retry = 1;
            }
        }
        // ELSE (Order OK)
        elseif(isset($res['result']) && is_array($res['result']) && count($res['result']) > 0) {
            $result = new stdClass();
            $result->type    = 'success';
            $result->message = $res['result']['descr']['order'];
            $message[] = $result;

            // Delete Cookie
            setcookie("SimpleKraken", '', 1);
        }
        else {
            krumo($res);
        }

        
    }
 
} // END POST ORDER

elseif(isset($_POST['addAlert']) && $_POST['addAlert'] == 1) {

    /*
     * ALERT
     */


    if(!isset($_POST['priceAlert']) || !$_POST['priceAlert']) {
        $error = new stdClass();
        $error->type    = 'danger';
        $error->message = 'Price is not set';
        $message[] = $error;
    }

    $Alert = new Alert();

    $Alert->add($_POST['priceAlert'], $_POST['operator']);

    $result = new stdClass();
    $result->type    = 'success';
    $result->message = "New alert at ".$_POST['operator']." ".$_POST['priceAlert']." posted.";
    $message[] = $result;

} // END ADD ALERT


else {

    /*
     * CANCEL
     */

    if(isset($_GET['cancel']) && $_GET['cancel']) {
        $res = $kraken->QueryPrivate('CancelOrder', array('txid' => $_GET['cancel']));
        krumo($res);

        if(isset($res['error']) && is_array($res['error']) && count($res['error']) > 0) {
            foreach($res['error'] as $resmessage) {
                $error = new stdClass();
                $error->type    = 'danger';
                $error->message = $resmessage;
                $message[] = $error;
                $retry = 1;
            }
        }
        elseif(isset($res['result']) && is_array($res['result']) && count($res['result']) > 0) {
            $result = new stdClass();
            $result->type    = 'success';
            $result->message = 'Order '.$_GET['cancel']. ' canceled';
            $message[] = $result;

            unset($OpenOrders);
        }
    }

    /*
    * DISPLAY
    */


    // Query Last ETH price 
    if(!isset($Last)) {
        $res = $kraken->QueryPublic('Ticker', array('pair' => 'XETHZEUR'));
        if(isset($res['result'])) {
            if(isset($res['result']['XETHZEUR']['c']['0'])) {
                $Last = $res['result']['XETHZEUR']['c']['0'];
            }
            else $Last = 0;
        } else {
            $Last = 0;
            output($res['error'], 'warning');
        }

        $setcookie = 1;
    } else $cache .= " cookie ";

    // Query Balance
    if(!isset($Balance['ZEUR']) || !isset($Balance['XETH'])) {
        $res = $kraken->QueryPrivate('Balance');
        if(isset($res['result'])) {
            $Balance = $res['result'];
        }
        else {
            $Balance['ZEUR'] = 0;
            $Balance['XETH'] = 0;
            output($res['error'], 'warning');
        }

        $setcookie = 1;
    } else $cache .= " balance ";

    // Query Orders
    if(!isset($OpenOrders)) {
        $res = $kraken->QueryPrivate('OpenOrders', array('trades' => true));
        if(isset($res['result'])) {
            $OpenOrders = $res['result'];
        }
        else {
            $OpenOrders['open'] = array();
            output($res['error'], 'warning');
        }

        $setcookie = 1;
    } else $cache .= " orders ";


    // Buy or Sell ?
    $ETHValue = $Balance['XETH'] * $Last;
    if($Balance['ZEUR'] >= $ETHValue) {
            $order_default = 'buy';
            $cssEUR = 'info';
            $cssETH = 'default';
    }
    else {
        $order_default = 'sell';
        $cssEUR = 'default';
        $cssETH = 'info';
    }


    // Set Cookie
    if($setcookie) {
        $cookie = array();
        $cookie['Balance']      = $Balance;
        $cookie['Last']         = $Last;
        $cookie['OpenOrders']   = $OpenOrders;

        setcookie("SimpleKraken", json_encode($cookie), time()+3600);
    }


    // Google Chart on Ledger
    $query = "SELECT price, addDate FROM trade_history ORDER BY addDate DESC LIMIT 50";
    $sql = $db->query($query);
    mysqlerr($db, $query);

    if(isset($sql->num_rows) && $sql->num_rows > 0) {
        $i=0;
        $googleChartData = array();
        while($row = $sql->fetch_object()) {
            $googleChartData[$i] = new stdClass();
            $googleChartData[$i] = $row;
            $i++;
        }

        krsort($googleChartData);

        $googleChartRows = "";
        foreach($googleChartData as $data) {
            $googleChartRows .= "\t\t\t\t[new Date (".date('Y,n,d,H,i,s', strtotime($data->addDate))."), ".$data->price."],\n";
        }

    }

    // Active Alerts
    $Alert = new Alert();
    $Alert->select();
    

	
} // END DISPLAY

ob_flush();

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Simple Kraken Beta</title>
        
        <script src="//code.jquery.com/jquery-3.1.0.min.js" integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s=" crossorigin="anonymous"></script>
    
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

        <!-- Latest compiled and minified JavaScript -->
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

        <!-- Google Graph API -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

        <style>
            .btn-circle {
                width: 30px;
                height: 30px;
                text-align: center;
                padding: 6px 0;
                font-size: 12px;
                line-height: 1.428571429;
                border-radius: 15px;
            }
            .btn-circle.btn-lg {
                width: 50px;
                height: 50px;
                padding: 10px 16px;
                font-size: 18px;
                line-height: 1.33;
                border-radius: 25px;
            }
            .btn-circle.btn-xl {
                width: 70px;
                height: 70px;
                padding: 10px 16px;
                font-size: 24px;
                line-height: 1.33;
                border-radius: 35px;
            }
            .loader {
                display:none;
            }
            .loader div {
                position: fixed;
                left: 0px;
                top: 0px;
                width:100%;
                height:100%;
            }

            .loader-bg {
                z-index: 9998;
                filter:alpha(opacity=50);
                opacity:0.5;
                background-color:white;
            }

            .loader-img {
                z-index: 9999;
                background: url('images/Preloader_7.gif') 50% 50% no-repeat;
                filter:alpha(opacity=100);
                opacity:1;
            }

            .colorgraph {
                height: 7px;
                border-top: 0;
                background: #c4e17f;
                border-radius: 5px;
                background-image: -webkit-linear-gradient(left, #c4e17f, #c4e17f 12.5%, #f7fdca 12.5%, #f7fdca 25%, #fecf71 25%, #fecf71 37.5%, #f0776c 37.5%, #f0776c 50%, #db9dbe 50%, #db9dbe 62.5%, #c49cde 62.5%, #c49cde 75%, #669ae1 75%, #669ae1 87.5%, #62c2e4 87.5%, #62c2e4);
                background-image: -moz-linear-gradient(left, #c4e17f, #c4e17f 12.5%, #f7fdca 12.5%, #f7fdca 25%, #fecf71 25%, #fecf71 37.5%, #f0776c 37.5%, #f0776c 50%, #db9dbe 50%, #db9dbe 62.5%, #c49cde 62.5%, #c49cde 75%, #669ae1 75%, #669ae1 87.5%, #62c2e4 87.5%, #62c2e4);
                background-image: -o-linear-gradient(left, #c4e17f, #c4e17f 12.5%, #f7fdca 12.5%, #f7fdca 25%, #fecf71 25%, #fecf71 37.5%, #f0776c 37.5%, #f0776c 50%, #db9dbe 50%, #db9dbe 62.5%, #c49cde 62.5%, #c49cde 75%, #669ae1 75%, #669ae1 87.5%, #62c2e4 87.5%, #62c2e4);
                background-image: linear-gradient(to right, #c4e17f, #c4e17f 12.5%, #f7fdca 12.5%, #f7fdca 25%, #fecf71 25%, #fecf71 37.5%, #f0776c 37.5%, #f0776c 50%, #db9dbe 50%, #db9dbe 62.5%, #c49cde 62.5%, #c49cde 75%, #669ae1 75%, #669ae1 87.5%, #62c2e4 87.5%, #62c2e4);
            }
        </style>

        <script type="text/javascript">
            $(document).ready(function() {

                $("#priceOrder").keyup(function() {

                    var order  = $("#order input[type='radio']:checked").val();
                    var price = $("#priceOrder").val();

                    if(order == 'buy') {
                        var total = $("#total").val();
                        var volume = Math.round((total / price * 100000 )) / 100000;

                        $("#volume").val(volume);

                    }
                    else {
                        var volume = $("#volume").val();
                        var total = Math.round((volume * price * 100000 )) / 100000;

                        $("#total").val(total);

                    }

                });

                $("#BalanceZEUR").click(function() {

                    var balance  = Math.round(($("#BalanceZEUR").val() * 100 )) / 100;
                    $("#total").val(balance);

                });

                $("#BalanceXETH").click(function() {

                    var balance  = Math.round(($("#BalanceXETH").val() * 100000 )) / 100000;
                    $("#volume").val(balance);

                });

            });


            google.charts.load('current', {packages: ['corechart', 'line']});
            google.charts.setOnLoadCallback(drawBasic);

            function drawBasic() {

                var data = new google.visualization.DataTable();
                data.addColumn('date', 'Time'); // date | timeofday
                data.addColumn('number', 'Price');
                //data.addColumn({type: 'string', role: 'tooltip'});

                data.addRows([<?php echo $googleChartRows; ?>]);

                var options = {
                    legend: 'none',
                    hAxis: {
                        //title: 'Date'
                        //format: 'HH:mm:ss',
                        gridlines: {
                            count: -1,
                            units: {
                                hours: {format: ['hh:mm:ss']},
                                minutes: {format: ['HH:mm']}
                            }
                        },
                        minorGridlines: {
                            units: {
                                hours: {format: ['hh:mm:ss']},
                                minutes: {format: ['HH:mm']}
                            }
                        }
                        
                    },
                    vAxis: {
                        //title: 'Price',
                        format: '###,###,###.00 €', //'currency'
                    },
                    pointSize: 0,
                    
                };

                var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

                chart.draw(data, options);
            }

        </script>
                
        </head>
    <body>

        <div class="container-fluid">

            <div id="loginbox" style="margin-top:5px;" class="mainbox col-md-12 col-sm-12">                    
            <div class="panel panel-primary" >

                <div class="panel-heading">
                    <div class="panel-title"><a href="index.php">Simple Kraken</a></div>
                    <div style="float:right; font-size: 80%; position: relative; top:-18px"><a href="index.php?purge=1" class="btn btn-default btn-xs" role="button"><span class="glyphicon glyphicon-flash"></span> Clear cache</a></div>
                </div>     

                <div style="padding-top:10px" class="panel-body" >

<?php
if(count($message) > 0) {
    foreach($message as $key => $resp) { ?>
                    <div class="alert alert-<?php echo $resp->type; ?> col-sm-12"><b><?php echo $resp->message; ?></b></div>
<?php
    }
}

if(isset($_POST['postOrder']) && $_POST['postOrder']) {

 
} // END POST ORDER

else {

?>

                    <div class="col-sm-5 col-lg-5">
                        <div class="row">

                            <div class="col-sm-6 col-lg-6">
                                <h3>Balance</h3>
                                    
                                <div class="col-xs-3 col-sm-6 col-lg-6">
                                    <input id="UpdateBalanceZEUR" class="btn btn-<?php echo $cssEUR; ?>" type="button" value="<?php echo number_format($Balance['ZEUR'], 2, '.', ' '); ?> EUR">
                                    <input id="BalanceZEUR" type="hidden" value="<?php echo $Balance['ZEUR']; ?>">
                                </div>
                                <div class="col-xs-3 col-sm-6 col-lg-6">
                                    <input id="UpdateBalanceXETH" class="btn btn-<?php echo $cssETH; ?>" type="button" value="<?php echo number_format($Balance['XETH'], 4, '.', ' '); ?> ETH">
                                    <input id="BalanceXETH" type="hidden" value="<?php echo $Balance['XETH']; ?>">
                                </div>
                            </div>

                            <div class="col-xs-3 col-sm-6 col-lg-6">
                                <h3>Last</h3>
                                <div class="col-xs-12 col-sm-12 col-lg-12"><?php echo round($Last, 2); ?> EUR</div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-sm-12 col-lg-12">
                                <h3>Orders</h3>
                                <div class="row">
                                    
                    <?php
                                        foreach($OpenOrders['open'] as $OrderID => $OrderContent) {
                    ?>
                                            <div class="col-sm-12 col-lg-12">
                                                <?php echo $OrderContent['descr']['order']; ?>
                                                (<?php echo $OrderContent['status']; ?>)
                                                <a href="index.php?cancel=<?php echo $OrderID; ?>" class="btn btn-danger btn-xs" role="button"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
                                            </div>
                    <?php
                                        }
                    ?>
                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div id="chart_div"></div>
                        </div>

                    </div>



            <div class="col-sm-7 col-lg-7">

<!-- <hr class="colorgraph"><br> -->

<div class="row">

                <h3>Create Order</h3>

                <form id="order" name="order" action="index.php" method="post" class="form-horizontal require-validation" role="form">


                    <!--     <div class='form-group required'>
                            <div class='error form-group hide'>
                            <div class='alert-danger alert'>
                            Please correct the errors and try again.
                        
                            </div>
                            </div>
                        </div> -->

                    <div class="col-sm-12 col-lg-12">
                        <div class="form-group">
                            <div class="col-md-6">
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="order" id="order1" value="buy"<?php if($order_default == 'buy') { echo ' checked'; } ?>>
                                        Buy
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="order" id="order2" value="sell"<?php if($order_default == 'sell') { echo ' checked'; } ?>>
                                        Sell
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6">
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="type" id="type1" value="limit" checked>
                                        Limit
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="type" id="type2" value="market">
                                        Market
                                    </label>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="form-group required">
                            <label for="volume" class="control-label col-md-4">Volume</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <input type="text" class="form-control" id="volume" name="volume" <?php
                                
                                if($order_default == 'buy') {
                                    echo 'value="'.round($Balance['ZEUR']/$Last,5).'"';
                                }
                                else {
                                    echo 'value="'.round($Balance['XETH'],6).'"';
                                }

                                ?>>
                                <div class="input-group-addon">ETH</div>
                                </div>
                            </div>  
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="form-group required">
                            <label for="priceOrder" class="control-label col-md-4">Price</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <input type="text" class="form-control" id="priceOrder" name="priceOrder" value="<?php echo $Last; ?>">
                                <div class="input-group-addon">EUR</div>
                                </div>
                            </div>  
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="form-group">
                            <label for="volume" class="control-label col-md-4">Total</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <input type="text" class="form-control" id="total" name="total" <?php
                                
                                if($order_default == 'buy') {
                                    echo 'value="'.round($Balance['ZEUR'],4).'"';
                                }
                                else {
                                    echo 'value="'.round($Balance['XETH']*$Last,2).'"';
                                }

                                ?>>
                                <div class="input-group-addon">EUR</div>
                                </div>
                            </div>  
                        </div>
                        </div>
                    </div>


                    <div class="col-sm-12 col-lg-12">

                        <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="form-group">
                            <label for="volume" class="control-label col-md-4">Take-Profit</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" aria-label="Activate Sell at Take Profit" id="takeProfit_active" name="takeProfit_active" value="1">
                                </span>
                                <input type="text" class="form-control" aria-label="" id="takeProfit" name="takeProfit" value="10">
                                <div class="input-group-addon">%</div>
                                </div>
                            </div>  
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="form-group">
                            <label for="volume" class="control-label col-md-4">Stop-Loss</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" aria-label="Activate Sell at Stop Loss Profit" id="stopLoss_active" name="stopLoss_active" value="1">
                                </span>
                                <input type="text" class="form-control" aria-label="" id="stopLoss" name="stopLoss" value="3">
                                <div class="input-group-addon">%</div>
                                </div>
                            </div>  
                        </div>
                        </div>

                    </div>

                    <div class="col-sm-12 col-lg-12">
                        <div class="form-group">
                                <input type="hidden" name="postOrder" value="1" />
                                <button type="submit" name="submitOrder" class="btn btn-success btn-lg btn-block"><span class="glyphicon glyphicon-plus-sign"></span> Post Order</button>
                        </div>
                    </div>

                
                </form>


                <h3>Android notifications</h3>
                <form id="alert" name="alert" action="index.php" method="post" class="form-horizontal require-validation" role="form">
                
                    <div class="col-sm-12 col-lg-12">

                        <div  class="col-sm-12 col-md-8 col-lg-2">
                                <?php
if(is_array($Alert->List) && count($Alert->List) > 0) {
    echo "<ul>\n";
    foreach($Alert->List as $alertDetail) {

        switch($alertDetail->operator) {
            case 'less':
                $operator = '<';
                break;
            case 'more':
                $operator = '>';
                break;
            case 'even':
                $operator = '=';
                break;
        }

        echo "<li>$operator $alertDetail->price</li>\n";
    }
    echo "</ul>\n";

}
                                ?>
                        </div>

                        <div class="form-group">
                            
                            <div class="col-sm-4 col-md-3 col-lg-2">
                                <div class="ib btn-group">
                                    <button type="button" class="btn btn-mini active btn-info" value="less" title="" autocomplete="off" data-original-title="" clicked="clicked"><</button><button type="button" class="btn btn-mini" value="even" title="" autocomplete="off" data-original-title="">=</button><button type="button" class="btn btn-mini" value="more" title="" autocomplete="off" data-original-title="">></button>
                                </div>

                                </div>

                                <div class="col-md-9 col-lg-10">
                        
                                <div class="input-group">
                                    <input type="hidden" id="operator" name="operator" value="less">   
                                
                                    <input type="text" class="form-control" id="priceAlert" name="priceAlert" value="<?php echo $Last; ?>">
                                    <div class="input-group-addon">EUR</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 col-lg-12">
                        <div class="form-group">
                            <input type="hidden" name="addAlert" value="1" />
                            <button type="submit" name="submitAlert" class="btn btn-info btn-lg btn-block"><span class="glyphicon glyphicon-plus-sign"></span> Add notification</button>
                        </div>
                    </div>
                </form>

            </div>

            </div>
            </div>

<?php
} // END DISPLAY
?>

        </div>
    </body>
</html>
