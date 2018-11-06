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

function displayData($tableName, $columnFactors, $conn)
{
  $now = new DateTime();
  $columnNames = implode(",", array_keys($columnFactors));

  $sql = "SELECT " . $columnNames . ", time FROM " . $tableName . " WHERE time > '" . $now->format('Y-m-d 00:00:00') . "' ORDER BY time ASC";
  echo "<br/>sql: " . $sql;
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    while($row = $result->fetch_assoc()) 
    {
      $time = $row["time"];
      echo $time . ":";
      foreach  ($columnFactors as $columnName=>$columnFactor)
      {
        $value = $row[$columnName];
        echo ($value / $columnFactor). " ";
      }
      echo "<br/>";
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
}

function renderDates($tableName, $conn, $selectId, $class)
{
  $currentDate = date("Y-m-d");
  $sql = "SELECT DISTINCT(DATE(time)) as distinctdate FROM " . $tableName . " ORDER BY distinctdate ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    echo '<select id="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdateCharts()">';
    while($row = $result->fetch_assoc()) 
    {
      $date = DateTime::createFromFormat("Y-m-d", $row["distinctdate"]);
      $dateValue = $date->format('Y-m-d');
      $selected = ($dateValue == $currentDate) ? ' selected="selected"' : ''; 
      echo '<option value="' . $dateValue . '"' . $selected . '>' . $date->format('d.m.Y') . '</option>';
    }
    echo "</select>";
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
}

function renderTimes($offset, $default, $selectId, $class)
{
  echo '<select id="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdateCharts()">';
  for ($i = $offset; $i < 24 + $offset; $i++)
  {
    $selectedString = ($i == $default ? ' selected="selected"' : '');
    echo '<option value="' .$i . ':00:00"' . $selectedString . '>' .$i . ':00</option>';
  }
  echo "</select>";
}

function columnDataAsJson($tableName, $columnName, $columnFactor, $columnOffset, $dateFrom, $dateTo, $conn, $averageOver=1)
{
  $sql = "SELECT " . $columnName . ", time FROM " . $tableName . " WHERE time > '" . $dateFrom->format('Y-m-d H:i:s') . "' AND time <= '" . $dateTo->format('Y-m-d H:i:s') . "' ORDER BY time ASC";
  $result = $conn->query($sql);
  echo "[";
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    $first = true;
    $averageCount = 0;
    $averagedValue = 0;
    while($row = $result->fetch_assoc()) 
    {
      $averageCount++;
      $time = $row["time"];
      $averagedValue += $row[$columnName];
      if ($averageCount == $averageOver)
      {
        if (!$first)
        {
          echo ",";
        }
        echo '{"x": "' . $time . '","y": ' . ((($averagedValue / $columnFactor) - $columnOffset) / $averageCount) . '}';
        $first = false;
        $averageCount = 0;
        $averagedValue = 0;
      }
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  echo "]";
}

function speedDirectionHistogram($conn)
{
  $sql = 'SELECT round(speed/10,0) as s, round(direction/10,0) as d, count(*) as c FROM wind group by s, d order by s, d asc;';
  $sqlResult = $conn->query($sql);
  $result = array();
  if ($conn->errno == 0 && $sqlResult->num_rows > 0)
  {
    while ($row = $sqlResult->fetch_assoc()) 
    {
      $speed = $row['s'];
      $direction = $row['d'] * 10;
      $count = $row['c'];
      if (!isset($result[$speed]))
      {
        $result[$speed] = array();
      }
      $result[$speed][$direction] = $count;
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  
  // add 360 degrees bucket to zero degrees
  foreach ($result as $speed => $directionArr)
  {
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
  }
  // remove direction for zero speed
  if (isset($result[0]))
  {
    $sumZero = 0;
    foreach ($result[0] as $count)
    {
      $sumZero += $count;
    }
    $result[0]= array();
    $result[0][0] = $sumZero;
  }
  return $result;
}
?>