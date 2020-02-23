<?php
header('Content-Type: application/json');
include "include/config.php";
include "include/database_functions.php";
include "include/query_functions.php";
include "include/date_functions.php";

$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
$table = 'wind';

$dateFromTo = getDateFromTo();

echoCount($table, $dateFromTo[0], $dateFromTo[1], $conn);
?>
