<?php
$page_title = 'Home';
require __DIR__ . '/includes/header.php';
$stats = [
  'Problems'    => q('SELECT COUNT(*) FROM problems')->fetchColumn(),
  'Contests'    => q('SELECT COUNT(*) FROM contests')->fetchColumn(),
  'Users'       => q('SELECT COUNT(*) FROM users')->fetchColumn(),
  'Submissions' => q('SELECT COUNT(*) FROM submissions')->fetchColumn(),
];
$running = q('SELECT ' . CONTEST_COLS . ' FROM contests c WHERE c.end_time >= NOW() ORDER BY c.start_time LIMIT 3')->fetchAll();
$ann = q('SELECT * FROM announcements ORDER BY id DESC LIMIT 3')->fetchAll();
?>
<div class="sx-hero p-5 mb-4 text-center">
  <h1 class="display-4 fw-bold">&lt;/&gt; <?= APP_NAME ?></h1>
  <p class="tagline fs-5 mb-1">Code. Compete. Conquer.</p>
  <p class="lead mb-4">Online programming contest &amp; judge platform for university students — submit C++ and get instant verdicts.</p>
  <?php if (!$u): ?>
    <a class="btn btn-warning btn-lg me-2" href="<?= url('register.php') ?>">Get Started</a>
    <a class="btn btn-outline-light btn-lg" href="<?= url('login.php') ?>">Login</a>
  <?php else: ?>
    <a class="btn btn-warning btn-lg me-2" href="<?= url('problems.php') ?>">Solve Problems</a>
    <a class="btn btn-outline-light btn-lg" href="<?= url('contests.php') ?>">Join a Contest</a>
  <?php endif; ?>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $k => $v): ?>
    <div class="col-6 col-md-3"><div class="card stat-card text-center p-3"><div class="num"><?= (int)$v ?></div><div class="text-muted"><?= e($k) ?></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-4">
  <div class="col-lg-7">
    <h4>Upcoming &amp; Running Contests</h4>
    <?php if (!$running): ?><p class="text-muted">No contests scheduled right now.</p><?php endif; ?>
    <?php foreach ($running as $c): $st = contest_status($c); ?>
      <div class="card mb-2"><div class="card-body d-flex justify-content-between align-items-center">
        <div><a class="fw-semibold" href="<?= url('contest.php?id=' . $c['id']) ?>"><?= e($c['title']) ?></a>
          <div class="small text-muted"><?= fmt_dt($c['start_ts']) ?> → <?= fmt_dt($c['end_ts']) ?></div></div>
        <span class="badge text-bg-<?= $st === 'Running' ? 'success' : 'primary' ?>"><?= $st ?></span>
      </div></div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-5">
    <h4>Announcements</h4>
    <?php foreach ($ann as $a): ?>
      <div class="card mb-2"><div class="card-body"><div class="fw-semibold"><?= e($a['title']) ?></div>
        <div class="small text-muted mb-1"><?= fmt_dt($a['created_at']) ?></div><div><?= nl2br(e($a['body'])) ?></div></div></div>
    <?php endforeach; if (!$ann): ?><p class="text-muted">Nothing yet.</p><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
