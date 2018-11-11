<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
 
<body>
<?php 
include "include/config.php";
include "include/statistics_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$speedStep = 0.2;
$directionStep = 5;
$speedDirectionHistogram = speedDirectionHistogram($speedStep, $directionStep, $conn);
?>
  <svg viewbox="0 0 800 400">
<?php 
drawLogColorscale(100, 5);
drawRadialWindDirectionDistribution($speedDirectionHistogram, $speedStep, 200, 240);
?>
  </svg>
</body>
</html>
