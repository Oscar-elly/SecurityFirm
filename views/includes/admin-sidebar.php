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
                <span class="nav-section-title">Security Management</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'guards.php' ? 'active' : ''; ?>">
                <a href="guards.php">
                    <i data-lucide="shield"></i>
                    <span>Guards</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'duty-assignments.php' ? 'active' : ''; ?>">
                <a href="duty-assignments.php">
                    <i data-lucide="calendar"></i>
                    <span>Duty Assignments</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'shifts.php' ? 'active' : ''; ?>">
                <a href="shifts.php">
                    <i data-lucide="clock"></i>
                    <span>Shifts</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'incidents.php' ? 'active' : ''; ?>">
                <a href="incidents.php">
                    <i data-lucide="alert-triangle"></i>
                    <span>Incidents</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'active' : ''; ?>">
                <a href="attendance.php">
                    <i data-lucide="check-square"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Organizations</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'organizations.php' ? 'active' : ''; ?>">
                <a href="organizations.php">
                    <i data-lucide="building-2"></i>
                    <span>Organizations</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'locations.php' ? 'active' : ''; ?>">
                <a href="locations.php">
                    <i data-lucide="map-pin"></i>
                    <span>Locations</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'guard-requests.php' ? 'active' : ''; ?>">
                <a href="guard-requests.php">
                    <i data-lucide="file-text"></i>
                    <span>Guard Requests</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Reports & Analytics</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php">
                    <i data-lucide="bar-chart-2"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?>">
                <a href="analytics.php">
                    <i data-lucide="pie-chart"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'evaluations.php' ? 'active' : ''; ?>">
                <a href="evaluations.php">
                    <i data-lucide="trending-up"></i>
                    <span>Performance</span>
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
                <span class="nav-section-title">System</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                <a href="users.php">
                    <i data-lucide="users"></i>
                    <span>Users</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'activity-logs.php' ? 'active' : ''; ?>">
                <a href="activity-logs.php">
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