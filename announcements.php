<?php
$page_title = 'Announcements';
require __DIR__ . '/includes/header.php';
$rows = q('SELECT a.*, u.username FROM announcements a LEFT JOIN users u ON u.id = a.created_by ORDER BY a.id DESC LIMIT 100')->fetchAll();
?>
<h2>Announcements</h2>
<?php foreach ($rows as $a): ?>
  <div class="card mb-3"><div class="card-body"><h5><?= e($a['title']) ?></h5>
    <div class="small text-muted mb-2"><?= fmt_dt($a['created_at']) ?><?= $a['username'] ? ' · ' . e($a['username']) : '' ?></div>
    <div><?= nl2br(e($a['body'])) ?></div></div></div>
<?php endforeach; if (!$rows): ?><p class="text-muted">No announcements yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
