<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
if (is_post()) {
    csrf_verify();
    $uid = (int)($_POST['id'] ?? 0); $act = $_POST['action'] ?? '';
    if ($uid === (int)$admin['id']) flash('You cannot change or delete your own account here.', 'warning');
    elseif ($act === 'role') { q('UPDATE users SET role = ? WHERE id = ?', [($_POST['role'] ?? '') === 'admin' ? 'admin' : 'student', $uid]); flash('Role updated.'); }
    elseif ($act === 'delete') { q('DELETE FROM users WHERE id = ?', [$uid]); flash('User deleted.'); }
    redirect('admin/users.php');
}
$search = trim((string)($_GET['q'] ?? ''));
$like = '%' . addcslashes($search, '%_\\') . '%';
$rows = q('SELECT u.*, (SELECT COUNT(*) FROM submissions s WHERE s.user_id = u.id) AS subs FROM users u WHERE (? = \'\' OR u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.university_id LIKE ?) ORDER BY u.id DESC LIMIT 300', [$search, $like, $like, $like, $like])->fetchAll();
$page_title = 'Manage Users';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<div class="d-flex justify-content-between mb-3"><h4>Users</h4>
  <form class="d-flex gap-2" method="get"><input class="form-control" name="q" placeholder="Search…" value="<?= e($search) ?>"><button class="btn btn-outline-primary">Search</button></form></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Univ. ID</th><th>Dept.</th><th>Subs</th><th>Role</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e($r['full_name']) ?></td><td><a href="<?= url('profile.php?u=' . urlencode($r['username'])) ?>"><?= e($r['username']) ?></a></td><td class="small"><?= e($r['email']) ?></td><td class="small"><?= e($r['university_id']) ?></td><td class="small"><?= e($r['department']) ?></td><td><?= (int)$r['subs'] ?></td>
  <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <select name="role" class="form-select form-select-sm" <?= $r['id'] == $admin['id'] ? 'disabled' : '' ?>><option value="student" <?= $r['role'] === 'student' ? 'selected' : '' ?>>student</option><option value="admin" <?= $r['role'] === 'admin' ? 'selected' : '' ?>>admin</option></select>
    <button class="btn btn-sm btn-outline-primary" <?= $r['id'] == $admin['id'] ? 'disabled' : '' ?>>Set</button></form></td>
  <td><?php if ($r['id'] != $admin['id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this user and all their submissions?">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
