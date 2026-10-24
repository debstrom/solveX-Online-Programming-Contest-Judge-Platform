<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
if (is_post()) {
    csrf_verify();
    $act = $_POST['action'] ?? '';
    if ($act === 'rejudge') {
        $sid = (int)$_POST['id'];
        q("UPDATE submissions SET verdict = 'Pending', time_ms = 0, score = 0, judge_message = NULL, judged_at = NULL WHERE id = ?", [$sid]);
        spawn_judge($sid); flash("Submission #$sid queued for re-judging.");
    } elseif ($act === 'rejudge_pending') {
        $ids = q("SELECT id FROM submissions WHERE verdict = 'Pending' ORDER BY id LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $i) spawn_judge((int)$i);
        flash(count($ids) . ' pending submission(s) sent to the judge.');
    }
    redirect('admin/submissions.php');
}
$fv = (string)($_GET['verdict'] ?? ''); $fu = trim((string)($_GET['user'] ?? '')); $fc = (int)($_GET['contest'] ?? 0);
$w = ['1=1']; $pr = [];
if ($fv !== '') { $w[] = 's.verdict = ?'; $pr[] = $fv; }
if ($fu !== '') { $w[] = 'u.username = ?'; $pr[] = $fu; }
if ($fc) { $w[] = 's.contest_id = ?'; $pr[] = $fc; }
$rows = q('SELECT s.id, s.verdict, s.time_ms, s.score, s.submitted_at, u.username, p.title, c.title AS ctitle FROM submissions s JOIN users u ON u.id = s.user_id JOIN problems p ON p.id = s.problem_id LEFT JOIN contests c ON c.id = s.contest_id WHERE ' . implode(' AND ', $w) . ' ORDER BY s.id DESC LIMIT 200', $pr)->fetchAll();
$contests = q('SELECT id, title FROM contests ORDER BY id DESC')->fetchAll();
$page_title = 'Monitor Submissions';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<div class="d-flex justify-content-between mb-3"><h4>Submissions</h4>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="rejudge_pending"><button class="btn btn-outline-warning">Push stuck “Pending” to judge</button></form></div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-3"><select class="form-select" name="verdict"><option value="">Any verdict</option><?php foreach (['Pending', 'Judging', 'Accepted', 'Wrong Answer', 'Compilation Error', 'Time Limit Exceeded', 'Runtime Error'] as $v): ?><option <?= $fv === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><input class="form-control" name="user" placeholder="Username" value="<?= e($fu) ?>"></div>
  <div class="col-md-3"><select class="form-select" name="contest"><option value="0">Any contest</option><?php foreach ($contests as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $fc === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="<?= url('admin/submissions.php') ?>">Reset</a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle"><thead class="table-light"><tr><th>ID</th><th>User</th><th>Problem</th><th>Contest</th><th>Verdict</th><th>Time</th><th>Score</th><th>When</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><a href="<?= url('submission.php?id=' . $r['id']) ?>">#<?= (int)$r['id'] ?></a></td><td><?= e($r['username']) ?></td><td><?= e($r['title']) ?></td><td class="small"><?= e($r['ctitle'] ?? 'Practice') ?></td><td><?= verdict_badge($r['verdict']) ?></td><td><?= (int)$r['time_ms'] ?> ms</td><td><?= (int)$r['score'] ?></td><td class="small text-muted"><?= fmt_dt($r['submitted_at']) ?></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="rejudge"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-secondary">Rejudge</button></form></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-3">No submissions match.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
