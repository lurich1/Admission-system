<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Process registration form
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
    
    // Validate name
    if(empty($full_name)) {
        $error = "Please enter your full name";
    }
    
    // Validate email
    if(empty($email)) {
        $error = "Please enter your email";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if($stmt->execute()) {
                $stmt->store_result();
                
                if($stmt->num_rows > 0) {
                    $error = "This email is already registered";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    // Validate password
    if(empty($password)) {
        $error = "Please enter a password";
    } elseif(strlen($password) < 6) {
        $error = "Password must have at least 6 characters";
    }
    
    // Validate confirm password
    if(empty($confirm_password)) {
        $error = "Please confirm your password";
    } else {
        if($password != $confirm_password) {
            $error = "Passwords do not match";
        }
    }
    
    // Validate phone
    if(empty($phone)) {
        $error = "Please enter your phone number";
    }
    
    // If no errors, proceed with registration
    if(empty($error)) {
        // Prepare an insert statement
        $sql = "INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)";
        
        if($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssss", $param_full_name, $param_email, $param_password, $param_phone);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_phone = $phone;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Registration successful
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - University Admission Portal</title>
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
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="active">Register</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="auth-section">
                <div class="auth-container">
                    <h2>Create an Account</h2>
                    <p>Register to start your application process</p>
                    
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-group terms">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="terms.php">Terms and Conditions</a></label>
                        </div>
                        <button type="submit" class="btn primary-btn full-width">Register</button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login</a></p>
                    </div>
                </div>
                <div class="auth-image">
                    <img src="/placeholder.svg?height=600&width=600" alt="Campus Building">
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

