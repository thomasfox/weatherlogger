<?php

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

function renderDates($tableName, $conn, $selectId)
{
  $sql = "SELECT DISTINCT(DATE(time)) as distinctdate FROM " . $tableName . " ORDER BY distinctdate ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    echo '<select id="' . $selectId . '">';
    while($row = $result->fetch_assoc()) 
    {
      $time = DateTime::createFromFormat("Y-m-d", $row["distinctdate"]);
      echo "<option value=" . $time->format('Y-m-d') . ">" . $time->format('d.m.Y') . "</option>";
    }
    echo "</select>";
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
}

function columnDataAsJson($tableName, $columnName, $columnFactor, $date, $conn)
{
  $sql = "SELECT " . $columnName . ", time FROM " . $tableName . " WHERE time > '" . $date->format('Y-m-d 00:00:00') . "' AND time <= '" . $date->format('Y-m-d 23:59:59') . "' ORDER BY time ASC";
  $result = $conn->query($sql);
  echo "[";
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    $first = true;
    while($row = $result->fetch_assoc()) 
    {
      $time = $row["time"];
      $value = $row[$columnName];
      if (!$first)
      {
        echo ",";
      }
      echo '{"x": "' . $time . '","y": ' . ($value / $columnFactor) . '}';
      $first = false;
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  echo "]";
}
?>