<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Include required files
require_once '../config/database.php';
require_once '../includes/notification-system.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if(!isset($data['device_token']) || !isset($data['device_type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$user_id = $_SESSION["user_id"];
$device_token = $data['device_token'];
$device_type = $data['device_type'];

// Validate device type
if(!in_array($device_type, ['android', 'ios', 'web'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid device type']);
    exit;
}

// Register device token
$success = registerDeviceToken($user_id, $device_token, $device_type);

if($success) {
    echo json_encode(['success' => true, 'message' => 'Device registered successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to register device']);
}
?>

