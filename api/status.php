<?php
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$u = current_user();
if (!$u) { http_response_code(401); echo '{"error":"auth"}'; exit; }
$id = (int)($_GET['id'] ?? 0);
$s = q('SELECT user_id, verdict, submitted_at < NOW() - INTERVAL 15 SECOND AS stale FROM submissions WHERE id = ?', [$id])->fetch();
if (!$s || ($s['user_id'] != $u['id'] && $u['role'] !== 'admin')) { http_response_code(404); echo '{"error":"nf"}'; exit; }
// if the background worker could not start (e.g. exec disabled) retry once per poll while still Pending
if ($s['verdict'] === 'Pending' && $s['stale']) spawn_judge($id);
echo json_encode(['verdict' => $s['verdict'], 'pending' => in_array($s['verdict'], ['Pending', 'Judging'], true)]);
