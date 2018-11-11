<!DOCTYPE html>
<html>
<head>
<title>Windstatistik</title>
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
    <h2 class="text-center">Statistik über alle Messpunkte (<?php echo getDateRange($conn); ?>)</h2>
    <div class="row mt-4">
      <div class="col-sm">
      <h4>Relative Häufigkeiten nach Windrichtung und Windstärke, tabellarisch</h4>
<?php
$speedBucketSize=1.0;
$directionBucketSize=45;
$speedDirectionHistogram = speedDirectionHistogram($speedBucketSize, $directionBucketSize, $conn);
$speedDirectionHistogram = setMissingDirectionBuckets($speedDirectionHistogram, $directionBucketSize);

printSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, 20);
?>
      </div>
      <div class="col-sm">
        <h4 class="mb-4">Relative Häufigkeiten nach Windrichtung und Windstärke, farblich codiert</h4>
        <svg viewBox="0 0 760 540" width="760px" height="540px" xmlns="http://www.w3.org/2000/svg" class="mb-4">

<?php
$speedStep = 0.2;
$directionStep = 5;
$speedCutoff = 25;
$speedDirectionHistogram = speedDirectionHistogram($speedStep, $directionStep, $conn);
$speedDirectionHistogram = truncateWindSpeed($speedDirectionHistogram, $speedStep, $speedCutoff);
drawLinearWindDirectionDistribution($speedDirectionHistogram, $speedStep, $speedCutoff, $directionStep);
$conn->close();
?>
        </svg>
        <h6 class="mb-3">Farbskala der relativen Häufigkeiten (1=größte relative Häufigkeit)</h6>
        <svg viewBox="0 0 760 50" width="760px" height="50px" xmlns="http://www.w3.org/2000/svg">
<?php 
drawLogColorscale();
?>
        </svg>
     </div>
    </div>
  </div>
</body>
</html>
