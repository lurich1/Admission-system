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

// Get application details
$sql = "SELECT a.*, p1.name as program_first_choice_name, 
        p2.name as program_second_choice_name, 
        p3.name as program_third_choice_name,
        u.email as user_email
        FROM applications a 
        LEFT JOIN programs p1 ON a.program_first_choice = p1.id 
        LEFT JOIN programs p2 ON a.program_second_choice = p2.id 
        LEFT JOIN programs p3 ON a.program_third_choice = p3.id
        LEFT JOIN users u ON a.user_id = u.id
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

// Get documents
$documents = [];
$sql = "SELECT * FROM documents WHERE application_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $application_id);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $documents[$row['document_type']] = $row;
        }
    }
    
    $stmt->close();
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
    <title>View Application - University Admission System</title>
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
                    <h2>Application Details</h2>
                    <div class="header-actions">
                        <a href="applications.php" class="btn secondary-btn"><i class="fas fa-arrow-left"></i> Back to List</a>
                        <a href="process-application.php?id=<?php echo $application_id; ?>" class="btn primary-btn"><i class="fas fa-cog"></i> Process Application</a>
                    </div>
                </div>
                
                <div class="application-status-bar">
                    <div class="status-info">
                        <span class="status-label">Status:</span>
                        <span class="status-badge <?php echo strtolower($application['status']); ?>"><?php echo $application['status']; ?></span>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Application ID:</span>
                        <span class="status-value">#<?php echo $application_id; ?></span>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Submitted:</span>
                        <span class="status-value"><?php echo date('F d, Y', strtotime($application['created_at'])); ?></span>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Last Updated:</span>
                        <span class="status-value"><?php echo date('F d, Y', strtotime($application['updated_at'])); ?></span>
                    </div>
                </div>
                
                <div class="application-details">
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="section-content">
                            <div class="detail-row">
                                <div class="detail-group">
                                    <span class="detail-label">Full Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Date of Birth</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['dob']); ?></span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-group">
                                    <span class="detail-label">Gender</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['gender']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Nationality</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['nationality']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-envelope"></i> Contact Information</h3>
                        </div>
                        <div class="section-content">
                            <div class="detail-row">
                                <div class="detail-group">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['email']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['phone']); ?></span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-group full-width">
                                    <span class="detail-label">Address</span>
                                    <span class="detail-value">
                                        <?php echo htmlspecialchars($application['address']); ?><br>
                                        <?php echo htmlspecialchars($application['city'] . ', ' . $application['state'] . ' ' . $application['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($application['country']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-graduation-cap"></i> Educational Background</h3>
                        </div>
                        <div class="section-content">
                            <div class="detail-row">
                                <div class="detail-group">
                                    <span class="detail-label">High School</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['high_school']); ?></span>
                                </div>
                                <div class="detail-group">
                                    <span class="detail-label">Graduation Year</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['high_school_graduation_year']); ?></span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-group">
                                    <span class="detail-label">GPA</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['high_school_gpa']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-list-alt"></i> Program Preferences</h3>
                        </div>
                        <div class="section-content">
                            <div class="detail-row">
                                <div class="detail-group full-width">
                                    <span class="detail-label">First Choice</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['program_first_choice_name']); ?></span>
                                </div>
                            </div>
                            <?php if(!empty($application['program_second_choice'])): ?>
                            <div class="detail-row">
                                <div class="detail-group full-width">
                                    <span class="detail-label">Second Choice</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['program_second_choice_name']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($application['program_third_choice'])): ?>
                            <div class="detail-row">
                                <div class="detail-group full-width">
                                    <span class="detail-label">Third Choice</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($application['program_third_choice_name']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-file-alt"></i> Documents</h3>
                        </div>
                        <div class="section-content">
                            <div class="documents-grid">
                                <?php 
                                $document_types = [
                                    'id_card' => 'ID Card/Passport',
                                    'high_school_transcript' => 'High School Transcript',
                                    'birth_certificate' => 'Birth Certificate',
                                    'passport_photo' => 'Passport Photo',
                                    'recommendation_letter' => 'Recommendation Letter',
                                    'personal_statement' => 'Personal Statement'
                                ];
                                
                                foreach($document_types as $type => $label):
                                ?>
                                <div class="document-card <?php echo isset($documents[$type]) ? 'has-document' : 'no-document'; ?>">
                                    <div class="document-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="document-info">
                                        <h4><?php echo $label; ?></h4>
                                        <?php if(isset($documents[$type])): ?>
                                        <p><?php echo htmlspecialchars($documents[$type]['file_name']); ?></p>
                                        <a href="../<?php echo htmlspecialchars($documents[$type]['file_path']); ?>" target="_blank" class="btn small-btn">View Document</a>
                                        <?php else: ?>
                                        <p class="not-submitted">Not submitted</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($ai_recommendations): ?>
                    <div class="details-section ai-section">
                        <div class="section-header">
                            <h3><i class="fas fa-robot"></i> AI Recommendations</h3>
                            <span class="ai-timestamp">Generated on <?php echo date('F d, Y', strtotime($ai_recommendations['created_at'])); ?></span>
                        </div>
                        <div class="section-content">
                            <div class="ai-recommendation-card">
                                <div class="recommendation-header">
                                    <h4>Program Recommendation</h4>
                                    <div class="confidence-meter">
                                        <span class="confidence-label">Confidence:</span>
                                        <div class="confidence-bar">
                                            <div class="confidence-level" style="width: <?php echo $ai_recommendations['confidence_score']; ?>%"></div>
                                        </div>
                                        <span class="confidence-percentage"><?php echo $ai_recommendations['confidence_score']; ?>%</span>
                                    </div>
                                </div>
                                <div class="recommended-programs">
                                    <div class="program-match primary-match">
                                        <div class="match-percentage"><?php echo $ai_recommendations['primary_match_score']; ?>%</div>
                                        <div class="match-details">
                                            <h5><?php echo htmlspecialchars($ai_recommendations['primary_program']); ?></h5>
                                            <p>Primary Recommendation</p>
                                        </div>
                                    </div>
                                    <?php if(!empty($ai_recommendations['alternate_program'])): ?>
                                    <div class="program-match alternate-match">
                                        <div class="match-percentage"><?php echo $ai_recommendations['alternate_match_score']; ?>%</div>
                                        <div class="match-details">
                                            <h5><?php echo htmlspecialchars($ai_recommendations['alternate_program']); ?></h5>
                                            <p>Alternate Recommendation</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="recommendation-analysis">
                                    <h4>Analysis</h4>
                                    <p><?php echo nl2br(htmlspecialchars($ai_recommendations['analysis'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="details-section">
                        <div class="section-header">
                            <h3><i class="fas fa-comments"></i> Admin Notes</h3>
                        </div>
                        <div class="section-content">
                            <div class="admin-notes">
                                <textarea id="admin-notes" placeholder="Add notes about this application..."><?php echo isset($application['admin_notes']) ? htmlspecialchars($application['admin_notes']) : ''; ?></textarea>
                                <button id="save-notes" class="btn primary-btn">Save Notes</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="action-footer">
                    <a href="email-applicant.php?id=<?php echo $application_id; ?>" class="btn secondary-btn"><i class="fas fa-envelope"></i> Email Applicant</a>
                    <div class="decision-buttons">
                        <button id="reject-btn" class="btn reject-btn"><i class="fas fa-times"></i> Reject</button>
                        <button id="approve-btn" class="btn approve-btn"><i class="fas fa-check"></i> Approve</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Save admin notes
        document.getElementById('save-notes').addEventListener('click', function() {
            const notes = document.getElementById('admin-notes').value;
            
            fetch('save-notes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: <?php echo $application_id; ?>,
                    notes: notes
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notes saved successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving notes');
            });
        });
        
        // Approve application
        document.getElementById('approve-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to approve this application?')) {
                updateApplicationStatus('Approved');
            }
        });
        
        // Reject application
        document.getElementById('reject-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to reject this application?')) {
                updateApplicationStatus('Rejected');
            }
        });
        
        function updateApplicationStatus(status) {
            fetch('update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: <?php echo $application_id; ?>,
                    status: status
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Application ${status.toLowerCase()} successfully`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating status');
            });
        }
    </script>
</body>
</html>

