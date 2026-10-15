<?php
/**
 * includes/functions.php — Global helper functions
 */

require_once __DIR__ . '/../config/database.php';

// ── Application constants ──────────────────────────────────────────
define('UPLOAD_DIR',             __DIR__ . '/../uploads/researches/');
define('MAX_FILE_SIZE',          50 * 1024 * 1024);  // 50 MB
define('ITEMS_PER_PAGE',         10);
define('ADMIN_SESSION_TIMEOUT',  1800);   // 30 minutes
define('STUDENT_SESSION_TIMEOUT',3600);   // 60 minutes

// ── Session ────────────────────────────────────────────────────────

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode',  '1');
        ini_set('session.cookie_samesite',  'Strict');
        session_start();
    }
}

/** Regenerate session ID and update last-activity on login. */
function loginSession(string $role, array $userData): void
{
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    if ($role === 'admin') {
        $_SESSION['admin_id']        = $userData['id'];
        $_SESSION['admin_username']  = $userData['username'];
        $_SESSION['admin_full_name'] = $userData['full_name'] ?? 'Admin';
    } else {
        $_SESSION['student_id']         = $userData['id'];
        $_SESSION['student_full_name']  = $userData['full_name'];
        $_SESSION['student_course']     = $userData['course'];
        $_SESSION['student_year_level'] = $userData['year_level'];
        $_SESSION['student_status']     = $userData['status'];
    }
}

/** Check session timeout; redirect to login if expired. */
function checkSessionTimeout(): void
{
    $timeout = isAdmin() ? ADMIN_SESSION_TIMEOUT : STUDENT_SESSION_TIMEOUT;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header('Location: ' . baseUrl('login.php?timeout=1'));
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ── Role helpers ───────────────────────────────────────────────────

function isAdmin(): bool    { return !empty($_SESSION['admin_id']); }
function isStudent(): bool  { return !empty($_SESSION['student_id']); }

function isApprovedStudent(): bool
{
    return isStudent() && ($_SESSION['student_status'] ?? '') === 'active';
}

// ── URL helper ─────────────────────────────────────────────────────

/**
 * Returns an absolute URL rooted at the application directory.
 * Works whether the app is at htdocs/ root or a sub-folder.
 */
function baseUrl(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // functions.php lives in includes/ — two levels from the web root of the app
        $appDir   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $relative = str_replace($docRoot, '', $appDir);
        $base     = $protocol . '://' . $host . $relative . '/';
    }
    return $base . ltrim($path, '/');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ── XSS protection ─────────────────────────────────────────────────

/** Escape a value for safe HTML output. */
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ── CSRF ───────────────────────────────────────────────────────────

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Outputs a hidden CSRF input field. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/** Abort with 403 if the submitted CSRF token is invalid. */
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token validation failed. Go back and try again.');
    }
}

// ── Flash messages ─────────────────────────────────────────────────

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Returns the flash message array and removes it from the session. */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Renders a Bootstrap 5 alert from the current flash message. */
function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) return;
    $typeMap = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
    $cls = $typeMap[$flash['type']] ?? 'alert-info';
    echo '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
        . e($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        . '</div>';
}

// ── Activity logging ───────────────────────────────────────────────

function logActivity(string $userType, int $userId, string $action, ?int $researchId = null): void
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Support IPv6 / proxy forwarding
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        getDB()->prepare(
            'INSERT INTO activity_logs (user_type, user_id, action, research_id, ip_address)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$userType, $userId, $action, $researchId, trim($ip)]);
    } catch (PDOException) {
        // Never break UX for a logging error
    }
}

// ── Badge colours ──────────────────────────────────────────────────

function courseColor(string $course): string
{
    return match ($course) {
        'IT'                   => 'badge-it',
        'Computer Engineering' => 'badge-compe',
        'Computer Science'     => 'badge-compsci',
        default                => 'bg-secondary',
    };
}

function typeColor(string $type): string
{
    return match ($type) {
        'Web App'             => 'badge-webapp',
        'Mobile App'          => 'badge-mobile',
        'IoT'                 => 'badge-iot',
        'Machine Learning/AI' => 'badge-ml',
        'Desktop App'         => 'badge-desktop',
        'Cybersecurity'       => 'badge-cyber',
        'Data Analytics'      => 'badge-analytics',
        default               => 'bg-secondary',
    };
}

// ── Pagination ─────────────────────────────────────────────────────

function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array
{
    $totalPages  = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($page, $totalPages));
    return [
        'total'        => $total,
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
        'per_page'     => $perPage,
        'offset'       => ($currentPage - 1) * $perPage,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

// ── Research queries ───────────────────────────────────────────────

function getResearches(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
{
    $pdo    = getDB();
    $where  = ['1=1'];
    $params = [];

    if (!empty($filters['search'])) {
        $where[]  = '(title LIKE ? OR authors LIKE ? OR abstract LIKE ?)';
        $keyword  = '%' . $filters['search'] . '%';
        $params   = array_merge($params, [$keyword, $keyword, $keyword]);
    }
    if (!empty($filters['course'])) {
        $where[] = 'course = ?';
        $params[] = $filters['course'];
    }
    if (!empty($filters['year'])) {
        $where[] = 'year = ?';
        $params[] = $filters['year'];
    }
    if (!empty($filters['research_type'])) {
        $where[] = 'research_type = ?';
        $params[] = $filters['research_type'];
    }

    $whereClause = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM researches WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $orderBy = match ($filters['sort'] ?? '') {
        'oldest'    => 'year ASC, created_at ASC',
        'title_asc' => 'title ASC',
        default     => 'year DESC, created_at DESC',
    };

    $pg     = paginate($total, $page, $perPage);
    $stmt   = $pdo->prepare(
        "SELECT * FROM researches WHERE $whereClause
         ORDER BY $orderBy
         LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
    );
    $stmt->execute($params);

    return ['data' => $stmt->fetchAll(), 'pagination' => $pg];
}

function getResearchById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM researches WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Student queries ────────────────────────────────────────────────

function getStudentById(int $id): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Admin stats ────────────────────────────────────────────────────

function getPendingStudentsCount(): int
{
    return (int) getDB()->query("SELECT COUNT(*) FROM students WHERE status = 'pending'")->fetchColumn();
}

function getDashboardStats(): array
{
    $pdo = getDB();
    return [
        'total_researches'  => (int) $pdo->query('SELECT COUNT(*) FROM researches')->fetchColumn(),
        'total_students'    => (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn(),
        'pending_approvals' => (int) $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'pending'")->fetchColumn(),
        'total_downloads'   => (int) ($pdo->query('SELECT COALESCE(SUM(download_count),0) FROM researches')->fetchColumn()),
    ];
}

function getResearchCountByYear(): array
{
    return getDB()->query('SELECT year, COUNT(*) AS cnt FROM researches GROUP BY year ORDER BY year')->fetchAll();
}

function getResearchCountByCourse(): array
{
    return getDB()->query('SELECT course, COUNT(*) AS cnt FROM researches GROUP BY course')->fetchAll();
}

function getResearchCountByType(): array
{
    return getDB()->query('SELECT research_type, COUNT(*) AS cnt FROM researches GROUP BY research_type')->fetchAll();
}

function getRecentResearches(int $limit = 5): array
{
    $stmt = getDB()->prepare('SELECT * FROM researches ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getRecentStudents(int $limit = 5): array
{
    $stmt = getDB()->prepare('SELECT * FROM students ORDER BY registered_at DESC LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getPendingStudents(): array
{
    return getDB()->query("SELECT * FROM students WHERE status = 'pending' ORDER BY registered_at DESC")->fetchAll();
}

// ── Validation helpers ─────────────────────────────────────────────

function isValidStudentId(string $id): bool
{
    return (bool) preg_match('/^\d{2}-\d{4}-\d{3}$/', $id);
}

/** Returns an array of all ALLOWED course values. */
function allowedCourses(): array
{
    return ['IT', 'Computer Engineering', 'Computer Science'];
}

/** Returns an array of all ALLOWED research type values. */
function allowedTypes(): array
{
    return ['Web App', 'Mobile App', 'IoT', 'Machine Learning/AI', 'Desktop App', 'Cybersecurity', 'Data Analytics'];
}

/** Returns an array of all ALLOWED year-level values. */
function allowedYearLevels(): array
{
    return ['1st Year', '2nd Year', '3rd Year', '4th Year'];
}
