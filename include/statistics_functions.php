<?php
function getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName)
{
  $conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
  if ($conn->connect_error)
  {
    die("Connection failed: " . $conn->connect_error);
  }
  return $conn;
}

/**
 * Returns the speed direction histogram as an array. 
 * First key is minimal speed of bucket, second key is average direction of bucket, values are percentages of occurence.
 * 
 * @param $speedStep how large the speed buckets should be, in knots
 * @param $directionStep how large the direction buckets should be, in degrees
 * @param $conn the mysql connection.
 * 
 * @return array
 */
function speedDirectionHistogram(float $speedStep, int $directionStep, $conn)
{
  $dbSpeedStep = $speedStep * 10;
  $sql = 'SELECT floor(speed/'. $dbSpeedStep. ') as s, floor((direction + ' . ($directionStep/2) . ')/' . $directionStep. ') as d, count(*) as c FROM wind group by s, d order by s, d asc;';
  $sqlResult = $conn->query($sql);
  $dbResult = array();
  $totalCount = 0;
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    while ($row = $sqlResult->fetch_assoc()) 
    {
      $speed = $row['s'] * $dbSpeedStep / 10;
      $direction = $row['d'] * $directionStep;
      $count = $row['c'];
      if (!isset($dbResult[$speed]))
      {
        $dbResult[$speed] = array();
      }
      $dbResult[$speed][$direction] = $count;
      $totalCount += $count;
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  
  $result = array();
  foreach ($dbResult as $speed => $directionArr)
  {
    // add 360 degrees bucket to zero degrees
    if (isset($directionArr[360]))
    {
      if (!isset($directionArr[0]))
      {
        $directionArr[0] = $directionArr[360];
      }
      else
      {
        $directionArr[0] += $directionArr[360];
      }
    }
    // divide by totalcount
    foreach ($directionArr as $direction => $count)
    {
      if (!isset($result[$speed]))
      {
        $result[$speed] = array();
      }
      if ($direction < 360)
      {
        $result[$speed][$direction] = $count / $totalCount * 100;
      }
    }
  }
  return $result;
}

/**
 * Sets the missing direction buckets to zero for all speed buckets in the speedDirectionHistogram.
 * 
 * @param array $speedDirectionHistogram the speedDirectionHistogram to fill.
 * @param int $directionStep the direction bucket size.
 * 
 * @return the filled speedDirectionHistogram.
 */
function setMissingDirectionBuckets(array $speedDirectionHistogram, int $directionStep)
{
  foreach ($speedDirectionHistogram as $speed => $directionHistogramForSpeed)
  {
    foreach (getDirectionsArray($directionStep) as $direction)
    {
      if (!isset($speedDirectionHistogram[$speed][$direction]))
      {
        $speedDirectionHistogram[$speed][$direction] = 0;
      }
    }
  }
  return $speedDirectionHistogram;
}

function truncateWindSpeed(array $speedDirectionHistogram, float $speedCutoff)
{
  $result = array();
  foreach ($speedDirectionHistogram as $speed => $directionHistogramForSpeed)
  {
    if ($speed < $speedCutoff)
    {
      $result[$speed] = $directionHistogramForSpeed;
    }
  }
  return $result;
}

function sumTruncatedWindSpeeds(array $speedDirectionHistogram, float $speedCutoff)
{
  $result = array();
  foreach ($speedDirectionHistogram as $speed => $directionHistogramForSpeed)
  {
    if ($speed >= $speedCutoff)
    {
      if (empty($result))
      {
        $result = $directionHistogramForSpeed;
      }
      else
      {
        foreach ($directionHistogramForSpeed as $direction => $percentage)
        {
          if (!isset($result[$direction]))
          {
            $result[$direction] = 0;
          }
          $result[$direction] += $percentage;
        }
      }
    }
  }
  return $result;
}


/**
 * Calculates the direction histogram in the speedDirectionHistogram.
 * It is assumed that each speed bucket has the same direction buckets (not all need to be existent).
 * 
 * @param array $windSpeedStatistics the speedDirectionHistogram to fill.
 * @param int $directionStep the direction bucket size.
 * 
 * @return the filled speedDirectionHistogram.
 */
function getDirectionHistogram(array $speedDirectionHistogram)
{
  $result = array();
  foreach ($speedDirectionHistogram as $speed => $directionHistogramForSpeed)
  {
    foreach ($directionHistogramForSpeed as $direction => $percentage)
    {
      if (!isset($result[$direction]))
      {
        $result[$direction] = 0;
      }
      $result[$direction] += $percentage;
    }
  }
  return $result;
}

/**
 * Returns an array with all directions from including 0 to excluding 360 degrees with the given step width.
 * 
 * @param int $directionsStep the direction step width, in degrees.
 * 
 * @return int[] an array containing the directions. 
 */
function getDirectionsArray(int $directionsStep)
{
  $result = array();
  for ($direction = 0; $direction < 360; $direction+= $directionsStep)
  {
    $result[] = $direction;
  }
  return $result;
}

function getDirectionNamesFor45DegreesStep()
{
  $directionNames = array();
  $directionNames[0] = 'N';
  $directionNames[45] = 'NO';
  $directionNames[90] = 'O';
  $directionNames[135] = 'SO';
  $directionNames[180] = 'S';
  $directionNames[225] = 'SW';
  $directionNames[270] = 'W';
  $directionNames[315] = 'NW';
  return $directionNames;
}

function printSpeedDirectionTable(array $speedDirectionHistogram, $largestSpeed)
{
  echo '<table class="table table-sm table-bordered">';
  printDirectionHeadlineForSpeedDirectionTable($speedDirectionHistogram);
  echo '<tbody>';
  printAllSpeedDirectionLineForSpeedDirectionTable($speedDirectionHistogram);
  printSpeedDirectionLinesForSpeedDirectionTable($speedDirectionHistogram, 20);
  printTruncatedSpeedLineForSpeedDirectionTable($speedDirectionHistogram, 20);
  echo '</tbody></table>';
}

function printDirectionHeadlineForSpeedDirectionTable(array $speedDirectionHistogram)
{
  $directionNames = getDirectionNamesFor45DegreesStep();
  echo '<thead class="table-primary">'
      . '<tr><th scope="col">Windgeschwindigkeit w [kt]</th><th scope="col">alle Richtungen</th>';
  foreach ($speedDirectionHistogram[0] as $direction => $dummy)
  {
    if (isset($directionNames[$direction]))
    {
      $directionNameAppendix = ' ('. $directionNames[$direction] . ')';
    }
    else
    {
      $directionNameAppendix = '';
    }
    echo '<th scope="col">' . $direction . '° ' . $directionNameAppendix . '</th>';
  }
  echo '</tr></thead>';
}

function printAllSpeedDirectionLineForSpeedDirectionTable(array $speedDirectionHistogram)
{
  $directionHistogram = getDirectionHistogram($speedDirectionHistogram);
  echo '<tr><th scope="row" class="table-primary">alle</th><td class="table-secondary">100</td>';
  foreach ($directionHistogram as $direction => $percentage)
  {
    echo '<td class="table-secondary">' . round($percentage, 2) . '</td>';
  }
  echo '</tr>';
}

function printSpeedDirectionLinesForSpeedDirectionTable(array $speedDirectionHistogram, float $speedCutoff)
{
  foreach (truncateWindSpeed($speedDirectionHistogram, $speedCutoff) as $speed => $directionHistogram)
  {
    echo '<tr><th scope="row" class="table-primary">' . $speed . ' &lt;= w &lt; ' . ($speed + 1) . '</th>';
    $totalPercentage = getTotalPercentage($directionHistogram);
    echo '<td class="table-secondary">' . round($totalPercentage,2) . '</td>';
    foreach ($directionHistogram as $direction => $percentage)
    {
      echo '<td>' . round($percentage, 2) . '</td>';
    }
    echo '</tr>';
  }
}

function printTruncatedSpeedLineForSpeedDirectionTable(array $speedDirectionHistogram, float $speedCutoff)
{
  $directionHistogram = sumTruncatedWindSpeeds($speedDirectionHistogram, $speedCutoff);
  echo '<tr><th scope="row" class="table-primary">w &gt;=' . $speedCutoff . '</th>';
    $totalPercentage = getTotalPercentage($directionHistogram);
    echo '<td class="table-secondary">' . round($totalPercentage,2) . '</td>';
  foreach ($directionHistogram as $direction => $percentage)
  {
    echo '<td>' . round($percentage, 2) . '</td>';
  }
  echo '</tr>';
}

function getTotalPercentage(array $simpleHistogram)
{
  $totalPercentage = 0;
  foreach ($simpleHistogram as $percentage)
  {
    $totalPercentage += $percentage;
  }
  return $totalPercentage;
}

// not to be used, too slow
function windSpeedStatistics($conn)
{
  $totalcount = countInDb("select count(*) as count from wind", $conn);
  $result = array();
  for ($minSpeed = 0; $minSpeed < 20; $minSpeed++)
  {
    $result[$minSpeed] = array();
    $sql = 'select count(*) as count from wind'
        . ' where speed>=' . ($minSpeed * 10) .' and speed <'. ($minSpeed * 10 + 10) 
        . ' and (direction < 23 or direction > 337)';
    $result[$minSpeed][0] = countInDb($sql, $conn) / $totalcount * 100;
    for ($avgDirection = 45; $avgDirection < 360; $avgDirection+=45)
    {
      $sql = 'select count(*) as count from wind'
          . ' where speed>=' . ($minSpeed * 10) .' and speed <'. ($minSpeed * 10 + 10) 
          . ' and direction > ' . ($avgDirection - 23) . ' and direction <= ' . ($avgDirection + 22);
      $result[$minSpeed][$avgDirection] = countInDb($sql , $conn) / $totalcount * 100;
    }
  }
  return $result;
}
  
function countInDb($sql, $conn)
{
  $sqlResult = $conn->query($sql);
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    $row = $sqlResult->fetch_assoc();
    return $row["count"];
  }
  else
  {
    echo "no result for " . $sql . " : " . $conn->error . "<br>";
  }
  $sqlResult.close();
  return null;
}
?>