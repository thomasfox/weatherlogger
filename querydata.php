<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/query_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$table = $_GET["table"];
$column = $_GET["column"];

$timeFrom = $_GET["timeFrom"];
if($timeFrom == null)
{
  $timeFrom = "00:00:00";
}
else
{
  $date = DateTime::createFromFormat("Y-m-d", $date);
}

$timeTo = $_GET["timeTo"];
if($timeTo == null)
{
  $timeTo = "24:00:00";
}
else
{
  $date = DateTime::createFromFormat("Y-m-d", $date);
}

$date = $_GET["date"];
if($date == null)
{
	$date = new DateTime().format("Y-m-d");
}
$dateFrom = DateTime::createFromFormat("Y-m-d H:i:s", $date . ' ' . $timeFrom);
$dateTo = DateTime::createFromFormat("Y-m-d H:i:s", $date . ' ' . $timeTo);

$average = $_GET["average"];
if($average == null)
{
  $average = 1;
}

$factors = array(
  "wind"=>array(
    "speed"=>10,
	"gusts"=>10,
	"direction"=>1),
  "temperature"=>array(
    "temperature"=>10,
    "humidity"=>1
  ),
  "pressure"=>array(
    "pressure"=>10
  ),
  "rain"=>array(
    "rate"=>1000,
    "yearly"=>10
  ));
  $factor = $factors[$table][$column];
$offset = 0;
if ($table == "temperature" && $column =="temperature")
{
  $offset=100;
}
columnDataAsJson($table, $column, $factor, $offset, $dateFrom, $dateTo, $conn, $average);
?>
