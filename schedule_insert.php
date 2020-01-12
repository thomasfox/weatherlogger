
<?php
$end = new DateTime();
$end->add(new DateInterval('PT1M'));

include "include/config.php";
include "include/functions.php";
include "include/database_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
if (isset($_GET['silent']))
{
  $silent = boolval($_GET['silent']);
}
else 
{
  $silent = true;
}
if ($silent)
{
  ob_start();
}
?>
<html>
<head>
<title>schedule inserting weather data</title>
</head>
<body>
<?php
echo "Scheduling started at " . date_format(new DateTime(), 'Y-m-d H:i:s'). " , silent is " . $silent . "<br/>";
?>
</body>
</html>
<?php
if ($silent)
{
  echo "now running in background...";
  $size = ob_get_length();
  header("Content-Encoding: none");
  header("Content-Length: {$size}");
  header("Connection: close");
  ob_end_flush();
  ob_flush();
  flush();
}

for ($i = 1; $i <= $selfMaxCalls; $i++)
{
  // echo 'i=' . $i;
  if (!is_locked($conn, "insert"))
  {
    call($selfUrl, $basicAuthUser, $basicAuthPassword, $silent);
  }
  if (new DateTime() > $end)
  {
    // echo 'time is up';
    break;
  }
  usleep($sleepTimeMicros);
}
$conn->close();
?>

