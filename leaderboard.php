<?php
require __DIR__ . '/includes/functions.php';
$cid = (int)($_GET['id'] ?? 0);
$c = get_contest($cid);
if (!$c) { http_response_code(404); exit('Contest not found.'); }
$u = current_user(); $st = contest_status($c);
$data = contest_standings($c);
$page_title = 'Leaderboard — ' . $c['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div><h2 class="mb-0">Leaderboard</h2><a href="<?= url('contest.php?id=' . $cid) ?>"><?= e($c['title']) ?></a> <span class="badge text-bg-secondary"><?= $st ?></span></div>
  <?php if ($st === 'Running'): ?><div id="lb-refresh" class="small text-muted">Auto-refreshes every 30 s</div><?php endif; ?>
</div>
<div class="card"><div class="table-responsive"><table class="table table-bordered mb-0 lb">
  <thead class="table-dark"><tr><th>Rank</th><th class="left">Participant</th><th>Score</th><th>Time</th>
    <?php foreach ($data['problems'] as $p): ?><th title="<?= e($p['title']) ?>"><?= e($p['label']) ?><div class="small fw-normal"><?= (int)$p['points'] ?></div></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($data['rows'] as $r): $me = $u && $u['id'] == $r['user']['id']; ?>
    <tr class="<?= $r['rank'] === 1 && $r['score'] > 0 ? 'rank-1' : '' ?> <?= $me ? 'table-primary' : '' ?>">
      <td class="fw-bold"><?= (int)$r['rank'] ?></td>
      <td class="left"><?= e($r['user']['full_name']) ?> <span class="text-muted small">@<?= e($r['user']['username']) ?></span></td>
      <td class="fw-bold"><?= (int)$r['score'] ?></td><td><?= (int)$r['time'] ?></td>
      <?php foreach ($data['problems'] as $p): $cell = $r['cells'][$p['problem_id']] ?? null; ?>
        <td class="<?= $cell && $cell['ac'] !== null ? 'cell-ac' : ($cell ? 'cell-wa' : '') ?>">
          <?php if ($cell && $cell['ac'] !== null): ?>+<?= $cell['wrong'] ? $cell['wrong'] : '' ?><div class="small"><?= (int)$cell['ac'] ?>′</div>
          <?php elseif ($cell): ?>−<?= (int)$cell['wrong'] ?><?php endif; ?></td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; if (!$data['rows']): ?><tr><td colspan="<?= 4 + count($data['problems']) ?>" class="text-center text-muted py-4">No participants yet.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<p class="small text-muted mt-2">Ranking: higher total score first; ties are broken by lower total time (minutes to each accepted solution + <?= WRONG_PENALTY_MIN ?> min penalty per wrong attempt before it). Compilation errors carry no penalty.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
