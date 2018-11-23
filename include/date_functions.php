<?php
function renderDates($tableName, $conn, $default, $selectId, $class)
{
  $sql = "SELECT DISTINCT(DATE(time)) as distinctdate FROM " . $tableName . " ORDER BY distinctdate ASC";
  $result = $conn->query($sql);
  if ($conn->errno == 0 && $result->num_rows > 0)
  {
    echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
    $first = true;
    while($row = $result->fetch_assoc()) 
    {
      $date = DateTime::createFromFormat("Y-m-d", $row["distinctdate"]);
      $dateValue = $date->format('Y-m-d');
      $selected = ($dateValue == $default) ? ' selected="selected"' : ''; 
      echo '<option value="' . $dateValue . '"' . $selected . '>' . $date->format('d.m.Y') . '</option>';
      $first = false;
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
  echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
  for ($i = $offset; $i < 24 + $offset; $i++)
  {
    $selectedString = ($i == $default ? ' selected="selected"' : '');
    echo '<option value="' .$i . ':00:00"' . $selectedString . '>' .$i . ':00</option>';
  }
  echo "</select>";
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


function getMaxDate($tablename, $conn)
{
	return retrieveDateFromDb("SELECT max(time) as maxtime FROM " . $tablename, "maxtime", $conn, "last " . $tablename . " entry");
}
	
function getMinDate($tablename, $conn)
{
	return retrieveDateFromDb("SELECT min(time) as mintime FROM " . $tablename, "mintime", $conn, "first " . $tablename . " entry");
}

?>