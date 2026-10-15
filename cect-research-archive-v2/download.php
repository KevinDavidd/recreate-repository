<?php
/**
 * download.php — Secure PDF delivery handler.
 * Streams the PDF from /uploads/researches/ without exposing real paths.
 * Increments download_count (unless ?view=1 for inline preview).
 */

require_once __DIR__ . '/includes/functions.php';
startSession();
checkSessionTimeout();

// Allow both active students AND admins; redirect everyone else to login
if (!isAdmin() && !isApprovedStudent()) {
    redirect(baseUrl('login.php'));
}

$id   = filter_input(INPUT_GET, 'id',   FILTER_VALIDATE_INT);
$view = isset($_GET['view']) && $_GET['view'] === '1';

if (!$id || $id < 1) {
    http_response_code(400);
    die('Invalid request.');
}

$research = getResearchById($id);
if (!$research) {
    http_response_code(404);
    die('Research paper not found.');
}

$filename = $research['pdf_file'];

if (empty($filename)) {
    http_response_code(404);
    die('No PDF file associated with this entry.');
}

// Prevent path traversal — only basename allowed
$filename = basename($filename);
$filepath = UPLOAD_DIR . $filename;

if (!file_exists($filepath) || !is_readable($filepath)) {
    http_response_code(404);
    die('File not found on server.');
}

// Increment download count for actual downloads (not inline views)
if (!$view) {
    getDB()->prepare('UPDATE researches SET download_count = download_count + 1 WHERE id = ?')
           ->execute([$id]);
    if (isAdmin()) {
        logActivity('admin',   $_SESSION['admin_id'],   'downloaded', $id);
    } else {
        logActivity('student', $_SESSION['student_id'], 'downloaded', $id);
    }
}

// Stream the file
$safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', mb_substr($research['title'], 0, 60));
$disposition = $view ? 'inline' : 'attachment';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename="' . $safeTitle . '.pdf"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filepath);
exit;
