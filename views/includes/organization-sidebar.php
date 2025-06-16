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
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'locations.php' ? 'active' : ''; ?>">
                <a href="locations.php">
                    <i data-lucide="map-pin"></i>
                    <span>Locations</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'guards.php' ? 'active' : ''; ?>">
                <a href="guards.php">
                    <i data-lucide="shield"></i>
                    <span>Guard Details</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'guard-requests.php' ? 'active' : ''; ?>">
                <a href="guard-requests.php">
                    <i data-lucide="file-text"></i>
                    <span>Guard Requests</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'security-status.php' ? 'active' : ''; ?>">
                <a href="security-status.php">
                    <i data-lucide="shield-check"></i>
                    <span>Security Status</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Incidents</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'incidents.php' ? 'active' : ''; ?>">
                <a href="incidents.php">
                    <i data-lucide="alert-triangle"></i>
                    <span>Incidents</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?>">
                <a href="analytics.php">
                    <i data-lucide="bar-chart-2"></i>
                    <span>Security Analytics</span>
                </a>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'risk-assessment.php' ? 'active' : ''; ?>">
                <a href="risk-assessment.php">
                    <i data-lucide="activity"></i>
                    <span>Risk Assessment</span>
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
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php">
                    <i data-lucide="file-text"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-section">
                <span class="nav-section-title">Account</span>
            </li>
            
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i data-lucide="user"></i>
                    <span>Organization Profile</span>
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