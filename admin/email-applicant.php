<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Check if application ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: applications.php");
    exit;
}

$application_id = $_GET['id'];
$success_message = '';
$error_message = '';

// Get application details
$sql = "SELECT a.*, u.email as user_email, p.name as program_name 
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

// Process email form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $recipient_email = trim($_POST['recipient_email']);
    
    if(empty($subject) || empty($message) || empty($recipient_email)) {
        $error_message = "Please fill all required fields";
    } else {
        // In a real application, this would send an actual email
        // For this demo, we'll just simulate sending
        
        // Log the email in the database
        $sql = "INSERT INTO email_logs (application_id, recipient, subject, message, sent_by, sent_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isssi", $application_id, $recipient_email, $subject, $message, $_SESSION["admin_id"]);
            
            if($stmt->execute()) {
                $success_message = "Email sent successfully to " . htmlspecialchars($recipient_email);
            } else {
                $error_message = "Failed to send email. Please try again.";
            }
            
            $stmt->close();
        }
    }
}

// Get email templates
$templates = [
    [
        'id' => 1,
        'name' => 'Application Received',
        'subject' => 'Your Application Has Been Received',
        'message' => "Dear [FIRST_NAME],\n\nThank you for submitting your application to the University of Energy and Natural Resources. We are pleased to confirm that we have received your application for the [PROGRAM] program.\n\nYour application is currently under review, and we will notify you of any updates or decisions regarding your admission status.\n\nIf you have any questions, please don't hesitate to contact our admissions office.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources"
    ],
    [
        'id' => 2,
        'name' => 'Application Approved',
        'subject' => 'Congratulations! Your Application Has Been Approved',
        'message' => "Dear [FIRST_NAME],\n\nCongratulations! We are pleased to inform you that your application to the [PROGRAM] program at the University of Energy and Natural Resources has been approved.\n\nPlease log in to your account to view your admission letter and next steps for enrollment.\n\nWe look forward to welcoming you to our university community.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources"
    ],
    [
        'id' => 3,
        'name' => 'Application Rejected',
        'subject' => 'Update on Your Application Status',
        'message' => "Dear [FIRST_NAME],\n\nThank you for your interest in the University of Energy and Natural Resources and for submitting your application to the [PROGRAM] program.\n\nAfter careful review of your application, we regret to inform you that we are unable to offer you admission at this time.\n\nWe encourage you to explore other programs that may better align with your qualifications and interests.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources"
    ],
    [
        'id' => 4,
        'name' => 'Additional Documents Required',
        'subject' => 'Additional Documents Required for Your Application',
        'message' => "Dear [FIRST_NAME],\n\nThank you for submitting your application to the [PROGRAM] program at the University of Energy and Natural Resources.\n\nUpon reviewing your application, we find that we need additional documents to complete the evaluation process. Please log in to your account and upload the following documents:\n\n- [DOCUMENT_LIST]\n\nPlease submit these documents at your earliest convenience to avoid delays in processing your application.\n\nBest regards,\nAdmissions Team\nUniversity of Energy and Natural Resources"
    ]
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Applicant - University Admission System</title>
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
                    <h2>Email Applicant</h2>
                    <div class="header-actions">
                        <a href="view-application.php?id=<?php echo $application_id; ?>" class="btn secondary-btn"><i class="fas fa-arrow-left"></i> Back to Application</a>
                    </div>
                </div>
                
                <div class="email-section">
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
                                <span class="detail-value"><?php echo htmlspecialchars($application['user_email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Program:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($application['program_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="status-badge <?php echo strtolower($application['status']); ?>"><?php echo $application['status']; ?></span>
                            </div>
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
                    
                    <div class="email-form-container">
                        <div class="email-templates">
                            <h3>Email Templates</h3>
                            <div class="template-list">
                                <?php foreach($templates as $template): ?>
                                <div class="template-item" data-id="<?php echo $template['id']; ?>" data-subject="<?php echo htmlspecialchars($template['subject']); ?>" data-message="<?php echo htmlspecialchars($template['message']); ?>">
                                    <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                                    <button class="use-template-btn">Use Template</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="email-composer">
                            <h3>Compose Email</h3>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $application_id); ?>" method="post" class="email-form">
                                <div class="form-group">
                                    <label for="recipient_email">To</label>
                                    <input type="email" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($application['user_email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="subject">Subject</label>
                                    <input type="text" id="subject" name="subject" required>
                                </div>
                                <div class="form-group">
                                    <label for="message">Message</label>
                                    <textarea id="message" name="message" rows="12" required></textarea>
                                </div>
                                <div class="email-placeholders">
                                    <h4>Available Placeholders</h4>
                                    <div class="placeholder-list">
                                        <div class="placeholder-item" data-placeholder="[FIRST_NAME]">
                                            <span class="placeholder-text">[FIRST_NAME]</span>
                                            <span class="placeholder-value"><?php echo htmlspecialchars($application['first_name']); ?></span>
                                        </div>
                                        <div class="placeholder-item" data-placeholder="[LAST_NAME]">
                                            <span class="placeholder-text">[LAST_NAME]</span>
                                            <span class="placeholder-value"><?php echo htmlspecialchars($application['last_name']); ?></span>
                                        </div>
                                        <div class="placeholder-item" data-placeholder="[PROGRAM]">
                                            <span class="placeholder-text">[PROGRAM]</span>
                                            <span class="placeholder-value"><?php echo htmlspecialchars($application['program_name']); ?></span>
                                        </div>
                                        <div class="placeholder-item" data-placeholder="[APPLICATION_ID]">
                                            <span class="placeholder-text">[APPLICATION_ID]</span>
                                            <span class="placeholder-value"><?php echo $application_id; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn primary-btn"><i class="fas fa-paper-plane"></i> Send Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Use template
        document.querySelectorAll('.use-template-btn').forEach(button => {
            button.addEventListener('click', function() {
                const templateItem = this.parentElement;
                const subject = templateItem.getAttribute('data-subject');
                const message = templateItem.getAttribute('data-message');
                
                document.getElementById('subject').value = subject;
                document.getElementById('message').value = message;
            });
        });
        
        // Insert placeholder
        document.querySelectorAll('.placeholder-item').forEach(item => {
            item.addEventListener('click', function() {
                const placeholder = this.getAttribute('data-placeholder');
                const messageField = document.getElementById('message');
                
                // Insert at cursor position or append to end
                if (messageField.selectionStart || messageField.selectionStart === 0) {
                    const startPos = messageField.selectionStart;
                    const endPos = messageField.selectionEnd;
                    messageField.value = messageField.value.substring(0, startPos) + placeholder + messageField.value.substring(endPos, messageField.value.length);
                    messageField.selectionStart = startPos + placeholder.length;
                    messageField.selectionEnd = startPos + placeholder.length;
                } else {
                    messageField.value += placeholder;
                }
                
                messageField.focus();
            });
        });
    </script>
</body>
</html>

