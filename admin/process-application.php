<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
   header("location: login.php");
   exit;
}

// Include database connection and notification system
require_once "../config/database.php";
require_once "../includes/notification-system.php";
require_once "../includes/email-notification.php";

$success_message = '';
$error_message = '';

// Check if application ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
   header("location: applications.php");
   exit;
}

$application_id = $_GET['id'];

// Get application details
$sql = "SELECT a.*, u.id as user_id, p.name as program_name 
       FROM applications a 
       JOIN users u ON a.user_id = u.id 
       JOIN programs p ON a.program_first_choice = p.id 
       WHERE a.id = ?";

if($stmt = $conn->prepare($sql)) {
   $stmt->bind_param("i", $application_id);
   
   if($stmt->execute()) {
       $result = $stmt->get_result();
       
       if($result->num_rows == 1) {
           $application = $result->fetch_assoc();
       } else {
           // Application not found
           header("location: applications.php");
           exit;
       }
   } else {
       echo "Oops! Something went wrong. Please try again later.";
       exit;
   }
   
   $stmt->close();
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
   $status = $_POST['status'];
   $admin_notes = $_POST['admin_notes'];
   $send_notification = isset($_POST['send_notification']) ? true : false;
   
   // Update application status
   $sql = "UPDATE applications SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?";
   
   if($stmt = $conn->prepare($sql)) {
       $stmt->bind_param("ssi", $status, $admin_notes, $application_id);
       
       if($stmt->execute()) {
           $success_message = "Application status updated successfully!";
           
           // Send email notification
           if($send_notification) {
               sendStatusUpdateNotification($application_id, $conn);
               
               // Send push notification for course assignment if approved
               if($status === 'Approved') {
                   sendCourseAssignmentNotification(
                       $application['user_id'], 
                       $application_id, 
                       $application['program_name']
                   );
               }
           }
       } else {
           $error_message = "Error: Failed to update application status.";
       }
       
       $stmt->close();
   }
}

// Get AI recommendations if available
$ai_recommendations = null;
$sql = "SELECT * FROM ai_recommendations WHERE application_id = ?";
if($stmt = $conn->prepare($sql)) {
   $stmt->bind_param("i", $application_id);
   
   if($stmt->execute()) {
       $result = $stmt->get_result();
       
       if($result->num_rows > 0) {
           $ai_recommendations = $result->fetch_assoc();
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
   <title>Process Application - University Admission System</title>
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
                   <h2>Process Application</h2>
                   <div class="header-actions">
                       <a href="applications.php" class="btn secondary-btn"><i class="fas fa-arrow-left"></i> Back to List</a>
                       <a href="view-application.php?id=<?php echo $application_id; ?>" class="btn primary-btn"><i class="fas fa-eye"></i> View Application</a>
                   </div>
               </div>
               
               <?php if(!empty($success_message)): ?>
                   <div class="success-message">
                       <?php echo $success_message; ?>
                   </div>
               <?php endif; ?>
               
               <?php if(!empty($error_message)): ?>
                   <div class="error-message">
                       <?php echo $error_message; ?>
                   </div>
               <?php endif; ?>
               
               <div class="process-application-section">
                   <div class="applicant-info-card">
                       <div class="applicant-header">
                           <h3>Applicant Information</h3>
                       </div>
                       <div class="applicant-details">
                           <div class="detail-item">
                               <span class="detail-label">Name:</span>
                               <span class="detail-value"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                           </div>
                           <div class="detail-item">
                               <span class="detail-label">Email:</span>
                               <span class="detail-value"><?php echo htmlspecialchars($application['email']); ?></span>
                           </div>
                           <div class="detail-item">
                               <span class="detail-label">Program:</span>
                               <span class="detail-value"><?php echo htmlspecialchars($application['program_name']); ?></span>
                           </div>
                           <div class="detail-item">
                               <span class="detail-label">Current Status:</span>
                               <span class="status-badge <?php echo strtolower($application['status']); ?>"><?php echo $application['status']; ?></span>
                           </div>
                       </div>
                   </div>
                   
                   <?php if($ai_recommendations): ?>
                   <div class="ai-recommendation-card">
                       <div class="card-header">
                           <h3><i class="fas fa-robot"></i> AI Recommendation</h3>
                       </div>
                       <div class="card-body">
                           <div class="recommendation-summary">
                               <div class="recommendation-item">
                                   <span class="recommendation-label">Primary Program:</span>
                                   <span class="recommendation-value"><?php echo htmlspecialchars($ai_recommendations['primary_program']); ?></span>
                                   <span class="match-score"><?php echo $ai_recommendations['primary_match_score']; ?>% Match</span>
                               </div>
                               <?php if(!empty($ai_recommendations['alternate_program'])): ?>
                               <div class="recommendation-item">
                                   <span class="recommendation-label">Alternate Program:</span>
                                   <span class="recommendation-value"><?php echo htmlspecialchars($ai_recommendations['alternate_program']); ?></span>
                                   <span class="match-score"><?php echo $ai_recommendations['alternate_match_score']; ?>% Match</span>
                               </div>
                               <?php endif; ?>
                               <div class="recommendation-item">
                                   <span class="recommendation-label">Confidence Score:</span>
                                   <span class="recommendation-value"><?php echo $ai_recommendations['confidence_score']; ?>%</span>
                               </div>
                           </div>
                           <div class="recommendation-analysis">
                               <h4>Analysis</h4>
                               <p><?php echo nl2br(htmlspecialchars($ai_recommendations['analysis'])); ?></p>
                           </div>
                       </div>
                   </div>
                   <?php endif; ?>
                   
                   <div class="process-form-card">
                       <div class="card-header">
                           <h3><i class="fas fa-cog"></i> Process Application</h3>
                       </div>
                       <div class="card-body">
                           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $application_id); ?>" method="post" class="process-form">
                               <div class="form-group">
                                   <label for="status">Application Status</label>
                                   <select id="status" name="status" required>
                                       <option value="Submitted" <?php echo $application['status'] == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                       <option value="Approved" <?php echo $application['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                       <option value="Rejected" <?php echo $application['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                   </select>
                               </div>
                               <div class="form-group">
                                   <label for="admin_notes">Admin Notes</label>
                                   <textarea id="admin_notes" name="admin_notes" rows="5"><?php echo isset($application['admin_notes']) ? htmlspecialchars($application['admin_notes']) : ''; ?></textarea>
                               </div>
                               <div class="form-group notification-option">
                                   <input type="checkbox" id="send_notification" name="send_notification" checked>
                                   <label for="send_notification">Send notification to applicant</label>
                               </div>
                               <div class="form-actions">
                                   <button type="submit" class="btn primary-btn">Update Application</button>
                               </div>
                           </form>
                       </div>
                   </div>
               </div>
           </main>
       </div>
   </div>

   <script>
       // Show confirmation dialog when changing status to Approved or Rejected
       document.getElementById('status').addEventListener('change', function() {
           const status = this.value;
           
           if(status === 'Approved' || status === 'Rejected') {
               const confirmed = confirm(`Are you sure you want to change the status to ${status}? This will send a notification to the applicant if the notification option is checked.`);
               
               if(!confirmed) {
                   this.value = '<?php echo $application['status']; ?>';
               }
           }
       });
   </script>
</body>
</html>

