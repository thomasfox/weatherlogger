<?php
# Database settings
$dbServer = TODO;
$dbUser = TODO;
$dbPassword = TODO;
$dbName = TODO;

# URL where the clientraw.txt can be obtained from the weather station to monitor
$clientrawUrl = TODO;

$windStoreIntervalSeconds = 2;
$temperatureStoreIntervalSeconds = 300;
$pressureStoreIntervalSeconds = 600;
$rainStoreIntervalSeconds = 300;

# Username and password, assuming that the page is protected by basic auth
$basicAuthUser = TODO;
$basicAuthPassword = TODO;

# We assume that insert is called every minute by a cron script. 
# To get data more often than once per minute, we call ourselves repetitively again after a delay
# Below are the configuration settings for the url of the include script,
# how often a self call is repeated after a original call
# and how much time occurs between calls.
$selfUrl = TODO;
$selfMaxCalls = 60;
$sleepTimeMicros = 1000000;
?>