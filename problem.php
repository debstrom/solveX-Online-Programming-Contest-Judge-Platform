<?php
require __DIR__ . '/includes/functions.php';
$pid = (int)($_GET['id'] ?? 0); $cid = (int)($_GET['contest'] ?? 0);
$p = q('SELECT * FROM problems WHERE id = ?', [$pid])->fetch();
if (!$p) { http_response_code(404); exit('Problem not found.'); }
$u = current_user(); $contest = null; $can_submit = false; $note = '';
if ($cid) {
    $contest = get_contest($cid);
    $cp = q('SELECT label, points FROM contest_problems WHERE contest_id = ? AND problem_id = ?', [$cid, $pid])->fetch();
    if (!$contest || !$cp) { http_response_code(404); exit('This problem is not part of that contest.'); }
    $st = contest_status($contest);
    $is_admin = $u && $u['role'] === 'admin';
    if ($st === 'Upcoming' && !$is_admin) { flash('The contest has not started yet.', 'warning'); redirect('contest.php?id=' . $cid); }
    if ($st === 'Running' && !$is_admin) {
        $u = require_login();
        if (!q('SELECT 1 FROM registrations WHERE contest_id = ? AND user_id = ?', [$cid, $u['id']])->fetch()) { flash('Register for the contest to see its problems.', 'warning'); redirect('contest.php?id=' . $cid); }
    }
    if ($st === 'Running') {
        $registered = $u && q('SELECT 1 FROM registrations WHERE contest_id = ? AND user_id = ?', [$cid, $u['id']])->fetch();
        $can_submit = (bool)$registered; if (!$registered) $note = 'Only registered participants can submit in a running contest.';
    } else { $cid = 0; $note = 'This contest is not running; submissions here count as practice.'; $can_submit = problem_open_for_practice($pid); }
} else {
    if (!problem_open_for_practice($pid) && !($u && $u['role'] === 'admin')) { http_response_code(403); exit('This problem is only available inside its contest.'); }
    $can_submit = true;
}
$samples = q('SELECT input_data, expected_output FROM test_cases WHERE problem_id = ? AND is_sample = 1 ORDER BY id', [$pid])->fetchAll();
$history = $u ? q('SELECT id, verdict, time_ms, submitted_at FROM submissions WHERE user_id = ? AND problem_id = ? ORDER BY id DESC LIMIT 8', [$u['id'], $pid])->fetchAll() : [];
$template = "#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    ios::sync_with_stdio(false);\n    cin.tie(nullptr);\n\n    // write your solution here\n\n    return 0;\n}\n";
$page_title = $p['title'];
require __DIR__ . '/includes/header.php';
?>
<?php if ($contest): ?><div class="mb-2"><a href="<?= url('contest.php?id=' . $contest['id']) ?>">&larr; <?= e($contest['title']) ?></a></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h3><?= $contest && isset($cp) ? e($cp['label']) . '. ' : '' ?><?= e($p['title']) ?></h3>
      <div class="mb-3"><?= diff_badge($p['difficulty']) ?>
        <span class="badge text-bg-light border">⏱ <?= (int)$p['time_limit_ms'] ?> ms</span>
        <span class="badge text-bg-light border">💾 <?= (int)$p['memory_limit_mb'] ?> MB</span>
        <?php if ($contest && isset($cp)): ?><span class="badge text-bg-primary"><?= (int)$cp['points'] ?> points</span><?php endif; ?></div>
      <h6>Statement</h6><p class="statement"><?= e($p['statement']) ?></p>
      <?php if ($p['input_format']): ?><h6>Input</h6><p class="statement"><?= e($p['input_format']) ?></p><?php endif; ?>
      <?php if ($p['output_format']): ?><h6>Output</h6><p class="statement"><?= e($p['output_format']) ?></p><?php endif; ?>
      <?php if ($samples): foreach ($samples as $i => $s): ?>
        <h6>Sample <?= $i + 1 ?> — Input</h6><pre class="sx-pre"><?= e($s['input_data']) ?></pre>
        <h6>Sample <?= $i + 1 ?> — Output</h6><pre class="sx-pre"><?= e($s['expected_output']) ?></pre>
      <?php endforeach; elseif ($p['sample_input'] !== null): ?>
        <h6>Sample Input</h6><pre class="sx-pre"><?= e($p['sample_input']) ?></pre><h6>Sample Output</h6><pre class="sx-pre"><?= e($p['sample_output']) ?></pre>
      <?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card mb-3"><div class="card-body">
      <h5>Submit solution <span class="badge text-bg-secondary">C++17</span></h5>
      <?php if ($note): ?><div class="alert alert-info py-2 small"><?= e($note) ?></div><?php endif; ?>
      <?php if (!$u): ?>
        <p class="mb-0">Please <a href="<?= url('login.php') ?>">log in</a> to submit code.</p>
      <?php elseif (!$can_submit): ?>
        <p class="text-muted mb-0">Submissions are not available for this problem right now.</p>
      <?php else: ?>
        <form method="post" action="<?= url('submit.php') ?>"><?= csrf_field() ?>
          <input type="hidden" name="problem_id" value="<?= (int)$pid ?>"><input type="hidden" name="contest_id" value="<?= (int)$cid ?>">
          <textarea class="form-control code-input mb-3" name="code" spellcheck="false" required maxlength="<?= MAX_SOURCE_BYTES ?>"><?= e($template) ?></textarea>
          <button class="btn btn-success">Submit</button>
        </form>
      <?php endif; ?>
    </div></div>
    <?php if ($history): ?>
    <div class="card"><div class="card-body"><h6>Your recent submissions</h6>
      <table class="table table-sm mb-0"><tbody>
      <?php foreach ($history as $h): ?>
        <tr><td><a href="<?= url('submission.php?id=' . $h['id']) ?>">#<?= (int)$h['id'] ?></a></td><td><?= verdict_badge($h['verdict']) ?></td><td><?= (int)$h['time_ms'] ?> ms</td><td class="small text-muted"><?= fmt_dt($h['submitted_at']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div></div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
