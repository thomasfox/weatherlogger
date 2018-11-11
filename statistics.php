<!DOCTYPE html>
<html>
<head>
<title>Windstatistik</title>
<meta charset="UTF-8"> 
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/weatherlogger.css" />
</head>
<body>
  <h2>Statistik über alle Messpunkte</h2>
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm">
 <?php
include "include/config.php";
include "include/statistics_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$speedBucketSize=1;
$directionBucketSize=45;
$speedDirectionHistogram = speedDirectionHistogram($speedBucketSize, $directionBucketSize, $conn);
$conn->close();
$speedDirectionHistogram = setMissingDirectionBuckets($speedDirectionHistogram, $directionBucketSize);

printSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, 20);
?>
      </div>
      <div class="col-sm">
      </div>
    </div>
  </div>
</body>
</html>
