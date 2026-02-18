<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM beneficiaries WHERE beneficiary_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (isset($_POST['update'])) {

    $name           = $_POST['name'];
    $contact_info   = $_POST['contact_info'];
    $community_name = $_POST['community_name'];
    $notes          = $_POST['notes'];

    $stmt = $conn->prepare(
        "UPDATE beneficiaries
         SET name=?, contact_info=?, community_name=?, notes=?
         WHERE beneficiary_id=?"
    );
    $stmt->bind_param(
        "ssssi",
        $name,
        $contact_info,
        $community_name,
        $notes,
        $id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>

<div class="container mt-4">
    <h2>Edit Beneficiary</h2>

    <form method="POST">

        <input type="text"
               name="name"
               class="form-control mb-2"
               value="<?= htmlspecialchars($data['name']) ?>"
               required>

        <input type="text"
               name="contact_info"
               class="form-control mb-2"
               value="<?= htmlspecialchars($data['contact_info']) ?>"
               required>

        <input type="text"
               name="community_name"
               class="form-control mb-2"
               value="<?= htmlspecialchars($data['community_name']) ?>"
               required>

        <textarea name="notes"
                  class="form-control mb-3"><?= htmlspecialchars($data['notes']) ?></textarea>

        <button name="update" class="btn btn-success">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php include("../../includes/footer.php"); ?>
