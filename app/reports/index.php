<?php
session_start();
include("../../includes/header.php");
include("../../includes/config.php");
// Add authentication and role check if needed
?>
<div class="container-fluid py-4">
    <!-- Top Row: Executive Summary KPIs -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Total Donations ($)</h6>
                    <h2 id="kpi-donations">$0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Beneficiaries Served</h6>
                    <h2 id="kpi-beneficiaries">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Active Volunteers</h6>
                    <h2 id="kpi-volunteers">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Service Hours</h6>
                    <h2 id="kpi-hours">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Active Projects</h6>
                    <h2 id="kpi-projects">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <!-- Date Range Filter -->
            <select class="form-select" id="date-range">
                <option value="30">Last 30 Days</option>
                <option value="365">This Year</option>
                <option value="custom">Custom</option>
            </select>
        </div>
    </div>
    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <input type="text" class="form-control" id="quick-search" placeholder="Search volunteer or project...">
        </div>
    </div>
    <!-- Main Body: Grid Layout -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white">Donations Report</div>
                <div class="card-body">
                    <div id="donations-list">Recent transactions go here...</div>
                    <div class="progress my-2">
                        <div class="progress-bar" id="donation-progress" style="width: 0%">0%</div>
                    </div>
                    <a href="../donations/index.php" class="btn btn-outline-success">View Detailed Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">Beneficiaries Report</div>
                <div class="card-body">
                    <div id="beneficiaries-chart">Geographic/Demographic chart here...</div>
                    <a href="../beneficiaries/index.php" class="btn btn-outline-primary">View Detailed Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-info text-white">Volunteers Report</div>
                <div class="card-body">
                    <div id="volunteers-growth">Headcount/growth chart here...</div>
                    <a href="../volunteers/index.php" class="btn btn-outline-info">View Detailed Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-warning text-dark">Projects Report</div>
                <div class="card-body">
                    <div id="projects-status">Status cards/percentage bars here...</div>
                    <a href="../projects/index.php" class="btn btn-outline-warning">View Detailed Report</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-secondary text-white">Users Report</div>
                <div class="card-body">
                    <div id="users-logs">Access logs/trends here...</div>
                    <a href="../users/listUsers.php" class="btn btn-outline-secondary">View Detailed Report</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Middle Section: Advanced Analytics -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Volunteer Analytics</div>
                <div class="card-body">
                    <div id="volunteer-analytics-chart">Retention/skills chart here...</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">Impact & Service Report</div>
                <div class="card-body">
                    <div id="impact-scorecard">Impact Scorecard here...</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Sidebar/Bottom Section: Admin & Tools -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">Operational/Compliance Report</div>
                <div class="card-body">
                    <div id="compliance-health">Health check status here...</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">Export Center</div>
                <div class="card-body">
                    <select class="form-select mb-2" id="export-type">
                        <option value="donations">Donations</option>
                        <option value="beneficiaries">Beneficiaries</option>
                        <option value="volunteers">Volunteers</option>
                        <option value="projects">Projects</option>
                        <option value="users">Users</option>
                    </select>
                    <button class="btn btn-outline-dark me-2">PDF</button>
                    <button class="btn btn-outline-dark me-2">CSV</button>
                    <button class="btn btn-outline-dark">Excel</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">Live Feed / Map View</div>
                <div class="card-body">
                    <div id="live-feed">Live activity/map here...</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("../../includes/footer.php"); ?>
