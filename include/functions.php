<?php
function shouldStore($tablename, $storeIntervalSeconds, $databaseTime, $conn)
{
  $threshold = clone $databaseTime;
  $threshold->sub(new DateInterval("PT" . $storeIntervalSeconds . "S"));
//  echo "<br/>" . $tablename . " threshold: " . $threshold->format('Y-m-d H:i:s') . "<br>";

  $lastStoreTime = retrieveDateFromDb("SELECT max(time) as maxtime FROM " . $tablename, "maxtime", $conn, "last " . $tablename . " entry", true);
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

function call($url, $user, $password, $silent)
{
  if (!$silent)
  {
    echo("<br/>call: " . $url);
  }
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
  if (!$silent)
  {
    echo $file;
  }
}
?>