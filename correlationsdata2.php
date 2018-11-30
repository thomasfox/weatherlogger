<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/query_functions.php";
include "include/date_functions.php";

$dateFromTo = getDateFromTo();
$correlationLength = 10 * 60;
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$sql = 'select sum(speed) as speedsum,  count(*) as count, max(time) as maxtime, min(time) as mintime from wind'
    . ' where time >= \'' . $dateFromTo[0]->format('Y-m-d H:i:s') . '\' and time <= \'' . $dateFromTo[1]->format('Y-m-d H:i:s') . '\'';
$sqlResult = $conn->query($sql);
if ($conn->errno == 0 && $sqlResult->num_rows > 0)
{
  $row = $sqlResult->fetch_assoc();
  $windSpeedSumm = $row["speedsum"];
  $count = $row["count"];
  $mintime = DateTime::createFromFormat("Y-m-d H:i:s", $row["mintime"]);
  $maxtime = DateTime::createFromFormat("Y-m-d H:i:s", $row["maxtime"]);
  $timespan = $maxtime->getTimestamp() - $mintime->getTimestamp();
  $averagedWindSpeed = $windSpeedSumm / $count;
  
  echo '{"average":' . $averagedWindSpeed. ',';
  echo '"count":' . $count. ',';
  echo '"timeSpan":' . $timespan. ',';
  echo '"mintime":"' . $mintime->format('Y-m-d H:i:s') . '",';
  echo '"maxtime":"' . $maxtime->format('Y-m-d H:i:s') . '",';
}
else
{
  echo "no result for " . $sql . " : " . $conn->error . "<br>";
  return;
}


$sql = 'select sum((w1.speed - ' . $averagedWindSpeed . ')*(w2.speed-' . $averagedWindSpeed .')) as correlationSum,'
    .'(UNIX_TIMESTAMP(w2.time) - UNIX_TIMESTAMP(w1.time)) as timeDifference,'
    .'count(*) as count'
    .' from (select * from wind where time > \'' . $dateFromTo[0]->format('Y-m-d H:i:s') . '\' and time < \'' . $dateFromTo[1]->format('Y-m-d H:i:s') . '\') w1'
    .' join (select * from wind where time > \'' . $dateFromTo[0]->format('Y-m-d H:i:s') . '\' and time < \'' . $dateFromTo[1]->format('Y-m-d H:i:s') . '\') w2'
    .'  where w2.time >= w1.time'
    .' and (UNIX_TIMESTAMP(w2.time) - UNIX_TIMESTAMP(w1.time)) < '. $correlationLength
    .' group by timeDifference'
    .' having timeDifference >= 0'
    .' order by timeDifference asc';
  echo '"sql":"' . $sql . '",';
  $sqlResult = $conn->query($sql);
  $intermediateResult = array();
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    while ($row = $sqlResult->fetch_assoc()) 
    {
      $intermediateResult[$row["timeDifference"]] = array($row["correlationSum"], $row["count"]);
    }
  }
  else
  {
    echo "no result for " . $sql . " : " . $conn->error . "<br>";
    return;
  }
  
  $variance = $intermediateResult[0][0] / $intermediateResult[0][1];
  echo '"intermediateResult0Av":' . $intermediateResult[0][0] . ',"intermediateResult0Count":' . $intermediateResult[0][1] . ',';
  echo '"variance":' . $variance . ',"stdDev":' . sqrt($variance) . ',"autocorrelation":[';
  
  $result = array();
  
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
        $result[$averageIndex] = $correlationSum / $countSum;
      }
      $correlationSum = 0;
      $countSum = 0;
    }
    if (isset($intermediateResult[$i]))
    {
      $correlationSum += $intermediateResult[$i][0];
      $countSum += $intermediateResult[$i][1];
    }
  }
  
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
