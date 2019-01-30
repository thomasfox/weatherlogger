<?php
include "date_functions.php";
include "direction_functions.php";
include "color_functions.php";

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
function speedDirectionHistogram($speedStep, $directionStep, $from, $to, $conn)
{
  $dbSpeedStep = $speedStep * 10;
  $sql = 'SELECT floor(speed/'. $dbSpeedStep. ') as s, floor((direction + ' . ($directionStep/2) . ')/' . $directionStep. ') as d, count(*) as c FROM wind WHERE time > "'. $from . '" AND time < "' . $to . '" group by s, d order by s, d asc;';
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
function setMissingDirectionBuckets($speedDirectionHistogram, $directionStep)
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

function truncateWindSpeed($speedDirectionHistogram, $speedBucketSize, $speedCutoff)
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

function sumTruncatedWindSpeeds($speedDirectionHistogram, $speedBucketSize, $speedCutoff)
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
function getDirectionHistogram($speedDirectionHistogram)
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

function printSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $largestSpeed)
{
  echo '<table class="table table-sm table-bordered">';
  printDirectionHeadlineForSpeedDirectionTable($speedDirectionHistogram);
  echo '<tbody>';
  printAllSpeedDirectionLineForSpeedDirectionTable($speedDirectionHistogram);
  printSpeedDirectionLinesForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $largestSpeed);
  printTruncatedSpeedLineForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $largestSpeed);
  echo '</tbody></table>';
}

function printDirectionHeadlineForSpeedDirectionTable($speedDirectionHistogram)
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

function printAllSpeedDirectionLineForSpeedDirectionTable($speedDirectionHistogram)
{
  $directionHistogram = getDirectionHistogram($speedDirectionHistogram);
  echo '<tr><th scope="row" class="table-primary">alle</th><td class="table-secondary">100</td>';
  foreach ($directionHistogram as $direction => $percentage)
  {
    echo '<td class="table-secondary">' . round($percentage, 2) . '</td>';
  }
  echo '</tr>';
}

function printSpeedDirectionLinesForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $speedCutoff)
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

function printTruncatedSpeedLineForSpeedDirectionTable($speedDirectionHistogram, $speedBucketSize, $speedCutoff)
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

function getTotalPercentage($simpleHistogram)
{
  $totalPercentage = 0;
  foreach ($simpleHistogram as $percentage)
  {
    $totalPercentage += $percentage;
  }
  return $totalPercentage;
}

function maxPercentage($speedDirectionHistogram)
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

function drawRadialWindDirectionDistribution($speedDirectionHistogram, $speedStep)
{
  $xOffset = 400;
  $yOffset = 400;
  $maxPercentage = maxPercentage($speedDirectionHistogram);
  
  echo '<g fill="none" stroke-width="' . ($speedStep * 20) . '">';
  foreach ($speedDirectionHistogram as $speedBucket => $directionHistogram)
  {
    foreach ($directionHistogram as $direction => $percentage)
    {
      drawArcLine($speedBucket, $speedStep, $direction, $percentage, $maxPercentage, $xOffset, $yOffset);
    }
  }
  echo '</g>';
}

function drawArcLine($speedBucket, $speedStep, $direction, $percentage, $percentageScale, $xOffset, $yOffset)
{
  $xStart  =   $speedBucket * $speedStep * 20 * sin(($direction - 5.5)/180*M_PI);
  $yStart  =  -$speedBucket * $speedStep * 20 * cos(($direction - 5.5)/180*M_PI);
  $xEndRel = ( $speedBucket * $speedStep * 20 * sin(($direction + 5.5)/180*M_PI)) - $xStart;
  $yEndRel = (-$speedBucket * $speedStep * 20 * cos(($direction + 5.5)/180*M_PI)) - $yStart;
  echo '<path d="m' . ($xOffset + $xStart) . ',' . ($yOffset + $yStart) .' a100,100 0 0,1 ' . $xEndRel . ',' . $yEndRel . '" stroke="#' . logColorscale($percentage / $percentageScale) . '" />';
}

function drawLinearWindDirectionDistribution($speedDirectionHistogram, $speedStep, $speedCutoff, $directionStep)
{
  $maxPercentage = maxPercentage($speedDirectionHistogram);

  echo '<g fill="none" stroke-width="' . ($speedStep * 20) . '">';
  foreach ($speedDirectionHistogram as $speedBucket => $directionHistogram)
  {
    foreach ($directionHistogram as $direction => $percentage)
    {
      drawLinearDistributionLine($speedBucket, $speedStep, $speedCutoff, $direction, $directionStep, $percentage, $maxPercentage);
    }
  }
  echo '</g>';
  $directionArray = getDirectionsArray(45);
  $directionArray[] = 360;
  $directionNames = getDirectionNamesFor45DegreesStep();
  foreach ($directionArray as $direction)
  {
    $x = getLinearDistributionDirectionX($direction) + $directionStep;
    $y = getLinearDistributionDirectionY(0, 1, $speedCutoff);
    echo '<line x1="' . $x . '" y1="'. $y . '" x2="' . $x . '" y2="'. ($y + 8) . '" stroke-width="1" stroke="black" />';
    echo '<text x="'. ($x - 15) . '" y="'. ($y + 20) . '" fill="black" font-size="12">' . $direction . '° (' . $directionNames[$direction] . ')</text>';
  }
  for ($speed = 0; $speed <= $speedCutoff; $speed += 5) 
  {
    $y = getLinearDistributionDirectionY($speed, 1, $speedCutoff) + ($speedStep * 10);
    echo '<line x1="29" y1="' . $y . '" x2="35" y2="' . $y . '" stroke-width="1" stroke="black" />';
    echo '<text x="0" y="' . ($y + 3) . '" fill="black" font-size="12">' . $speed . ' kt</text>';
  	}
}

function drawLinearDistributionLine($speedBucket, $speedStep, $speedCutoff, $direction, $directionStep, $percentage, $percentageScale)
{
  $xStart = getLinearDistributionDirectionX($direction);
  $y = getLinearDistributionDirectionY($speedBucket, $speedStep, $speedCutoff);
  $xEndRel = $directionStep * 2;
  echo '<line x1="' . $xStart . '" y1="' . $y .'" x2="' . ($xStart + $xEndRel) . '" y2="' . $y . '" stroke="#' . logColorscale($percentage / $percentageScale) . '" />';
}

function getLinearDistributionDirectionX($direction)
{
  return 35 + $direction * 2;
}

function getLinearDistributionDirectionY($speedBucket, $speedStep, $speedCutoff)
{
  return ($speedCutoff * 20) + 10 - $speedBucket * $speedStep * 20;
}

function getDateRange($conn)
{
  $sql = 'select DATE(min(time)) as mindate, DATE(max(time)) as maxdate from wind';
  $sqlResult = $conn->query($sql);
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    $row = $sqlResult->fetch_assoc();
    return toHumanReadableDate($row["mindate"]) . ' - ' . toHumanReadableDate($row["maxdate"]);
  }
}

function toHumanReadableDate($dbDate)
{
  return DateTime::createFromFormat("Y-m-d", $dbDate)->format('d.m.Y');
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

function getAndRenderFromTo($conn)
{
	if (isset($_GET['dateSelectorFrom']) && isset($_GET['timeSelectorFrom']))
	{
		$fromDate = $_GET['dateSelectorFrom'];
		$fromTime = $_GET['timeSelectorFrom'];
		$from = $fromDate . ' ' . $fromTime;
	}
	else
	{
		$from = getMinDate("wind", $conn)->format('Y-m-d H:i:s');
		$fromDate = $arr = explode(" ", $from, 2)[0];
		$fromTime = 0;
	}
	
	if (isset($_GET['dateSelectorTo']) && isset($_GET['timeSelectorTo']))
	{
		$toDate = $_GET['dateSelectorTo'];
		$toTime = $_GET['timeSelectorTo'];
		$to = $toDate . ' ' . $toTime;
	}
	else
	{
		$to = getMaxDate("wind", $conn)->format('Y-m-d H:i:s');
		$toDate = $arr = explode(" ", $to, 2)[0];
		$toTime = 24;
	}
	echo '<label class="my-1 mx-2" for="dateSelectorFrom">von</label>';
	renderDates("wind", $fromDate, 'dateSelectorFrom', 'form-control wl-mobile-form-enlarge mx-2', $conn);
	renderTimes(0, $fromTime, 'timeSelectorFrom', 'form-control wl-mobile-form-enlarge mx-2');
	echo '<label class="my-1 mx-2" for="dateSelectorTo">bis</label>';
	renderDates("wind", $toDate, 'dateSelectorTo', 'form-control wl-mobile-form-enlarge mx-2', $conn);
	renderTimes(1, $toTime, 'timeSelectorTo', 'form-control wl-mobile-form-enlarge mx-2');
	return array($from, $to);
}
?>