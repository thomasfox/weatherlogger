<?php
header('Content-Type: text/html');
include "include/config.php";
include "include/database_functions.php";
include "include/query_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$windLower = $_GET["windLower"];
$windUpper = $_GET["windUpper"];
$windDirectionFrom = $_GET["windDirectionFrom"];
$windDirectionTo = $_GET["windDirectionTo"];
$monthAndYear = $_GET["monthAndYear"];
timelineData($windLower, $windUpper, $windDirectionFrom, $windDirectionTo, $monthAndYear, $conn);
?>
