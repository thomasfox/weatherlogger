<?php
include "direction_functions.php";
include "color_functions.php";

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
    echo "no result for " . $sql;
  }
  echo "]";
}

function timelineData($minSpeed, $maxSpeed, $minDirection, $maxDirection, $monthAndYear, $conn)
{
	$dateFrom = new DateTime($monthAndYear);
	$dateTo = (new DateTime($monthAndYear))->add(new DateInterval('P1M'));
	$monthAndYearTo = $dateTo->format('Y-m');
	$sql = "select DATE_FORMAT(time, '%Y-%m-%d %H') as formattedtime, count(*) as count from wind "
		. " where wind.speed >= " . ($minSpeed * 10);
	if (!empty($maxSpeed))
	{
		$sql .= " and wind.speed <= " . ($maxSpeed * 10);
	}
	$sql .= " and wind.direction > " . $minDirection
		. " and wind.direction < " . $maxDirection
		. " and time >= '" . $monthAndYear . "-01 00:00:00'"
		. " and time < '" . $monthAndYearTo . "-01 00:00:00'"
		. " group by formattedtime"
		. " order by formattedtime asc";
	$sqlResult = $conn->query($sql);
	$result = array();
	if ($conn->errno == 0)
	{
		while($row = $sqlResult->fetch_assoc())
		{
			$result[$row['formattedtime']] = $row['count'];
		}
	}
	else
	{
		echo "error for " . $sql . ' : ' . $conn->error;
		return;
	}
	if (sizeof($result) == 0)
	{
		return;
	}
	echo '<div class="mb-2">';
	echo '<div style="display: inline-block; vertical-align:middle;" class="mx-2">';
	echo  $dateFrom->format('m/Y');
	echo '</div>';
	echo '<div style="height:40px; width:1500px; display: inline-block; vertical-align:middle;">';
	echo '<div style="height:20px;">';
	echo '<div style="height:20px; width:1px; background-color:black ; display: inline-block;"></div>';
	$max_date = new DateTime(array_keys($result)[sizeof($result) - 1] .":00:00");
	for ($date = clone $dateFrom; $date < $dateTo; $date->add(new DateInterval('PT1H')))
	{
		$key = $date->format('Y-m-d H');
		$count = 0;
		if (isset($result[$key]))
		{
			$count = $result[$key];
		}
		$color = linearMonochromeColorscale($count / 200);
		echo '<div style="height:20px; width:2px; background-color: #' . $color. '; display: inline-block;"></div>';
	}
	echo '<div style="height:20px; width:1px; background-color:black ; display: inline-block;"></div>';
	echo '</div>';
	echo '<div style="height:20px;">';
	echo '<div style="height:20px; width:1px; background-color:black ; display: inline-block;"></div>';
	for ($date = clone $dateFrom; $date < $dateTo; $date->add(new DateInterval('PT1H')))
	{
		$key = $date->format('Y-m-d H');
		if ($date->format('H') == '00')
		{
			echo '<div style="text-align:center; width:48px; display: inline-block;">'. $date->format('d') . '</div>';
		}
	}
	echo '<div style="height:20px; width:1px; background-color:black ; display: inline-block;"></div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}

function renderWindSpeedSelector($min, $max, $default, $withoutBound, $selectId, $class)
{
	echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
	for ($i = $min; $i <= $max; $i++)
	{
		$selectedString = ($i == $default ? ' selected="selected"' : '');
		echo '<option value="' .$i . '"' . $selectedString . '>' .$i . ' kt</option>';
	}
	if ($withoutBound)
	{
		$selectedString = ($i == $default ? ' selected="selected"' : '');
		echo '<option value=""' . $selectedString . '> - </option>';
	}
	echo "</select>";
}

function renderWindDirectionSelector($offset, $selectId, $class)
{
	echo '<select id="' . $selectId . '" name="' . $selectId . '" class="' . $class . '" onchange="loadDataAndUpdate()">';
	$directions = getDirectionsArray(45);
	$directionNames = getDirectionNamesFor45DegreesStep();
	foreach ($directions as $direction)
	{
		echo '<option value="' . ($direction + $offset). '">' . ($direction  + $offset) . '° (' . $directionNames[($direction  + $offset)] . ')</option>';
	}
	echo "</select>";
}
?>