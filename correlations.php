<!DOCTYPE html>
<html>
<head>
<title>SVS Wetter - Autokorrelation</title>
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
    <div class="row justify-content-center my-3 mb-2">
      <form class="form-inline">
        <label for="dateSelector">Autokorrelationsfunktion ab</label>
<?php
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$currentDate = date("Y-m-d");
renderDates("wind", $currentDate, 'dateSelector', 'form-control wl-mobile-form-enlarge mx-2', $conn);
$conn->close();
?>
        <label class="sr-only" for="timeFromSelector">Time from:</label>
<?php
renderTimes(0, 0, 'timeSelectorFrom', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <span class=" mx-2"> für </span>
        <label class="sr-only" for="hoursSelector">Zeitraum in Stunden</label>
<?php
renderHours(48, 'hoursSelector', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <span class="mx-2"> Stunden </span>
      </form>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <canvas id="windSpeedCorrelationCanvas" class="wl"></canvas>
      </div>
    </div>
  </div>
  <script>

function loadChartData(config, date, timeFrom, hours, onReady) {
	var clientId = '<?php echo $basicAuthUser; ?>';
	var clientSecret = '<?php echo $basicAuthPassword; ?>';

	var authorizationBasic = window.btoa(clientId + ':' + clientSecret);

	var request = new XMLHttpRequest();
	var url = "correlationsdata.php?date=" + date+ "&timeFrom=" + timeFrom+ "&hours=" + hours;
	request.open('GET', url, true);
	
	request.responseType = "json";
	request.setRequestHeader('Authorization', 'Basic ' + authorizationBasic);
	request.setRequestHeader('Accept', 'application/json');

	request.onload = function () {
		var response = request.response;
		config.data.datasets[0].data = response.autocorrelation;

		onReady();
	};
	request.send();
}

function showChart(label, date, timeFrom, hours, canvasId) {
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
					type: 'linear',
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Sekunden',
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
	displayChartInCanvas(canvasId, config); // in case we get an error

	loadChartData(config, date, timeFrom, hours, function() {
		displayChartInCanvas(canvasId, config);
	})
}

function displayChartInCanvas(canvasId, config)
{
	var ctx = document.getElementById(canvasId).getContext('2d'); 
	window[canvasId] = new Chart(ctx, config);
}

function mobile()
{
	return /Mobi/.test(navigator.userAgent);
}

function loadDataAndUpdate()
{
	loadChartData(window.windSpeedCorrelationCanvas.config, document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('hoursSelector').value, function() {window.windSpeedCorrelationCanvas.update();})
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
	showChart('Autokorrelation der Windgeschwindigkeit', document.getElementById('dateSelector').value, document.getElementById('timeSelectorFrom').value, document.getElementById('hoursSelector').value, 'windSpeedCorrelationCanvas');
};
  </script>
</body>
</html>
