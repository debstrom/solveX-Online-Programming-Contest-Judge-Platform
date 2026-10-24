<?php
require_once __DIR__ . '/db.php';

/* ---------- session (FR-02) ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('solvexsid');
    session_start();
}
if (isset($_SESSION['uid']) && isset($_SESSION['last']) && time() - $_SESSION['last'] > SESSION_IDLE_SEC) {
    $_SESSION = []; session_destroy(); session_start();
}
if (isset($_SESSION['uid'])) $_SESSION['last'] = time();

/* ---------- helpers ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419); exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

/* ---------- auth ---------- */
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = q('SELECT id, full_name, username, email, university_id, department, role, created_at FROM users WHERE id = ?', [$_SESSION['uid']])->fetch() ?: null;
            if (!$u) { $_SESSION = []; }
        }
    }
    return $u;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('Please log in to continue.', 'warning'); $_SESSION['back'] = $_SERVER['REQUEST_URI'] ?? ''; redirect('login.php'); }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('403 — Administrators only.'); }
    return $u;
}

/* ---------- display helpers ---------- */
function verdict_class(string $v): string {
    return match ($v) {
        'Accepted' => 'success', 'Wrong Answer' => 'danger', 'Compilation Error' => 'warning',
        'Time Limit Exceeded' => 'info', 'Runtime Error' => 'dark', default => 'secondary',
    };
}
function verdict_badge(string $v): string {
    return '<span class="badge text-bg-' . verdict_class($v) . '">' . e($v) . '</span>';
}
function diff_badge(string $d): string {
    $c = ['Easy' => 'success', 'Medium' => 'warning', 'Hard' => 'danger'][$d] ?? 'secondary';
    return '<span class="badge text-bg-' . $c . '">' . e($d) . '</span>';
}
function contest_status(array $c): string {   // expects start_ts / end_ts / now_ts from SQL
    if ($c['now_ts'] < $c['start_ts']) return 'Upcoming';
    if ($c['now_ts'] <= $c['end_ts']) return 'Running';
    return 'Ended';
}
const CONTEST_COLS = "c.*, UNIX_TIMESTAMP(c.start_time) AS start_ts, UNIX_TIMESTAMP(c.end_time) AS end_ts, UNIX_TIMESTAMP(NOW()) AS now_ts";
function get_contest(int $id): ?array {
    return q('SELECT ' . CONTEST_COLS . ' FROM contests c WHERE c.id = ?', [$id])->fetch() ?: null;
}
function fmt_dt($s): string { return date('d M Y, h:i A', is_numeric($s) ? (int)$s : strtotime($s)); }
function fmt_dur(int $sec): string {
    $sec = max(0, $sec); return sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
}

/* ---------- judging dispatch (asynchronous, FR-10) ---------- */
function spawn_judge(int $id): void {
    $script = realpath(__DIR__ . '/../judge/worker.php');
    if (!$script) return;
    if (DIRECTORY_SEPARATOR === '\\') {
        @pclose(@popen('start /B "" "' . PHP_CLI . '" "' . $script . '" ' . $id . ' > NUL 2>&1', 'r'));
    } elseif (function_exists('exec')) {
        @exec(escapeshellarg(PHP_CLI) . ' ' . escapeshellarg($script) . ' ' . (int)$id . ' > /dev/null 2>&1 &');
    }
}

/* ---------- leaderboard (FR-11) ---------- */
function contest_standings(array $contest): array {
    $cid = (int)$contest['id'];
    $problems = q('SELECT cp.problem_id, cp.label, cp.points, p.title FROM contest_problems cp JOIN problems p ON p.id = cp.problem_id WHERE cp.contest_id = ? ORDER BY cp.label', [$cid])->fetchAll();
    $pts = []; foreach ($problems as $p) $pts[$p['problem_id']] = (int)$p['points'];
    $users = q('SELECT u.id, u.full_name, u.username, u.university_id FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.contest_id = ?', [$cid])->fetchAll();
    $rows = [];
    foreach ($users as $u) $rows[$u['id']] = ['user' => $u, 'score' => 0, 'time' => 0, 'cells' => []];
    $subs = q("SELECT user_id, problem_id, verdict, TIMESTAMPDIFF(SECOND, ?, submitted_at) AS t FROM submissions
               WHERE contest_id = ? AND submitted_at BETWEEN ? AND ? AND verdict NOT IN ('Pending','Judging','Compilation Error')
               ORDER BY id", [$contest['start_time'], $cid, $contest['start_time'], $contest['end_time']])->fetchAll();
    foreach ($subs as $s) {
        $uid = $s['user_id']; $pid = $s['problem_id'];
        if (!isset($rows[$uid]) || !isset($pts[$pid])) continue;
        $cell = &$rows[$uid]['cells'][$pid];
        $cell ??= ['wrong' => 0, 'ac' => null];
        if ($cell['ac'] !== null) continue;
        if ($s['verdict'] === 'Accepted') {
            $cell['ac'] = (int)floor($s['t'] / 60);
            $rows[$uid]['score'] += $pts[$pid];
            $rows[$uid]['time']  += $cell['ac'] + $cell['wrong'] * WRONG_PENALTY_MIN;
        } else $cell['wrong']++;
        unset($cell);
    }
    $rows = array_values($rows);
    usort($rows, fn($a, $b) => [$b['score'], $a['time']] <=> [$a['score'], $b['time']] ?: strcmp($a['user']['username'], $b['user']['username']));
    $rank = 0; $prev = null;
    foreach ($rows as $i => &$r) {
        $key = $r['score'] . ':' . $r['time'];
        if ($key !== $prev) { $rank = $i + 1; $prev = $key; }
        $r['rank'] = $rank;
    }
    return ['problems' => $problems, 'rows' => $rows];
}

/* ---------- problem access ---------- */
/** Practice mode: public problems, or problems whose every contest has ended. */
function problem_open_for_practice(int $pid): bool {
    $p = q('SELECT is_public FROM problems WHERE id = ?', [$pid])->fetch();
    if (!$p) return false;
    if ($p['is_public']) return true;
    $live = q('SELECT 1 FROM contest_problems cp JOIN contests c ON c.id = cp.contest_id WHERE cp.problem_id = ? AND c.end_time >= NOW() LIMIT 1', [$pid])->fetch();
    $any  = q('SELECT 1 FROM contest_problems WHERE problem_id = ? LIMIT 1', [$pid])->fetch();
    return $any && !$live;
}
