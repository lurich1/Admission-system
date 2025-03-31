<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Set default filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query
$query = "SELECT a.id, a.first_name, a.last_name, a.email, a.status, a.created_at, p.name as program 
          FROM applications a 
          JOIN programs p ON a.program_first_choice = p.id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM applications a 
                JOIN programs p ON a.program_first_choice = p.id 
                WHERE 1=1";

$params = [];
$types = "";

if(!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $count_query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($program_filter)) {
    $query .= " AND a.program_first_choice = ?";
    $count_query .= " AND a.program_first_choice = ?";
    $params[] = $program_filter;
    $types .= "i";
}

if(!empty($search)) {
    $query .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
    $count_query .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Get total records
$stmt = $conn->prepare($count_query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get applications with pagination
$query .= " ORDER BY a.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$applications = [];
while($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Get programs for filter
$programs = [];
$sql = "SELECT id, name FROM programs ORDER BY name";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $programs[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - University Admission System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>
            
            <main class="admin-main">
                <div class="page-header">
                    <h2>Manage Applications</h2>
                    <div class="header-actions">
                        <a href="export-applications.php" class="btn secondary-btn"><i class="fas fa-download"></i> Export</a>
                        <a href="ai-process.php" class="btn primary-btn"><i class="fas fa-robot"></i> Run AI Matching</a>
                    </div>
                </div>
                
                <div class="filter-section">
                    <form action="" method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Not Started" <?php echo $status_filter == 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                    <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Submitted" <?php echo $status_filter == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="program">Program</label>
                                <select id="program" name="program">
                                    <option value="">All Programs</option>
                                    <?php foreach($programs as $program): ?>
                                    <option value="<?php echo $program['id']; ?>" <?php echo $program_filter == $program['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group search-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" placeholder="Name or Email" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn primary-btn">Apply Filters</button>
                                <a href="applications.php" class="btn secondary-btn">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="data-section">
                    <div class="data-header">
                        <div class="data-summary">
                            Showing <?php echo count($applications); ?> of <?php echo $total_records; ?> applications
                        </div>
                        <div class="bulk-actions">
                            <select id="bulk-action">
                                <option value="">Bulk Actions</option>
                                <option value="approve">Approve Selected</option>
                                <option value="reject">Reject Selected</option>
                                <option value="email">Send Email to Selected</option>
                            </select>
                            <button id="apply-bulk" class="btn secondary-btn">Apply</button>
                        </div>
                    </div>
                    
                    <div class="data-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($applications as $app): ?>
                                <tr>
                                    <td><input type="checkbox" class="select-item" value="<?php echo $app['id']; ?>"></td>
                                    <td>#<?php echo $app['id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                                    <td><?php echo htmlspecialchars($app['program']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view-application.php?id=<?php echo $app['id']; ?>" class="action-btn view-btn" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="process-application.php?id=<?php echo $app['id']; ?>" class="action-btn edit-btn" title="Process"><i class="fas fa-cog"></i></a>
                                            <a href="email-applicant.php?id=<?php echo $app['id']; ?>" class="action-btn email-btn" title="Email"><i class="fas fa-envelope"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($applications)): ?>
                                <tr>
                                    <td colspan="8" class="no-data">No applications found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($status_filter); ?>&program=<?php echo urlencode($program_filter); ?>&search=<?php echo urlencode($search); ?>" class="pagination-btn"><i class="fas fa-chevron-left"></i> Previous</a>
                        <?php endif; ?>
                        
                        <div class="pagination-pages">
                            <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&program=<?php echo urlencode($program_filter); ?>&search=<?php echo urlencode($search); ?>" class="pagination-page <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($status_filter); ?>&program=<?php echo urlencode($program_filter); ?>&search=<?php echo urlencode($search); ?>" class="pagination-btn">Next <i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select  function() {
            const checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Bulk actions
        document.getElementById('apply-bulk').addEventListener('click', function() {
            const action = document.getElementById('bulk-action').value;
            if (!action) {
                alert('Please select an action');
                return;
            }
            
            const selected = Array.from(document.querySelectorAll('.select-item:checked')).map(el => el.value);
            if (selected.length === 0) {
                alert('Please select at least one application');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selected.length} applications?`)) {
                // Send AJAX request to process bulk action
                fetch('process-bulk-action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        ids: selected
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the request');
                });
            }
        });
    </script>
</body>
</html>

