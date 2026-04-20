<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'adrian');
define('DB_PASS', 'qwerty');
define('DB_NAME', 'perpustakaan');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}

function sanitize($data) {
    if ($data === null || $data === '') {
        return '';
    }
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function checkAuth() {
    if (!isLoggedIn()) {
        redirect('/perpustakaan/auth/login.php');
    }
}

function checkAdmin() {
    if (!isAdmin()) {
        redirect('/perpustakaan/');
    }
}

function checkUser() {
    if (!isUser()) {
        redirect('/perpustakaan/');
    }
}

function getAverageRating($book_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE book_id = ? AND status = 'approved'");
    $stmt->execute([$book_id]);
    $result = $stmt->fetch();
    return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
}

function getReviewCount($book_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE book_id = ? AND status = 'approved'");
    $stmt->execute([$book_id]);
    $result = $stmt->fetch();
    return $result['count'];
}

function formatDate($date) {
    if (!$date) return '-';
    $datetime = new DateTime($date);
    return $datetime->format('d M Y');
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    $dt = new DateTime($datetime);
    return $dt->format('d M Y H:i');
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return round($diff / 60) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    if ($diff < 604800) return round($diff / 86400) . 'd ago';
    
    return date('d M Y', $time);
}

function uploadImage($file, $folder = 'uploads') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return null;
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $folder . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    return null;
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = preg_replace('~-+~', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

function generateStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    
    $stars = str_repeat('★', $full);
    if ($half) $stars .= '✯';
    $stars .= str_repeat('☆', $empty);
    
    return $stars;
}
