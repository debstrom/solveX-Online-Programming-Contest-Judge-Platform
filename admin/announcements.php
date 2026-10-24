<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
if (is_post()) {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'add') {
        $t = trim((string)$_POST['title']); $b = trim((string)$_POST['body']);
        if ($t === '' || $b === '' || strlen($t) > 150) flash('Title and message are required.', 'danger');
        else { q('INSERT INTO announcements (title, body, created_by) VALUES (?,?,?)', [$t, $b, $admin['id']]); flash('Announcement published.'); }
    } elseif (($_POST['action'] ?? '') === 'delete') { q('DELETE FROM announcements WHERE id = ?', [(int)$_POST['id']]); flash('Announcement deleted.'); }
    redirect('admin/announcements.php');
}
$rows = q('SELECT * FROM announcements ORDER BY id DESC')->fetchAll();
$page_title = 'Announcements';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<h4>Announcements</h4>
<form method="post" class="card card-body mb-4"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <input class="form-control mb-2" name="title" placeholder="Title" maxlength="150" required><textarea class="form-control mb-2" name="body" rows="3" placeholder="Message" required></textarea>
  <div><button class="btn btn-primary">Publish</button></div></form>
<?php foreach ($rows as $a): ?><div class="card mb-2"><div class="card-body d-flex justify-content-between"><div><div class="fw-semibold"><?= e($a['title']) ?></div><div class="small text-muted"><?= fmt_dt($a['created_at']) ?></div><div><?= nl2br(e($a['body'])) ?></div></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete announcement?">Delete</button></form></div></div><?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
