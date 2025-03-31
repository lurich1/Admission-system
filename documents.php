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
$error = '';
$success = '';

// Check if application exists
$application_exists = false;
$application_id = null;
$application_status = "Not Started";

$sql = "SELECT * FROM applications WHERE user_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_user_id);
    $param_user_id = $user_id;
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $application_exists = true;
            $application = $result->fetch_assoc();
            $application_id = $application['id'];
            $application_status = $application['status'];
        }
    }
    
    $stmt->close();
}

// If no application exists, redirect to application form
if(!$application_exists) {
    header("location: application.php");
    exit;
}

// Get existing documents
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

// Process document upload
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_type = $_POST['document_type'];
    
    // Check if file was uploaded without errors
    if(isset($_FILES["document"]) && $_FILES["document"]["error"] == 0) {
        $allowed = ["pdf" => "application/pdf", "doc" => "application/msword", "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["document"]["name"];
        $filetype = $_FILES["document"]["type"];
        $filesize = $_FILES["document"]["size"];
        
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $error = "Error: Please select a valid file format (PDF, DOC, DOCX, JPG, JPEG, PNG).";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $error = "Error: File size is larger than the allowed limit (5MB).";
        }
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed)) {
            // Check if document already exists
            $document_exists = isset($documents[$document_type]);
            
            // Create uploads directory if it doesn't exist
            if(!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Create unique filename
            $new_filename = uniqid() . "-" . $filename;
            $upload_path = "uploads/" . $new_filename;
            
            // Upload file
            if(move_uploaded_file($_FILES["document"]["tmp_name"], $upload_path)) {
                if($document_exists) {
                    // Update existing document
                    $sql = "UPDATE documents SET file_name = ?, file_path = ?, updated_at = NOW() WHERE application_id = ? AND document_type = ?";
                    
                    if($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("ssis", $filename, $upload_path, $application_id, $document_type);
                        
                        if  $upload_path, $application_id, $document_type);
                        
                        if($stmt->execute()) {
                            $success = "Document updated successfully!";
                            
                            // Update documents array
                            $documents[$document_type]['file_name'] = $filename;
                            $documents[$document_type]['file_path'] = $upload_path;
                        } else {
                            $error = "Error: Failed to update document.";
                        }
                        
                        $stmt->close();
                    }
                } else {
                    // Insert new document
                    $sql = "INSERT INTO documents (application_id, document_type, file_name, file_path, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                    
                    if($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("isss", $application_id, $document_type, $filename, $upload_path);
                        
                        if($stmt->execute()) {
                            $success = "Document uploaded successfully!";
                            
                            // Add to documents array
                            $documents[$document_type] = [
                                'document_type' => $document_type,
                                'file_name' => $filename,
                                'file_path' => $upload_path
                            ];
                        } else {
                            $error = "Error: Failed to upload document.";
                        }
                        
                        $stmt->close();
                    }
                }
            } else {
                $error = "Error: There was a problem uploading your file. Please try again.";
            }
        } else {
            $error = "Error: There was a problem with the file type. Please try again.";
        }
    } else {
        $error = "Error: " . $_FILES["document"]["error"];
    }
}

// Process application submission
if(isset($_POST['submit_application']) && $_POST['submit_application'] == 1) {
    // Check if all required documents are uploaded
    $required_documents = ['id_card', 'high_school_transcript', 'birth_certificate'];
    $missing_documents = [];
    
    foreach($required_documents as $doc) {
        if(!isset($documents[$doc])) {
            $missing_documents[] = str_replace('_', ' ', ucfirst($doc));
        }
    }
    
    if(!empty($missing_documents)) {
        $error = "Please upload the following required documents before submitting: " . implode(', ', $missing_documents);
    } else {
        // Update application status to Submitted
        $sql = "UPDATE applications SET status = 'Submitted', updated_at = NOW() WHERE id = ?";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $application_id);
            
            if($stmt->execute()) {
                $success = "Application submitted successfully!";
                $application_status = "Submitted";
            } else {
                $error = "Error: Failed to submit application.";
            }
            
            $stmt->close();
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
    <title>Upload Documents - University Admission Portal</title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="page-header">
                <h2>Upload Documents</h2>
                <p>Please upload all required documents for your application</p>
            </section>

            <section class="documents-section">
                <div class="documents-container">
                    <?php if(!empty($error)): ?>
                        <div class="error-message">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($success)): ?>
                        <div class="success-message">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($application_status == "Submitted" || $application_status == "Approved" || $application_status == "Rejected"): ?>
                        <div class="application-status-message">
                            <h3>Application Status: <?php echo $application_status; ?></h3>
                            <p>Your application has been submitted and is currently <?php echo strtolower($application_status); ?>.</p>
                            <p>You cannot upload or modify documents at this stage. If you need to make changes, please contact the admissions office.</p>
                            <a href="dashboard.php" class="btn primary-btn">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <div class="documents-info">
                            <h3>Document Requirements</h3>
                            <p>Please upload the following documents in PDF, DOC, DOCX, JPG, JPEG, or PNG format. Maximum file size is 5MB.</p>
                            <ul class="document-requirements">
                                <li><strong>ID Card/Passport</strong> - A valid government-issued ID or passport</li>
                                <li><strong>High School Transcript</strong> - Official high school academic records</li>
                                <li><strong>Birth Certificate</strong> - Official birth certificate</li>
                                <li><strong>Passport Photo</strong> - Recent passport-sized photograph</li>
                                <li><strong>Recommendation Letter</strong> - Letter of recommendation from a teacher or counselor (optional)</li>
                                <li><strong>Personal Statement</strong> - A brief statement about your academic goals (optional)</li>
                            </ul>
                        </div>
                        
                        <div class="document-upload-form">
                            <h3>Upload Document</h3>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="document_type">Document Type *</label>
                                    <select id="document_type" name="document_type" required>
                                        <option value="">Select Document Type</option>
                                        <option value="id_card">ID Card/Passport</option>
                                        <option value="high_school_transcript">High School Transcript</option>
                                        <option value="birth_certificate">Birth Certificate</option>
                                        <option value="passport_photo">Passport Photo</option>
                                        <option value="recommendation_letter">Recommendation Letter</option>
                                        <option value="personal_statement">Personal Statement</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="document">Select File *</label>
                                    <input type="file" id="document" name="document" required>
                                </div>
                                <button type="submit" class="btn primary-btn">Upload Document</button>
                            </form>
                        </div>
                        
                        <div class="uploaded-documents">
                            <h3>Uploaded Documents</h3>
                            <?php if(empty($documents)): ?>
                                <p>No documents uploaded yet.</p>
                            <?php else: ?>
                                <table class="documents-table">
                                    <thead>
                                        <tr>
                                            <th>Document Type</th>
                                            <th>File Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($documents as $doc): ?>
                                            <tr>
                                                <td><?php echo str_replace('_', ' ', ucfirst($doc['document_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn small-btn">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <a href="application.php" class="btn secondary-btn">Back to Application</a>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="submit_application" value="1">
                                <button type="submit" class="btn primary-btn">Submit Application</button>
                            </form>
                        </div>
                    <?php endif; ?>
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

