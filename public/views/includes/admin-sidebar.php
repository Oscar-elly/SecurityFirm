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
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/dashboard.php' ? 'active' : ''; ?>">
                <a href="../admin/dashboard.php">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Security Management</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/guards.php' ? 'active' : ''; ?>">
                <a href="../admin/guards.php">
                    <i data-lucide="shield"></i>
                    <span>Guards</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/duty-assignments.php' ? 'active' : ''; ?>">
                <a href="../admin/duty-assignments.php">
                    <i data-lucide="calendar"></i>
                    <span>Duty Assignments</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/shifts.php' ? 'active' : ''; ?>">
                <a href="../admin/shifts.php">
                    <i data-lucide="clock"></i>
                    <span>Shifts</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/incidents.php' ? 'active' : ''; ?>">
                <a href="../admin/incidents.php">
                    <i data-lucide="alert-triangle"></i>
                    <span>Incidents</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/attendance.php' ? 'active' : ''; ?>">
                <a href="../admin/attendance.php">
                    <i data-lucide="check-square"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Organizations</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/organizations.php' ? 'active' : ''; ?>">
                <a href="../admin/organizations.php">
                    <i data-lucide="building-2"></i>
                    <span>Organizations</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/locations.php' ? 'active' : ''; ?>">
                <a href="../admin/locations.php">
                    <i data-lucide="map-pin"></i>
                    <span>Locations</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/guard-requests.php' ? 'active' : ''; ?>">
                <a href="../admin/guard-requests.php">
                    <i data-lucide="file-text"></i>
                    <span>Guard Requests</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Reports & Analytics</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/reports.php' ? 'active' : ''; ?>">
                <a href="../admin/reports.php">
                    <i data-lucide="bar-chart-2"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/analytics.php' ? 'active' : ''; ?>">
                <a href="../admin/analytics.php">
                    <i data-lucide="pie-chart"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/evaluations.php' ? 'active' : ''; ?>">
                <a href="../admin/evaluations.php">
                    <i data-lucide="trending-up"></i>
                    <span>Performance</span>
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
                <span class="nav-section-title">System</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/users.php' ? 'active' : ''; ?>">
                <a href="../admin/users.php">
                    <i data-lucide="users"></i>
                    <span>Users</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/settings.php' ? 'active' : ''; ?>">
                <a href="../admin/settings.php">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === '../admin/activity-logs.php' ? 'active' : ''; ?>">
                <a href="../admin/activity-logs.php">
                    <i data-lucide="activity"></i>
                    <span>Activity Logs</span>
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