<?php
require __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$pid = (int)($_GET['problem_id'] ?? 0);
$p = q('SELECT id, title FROM problems WHERE id = ?', [$pid])->fetch() ?: exit('Problem not found.');
$MAX = 2 * 1024 * 1024;
if (is_post()) {
    csrf_verify();
    $act = $_POST['action'] ?? '';
    if ($act === 'delete') { q('DELETE FROM test_cases WHERE id = ? AND problem_id = ?', [(int)$_POST['id'], $pid]); flash('Test case deleted.'); }
    elseif ($act === 'add') {
        $in = (string)($_POST['input_data'] ?? ''); $out = (string)($_POST['expected_output'] ?? '');
        foreach (['input_file' => &$in, 'output_file' => &$out] as $f => &$ref) {   // uploaded .in/.out files override textareas
            if (!empty($_FILES[$f]['tmp_name']) && is_uploaded_file($_FILES[$f]['tmp_name']) && $_FILES[$f]['size'] <= $MAX) $ref = (string)file_get_contents($_FILES[$f]['tmp_name']);
        }
        unset($ref);
        $in = str_replace("\r\n", "\n", $in); $out = str_replace("\r\n", "\n", $out);
        if (trim($out) === '') flash('Expected output cannot be empty.', 'danger');
        elseif (strlen($in) > $MAX || strlen($out) > $MAX) flash('Test data is too large (max 2 MB each).', 'danger');
        else { q('INSERT INTO test_cases (problem_id, input_data, expected_output, is_sample) VALUES (?,?,?,?)', [$pid, $in, $out, isset($_POST['is_sample']) ? 1 : 0]); flash('Test case added.'); }
    } elseif ($act === 'toggle') { q('UPDATE test_cases SET is_sample = 1 - is_sample WHERE id = ? AND problem_id = ?', [(int)$_POST['id'], $pid]); }
    redirect('admin/testcases.php?problem_id=' . $pid);
}
$tcs = q('SELECT id, is_sample, LENGTH(input_data) li, LENGTH(expected_output) lo, LEFT(input_data, 80) pi, LEFT(expected_output, 80) po FROM test_cases WHERE problem_id = ? ORDER BY id', [$pid])->fetchAll();
$page_title = 'Test cases';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/admin_nav.php';
?>
<h4>Test cases — <?= e($p['title']) ?></h4>
<p class="text-muted small">Hidden test cases are never shown to students. Cases marked <b>sample</b> are displayed on the problem page and judged first.</p>
<div class="card mb-4"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>#</th><th>Type</th><th>Input (preview)</th><th>Output (preview)</th><th>Size</th><th></th></tr></thead><tbody>
<?php foreach ($tcs as $i => $t): ?><tr><td><?= $i + 1 ?></td><td><?= $t['is_sample'] ? '<span class="badge text-bg-info">Sample</span>' : '<span class="badge text-bg-dark">Hidden</span>' ?></td>
  <td><code class="small"><?= e(str_replace("\n", ' ⏎ ', $t['pi'])) ?></code></td><td><code class="small"><?= e(str_replace("\n", ' ⏎ ', $t['po'])) ?></code></td><td class="small"><?= (int)$t['li'] ?> / <?= (int)$t['lo'] ?> B</td>
  <td class="text-end text-nowrap">
    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-sm btn-outline-secondary">Toggle sample</button></form>
    <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this test case?">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$tcs): ?><tr><td colspan="6" class="text-center text-muted py-3">No test cases yet — the judge cannot grade this problem.</td></tr><?php endif; ?></tbody></table></div></div>
<form method="post" enctype="multipart/form-data" class="card card-body"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h6>Add test case</h6>
  <div class="row"><div class="col-md-6 mb-2"><label class="form-label">Input</label><textarea class="form-control font-monospace" name="input_data" rows="5"></textarea><input type="file" class="form-control form-control-sm mt-1" name="input_file" accept=".in,.txt"></div>
  <div class="col-md-6 mb-2"><label class="form-label">Expected output</label><textarea class="form-control font-monospace" name="expected_output" rows="5"></textarea><input type="file" class="form-control form-control-sm mt-1" name="output_file" accept=".out,.ans,.txt"></div></div>
  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_sample" id="smp"><label class="form-check-label" for="smp">Show as sample on the problem page</label></div>
  <div><button class="btn btn-primary">Add</button> <a class="btn btn-outline-secondary" href="<?= url('admin/problems.php') ?>">Back to problems</a></div></form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
