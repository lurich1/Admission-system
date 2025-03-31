<?php
/**
 * Notification System
 * 
 * This file contains functions for sending notifications to users
 * through various channels (push notifications, email, in-app)
 */

// Include required files
require_once 'config/database.php';
require_once 'includes/email-notification.php';

/**
 * Send a notification to a user
 * 
 * @param int $user_id The ID of the user to notify
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $type The notification type (application_status, course_assignment, etc.)
 * @param array $additional_data Additional data to include with the notification
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendNotification($user_id, $title, $message, $type, $additional_data = []) {
    global $conn;
    
    // First, save notification to database
    $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        
        if(!$stmt->execute()) {
            // Failed to save notification
            return false;
        }
        
        $notification_id = $stmt->insert_id;
        $stmt->close();
    } else {
        return false;
    }
    
    // Check user notification preferences
    $sql = "SELECT * FROM notification_settings WHERE user_id = ?";
    $settings = null;
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                $settings = $result->fetch_assoc();
            } else {
                // Create default settings if none exist
                $sql = "INSERT INTO notification_settings (user_id) VALUES (?)";
                if($insert_stmt = $conn->prepare($sql)) {
                    $insert_stmt->bind_param("i", $user_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                
                // Use default settings
                $settings = [
                    'application_updates' => true,
                    'course_assignments' => true,
                    'document_requests' => true,
                    'general_announcements' => true,
                    'email_notifications' => true,
                    'push_notifications' => true
                ];
            }
        }
        
        $stmt->close();
    }
    
    // Check if this type of notification is enabled
    $type_enabled = true;
    switch($type) {
        case 'application_status':
            $type_enabled = $settings['application_updates'];
            break;
        case 'course_assignment':
            $type_enabled = $settings['course_assignments'];
            break;
        case 'document_request':
            $type_enabled = $settings['document_requests'];
            break;
        case 'general':
            $type_enabled = $settings['general_announcements'];
            break;
    }
    
    if(!$type_enabled) {
        // This type of notification is disabled for this user
        return false;
    }
    
    // Get user email for email notifications
    $user_email = null;
    $sql = "SELECT email FROM users WHERE id = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_email = $user['email'];
            }
        }
        
        $stmt->close();
    }
    
    // Send email notification if enabled
    if($settings['email_notifications'] && $user_email) {
        sendEmailNotification($user_email, $title, $message);
    }
    
    // Send push notification if enabled
    if($settings['push_notifications']) {
        sendPushNotification($user_id, $title, $message, $type, $additional_data);
    }
    
    return true;
}

/**
 * Send a push notification to a user's devices
 * 
 * @param int $user_id The ID of the user to notify
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $type The notification type
 * @param array $additional_data Additional data to include with the notification
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendPushNotification($user_id, $title, $message, $type, $additional_data = []) {
    global $conn;
    
    // Check if push notifications are enabled in system settings
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = 'push_notifications_enabled'";
    $result = $conn->query($sql);
    
    if($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if($row['setting_value'] !== 'true') {
            // Push notifications are disabled system-wide
            return false;
        }
    }
    
    // Get user's device tokens
    $sql = "SELECT device_token, device_type FROM device_tokens WHERE user_id = ? AND is_active = TRUE";
    $device_tokens = [];
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            while($row = $result->fetch_assoc()) {
                $device_tokens[] = [
                    'token' => $row['device_token'],
                    'type' => $row['device_type']
                ];
            }
        }
        
        $stmt->close();
    }
    
    if(empty($device_tokens)) {
        // No device tokens found for this user
        return false;
    }
    
    // Prepare notification payload
    $payload = [
        'title' => $title,
        'body' => $message,
        'type' => $type,
        'data' => $additional_data
    ];
    
    // Group tokens by device type
    $android_tokens = [];
    $ios_tokens = [];
    
    foreach($device_tokens as $device) {
        if($device['type'] === 'android') {
            $android_tokens[] = $device['token'];
        } else if($device['type'] === 'ios') {
            $ios_tokens[] = $device['token'];
        }
    }
    
    $success = true;
    
    // Send to Android devices using Firebase Cloud Messaging
    if(!empty($android_tokens)) {
        $success = $success && sendFCMNotification($android_tokens, $payload);
    }
    
    // Send to iOS devices using Apple Push Notification Service
    if(!empty($ios_tokens)) {
        $success = $success && sendAPNSNotification($ios_tokens, $payload);
    }
    
    return $success;
}

/**
 * Send notification to Android devices using Firebase Cloud Messaging
 * 
 * @param array $tokens Array of FCM tokens
 * @param array $payload Notification payload
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendFCMNotification($tokens, $payload) {
    // Firebase Cloud Messaging API key (should be stored securely)
    $api_key = 'YOUR_FCM_API_KEY';
    
    // Prepare FCM message
    $fields = [
        'registration_ids' => $tokens,
        'notification' => [
            'title' => $payload['title'],
            'body' => $payload['body'],
            'sound' => 'default',
            'badge' => '1'
        ],
        'data' => [
            'type' => $payload['type'],
            'custom_data' => $payload['data']
        ]
    ];
    
    // Set headers
    $headers = [
        'Authorization: key=' . $api_key,
        'Content-Type: application/json'
    ];
    
    // Send request to FCM
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    // Check for errors
    if($result === false) {
        return false;
    }
    
    $response = json_decode($result, true);
    return isset($response['success']) && $response['success'] > 0;
}

/**
 * Send notification to iOS devices using Apple Push Notification Service
 * 
 * @param array $tokens Array of APNS tokens
 * @param array $payload Notification payload
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendAPNSNotification($tokens, $payload) {
    // In a real implementation, you would use a library like php-apns
    // For this demo, we'll simulate a successful send
    return true;
}

/**
 * Send course assignment notification to a user
 * 
 * @param int $user_id The ID of the user to notify
 * @param int $application_id The ID of the application
 * @param string $program_name The name of the assigned program
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendCourseAssignmentNotification($user_id, $application_id, $program_name) {
    $title = "Course Assignment Notification";
    $message = "Congratulations! You have been assigned to the $program_name program. Log in to view your admission details.";
    
    $additional_data = [
        'application_id' => $application_id,
        'program_name' => $program_name,
        'action_url' => 'admission-letter.php'
    ];
    
    return sendNotification($user_id, $title, $message, 'course_assignment', $additional_data);
}

/**
 * Register a device token for push notifications
 * 
 * @param int $user_id The ID of the user
 * @param string $device_token The device token
 * @param string $device_type The device type (android, ios, web)
 * @return bool True if token was registered successfully, false otherwise
 */
function registerDeviceToken($user_id, $device_token, $device_type) {
    global $conn;
    
    // Check if token already exists
    $sql = "SELECT id FROM device_tokens WHERE user_id = ? AND device_token = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $device_token);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                // Token exists, update it
                $sql = "UPDATE device_tokens SET device_type = ?, is_active = TRUE, last_used = NOW() WHERE user_id = ? AND device_token = ?";
                
                if($update_stmt = $conn->prepare($sql)) {
                    $update_stmt->bind_param("sis", $device_type, $user_id, $device_token);
                    $success = $update_stmt->execute();
                    $update_stmt->close();
                    return $success;
                }
            } else {
                // Token doesn't exist, insert it
                $sql = "INSERT INTO device_tokens (user_id, device_token, device_type) VALUES (?, ?, ?)";
                
                if($insert_stmt = $conn->prepare($sql)) {
                    $insert_stmt->bind_param("iss", $user_id, $device_token, $device_type);
                    $success = $insert_stmt->execute();
                    $insert_stmt->close();
                    return $success;
                }
            }
        }
        
        $stmt->close();
    }
    
    return false;
}
?>

