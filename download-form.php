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

// Check if application exists
$application_exists = false;
$application_data = [];

$sql = "SELECT * FROM applications WHERE user_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_user_id);
    $param_user_id = $user_id;
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $application_exists = true;
            $application_data = $result->fetch_assoc();
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
    <title>Download Form - University Admission Portal</title>
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
                <h2>Download Application Form</h2>
                <p>Download and print your application form</p>
            </section>

            <section class="download-section">
                <div class="download-container">
                    <?php if(!$application_exists): ?>
                        <div class="no-application-message">
                            <h3>No Application Found</h3>
                            <p>You need to start your application before you can download the form.</p>
                            <a href="application.php" class="btn primary-btn">Start Application</a>
                        </div>
                    <?php else: ?>
                        <div class="form-preview">
                            <h3>Application Form Preview</h3>
                            <div class="preview-content">
                                <div class="preview-header">
                                    <img src="/placeholder.svg?height=80&width=80" alt="University Logo">
                                    <div class="preview-title">
                                        <h2>University of Energy and Natural Resources</h2>
                                        <h3>Application for Admission</h3>
                                    </div>
                                </div>
                                
                                <div class="preview-section">
                                    <h4>Personal Information</h4>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Full Name:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['first_name'] . ' ' . $application_data['last_name']); ?></span>
                                        </div>
                                        <div class="preview-field">
                                            <span class="field-label">Date of Birth:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['dob']); ?></span>
                                        </div>
                                    </div>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Gender:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['gender']); ?></span>
                                        </div>
                                        <div class="preview-field">
                                            <span class="field-label">Nationality:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['nationality']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-section">
                                    <h4>Contact Information</h4>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Address:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['address']); ?></span>
                                        </div>
                                    </div>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">City:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['city']); ?></span>
                                        </div>
                                        <div class="preview-field">
                                            <span class="field-label">Country:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['country']); ?></span>
                                        </div>
                                    </div>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Phone:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['phone']); ?></span>
                                        </div>
                                        <div class="preview-field">
                                            <span class="field-label">Email:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['email']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-section">
                                    <h4>Educational Background</h4>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">High School:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['high_school']); ?></span>
                                        </div>
                                        <div class="preview-field">
                                            <span class="field-label">Graduation Year:</span>
                                            <span class="field-value"><?php echo htmlspecialchars($application_data['high_school_graduation_year']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="preview-section">
                                    <h4>Program Selection</h4>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">First Choice:</span>
                                            <span class="field-value">Program #<?php echo htmlspecialchars($application_data['program_first_choice']); ?></span>
                                        </div>
                                    </div>
                                    <?php if(!empty($application_data['program_second_choice'])): ?>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Second Choice:</span>
                                            <span class="field-value">Program #<?php echo htmlspecialchars($application_data['program_second_choice']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if(!empty($application_data['program_third_choice'])): ?>
                                    <div class="preview-row">
                                        <div class="preview-field">
                                            <span class="field-label">Third Choice:</span>
                                            <span class="field-value">Program #<?php echo htmlspecialchars($application_data['program_third_choice']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="preview-section">
                                    <h4>Declaration</h4>
                                    <p>I hereby declare that the information provided in this application is true and correct to the best of my knowledge. I understand that any false or misleading information may result in the rejection of my application or dismissal if admitted.</p>
                                    <div class="signature-section">
                                        <div class="signature-line">
                                            <span>Signature: _________________________</span>
                                        </div>
                                        <div class="signature-line">
                                            <span>Date: _________________________</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="download-actions">
                            <button id="printBtn" class="btn secondary-btn"><i class="fas fa-print"></i> Print Form</button>
                            <button id="downloadBtn" class="btn primary-btn"><i class="fas fa-download"></i> Download PDF</button>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.getElementById('printBtn').addEventListener('click', function() {
            window.print();
        });
        
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const element = document.querySelector('.preview-content');
            const opt = {
                margin: 1,
                filename: 'admission-application-form.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        });
    </script>
</body>
</html>

