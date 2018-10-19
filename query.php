<!DOCTYPE html>
<html>
<head>
<title>query weather data</title>
</head>
<body>
  <script src="js/moment.min.js"></script>
  <script src="js/Chart.min.js"></script>
  <canvas id="windSpeedCanvas" width="800" height="400" style="float:left;"></canvas>
  <canvas id="windDirectionCanvas" width="800" height="400" style="float:left;"></canvas>
  <br style="clear:both;" />
  <canvas id="temperatureCanvas" width="800" height="400" style="float:left;"></canvas>
  <canvas id="pressureCanvas" width="800" height="400" style="float:left;"></canvas>
  <br style="clear:both;" />
<?php
include "include/config.php";
include "include/query_functions.php";

$conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}
renderDates("wind", $conn, 'dateSelector');

$conn->close();

?>

  <script>

function loadChartData(table, column, config, date, onReady) {
	var clientId = '<?php include "include/config.php"; echo $basicAuthUser; ?>';
	var clientSecret = '<?php include "include/config.php"; echo $basicAuthPassword; ?>';

	var authorizationBasic = window.btoa(clientId + ':' + clientSecret);

	var request = new XMLHttpRequest();
	var url = "querydata.php?table=" + table + "&column=" + column + "&date=" + date;
	request.open('GET', url, true);
	
	request.responseType = "json";
	request.setRequestHeader('Authorization', 'Basic ' + authorizationBasic);
	request.setRequestHeader('Accept', 'application/json');
	request.send();

	request.onload = function () {
		var response = request.response;
		for (var i = 0; i < response.length; i++) {
			response[i].x = new Date(response[i].x);
		}
		config.data.datasets[0].data = response;
		
		onReady();
	};
}

function showChart(table, column, label, date, canvasId) {
	var color = Chart.helpers.color;
	var config = {
		type: 'line',
		data: {
			datasets: [{
				label: label,
				backgroundColor: 'rgba(255, 99, 132, 0.2)',
				borderColor: 'rgba(255, 99, 132, 0.2)',
				fill: false
			}]
		},
		options: {
			responsive: false,
			title: {
				display: false,
			},
			scales: {
				xAxes: [{
					type: 'time',
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Uhrzeit'
					},
					ticks: {
						major: {
							fontStyle: 'bold',
							fontColor: '#FF0000'
						}
					}
				}],
				yAxes: [{
					display: true,
					scaleLabel: {
						display: true,
						labelString: label
					}
				}]
			}
		}
	};

	loadChartData(table, column, config, date, function() {
		var ctx = document.getElementById(canvasId).getContext('2d'); 
		window[table + "_" + column] = new Chart(ctx, config);
	})
}

document.getElementById('dateSelector').addEventListener('change', function(event) {
	loadChartData("wind", "speed", window.wind_speed.config, event.target.value, function() {window.wind_speed.update();})
	loadChartData("wind", "direction", window.wind_direction.config, event.target.value, function() {window.wind_direction.update();})
	loadChartData("temperature", "temperature", window.temperature_temperature.config, event.target.value, function() {window.temperature_temperature.update();})
	loadChartData("pressure", "pressure", window.pressure_pressure.config, event.target.value, function() {window.pressure_pressure.update();})
});

window.onload = function() {
	showChart("wind", "speed", 'Windgeschwindigkeit [kt]', document.getElementById('dateSelector').value, 'windSpeedCanvas');
	showChart("wind", "direction", 'Windrichtung [Grad]', document.getElementById('dateSelector').value, 'windDirectionCanvas');
	showChart("temperature", "temperature", 'Temperatur + 100 [Grad]', document.getElementById('dateSelector').value, 'temperatureCanvas');
	showChart("pressure", "pressure", 'Luftdruck [hPa]', document.getElementById('dateSelector').value, 'pressureCanvas');
};
  </script>
</body>
</html>
