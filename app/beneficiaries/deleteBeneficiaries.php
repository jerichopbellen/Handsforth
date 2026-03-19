<?php
include("../../includes/config.php");

$id = $_GET['id'];


$stmt = $conn->prepare("UPDATE beneficiaries SET is_deleted = 1 WHERE beneficiary_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: index.php");
exit;
