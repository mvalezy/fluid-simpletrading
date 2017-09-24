$(document).ready(function() {

    $("#price").keyup(function() {

        var order  = $("#orderAction input[type='radio']:checked").val();
        var price = $("#price").val();

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


// Load the Visualization API
google.charts.load('current', {packages: ['corechart', 'line', 'table']});


// Table Open Orders
google.charts.setOnLoadCallback(drawTableOpen);
function drawTableOpen() {

    var jsonData = $.ajax({
    url: "table.ajax.php",
    data: {limit: 20, status: 'open'},
    dataType: "json",
    cache: false,
    //method: "POST",
    async: false,
    }).responseText;

    console.log(jsonData);

    var data = new google.visualization.DataTable(jsonData);

    var options = {
        allowHtml: true,
        /*showRowNumber: true,
        width: '100%',
        height: '100%'*/
    }

    var table = new google.visualization.Table(document.getElementById('table_open_div'));

    table.draw(data, options);
}

/*// Table Closed Orders
google.charts.setOnLoadCallback(drawTableClosed);
function drawTableClosed() {

    var jsonData = $.ajax({
    url: "table.ajax.php",
    data: {limit: 20, status: 'closed'},
    dataType: "json",
    cache: false,
    //method: "POST",
    async: false,
    }).responseText;

    console.log(jsonData);

    var data = new google.visualization.DataTable(jsonData);

    var options = {
    }

    var table = new google.visualization.Table(document.getElementById('table_closed_div'));

    var formatter = new google.visualization.ColorFormat();
    formatter.addRange(-20000, 0, 'white', 'red');
    formatter.addRange(20000, null, 'white', 'blue');
    formatter.format(data, 7);

    table.draw(data, options);
}*/

// Chart short
google.charts.setOnLoadCallback(drawChartShort);
function drawChartShort() {

    var jsonData = $.ajax({
    url: "chart.ajax.php",
    data: {limit: 30},
    dataType: "json",
    cache: false,
    //method: "POST",
    async: false,
    }).responseText;

    //jsonData = jsonData.replace(/\"({v[^v]*})\"/gi,"$1");

    // Create our data table out of JSON data loaded from server.
    var data = new google.visualization.DataTable(jsonData);

    var options = {
        title: '30m analysis',
        legend: 'none',
        pointSize: 0,
        hAxis: {
            format: 'H:mm',                      
        },
        vAxis: {
            format: '###,###,###.00 '.FIAT_SYMBOL, //'currency'
        },
        
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_short_div'));

    chart.draw(data, options);
}


// Chart Medium
google.charts.setOnLoadCallback(drawChartMedium);
function drawChartMedium() {

    var jsonData = $.ajax({
        url: "chart.ajax.php",
        data: {limit: 720},
        dataType: "json",
        cache: true,
        //method: "POST",
        async: false
    }).responseText;

    // Create our data table out of JSON data loaded from server.
    var data = new google.visualization.DataTable(jsonData);

    var options = {
        title: '12H analysis',
        legend: 'none',
        pointSize: 0,
        hAxis: {
            format: 'H',                      
        },
        vAxis: {
            format: '###,###,###.00 '.FIAT_SYMBOL, //'currency'
        },
        /*trendlines: {
            //0: {type: 'exponential', color: '#333', opacity: 1},
            0: {type: 'exponential', color: '#111', opacity: .3} // linear
        }*/
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_medium_div'));

    chart.draw(data, options);
}


// Chart Long
google.charts.setOnLoadCallback(drawChartLong);
function drawChartLong() {

    var jsonData = $.ajax({
        url: "chart.ajax.php",
        data: {limit: 5760},
        dataType: "json",
        cache: true,
        //method: "POST",
        async: false
    }).responseText;

    // Create our data table out of JSON data loaded from server.
    var data = new google.visualization.DataTable(jsonData);

    var options = {
        title: '4D analysis',
        legend: 'none',
        pointSize: 0,
        curveType: 'function',
        hAxis: {
            format: 'd/m H',                      
        },
        vAxis: {
            format: '###,###,###.00 '.FIAT_SYMBOL, //'currency'
        },
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_long_div'));

    chart.draw(data, options);
}