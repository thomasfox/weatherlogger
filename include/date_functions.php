<?php
function renderDates($tableName, $default, $selectId, $class, $conn)
{
  renderDatesWithFormats($tableName, $default, $selectId, $class, '%Y-%m-%d', 'Y-m-d', 'd.m.Y', $conn);
}

function renderMonthAndYearSelector($selectId, $class, $conn)
{
  renderDatesWithFormats('wind', null, $selectId, $class, '%Y-%m', 'Y-m', 'm/Y', $conn);
}

function renderYearSelector($selectId, $class, $conn)
{
  renderDatesWithFormats('wind', null, $selectId, $class, '%Y', 'Y', 'Y', $conn);
}

function renderDatesWithFormats($tableName, $default, $selectId, $class, $valueMysqlFormat, $valuePhpFormat, $labelPhpFormat, $conn)
{
  $sql = "SELECT DISTINCT(DATE_FORMAT(time, '" . $valueMysqlFormat . "')) as distinctdate FROM " . $tableName . " ORDER BY distinctdate ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
    while($row = $result->fetch_assoc())
    {
        if ($valueMysqlFormat=='%Y' && $valuePhpFormat == 'Y' && $labelPhpFormat == 'Y')
        {
          $dateValue = $row["distinctdate"];
          $dateLabel = $row["distinctdate"];
        }
        else
        {
          $date = new DateTime($row["distinctdate"]);
          $dateValue = $date->format($valuePhpFormat);
          $dateLabel = $date->format($labelPhpFormat);
        }
      $selected = ($dateValue == $default) ? ' selected="selected"' : '';
      echo '<option value="' . $dateValue . '"' . $selected . '>' . $dateLabel . '</option>';
    }
    echo "</select>";
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
}

function getMonthsAndYears($tableName, $conn)
{
  $sql = "SELECT DISTINCT(DATE_FORMAT(time, '%Y-%m')) as distinctdate FROM " . $tableName . " ORDER BY distinctdate ASC";
  $databaseResult = $conn->query($sql);
  $result = array();
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    while($row = $databaseResult->fetch_assoc())
    {
      $date = new DateTime($row["distinctdate"]);
      $year = $date->format('Y');
      if (!isset($result[$year]))
      {
        $result[$year] = array();
      }
      $result[$year][] = $date;
      
    }
  }
  return $result;
}


function renderTimes($offset, $default, $selectId, $class)
{
  echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
  for ($i = $offset; $i < 24 + $offset; $i++)
  {
    $selectedString = ($i == $default ? ' selected="selected"' : '');
    echo '<option value="' .$i . ':00:00"' . $selectedString . '>' .$i . ':00</option>';
  }
  echo "</select>";
}

function renderHours($end, $selectId, $class)
{
  echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
  for ($i = 1; $i <= $end; $i++)
  {
    echo '<option value="' .$i . '">' .$i . '</option>';
  }
  echo "</select>";
}

function getMaxDate($tablename, $conn)
{
  return retrieveDateFromDb("SELECT max(time) as maxtime FROM " . $tablename, "maxtime", $conn, "last " . $tablename . " entry");
}
  
function getMinDate($tablename, $conn)
{
  return retrieveDateFromDb("SELECT min(time) as mintime FROM " . $tablename, "mintime", $conn, "first " . $tablename . " entry");
}

function retrieveDateFromDb($sql, $columnName, $conn, $displayName, $echoResult = false)
{
  $databaseTime = DateTime::createFromFormat("Y-m-d H:i:s", "1970-01-01 00:00:00");
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    $row = $result->fetch_assoc();
    $value = $row[$columnName];
    if ($value != null)
    {
      $databaseTime = DateTime::createFromFormat("Y-m-d H:i:s", $row[$columnName]);
    }
    else
    {
      echo "no entry found for " . $displayName . "<br>";
    }
  }
  else
  {
    echo "no result for " . $sql . "<br>";
  }
  if ($echoResult)
  {
    echo $displayName . ": " . $databaseTime->format('Y-m-d H:i:s') . "<br>";
  }
  return $databaseTime;
}

function getDateFromTo()
{
  if (!isset($_GET["date"]))
  {
    $now = new DateTime();
    $date = $now->format("Y-m-d");
  }
  else
  {
    $date = $_GET["date"];
  }
  
  if (!isset($_GET["timeFrom"]))
  {
    $timeFrom = "00:00:00";
  }
  else 
  {
    $timeFrom = $_GET["timeFrom"];
  }
  
  $dateFrom = DateTime::createFromFormat("Y-m-d H:i:s", $date . ' ' . $timeFrom);
  
  if (isset($_GET["timeTo"]))
  {
    $timeTo = $_GET["timeTo"];
  }
  else if (isset($_GET["hours"]))
  {
    $hours = $_GET["hours"];
  }
  else
  {
    $timeTo = "24:00:00";
  }
  
  if (isset($timeTo))
  {
    $dateTo = DateTime::createFromFormat("Y-m-d H:i:s", $date . ' ' . $timeTo);
  }
  else
  {
    $dateTo = clone $dateFrom;
    $dateTo->add(new DateInterval('PT' . $hours . 'H'));
  }
  
  return array($dateFrom, $dateTo);
}
?>