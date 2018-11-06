<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
 
<body>
  <svg viewbox="0 0 800 400">
    <g fill="none" stroke-width="5">
<?php
include "include/config.php";
include "include/query_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$speedDirectionHistogram = speedDirectionHistogram($conn);

$maxcount = 0;
for ($speed = 1; $speed < 10; ++$speed)
{
  if (isset($speedDirectionHistogram[$speed]))
  {
    for ($direction = 0; $direction <= 350; $direction+=10)
    {
      if (isset($speedDirectionHistogram[$speed][$direction]))
      {
        $count = $speedDirectionHistogram[$speed][$direction];
        if ($maxcount < $count)
        {
          $maxcount = $count;
        }
      }
    }
  }
}
//echo 'maxcount=' . $maxcount;
  
for ($speed = 1; $speed < 10; ++$speed)
{
  for ($direction = 0; $direction <= 350; $direction+=10)
  {
    $count = 0;
    if (isset($speedDirectionHistogram[$speed]) && isset($speedDirectionHistogram[$speed][$direction]))
    {
      $count = $speedDirectionHistogram[$speed][$direction];
      //echo '\ncount='. $count;
    }
    //echo 'normcolor='. ($count * 767.0 / $maxcount);
    $xStart = $speed*10 * sin(($direction - 5)/180*M_PI);
    $yStart = $speed*10 * cos(($direction - 5)/180*M_PI);
    $xEndRel = ($speed*10 * sin(($direction + 5)/180*M_PI)) - $xStart;
    $yEndRel = ($speed*10 * cos(($direction + 5)/180*M_PI)) - $yStart;
    echo '<path d="m' . (200 + $xStart) . ',' . (200 + $yStart) .' a100,100 0 0,1 ' . $xEndRel . ',' . $yEndRel . '" stroke="#' . logcolorscale($count / $maxcount) . '" />';
  }
}

function logcolorscale($i)
{
//  echo $i . " " . (1 - (log($i + 0.001)/log(0.001)));
  if ($i <= 0)
  {
  	return colorscale(0);
  }
  if ($i >= 1)
  {
  	return colorscale(1);
  }
  return colorscale(1 - (log($i + 0.001)/log(0.001)));
}

function colorscale($value)
{
  $scaledValue = $value * 767;
  if ($scaledValue < 256)
  {
    return 'FFFF'.tohex(255-$scaledValue);
  }
  if ($scaledValue < 512)
  {
    return tohex(511-$scaledValue).'FF00';
  }
  if ($scaledValue < 768)
  {
    return tohex($scaledValue-512).tohex(767-$scaledValue).'00';
  }
  return 'FF0000';
}

function tohex($i)
{
  return str_pad(dechex((int) $i),2,'0',STR_PAD_LEFT);
}
?>
    </g>
  </svg>
</body>
</html>
