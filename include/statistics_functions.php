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
      $dbSpeed = $row['s'];
      $direction = $row['d'] * $directionStep;
      $count = $row['c'];
      if (!isset($dbResult[$dbSpeed]))
      {
        $dbResult[$dbSpeed] = array();
      }
      $dbResult[$dbSpeed][$direction] = $count;
      $totalCount += $count;
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  $result = array();
  foreach ($dbResult as $speedbucket => $directionArr)
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
      $directionArr[360] = 0;
    }
    // divide by totalcount
    foreach ($directionArr as $direction => $count)
    {
      if ($direction < 360)
      {
        $result[$speedbucket][$direction] = $count / $totalCount * 100;
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
  foreach ($speedDirectionHistogram as $speedbucket => $directionHistogramForSpeed)
  {
    foreach (getDirectionsArray($directionStep) as $direction)
    {
      if (!isset($speedDirectionHistogram[$speedbucket][$direction]))
      {
        $speedDirectionHistogram[$speedbucket][$direction] = 0;
      }
    }
  }
  return $speedDirectionHistogram;
}

function truncateWindSpeed(array $speedDirectionHistogram, float $speedBucketSize, float $speedCutoff)
{
  $result = array();
  foreach ($speedDirectionHistogram as $speedbucket => $directionHistogramForSpeed)
  {
    if ($speedbucket * $speedBucketSize < $speedCutoff)
    {
      $result[$speedbucket] = $directionHistogramForSpeed;
    }
  }
  return $result;
}

function sumTruncatedWindSpeeds(array $speedDirectionHistogram, float $speedBucketSize, float $speedCutoff)
{
  $result = array();
  foreach ($speedDirectionHistogram as $speedbucket => $directionHistogramForSpeed)
  {
    if ($speedbucket * $speedBucketSize >= $speedCutoff)
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
  foreach ($speedDirectionHistogram as $directionHistogramForSpeed)
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

function printSpeedDirectionTable(array $speedDirectionHistogram, float $speedBucketSize, float $largestSpeed)
{
  echo '<table class="table table-sm table-bordered">';
  printDirectionHeadlineForSpeedDirectionTable($speedDirectionHistogram);
  echo '<tbody>';
  printAllSpeedDirectionLineForSpeedDirectionTable($speedDirectionHistogram);
  printSpeedDirectionLinesForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $largestSpeed);
  printTruncatedSpeedLineForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $largestSpeed);
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

function printSpeedDirectionLinesForSpeedDirectionTable(array $speedDirectionHistogram, float $speedBucketSize, float $speedCutoff)
{
  foreach (truncateWindSpeed($speedDirectionHistogram, $speedBucketSize, $speedCutoff) as $speedbucket => $directionHistogram)
  {
    echo '<tr><th scope="row" class="table-primary">' . $speedbucket * $speedBucketSize . ' &lt;= w &lt; ' . ($speedbucket + 1) * $speedBucketSize. '</th>';
    $totalPercentage = getTotalPercentage($directionHistogram);
    echo '<td class="table-secondary">' . round($totalPercentage,2) . '</td>';
    foreach ($directionHistogram as $direction => $percentage)
    {
      echo '<td>' . round($percentage, 2) . '</td>';
    }
    echo '</tr>';
  }
}

function printTruncatedSpeedLineForSpeedDirectionTable(array $speedDirectionHistogram, float $speedBucketSize, float $speedCutoff)
{
  $directionHistogram = sumTruncatedWindSpeeds($speedDirectionHistogram, $speedBucketSize, $speedCutoff);
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

function drawLogColorscale(int $xOffset, int $yOffset)
{
  echo '<g fill="none" stroke-width="1">';
  for ($i = 0; $i <= 500; $i++)
  {
    if ($i == 0 || $i == 500)
    {
      $color = '000000';
    }
    else
    {
      $color = logColorscale(pow(10, -$i/100));
    }
    echo '<line x1="' . ($xOffset + $i) . '" x2="' . ($xOffset + $i) .'" y1="' . $yOffset . '" y2="' . ($yOffset + 10) . '" stroke-width="1" stroke="#' . $color . '" />';
    if ($i % 100 == 0)
    {
      echo '<text x="'. ($xOffset - 2 + $i) . '" y="25" fill="black" font-size="5">' . pow(10, -$i/100) . '</text>';
    }
  }
  echo '</g>';
}

function drawLinearColorscale(int $xOffset, int $yOffset)
{
  echo '<g fill="none" stroke-width="1">';
  for ($i = 0; $i <= 500; $i++)
  {
    if ($i==0 || $i==500)
    {
      $color = '000000';
    }
    else
    {
      $color = linearColorscale($i/500);
    }
    echo '<line x1="' . ($xOffset + $i) . '" x2="' . ($xOffset + $i) .'" y1="' . $yOffset . '" y2="' . ($yOffset + 10) . '" stroke="#' . $color . '" />';
    if ($i % 50 == 0)
    {
      echo '<text x="'. ($xOffset - 1 + $i) . '" y="25" fill="black" font-size="5">' . $i/500 . '</text>';
    }
  }
  echo '</g>';
}

function logColorscale($i)
{
  if ($i <= 0)
  {
    return linearColorscale(0);
  }
  if ($i >= 1)
  {
    return linearColorscale(1);
  }
  return linearColorscale(1 - (log($i + 0.0001)/log(0.0001)));
}

/**
 * Returns a RGB Color Code for a value between 0 and 1.
 *
 * @param float $value the value to get the color for.
 * @return 6-char hex color code.
 */
function linearColorscale(float $value)
{
  if ($value < 0.2)
  {
    return colorgradient($value, 0,0.2, 255,255,255, 255,255,0); // white-> yellow
  }
  if ($value < 0.5)
  {
    return colorgradient($value, 0.2,0.5, 255,255,0, 0,128,0); // yellow -> green
  }
  if ($value < 0.7)
  {
    return colorgradient($value, 0.5,0.7, 0,128,0, 0,0,255); // green -> blue
  }
  if ($value < 0.8)
  {
    return colorgradient($value, 0.7,0.8, 0,0,255, 128,0,255); // blue -> violet
  }
  if ($value < 1)
  {
    return colorgradient($value, 0.8,1, 128,0,255, 255,0,0); // violet -> red
  }
  return 'FF0000';
}

function colorgradient($value, $startValue, $endValue, $red0, $green0, $blue0, $red1, $green1, $blue1)
{
  $interpolationValue = ($value - $startValue) / ($endValue - $startValue);
  $red   = interpolate($interpolationValue, $red0, $red1);
  $green = interpolate($interpolationValue, $green0, $green1);
  $blue  = interpolate($interpolationValue, $blue0, $blue1);
  return (toColorHex($red) . toColorHex($green) . toColorHex($blue));
}

function interpolate($value, $result0, $result1)
{
  return (1 - $value) * $result0 + $value * $result1;
}

function toColorHex($i)
{
  return str_pad(dechex((int) $i),2,'0',STR_PAD_LEFT);
}

function maxPercentage(array $speedDirectionHistogram)
{
  $maxPercentage = 0;
  foreach ($speedDirectionHistogram as $speed => $directionHistogram)
  {
    foreach ($directionHistogram as $direction => $percentage)
    {
      if ($maxPercentage < $percentage)
      {
        $maxPercentage = $percentage;
      }
    }
  }
  return $maxPercentage;
}

function drawRadialWindDirectionDistribution(array $speedDirectionHistogram, float $speedStep, int $xOffset, int $yOffset)
{
  $maxPercentage = maxPercentage($speedDirectionHistogram);
  
  echo '<g fill="none" stroke-width="' . ($speedStep * 10) . '">';
  foreach ($speedDirectionHistogram as $speedBucket => $directionHistogram)
  {
    foreach ($directionHistogram as $direction => $percentage)
    {
      drawArcLine($speedBucket, $speedStep, $direction, $percentage, $maxPercentage, $xOffset, $yOffset);
    }
  }
  echo '</g>';
}


function drawArcLine(int $speedBucket, float $speedStep, int $direction, float $percentage, float $percentageScale, int $xOffset, int $yOffset)
{
	$xStart  =   $speedBucket * $speedStep * 10 * sin(($direction - 5.5)/180*M_PI);
	$yStart  =  -$speedBucket * $speedStep * 10 * cos(($direction - 5.5)/180*M_PI);
	$xEndRel = ( $speedBucket * $speedStep * 10 * sin(($direction + 5.5)/180*M_PI)) - $xStart;
	$yEndRel = (-$speedBucket * $speedStep * 10 * cos(($direction + 5.5)/180*M_PI)) - $yStart;
	echo '<path d="m' . ($xOffset + $xStart) . ',' . ($yOffset + $yStart) .' a100,100 0 0,1 ' . $xEndRel . ',' . $yEndRel . '" stroke="#' . logColorscale($percentage / $percentageScale) . '" />';
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