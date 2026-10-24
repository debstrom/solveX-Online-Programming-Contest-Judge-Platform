<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$id = (int)($_GET['id'] ?? 0);
$p = ['title' => '', 'statement' => '', 'input_format' => '', 'output_format' => '', 'sample_input' => '', 'sample_output' => '', 'difficulty' => 'Easy', 'time_limit_ms' => 1000, 'memory_limit_mb' => 256, 'is_public' => 1];
if ($id) { $p = q('SELECT * FROM problems WHERE id = ?', [$id])->fetch() ?: exit('Problem not found.'); }
$err = [];
if (is_post()) {
    csrf_verify();
    foreach (['title', 'statement', 'input_format', 'output_format', 'sample_input', 'sample_output'] as $k) $p[$k] = trim(str_replace("\r\n", "\n", (string)($_POST[$k] ?? '')));
    $p['difficulty'] = in_array($_POST['difficulty'] ?? '', ['Easy', 'Medium', 'Hard'], true) ? $_POST['difficulty'] : 'Easy';
    $p['time_limit_ms'] = (int)($_POST['time_limit_ms'] ?? 1000); $p['memory_limit_mb'] = (int)($_POST['memory_limit_mb'] ?? 256);
    $p['is_public'] = isset($_POST['is_public']) ? 1 : 0;
    if ($p['title'] === '' || strlen($p['title']) > 150) $err[] = 'Title is required (max 150 characters).';
    if ($p['statement'] === '') $err[] = 'Statement is required.';
    if ($p['time_limit_ms'] < 100 || $p['time_limit_ms'] > 10000) $err[] = 'Time limit must be 100–10000 ms.';
    if ($p['memory_limit_mb'] < 16 || $p['memory_limit_mb'] > 1024) $err[] = 'Memory limit must be 16–1024 MB.';
    if (!$err) {
        $vals = [$p['title'], $p['statement'], $p['input_format'], $p['output_format'], $p['sample_input'], $p['sample_output'], $p['difficulty'], $p['time_limit_ms'], $p['memory_limit_mb'], $p['is_public']];
        if ($id) { q('UPDATE problems SET title=?, statement=?, input_format=?, output_format=?, sample_input=?, sample_output=?, difficulty=?, time_limit_ms=?, memory_limit_mb=?, is_public=? WHERE id=?', array_merge($vals, [$id])); flash('Problem updated.'); }
        else { q('INSERT INTO problems (title, statement, input_format, output_format, sample_input, sample_output, difficulty, time_limit_ms, memory_limit_mb, is_public, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$admin['id']])); $id = (int)db()->lastInsertId(); flash('Problem created. Now add its test cases.'); redirect('admin/testcases.php?problem_id=' . $id); }
        redirect('admin/problems.php');
    }
}
$page_title = $id ? 'Edit Problem' : 'New Problem';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<h4><?= $page_title ?></h4>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="card card-body"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($p['title']) ?>" required maxlength="150"></div>
  <div class="mb-3"><label class="form-label">Statement</label><textarea class="form-control" name="statement" rows="6" required><?= e($p['statement']) ?></textarea></div>
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Input format</label><textarea class="form-control" name="input_format" rows="3"><?= e($p['input_format']) ?></textarea></div>
    <div class="col-md-6 mb-3"><label class="form-label">Output format</label><textarea class="form-control" name="output_format" rows="3"><?= e($p['output_format']) ?></textarea></div></div>
  <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Sample input (display only — add judged samples under Test cases)</label><textarea class="form-control font-monospace" name="sample_input" rows="3"><?= e($p['sample_input']) ?></textarea></div>
    <div class="col-md-6 mb-3"><label class="form-label">Sample output</label><textarea class="form-control font-monospace" name="sample_output" rows="3"><?= e($p['sample_output']) ?></textarea></div></div>
  <div class="row">
    <div class="col-md-3 mb-3"><label class="form-label">Difficulty</label><select class="form-select" name="difficulty"><?php foreach (['Easy', 'Medium', 'Hard'] as $d): ?><option <?= $p['difficulty'] === $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3 mb-3"><label class="form-label">Time limit (ms)</label><input type="number" class="form-control" name="time_limit_ms" value="<?= (int)$p['time_limit_ms'] ?>"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Memory limit (MB)</label><input type="number" class="form-control" name="memory_limit_mb" value="<?= (int)$p['memory_limit_mb'] ?>"></div>
    <div class="col-md-3 mb-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_public" id="pub" <?= $p['is_public'] ? 'checked' : '' ?>><label class="form-check-label" for="pub">Public in problem library</label></div></div>
  </div>
  <div><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="<?= url('admin/problems.php') ?>">Cancel</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
