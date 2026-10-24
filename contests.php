<?php
$page_title = 'Contests';
require __DIR__ . '/includes/header.php';
$rows = q('SELECT ' . CONTEST_COLS . ', (SELECT COUNT(*) FROM registrations r WHERE r.contest_id = c.id) AS reg_count,
           (SELECT COUNT(*) FROM contest_problems cp WHERE cp.contest_id = c.id) AS prob_count' .
          ($u ? ', (SELECT COUNT(*) FROM registrations r2 WHERE r2.contest_id = c.id AND r2.user_id = ' . (int)$u['id'] . ') AS me_reg' : ', 0 AS me_reg') .
          ' FROM contests c ORDER BY c.start_time DESC')->fetchAll();
$groups = ['Running' => [], 'Upcoming' => [], 'Ended' => []];
foreach ($rows as $r) $groups[contest_status($r)][] = $r;
?>
<h2>Contests</h2>
<?php foreach ($groups as $label => $list): if (!$list) continue; ?>
  <h5 class="mt-4"><?= $label ?></h5>
  <div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
    <thead class="table-light"><tr><th>Contest</th><th>Start</th><th>End</th><th>Problems</th><th>Participants</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $c): ?>
      <tr><td><a class="fw-semibold" href="<?= url('contest.php?id=' . $c['id']) ?>"><?= e($c['title']) ?></a></td>
        <td class="small"><?= fmt_dt($c['start_ts']) ?></td><td class="small"><?= fmt_dt($c['end_ts']) ?></td>
        <td><?= (int)$c['prob_count'] ?></td><td><?= (int)$c['reg_count'] ?></td>
        <td class="text-end"><?php if ($c['me_reg']): ?><span class="badge text-bg-success">Registered</span>
          <?php elseif ($label !== 'Ended'): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('contest.php?id=' . $c['id']) ?>">Register</a>
          <?php else: ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('leaderboard.php?id=' . $c['id']) ?>">Standings</a><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div>
<?php endforeach; if (!$rows): ?><p class="text-muted">No contests yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
