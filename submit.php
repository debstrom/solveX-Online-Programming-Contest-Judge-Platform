<?php
require __DIR__ . '/includes/functions.php';
$u = require_login();
if (!is_post()) redirect('problems.php');
csrf_verify();
$pid = (int)($_POST['problem_id'] ?? 0); $cid = (int)($_POST['contest_id'] ?? 0);
$code = str_replace("\r\n", "\n", (string)($_POST['code'] ?? ''));
$back = $cid ? "problem.php?id=$pid&contest=$cid" : "problem.php?id=$pid";

if (!q('SELECT 1 FROM problems WHERE id = ?', [$pid])->fetch()) { http_response_code(404); exit('Unknown problem.'); }
if (trim($code) === '') { flash('Source code cannot be empty.', 'danger'); redirect($back); }
if (strlen($code) > MAX_SOURCE_BYTES) { flash('Source code is too large (max ' . (MAX_SOURCE_BYTES / 1024) . ' KB).', 'danger'); redirect($back); }

if ($cid) {
    $c = get_contest($cid);
    $in_contest = $c && q('SELECT 1 FROM contest_problems WHERE contest_id = ? AND problem_id = ?', [$cid, $pid])->fetch();
    if (!$in_contest || contest_status($c) !== 'Running') { flash('This contest is not accepting submissions for that problem.', 'danger'); redirect('contests.php'); }
    if (!q('SELECT 1 FROM registrations WHERE contest_id = ? AND user_id = ?', [$cid, $u['id']])->fetch()) { flash('You are not registered for this contest.', 'danger'); redirect("contest.php?id=$cid"); }
} else {
    if (!problem_open_for_practice($pid) && $u['role'] !== 'admin') { http_response_code(403); exit('This problem is only available inside its contest.'); }
    $cid = null;
}
$last = q('SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(submitted_at)) FROM submissions WHERE user_id = ?', [$u['id']])->fetchColumn();
if ($last !== null && $last < SUBMIT_COOLDOWN_SEC) { flash('Please wait a few seconds between submissions.', 'warning'); redirect($back); }

q('INSERT INTO submissions (user_id, problem_id, contest_id, source_code) VALUES (?,?,?,?)', [$u['id'], $pid, $cid, $code]);
$sid = (int)db()->lastInsertId();
spawn_judge($sid);                       // asynchronous: the web request returns immediately
redirect('submission.php?id=' . $sid);
