<?php
/**
 * index.php — Public landing page (entry point)
 * Introduces the archive and routes visitors to login.php / register.php.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

if (isAdmin())   redirect(baseUrl('admin/dashboard.php'));
if (isStudent()) {
    $status = $_SESSION['student_status'] ?? '';
    if ($status === 'active')    redirect(baseUrl('home.php'));
    if ($status === 'pending')   redirect(baseUrl('pending.php'));
    if ($status === 'suspended') redirect(baseUrl('suspended.php'));
}

// Live archive stats. The landing page must still render if the DB is unavailable.
$stats = null;
try {
    $pdo   = getDB();
    $row   = $pdo->query('SELECT COUNT(*) AS total, MIN(year) AS first_year FROM researches')->fetch();
    $stats = [
        'total'      => (int) ($row['total'] ?? 0),
        'first_year' => $row['first_year'] ?? '2020',
    ];
} catch (Throwable $e) {
    $stats = null;
}

$campusImg = baseUrl('assets/images/campus.jpg');
$year      = date('Y');

$programs = [
    ['icon' => 'bi-pc-display', 'name' => 'Information Technology', 'desc' => 'Web and mobile systems, information management, and enterprise solutions built for real clients.'],
    ['icon' => 'bi-cpu',        'name' => 'Computer Engineering',   'desc' => 'IoT devices, embedded systems, and hardware-software integration projects.'],
    ['icon' => 'bi-braces',     'name' => 'Computer Science',       'desc' => 'Machine learning, data analytics, algorithms, and cybersecurity research.'],
];

$features = [
    ['icon' => 'bi-search',           'title' => 'Search and filter',    'desc' => 'Find studies instantly by title, author, keyword, program, research type, or year.'],
    ['icon' => 'bi-shield-lock',      'title' => 'Department-exclusive', 'desc' => 'Only registered, admin-approved CECT students and faculty can access the collection.'],
    ['icon' => 'bi-file-earmark-pdf', 'title' => 'Secure PDF access',    'desc' => 'Full manuscripts are delivered through protected downloads, never public links.'],
    ['icon' => 'bi-clock-history',    'title' => 'Archived since 2020',  'desc' => 'A growing record of capstone and research work, preserved for future cohorts.'],
];

$steps = [
    ['title' => 'Register',     'desc' => 'Create an account using your Student ID, course, and university email.'],
    ['title' => 'Get approved', 'desc' => 'A CECT administrator verifies your enrollment and activates your account.'],
    ['title' => 'Explore',      'desc' => 'Sign in to browse, read, and download past research for reference.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="The official digital repository of research and capstone projects from the College of Engineering, Computer and Technology, Wesleyan University-Philippines.">
  <meta name="theme-color" content="#008000">
  <title>CECT Research Archive — Wesleyan University-Philippines</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-page">

<a class="visually-hidden-focusable landing-skip" href="#main">Skip to content</a>

<header class="landing-nav">
  <nav class="container d-flex align-items-center justify-content-between py-3" aria-label="Main">
    <a href="index.php" class="landing-brand d-flex align-items-center gap-2 text-decoration-none">
      <span class="landing-brand-mark"><i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i></span>
      <span class="d-flex flex-column lh-sm">
        <span class="fw-bold">CECT Research Archive</span>
        <span class="landing-brand-sub">Wesleyan University-Philippines</span>
      </span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="#about" class="landing-link d-none d-md-inline">About</a>
      <a href="#access" class="landing-link d-none d-md-inline">How it works</a>
      <a href="login.php" class="btn btn-sm landing-btn-outline">Sign in</a>
    </div>
  </nav>
</header>

<main id="main">

  <section class="landing-hero" style="background-image:url('<?= e($campusImg) ?>')">
    <div class="landing-hero-overlay"></div>
    <div class="container position-relative">
      <div class="landing-hero-content">
        <span class="landing-eyebrow">College of Engineering, Computer and Technology</span>
        <h1 class="landing-title">Every CECT study, preserved and within reach.</h1>
        <p class="landing-lead">
          The official digital repository of research and capstone projects from the IT,
          Computer Engineering, and Computer Science programs of Wesleyan University-Philippines.
        </p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="register.php" class="btn btn-lg landing-btn-gold">
            Request access <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </a>
          <a href="login.php" class="btn btn-lg landing-btn-ghost">I already have an account</a>
        </div>
      </div>

      <?php if ($stats && $stats['total'] > 0): ?>
      <dl class="landing-stats">
        <div>
          <dt>Research papers</dt>
          <dd><?= number_format($stats['total']) ?></dd>
        </div>
        <div>
          <dt>Programs</dt>
          <dd>3</dd>
        </div>
        <div>
          <dt>Archived since</dt>
          <dd><?= e((string) $stats['first_year']) ?></dd>
        </div>
      </dl>
      <?php endif; ?>
    </div>
  </section>

  <section id="about" class="landing-section">
    <div class="container">
      <div class="landing-section-head">
        <span class="landing-kicker">Three programs, one archive</span>
        <h2 class="landing-h2">Built for the CECT community</h2>
        <p class="landing-muted">
          A centralized home for the work of our students, so every new cohort can learn from
          and build on what came before.
        </p>
      </div>
      <div class="row g-4">
        <?php foreach ($programs as $p): ?>
        <div class="col-md-4">
          <article class="landing-card h-100">
            <span class="landing-icon"><i class="bi <?= e($p['icon']) ?>" aria-hidden="true"></i></span>
            <h3 class="landing-h3"><?= e($p['name']) ?></h3>
            <p class="landing-muted mb-0"><?= e($p['desc']) ?></p>
          </article>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="landing-section landing-section-alt">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-5">
          <span class="landing-kicker">What you get</span>
          <h2 class="landing-h2">A research library that respects the work</h2>
          <p class="landing-muted">
            Designed with the department to keep student research discoverable for peers
            while protecting it from public redistribution.
          </p>
        </div>
        <div class="col-lg-7">
          <ul class="landing-features list-unstyled m-0">
            <?php foreach ($features as $f): ?>
            <li>
              <span class="landing-icon landing-icon-sm"><i class="bi <?= e($f['icon']) ?>" aria-hidden="true"></i></span>
              <div>
                <h3 class="landing-h3 mb-1"><?= e($f['title']) ?></h3>
                <p class="landing-muted mb-0"><?= e($f['desc']) ?></p>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section id="access" class="landing-section">
    <div class="container">
      <div class="landing-section-head">
        <span class="landing-kicker">How it works</span>
        <h2 class="landing-h2">Getting access takes three steps</h2>
      </div>
      <ol class="list-unstyled row g-4 m-0">
        <?php foreach ($steps as $i => $s): ?>
        <li class="col-md-4">
          <div class="landing-step h-100">
            <span class="landing-step-num"><?= $i + 1 ?></span>
            <h3 class="landing-h3"><?= e($s['title']) ?></h3>
            <p class="landing-muted mb-0"><?= e($s['desc']) ?></p>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="landing-cta">
    <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
      <div>
        <h2 class="landing-h2 text-white mb-2">Ready to explore the archive?</h2>
        <p class="mb-0 text-white-50">Open to registered and approved CECT students and faculty.</p>
      </div>
      <div class="d-flex flex-wrap gap-3">
        <a href="register.php" class="btn btn-lg landing-btn-gold">Register now</a>
        <a href="login.php" class="btn btn-lg landing-btn-ghost">Sign in</a>
      </div>
    </div>
  </section>

</main>

<footer class="landing-footer">
  <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 py-4">
    <span>&copy; <?= $year ?> College of Engineering, Computer and Technology</span>
    <span>Wesleyan University-Philippines &bull; Nueva Ecija</span>
  </div>
</footer>

</body>
</html>
