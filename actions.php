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

if(isset($_GET['cancelScalp']) && $_GET['cancelScalp'] != '') { $cancelScalp = $_GET['cancelScalp']; }
else $cancelScalp = '';

if(isset($_GET['refresh']) && $_GET['refresh'] != '') { $refresh = $_GET['refresh']; }
else $refresh = 0;

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

        if($Ledger->type != 'position') {
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
        }
        else {
            $message[] = $Logger->display('success', "Posted position at $Ledger->price");
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

            // Cancel by Reference
            $Exchange = new Exchange();

            if($Exchange->cancelOrder(0, $cancel) === true) {
                $message[] = $Logger->log('INFO', $Exchange->Success, 'cancelOrder', 'success');
                $Ledger = new Ledger();
                $Ledger->cancelByReference($cancel);
                unset($OpenOrders); // Clear List Array
            }
            else {
                $message[] = $Logger->log('ERROR', $Exchange->Error, 'cancelOrder', 'danger');
            }
        }
        else {
            $message[] = $Logger->log('INFO', "Canceled simulator Order $cancel ($id)", 'cancelOrder', 'success');

            // Cancel by ID for Simulator
            $Ledger = new Ledger();
            $Ledger->cancel($id);
        }
    }
    elseif($cancelScalp) {
        $message[] = $Logger->log('INFO', "Canceled Scalp for Order $cancelScalp ($id)", 'cancelOrder', 'success');
        // Cancel by ID
        $Ledger = new Ledger();
        $Ledger->cancelScalp($id);
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