<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

header('Content-Type: application/json; charset=UTF-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare(
    "SELECT id, full_name, student_id, email FROM students
     WHERE status != 'pending' AND (full_name LIKE :q1 OR student_id LIKE :q2 OR email LIKE :q3)
     ORDER BY full_name ASC LIMIT 8"
);
$like = '%' . $q . '%';
$stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);

echo json_encode(array_map(function (array $r): array {
    return [
        'id'    => (int) $r['id'],
        'title' => $r['full_name'],
        'meta'  => $r['student_id'] . ' · ' . $r['email'],
    ];
}, $stmt->fetchAll()));
