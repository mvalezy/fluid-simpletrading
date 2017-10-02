<?php

ob_start();

require('depedencies.inc.php');

// Initiate vars
$message = array();

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 0;

if(isset($_GET['id']) && $_GET['id'] > 0) { $id = (int) $_GET['id']; }
else $id = 0;

if(isset($_GET['cancel']) && $_GET['cancel'] != '') { $cancel = $_GET['cancel']; }
else $cancel = '';


$Logger = new Logger('trader', 1, 'html');

if(isset($_GET['purge']) && $_GET['purge'] == 1) {
    setcookie("SimpleTrader", '', 1);
    $purge = 1;
}
else $purge = 0;

if($debug)
    krumo($_POST);


/*
 * CALL BACK COOKIE
 */
if(!$purge && isset($_COOKIE["SimpleTrader"]) && $_COOKIE["SimpleTrader"]) {

    $cookie     = json_decode($_COOKIE["SimpleTrader"], true);
    $Balance    = $cookie['Balance'];
    $cache = " - Cached: ";

}


/*
 * START SCENARIO
 * 1- IF POST = ORDER
 * 2- ELFIF POST = ALERT
 * 3- ELSE = DISPLAY
 */


if(isset($_POST['addOrder']) && $_POST['addOrder']) {

    /*
    * POST ORDER
    */

    $Ledger = new Ledger();
    $Ledger->getVars($_POST);

    if(!isset($Ledger->volume) || !$Ledger->volume)
        $message[] = $Logger->display('danger', 'Volume is not set');

    if(!isset($Ledger->price) || !$Ledger->price) {
        $message[] = $Logger->display('danger', 'Price is not set');

    }

    if(count($message) == 0) {

        $Ledger->round();

        // STORE Order
        $Ledger->add();

        if($debug)
            krumo($Ledger);

        // POST Exchange Order
        $Exchange = new Exchange();

        if($Exchange->AddOrder($Ledger->id) === true) {
            // STORE Reference of Last Order
            $Ledger->reference = $Exchange->reference;
            $Ledger->updateReference($Ledger->id);

            $message[] = $Logger->display('success', $Exchange->Success);

            // Delete Cookie
            setcookie("SimpleTrader", '', 1);
        }
        else {
            $message[] = $Logger->display('danger', $Exchange->Error);
        }

        if($debug)
            krumo($Exchange);
    }

} // END POST ORDER


/*
 * ALERT
 */
elseif(isset($_POST['addAlert']) && $_POST['addAlert'] == 1) {


    if(!isset($_POST['priceAlert']) || !$_POST['priceAlert'])
        $message[] = $Logger->display('danger', 'Price is not set');

    $Alert = new Alert();

    $Alert->add($_POST['operator'], $_POST['priceAlert']);

    $message[] = $Logger->display('success', "New alert at ".$_POST['operator']." ".$_POST['priceAlert']." posted.");

} // END ADD ALERT


else {

    /*
     * CANCEL
     */

    if($cancel) {

        if($cancel != 'SIMULATOR') {
            $Exchange = new Exchange();

            if($Exchange->cancelOrder(0, $cancel) === true) {
                $message[] = $Logger->display('success', $Exchange->Success);
                $Ledger = new Ledger();
                $Ledger->cancelByReference($cancel);
                unset($OpenOrders); // Clear List Array
            }
            else {
                $message[] = $Logger->display('danger', $Exchange->Error);
            }
        }
        else {
            $Ledger = new Ledger();
            $Ledger->cancel($id);
        }
    }


    /*
    * DISPLAY
    */

    if($purge) {
        // Query Last Exhange PAIR price
        if(!isset($last)) {
            $Exchange = new Exchange();
            if($Exchange->ticker() === true) {
                $last = $Exchange->price;
            }
            else {
                $last = 0;
                $message[] = $Logger->display('warning', $Exchange->Error, 'Ticker');
            }
        }
    }
    else {
        // Query Last Exhange PAIR price from History
        $History = new History();
        $last = $History->getLast();
    }


    // Query Exchange Balance
    if(!isset($Balance['ZEUR']) || !isset($Balance['XETH'])) {
        $Exchange = new Exchange();
        if($Exchange->balance() === true) {
            $Balance = $Exchange->Balance;
            $Balance['XETHZEUR'] = $Balance['XETH']*$last;
        }
        else {
            $Balance['ZEUR'] = 0;
            $Balance['XETH'] = 0;
            $Balance['XETHZEUR'] = 0;
            $message[] = $Logger->display('warning', $Exchange->Error, 'Balance');
        }

        $setcookie = 1;
    } else $cache .= " balance ";


    // Buy or Sell ?
    $ETHValue = $Balance['XETH'] * $last;
    if($Balance['ZEUR'] >= $ETHValue) {
            $orderDefault = 'buy';
            $cssEUR = 'info';
            $cssETH = 'default';
    }
    else {
        $orderDefault = 'sell';
        $cssEUR = 'default';
        $cssETH = 'info';
    }


    // Set Cookie
    if($setcookie) {
        $cookie = array();
        $cookie['Balance']         = $Balance;

        setcookie("SimpleTrader", json_encode($cookie), time()+3600);
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
        <title><?php echo TRADE_WEBSITE_NAME; ?></title>

        <!-- Jquery -->
        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>

        <!-- Bootstrap 4 -->
        <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>-->

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

        <!-- Google Graph API -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

        <link rel="stylesheet" href="style/simpletrader.css">
        <script type="text/javascript" src="script/simpletrader.js"></script>

        </head>
    <body>

        <div class="container-fluid">

            <div id="loginbox" style="margin-top:5px;" class="mainbox col-md-12 col-sm-12">
            <div class="panel panel-primary" >

                <div class="panel-heading">
                    <div class="panel-title"><a href="index.php"><?php echo TRADE_WEBSITE_NAME; ?></a></div>
                    <div style="float:right; font-size: 80%; position: relative; top:-18px"><a href="index.php?purge=1" class="btn btn-default btn-xs" role="button"><span class="glyphicon glyphicon-flash"></span> Clear cache</a></div>
                </div>

                <div style="padding-top:10px" class="panel-body" >

<?php
if(count($message) > 0) {
    foreach($message as $key => $resp) { ?>
                    <div class="alert alert-<?php echo $resp->css; ?> col-sm-12"><b><?php echo $resp->message; ?></b></div>
<?php
    }
}


if(isset($_POST['addOrder']) && $_POST['addOrder']) {


} // END DISPLAY FOR POST ORDER

else {

?>

                    <div class="col-sm-5 col-lg-5">
                        <div class="row">

                            <div class="col-sm-6 col-lg-6">
                                <h3>Balance</h3>

                                <div class="col-xs-3 col-sm-6 col-lg-6">
                                    <input id="UpdateBalanceZEUR" class="btn btn-<?php echo $cssEUR; ?>" type="button" value="<?php echo money_format('%i', $Balance['ZEUR']); ?>">
                                    <input id="BalanceZEUR" type="hidden" value="<?php echo $Balance['ZEUR']; ?>">
                                </div>
                                <div class="col-xs-3 col-sm-6 col-lg-6">
                                    <input id="UpdateBalanceXETH" class="btn btn-<?php echo $cssETH; ?>" type="button" value="<?php echo number_format($Balance['XETH'], 4, '.', ' '); ?> ETH">
                                    <input id="BalanceXETH" type="hidden" value="<?php echo $Balance['XETH']; ?>">
                                </div>
                            </div>
                            <div class="col-xs-3 col-sm-6 col-lg-6">
                                <h3>Value</h3>
                                <div class="col-xs-12 col-sm-12 col-lg-12"><?php echo money_format('%i', $Balance['XETHZEUR']); ?></div>
                            </div>
                            <div class="col-xs-3 col-sm-6 col-lg-6">
                                <h3>Last</h3>
                                <div class="col-xs-12 col-sm-12 col-lg-12"><?php echo money_format('%i', $last); ?></div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-sm-12 col-lg-12">
                                <h3>Ledger</h3>
                                <div class="row">

                    <?php
	  								if(is_array($OpenOrders['open']) && count($OpenOrders['open']) > 0)
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
                            <div id="table_open_div"></div>
                            <div id="table_closed_div"></div>
                            <div id="chart_short_div"></div>
                            <div id="chart_medium_div"></div>
                            <div id="chart_long_div"></div>
                        </div>

                    </div>



            <div class="col-sm-7 col-lg-7">

<!-- <hr class="colorgraph"><br> -->

<div class="row">

                <h3>Create Order</h3>

                <form id="createOrder" name="createOrder" action="index.php" method="post" class="form-horizontal require-validation" role="form">


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
                                        <input type="radio" name="orderAction" id="orderAction1" value="buy"<?php if($orderDefault == 'buy') { echo ' checked'; } ?>>
                                        Buy
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="orderAction" id="orderAction2" value="sell"<?php if($orderDefault == 'sell') { echo ' checked'; } ?>>
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

                                if($orderDefault == 'buy') {
                                    echo 'value="'.round($Balance['ZEUR']/$last,5).'"';
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
                            <label for="price" class="control-label col-md-4">Price</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <input type="text" class="form-control" id="price" name="price" value="<?php echo $last; ?>">
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

                                if($orderDefault == 'buy') {
                                    echo 'value="'.round($Balance['ZEUR'],4).'"';
                                }
                                else {
                                    echo 'value="'.round($Balance['XETH']*$last,2).'"';
                                }

                                ?>>
                                <div class="input-group-addon">EUR</div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>


                    <div class="col-sm-12 col-lg-12">

                        <div class="col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label for="volume" class="control-label col-md-4">Take-Profit</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" aria-label="Activate Sell at Take Profit" id="takeProfit_active" name="takeProfit_active" value="1">
                                </span>
                                <input type="text" class="form-control" aria-label="" id="takeProfit_rate" name="takeProfit_rate" value="10">
                                <div class="input-group-addon">%</div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <label for="volume" class="control-label col-md-4">Stop-Loss</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                <span class="input-group-addon">
                                    <input type="checkbox" aria-label="Activate Sell at Stop Loss Profit" id="stopLoss_active" name="stopLoss_active" value="1">
                                </span>
                                <input type="text" class="form-control" aria-label="" id="stopLoss_rate" name="stopLoss_rate" value="3">
                                <div class="input-group-addon">%</div>
                                </div>
                            </div>
                        </div>
                        </div>

                    </div>

                    <div class="col-sm-12 col-lg-12">
                        <div class="form-group">
                                <input type="hidden" name="addOrder" value="1" />
                                <button type="submit" name="submitOrder" class="btn btn-success btn-lg btn-block"><span class="glyphicon glyphicon-plus-sign"></span> Post Order</button>
                        </div>
                    </div>


                </form>


                <h3>Android notifications</h3>
                <form id="createAlert" name="createAlert" action="index.php" method="post" class="form-horizontal require-validation" role="form">

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

} else echo "empty";
?>
                        </div>

                        <div class="form-group">
  
                            <div class="col-md-6">
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="operator" id="operator1" value="less" checked>
                                        <
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="operator" id="operator2" value="even">
                                        =
                                    </label>
                                </div>
                                <div class="radio">
                                    <label>
                                        <input type="radio" name="operator" id="operator3" value="more">
                                        >
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="priceAlert" name="priceAlert" value="<?php echo $last; ?>">
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

// Display Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_finish = $loading_time;
$loading_total_time = round(($loading_finish - $loading_start), 4);
?>
<small>Simple Trading v0.1 - Load:<?php echo $loading_total_time; ?>s <em><?php echo $cache; ?></em></small>
        </div>
    </body>
</html>
