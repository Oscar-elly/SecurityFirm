<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <h2>SecureConnect</h2>
            <span>Kenya</span>
        </div>
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i data-lucide="menu"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Duty Management</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : ''; ?>">
                <a href="schedule.php">
                    <i data-lucide="calendar"></i>
                    <span>My Schedule</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : ''; ?>">
                <a href="attendance.php">
                    <i data-lucide="check-square"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Incidents</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'report-incident.php' ? 'active' : ''; ?>">
                <a href="report-incident.php">
                    <i data-lucide="alert-triangle"></i>
                    <span>Report Incident</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'incidents.php' ? 'active' : ''; ?>">
                <a href="incidents.php">
                    <i data-lucide="list"></i>
                    <span>My Incidents</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Performance</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'evaluations.php' ? 'active' : ''; ?>">
                <a href="evaluations.php">
                    <i data-lucide="trending-up"></i>
                    <span>My Evaluations</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Communication</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>">
                <a href="messages.php">
                    <i data-lucide="message-square"></i>
                    <span>Messages</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                <a href="notifications.php">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Account</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i data-lucide="user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../../logout.php" class="logout-btn">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>