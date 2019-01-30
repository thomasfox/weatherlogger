<?php
function drawLogColorscale()
{
  $xFactor = 1.5;
  echo '<g fill="none" stroke-width="2">';
  for ($i = 0; $i <= 500; $i++)
  {
    $color = logColorscale(pow(10, -$i/100));
    echo '<line x1="' . ((10 + $i) * $xFactor) . '" x2="' . ((10 + $i) * $xFactor) . '" y1="0" y2="20" stroke="#' . $color . '" />';
    if ($i % 100 == 0)
    {
      echo '<line x1="' . ((10 + $i) * $xFactor) . '" x2="' . ((10 + $i) * $xFactor) .'" y1="20" y2="28" stroke="black" />';
      echo '<text x="'. ((7 + $i) * $xFactor * 0.99) . '" y="42" fill="black" font-size="12" stroke-width="1">' . pow(10, -$i/100) . '</text>';
    }
  }
  echo '</g>';
}

function drawLinearColorscale($xOffset, $yOffset)
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
 * Returns a multicolor RGB Color Code for a value between 0 and 1.
 *
 * @param float $value the value to get the color for.
 * @return 6-char hex color code.
 */
function linearColorscale($value)
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

function linearMonochromeColorscale($value)
{
	if ($value < 1)
	{
		return colorgradient($value, 0,1, 255,255,255, 0,0,255); // white -> blue
	}
	return '0000FF';
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
?>