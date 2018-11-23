<?php

function retrieveDateFromDb($sql, $columnName, $conn, $displayName)
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
  echo $displayName . ": " . $databaseTime->format('Y-m-d H:i:s') . "<br>";
  return $databaseTime;
}

function shouldStore($tablename, $storeIntervalSeconds, $databaseTime, $conn)
{
  $threshold = clone $databaseTime;
  $threshold->sub(new DateInterval("PT" . $storeIntervalSeconds . "S"));
//  echo "<br/>" . $tablename . " threshold: " . $threshold->format('Y-m-d H:i:s') . "<br>";

  $lastStoreTime = retrieveDateFromDb("SELECT max(time) as maxtime FROM " . $tablename, "maxtime", $conn, "last " . $tablename . " entry");
  return $threshold >= $lastStoreTime;
}

function store($valuesToStore, $tablename, $conn)
{
  $columns = implode(",", array_keys($valuesToStore));
  $values = implode(",", $valuesToStore);
  $sql = "INSERT INTO " . $tablename . " (" . $columns . ") VALUES (" . $values . ")";

  if ($conn->query($sql) === TRUE) 
  {
    echo "New " . $tablename . " record (" . $columns . ")=(" . $values . ") created successfully<br/>";
  } 
  else 
  {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

function storeWhenThresholdIsReached($tablename, $valuesToStore, $storeIntervalSeconds, $databaseTime, $conn)
{
  if (shouldStore($tablename, $storeIntervalSeconds, $databaseTime, $conn))
  {
    store($valuesToStore, $tablename, $conn);
  }
  else
  {
    echo "No " . $tablename . " record created, too close to last " . $tablename . " entry<br/>";
  }
}

function call($url, $user, $password)
{
  echo("<br/>call: " . $url);
  if ($url == null)
  {
    return;
  }
  $opts = array(
    'http'=>array(
      'method'=>"GET",
      'timeout'=>3.0,
      'header'=>"Authorization: Basic ". base64_encode($user . ":" . $password) . "\r\n"));

  $context = stream_context_create($opts);

  $file = file_get_contents($url, false, $context);
  echo $file;
}
?>