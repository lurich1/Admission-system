<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Get application statistics
$stats = [];

// Total applications
$sql = "SELECT COUNT(*) as total FROM applications";
$result = $conn->query($sql);
$stats['total'] = $result->fetch_assoc()['total'];

// Applications by status
$sql = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$result = $conn->query($sql);
$stats['by_status'] = [];
while($row = $result->fetch_assoc()) {
    $stats['by_status'][$row['status']] = $row['count'];
}

// Applications by program (first choice)
$sql = "SELECT p.name, COUNT(*) as count 
        FROM applications a 
        JOIN programs p ON a.program_first_choice = p.id 
        GROUP BY a.program_first_choice 
        ORDER BY count DESC 
        LIMIT 5";
$result = $conn->query($sql);
$stats['by_program'] = [];
while($row = $result->fetch_assoc()) {
    $stats['by_program'][$row['name']] = $row['count'];
}

// Recent applications
$sql = "SELECT a.id, a.first_name, a.last_name, a.status, a.created_at, p.name as program 
        FROM applications a 
        JOIN programs p ON a.program_first_choice = p.id 
        ORDER BY a.created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);
$recent_applications = [];
while($row = $result->fetch_assoc()) {
    $recent_applications[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Admission System</title>
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
                <div class="dashboard-welcome">
                    <h2>Welcome to Admin Dashboard</h2>
                    <p>Manage applications, view analytics, and process admissions</p>
                </div>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Applications</h3>
                            <p class="stat-number"><?php echo $stats['total']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Review</h3>
                            <p class="stat-number"><?php echo isset($stats['by_status']['Submitted']) ? $stats['by_status']['Submitted'] : 0; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Approved</h3>
                            <p class="stat-number"><?php echo isset($stats['by_status']['Approved']) ? $stats['by_status']['Approved'] : 0; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Rejected</h3>
                            <p class="stat-number"><?php echo isset($stats['by_status']['Rejected']) ? $stats['by_status']['Rejected'] : 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-row">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Applications by Status</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Top Programs</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="programChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Applications</h3>
                        <a href="applications.php" class="view-all">View All</a>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_applications as $app): ?>
                                <tr>
                                    <td>#<?php echo $app['id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['program']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" class="action-btn view-btn" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="process-application.php?id=<?php echo $app['id']; ?>" class="action-btn edit-btn" title="Process"><i class="fas fa-cog"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_applications)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">No applications found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-row">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>AI Course Matching</h3>
                        </div>
                        <div class="card-body">
                            <div class="ai-status">
                                <div class="ai-status-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="ai-status-content">
                                    <h4>AI Matching Engine</h4>
                                    <p>The AI engine is active and processing applications</p>
                                    <div class="ai-metrics">
                                        <div class="ai-metric">
                                            <span class="metric-label">Processed Today:</span>
                                            <span class="metric-value">12</span>
                                        </div>
                                        <div class="ai-metric">
                                            <span class="metric-label">Avg. Processing Time:</span>
                                            <span class="metric-value">2.3 hours</span>
                                        </div>
                                        <div class="ai-metric">
                                            <span class="metric-label">Accuracy Rate:</span>
                                            <span class="metric-value">94.7%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ai-actions">
                                <a href="ai-settings.php" class="btn secondary-btn"><i class="fas fa-cog"></i> Configure AI</a>
                                <a href="ai-process.php" class="btn primary-btn"><i class="fas fa-play"></i> Run Matching Now</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>System Notifications</h3>
                        </div>
                        <div class="card-body">
                            <ul class="notification-list">
                                <li class="notification">
                                    <div class="notification-icon success">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>Email notifications sent to 15 applicants</p>
                                        <span class="notification-time">2 hours ago</span>
                                    </div>
                                </li>
                                <li class="notification">
                                    <div class="notification-icon warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>5 applications require manual review</p>
                                        <span class="notification-time">5 hours ago</span>
                                    </div>
                                </li>
                                <li class="notification">
                                    <div class="notification-icon info">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>AI matching completed for 8 applications</p>
                                        <span class="notification-time">Yesterday</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Status Chart
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
                            $counts[] = isset($stats['by_status'][$status]) ? $stats['by_status'][$status] : 0;
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
        
        // Program Chart
        const programCtx = document.getElementById('programChart').getContext('2d');
        const programChart = new Chart(programCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    $programs = array_keys($stats['by_program']);
                    echo "'" . implode("', '", $programs) . "'";
                    ?>
                ],
                datasets: [{
                    label: 'Applications',
                    data: [
                        <?php 
                        echo implode(", ", array_values($stats['by_program']));
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
    </script>
</body>
</html>

