<?php
require __DIR__ . '/includes/functions.php';
$u = require_login();
if (!is_post()) redirect('contests.php');
csrf_verify();
$cid = (int)($_POST['contest_id'] ?? 0);
$c = get_contest($cid);
if (!$c) { flash('Contest not found.', 'danger'); redirect('contests.php'); }
if (contest_status($c) === 'Ended') { flash('This contest has already ended.', 'warning'); redirect("contest.php?id=$cid"); }
q('INSERT IGNORE INTO registrations (contest_id, user_id) VALUES (?, ?)', [$cid, $u['id']]);
flash('You are now registered for "' . $c['title'] . '". Good luck!');
redirect("contest.php?id=$cid");
