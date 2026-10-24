<?php
require __DIR__ . '/includes/functions.php';
$u = require_login();
$page_title = 'My Submissions';
$per = 25; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)q('SELECT COUNT(*) FROM submissions WHERE user_id = ?', [$u['id']])->fetchColumn();
$rows = q('SELECT s.id, s.verdict, s.time_ms, s.score, s.submitted_at, p.id AS pid, p.title, c.title AS ctitle FROM submissions s
           JOIN problems p ON p.id = s.problem_id LEFT JOIN contests c ON c.id = s.contest_id
           WHERE s.user_id = ? ORDER BY s.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), [$u['id']])->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<h2>My Submission History</h2>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
  <thead class="table-light"><tr><th>ID</th><th>Problem</th><th>Contest</th><th>Verdict</th><th>Time</th><th>Score</th><th>Submitted</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><a href="<?= url('submission.php?id=' . $r['id']) ?>">#<?= (int)$r['id'] ?></a></td>
      <td><a href="<?= url('problem.php?id=' . $r['pid']) ?>"><?= e($r['title']) ?></a></td>
      <td class="small"><?= e($r['ctitle'] ?? 'Practice') ?></td><td><?= verdict_badge($r['verdict']) ?></td>
      <td><?= (int)$r['time_ms'] ?> ms</td><td><?= (int)$r['score'] ?></td><td class="small text-muted"><?= fmt_dt($r['submitted_at']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">You have not submitted anything yet.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php if ($total > $per): ?>
<nav class="mt-3"><ul class="pagination">
  <?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?>
</ul></nav><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
