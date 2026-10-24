<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
if (is_post()) {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') { q('DELETE FROM problems WHERE id = ?', [(int)$_POST['id']]); flash('Problem deleted (with its test cases and submissions).'); }
    redirect('admin/problems.php');
}
$rows = q('SELECT p.*, (SELECT COUNT(*) FROM test_cases t WHERE t.problem_id = p.id) AS tcs, (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id) AS subs FROM problems p ORDER BY p.id DESC')->fetchAll();
$page_title = 'Manage Problems';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<div class="d-flex justify-content-between mb-3"><h4>Problems</h4><a class="btn btn-primary" href="<?= url('admin/problem_form.php') ?>">+ New problem</a></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>#</th><th>Title</th><th>Difficulty</th><th>Limits</th><th>Visibility</th><th>Tests</th><th>Subs</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><a href="<?= url('problem.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></td><td><?= diff_badge($r['difficulty']) ?></td>
  <td class="small"><?= (int)$r['time_limit_ms'] ?> ms / <?= (int)$r['memory_limit_mb'] ?> MB</td><td><?= $r['is_public'] ? 'Public' : '<span class="text-muted">Contest only</span>' ?></td>
  <td><?= (int)$r['tcs'] ?></td><td><?= (int)$r['subs'] ?></td>
  <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/testcases.php?problem_id=' . $r['id']) ?>">Test cases</a>
    <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/problem_form.php?id=' . $r['id']) ?>">Edit</a>
    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this problem and all its submissions?">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
