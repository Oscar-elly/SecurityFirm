<?php
if (substr_count($query, '?') !== count((array) $params)) {
    error_log("⚠️ Query param mismatch: placeholders = " . substr_count($query, '?') . ", params = " . count((array) $params));
    return false;
}

function executeQuery($query, $params = [], $options = []) {
    global $conn;

    // Prepare the SQL statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    // Bind parameters if any
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // auto-detect all as strings
        $stmt->bind_param($types, ...$params);
    }

    // Execute the statement
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    // Handle SELECT queries
    if (stripos($query, 'SELECT') === 0) {
        $result = $stmt->get_result();
        return !empty($options['single']) ? $result->fetch_assoc() : $result->fetch_all(MYSQLI_ASSOC);
    }

    // Handle INSERT, UPDATE, DELETE
    return [
        'affected_rows' => $stmt->affected_rows,
        'insert_id' => $stmt->insert_id
    ];
}

/**
 * Common functions for the Security Management System
 */

/**
 * Sanitize user input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool Returns true if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool Returns true if user has the role, false otherwise
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    
    return $_SESSION['role'] === $roles;
}

/**
 * Require user to be logged in, redirect to login if not
 * 
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page';
        redirect(SITE_URL);
    }
}

/**
 * Require user to have specific role, redirect if not
 * 
 * @param string|array $roles Role(s) to check
 * @return void
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        
        switch ($_SESSION['role']) {
            case 'admin':
                redirect(SITE_URL . '/views/admin/dashboard.php');
                break;
            case 'guard':
                redirect(SITE_URL . '/views/guard/dashboard.php');
                break;
            case 'organization':
                redirect(SITE_URL . '/views/organization/dashboard.php');
                break;
            default:
                redirect(SITE_URL);
        }
    }
}

/**
 * Display flash message and clear it from session
 * 
 * @param string $type Message type (success, error, info, warning)
 * @return string HTML for the message
 */
function flashMessage($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

/**
 * Format date to a readable format
 * 
 * @param string $date Date to format
 * @param string $format Format to use
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M Y, h:i A') {
    return date($format, strtotime($date));
}

/**
 * Generate random token
 * 
 * @param int $length Length of token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log activity
 * 
 * @param int $user_id User ID
 * @param string $activity Activity description
 * @param string $type Activity type
 * @return bool Success or failure
 */
function logActivity($user_id, $activity, $type = 'general') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity, type) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $activity, $type);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Upload file
 * 
 * @param array $file File from $_FILES
 * @param string $directory Directory to upload to (inside UPLOAD_DIR)
 * @return array|bool File info or false on failure
 */
function uploadFile($file, $directory = '') {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check file extension
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Create target directory if it doesn't exist
    $target_dir = UPLOAD_DIR . $directory;
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $extension;
    $target_path = $target_dir . '/' . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return [
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'path' => $directory . '/' . $new_filename
        ];
    }
    
    return false;
}

/**
 * Get distance between two coordinates in kilometers
 * 
 * @param float $lat1 Latitude of first point
 * @param float $lon1 Longitude of first point
 * @param float $lat2 Latitude of second point
 * @param float $lon2 Longitude of second point
 * @return float Distance in kilometers
 */
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}