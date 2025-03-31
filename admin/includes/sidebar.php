<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="admin-logo">
            <img src="/placeholder.svg?height=60&width=60" alt="University Logo">
            <h2>Admin Portal</h2>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>">
                <a href="applications.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'ai-process.php' ? 'active' : ''; ?>">
                <a href="ai-process.php">
                    <i class="fas fa-robot"></i>
                    <span>AI Matching</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                <a href="analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'programs.php' ? 'active' : ''; ?>">
                <a href="programs.php">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Programs</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'email-templates.php' ? 'active' : ''; ?>">
                <a href="email-templates.php">
                    <i class="fas fa-envelope"></i>
                    <span>Email Templates</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

