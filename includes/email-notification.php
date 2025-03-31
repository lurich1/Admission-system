<?php
/**
 * Email Notification System
 * 
 * This file contains functions for sending email notifications to applicants
 */

// Function to send email notification
function sendEmailNotification($to, $subject, $message, $from = 'admissions@uenr.edu.gh') {
    // Set headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: University Admissions <$from>" . "\r\n";
    
    // In a real application, you would use a proper email sending library
    // like PHPMailer or the mail() function
    
    // For this demo, we'll just simulate sending
    // Return true to simulate successful sending
    return true;
}

// Function to send application status update notification
function sendStatusUpdateNotification($application_id, $conn) {
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
                
                $to = $application['user_email'];
                $subject = "Update on Your Application Status";
                
                // Create message based on status
                $message = "<html><body>";
                $message .= "<h2>Application Status Update</h2>";
                $message .= "<p>Dear " . htmlspecialchars($application['first_name']) . ",</p>";
                
                switch($application['status']) {
                    case 'Submitted':
                        $message .= "<p>Thank you for submitting your application to the " . htmlspecialchars($application['program_name']) . " program at the University of Energy and Natural Resources.</p>";
                        $message .= "<p>Your application has been received and is currently under review. We will notify you of any updates or decisions regarding your admission status.</p>";
                        break;
                    case 'Approved':
                        $message .= "<p>Congratulations! We are pleased to inform you that your application to the " . htmlspecialchars($application['program_name']) . " program at the University of Energy and Natural Resources has been approved.</p>";
                        $message .= "<p>Please log in to your account to view your admission letter and next steps for enrollment.</p>";
                        break;
                    case 'Rejected':
                        $message .= "<p>Thank you for your interest in the University of Energy and Natural Resources and for submitting your application to the " . htmlspecialchars($application['program_name']) . " program.</p>";
                        $message .= "<p>After careful review of your application, we regret to inform you that we are unable to offer you admission at this time.</p>";
                        $message .= "<p>We encourage you to explore other programs that may better align with your qualifications and interests.</p>";
                        break;
                    default:
                        $message .= "<p>Your application status has been updated. Please log in to your account to view the current status of your application.</p>";
                }
                
                $message .= "<p>If you have any questions, please don't hesitate to contact our admissions office.</p>";
                $message .= "<p>Best regards,<br>Admissions Team<br>University of Energy and Natural Resources</p>";
                $message .= "</body></html>";
                
                // Send email
                return sendEmailNotification($to, $subject, $message);
            }
        }
        
        $stmt->close();
    }
    
    return false;
}

// Function to send AI recommendation notification
function sendAIRecommendationNotification($application_id, $conn) {
    // Get application and AI recommendation details
    $sql = "SELECT a.*, u.email as user_email, r.primary_program, r.alternate_program 
            FROM applications a 
            JOIN users u ON a.user_id = u.id 
            JOIN ai_recommendations r ON a.id = r.application_id 
            WHERE a.id = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $application_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            if($result->num_rows == 1) {
                $data = $result->fetch_assoc();
                
                $to = $data['user_email'];
                $subject = "Program Recommendation for Your Application";
                
                // Create message
                $message = "<html><body>";
                $message .= "<h2>Program Recommendation</h2>";
                $message .= "<p>Dear " . htmlspecialchars($data['first_name']) . ",</p>";
                $message .= "<p>Based on your academic background and qualifications, our system has analyzed your application and recommends the following program(s):</p>";
                $message .= "<ul>";
                $message .= "<li><strong>Primary Recommendation:</strong> " . htmlspecialchars($data['primary_program']) . "</li>";
                
                if(!empty($data['alternate_program'])) {
                    $message .= "<li><strong>Alternate Recommendation:</strong> " . htmlspecialchars($data['alternate_program']) . "</li>";
                }
                
                $message .= "</ul>";
                $message .= "<p>These recommendations are based on your academic profile and the requirements of our programs. You can log in to your account to view more details about these recommendations.</p>";
                $message .= "<p>If you have any questions or would like to discuss these recommendations further, please contact our admissions office.</p>";
                $message .= "<p>Best regards,<br>Admissions Team<br>University of Energy and Natural Resources</p>";
                $message .= "</body></html>";
                
                // Send email
                return sendEmailNotification($to, $subject, $message);
            }
        }
        
        $stmt->close();
    }
    
    return false;
}
?>

