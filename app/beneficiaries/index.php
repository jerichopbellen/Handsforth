<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

$search    = $_GET['search'] ?? '';
$community = $_GET['community'] ?? '';

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
$totalBeneficiaries = $result->num_rows;
?>

<div class="container my-5">
    <?php include("../../includes/alert.php"); ?>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">
                <i class="bi bi-people-fill me-2"></i> Beneficiaries
            </h2>
            <p class="text-muted">Manage and track all beneficiaries</p>
        </div>
        <div>
            <a href="addBeneficiaries.php" class="btn fw-semibold" style="background-color:#FFD700; color:#2B547E;">
                <i class="bi bi-plus-circle me-2"></i> Add Beneficiary
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control bg-light border-0"
                               placeholder="Search by name"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted">Community</label>
                    <select name="community" class="form-select bg-light border-0">
                        <option value="">All Communities</option>
                        <?php while ($c = $communityResult->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($c['community_name']) ?>"
                                <?= $community == $c['community_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['community_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn flex-grow-1 fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                        Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary fw-semibold">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Counter -->
    <div class="mb-3">
        <small class="text-muted">Found <strong><?= $totalBeneficiaries; ?></strong> beneficiary<?= $totalBeneficiaries !== 1 ? 'ies' : 'y'; ?></small>
    </div>

    <!-- Beneficiaries Table -->
    <?php if ($result && $totalBeneficiaries > 0): ?>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Name</th>
                            <th class="fw-semibold">Contact Info</th>
                            <th class="fw-semibold">Community</th>
                            <th class="fw-semibold">Notes</th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                <td><small><?= htmlspecialchars($row['contact_info']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['community_name']) ?></small></td>
                                <td><small><?= htmlspecialchars($row['notes']) ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="editBeneficiaries.php?id=<?= $row['beneficiary_id'] ?>"
                                           class="btn btn-sm fw-semibold" style="background-color:#2B547E; color:#FFD700;" title="Edit">
                                           <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="deleteBeneficiaries.php?id=<?= $row['beneficiary_id'] ?>"
                                           class="btn btn-sm fw-semibold" style="background-color:#dc3545; color:#fff;"
                                           onclick="return confirm('Delete this beneficiary?')" title="Delete">
                                           <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color:#ccc;"></i>
                <p class="text-muted mt-4 mb-0">No beneficiaries found. Try adjusting your search or add a new one.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("../../includes/footer.php"); ?>