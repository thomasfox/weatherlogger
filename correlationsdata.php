<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/database_functions.php";
include "include/query_functions.php";
include "include/date_functions.php";

$dateFromTo = getDateFromTo();
$correlationLength = 30 * 60;
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$sql = 'SELECT time,speed, direction FROM wind WHERE time > "'. $dateFromTo[0]->format('Y-m-d H:i:s') . '" AND time < "' . $dateFromTo[1]->format('Y-m-d H:i:s') . '" order by time asc;';
  $sqlResult = $conn->query($sql);
  $intermediateResult = array();
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    while ($row = $sqlResult->fetch_assoc()) 
    {
      $datetime = new DateTime($row["time"]);
      $intermediateResult[] = array($datetime->getTimestamp(), $row["speed"], $row["direction"]);
    }
  }
  else
  {
    echo "no result for " . $sql . " : " . $conn->error . "<br>";
    return;
  }
  
  $averagedWindSpeed = 0;
  foreach ($intermediateResult as $timeSpeedDirection)
  {
    $averagedWindSpeed += $timeSpeedDirection[1];
  }
  $averagedWindSpeed = $averagedWindSpeed / sizeof($intermediateResult);
  echo '{"average":' . $averagedWindSpeed. ',';
  $timeSpan = $intermediateResult[sizeof($intermediateResult) - 1][0] - $intermediateResult[0][0];
  echo '"timeSpan":' . $timeSpan. ',';
  
  $result = array();
  $count = array();
  
  // fill arrays beforehand so the order is correct
  for ($i = 0; $i <= $correlationLength; ++$i)
  {
    $result[$i] = 0;
    $count[$i] = 0;
  }
  
  for ($i = 0; $i < sizeof($intermediateResult); ++$i)
  {
    $timeSpeedDirection1 = $intermediateResult[$i];
    for ($j = $i; $j < sizeof($intermediateResult); ++$j)
    {
      $timeSpeedDirection2 = $intermediateResult[$j];
      $timeDifference = $timeSpeedDirection2[0] - $timeSpeedDirection1[0];
      if ($timeDifference > $correlationLength)
      {
         break;
      }
      $result[$timeDifference] += ($timeSpeedDirection1[1] - $averagedWindSpeed) * ($timeSpeedDirection2[1] - $averagedWindSpeed);
      $count[$timeDifference]++;
    }
  }
  
  $averageSpan = 5;
  $correlationSum = 0;
  $countSum = 0;
  // average over 5 seconds, remove empty array elements, 
  for ($i = 1; $i <= $correlationLength; ++$i)
  {
    if ($i % $averageSpan == 0)
    {
      if ($countSum > 0)
      {
        $averageIndex = (int) ($i - ($averageSpan / 2));
        $count[$averageIndex] = $countSum;
        $result[$averageIndex] = $correlationSum;
      }
      $correlationSum = 0;
      $countSum = 0;
    }
    $correlationSum += $result[$i];
    $countSum += $count[$i];
    unset($count[$i]);
    unset($result[$i]);
  }
  
  foreach (array_keys($result) as $key)
  {
    $result[$key] /= $count[$key];
  }
  $variance = $result[0];
  echo '"variance":' . $variance . ',"stdDev":' . sqrt($variance) . ',"autocorrelation":[';

  $comma = '';
  foreach (array_keys($result) as $timeDifference)
  {
    $result[$timeDifference] /= $variance;
    echo $comma . '{"x":'. $timeDifference . ',"y":' . $result[$timeDifference] .'}';
//    echo $comma . '{"x":'. $timeDifference . ',"y":' . $result[$timeDifference] . ',"count":' . $count[$timeDifference] .'}';
    $comma = ',';
  }
  echo ']}';

?>
