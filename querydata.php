<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/query_functions.php";

$conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}
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
columnDataAsJson($table, $column, $factor, $date, $conn);
?>
