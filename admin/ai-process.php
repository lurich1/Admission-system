<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_id"])) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

$success_message = '';
$error_message = '';

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_ids = isset($_POST['application_ids']) ? $_POST['application_ids'] : [];
    
    if(empty($application_ids)) {
        $error_message = "Please select at least one application to process";
    } else {
        // Process selected applications
        $processed_count = 0;
        $failed_count = 0;
        
        foreach($application_ids as $app_id) {
            // Call AI processing function
            $result = processApplicationWithAI($app_id, $conn);
            
            if($result) {
                $processed_count++;
            } else {
                $failed_count++;
            }
        }
        
        if($processed_count > 0) {
            $success_message = "Successfully processed $processed_count application(s)";
            if($failed_count > 0) {
                $success_message .= ", $failed_count application(s) failed";
            }
        } else {
            $error_message = "Failed to process applications";
        }
    }
}

// Get applications that can be processed (status = Submitted)
$applications = [];
$sql = "SELECT a.id, a.first_name, a.last_name, a.email, a.status, a.created_at, p.name as program 
        FROM applications a 
        JOIN programs p ON a.program_first_choice = p.id 
        WHERE a.status = 'Submitted' 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Function to process application with AI
function processApplicationWithAI($application_id, $conn) {
    // Get application details
    $sql = "SELECT a.*, p1.name as program_first_choice_name, 
            p2.name as program_second_choice_name, 
            p3.name as program_third_choice_name
            FROM applications a 
            LEFT JOIN programs p1 ON a.program_first_choice = p1.id 
            LEFT JOIN programs p2 ON a.program_second_choice = p2.id 
            LEFT JOIN programs p3 ON a.program_third_choice = p3.id
            WHERE a.id = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $application_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            
            if($result->num_rows == 1) {
                $application = $result->fetch_assoc();
                
                // Get all available programs
                $programs = [];
                $sql = "SELECT * FROM programs";
                $program_result = $conn->query($sql);
                while($row = $program_result->fetch_assoc()) {
                    $programs[] = $row;
                }
                
                // Simulate AI analysis
                // In a real system, this would call an AI service or model
                $primary_program = $application['program_first_choice_name'];
                $primary_match_score = rand(75, 95);
                
                // Find an alternate program
                $alternate_programs = array_filter($programs, function($p) use ($application) {
                    return $p['id'] != $application['program_first_choice'];
                });
                
                if(!empty($alternate_programs)) {
                    $alternate_program = $alternate_programs[array_rand($alternate_programs)]['name'];
                    $alternate_match_score = rand(60, 85);
                } else {
                    $alternate_program = '';
                    $alternate_match_score = 0;
                }
                
                $confidence_score = rand(80, 98);
                
                // Generate analysis text
                $analysis = "Based on the applicant's academic background and profile, they show strong aptitude for {$primary_program}. ";
                $analysis .= "Their high school GPA of {$application['high_school_gpa']} indicates good academic performance. ";
                
                if(!empty($alternate_program)) {
                    $analysis .= "As an alternative, {$alternate_program} could also be a good fit based on their qualifications. ";
                }
                
                $analysis .= "\n\nRecommendation: The applicant should be considered for admission to {$primary_program}.";
                
                // Check if recommendation already exists
                $sql = "SELECT id FROM ai_recommendations WHERE application_id = ?";
                if($stmt2 = $conn->prepare($sql)) {
                    $stmt2->bind_param("i", $application_id);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    
                    if($result2->num_rows > 0) {
                        // Update existing recommendation
                        $sql = "UPDATE ai_recommendations SET 
                                primary_program = ?, 
                                primary_match_score = ?, 
                                alternate_program = ?, 
                                alternate_match_score = ?, 
                                confidence_score = ?, 
                                analysis = ?, 
                                updated_at = NOW() 
                                WHERE application_id = ?";
                        
                        if($stmt3 = $conn->prepare($sql)) {
                            $stmt3->bind_param("sisissi", 
                                $primary_program, 
                                $primary_match_score, 
                                $alternate_program, 
                                $alternate_match_score, 
                                $confidence_score, 
                                $analysis, 
                                $application_id
                            );
                            
                            $success = $stmt3->execute();
                            $stmt3->close();
                            return $success;
                        }
                    } else {
                        // Insert new recommendation
                        $sql = "INSERT INTO ai_recommendations (
                                application_id, 
                                primary_program, 
                                primary_match_score, 
                                alternate_program, 
                                alternate_match_score, 
                                confidence_score, 
                                analysis, 
                                created_at, 
                                updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                        
                        if($stmt3 = $conn->prepare($sql)) {
                            $stmt3->bind_param("isisiss", 
                                $application_id, 
                                $primary_program, 
                                $primary_match_score, 
                                $alternate_program, 
                                $alternate_match_score, 
                                $confidence_score, 
                                $analysis
                            );
                            
                            $success = $stmt3->execute();
                            $stmt3->close();
                            return $success;
                        }
                    }
                    
                    $stmt2->close();
                }
            }
        }
        
        $stmt->close();
    }
    
    return false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Processing - University Admission System</title>
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
                    <h2>AI Course Matching</h2>
                    <div class="header-actions">
                        <a href="applications.php" class="btn secondary-btn"><i class="fas fa-arrow-left"></i> Back to Applications</a>
                    </div>
                </div>
                
                <div class="ai-process-section">
                    <div class="ai-info-card">
                        <div class="ai-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="ai-info">
                            <h3>AI Course Matching Engine</h3>
                            <p>The AI engine analyzes applicant data and recommends the most suitable academic programs based on their qualifications, academic history, and preferences.</p>
                            <div class="ai-features">
                                <div class="ai-feature">
                                    <i class="fas fa-bolt"></i>
                                    <span>Fast Processing</span>
                                </div>
                                <div class="ai-feature">
                                    <i class="fas fa-chart-line"></i>
                                    <span>High Accuracy</span>
                                </div>
                                <div class="ai-feature">
                                    <i class="fas fa-brain"></i>
                                    <span>Smart Analysis</span>
                                </div>
                                <div class="ai-feature">
                                    <i class="fas fa-sync-alt"></i>
                                    <span>Continuous Learning</span>
                                </div>
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
                    
                    <div class="ai-process-form">
                        <h3>Process Applications</h3>
                        <p>Select applications to process with the AI matching engine:</p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="application-selection">
                                <?php if(empty($applications)): ?>
                                <div class="no-applications">
                                    <p>No applications available for processing. Only submitted applications can be processed.</p>
                                </div>
                                <?php else: ?>
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all"></th>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Program Choice</th>
                                            <th>Submission Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($applications as $app): ?>
                                        <tr>
                                            <td><input type="checkbox" name="application_ids[]" value="<?php echo $app['id']; ?>" class="select-item"></td>
                                            <td>#<?php echo $app['id']; ?></td>
                                            <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                                            <td><?php echo htmlspecialchars($app['program']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn primary-btn"><i class="fas fa-play"></i> Process Selected Applications</button>
                                    <button type="submit" name="process_all" value="1" class="btn secondary-btn"><i class="fas fa-play-circle"></i> Process All Applications</button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <div class="ai-settings-card">
                        <h3>AI Engine Settings</h3>
                        <div class="settings-form">
                            <div class="form-group">
                                <label for="confidence_threshold">Confidence Threshold (%)</label>
                                <input type="number" id="confidence_threshold" name="confidence_threshold" min="50" max="100" value="75">
                            </div>
                            <div class="form-group">
                                <label for="matching_algorithm">Matching Algorithm</label>
                                <select id="matching_algorithm" name="matching_algorithm">
                                    <option value="neural_network">Neural Network (Recommended)</option>
                                    <option value="decision_tree">Decision Tree</option>
                                    <option value="random_forest">Random Forest</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="auto_approve">Auto-Approve Recommendations</label>
                                <div class="toggle-switch">
                                    <input type="checkbox" id="auto_approve" name="auto_approve">
                                    <label for="auto_approve"></label>
                                </div>
                                <span class="setting-hint">Automatically approve applications if confidence is above threshold</span>
                            </div>
                            <div class="form-group">
                                <label for="email_notifications">Send Email Notifications</label>
                                <div class="toggle-switch">
                                    <input type="checkbox" id="email_notifications" name="email_notifications" checked>
                                    <label for="email_notifications"></label>
                                </div>
                                <span class="setting-hint">Send email notifications to applicants after processing</span>
                            </div>
                            <button type="button" id="save-settings" class="btn primary-btn">Save Settings</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Save settings
        document.getElementById('save-settings').addEventListener('click', function() {
            const confidenceThreshold = document.getElementById('confidence_threshold').value;
            const matchingAlgorithm = document.getElementById('matching_algorithm').value;
            const autoApprove = document.getElementById('auto_approve').checked;
            const emailNotifications = document.getElementById('email_notifications').checked;
            
            // In a real application, this would save to the database
            alert('Settings saved successfully');
        });
    </script>
</body>
</html>

