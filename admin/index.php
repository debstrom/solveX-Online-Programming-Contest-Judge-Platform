<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$page_title = 'Admin Dashboard';
$s = [
  'Users'            => q('SELECT COUNT(*) FROM users')->fetchColumn(),
  'Problems'         => q('SELECT COUNT(*) FROM problems')->fetchColumn(),
  'Contests'         => q('SELECT COUNT(*) FROM contests')->fetchColumn(),
  'Submissions'      => q('SELECT COUNT(*) FROM submissions')->fetchColumn(),
  'Awaiting judge'   => q("SELECT COUNT(*) FROM submissions WHERE verdict IN ('Pending','Judging')")->fetchColumn(),
];
$recent = q('SELECT s.id, s.verdict, s.time_ms, s.submitted_at, u.username, p.title FROM submissions s JOIN users u ON u.id = s.user_id JOIN problems p ON p.id = s.problem_id ORDER BY s.id DESC LIMIT 15')->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<div class="row g-3 mb-4">
  <?php foreach ($s as $k => $v): ?><div class="col-6 col-md"><div class="card stat-card text-center p-3"><div class="num"><?= (int)$v ?></div><div class="text-muted small"><?= e($k) ?></div></div></div><?php endforeach; ?>
</div>
<div class="d-flex justify-content-between"><h5>Live submissions</h5><span class="small text-muted">Reload to refresh</span></div>
<div class="card"><table class="table table-sm table-hover mb-0 align-middle"><thead class="table-light"><tr><th>ID</th><th>User</th><th>Problem</th><th>Verdict</th><th>Time</th><th>When</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td><a href="<?= url('submission.php?id=' . $r['id']) ?>">#<?= (int)$r['id'] ?></a></td><td><?= e($r['username']) ?></td><td><?= e($r['title']) ?></td><td><?= verdict_badge($r['verdict']) ?></td><td><?= (int)$r['time_ms'] ?> ms</td><td class="small text-muted"><?= fmt_dt($r['submitted_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="6" class="text-center text-muted py-3">No submissions yet.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
