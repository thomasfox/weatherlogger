<?php
/**
 * Returns an array with all directions from including 0 to excluding 360 degrees with the given step width.
 * 
 * @param int $directionsStep the direction step width, in degrees.
 * 
 * @return int[] an array containing the directions. 
 */
function getDirectionsArray($directionsStep)
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
  $directionNames[360] = 'N';
  return $directionNames;
}
?>