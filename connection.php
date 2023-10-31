<?php
$host = "localhost"; // Hostname
$username = "root"; // MySQL username
$password = "sys@123"; // MySQL password
$database = "pathologylab_final"; // Database name

// Create a connection to the database
$mysqli = new mysqli($host, $username, $password, $database);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>