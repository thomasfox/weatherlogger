<!DOCTYPE html>
<html>
<head>
<title>Admin</title>
<meta charset="UTF-8"> 
<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/weatherlogger.css" />
</head>
<?php
include "include/config.php";
include "include/database_functions.php";
include "include/date_functions.php";
include "include/admin_functions.php";
?>
<body>
  <div class="container-fluid">
    <h2 class="text-center">Admin</h2>
<?php include "include/menu.php" ?>
    <div class="row">
<?php 
$conn = getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName);
if (isset($_POST["average"]))
{
  averageWindInDb($_POST["dateTo"], $windAverageMinutes, $_POST["adminPw"], $adminPassword, $conn);
}
?>
    </div>
    <div class="row justify-content-center my-3 mb-2 ">
      <form class="form-inline" method="POST">
        <label class="mx-2" for="dateTo">Bis</label>
<?php
renderDates('wind WHERE averaged is false', null, 'dateTo', 'form-control wl-mobile-form-enlarge mx-2', $conn);
?>
        <label class="mx-2" for="adminPw">Admin-Passwort</label>
        <input type="password" class="form-control" class="mx-2" name="adminPw"></input>
        <button name="average" value="average" class="btn btn-primary">Wind mitteln</button> 
      </form>
    </div>
  </div>
</body>
</html>
