<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Admission Portal</title>
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
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

        <main>
            <section class="hero">
                <div class="hero-content">
                    <h2>Welcome to Our Admission Portal</h2>
                    <p>Start your journey to academic excellence with us</p>
                    <div class="cta-buttons">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="dashboard.php" class="btn primary-btn">Go to Dashboard</a>
                        <?php else: ?>
                            <a href="login.php" class="btn primary-btn">Login</a>
                            <a href="register.php" class="btn secondary-btn">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="/placeholder.svg?height=400&width=600" alt="Students at campus">
                </div>
            </section>

            <section class="features">
                <h2>Why Choose Our Admission System?</h2>
                <div class="feature-cards">
                    <div class="card">
                        <i class="fas fa-clock"></i>
                        <h3>Real-time Updates</h3>
                        <p>Get instant notifications about your application status</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Secure Process</h3>
                        <p>Your data is protected with advanced security measures</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-check-circle"></i>
                        <h3>Transparent</h3>
                        <p>Clear criteria and fair evaluation for all applicants</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-file-alt"></i>
                        <h3>Paperless</h3>
                        <p>Environmentally friendly digital application process</p>
                    </div>
                </div>
            </section>

            <section class="programs">
                <h2>Available Programs</h2>
                <div class="program-list">
                    <div class="program">
                        <h3>Computer Science</h3>
                        <p>Bachelor of Science in Computer Science</p>
                        <a href="program-details.php?id=1" class="btn small-btn">Learn More</a>
                    </div>
                    <div class="program">
                        <h3>Electrical Engineering</h3>
                        <p>Bachelor of Engineering in Electrical Engineering</p>
                        <a href="program-details.php?id=2" class="btn small-btn">Learn More</a>
                    </div>
                    <div class="program">
                        <h3>Business Administration</h3>
                        <p>Bachelor of Business Administration</p>
                        <a href="program-details.php?id=3" class="btn small-btn">Learn More</a>
                    </div>
                    <div class="program">
                        <h3>Environmental Science</h3>
                        <p>Bachelor of Science in Environmental Science</p>
                        <a href="program-details.php?id=4" class="btn small-btn">Learn More</a>
                    </div>
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

