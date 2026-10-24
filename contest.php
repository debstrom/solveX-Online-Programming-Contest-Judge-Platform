<?php
require __DIR__ . '/includes/functions.php';
$cid = (int)($_GET['id'] ?? 0);
$c = get_contest($cid);
if (!$c) { http_response_code(404); exit('Contest not found.'); }
$u = current_user(); $st = contest_status($c);
$registered = $u && q('SELECT 1 FROM registrations WHERE contest_id = ? AND user_id = ?', [$cid, $u['id']])->fetch();
$is_admin = $u && $u['role'] === 'admin';
$show_problems = $st === 'Ended' || (($registered || $is_admin) && $st === 'Running');
$problems = $show_problems ? q('SELECT cp.label, cp.points, p.id, p.title, p.difficulty,
    (SELECT COUNT(*) FROM submissions s WHERE s.contest_id = cp.contest_id AND s.problem_id = p.id AND s.user_id = ? AND s.verdict = \'Accepted\') AS me_ac
    FROM contest_problems cp JOIN problems p ON p.id = cp.problem_id WHERE cp.contest_id = ? ORDER BY cp.label', [$u['id'] ?? 0, $cid])->fetchAll() : [];
$reg_count = q('SELECT COUNT(*) FROM registrations WHERE contest_id = ?', [$cid])->fetchColumn();
$page_title = $c['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="card mb-4"><div class="card-body">
  <div class="d-flex flex-wrap justify-content-between align-items-start">
    <div><h2 class="mb-1"><?= e($c['title']) ?></h2>
      <span class="badge text-bg-<?= ['Running' => 'success', 'Upcoming' => 'primary', 'Ended' => 'secondary'][$st] ?>"><?= $st ?></span>
      <span class="text-muted small ms-2"><?= fmt_dt($c['start_ts']) ?> → <?= fmt_dt($c['end_ts']) ?> · <?= (int)$reg_count ?> participant(s)</span></div>
    <div class="text-end">
      <?php if ($st !== 'Ended'): ?>
        <div class="small text-muted"><?= $st === 'Upcoming' ? 'Starts in' : 'Ends in' ?></div>
        <div class="countdown" data-reload="1" data-seconds="<?= max(0, ($st === 'Upcoming' ? $c['start_ts'] : $c['end_ts']) - $c['now_ts']) ?>">--:--:--</div>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($c['description']): ?><p class="mt-3 mb-0 statement"><?= e($c['description']) ?></p><?php endif; ?>
  <div class="mt-3">
    <?php if ($st !== 'Ended'): ?>
      <?php if ($registered): ?><span class="badge text-bg-success fs-6">You are registered</span>
      <?php elseif ($u): ?>
        <form method="post" action="<?= url('contest_register.php') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="contest_id" value="<?= $cid ?>"><button class="btn btn-primary">Register for this contest</button></form>
      <?php else: ?><a class="btn btn-primary" href="<?= url('login.php') ?>">Login to register</a><?php endif; ?>
    <?php endif; ?>
    <a class="btn btn-outline-dark" href="<?= url('leaderboard.php?id=' . $cid) ?>">Leaderboard</a>
  </div>
</div></div>
<h4>Problems</h4>
<?php if (!$show_problems): ?>
  <div class="alert alert-info"><?= $st === 'Upcoming' ? 'Problems will be visible once the contest starts.' : 'Register for the contest to see its problems.' ?></div>
<?php else: ?>
<div class="card"><table class="table table-hover mb-0 align-middle">
  <thead class="table-light"><tr><th style="width:60px">#</th><th>Title</th><th>Difficulty</th><th>Points</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($problems as $p): ?>
    <tr><td class="fw-bold"><?= e($p['label']) ?></td><td><a href="<?= url('problem.php?id=' . $p['id'] . '&contest=' . $cid) ?>"><?= e($p['title']) ?></a></td>
      <td><?= diff_badge($p['difficulty']) ?></td><td><?= (int)$p['points'] ?></td><td><?= $p['me_ac'] ? '<span class="badge text-bg-success">Solved</span>' : '' ?></td></tr>
  <?php endforeach; if (!$problems): ?><tr><td colspan="5" class="text-muted text-center py-3">No problems added yet.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
