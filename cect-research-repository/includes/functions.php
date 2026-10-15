<?php
/**
 * Shared helper functions for the CECT Research Repository.
 */

require_once __DIR__ . '/../config/database.php';

const STUDENT_TIMEOUT = 60 * 60; // 60 minutes
const ADMIN_TIMEOUT   = 30 * 60; // 30 minutes
const STUDENT_ID_REGEX = '/^\d{2}-\d{4}-\d{3}$/';
const INSTITUTIONAL_EMAIL_DOMAIN = '@wesleyan.edu.ph';
const INSTITUTIONAL_EMAIL_REGEX = '/^[a-z]+\.[a-z]+@wesleyan\.edu\.ph$/i';
const RESEARCH_TYPES = ['Thesis', 'Capstone'];

const RESEARCH_CATEGORIES = [
    'Mobile App', 'Web App', 'Desktop App', 'Information System', 'AI', 'Machine Learning',
    'IoT', 'Computer Vision', 'Cybersecurity', 'Cloud Computing', 'Networking', 'Robotics',
    'Embedded Systems', 'Blockchain', 'AR/VR', 'Game Development', 'Data Science', 'GIS', 'Automation',
];

/**
 * Escape output for safe HTML rendering.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

/* ---------------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

/* ---------------------------------------------------------------------
 * Session / timeout handling
 * ------------------------------------------------------------------- */

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function enforce_timeout(int $seconds, string $loginPath = 'login.php'): void
{
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $seconds) {
        $_SESSION = [];
        session_destroy();
        redirect($loginPath . '?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

/* ---------------------------------------------------------------------
 * Activity logging
 * ------------------------------------------------------------------- */

function log_activity(string $userType, int $userId, string $userName, string $action, ?string $details = null): void
{
    $db = Database::getConnection();
    $stmt = $db->prepare(
        'INSERT INTO activity_logs (user_type, user_id, user_name, action, details, ip_address)
         VALUES (:user_type, :user_id, :user_name, :action, :details, :ip)'
    );
    $stmt->execute([
        'user_type' => $userType,
        'user_id'   => $userId,
        'user_name' => $userName,
        'action'    => $action,
        'details'   => $details,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

/* ---------------------------------------------------------------------
 * Authentication (shared by the student and admin login pages)
 * ------------------------------------------------------------------- */

/**
 * Verifies credentials against both the admins and students tables and
 * returns which role they belong to. Credentials are checked in full
 * (including password_verify) before any role is disclosed, so a wrong
 * password never reveals which table an identifier matched.
 *
 * @return array{role: 'admin'|'student', record: array}|null
 */
function authenticate_user(string $identifier, string $password): ?array
{
    $db = Database::getConnection();

    $stmt = $db->prepare('SELECT * FROM admins WHERE username = :u1 OR email = :u2 LIMIT 1');
    $stmt->execute(['u1' => $identifier, 'u2' => $identifier]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return ['role' => 'admin', 'record' => $admin];
    }

    $stmt = $db->prepare('SELECT * FROM students WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $identifier]);
    $student = $stmt->fetch();
    if ($student && password_verify($password, $student['password_hash'])) {
        return ['role' => 'student', 'record' => $student];
    }

    return null;
}

/* ---------------------------------------------------------------------
 * Research categories (many-to-many)
 * ------------------------------------------------------------------- */

function save_research_categories(PDO $db, int $researchId, array $categories): void
{
    $categories = array_values(array_intersect($categories, RESEARCH_CATEGORIES));

    $db->prepare('DELETE FROM research_categories WHERE research_id = :id')->execute(['id' => $researchId]);

    if (empty($categories)) {
        return;
    }

    $stmt = $db->prepare('INSERT INTO research_categories (research_id, category) VALUES (:id, :category)');
    foreach ($categories as $category) {
        $stmt->execute(['id' => $researchId, 'category' => $category]);
    }
}

function get_research_categories(PDO $db, int $researchId): array
{
    $stmt = $db->prepare('SELECT category FROM research_categories WHERE research_id = :id ORDER BY category');
    $stmt->execute(['id' => $researchId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* ---------------------------------------------------------------------
 * Validation helpers
 * ------------------------------------------------------------------- */

function valid_student_id(string $id): bool
{
    return (bool) preg_match(STUDENT_ID_REGEX, $id);
}

function valid_institutional_email(string $email): bool
{
    return (bool) preg_match(INSTITUTIONAL_EMAIL_REGEX, $email);
}

function valid_password(string $password): bool
{
    // At least 8 chars, one letter, one number
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/\d/', $password);
}

/* ---------------------------------------------------------------------
 * File upload helpers
 * ------------------------------------------------------------------- */

function store_research_pdf(array $file): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['ok' => false, 'error' => 'File exceeds the 20MB size limit.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['ok' => false, 'error' => 'Only PDF files are allowed.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        return ['ok' => false, 'error' => 'The uploaded file is not a valid PDF.'];
    }

    $storedName = uniqid('res_', true) . '.pdf';
    $destination = UPLOAD_DIR . $storedName;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
    }

    return [
        'ok'                 => true,
        'file_name'          => $storedName,
        'original_file_name' => basename($file['name']),
        'file_size'          => $file['size'],
    ];
}

function format_file_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 2) . ' MB';
}
