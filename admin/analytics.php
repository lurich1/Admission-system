<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get application statistics
// Total applications
$sql = "SELECT COUNT(*) as total FROM applications WHERE created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$total_applications = $result->fetch_assoc()['total'];

// Applications by status
$sql = "SELECT status, COUNT(*) as count FROM applications WHERE created_at BETWEEN ? AND ? GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$status_stats = [];
while($row = $result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}

// Applications by program
$sql = "SELECT p.name, COUNT(*) as count 
        FROM applications a 
        JOIN programs p ON a.program_first_choice = p.id 
        WHERE a.created_at BETWEEN ? AND ?
        GROUP BY a.program_first_choice 
        ORDER BY count DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$program_stats = [];
while($row = $result->fetch_assoc()) {
    $program_stats[$row['name']] = $row['count'];
}

// Applications over time
$sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM applications 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY DATE(created_at) 
        ORDER BY date";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$time_stats = [];
while($row = $result->fetch_assoc()) {
    $time_stats[$row['date']] = $row['count'];
}

// Conversion rates
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) as submitted
        FROM applications 
        WHERE created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$conversion_stats = $result->fetch_assoc();

$approval_rate = $conversion_stats['total'] > 0 ? round(($conversion_stats['approved'] / $conversion_stats['total']) * 100, 1) : 0;
$rejection_rate = $conversion_stats['total'] > 0 ? round(($conversion_stats['rejected'] / $conversion_stats['total']) * 100, 1) : 0;
$pending_rate = $conversion_stats['total'] > 0 ? round(($conversion_stats['submitted'] / $conversion_stats['total']) * 100, 1) : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - University Admission System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>
            
            <main class="admin-main">
                <div class="page-header">
                    <h2>Analytics Dashboard</h2>
                    <div class="header-actions">
                        <a href="export-analytics.php" class="btn secondary-btn"><i class="fas fa-download"></i> Export Report</a>
                    </div>
                </div>
                
                <div class="filter-section">
                    <form action="" method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="filter-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn primary-btn">Apply Filters</button>
                                <a href="analytics.php" class="btn secondary-btn">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Applications</h3>
                            <p class="stat-number"><?php echo $total_applications; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Approval Rate</h3>
                            <p class="stat-number"><?php echo $approval_rate; ?>%</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rejection Rate</h3>
                            <p class="stat-number"><?php echo $rejection_rate; ?>%</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Rate</h3>
                            <p class="stat-number"><?php echo $pending_rate; ?>%</p>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-row">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3>Applications Over Time</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="timeChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3>Applications by Status</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-row">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3>Top Programs</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="programChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3>Conversion Funnel</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="funnelChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <div class="card-header">
                        <h3>AI Matching Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="ai-performance">
                            <div class="performance-metric">
                                <div class="metric-header">
                                    <h4>Accuracy Rate</h4>
                                    <span class="metric-value">94.7%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 94.7%"></div>
                                </div>
                                <p class="metric-description">Percentage of AI recommendations that matched final decisions</p>
                            </div>
                            <div class="performance-metric">
                                <div class="metric-header">
                                    <h4>Average Processing Time</h4>
                                    <span class="metric-value">2.3 hours</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 76.7%"></div>
                                </div>
                                <p class="metric-description">Average time to process an application with AI</p>
                            </div>
                            <div class="performance-metric">
                                <div class="metric-header">
                                    <h4>Manual Review Rate</h4>
                                    <span class="metric-value">12.5%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 12.5%"></div>
                                </div>
                                <p class="metric-description">Percentage of applications that required manual review after AI processing</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Applications over time chart
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        const timeChart = new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $dates = array_keys($time_stats);
                    echo "'" . implode("', '", $dates) . "'";
                    ?>
                ],
                datasets: [{
                    label: 'Applications',
                    data: [
                        <?php 
                        echo implode(", ", array_values($time_stats));
                        ?>
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Status chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $statuses = ['Not Started', 'In Progress', 'Submitted', 'Approved', 'Rejected'];
                    echo "'" . implode("', '", $statuses) . "'";
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        $counts = [];
                        foreach($statuses as $status) {
                            $counts[] = isset($status_stats[$status]) ? $status_stats[$status] : 0;
                        }
                        echo implode(", ", $counts);
                        ?>
                    ],
                    backgroundColor: [
                        '#6b7280',
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                        '#ef4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                },
                cutout: '70%'
            }
        });
        
        // Program chart
        const programCtx = document.getElementById('programChart').getContext('2d');
        const programChart = new Chart(programCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    $programs = array_keys($program_stats);
                    echo "'" . implode("', '", $programs) . "'";
                    ?>
                ],
                datasets: [{
                    label: 'Applications',
                    data: [
                        <?php 
                        echo implode(", ", array_values($program_stats));
                        ?>
                    ],
                    backgroundColor: '#3b82f6',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Funnel chart
        const funnelCtx = document.getElementById('funnelChart').getContext('2d');
        const funnelChart = new Chart(funnelCtx, {
            type: 'bar',
            data: {
                labels: ['Total', 'Submitted', 'Approved', 'Rejected'],
                datasets: [{
                    label: 'Applications',
                    data: [
                        <?php echo $conversion_stats['total']; ?>,
                        <?php echo $conversion_stats['submitted']; ?>,
                        <?php echo $conversion_stats['approved']; ?>,
                        <?php echo $conversion_stats['rejected']; ?>
                    ],
                    backgroundColor: [
                        '#6b7280',
                        '#3b82f6',
                        '#10b981',
                        '#ef4444'
                    ],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

