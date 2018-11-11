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
  <div>
 <?php
include "include/config.php";
include "include/statistics_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$speedBucketSize=1;
$directionBucketSize=45;
$speedDirectionHistogram = speedDirectionHistogram($speedBucketSize, $directionBucketSize, $conn);
$conn->close();
$speedDirectionHistogram = setMissingDirectionBuckets($speedDirectionHistogram, $directionBucketSize);

printSpeedDirectionTable($speedDirectionHistogram, 20);
?>
  </div>
</body>
</html>
