<!DOCTYPE html>
<html>
<head>
<title>SVS Wetter - Tagesdaten</title>
<meta charset="UTF-8"> 
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/weatherlogger.css" />
</head>
<?php
include "include/config.php";
include "include/database_functions.php";
include "include/query_functions.php";
include "include/date_functions.php";
?>
<body>
  <script src="js/moment.min.js"></script>
  <script src="js/Chart.min.js"></script>
  
  <div class="container-fluid">
    <h2 class="text-center">Wind und Wetter - SVS Wetterstation</h2>
<?php include "include/menu.php" ?>
    <div class="row justify-content-center my-3 mb-2 ">
      <form class="form-inline">
        <label class="sr-only" for="dateSelector">Datum:</label>
<?php
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$currentDate = date("Y-m-d");
renderDates("wind", $currentDate, 'dateSelector', 'form-control wl-mobile-form-enlarge mr-sm-3', $conn);
$conn->close();
?>
        <label class="sr-only" for="timeFromSelector">Time from:</label>
<?php
renderTimes(0, 0, 'timeSelectorFrom', 'form-control wl-mobile-form-enlarge');
?>
        <span class="mx-2">-</span>
        <label class="sr-only" for="dateSelector">Datum:</label>
<?php
renderTimes(1, 24, 'timeSelectorTo', 'form-control wl-mobile-form-enlarge mr-sm-3');
?>
      </form>
    </div>
    <div class="row">
      <div class="col-lg-6">
        <canvas id="windSpeedCanvas" class="wl"></canvas>
      </div>
      <div class="col-lg-6">
        <canvas id="windDirectionCanvas" class="wl"></canvas>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-3">
        <canvas id="temperatureCanvas" class="wl"></canvas>
      </div>
      <div class="col-lg-3">
        <canvas id="humidityCanvas" class="wl"></canvas>
      </div>
      <div class="col-lg-3">
        <canvas id="pressureCanvas" class="wl"></canvas>
      </div>
      <div class="col-lg-3">
        <canvas id="rainCanvas" class="wl"></canvas>
      </div>
    </div>
  </div>
  <script>

function loadChartData(table, column, config, date, timeFrom, timeTo, average, onReady) {
	var clientId = '<?php echo $basicAuthUser; ?>';
	var clientSecret = '<?php echo $basicAuthPassword; ?>';

	var authorizationBasic = window.btoa(clientId + ':' + clientSecret);

	var request = new XMLHttpRequest();
	var url = "querydata.php?table=" + table + "&column=" + column + "&date=" + date+ "&timeFrom=" + timeFrom+ "&timeTo=" + timeTo + "&average=" + average;
	request.open('GET', url, true);
	
	request.responseType = "json";
	request.setRequestHeader('Authorization', 'Basic ' + authorizationBasic);
	request.setRequestHeader('Accept', 'application/json');

	request.onload = function () {
		var response = request.response;
		if (response != null)
		{
			for (var i = 0; i < response.length; i++) {
				response[i].x = new Date(response[i].x);
			}
		}
		config.data.datasets[0].data = response;

		onReady();
	};
	request.send();
}

function readWindCount(date, timeFrom, timeTo, onReady) {
	var clientId = '<?php echo $basicAuthUser; ?>';
	var clientSecret = '<?php echo $basicAuthPassword; ?>';

	var authorizationBasic = window.btoa(clientId + ':' + clientSecret);

	var request = new XMLHttpRequest();
	var url = "querywindcount.php?date=" + date + "&timeFrom=" + timeFrom+ "&timeTo=" + timeTo;
	request.open('GET', url, true);
	
	request.responseType = "json";
	request.setRequestHeader('Authorization', 'Basic ' + authorizationBasic);
	request.setRequestHeader('Accept', 'application/json');

	request.onload = function () {
		var response = request.response;
        console.log("response is " + response);
		onReady(response);
	};
	request.send();
}

function showChart(table, column, label, date, timeFrom, timeTo, canvasId) {
	var color = Chart.helpers.color;
	var config = {
		type: 'line',
		data: {
			datasets: [{
				label: label,
				borderColor: 'rgba(0, 20, 255, 0.2)',
				backgroundColor: 'rgba(0, 20, 255, 0.2)',
				borderWidth: mobile() ? 4 : 2,
				pointRadius : mobile() ? 6 : 3,
				fill: false
			}]
		},
		options: {
			responsive: true,
			title: {
				display: false,
			},
			legend: {
				display: false,
			},
			scales: {
				xAxes: [{
					type: 'time',
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Uhrzeit',
						fontSize: mobile() ? 30 : 16
					},
					ticks: {
						major: {
							fontStyle: 'bold',
						},
						fontSize: mobile() ? 30 : 16
					}
				}],
				yAxes: [{
					display: true,
					scaleLabel: {
						display: true,
						labelString: label,
						fontSize: mobile() ? 30 : 16
					},
					ticks: {
						fontSize: mobile() ? 30 : 16
					}
				}]
			}
		}
	};

	var ctx = document.getElementById(canvasId).getContext('2d'); 
	window[table + "_" + column] = new Chart(ctx, config);
}

function mobile()
{
	return /Mobi/.test(navigator.userAgent);
}

function loadDataAndUpdate()
{
	readWindCount(document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, loadAllChartData)
}

function loadAllChartData(countOfWindDataPoints)
{
    var windAverageTo = Math.ceil(countOfWindDataPoints/<?php echo $windGraphAveragePoints ?>);
    // console.debug("wind count is " + countOfWindDataPoints + ", windAverageTo is " + windAverageTo);
	loadChartData("wind", "speed", window.wind_speed.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, windAverageTo, function() {window.wind_speed.update();})
	loadChartData("wind", "direction", window.wind_direction.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, windAverageTo, function() {window.wind_direction.update();})
	loadChartData("temperature", "temperature", window.temperature_temperature.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 1, function() {window.temperature_temperature.update();})
	loadChartData("temperature", "humidity", window.temperature_humidity.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 1, function() {window.temperature_humidity.update();})
	loadChartData("pressure", "pressure", window.pressure_pressure.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 1, function() {window.pressure_pressure.update();})
	loadChartData("rain", "yearly", window.rain_yearly.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 1, function() {window.rain_yearly.update();})
}

window.onload = function() {
	if (mobile())
	{
		var elements = document.getElementsByClassName("wl-mobile-form-enlarge");
		for (var i = 0; i < elements.length; i++)
		{
			var element = elements[i];
			element.classList.add("form-control-lg");
		}
	}
	showChart("wind", "speed", 'Windgeschwindigkeit [kt]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'windSpeedCanvas');
	showChart("wind", "direction", 'Windrichtung [Grad]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'windDirectionCanvas');
	showChart("temperature", "temperature", 'Temperatur [°C]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'temperatureCanvas');
	showChart("temperature", "humidity", 'Luftfeuchtigkeit [%]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'humidityCanvas');
	showChart("pressure", "pressure", 'Luftdruck [hPa]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'pressureCanvas');
	showChart("rain", "yearly", 'Regenmenge Jahr [mm]', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('timeSelectorTo').value, 'rainCanvas');
	loadDataAndUpdate();
};
  </script>
</body>
</html>
