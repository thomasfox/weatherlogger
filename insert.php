#!/usr/bin/env php
<html>
<head>
<title>insert weather data</title>
</head>
<body>
<?php
include "include/config.php";
include "include/functions.php";
include "include/date_functions.php";

$rawdata = file_get_contents($clientrawUrl);
//echo $rawdata . '<br/>';
$data = explode(" ", $rawdata);
//echo $data . '<br/>';

$hour = (int) $data[29];
$minute = (int) $data[20];
$second = (int) $data[31];
$day = (int) $data[35];
$month = (int) $data[36];
// echo 'weatherstation timestamp is ' . $day . '.' . $month . ' ' . $hour . ':' . $minute . ':' .$seconds . '<br/>';

$windspeed = (float) $data[1];
$windspeedRecorded = (int) ($windspeed * 10);
$windspeedGusts = (float) $data[2];
$windspeedGustsRecorded = (int) ($windspeedGusts * 10);
$winddirection = (int) $data[3];
$temperature = (float) $data[4];
$temperatureRecorded = (int) (($temperature * 10) + 1000);
$humidity = (float) $data[5];
$pressure = (float) $data[6];
$pressureRecorded = (int) ($pressure * 10);
$rainRate = (float) $data[10];
$rainRateRecorded = (int) ($rainRate * 1000);
$yearlyRain = (float) $data[9];
$yearlyRainRecorded = (int) ($yearlyRain * 10);
//echo '<br/>durchschnittliche windgeschwindigkeit*10 in knoten: ' . $windspeedRecorded . '<br/>';
//echo 'windgeschwindigkeit boen*10 in knoten: ' . $windspeedGustsRecorded . '<br/>';
//echo 'windrichtung in Grad:' . $winddirection . '<br/>';
//echo '(Temperatur*10)+1000 in Grad Celsius: ' . $temperatureRecorded . '<br/>';
//echo 'Feuchtigkeit in %: ' . $humidity . '<br/>';
//echo 'Luftruck*10 in hPa: ' . $pressureRecorded . '<br/>';

$conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo '<br/>';
$databaseTime = retrieveDateFromDb("SELECT NOW() as now", "now", $conn, "database time");

$windData = array(
    "speed"  => $windspeedRecorded,
    "gusts" => $windspeedGustsRecorded,
    "direction" => $winddirection);
storeWhenThresholdIsReached("wind", $windData, $windStoreIntervalSeconds, $databaseTime, $conn);

$temperatureData = array(
    "temperature"  => $temperatureRecorded,
	"humidity" => $humidity);
storeWhenThresholdIsReached("temperature", $temperatureData, $temperatureStoreIntervalSeconds, $databaseTime, $conn);

$pressureData = array(
    "pressure"  => $pressureRecorded);
storeWhenThresholdIsReached("pressure", $pressureData, $pressureStoreIntervalSeconds, $databaseTime, $conn);

$rainData = array(
	"rate"  => $rainRateRecorded,
	"yearly"  => $yearlyRainRecorded);
storeWhenThresholdIsReached("rain", $rainData, $rainStoreIntervalSeconds, $databaseTime, $conn);

$timeData = array(
	"hour"  => $hour,
	"minute"  => $minute,
	"second"  => $second,
	"day"  => $day,
	"month"  => $month);
storeWhenThresholdIsReached("time", $timeData, $timeStoreIntervalSeconds, $databaseTime, $conn);

$hour = (int) $data[29];
$minute = (int) $data[20];
$second = (int) $data[31];
$day = (int) $data[35];
$month = (int) $data[36];


$conn->close();
?>
</body>
</html>
