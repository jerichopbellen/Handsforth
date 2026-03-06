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
    $stmt->bind_param("ssssi", $name, $contact_info, $community_name, $notes, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white" style="background-color:#2B547E;">
                    <h4 class="mb-0" style="color:#FFD700;">
                        <i class="bi bi-pencil-square me-2"></i>Edit Beneficiary
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Beneficiary Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control"
                                   value="<?= htmlspecialchars($data['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="contact_info" class="form-label fw-semibold">Contact Information <span class="text-danger">*</span></label>
                            <input type="text" id="contact_info" name="contact_info" class="form-control"
                                   value="<?= htmlspecialchars($data['contact_info']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="community_name" class="form-label fw-semibold">Community Name <span class="text-danger">*</span></label>
                            <input type="text" id="community_name" name="community_name" class="form-control"
                                   value="<?= htmlspecialchars($data['community_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label fw-semibold">Notes</label>
                            <textarea id="notes" name="notes" class="form-control"><?= htmlspecialchars($data['notes']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button name="update" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                <i class="bi bi-check-circle me-1"></i>Update
                            </button>
                            <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>