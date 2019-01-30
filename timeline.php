<!DOCTYPE html>
<html>
<head>
<title>Zeiten abfragen</title>
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
    <h2 class="text-center">Zeiten für Windstärke und -richtung</h2>
<?php include "include/menu.php" ?>
    <div class="row justify-content-center my-3 mb-2 ">
      <form class="form-inline">
        <label class="mx-2" for="windLower">Windstärke von</label>
<?php
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
renderWindSpeedSelector(0, 19, 4, false, 'windLower', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <label class="mx-2" for="windUpper">bis</label>
<?php
renderWindSpeedSelector(0, 20, 15, true, 'windUpper', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <label class="mx-2" for="windDirectionFrom">Windrichtung von</label>
<?php
renderWindDirectionSelector(0, 'windDirectionFrom', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <label class="mx-2" for="windDirectionTo">bis</label>
<?php
renderWindDirectionSelector(45, 'windDirectionTo', 'form-control wl-mobile-form-enlarge mx-2');
?>
        <label class="mx-2" for="year">Jahr</label>
<?php
renderYearSelector('year', 'form-control wl-mobile-form-enlarge mx-2', $conn);
?>
      </form>
    </div>
<?php
for ($i=1; $i<=12; ++$i)
{
	echo '<div class="row">';
	echo '<div class="col-lg-12" id="timeline-' . $i . '"></div>';
	echo '</div>';
}
?>
    </div>
  </div>
  <script>

function loadTimelineData(windLower, windUpper, windDirectionFrom, windDirectionTo, monthAndYear, onReady) {
	var clientId = '<?php echo $basicAuthUser; ?>';
	var clientSecret = '<?php echo $basicAuthPassword; ?>';

	var authorizationBasic = window.btoa(clientId + ':' + clientSecret);

	var request = new XMLHttpRequest();
	var url = "timelinedata.php?windLower=" + windLower + "&windUpper=" + windUpper+ "&windDirectionFrom=" + windDirectionFrom+ "&windDirectionTo=" + windDirectionTo + '&monthAndYear=' + monthAndYear;
	request.open('GET', url, true);
	
	request.responseType = "html";
	request.setRequestHeader('Authorization', 'Basic ' + authorizationBasic);
	request.setRequestHeader('Accept', '*/*');

	request.onload = function () {
		var response = request.response;
		console.log("Got response for " + monthAndYear); 
		onReady(response);
	};
	request.send();
}

function mobile()
{
	return /Mobi/.test(navigator.userAgent);
}

function loadDataAndUpdate()
{
	for (var i = 1; i <= 12; ++i)
	{
		loadTimelineData(
				document.getElementById('windLower').value, 
				document.getElementById('windUpper').value, 
				document.getElementById('windDirectionFrom').value,
				document.getElementById('windDirectionTo').value, 
				document.getElementById('year').value + '-' + i, 
				makeUpdateInnerHtmlCallback(i))
	}
}

function makeUpdateInnerHtmlCallback(i)
{
	return function(content)
	{
		document.getElementById('timeline-' + i).innerHTML = content;
	}
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
	loadDataAndUpdate();
}
  </script>
</body>
</html>
