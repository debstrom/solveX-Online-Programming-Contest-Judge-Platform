<?php
$page_title = 'Problems';
require __DIR__ . '/includes/header.php';
$search = trim((string)($_GET['q'] ?? '')); $diff = (string)($_GET['difficulty'] ?? '');
$where = ["(p.is_public = 1 OR (EXISTS (SELECT 1 FROM contest_problems x WHERE x.problem_id = p.id)
          AND NOT EXISTS (SELECT 1 FROM contest_problems y JOIN contests cc ON cc.id = y.contest_id WHERE y.problem_id = p.id AND cc.end_time >= NOW())))"];
$params = [];
if ($search !== '') { $where[] = '(p.title LIKE ? OR p.id = ?)'; $params[] = '%' . addcslashes($search, '%_\\') . '%'; $params[] = ctype_digit($search) ? (int)$search : 0; }
if (in_array($diff, ['Easy', 'Medium', 'Hard'], true)) { $where[] = 'p.difficulty = ?'; $params[] = $diff; }
$uid = $u['id'] ?? 0;
$sql = "SELECT p.id, p.title, p.difficulty, p.time_limit_ms, p.memory_limit_mb,
          (SELECT COUNT(*) FROM submissions s WHERE s.problem_id = p.id AND s.verdict = 'Accepted') AS solved_by,
          (SELECT COUNT(*) FROM submissions s WHERE s.user_id = ? AND s.problem_id = p.id AND s.verdict = 'Accepted') AS me_ac,
          (SELECT COUNT(*) FROM submissions s WHERE s.user_id = ? AND s.problem_id = p.id) AS me_tried
        FROM problems p WHERE " . implode(' AND ', $where) . ' ORDER BY p.id LIMIT 300';
$rows = q($sql, array_merge([$uid, $uid], $params))->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">Problem Library</h2></div>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-6"><input class="form-control" name="q" placeholder="Search by title or ID…" value="<?= e($search) ?>"></div>
  <div class="col-md-3"><select class="form-select" name="difficulty">
    <option value="">All difficulties</option>
    <?php foreach (['Easy', 'Medium', 'Hard'] as $d): ?><option <?= $diff === $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-md-3"><button class="btn btn-primary">Search</button> <a class="btn btn-outline-secondary" href="<?= url('problems.php') ?>">Reset</a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
  <thead class="table-light"><tr><th>#</th><th>Title</th><th>Difficulty</th><th>Limits</th><th>Solved by</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><a href="<?= url('problem.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></td>
      <td><?= diff_badge($r['difficulty']) ?></td>
      <td class="small text-muted"><?= (int)$r['time_limit_ms'] ?> ms / <?= (int)$r['memory_limit_mb'] ?> MB</td>
      <td><?= (int)$r['solved_by'] ?></td>
      <td><?= $r['me_ac'] ? '<span class="badge text-bg-success">Solved</span>' : ($r['me_tried'] ? '<span class="badge text-bg-warning">Attempted</span>' : '') ?></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No problems found.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
