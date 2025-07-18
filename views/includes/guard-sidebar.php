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
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/dashboard.php' ? 'active' : ''; ?>">
                <a href="../guard/dashboard.php">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Duty Management</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/schedule.php' ? 'active' : ''; ?>">
                <a href="../guard/schedule.php">
                    <i data-lucide="calendar"></i>
                    <span>My Schedule</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/attendance.php' ? 'active' : ''; ?>">
                <a href="../guard/attendance.php">
                    <i data-lucide="check-square"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Incidents</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/report-incident.php' ? 'active' : ''; ?>">
                <a href="../guard/report-incident.php">
                    <i data-lucide="alert-triangle"></i>
                    <span>Report Incident</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/incidents.php' ? 'active' : ''; ?>">
                <a href="../guard/incidents.php">
                    <i data-lucide="list"></i>
                    <span>My Incidents</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Performance</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/evaluations.php' ? 'active' : ''; ?>">
                <a href="../guard/evaluations.php">
                    <i data-lucide="trending-up"></i>
                    <span>My Evaluations</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Communication</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../shared/messages.php' ? 'active' : ''; ?>">
                <a href="../shared/messages.php">
                    <i data-lucide="message-square"></i>
                    <span>Messages</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../shared/notifications.php' ? 'active' : ''; ?>">
                <a href="../shared/notifications.php">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Account</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/profile.php' ? 'active' : ''; ?>">
                <a href="../guard/profile.php">
                    <i data-lucide="user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../guard/settings.php' ? 'active' : ''; ?>">
                <a href="../guard/settings.php">
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