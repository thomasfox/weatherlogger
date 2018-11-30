<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/query_functions.php";
include "include/date_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$table = $_GET["table"];
$column = $_GET["column"];

$dateFromTo = getDateFromTo();

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
columnDataAsJson($table, $column, $factor, $offset, $dateFromTo[0], $dateFromTo[1], $conn, $average);
?>
