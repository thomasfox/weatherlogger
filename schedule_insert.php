#!/usr/bin/env php
<html>
<head>
<title>schedule inserting weather data</title>
</head>
<body>
<?php
include "include/config.php";
include "include/functions.php";

for ($i = 1; $i <= $selfMaxCalls; $i++)
{
  echo 'i=' . $i;
  usleep($sleepTimeMicros);
  call($selfUrl, $basicAuthUser, $basicAuthPassword);
}
?>
</body>
</html>
