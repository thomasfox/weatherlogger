<!DOCTYPE html>
<html>
<head>
<title>SVS Wetter - Windstatistik</title>
<meta charset="UTF-8"> 
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/weatherlogger.css" />
</head>
<body>
<?php
include "include/config.php";
include "include/statistics_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
?>
  <div class="container-fluid">
    <h2 class="text-center">Relative Häufigkeiten nach Windrichtung und Windstärke, farblich codiert</h2>
<?php include "include/menu.php" ?>
    <div class="row justify-content-center my-3">
      <form class="form-inline" id="dateSelectForm">
<?php
$fromTo = getAndRenderFromTo($conn);
?>
      </form>
    </div>
    <div class="row justify-content-center">
      <svg viewBox="0 0 760 540" width="760px" height="540px" xmlns="http://www.w3.org/2000/svg" class="mb-4">

<?php
$speedStep = 0.2;
$directionStep = 5;
$speedCutoff = 25;
$speedDirectionHistogram = speedDirectionHistogram($speedStep, $directionStep, $fromTo[0], $fromTo[1], $conn);
$speedDirectionHistogram = truncateWindSpeed($speedDirectionHistogram, $speedStep, $speedCutoff);
drawLinearWindDirectionDistribution($speedDirectionHistogram, $speedStep, $speedCutoff, $directionStep);
$conn->close();
?>
      </svg>
    </div>
    <div class="row justify-content-center">
      <h6 class="mb-3">Farbskala der relativen Häufigkeiten (1=größte relative Häufigkeit)</h6>
    </div>
    <div class="row justify-content-center">
      <svg viewBox="0 0 760 50" width="760px" height="50px" xmlns="http://www.w3.org/2000/svg">
<?php 
drawLogColorscale();
?>
      </svg>
    </div>
  </div>
</body>
<script>
function loadDataAndUpdate()
{
	document.getElementById("dateSelectForm").submit();
}
</script>
</html>
