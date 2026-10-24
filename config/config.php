<?php
/* ===== SolveX configuration — edit these for your machine ===== */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'solvex');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is empty

define('BASE_URL', '/solvex');    // folder name inside htdocs, no trailing slash
define('APP_NAME', 'SolveX');
define('APP_TZ', 'Asia/Dhaka');
define('DB_TZ', '+06:00');        // keep in sync with APP_TZ

/* Judge settings.
   XAMPP on Windows: PHP_CLI = 'C:\\xampp\\php\\php.exe', GPP = 'C:\\MinGW\\bin\\g++.exe' (or your g++ path)
   Ubuntu/Linux:     PHP_CLI = 'php', GPP = 'g++'                                              */
define('PHP_CLI', 'php');
define('GPP', 'g++');
define('GPP_FLAGS', ['-O2', '-std=c++17']);
define('COMPILE_TIMEOUT_MS', 20000);
define('MAX_OUTPUT_BYTES', 1048576);   // 1 MB of program output
define('MAX_SOURCE_BYTES', 65536);     // 64 KB of source code
define('JUDGE_TMP', __DIR__ . '/../storage/judge');
/* Optional extra isolation (Linux): prefix every run, e.g. ['firejail','--quiet','--net=none','--private-tmp']
   Leave as [] to use only ulimit limits (fine for a lab, not for the open internet). */
define('SANDBOX_PREFIX', []);

define('WRONG_PENALTY_MIN', 20);       // leaderboard penalty per wrong attempt before AC
define('SUBMIT_COOLDOWN_SEC', 5);
define('SESSION_IDLE_SEC', 7200);

date_default_timezone_set(APP_TZ);
