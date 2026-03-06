<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$search    = isset($_GET['search']) ? $_GET['search'] : '';
$community = isset($_GET['community']) ? $_GET['community'] : '';

$sql = "SELECT * FROM beneficiaries WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($community)) {
    $sql .= " AND community_name = ?";
    $params[] = $community;
    $types .= "s";
}

$sql .= " ORDER BY beneficiary_id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$communityResult = $conn->query(
    "SELECT DISTINCT community_name FROM beneficiaries ORDER BY community_name"
);
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#2B547E;">
            <h4 class="mb-0" style="color:#FFD700;">
                <i class="bi bi-people-fill me-2"></i>Beneficiaries
            </h4>
            <a href="addBeneficiaries.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-1"></i>Add Beneficiary
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row mb-4">
                <div class="col-md-4 mb-2 mb-md-0">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search by name"
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <select name="community" class="form-select">
                        <option value=""> All Communities </option>
                        <?php while ($c = $communityResult->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($c['community_name']) ?>"
                                <?= $community == $c['community_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['community_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">Filter</button>
                    <a href="index.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Name</th>
                            <th class="fw-semibold">Contact Info</th>
                            <th class="fw-semibold">Community</th>
                            <th class="fw-semibold">Notes</th>
                            <th class="fw-semibold" width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0) { ?>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_info']) ?></td>
                                    <td><?= htmlspecialchars($row['community_name']) ?></td>
                                    <td><?= htmlspecialchars($row['notes']) ?></td>
                                    <td>
                                        <a href="editBeneficiaries.php?id=<?= $row['beneficiary_id'] ?>"
                                           class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                                           <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="deleteBeneficiaries.php?id=<?= $row['beneficiary_id'] ?>"
                                           class="btn btn-sm fw-semibold" style="background-color:#FFD700; color:#2B547E;"
                                           onclick="return confirm('Delete this beneficiary?')">
                                           <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center">No records found</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>