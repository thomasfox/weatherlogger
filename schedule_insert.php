#!/usr/bin/env php
<html>
<head>
<title>schedule inserting weather data</title>
</head>
<body>
<?php
include "include/config.php";
include "include/functions.php";
include "include/database_functions.php";
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$end = new DateTime();
$end->add(new DateInterval('PT1M'));
for ($i = 1; $i <= $selfMaxCalls; $i++)
{
  echo 'i=' . $i;
  if (!is_locked($conn, "insert"))
  {
    call($selfUrl, $basicAuthUser, $basicAuthPassword);
  }
  if (new DateTime() > $end)
  {
    echo 'time is up';
    break;
  }
  usleep($sleepTimeMicros);
}
$conn->close();
?>
</body>
</html>
