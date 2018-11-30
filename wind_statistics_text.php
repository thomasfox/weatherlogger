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
include "include/database_functions.php";
include "include/statistics_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
?>
  <div class="container-fluid">
    <h2 class="text-center">Relative Häufigkeiten nach Windrichtung und Windstärke, tabellarisch</h2>
<?php include "include/menu.php" ?>
    <div class="row justify-content-center my-3">
      <form class="form-inline" id="dateSelectForm">
<?php
$fromTo = getAndRenderFromTo($conn);
?>
      </form>
    </div>
    <div class="row">
<?php
$speedBucketSize=1.0;
$directionBucketSize=45;
$speedDirectionHistogram = speedDirectionHistogram($speedBucketSize, $directionBucketSize, $fromTo[0], $fromTo[1], $conn);
$speedDirectionHistogram = setMissingDirectionBuckets($speedDirectionHistogram, $directionBucketSize);

printSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, 20);
?>
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
