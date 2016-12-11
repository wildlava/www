<?php

/* Comment this section out temporarily to allow initialization

$conn = mysql_connect("localhost", "gbh", "saltisland");

$query = "CREATE TABLE Users (userid int AUTO_INCREMENT, email varchar(128) NOT NULL, password varchar(32), priv varchar(32), name varchar(64), phone varchar(32), color int, PRIMARY KEY(userid, email))";
$result = mysql_db_query("gbh", $query);
$query = "CREATE TABLE Reservations (number int AUTO_INCREMENT, userid int, start date, end date, comments varchar(255), PRIMARY KEY(number))";
$result = mysql_db_query("gbh", $query);

*/

?>
