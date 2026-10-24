<?php
// Optional: keeps judging Pending submissions in a loop.  php judge/daemon.php
// Use this if your host blocks exec()/popen(), or to re-judge anything left Pending.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/judge.php';
echo "SolveX judge daemon started. Ctrl+C to stop.\n";
while (true) {
    // recover submissions stuck in 'Judging' for more than 3 minutes (crashed worker)
    q("UPDATE submissions SET verdict = 'Pending' WHERE verdict = 'Judging' AND submitted_at < NOW() - INTERVAL 3 MINUTE AND judged_at IS NULL");
    $id = q("SELECT id FROM submissions WHERE verdict = 'Pending' ORDER BY id LIMIT 1")->fetchColumn();
    if ($id) { echo date('H:i:s') . " judging #$id\n"; judge_submission((int)$id); } else sleep(1);
}
