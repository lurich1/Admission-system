<header class="admin-header">
    <div class="header-left">
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-bar">
            <input type="text" placeholder="Search...">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
    <div class="header-right">
        <div class="notifications">
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <div class="notification-dropdown">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <a href="#">Mark all as read</a>
                </div>
                <div class="notification-list">
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="notification-content">
                            <p>5 new applications submitted</p>
                            <span class="notification-time">2 hours ago</span>
                        </div>
                    </div>
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="notification-content">
                            <p>AI matching completed for 8 applications</p>
                            <span class="notification-time">5 hours ago</span>
                        </div>
                    </div>
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="notification-content">
                            <p>3 applications require manual review</p>
                            <span class="notification-time">Yesterday</span>
                        </div>
                    </div>
                </div>
                <div class="notification-footer">
                    <a href="notifications.php">View all notifications</a>
                </div>
            </div>
        </div>
        <div class="admin-profile">
            <div class="profile-info">
                <span class="admin-name"><?php echo isset($_SESSION["admin_name"]) ? htmlspecialchars($_SESSION["admin_name"]) : 'Admin User'; ?></span>
                <span class="admin-role">Administrator</span>
            </div>
            <div class="profile-image">
                <img src="/placeholder.svg?height=40&width=40" alt="Admin Profile">
            </div>
            <div class="profile-dropdown">
                <div class="dropdown-item">
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </div>
                <div class="dropdown-item">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-item">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Toggle sidebar
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
    });
    
    // Toggle notification dropdown
    document.querySelector('.notification-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.notification-dropdown').classList.toggle('show');
        document.querySelector('.profile-dropdown').classList.remove('show');
    });
    
    // Toggle profile dropdown
    document.querySelector('.profile-image').addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.profile-dropdown').classList.toggle('show');
        document.querySelector('.notification-dropdown').classList.remove('show');
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelector('.notification-dropdown').classList.remove('show');
        document.querySelector('.profile-dropdown').classList.remove('show');
    });
</script>

