<?php
// Database credentials
$db_host = "localhost";
$db_port = "3306";
$db_name = "handsforth_db";
$db_username = "root";
$db_passwd = "";

// PDO connection (for future use)
try {
	$pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_username, $db_passwd);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Database connection failed: " . $e->getMessage());
}

// MySQLi connection (for legacy code)
$conn = new mysqli($db_host, $db_username, $db_passwd, $db_name, $db_port);
if ($conn->connect_error) {
	die("Database connection failed: " . $conn->connect_error);
}
?>