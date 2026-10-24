<?php
// Judge a single submission:  php judge/worker.php <submission_id>
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../includes/judge.php';
$id = (int)($argv[1] ?? 0);
if ($id > 0) judge_submission($id);
