<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

// Get user information
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM users WHERE id = ?";

if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $user_id;
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
        } else {
            // User not found
            header("location: logout.php");
            exit;
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    $stmt->close();
}

// Check application status
$application_status = "Not Started";
$application_id = null;

$sql = "SELECT * FROM applications WHERE user_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_user_id);
    $param_user_id = $user_id;
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $application = $result->fetch_assoc();
            $application_id = $application['id'];
            $application_status = $application['status'];
        }
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - University Admission Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="/placeholder.svg?height=80&width=80" alt="University Logo">
                <h1>University of Energy and Natural Resources</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="dashboard-header">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h2>
                <p>Manage your application process from this dashboard</p>
            </section>

            <section class="dashboard-content">
                <div class="dashboard-sidebar">
                    <div class="user-profile">
                        <div class="profile-image">
                            <img src="/placeholder.svg?height=100&width=100" alt="Profile Picture">
                        </div>
                        <h3><?php echo htmlspecialchars($_SESSION["full_name"]); ?></h3>
                        <p><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                        <a href="profile.php" class="btn small-btn">Edit Profile</a>
                    </div>
                    <div class="dashboard-menu">
                        <ul>
                            <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="application.php"><i class="fas fa-file-alt"></i> My Application</a></li>
                            <li><a href="documents.php"><i class="fas fa-file-upload"></i> Documents</a></li>
                            <li><a href="programs.php"><i class="fas fa-graduation-cap"></i> Programs</a></li>
                            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        </ul>
                    </div>
                </div>
                <div class="dashboard-main">
                    <div class="status-card">
                        <h3>Application Status</h3>
                        <div class="status-indicator <?php echo strtolower(str_replace(' ', '-', $application_status)); ?>">
                            <?php echo $application_status; ?>
                        </div>
                        <?php if($application_status == "Not Started"): ?>
                            <p>You haven't started your application yet. Click the button below to begin.</p>
                            <a href="application.php" class="btn primary-btn">Start Application</a>
                        <?php elseif($application_status == "In Progress"): ?>
                            <p>Your application is in progress. Continue where you left off.</p>
                            <a href="application.php" class="btn primary-btn">Continue Application</a>
                        <?php elseif($application_status == "Submitted"): ?>
                            <p>Your application has been submitted and is under review.</p>
                            <a href="application.php" class="btn secondary-btn">View Application</a>
                        <?php elseif($application_status == "Approved"): ?>
                            <p>Congratulations! Your application has been approved.</p>
                            <a href="admission-letter.php" class="btn primary-btn">Download Admission Letter</a>
                        <?php elseif($application_status == "Rejected"): ?>
                            <p>We regret to inform you that your application was not successful.</p>
                            <a href="feedback.php" class="btn secondary-btn">View Feedback</a>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-cards">
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="card-content">
                                <h4>Application Requirements</h4>
                                <p>View the requirements for your application</p>
                                <a href="requirements.php" class="btn small-btn">View Requirements</a>
                            </div>
                        </div>
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="card-content">
                                <h4>Download Form</h4>
                                <p>Download the application form</p>
                                <a href="download-form.php" class="btn small-btn">Download</a>
                            </div>
                        </div>
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-upload"></i>
                            </div>
                            <div class="card-content">
                                <h4>Upload Documents</h4>
                                <p>Upload your required documents</p>
                                <a href="documents.php" class="btn small-btn">Upload</a>
                            </div>
                        </div>
                        <div class="dashboard-card">
                            <div class="card-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-content">
                                <h4>Check Status</h4>
                                <p>Check your application status</p>
                                <a href="status.php" class="btn small-btn">Check Status</a>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-section">
                        <h3>Application Timeline</h3>
                        <div class="timeline">
                            <div class="timeline-item <?php echo ($application_status != "Not Started") ? 'completed' : 'current'; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Registration</h4>
                                    <p>Create an account and complete your profile</p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo ($application_status == "In Progress" || $application_status == "Submitted" || $application_status == "Approved" || $application_status == "Rejected") ? 'completed' : (($application_status != "Not Started") ? 'current' : ''); ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Application Form</h4>
                                    <p>Fill out and submit your application form</p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo ($application_status == "Submitted" || $application_status == "Approved" || $application_status == "Rejected") ? 'completed' : (($application_status == "In Progress") ? 'current' : ''); ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Document Upload</h4>
                                    <p>Upload all required documents</p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo ($application_status == "Approved" || $application_status == "Rejected") ? 'completed' : (($application_status == "Submitted") ? 'current' : ''); ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Application Review</h4>
                                    <p>Your application is being reviewed</p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo ($application_status == "Approved" || $application_status == "Rejected") ? 'completed' : ''; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4>Decision</h4>
                                    <p>Final decision on your application</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> University Road, Sunyani, Ghana</p>
                    <p><i class="fas fa-phone"></i> +233 123 456 789</p>
                    <p><i class="fas fa-envelope"></i> admissions@uenr.edu.gh</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="programs.php">Programs</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 University of Energy and Natural Resources. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

