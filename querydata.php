<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/query_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$table = $_GET["table"];
$column = $_GET["column"];

$date = $_GET["date"];
if($date == null)
{
  $date = new DateTime();
}
else
{
  $date = DateTime::createFromFormat("Y-m-d", $date);
}
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
    "temperature"=>10),
  "pressure"=>array(
    "pressure"=>10));
$factor = $factors[$table][$column];
columnDataAsJson($table, $column, $factor, $date, $conn, $average);
?>
