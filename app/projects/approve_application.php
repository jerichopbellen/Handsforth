<?php
session_start();
include("../../includes/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and validate input
    $app_id       = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    $project_id   = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $volunteer_id = isset($_POST['volunteer_id']) ? (int)$_POST['volunteer_id'] : 0;
    $role         = isset($_POST['role_in_project']) ? $_POST['role_in_project'] : 'member';

    if ($app_id <= 0 || $project_id <= 0 || $volunteer_id <= 0) {
        header("Location: ../projects/view.php?id=$project_id&msg=Invalid+request+parameters.");
        exit;
    }

    // 2. Start Transaction
    $conn->begin_transaction();

    try {
        // A. Insert into project_volunteers table
        $stmt1 = $conn->prepare("INSERT INTO project_volunteers (project_id, volunteer_id, role_in_project) VALUES (?, ?, ?)");
        $stmt1->bind_param("iis", $project_id, $volunteer_id, $role);
        
        if (!$stmt1->execute()) {
            throw new Exception("Failed to assign volunteer to project.");
        }

        // B. Update application status to 'approved'
        $stmt2 = $conn->prepare("UPDATE project_applications SET status = 'approved' WHERE application_id = ?");
        $stmt2->bind_param("i", $app_id);
        
        if (!$stmt2->execute()) {
            throw new Exception("Failed to update application status.");
        }

        // C. Commit changes
        $conn->commit();
        $msg = "Volunteer approved and added to project successfully.";
        
    } catch (Exception $e) {
        // Rollback on any error
        $conn->rollback();
        $msg = "Error: " . $e->getMessage();
    }

    // 3. Clean up and redirect back to the project overview
    if (isset($stmt1)) $stmt1->close();
    if (isset($stmt2)) $stmt2->close();
    
    $_SESSION['success'] = $msg;
    header("Location: view.php?id=$project_id");
    exit;
} else {
    // If accessed directly without POST
    header("Location: ../projects/index.php");
    exit;
}
?>