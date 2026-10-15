<?php
require_once __DIR__ . '/includes/auth_check.php';

header('Content-Type: application/json; charset=UTF-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT id, title, course, year_published FROM researches
     WHERE status = 'active' AND (title LIKE :q1 OR authors LIKE :q2)
     ORDER BY title ASC LIMIT 8"
);
$like = '%' . $q . '%';
$stmt->execute(['q1' => $like, 'q2' => $like]);

echo json_encode(array_map(function (array $r): array {
    return [
        'id'    => (int) $r['id'],
        'title' => $r['title'],
        'meta'  => $r['course'] . ' · ' . $r['year_published'],
    ];
}, $stmt->fetchAll()));
