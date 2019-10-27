<?php
function getDatabaseConnection($dbServer, $dbUser, $dbPassword, $dbName)
{
  $conn = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
  if ($conn->connect_error)
  {
    die("Connection failed: " . $conn->connect_error);
  }
  return $conn;
}

function is_locked($conn, $lockId)
{
  $lockExpiryDate = new DateTime();
  $lockExpiryDate->sub(new DateInterval('PT2M'));
  $sql = "SELECT * FROM locks WHERE time > '" . $lockExpiryDate->format('Y-m-d H:i:s') . "' AND id ='" . $lockId ."'";
  $result = $conn->query($sql);
  if ($conn->errno != 0 || $result->num_rows > 0)
  {
    return true;
  }
  return false;
}

function lock($conn, $lockId)
{
  $sql = "SELECT id FROM locks WHERE id='" . $lockId ."'";
  $result = $conn->query($sql);
  if ($conn->errno == 0)
  {
    if ($result->num_rows > 0)
    {
      update_lock($conn, $lockId);
    }
    else 
    {
      create_lock($conn, $lockId);
    }
  }
  else
  {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}


function create_lock($conn, $lockId)
{
  $now = new DateTime();
  $sql = "INSERT INTO locks (id,time) values ('" . $lockId ."','" . $now->format('Y-m-d H:i:s') . "')";
  if ($conn->query($sql) === TRUE)
  {
    echo "New lock entry created for id " . $lockId . "<br/>";
  }
  else
  {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}

function update_lock($conn, $lockId)
{
  $now =  new DateTime();
  $sql = "UPDATE locks SET time='" . $now->format('Y-m-d H:i:s') . "' WHERE id='" . $lockId ."'";
  if ($conn->query($sql) === TRUE)
  {
    echo "Lock entry updated for id " . $lockId. "<br/>";
  }
  else
  {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}


function unlock($conn, $lockId)
{
  $sql = "DELETE FROM locks where id='" . $lockId ."'";
  if ($conn->query($sql) === TRUE)
  {
    echo "Lock released for id " . $lockId. "<br/>";
  }
  else
  {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }
}
?>