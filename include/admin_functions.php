<?php
function averageWindInDb($dateTo, $windAverageMinutes, $adminPasswordFromUser, $adminPasswordFromConfig, $conn)
{
  if ($adminPasswordFromUser != $adminPasswordFromConfig)
  {
    sleep(2);
    echo("wrong password");
    return;
  }

  $sql = "SELECT MIN(time) as mintime from wind where averaged = false";
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    $row = $result->fetch_assoc();
    $date = new DateTime($row["mintime"]);
    
    while ($date < new DateTime($dateTo))
    {
      $lowerDate = getLowerAverageTime($date, $windAverageMinutes);
      $higherDate = clone $lowerDate;
      $higherDate->add(new DateInterval('PT' . $windAverageMinutes . 'M'));
    
      if ($lowerDate->format('i') == '00')
      {
        echo "date: " . $lowerDate->format('Y-m-d H:i:s') . '<br/>';
      }
    
      $sql = 'select AVG(direction) as direction, AVG(speed) as speed, MAX(speed) as gusts '
          . 'from wind where time >= \'' . $lowerDate->format('Y-m-d H:i:s') . '\' and time <= \'' . $higherDate->format('Y-m-d H:i:s') . '\' and averaged=false';
//      echo $sql;
      $result = $conn->query($sql);
    
//      echo ' ' . $conn->errno . ' ' . $result->num_rows . '<br/>';
      if ($conn->errno == 0 && $result->num_rows > 0)
      {
        $row = $result->fetch_assoc();
        $direction = $row['direction'];
        $speed = $row['speed'];
        $gusts = $row['gusts'];
        $averageDate = getAverageDate($lowerDate, $windAverageMinutes);
//        echo " time: " . $averageDate->format('Y-m-d H:i:s');
//        echo " direction: " . $direction;
//        echo " speed: " . $speed;
//        echo " gusts: " . $gusts. '<br/>';
        $sql = 'INSERT INTO wind (time,direction,speed,gusts,averaged) VALUES (\'' . $averageDate->format('Y-m-d H:i:s') . '\','.$direction . ',' . $speed . ',' . $gusts . ', true)';
        if ($conn->query($sql) === TRUE) 
        {
          $sql = 'DELETE FROM wind WHERE averaged=false and time >= \'' . $lowerDate->format('Y-m-d H:i:s') . '\' and time <= \'' . $higherDate->format('Y-m-d H:i:s') . '\'';
          $conn->query($sql);
        }
      }
      $date = $higherDate;
    }
  }
}

function getLowerAverageTime($date, $windAverageMinutes)
{
  $lowerDate = new DateTime($date->format('Y-m-d H:00:00'));
  $lowerDateMinutes = intval($date->format('i'));
  $lowerDateMinutes = $lowerDateMinutes - ($lowerDateMinutes % $windAverageMinutes);
  $lowerDate->add(new DateInterval('PT' . $lowerDateMinutes.'M'));
  return $lowerDate;
}

function getAverageDate($lowerAverageTime, $windAverageMinutes)
{
    $averageDate = clone $lowerAverageTime;
    $minutesToAdd = ($windAverageMinutes - ($windAverageMinutes % 2)) / 2;
    $secondsToAdd = ($windAverageMinutes % 2) * 30;
    $averageDate->add(new DateInterval('PT' . $minutesToAdd.'M'));
    if ($secondsToAdd != 0)
    {
      $averageDate->add(new DateInterval('PT' . $secondsToAdd.'S'));
    }
    return $averageDate;
}

?>