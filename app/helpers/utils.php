<?php
/**
 * Utility Helper Functions
 * Common utility functions for the SmartGrade System
 */

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_type']) && isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 * @return string
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = 'alert-' . $flash['type'];
        return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                    {$flash['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return '';
}

/**
 * Format date to readable format
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'F j, Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format datetime to readable format
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    if (!$datetime) return 'N/A';
    return date('F j, Y g:i A', strtotime($datetime));
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . $suffix;
    }
    return $text;
}

/**
 * Get URL parameter
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getParam($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Get POST data
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function postData($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Debug print (only in development)
 * @param mixed $data
 */
function debug($data) {
    if (ENV === 'development') {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

/**
 * Get active menu class
 * @param string $page
 * @return string
 */
function activeMenu($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}

/**
 * Calculate age from birthdate
 * @param string $birthdate
 * @return int
 */
function calculateAge($birthdate) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get school year label
 * @param int $year
 * @return string
 */
function getSchoolYearLabel($year) {
    return $year . '-' . ($year + 1);
}

/**
 * Check if request is POST
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 * @return bool
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Return JSON response
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
