<?php
session_start();

include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['save'])) {

    $name           = $_POST['name'];
    $contact_info   = $_POST['contact_info'];
    $community_name = $_POST['community_name'];
    $notes          = $_POST['notes'];

    $stmt = $conn->prepare(
        "INSERT INTO beneficiaries (name, contact_info, community_name, notes)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssss",
        $name,
        $contact_info,
        $community_name,
        $notes
    );
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>

<div class="container mt-4">
    <h2>Add Beneficiary</h2>

    <form method="POST">

        <input type="text"
               name="name"
               class="form-control mb-2"
               placeholder="Beneficiary Name"
               required>

        <input type="text"
               name="contact_info"
               class="form-control mb-2"
               placeholder="Contact Information"
               required>

        <input type="text"
               name="community_name"
               class="form-control mb-2"
               placeholder="Community Name"
               required>

        <textarea name="notes"
                  class="form-control mb-3"
                  placeholder="Notes (optional)"></textarea>

        <button name="save" class="btn btn-success">Save</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php include("../../includes/footer.php"); ?>
