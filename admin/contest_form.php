<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$id = (int)($_GET['id'] ?? 0);
$c = ['title' => '', 'description' => '', 'start_time' => date('Y-m-d H:i:s', time() + 3600), 'end_time' => date('Y-m-d H:i:s', time() + 3600 * 4)];
if ($id) { $c = q('SELECT * FROM contests WHERE id = ?', [$id])->fetch() ?: exit('Contest not found.'); }
$err = [];
if (is_post()) {
    csrf_verify();
    $act = $_POST['action'] ?? 'save';
    if ($act === 'save') {
        $c['title'] = trim((string)$_POST['title']); $c['description'] = trim((string)$_POST['description']);
        $s = strtotime((string)$_POST['start_time']); $en = strtotime((string)$_POST['end_time']);
        if ($c['title'] === '' || strlen($c['title']) > 150) $err[] = 'Title is required (max 150 characters).';
        if (!$s || !$en) $err[] = 'Please enter valid start and end times.';
        elseif ($en <= $s) $err[] = 'End time must be after the start time.';
        if (!$err) {
            $c['start_time'] = date('Y-m-d H:i:s', $s); $c['end_time'] = date('Y-m-d H:i:s', $en);
            if ($id) { q('UPDATE contests SET title=?, description=?, start_time=?, end_time=? WHERE id=?', [$c['title'], $c['description'], $c['start_time'], $c['end_time'], $id]); flash('Contest updated.'); }
            else { q('INSERT INTO contests (title, description, start_time, end_time, created_by) VALUES (?,?,?,?,?)', [$c['title'], $c['description'], $c['start_time'], $c['end_time'], $admin['id']]); $id = (int)db()->lastInsertId(); flash('Contest created. Now add problems to it.'); }
            redirect('admin/contest_form.php?id=' . $id);
        }
    } elseif ($id && $act === 'add_problem') {
        $label = strtoupper(trim((string)$_POST['label'])); $pts = (int)$_POST['points']; $pid = (int)$_POST['problem_id'];
        if (!preg_match('/^[A-Z0-9]{1,5}$/', $label) || $pts < 1 || !q('SELECT 1 FROM problems WHERE id = ?', [$pid])->fetch()) flash('Enter a valid problem, label (A–Z, 0–9) and points.', 'danger');
        elseif (q('SELECT 1 FROM contest_problems WHERE contest_id = ? AND (problem_id = ? OR label = ?)', [$id, $pid, $label])->fetch()) flash('That problem or label is already used in this contest.', 'danger');
        else { q('INSERT INTO contest_problems (contest_id, problem_id, label, points) VALUES (?,?,?,?)', [$id, $pid, $label, $pts]); flash('Problem added to contest.'); }
        redirect('admin/contest_form.php?id=' . $id);
    } elseif ($id && $act === 'remove_problem') {
        q('DELETE FROM contest_problems WHERE id = ? AND contest_id = ?', [(int)$_POST['cp_id'], $id]); flash('Problem removed from contest.');
        redirect('admin/contest_form.php?id=' . $id);
    }
}
$cps = $id ? q('SELECT cp.id, cp.label, cp.points, p.id AS pid, p.title FROM contest_problems cp JOIN problems p ON p.id = cp.problem_id WHERE cp.contest_id = ? ORDER BY cp.label', [$id])->fetchAll() : [];
$all = $id ? q('SELECT id, title FROM problems ORDER BY id')->fetchAll() : [];
$nextLabel = chr(ord('A') + count($cps) % 26);
$dt = fn($v) => date('Y-m-d\TH:i', strtotime($v));
$page_title = $id ? 'Edit Contest' : 'New Contest';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<h4><?= $page_title ?></h4>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="card card-body mb-4"><?= csrf_field() ?><input type="hidden" name="action" value="save">
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($c['title']) ?>" required maxlength="150"></div>
  <div class="mb-3"><label class="form-label">Description / rules</label><textarea class="form-control" name="description" rows="4"><?= e($c['description']) ?></textarea></div>
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Start time</label><input type="datetime-local" class="form-control" name="start_time" value="<?= e($dt($c['start_time'])) ?>" required></div>
  <div class="col-md-6 mb-3"><label class="form-label">End time</label><input type="datetime-local" class="form-control" name="end_time" value="<?= e($dt($c['end_time'])) ?>" required></div></div>
  <div><button class="btn btn-primary">Save contest</button> <a class="btn btn-outline-secondary" href="<?= url('admin/contests.php') ?>">Back</a></div>
</form>
<?php if ($id): ?>
<h5>Contest problems</h5>
<div class="card mb-3"><table class="table table-sm mb-0 align-middle"><thead class="table-light"><tr><th>Label</th><th>Problem</th><th>Points</th><th></th></tr></thead><tbody>
<?php foreach ($cps as $cp): ?><tr><td class="fw-bold"><?= e($cp['label']) ?></td><td><a href="<?= url('admin/problem_form.php?id=' . $cp['pid']) ?>"><?= e($cp['title']) ?></a></td><td><?= (int)$cp['points'] ?></td>
  <td class="text-end"><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="remove_problem"><input type="hidden" name="cp_id" value="<?= (int)$cp['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Remove from contest?">Remove</button></form></td></tr><?php endforeach; ?>
<?php if (!$cps): ?><tr><td colspan="4" class="text-center text-muted py-3">No problems yet.</td></tr><?php endif; ?></tbody></table></div>
<form method="post" class="card card-body"><?= csrf_field() ?><input type="hidden" name="action" value="add_problem">
  <div class="row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label">Problem</label><select class="form-select" name="problem_id" required><?php foreach ($all as $p): ?><option value="<?= (int)$p['id'] ?>">#<?= (int)$p['id'] ?> — <?= e($p['title']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Label</label><input class="form-control" name="label" value="<?= e($nextLabel) ?>" maxlength="5" required></div>
    <div class="col-md-2"><label class="form-label">Points</label><input type="number" class="form-control" name="points" value="100" min="1" required></div>
    <div class="col-md-3"><button class="btn btn-success w-100">Add to contest</button></div>
  </div></form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
