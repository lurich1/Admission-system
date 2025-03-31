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

// Check if application already exists
$application_exists = false;
$application_data = [];
$application_status = "Not Started";

$sql = "SELECT * FROM applications WHERE user_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_user_id);
    $param_user_id = $user_id;
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $application_exists = true;
            $application_data = $result->fetch_assoc();
            $application_status = $application_data['status'];
        }
    }
    
    $stmt->close();
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $nationality = trim($_POST['nationality']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $emergency_contact_relationship = trim($_POST['emergency_contact_relationship']);
    $high_school = trim($_POST['high_school']);
    $high_school_graduation_year = trim($_POST['high_school_graduation_year']);
    $high_school_gpa = trim($_POST['high_school_gpa']);
    $program_first_choice = trim($_POST['program_first_choice']);
    $program_second_choice = trim($_POST['program_second_choice']);
    $program_third_choice = trim($_POST['program_third_choice']);
    
    // Basic validation
    if(empty($first_name) || empty($last_name) || empty($dob) || empty($gender) || 
       empty($nationality) || empty($address) || empty($city) || empty($country) || 
       empty($phone) || empty($email) || empty($high_school) || 
       empty($high_school_graduation_year) || empty($program_first_choice)) {
        $error = "Please fill all required fields";
    } else {
        if($application_exists) {
            // Update existing application
            $sql = "UPDATE applications SET 
                    first_name = ?, last_name = ?, dob = ?, gender = ?, nationality = ?, 
                    address = ?, city = ?, state = ?, postal_code = ?, country = ?, 
                    phone = ?, email = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                    emergency_contact_relationship = ?, high_school = ?, high_school_graduation_year = ?, 
                    high_school_gpa = ?, program_first_choice = ?, program_second_choice = ?, 
                    program_third_choice = ?, status = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            
            if($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssssssssssssssssssssi", 
                    $first_name, $last_name, $dob, $gender, $nationality, 
                    $address, $city, $state, $postal_code, $country, 
                    $phone, $email, $emergency_contact_name, $emergency_contact_phone, 
                    $emergency_contact_relationship, $high_school, $high_school_graduation_year, 
                    $high_school_gpa, $program_first_choice, $program_second_choice, 
                    $program_third_choice, $param_status, $user_id);
                
                $param_status = "In Progress";
                
                if($stmt->execute()) {
                    $success = "Application updated successfully!";
                    $application_status = "In Progress";
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
                
                $stmt->close();
            }
        } else {
            // Create new application
            $sql = "INSERT INTO applications (user_id, first_name, last_name, dob, gender, nationality, 
                    address, city, state, postal_code, country, phone, email, emergency_contact_name, 
                    emergency_contact_phone, emergency_contact_relationship, high_school, 
                    high_school_graduation_year, high_school_gpa, program_first_choice, 
                    program_second_choice, program_third_choice, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            if($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isssssssssssssssssssss", 
                    $user_id, $first_name, $last_name, $dob, $gender, $nationality, 
                    $address, $city, $state, $postal_code, $country, 
                    $phone, $email, $emergency_contact_name, $emergency_contact_phone, 
                    $emergency_contact_relationship, $high_school, $high_school_graduation_year, 
                    $high_school_gpa, $program_first_choice, $program_second_choice, 
                    $program_third_choice, $param_status);
                
                $param_status = "In Progress";
                
                if($stmt->execute()) {
                    $success = "Application created successfully!";
                    $application_exists = true;
                    $application_status = "In Progress";
                    
                    // Get the newly created application data
                    $sql = "SELECT * FROM applications WHERE user_id = ?";
                    if($stmt2 = $conn->prepare($sql)) {
                        $stmt2->bind_param("i", $user_id);
                        
                        if($stmt2->execute()) {
                            $result = $stmt2->get_result();
                            
                            if($result->num_rows > 0) {
                                $application_data = $result->fetch_assoc();
                            }
                        }
                        
                        $stmt2->close();
                    }
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
                
                $stmt->close();
            }
        }
    }
}

// Get available programs
$programs = [];
$sql = "SELECT * FROM programs ORDER BY name";
if($result = $conn->query($sql)) {
    while($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - University Admission Portal</title>
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
                <h2>Application Form</h2>
                <p>Please fill out all required fields in the application form</p>
            </section>

            <section class="application-section">
                <div class="application-container">
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
                            <p>You cannot edit your application at this stage. If you need to make changes, please contact the admissions office.</p>
                            <a href="dashboard.php" class="btn primary-btn">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="application-form">
                            <div class="form-section">
                                <h3>Personal Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" value="<?php echo isset($application_data['first_name']) ? htmlspecialchars($application_data['first_name']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" value="<?php echo isset($application_data['last_name']) ? htmlspecialchars($application_data['last_name']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="dob">Date of Birth *</label>
                                        <input type="date" id="dob" name="dob" value="<?php echo isset($application_data['dob']) ? htmlspecialchars($application_data['dob']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender *</label>
                                        <select id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo (isset($application_data['gender']) && $application_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($application_data['gender']) && $application_data['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo (isset($application_data['gender']) && $application_data['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="nationality">Nationality *</label>
                                    <input type="text" id="nationality" name="nationality" value="<?php echo isset($application_data['nationality']) ? htmlspecialchars($application_data['nationality']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Contact Information</h3>
                                <div class="form-group">
                                    <label for="address">Address *</label>
                                    <input type="text" id="address" name="address" value="<?php echo isset($application_data['address']) ? htmlspecialchars($application_data['address']) : ''; ?>" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City *</label>
                                        <input type="text" id="city" name="city" value="<?php echo isset($application_data['city']) ? htmlspecialchars($application_data['city']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="state">State/Province</label>
                                        <input type="text" id="state" name="state" value="<?php echo isset($application_data['state']) ? htmlspecialchars($application_data['state']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code</label>
                                        <input type="text" id="postal_code" name="postal_code" value="<?php echo isset($application_data['postal_code']) ? htmlspecialchars($application_data['postal_code']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="country">Country *</label>
                                        <input type="text" id="country" name="country" value="<?php echo isset($application_data['country']) ? htmlspecialchars($application_data['country']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone">Phone Number *</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo isset($application_data['phone']) ? htmlspecialchars($application_data['phone']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" id="email" name="email" value="<?php echo isset($application_data['email']) ? htmlspecialchars($application_data['email']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Emergency Contact</h3>
                                <div class="form-group">
                                    <label for="emergency_contact_name">Name</label>
                                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo isset($application_data['emergency_contact_name']) ? htmlspecialchars($application_data['emergency_contact_name']) : ''; ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="emergency_contact_phone">Phone Number</label>
                                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo isset($application_data['emergency_contact_phone']) ? htmlspecialchars($application_data['emergency_contact_phone']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency_contact_relationship">Relationship</label>
                                        <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" value="<?php echo isset($application_data['emergency_contact_relationship']) ? htmlspecialchars($application_data['emergency_contact_relationship']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Educational Background</h3>
                                <div class="form-group">
                                    <label for="high_school">High School *</label>
                                    <input type="text" id="high_school" name="high_school" value="<?php echo isset($application_data['high_school']) ? htmlspecialchars($application_data['high_school']) : ''; ?>" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="high_school_graduation_year">Graduation Year *</label>
                                        <input type="number" id="high_school_graduation_year" name="high_school_graduation_year" min="1990" max="2025" value="<?php echo isset($application_data['high_school_graduation_year']) ? htmlspecialchars($application_data['high_school_graduation_year']) : ''; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="high_school_gpa">GPA</label>
                                        <input type="text" id="high_school_gpa" name="high_school_gpa" value="<?php echo isset($application_data['high_school_gpa']) ? htmlspecialchars($application_data['high_school_gpa']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Program Selection</h3>
                                <div class="form-group">
                                    <label for="program_first_choice">First Choice *</label>
                                    <select id="program_first_choice" name="program_first_choice" required>
                                        <option value="">Select Program</option>
                                        <?php foreach($programs as $program): ?>
                                            <option value="<?php echo htmlspecialchars($program['id']); ?>" <?php echo (isset($application_data['program_first_choice']) && $application_data['program_first_choice'] == $program['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($program['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="program_second_choice">Second Choice</label>
                                    <select id="program_second_choice" name="program_second_choice">
                                        <option value="">Select Program</option>
                                        <?php foreach($programs as $program): ?>
                                            <option value="<?php echo htmlspecialchars($program['id']); ?>" <?php echo (isset($application_data['program_second_choice']) && $application_data['program_second_choice'] == $program['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($program['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="program_third_choice">Third Choice</label>
                                    <select id="program_third_choice" name="program_third_choice">
                                        <option value="">Select Program</option>
                                        <?php foreach($programs as $program): ?>
                                            <option value="<?php echo htmlspecialchars($program['id']); ?>" <?php echo (isset($application_data['program_third_choice']) && $application_data['program_third_choice'] == $program['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($program['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">Save Application</button>
                                <a href="documents.php" class="btn secondary-btn">Next: Upload Documents</a>
                            </div>
                        </form>
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

