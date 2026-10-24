<?php
/* SolveX online judge core. Used only from the CLI (judge/worker.php, judge/daemon.php). */
require_once __DIR__ . '/db.php';

const IS_WIN = DIRECTORY_SEPARATOR === '\\';

function rrmdir(string $d): void {
    if (!is_dir($d)) return;
    foreach (scandir($d) as $f) { if ($f === '.' || $f === '..') continue; $p = "$d/$f"; is_dir($p) ? rrmdir($p) : @unlink($p); }
    @rmdir($d);
}

/** Run $cmd (array, no shell) with stdin from a string; stdout/stderr go to files so it works on Linux and Windows. */
function run_process(array $cmd, string $stdin, int $timeout_ms, string $dir): array {
    $fin = "$dir/stdin.txt"; $fout = "$dir/stdout.txt"; $ferr = "$dir/stderr.txt";
    file_put_contents($fin, $stdin); file_put_contents($fout, ''); file_put_contents($ferr, '');
    if (SANDBOX_PREFIX) $cmd = array_merge(SANDBOX_PREFIX, $cmd);
    $desc = [0 => ['file', $fin, 'r'], 1 => ['file', $fout, 'w'], 2 => ['file', $ferr, 'w']];
    $env = IS_WIN ? null : ['PATH' => '/usr/local/bin:/usr/bin:/bin', 'LANG' => 'C'];
    $t0 = microtime(true);
    $p = @proc_open($cmd, $desc, $pipes, $dir, $env, ['bypass_shell' => true]);
    if (!is_resource($p)) return ['exit' => -1, 'timeout' => false, 'overflow' => false, 'signaled' => false, 'ms' => 0, 'out' => '', 'err' => 'Could not start process'];
    $exit = -1; $timeout = false; $overflow = false; $signaled = false;
    while (true) {
        $st = proc_get_status($p);
        if (!$st['running']) { $exit = $st['exitcode']; $signaled = !empty($st['signaled']); break; }
        if ((microtime(true) - $t0) * 1000 > $timeout_ms) { $timeout = true; proc_terminate($p, 9); break; }
        clearstatcache(true, $fout);
        if (filesize($fout) > MAX_OUTPUT_BYTES) { $overflow = true; proc_terminate($p, 9); break; }
        usleep(2000);
    }
    $ms = (int)round((microtime(true) - $t0) * 1000);
    if ($timeout || $overflow) { usleep(20000); }
    @proc_close($p);
    return [
        'exit' => $exit, 'timeout' => $timeout, 'overflow' => $overflow, 'signaled' => $signaled, 'ms' => $ms,
        'out' => (string)file_get_contents($fout, false, null, 0, MAX_OUTPUT_BYTES + 1),
        'err' => (string)file_get_contents($ferr, false, null, 0, 8192),
    ];
}

function normalize_output(string $s): string {
    $s = str_replace("\r\n", "\n", $s);
    $lines = array_map('rtrim', explode("\n", $s));
    return rtrim(implode("\n", $lines));
}

function source_is_unsafe(string $code): ?string {
    if (preg_match('/#\s*include\s*"/', $code)) return 'Quoted #include is not allowed.';
    if (preg_match('/#\s*include\s*<\s*(\/|\.\.|[A-Za-z]:)/', $code)) return 'Absolute or parent-directory #include is not allowed.';
    if (preg_match('/__has_include|#\s*embed|\.incbin/', $code)) return 'Forbidden preprocessor/assembler directive.';
    return null;
}

function finish_submission(int $id, string $verdict, int $ms, int $score, string $msg): void {
    q('UPDATE submissions SET verdict = ?, time_ms = ?, score = ?, judge_message = ?, judged_at = NOW() WHERE id = ?',
        [$verdict, $ms, $score, substr($msg, 0, 4000), $id]);
}

function judge_submission(int $id): void {
    // atomically claim the submission so two workers never judge the same one
    if (q("UPDATE submissions SET verdict = 'Judging' WHERE id = ? AND verdict = 'Pending'", [$id])->rowCount() !== 1) return;
    $s = q('SELECT s.*, p.time_limit_ms, p.memory_limit_mb FROM submissions s JOIN problems p ON p.id = s.problem_id WHERE s.id = ?', [$id])->fetch();
    if (!$s) return;
    $dir = JUDGE_TMP . '/sub_' . $id . '_' . bin2hex(random_bytes(3));
    @mkdir($dir, 0700, true);
    try {
        if ($bad = source_is_unsafe($s['source_code'])) { finish_submission($id, 'Compilation Error', 0, 0, $bad); return; }
        file_put_contents("$dir/main.cpp", $s['source_code']);
        $exe = "$dir/prog" . (IS_WIN ? '.exe' : '');
        $c = run_process(array_merge([GPP], GPP_FLAGS, ['-o', $exe, "$dir/main.cpp"]), '', COMPILE_TIMEOUT_MS, $dir);
        if ($c['exit'] !== 0 || !is_file($exe)) {
            $m = $c['timeout'] ? 'Compilation timed out.' : str_replace($dir, '', $c['err']);
            finish_submission($id, 'Compilation Error', 0, 0, $m); return;
        }
        $tests = q('SELECT * FROM test_cases WHERE problem_id = ? ORDER BY is_sample DESC, id', [$s['problem_id']])->fetchAll();
        if (!$tests) { finish_submission($id, 'Runtime Error', 0, 0, 'Judge error: this problem has no test cases.'); return; }

        $tl = (int)$s['time_limit_ms']; $memKb = max((int)$s['memory_limit_mb'], 128) * 1024; $cpu = (int)ceil($tl / 1000) + 1;
        $run = IS_WIN ? [$exe]
            : ['bash', '-c', 'ulimit -v "$1"; ulimit -t "$2"; ulimit -f 20480; exec "$3"', 'judge', (string)$memKb, (string)$cpu, $exe];
        $maxMs = 0; $n = count($tests); $verdict = 'Accepted'; $msg = "Passed all $n test case(s).";
        foreach ($tests as $i => $t) {
            $r = run_process($run, $t['input_data'], $tl, $dir);
            $maxMs = max($maxMs, min($r['ms'], $tl));
            $label = 'test ' . ($i + 1) . '/' . $n . ($t['is_sample'] ? ' (sample)' : ' (hidden)');
            if ($r['timeout'] || ($r['signaled'] && $r['ms'] >= $tl)) { $verdict = 'Time Limit Exceeded'; $msg = "Time limit exceeded on $label."; break; }
            if ($r['overflow']) { $verdict = 'Runtime Error'; $msg = "Output limit exceeded on $label."; break; }
            if ($r['exit'] !== 0 || $r['signaled']) {
                $verdict = 'Runtime Error'; $msg = "Runtime error on $label (exit code {$r['exit']})."; if ($r['signaled']) $msg .= ' Program was killed by a signal (e.g. segmentation fault or memory limit).'; break;
            }
            if (normalize_output($r['out']) !== normalize_output($t['expected_output'])) { $verdict = 'Wrong Answer'; $msg = "Wrong answer on $label."; break; }
        }
        $score = 0;
        if ($verdict === 'Accepted' && $s['contest_id']) {
            $already = q("SELECT 1 FROM submissions WHERE user_id = ? AND problem_id = ? AND contest_id = ? AND verdict = 'Accepted' AND id <> ? LIMIT 1",
                [$s['user_id'], $s['problem_id'], $s['contest_id'], $id])->fetch();
            if (!$already) $score = (int)(q('SELECT points FROM contest_problems WHERE contest_id = ? AND problem_id = ?', [$s['contest_id'], $s['problem_id']])->fetchColumn() ?: 0);
        }
        finish_submission($id, $verdict, $maxMs, $score, $msg);
    } catch (Throwable $e) {
        finish_submission($id, 'Runtime Error', 0, 0, 'Internal judge error: ' . $e->getMessage());
    } finally {
        rrmdir($dir);
    }
}
