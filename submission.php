<?php
require __DIR__ . '/includes/functions.php';
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
$s = q('SELECT s.*, p.title, u.username, cp.label FROM submissions s JOIN problems p ON p.id = s.problem_id JOIN users u ON u.id = s.user_id
        LEFT JOIN contest_problems cp ON cp.contest_id = s.contest_id AND cp.problem_id = s.problem_id WHERE s.id = ?', [$id])->fetch();
if (!$s || ($s['user_id'] != $u['id'] && $u['role'] !== 'admin')) { http_response_code(404); exit('Submission not found.'); }
$pending = in_array($s['verdict'], ['Pending', 'Judging'], true);
$page_title = 'Submission #' . $id;
require __DIR__ . '/includes/header.php';
?>
<h3>Submission #<?= (int)$s['id'] ?></h3>
<div class="card mb-3"><div class="card-body">
  <div class="row g-3" id="verdict-box" <?= $pending ? 'data-poll="' . e(url('api/status.php?id=' . $id)) . '"' : '' ?>>
    <div class="col-md-3"><div class="text-muted small">Verdict</div>
      <div class="fs-5"><?= verdict_badge($s['verdict']) ?><?php if ($pending): ?> <span class="spinner-border spinner-border-sm ms-1"></span><?php endif; ?></div></div>
    <div class="col-md-3"><div class="text-muted small">Problem</div><a href="<?= url('problem.php?id=' . $s['problem_id'] . ($s['contest_id'] ? '&contest=' . $s['contest_id'] : '')) ?>"><?= e(($s['label'] ? $s['label'] . '. ' : '') . $s['title']) ?></a></div>
    <div class="col-md-2"><div class="text-muted small">Time</div><?= (int)$s['time_ms'] ?> ms</div>
    <div class="col-md-2"><div class="text-muted small">Score</div><?= (int)$s['score'] ?></div>
    <div class="col-md-2"><div class="text-muted small">Submitted</div><?= fmt_dt($s['submitted_at']) ?></div>
  </div>
  <?php if ($u['role'] === 'admin'): ?><div class="small text-muted mt-2">By <?= e($s['username']) ?></div><?php endif; ?>
  <?php if ($pending): ?><div class="alert alert-secondary mt-3 mb-0 py-2">Your code is being judged… this page updates automatically.</div>
  <?php elseif ($s['judge_message']): ?><h6 class="mt-3">Judge message</h6><pre class="sx-pre mb-0"><?= e($s['judge_message']) ?></pre><?php endif; ?>
</div></div>
<div class="card"><div class="card-body"><h6>Source code (C++)</h6><pre class="code-view mb-0"><?= e($s['source_code']) ?></pre></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
