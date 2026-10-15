<?php
/**
 * admin/research_preview.php — AJAX endpoint: returns research details as JSON.
 */
require_once __DIR__ . '/../includes/admin_auth_check.php';

header('Content-Type: application/json');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$r = getResearchById($id);
if (!$r) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$pdfExists = !empty($r['pdf_file']) && file_exists(UPLOAD_DIR . $r['pdf_file']);

echo json_encode([
    'id'             => $r['id'],
    'title'          => $r['title'],
    'authors'        => $r['authors'],
    'course'         => $r['course'],
    'research_type'  => $r['research_type'],
    'year'           => $r['year'],
    'abstract'       => $r['abstract'],
    'adviser'        => $r['adviser'],
    'panel_chair'    => $r['panel_chair'],
    'panel_members'  => $r['panel_members'] ?? '',
    'view_count'     => $r['view_count'],
    'download_count' => $r['download_count'],
    'created_at'     => date('M j, Y', strtotime($r['created_at'])),
    'pdf_exists'     => $pdfExists,
]);
