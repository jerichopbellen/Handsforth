<?php
include("../../includes/config.php");

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM beneficiaries WHERE beneficiary_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: index.php");
exit;
