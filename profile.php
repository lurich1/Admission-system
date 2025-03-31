<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
   header("location: login.php");
   exit;
}

// Include database connection
require_once "config/database.php";

$user_id = $_SESSION["user_id"];
$success_message = '';
$error_message = '';

// Get user information
$sql = "SELECT * FROM users WHERE id = ?";
$user = null;

if($stmt = $conn->prepare($sql)) {
   $stmt->bind_param("i", $user_id);
   
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
       $error_message = "Oops! Something went wrong. Please try again later.";
   }
   
   $stmt->close();
}

// Get notification settings
$sql = "SELECT * FROM notification_settings WHERE user_id = ?";
$notification_settings = null;

if($stmt = $conn->prepare($sql)) {
   $stmt->bind_param("i", $user_id);
   
   if($stmt->execute()) {
       $result = $stmt->get_result();
       
       if($result->num_rows == 1) {
           $notification_settings = $result->fetch_assoc();
       } else {
           // Create default settings
           $sql = "INSERT INTO notification_settings (user_id) VALUES (?)";
           if($insert_stmt = $conn->prepare($sql)) {
               $insert_stmt->bind_param("i", $user_id);
               $insert_stmt->execute();
               $insert_stmt->close();
           }
           
           // Use default values
           $notification_settings = [
               'application_updates' => 1,
               'course_assignments' => 1,
               'document_requests' => 1,
               'general_announcements' => 1,
               'email_notifications' => 1,
               'push_notifications' => 1
           ];
       }
   }
   
   $stmt->close();
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
   if(isset($_POST['update_profile'])) {
       // Update profile information
       $full_name = trim($_POST['full_name']);
       $email = trim($_POST['email']);
       $phone = trim($_POST['phone']);
       
       // Validate input
       if(empty($full_name)) {
           $error_message = "Please enter your full name";
       } else {
           // Update user information
           $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
           
           if($stmt = $conn->prepare($sql)) {
               $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
               
               if($stmt->execute()) {
                   $success_message = "Profile updated successfully!";
                   
                   // Update session variables
                   $_SESSION["full_name"] = $full_name;
                   $_SESSION["email"] = $email;
                   
                   // Refresh user data
                   $user['full_name'] = $full_name;
                   $user['email'] = $email;
                   $user['phone'] = $phone;
               } else {
                   $error_message = "Something went wrong. Please try again later.";
               }
               
               $stmt->close();
           }
       }
   } elseif(isset($_POST['update_password'])) {
       // Update password
       $current_password = trim($_POST['current_password']);
       $new_password = trim($_POST['new_password']);
       $confirm_password = trim($_POST['confirm_password']);
       
       // Validate input
       if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
           $error_message = "Please fill all password fields";
       } elseif($new_password != $confirm_password) {
           $error_message = "New passwords do not match";
       } elseif(strlen($new_password) < 6) {
           $error_message = "Password must have at least 6 characters";
       } else {
           // Verify current password
           if(password_verify($current_password, $user['password'])) {
               // Update password
               $sql = "UPDATE users SET password = ? WHERE id = ?";
               
               if($stmt = $conn->prepare($sql)) {
                   $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                   $stmt->bind_param("si", $param_password, $user_id);
                   
                   if($stmt->execute()) {
                       $success_message = "Password updated successfully!";
                   } else {
                       $error_message = "Something went wrong. Please try again later.";
                   }
                   
                   $stmt->close();
               }
           } else {
               $error_message = "Current password is incorrect";
           }
       }
   } elseif(isset($_POST['update_notifications'])) {
       // Update notification settings
       $application_updates = isset($_POST['application_updates']) ? 1 : 0;
       $course_assignments = isset($_POST['course_assignments']) ? 1 : 0;
       $document_requests = isset($_POST['document_requests']) ? 1 : 0;
       $general_announcements = isset($_POST['general_announcements']) ? 1 : 0;
       $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
       $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
       
       // Check if settings exist
       if($notification_settings) {
           // Update existing settings
           $sql = "UPDATE notification_settings SET 
                   application_updates = ?, 
                   course_assignments = ?, 
                   document_requests = ?, 
                   general_announcements = ?, 
                   email_notifications = ?, 
                   push_notifications = ?, 
                   updated_at = NOW() 
                   WHERE user_id = ?";
           
           if($stmt = $conn->prepare($sql)) {
               $stmt->bind_param("iiiiiii", 
                   $application_updates, 
                   $course_assignments, 
                   $document_requests, 
                   $general_announcements, 
                   $email_notifications, 
                   $push_notifications, 
                   $user_id
               );
               
               if($stmt->execute()) {
                   $success_message = "Notification settings updated successfully!";
                   
                   // Update local settings
                   $notification_settings['application_updates'] = $application_updates;
                   $notification_settings['course_assignments'] = $course_assignments;
                   $notification_settings['document_requests'] = $document_requests;
                   $notification_settings['general_announcements'] = $general_announcements;
                   $notification_settings['email_notifications'] = $email_notifications;
                   $notification_settings['push_notifications'] = $push_notifications;
               } else {
                   $error_message = "Something went wrong. Please try again later.";
               }
               
               $stmt->close();
           }
       } else {
           // Insert new settings
           $sql = "INSERT INTO notification_settings (
                   user_id, 
                   application_updates, 
                   course_assignments, 
                   document_requests, 
                   general_announcements, 
                   email_notifications, 
                   push_notifications
               ) VALUES (?, ?, ?, ?, ?, ?, ?)";
           
           if($stmt = $conn->prepare($sql)) {
               $stmt->bind_param("iiiiiii", 
                   $user_id, 
                   $application_updates, 
                   $course_assignments, 
                   $document_requests, 
                   $general_announcements, 
                   $email_notifications, 
                   $push_notifications
               );
               
               if($stmt->execute()) {
                   $success_message = "Notification settings updated successfully!";
                   
                   // Update local settings
                   $notification_settings = [
                       'application_updates' => $application_updates,
                       'course_assignments' => $course_assignments,
                       'document_requests' => $document_requests,
                       'general_announcements' => $general_announcements,
                       'email_notifications' => $email_notifications,
                       'push_notifications' => $push_notifications
                   ];
               } else {
                   $error_message = "Something went wrong. Please try again later.";
               }
               
               $stmt->close();
           }
       }
   }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Profile - University Admission Portal</title>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="logged-in">
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
                   <li><a href="dashboard.php">Dashboard</a></li>
                   <li><a href="logout.php">Logout</a></li>
               </ul>
           </nav>
       </header>

       <main>
           <section class="page-header">
               <h2>Profile Settings</h2>
               <p>Manage your account information and preferences</p>
           </section>

           <section class="profile-section">
               <div class="profile-sidebar">
                   <div class="user-profile">
                       <div class="profile-image">
                           <img src="/placeholder.svg?height=100&width=100" alt="Profile Picture">
                       </div>
                       <h3><?php echo htmlspecialchars($_SESSION["full_name"]); ?></h3>
                       <p><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                   </div>
                   <div class="profile-menu">
                       <ul>
                           <li><a href="#profile-info" class="active">Personal Information</a></li>
                           <li><a href="#password">Change Password</a></li>
                           <li><a href="#notifications">Notification Settings</a></li>
                       </ul>
                   </div>
               </div>
               
               <div class="profile-content">
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
                   
                   <div id="profile-info" class="profile-card">
                       <div class="card-header">
                           <h3>Personal Information</h3>
                       </div>
                       <div class="card-body">
                           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="profile-form">
                               <div class="form-group">
                                   <label for="full_name">Full Name</label>
                                   <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                               </div>
                               <div class="form-group">
                                   <label for="email">Email Address</label>
                                   <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                               </div>
                               <div class="form-group">
                                   <label for="phone">Phone Number</label>
                                   <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                               </div>
                               <button type="submit" name="update_profile" class="btn primary-btn">Update Profile</button>
                           </form>
                       </div>
                   </div>
                   
                   <div id="password" class="profile-card">
                       <div class="card-header">
                           <h3>Change Password</h3>
                       </div>
                       <div class="card-body">
                           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="profile-form">
                               <div class="form-group">
                                   <label for="current_password">Current Password</label>
                                   <input type="password" id="current_password" name="current_password" required>
                               </div>
                               <div class="form-group">
                                   <label for="new_password">New Password</label>
                                   <input type="password" id="new_password" name="new_password" required>
                               </div>
                               <div class="form-group">
                                   <label for="confirm_password">Confirm New Password</label>
                                   <input type="password" id="confirm_password" name="confirm_password" required>
                               </div>
                               <button type="submit" name="update_password" class="btn primary-btn">Change Password</button>
                           </form>
                       </div>
                   </div>
                   
                   <div id="notifications" class="profile-card">
                       <div class="card-header">
                           <h3>Notification Settings</h3>
                       </div>
                       <div class="card-body">
                           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="profile-form" id="notification-settings-form">
                               <div class="form-section">
                                   <h4>Notification Types</h4>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="application_updates" name="application_updates" <?php echo $notification_settings['application_updates'] ? 'checked' : ''; ?>>
                                       <label for="application_updates">Application Status Updates</label>
                                   </div>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="course_assignments" name="course_assignments" <?php echo $notification_settings['course_assignments'] ? 'checked' : ''; ?>>
                                       <label for="course_assignments">Course Assignments</label>
                                   </div>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="document_requests" name="document_requests" <?php echo $notification_settings['document_requests'] ? 'checked' : ''; ?>>
                                       <label for="document_requests">Document Requests</label>
                                   </div>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="general_announcements" name="general_announcements" <?php echo $notification_settings['general_announcements'] ? 'checked' : ''; ?>>
                                       <label for="general_announcements">General Announcements</label>
                                   </div>
                               </div>
                               
                               <div class="form-section">
                                   <h4>Notification Channels</h4>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="email_notifications" name="email_notifications" <?php echo $notification_settings['email_notifications'] ? 'checked' : ''; ?>>
                                       <label for="email_notifications">Email Notifications</label>
                                   </div>
                                   <div class="form-group checkbox-group">
                                       <input type="checkbox" id="push_notifications" name="push_notifications" <?php echo $notification_settings['push_notifications'] ? 'checked' : ''; ?>>
                                       <label for="push_notifications">Push Notifications</label>
                                   </div>
                               </div>
                               
                               <button type="submit" name="update_notifications" class="btn primary-btn">Save Notification Settings</button>
                           </form>
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
   <script src="js/notification.js"></script>
</body>
</html>

