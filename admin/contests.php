<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
if (is_post()) {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') { q('DELETE FROM contests WHERE id = ?', [(int)$_POST['id']]); flash('Contest deleted.'); }
    redirect('admin/contests.php');
}
$rows = q('SELECT ' . CONTEST_COLS . ', (SELECT COUNT(*) FROM registrations r WHERE r.contest_id = c.id) AS regs, (SELECT COUNT(*) FROM contest_problems cp WHERE cp.contest_id = c.id) AS probs FROM contests c ORDER BY c.start_time DESC')->fetchAll();
$page_title = 'Manage Contests';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<div class="d-flex justify-content-between mb-3"><h4>Contests</h4><a class="btn btn-primary" href="<?= url('admin/contest_form.php') ?>">+ New contest</a></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>#</th><th>Title</th><th>Start</th><th>End</th><th>Status</th><th>Problems</th><th>Participants</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><a href="<?= url('contest.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></td><td class="small"><?= fmt_dt($r['start_ts']) ?></td><td class="small"><?= fmt_dt($r['end_ts']) ?></td>
  <td><?= contest_status($r) ?></td><td><?= (int)$r['probs'] ?></td><td><?= (int)$r['regs'] ?></td>
  <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/contest_form.php?id=' . $r['id']) ?>">Edit / Problems</a>
    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this contest and its registrations?">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
