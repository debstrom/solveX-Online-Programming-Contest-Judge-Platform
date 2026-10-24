<?php
require __DIR__ . '/includes/functions.php';
$me = require_login();
$name = trim((string)($_GET['u'] ?? ''));
$who = $name !== '' ? q('SELECT id, full_name, username, university_id, department, role, created_at FROM users WHERE username = ?', [$name])->fetch() : $me;
if (!$who) { http_response_code(404); exit('User not found.'); }
$own = $who['id'] == $me['id'];
$err = [];
if ($own && is_post()) {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'info') {
        $fn = trim((string)$_POST['full_name']); $dp = trim((string)$_POST['department']);
        if (strlen($fn) < 3 || strlen($fn) > 100 || $dp === '' || strlen($dp) > 80) $err[] = 'Please enter a valid name and department.';
        else { q('UPDATE users SET full_name = ?, department = ? WHERE id = ?', [$fn, $dp, $me['id']]); flash('Profile updated.'); redirect('profile.php'); }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $row = q('SELECT password_hash FROM users WHERE id = ?', [$me['id']])->fetch();
        $new = (string)$_POST['new']; 
        if (!password_verify((string)$_POST['old'], $row['password_hash'])) $err[] = 'Current password is incorrect.';
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $err[] = 'New password needs 8+ characters with a letter and a digit.';
        elseif ($new !== (string)$_POST['new2']) $err[] = 'New passwords do not match.';
        else { q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]); session_regenerate_id(true); flash('Password changed.'); redirect('profile.php'); }
    }
}
$uid = $who['id'];
$total = (int)q('SELECT COUNT(*) FROM submissions WHERE user_id = ?', [$uid])->fetchColumn();
$ac = (int)q("SELECT COUNT(*) FROM submissions WHERE user_id = ? AND verdict = 'Accepted'", [$uid])->fetchColumn();
$solved = q("SELECT DISTINCT p.id, p.title FROM submissions s JOIN problems p ON p.id = s.problem_id WHERE s.user_id = ? AND s.verdict = 'Accepted' ORDER BY p.id", [$uid])->fetchAll();
$contests = (int)q('SELECT COUNT(*) FROM registrations WHERE user_id = ?', [$uid])->fetchColumn();
$points = (int)q("SELECT COALESCE(SUM(score),0) FROM submissions WHERE user_id = ? AND contest_id IS NOT NULL", [$uid])->fetchColumn();
$breakdown = q("SELECT verdict, COUNT(*) n FROM submissions WHERE user_id = ? AND verdict NOT IN ('Pending','Judging') GROUP BY verdict ORDER BY n DESC", [$uid])->fetchAll();
$recent = q('SELECT s.id, s.verdict, s.submitted_at, p.title FROM submissions s JOIN problems p ON p.id = s.problem_id WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 8', [$uid])->fetchAll();
$page_title = $who['username'] . ' — Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h4 class="mb-0"><?= e($who['full_name']) ?></h4><div class="text-muted mb-2">@<?= e($who['username']) ?> <?= $who['role'] === 'admin' ? '<span class="badge text-bg-dark">Admin</span>' : '' ?></div>
      <div class="small">University ID: <b><?= e($who['university_id']) ?></b></div>
      <div class="small">Department: <b><?= e($who['department']) ?></b></div>
      <div class="small">Joined: <?= fmt_dt($who['created_at']) ?></div>
    </div></div>
    <?php if ($own): ?>
    <?php foreach ($err as $m): ?><div class="alert alert-danger mt-3 py-2"><?= e($m) ?></div><?php endforeach; ?>
    <div class="card mt-3"><div class="card-body"><h6>Edit profile</h6>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="info">
        <input class="form-control mb-2" name="full_name" value="<?= e($who['full_name']) ?>" required>
        <input class="form-control mb-2" name="department" value="<?= e($who['department']) ?>" required>
        <button class="btn btn-sm btn-primary">Save</button></form></div></div>
    <div class="card mt-3"><div class="card-body"><h6>Change password</h6>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <input type="password" class="form-control mb-2" name="old" placeholder="Current password" required>
        <input type="password" class="form-control mb-2" name="new" placeholder="New password" required minlength="8">
        <input type="password" class="form-control mb-2" name="new2" placeholder="Repeat new password" required minlength="8">
        <button class="btn btn-sm btn-primary">Change</button></form></div></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-8">
    <div class="row g-3 mb-3">
      <?php foreach (['Problems solved' => count($solved), 'Submissions' => $total, 'Acceptance' => ($total ? round($ac * 100 / $total) : 0) . '%', 'Contest points' => $points, 'Contests joined' => $contests] as $k => $v): ?>
        <div class="col-6 col-md"><div class="card stat-card text-center p-2"><div class="num fs-3"><?= e($v) ?></div><div class="small text-muted"><?= e($k) ?></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="row g-3">
      <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Verdict breakdown</h6>
        <?php foreach ($breakdown as $b): ?><div class="d-flex justify-content-between mb-1"><?= verdict_badge($b['verdict']) ?><span><?= (int)$b['n'] ?></span></div><?php endforeach; if (!$breakdown): ?><span class="text-muted small">No judged submissions yet.</span><?php endif; ?></div></div></div>
      <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Solved problems</h6>
        <?php foreach ($solved as $s): ?><a class="badge text-bg-success text-decoration-none me-1 mb-1" href="<?= url('problem.php?id=' . $s['id']) ?>"><?= e($s['title']) ?></a><?php endforeach; if (!$solved): ?><span class="text-muted small">Nothing solved yet.</span><?php endif; ?></div></div></div>
    </div>
    <?php if ($own): ?>
    <div class="card mt-3"><div class="card-body"><h6>Recent submissions</h6><table class="table table-sm mb-0"><tbody>
      <?php foreach ($recent as $r): ?><tr><td><a href="<?= url('submission.php?id=' . $r['id']) ?>">#<?= (int)$r['id'] ?></a></td><td><?= e($r['title']) ?></td><td><?= verdict_badge($r['verdict']) ?></td><td class="small text-muted"><?= fmt_dt($r['submitted_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
