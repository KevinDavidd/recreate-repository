<?php
/**
 * One-time installer for the CECT Research Repository.
 * Creates the database/tables, an admin account, sample students, and
 * sample research entries with bcrypt-hashed passwords.
 *
 * DELETE OR RENAME THIS FILE IMMEDIATELY AFTER RUNNING IT.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$steps = [];
$fatalError = null;

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $steps[] = 'Connected to MySQL server.';

    $sql = file_get_contents(__DIR__ . '/database/cect_repository.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read database/cect_repository.sql');
    }

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $steps[] = 'Database and tables created (or already existed).';

    $pdo->exec('USE ' . DB_NAME);

    // Admin account
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :u');
    $stmt->execute(['u' => 'admin']);
    if (!$stmt->fetch()) {
        $pdo->prepare(
            'INSERT INTO admins (username, full_name, email, password_hash) VALUES (:u, :n, :e, :p)'
        )->execute([
            'u' => 'admin',
            'n' => 'CECT Department Head',
            'e' => 'admin@wesleyan.edu.ph',
            'p' => password_hash('Admin@2025', PASSWORD_BCRYPT),
        ]);
        $steps[] = 'Admin account created (username: admin / password: Admin@2025).';
    } else {
        $steps[] = 'Admin account already exists — skipped.';
    }

    // Sample students
    $students = [
        ['21-1234-001', 'Juan dela Cruz', 'BSIT', 'delacruz.juan@wesleyan.edu.ph', 'Student@2025', 'active'],
        ['21-1234-002', 'Maria Santos', 'BSCS', 'santos.maria@wesleyan.edu.ph', 'Student@2025', 'active'],
        ['21-1234-003', 'Pedro Reyes', 'BSCpE', 'reyes.pedro@wesleyan.edu.ph', 'Student@2025', 'pending'],
        ['22-5678-004', 'Andrea Lim', 'BSIT', 'lim.andrea@wesleyan.edu.ph', 'Student@2025', 'pending'],
        ['22-5678-005', 'Marco Fernandez', 'BSCS', 'fernandez.marco@wesleyan.edu.ph', 'Student@2025', 'pending'],
        ['22-5678-006', 'Bianca Cruz', 'BSCpE', 'cruz.bianca@wesleyan.edu.ph', 'Student@2025', 'pending'],
        ['22-5678-007', 'Ronan Aquino', 'BSIT', 'aquino.ronan@wesleyan.edu.ph', 'Student@2025', 'pending'],
    ];

    $checkStudent = $pdo->prepare('SELECT id FROM students WHERE email = :e');
    $insertStudent = $pdo->prepare(
        'INSERT INTO students (student_id, full_name, course, email, password_hash, status, approved_at)
         VALUES (:sid, :name, :course, :email, :hash, :status, :approved)'
    );

    foreach ($students as [$sid, $name, $course, $email, $plainPassword, $status]) {
        $checkStudent->execute(['e' => $email]);
        if (!$checkStudent->fetch()) {
            $insertStudent->execute([
                'sid'      => $sid,
                'name'     => $name,
                'course'   => $course,
                'email'    => $email,
                'hash'     => password_hash($plainPassword, PASSWORD_BCRYPT),
                'status'   => $status,
                'approved' => $status === 'active' ? date('Y-m-d H:i:s') : null,
            ]);
        }
    }
    $steps[] = 'Sample students created (2 active, 1 pending).';

    // Sample research entries
    $adminId = $pdo->query('SELECT id FROM admins LIMIT 1')->fetchColumn();

    $researchList = [
        [
            'title'    => 'A Mobile-Based Attendance Monitoring System Using QR Code',
            'abstract' => 'This study presents the design and development of a mobile application that automates student attendance monitoring through QR code scanning, reducing manual recording errors and administrative workload.',
            'authors'  => 'Juan dela Cruz, Maria Santos',
            'adviser'  => 'Engr. Ana Bautista',
            'course'   => 'BSIT',
            'type'     => 'Capstone',
            'year'     => 2024,
            'categories' => ['Mobile App'],
        ],
        [
            'title'    => 'Design and Implementation of a Smart Irrigation System Using IoT Sensors',
            'abstract' => 'The research explores an IoT-based irrigation system that uses soil moisture and weather sensors to optimize water usage for small-scale farms, improving crop yield while conserving resources.',
            'authors'  => 'Pedro Reyes',
            'adviser'  => 'Engr. Carlos Mendoza',
            'course'   => 'BSCpE',
            'type'     => 'Capstone',
            'year'     => 2023,
            'categories' => ['IoT', 'Embedded Systems'],
        ],
        [
            'title'    => 'Predicting Student Academic Performance Using Machine Learning Algorithms',
            'abstract' => 'This paper compares several machine learning classifiers in predicting student academic performance based on historical enrollment and grade data, aiming to support early academic intervention programs.',
            'authors'  => 'Maria Santos, Juan dela Cruz',
            'adviser'  => 'Dr. Ramon Villareal',
            'course'   => 'BSCS',
            'type'     => 'Thesis',
            'year'     => 2025,
            'categories' => ['AI', 'Machine Learning', 'Data Science'],
        ],
        [
            'title'    => 'E-Tulong: A Web-Based Barangay Service Request and Tracking System',
            'abstract' => 'E-Tulong digitizes the process of requesting and tracking barangay services such as certificates, permits, and complaint resolution, reducing queueing time and improving transparency between residents and local officials.',
            'authors'  => 'Angelo Ramirez, Kristine Bautista',
            'adviser'  => 'Engr. Ana Bautista',
            'course'   => 'BSIT',
            'type'     => 'Capstone',
            'year'     => 2020,
            'categories' => ['Web App', 'Information System'],
        ],
        [
            'title'    => 'Development of a Campus Network Traffic Monitoring Dashboard',
            'abstract' => 'This study develops a real-time dashboard that visualizes bandwidth usage and flags anomalous network traffic across campus subnets, helping the IT office proactively manage congestion and security incidents.',
            'authors'  => 'Michael Torres',
            'adviser'  => 'Dr. Ramon Villareal',
            'course'   => 'BSIT',
            'type'     => 'Capstone',
            'year'     => 2021,
            'categories' => ['Web App', 'Networking', 'Cybersecurity'],
        ],
        [
            'title'    => 'Design of a Low-Cost Solar-Powered Water Quality Monitoring Buoy',
            'abstract' => 'The research details the design and field testing of a solar-powered floating buoy equipped with pH, turbidity, and temperature sensors for continuous water quality monitoring in freshwater lakes.',
            'authors'  => 'Jerome Villanueva, Angelo Ramirez',
            'adviser'  => 'Engr. Carlos Mendoza',
            'course'   => 'BSCpE',
            'type'     => 'Capstone',
            'year'     => 2021,
            'categories' => ['IoT', 'Embedded Systems', 'Automation'],
        ],
        [
            'title'    => 'An FPGA-Based Real-Time Traffic Light Control System',
            'abstract' => 'This project implements an adaptive traffic light controller on an FPGA platform that adjusts signal timing based on real-time vehicle density captured through inductive loop sensors.',
            'authors'  => 'Kristine Bautista',
            'adviser'  => 'Engr. Carlos Mendoza',
            'course'   => 'BSCpE',
            'type'     => 'Capstone',
            'year'     => 2022,
            'categories' => ['Embedded Systems', 'Automation'],
        ],
        [
            'title'    => 'Sentiment Analysis of Student Feedback Using Natural Language Processing',
            'abstract' => 'This study applies NLP techniques to classify open-ended student feedback into positive, neutral, and negative sentiment categories, giving academic departments a scalable way to gauge satisfaction trends.',
            'authors'  => 'Michael Torres, Maria Santos',
            'adviser'  => 'Dr. Ramon Villareal',
            'course'   => 'BSCS',
            'type'     => 'Thesis',
            'year'     => 2022,
            'categories' => ['AI', 'Machine Learning', 'Data Science'],
        ],
        [
            'title'    => 'A Blockchain-Based Framework for Academic Credential Verification',
            'abstract' => 'The paper proposes a permissioned blockchain framework that allows employers and institutions to verify academic credentials instantly while preventing tampering or falsification of student records.',
            'authors'  => 'Jerome Villanueva',
            'adviser'  => 'Dr. Ramon Villareal',
            'course'   => 'BSCS',
            'type'     => 'Thesis',
            'year'     => 2023,
            'categories' => ['Blockchain', 'Cybersecurity'],
        ],
        [
            'title'    => 'Development of a Mobile Application for Dengue Hotspot Mapping',
            'abstract' => 'This research presents a crowdsourced mobile mapping application that visualizes reported dengue cases by barangay, helping local health units prioritize fogging and awareness campaigns.',
            'authors'  => 'Angelo Ramirez',
            'adviser'  => 'Engr. Ana Bautista',
            'course'   => 'BSIT',
            'type'     => 'Capstone',
            'year'     => 2024,
            'categories' => ['Mobile App', 'GIS', 'Data Science'],
        ],
    ];

    // Defensive migration for installs created before the research_type column existed.
    try {
        $pdo->exec("ALTER TABLE researches ADD COLUMN research_type ENUM('Thesis', 'Capstone', 'Research Paper', 'Case Study') NOT NULL DEFAULT 'Capstone' AFTER course");
        $steps[] = 'Added research_type column to existing researches table.';
    } catch (PDOException $e) {
        // Column already exists — nothing to do.
    }

    // Defensive migration for installs created before the archive feature existed.
    try {
        $pdo->exec("ALTER TABLE researches
            ADD COLUMN status ENUM('active', 'archived') NOT NULL DEFAULT 'active' AFTER download_count,
            ADD COLUMN archived_at DATETIME NULL AFTER status,
            ADD COLUMN archived_by INT UNSIGNED NULL AFTER archived_at,
            ADD COLUMN archive_reason VARCHAR(255) NULL AFTER archived_by,
            ADD FOREIGN KEY (archived_by) REFERENCES admins(id) ON DELETE SET NULL");
        $steps[] = 'Added archive columns to existing researches table.';
    } catch (PDOException $e) {
        // Columns already exist — nothing to do.
    }

    $checkResearch = $pdo->prepare('SELECT id FROM researches WHERE title = :title');
    $insertResearch = $pdo->prepare(
        'INSERT INTO researches (title, abstract, authors, adviser, course, research_type, year_published,
                                  file_name, original_file_name, file_size, uploaded_by)
         VALUES (:title, :abstract, :authors, :adviser, :course, :type, :year,
                 :file_name, :original_file_name, :file_size, :uploaded_by)'
    );

    $uploadDir = UPLOAD_DIR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($researchList as $r) {
        $checkResearch->execute(['title' => $r['title']]);
        if ($checkResearch->fetch()) {
            continue;
        }

        // Placeholder PDF so the sample "Download" links resolve to a real file.
        $storedName = uniqid('res_', true) . '.pdf';
        $placeholder = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
        file_put_contents($uploadDir . $storedName, $placeholder);

        $insertResearch->execute([
            'title'               => $r['title'],
            'abstract'            => $r['abstract'],
            'authors'             => $r['authors'],
            'adviser'             => $r['adviser'],
            'course'              => $r['course'],
            'type'                => $r['type'],
            'year'                => $r['year'],
            'file_name'           => $storedName,
            'original_file_name'  => $r['title'] . '.pdf',
            'file_size'           => strlen($placeholder),
            'uploaded_by'         => $adminId,
        ]);

        save_research_categories($pdo, (int) $pdo->lastInsertId(), $r['categories'] ?? []);
    }
    $steps[] = 'Sample research entries created with placeholder PDFs.';

} catch (Throwable $e) {
    $fatalError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup | CECT Research Repository</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 640px;">
        <div class="card-body">
            <h4 class="fw-bold mb-3">CECT Research Repository &mdash; Setup</h4>
            <?php if ($fatalError): ?>
                <div class="alert alert-danger"><strong>Setup failed:</strong> <?= htmlspecialchars($fatalError) ?></div>
            <?php else: ?>
                <ul class="list-group mb-3">
                    <?php foreach ($steps as $step): ?>
                        <li class="list-group-item">✅ <?= htmlspecialchars($step) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="alert alert-warning">
                    <strong>Important:</strong> Delete or rename <code>setup.php</code> now that setup is complete.
                </div>
                <a href="login.php" class="btn btn-success">Go to Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
