<?php

require('actions.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?php echo TRADE_WEBSITE_NAME; ?>
    </title>

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
        crossorigin="anonymous"></script>

    <!-- Bootstrap 4 -->
    <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>-->

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
        crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp"
        crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
        crossorigin="anonymous"></script>

    <!-- Google Graph API -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <link rel="stylesheet" href="css/simpletrader.css">
    <script type="text/javascript" src="js/simpletrader.js"></script>

</head>

<body>

    <div class="container-fluid">

        <div id="loginbox" style="margin-top:5px;" class="mainbox col-md-12 col-sm-12">
            <div class="panel panel-primary">

                <div class="panel-heading">
                    <div class="panel-title">
                        <a href="index.php">
                            <?php echo TRADE_WEBSITE_NAME; ?>
                        </a>
                    </div>
                    <div style="float:right; font-size: 80%; position: relative; top:-18px">
                        <?php
                    if($refresh) {
?>
                            <a href="javascript:void(0)" id="auto-refresh" class="btn btn-xs btn-warning pause">
                                <span class="glyphicon glyphicon-pause"></span> Auto refresh</a>
                            <script type="text/javascript">
                                startAutoRefresh();
                            </script>
                            <?php
                    }
                    else {
?>
                                <a href="javascript:void(0)" id="auto-refresh" class="btn btn-xs btn-success play">
                                    <span class="glyphicon glyphicon-play"></span> Auto refresh</a>
                                <?php
                    }
?>
                                    <a href="index.php?purge=1" class="btn btn-default btn-xs" role="button">
                                        <span class="glyphicon glyphicon-flash"></span> Clear cache</a>
                    </div>
                </div>

                <div style="padding-top:10px" class="panel-body">

                    <?php
if(count($message) > 0) {
    foreach($message as $key => $resp) { ?>
                        <div class="alert alert-<?php echo $resp->css; ?> col-sm-12">
                            <b>
                                <?php echo $resp->message; ?>
                            </b>
                        </div>
                        <?php
    }
}


if(isset($_POST['addOrder']) && $_POST['addOrder']) {


} // END DISPLAY FOR POST ORDER

else {

?>

                            <div class="col-sm-6 col-lg-6">
                                <div class="row">

                                    <div class="col-sm-6 col-lg-6">
                                        <h3>Balance</h3>

                                        <div class="col-xs-3 col-sm-6 col-lg-6">
                                            <input id="UpdateBalanceZEUR" class="btn btn-<?php echo $cssEUR; ?>" type="button" value="<?php echo money_format('%#1n', $Balance['ZEUR']); ?>">
                                            <input id="BalanceZEUR" type="hidden" value="<?php echo $Balance['ZEUR']; ?>">
                                        </div>
                                        <div class="col-xs-3 col-sm-6 col-lg-6">
                                            <input id="UpdateBalanceXETH" class="btn btn-<?php echo $cssETH; ?>" type="button" value="<?php echo number_format($Balance['XETH'], 4, '.', ' '); ?> ETH">
                                            <input id="BalanceXETH" type="hidden" value="<?php echo $Balance['XETH']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-xs-3 col-sm-6 col-lg-6">
                                        <h3>Value</h3>
                                        <div class="col-xs-12 col-sm-12 col-lg-12">
                                            <?php echo money_format('%#1n', $Balance['XETHZEUR']); ?>
                                        </div>
                                    </div>
                                    <div class="col-xs-3 col-sm-6 col-lg-6">
                                        <h3>Last</h3>
                                        <div class="col-xs-12 col-sm-12 col-lg-12">
                                            <?php echo money_format('%#1n', $last); ?>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div id="table_closed_div"></div>
                                    <div id="chart_short_div"></div>
                                    <div id="chart_medium_div"></div>
                                    <div id="chart_long_div"></div>
                                </div>

                            </div>



                            <div class="col-sm-6 col-lg-6">

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

                                            <div class="row">
                                                <div id="table_open_div"></div>
                                            </div>

                                            <div class="form-group">
                                                <div class="col-md-6" id="orderAction">
                                                    <div class="radio">
                                                        <label>
                                                            <input type="radio" name="orderAction" id="orderAction_radio1" value="buy" <?php if($orderDefault=='buy' ) { echo ' checked'; }
                                                                ?>> Buy
                                                        </label>
                                                    </div>
                                                    <div class="radio">
                                                        <label>
                                                            <input type="radio" name="orderAction" id="orderAction_radio2" value="sell" <?php if($orderDefault=='sell' ) { echo ' checked';
                                                                } ?>> Sell
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="col-md-6">
                                                    <div class="radio">
                                                        <label>
                                                            <input type="radio" name="type" id="type1" value="limit" checked> Limit
                                                        </label>
                                                    </div>
                                                    <div class="radio">
                                                        <label>
                                                            <input type="radio" name="type" id="type2" value="market"> Market
                                                        </label>
                                                    </div>
                                                    <div class="radio">
                                                        <label>
                                                            <input type="radio" name="type" id="type2" value="position"> Position
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
                                                        <input type="text" class="form-control" id="volume" name="volume" <?php if($orderDefault=='buy' ) { echo 'value="'.round($Balance[
                                                            'ZEUR']/$last,5). '"'; } else { echo 'value="'.round($Balance[ 'XETH'],6).
                                                            '"'; } ?>>
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
                                                        <input type="text" class="form-control" id="total" name="total" <?php if($orderDefault=='buy' ) { echo 'value="'.round($Balance[
                                                            'ZEUR'],4). '"'; } else { echo 'value="'.round($Balance[ 'XETH']*$last,2).
                                                            '"'; } ?>>
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
                                                    <input type="text" class="form-control" aria-label="" id="takeProfit_rate" name="takeProfit_rate" value="5">
                                                    <div class="input-group-addon">%</div>
                                                    <div id="takeProfit_price" class="input-group-addon"></div>
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
                                                    <input type="text" class="form-control" aria-label="" id="stopLoss_rate" name="stopLoss_rate" value="1">
                                                    <div class="input-group-addon">%</div>
                                                    <div id="stopLoss_price" class="input-group-addon"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-sm-12 col-lg-12">
                                    <div class="form-group">
                                        <input type="hidden" name="addOrder" value="1" />
                                        <button type="submit" name="submitOrder" class="btn btn-success btn-lg btn-block">
                                            <span class="glyphicon glyphicon-plus-sign"></span> Post Order</button>
                                    </div>
                                </div>


                                </form>


                                <h3>Android notifications</h3>
                                <form id="createAlert" name="createAlert" action="index.php" method="post" class="form-horizontal require-validation" role="form">

                                    <div class="col-sm-12 col-lg-12">

                                        <div class="col-sm-12 col-md-8 col-lg-2">
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
                                                        < </label>
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