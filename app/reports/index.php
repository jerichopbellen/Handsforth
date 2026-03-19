<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/index.php");
    exit();
}

$range = $_GET['range'] ?? '30';
$search = trim($_GET['q'] ?? '');

$allowedRanges = [
    '30' => 30,
    '90' => 90,
    '365' => 365,
];

if (!isset($allowedRanges[$range])) {
    $range = '30';
}

$rangeDays = (int)$allowedRanges[$range];
$cutoffDate = date('Y-m-d', strtotime('-' . $rangeDays . ' days'));
$cutoffDateEsc = mysqli_real_escape_string($conn, $cutoffDate);
$searchEsc = mysqli_real_escape_string($conn, $search);
$searchFilterDonations = $search !== ''
    ? " AND (COALESCE(donors.name, '') LIKE '%$searchEsc%' OR COALESCE(d.description, '') LIKE '%$searchEsc%')"
    : '';
$searchFilterUsers = $search !== ''
    ? " WHERE (COALESCE(u.username, '') LIKE '%$searchEsc%' OR COALESCE(u.email, '') LIKE '%$searchEsc%')"
    : '';

$kpiDonationsFunds = 0.0;
$kpiBeneficiaries = 0;
$kpiVolunteers = 0;
$kpiServiceHours = 0.0;
$kpiProjects = 0;

$donationsSummarySql = "
    SELECT COALESCE(SUM(COALESCE(md.amount, d.amount, 0)), 0) AS total_funds
    FROM donations d
    LEFT JOIN monetary_details md ON md.id = (
        SELECT MAX(md2.id)
        FROM monetary_details md2
        WHERE md2.donation_id = d.donation_id
    )
    LEFT JOIN donors ON donors.donor_id = d.donor_id
    WHERE d.donation_type = 'funds'
      AND d.date_received >= '$cutoffDateEsc'
      $searchFilterDonations
";
$donationsSummaryRes = mysqli_query($conn, $donationsSummarySql);
if ($donationsSummaryRes) {
    $donationsSummaryRow = mysqli_fetch_assoc($donationsSummaryRes);
    $kpiDonationsFunds = (float)($donationsSummaryRow['total_funds'] ?? 0);
}

$beneficiariesSql = "
    SELECT COUNT(DISTINCT pb.beneficiary_id) AS total_served
    FROM project_beneficiaries pb
    WHERE pb.date_served IS NOT NULL
      AND pb.date_served >= '$cutoffDateEsc'
";
$beneficiariesRes = mysqli_query($conn, $beneficiariesSql);
if ($beneficiariesRes) {
    $beneficiariesRow = mysqli_fetch_assoc($beneficiariesRes);
    $kpiBeneficiaries = (int)($beneficiariesRow['total_served'] ?? 0);
}

$activeVolunteersSql = "
    SELECT COUNT(DISTINCT pv.volunteer_id) AS active_volunteers
    FROM project_volunteers pv
    INNER JOIN projects p ON p.project_id = pv.project_id
    WHERE LOWER(p.status) IN ('planned', 'pending', 'ongoing')
";
$activeVolunteersRes = mysqli_query($conn, $activeVolunteersSql);
if ($activeVolunteersRes) {
    $activeVolunteersRow = mysqli_fetch_assoc($activeVolunteersRes);
    $kpiVolunteers = (int)($activeVolunteersRow['active_volunteers'] ?? 0);
}

$serviceHoursSql = "
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, a.check_in_time, a.check_out_time)) / 60, 0) AS service_hours
    FROM attendance a
    INNER JOIN projects p ON p.project_id = a.project_id
    WHERE a.check_in_time IS NOT NULL
      AND a.check_out_time IS NOT NULL
      AND p.date >= '$cutoffDateEsc'
";
$serviceHoursRes = mysqli_query($conn, $serviceHoursSql);
if ($serviceHoursRes) {
    $serviceHoursRow = mysqli_fetch_assoc($serviceHoursRes);
    $kpiServiceHours = (float)($serviceHoursRow['service_hours'] ?? 0);
}

$activeProjectsSql = "
    SELECT COUNT(*) AS total_active
    FROM projects
    WHERE LOWER(status) IN ('planned', 'pending', 'ongoing')
      AND date >= '$cutoffDateEsc'
";
$activeProjectsRes = mysqli_query($conn, $activeProjectsSql);
if ($activeProjectsRes) {
    $activeProjectsRow = mysqli_fetch_assoc($activeProjectsRes);
    $kpiProjects = (int)($activeProjectsRow['total_active'] ?? 0);
}

$recentDonations = [];
$recentDonationsSql = "
    SELECT
        d.donation_id,
        d.donation_type,
        d.date_received,
        COALESCE(donors.name, 'Anonymous') AS donor_name,
        COALESCE(md.amount, d.amount, 0) AS monetary_amount,
        COALESCE(d.description, '') AS fallback_description,
        COALESCE(di.goods_text, '') AS goods_text
    FROM donations d
    LEFT JOIN donors ON donors.donor_id = d.donor_id
    LEFT JOIN monetary_details md ON md.id = (
        SELECT MAX(md2.id)
        FROM monetary_details md2
        WHERE md2.donation_id = d.donation_id
    )
    LEFT JOIN (
        SELECT donation_id,
               GROUP_CONCAT(CONCAT(COALESCE(description, 'Item'), ' x', COALESCE(quantity, 0)) SEPARATOR '; ') AS goods_text
        FROM donation_items
        GROUP BY donation_id
    ) di ON di.donation_id = d.donation_id
    WHERE d.date_received >= '$cutoffDateEsc'
      $searchFilterDonations
    ORDER BY d.date_received DESC, d.donation_id DESC
    LIMIT 5
";
$recentDonationsRes = mysqli_query($conn, $recentDonationsSql);
if ($recentDonationsRes) {
    $recentDonations = mysqli_fetch_all($recentDonationsRes, MYSQLI_ASSOC);
}

$totalDistributedFunds = 0.0;
$distributedFundsSql = "
    SELECT COALESCE(SUM(COALESCE(ds.distributed_amount, 0)), 0) AS total_distributed
    FROM distributions ds
    INNER JOIN donations d ON d.donation_id = ds.donation_id
    WHERE d.donation_type = 'funds'
      AND ds.distributed_date IS NOT NULL
      AND ds.distributed_date >= '$cutoffDateEsc'
";
$distributedFundsRes = mysqli_query($conn, $distributedFundsSql);
if ($distributedFundsRes) {
    $distributedFundsRow = mysqli_fetch_assoc($distributedFundsRes);
    $totalDistributedFunds = (float)($distributedFundsRow['total_distributed'] ?? 0);
}
$donationProgressPct = 0;
if ($kpiDonationsFunds > 0) {
    $donationProgressPct = (int)round(min(100, ($totalDistributedFunds / $kpiDonationsFunds) * 100));
}

$beneficiariesByCommunity = [];
$beneficiariesByCommunitySql = "
    SELECT COALESCE(community_name, 'Unspecified') AS community_name, COUNT(*) AS total
    FROM beneficiaries
    GROUP BY COALESCE(community_name, 'Unspecified')
    ORDER BY total DESC
    LIMIT 5
";
$beneficiariesByCommunityRes = mysqli_query($conn, $beneficiariesByCommunitySql);
if ($beneficiariesByCommunityRes) {
    $beneficiariesByCommunity = mysqli_fetch_all($beneficiariesByCommunityRes, MYSQLI_ASSOC);
}

$volunteerRoleSummary = [];
$volunteerRoleSummarySql = "
    SELECT COALESCE(pv.role_in_project, 'member') AS role_in_project, COUNT(*) AS total
    FROM project_volunteers pv
    INNER JOIN projects p ON p.project_id = pv.project_id
    WHERE p.date >= '$cutoffDateEsc'
    GROUP BY COALESCE(pv.role_in_project, 'member')
    ORDER BY total DESC
";
$volunteerRoleSummaryRes = mysqli_query($conn, $volunteerRoleSummarySql);
if ($volunteerRoleSummaryRes) {
    $volunteerRoleSummary = mysqli_fetch_all($volunteerRoleSummaryRes, MYSQLI_ASSOC);
}

$volunteerAssignmentsPeriod = 0;
$volunteerAssignmentsPeriodSql = "
    SELECT COUNT(*) AS total
    FROM project_volunteers
    WHERE assigned_at >= '$cutoffDateEsc 00:00:00'
";
$volunteerAssignmentsPeriodRes = mysqli_query($conn, $volunteerAssignmentsPeriodSql);
if ($volunteerAssignmentsPeriodRes) {
    $volunteerAssignmentsPeriod = (int)(mysqli_fetch_assoc($volunteerAssignmentsPeriodRes)['total'] ?? 0);
}

$volunteersWithAttendance = 0;
$volunteersWithAttendanceSql = "
    SELECT COUNT(DISTINCT a.volunteer_id) AS total
    FROM attendance a
    INNER JOIN projects p ON p.project_id = a.project_id
    WHERE p.date >= '$cutoffDateEsc'
";
$volunteersWithAttendanceRes = mysqli_query($conn, $volunteersWithAttendanceSql);
if ($volunteersWithAttendanceRes) {
    $volunteersWithAttendance = (int)(mysqli_fetch_assoc($volunteersWithAttendanceRes)['total'] ?? 0);
}

$projectStatusRows = [];
$projectStatusSql = "
    SELECT LOWER(status) AS status, COUNT(*) AS total
    FROM projects
    WHERE date >= '$cutoffDateEsc'
    GROUP BY LOWER(status)
";
$projectStatusRes = mysqli_query($conn, $projectStatusSql);
if ($projectStatusRes) {
    $projectStatusRows = mysqli_fetch_all($projectStatusRes, MYSQLI_ASSOC);
}

$statusCounts = [
    'planned' => 0,
    'ongoing' => 0,
    'completed' => 0,
    'cancelled' => 0,
];
foreach ($projectStatusRows as $statusRow) {
    $statusKey = strtolower((string)($statusRow['status'] ?? ''));
    if (isset($statusCounts[$statusKey])) {
        $statusCounts[$statusKey] = (int)($statusRow['total'] ?? 0);
    }
}

$statusTotal = array_sum($statusCounts);

$recentUsers = [];
$recentUsersSql = "
    SELECT u.username, u.email, u.created_at, COALESCE(r.role_name, 'unassigned') AS role_name
    FROM users u
    LEFT JOIN roles r ON r.role_id = u.role_id
    $searchFilterUsers
    ORDER BY u.created_at DESC
    LIMIT 5
";
$recentUsersRes = mysqli_query($conn, $recentUsersSql);
if ($recentUsersRes) {
    $recentUsers = mysqli_fetch_all($recentUsersRes, MYSQLI_ASSOC);
}

$distributionSummarySql = "
    SELECT
        COALESCE(SUM(COALESCE(distributed_amount, 0)), 0) AS distributed_total,
        COUNT(DISTINCT beneficiary_id) AS unique_beneficiaries
    FROM distributions
    WHERE distributed_date IS NOT NULL
      AND distributed_date >= '$cutoffDateEsc'
";
$distributionSummaryRes = mysqli_query($conn, $distributionSummarySql);
$impactDistributed = 0.0;
$impactBeneficiaries = 0;
if ($distributionSummaryRes) {
    $distributionSummaryRow = mysqli_fetch_assoc($distributionSummaryRes);
    $impactDistributed = (float)($distributionSummaryRow['distributed_total'] ?? 0);
    $impactBeneficiaries = (int)($distributionSummaryRow['unique_beneficiaries'] ?? 0);
}

$auditLogCount = 0;
$auditLogSql = "
    SELECT COUNT(*) AS total_logs
    FROM audit_logs
    WHERE timestamp >= '$cutoffDateEsc 00:00:00'
";
$auditLogRes = mysqli_query($conn, $auditLogSql);
if ($auditLogRes) {
    $auditLogRow = mysqli_fetch_assoc($auditLogRes);
    $auditLogCount = (int)($auditLogRow['total_logs'] ?? 0);
}

$reportQuery = http_build_query([
    'type' => '',
    'date_from' => $cutoffDate,
    'date_to' => date('Y-m-d'),
    'search' => $search,
]);
?>

<div class="container-fluid py-4">
    <?php include("../../includes/alert.php"); ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-bar-chart-line-fill me-2"></i>Reports Dashboard</h2>
            <p class="text-muted mb-0">Organization snapshots for the last <?= (int)$rangeDays; ?> days</p>
        </div>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label fw-semibold text-muted mb-1">Date Range</label>
                <select class="form-select" name="range" onchange="this.form.submit()">
                    <option value="30" <?= $range === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90" <?= $range === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                    <option value="365" <?= $range === '365' ? 'selected' : ''; ?>>Last 365 Days</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label fw-semibold text-muted mb-1">Search</label>
                <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Donor, donation, user...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn fw-semibold" style="background-color:#2B547E; color:#FFD700;">
                    Apply
                </button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Donations (Funds)</h6>
                    <h3 class="mb-0">$<?= number_format($kpiDonationsFunds, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Beneficiaries Served</h6>
                    <h3 class="mb-0"><?= number_format($kpiBeneficiaries); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Active Volunteers</h6>
                    <h3 class="mb-0"><?= number_format($kpiVolunteers); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Service Hours</h6>
                    <h3 class="mb-0"><?= number_format($kpiServiceHours, 1); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted">Active Projects</h6>
                    <h3 class="mb-0"><?= number_format($kpiProjects); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white">Donations Report</div>
                <div class="card-body">
                    <?php if (!empty($recentDonations)): ?>
                        <div class="list-group list-group-flush mb-3">
                            <?php foreach ($recentDonations as $donation): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($donation['donor_name']); ?></div>
                                            <?php if (($donation['donation_type'] ?? '') === 'funds'): ?>
                                                <small class="text-muted">$<?= number_format((float)$donation['monetary_amount'], 2); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted"><?= htmlspecialchars($donation['goods_text'] !== '' ? $donation['goods_text'] : $donation['fallback_description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($donation['date_received']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No donations found for this period.</p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Funds Distributed Progress</small>
                        <small class="text-muted"><?= $donationProgressPct; ?>%</small>
                    </div>
                    <div class="progress my-2">
                        <div class="progress-bar bg-success" style="width: <?= $donationProgressPct; ?>%">
                            <?= $donationProgressPct; ?>%
                        </div>
                    </div>
                    <a href="../donations/index.php" class="btn btn-outline-success">View Detailed Report</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">Beneficiaries Report</div>
                <div class="card-body">
                    <?php if (!empty($beneficiariesByCommunity)): ?>
                        <?php foreach ($beneficiariesByCommunity as $community): ?>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span><?= htmlspecialchars($community['community_name']); ?></span>
                                <span class="fw-semibold"><?= (int)$community['total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No community data available.</p>
                    <?php endif; ?>
                    <a href="../beneficiaries/index.php" class="btn btn-outline-primary mt-3">View Detailed Report</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-info text-white">Volunteers Report</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Assignments In Period</span>
                        <span class="fw-semibold"><?= number_format($volunteerAssignmentsPeriod); ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Volunteers With Attendance</span>
                        <span class="fw-semibold"><?= number_format($volunteersWithAttendance); ?></span>
                    </div>

                    <?php if (!empty($volunteerRoleSummary)): ?>
                        <?php foreach ($volunteerRoleSummary as $roleSummary): ?>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-capitalize">Role: <?= htmlspecialchars($roleSummary['role_in_project']); ?></span>
                                <span class="fw-semibold"><?= (int)$roleSummary['total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mt-2 mb-0">No volunteer assignment data available for this period.</p>
                    <?php endif; ?>
                    <a href="../projects/index.php" class="btn btn-outline-info mt-3">View Detailed Report</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-warning text-dark">Projects Report</div>
                <div class="card-body">
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <?php
                            $pct = $statusTotal > 0 ? (int)round(($count / $statusTotal) * 100) : 0;
                        ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <small class="text-capitalize"><?= htmlspecialchars($status); ?></small>
                                <small><?= (int)$count; ?> (<?= $pct; ?>%)</small>
                            </div>
                            <div class="progress" style="height: 7px;">
                                <div class="progress-bar bg-warning" style="width: <?= $pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="../projects/index.php" class="btn btn-outline-warning mt-2">View Detailed Report</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-secondary text-white">Users Report</div>
                <div class="card-body">
                    <?php if (!empty($recentUsers)): ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <div class="d-flex justify-content-between border-bottom py-2 gap-2">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($user['username']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($user['email']); ?></small>
                                </div>
                                <div class="text-end">
                                    <small class="badge bg-dark"><?= htmlspecialchars($user['role_name']); ?></small><br>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($user['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No users matched this search.</p>
                    <?php endif; ?>
                    <a href="../users/listUsers.php" class="btn btn-outline-secondary mt-3">View Detailed Report</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">Impact & Service Report</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Total Distributed Amount</span>
                        <strong>$<?= number_format($impactDistributed, 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Unique Beneficiaries Reached</span>
                        <strong><?= number_format($impactBeneficiaries); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Service Hours Logged</span>
                        <strong><?= number_format($kpiServiceHours, 1); ?></strong>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-4 g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">Export Center</div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-dark" href="../donations/printDonations.php?<?= htmlspecialchars($reportQuery); ?>" target="_blank">Donations PDF</a>
                    <a class="btn btn-outline-dark" href="../donations/exportDonationsCSV.php?<?= htmlspecialchars($reportQuery); ?>">Donations CSV</a>
                    <a class="btn btn-outline-dark" href="../donations/index.php">More Export Options</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
