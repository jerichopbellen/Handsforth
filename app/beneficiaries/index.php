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

<div class="container mt-4">
    <h2>Beneficiaries</h2>

    <a href="addBeneficiaries.php" class="btn btn-primary mb-3">Add Beneficiary</a>

    <form method="GET" class="row mb-3">

        <div class="col-md-4">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="Search by name"
                   value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-4">
            <select name="community" class="form-control">
                <option value=""> All Communities </option>
                <?php while ($c = $communityResult->fetch_assoc()) { ?>
                    <option value="<?= htmlspecialchars($c['community_name']) ?>"
                        <?= $community == $c['community_name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['community_name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-md-4">
            <button class="btn btn-success">Filter</button>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        </div>

    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact Info</th>
                <th>Community</th>
                <th>Notes</th>
                <th width="150">Actions</th>
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
                               class="btn btn-warning btn-sm">Edit</a>
                            <a href="deleteBeneficiaries.php?id=<?= $row['beneficiary_id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this beneficiary?')">
                               Delete
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

<?php include("../../includes/footer.php"); ?>
