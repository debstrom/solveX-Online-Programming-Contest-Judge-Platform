# SolveX — Online Programming Contest & Judge Platform
*Code. Compete. Conquer.* — Internet Programming Lab project (PHP + MySQL + Bootstrap 5)

Students register, solve problems, join timed contests and submit **C++** solutions that are compiled and run
against hidden test cases by an automated judge. Admins manage contests, problems, test cases, users and announcements.

## Requirements coverage
| ID | Feature | Where |
|----|---------|-------|
| FR-01 | Registration & secure login (bcrypt, session regeneration, login throttling) | `register.php`, `login.php` |
| FR-02 | Logout & session management (idle timeout, HttpOnly/SameSite cookie) | `logout.php`, `includes/functions.php` |
| FR-03 | Profile & performance statistics | `profile.php` |
| FR-04 | Browse / search problems | `problems.php`, `problem.php` |
| FR-05 | Admin manages contests (+ attach problems & points) | `admin/contests.php`, `admin/contest_form.php` |
| FR-06 | Admin manages problems | `admin/problems.php`, `admin/problem_form.php` |
| FR-07 | Contest registration | `contest.php`, `contest_register.php` |
| FR-08 | Timed contest interface with countdown | `contest.php`, `assets/js/app.js` |
| FR-09 | C++ submission interface | `problem.php`, `submit.php` |
| FR-10 | Automated judging & verdicts | `includes/judge.php`, `judge/worker.php` |
| FR-11 | Scoring & leaderboard | `leaderboard.php`, `contest_standings()` |
| FR-12 | Submission history | `submissions.php`, `submission.php` |
| FR-13 | Hidden test-case management | `admin/testcases.php` |
| FR-14 | Announcements | `announcements.php`, `admin/announcements.php` |
| FR-15 | Rating & achievements | *Future scope — not implemented (as per PRD)* |

## Setup (XAMPP, Windows)
1. Copy the `solvex` folder to `C:\xampp\htdocs\solvex`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open <http://localhost/phpmyadmin> → *Import* → choose `database/solvex.sql` → *Go*.
4. Install a C++ compiler (e.g. MinGW-w64 / MSYS2) and check `g++ --version` works in a terminal.
5. Edit `config/config.php`:
   ```php
   define('PHP_CLI', 'C:\\xampp\\php\\php.exe');
   define('GPP',     'C:\\msys64\\ucrt64\\bin\\g++.exe');   // your g++ path
   ```
   (If your MySQL root has a password, set `DB_PASS`. If the folder is not called `solvex`, change `BASE_URL`.)
6. Open <http://localhost/solvex/>.

## Setup (Ubuntu / Linux)
```bash
sudo apt install apache2 php php-mysql mariadb-server g++ libapache2-mod-php
sudo cp -r solvex /var/www/html/ && sudo chown -R www-data:www-data /var/www/html/solvex
sudo mysql < /var/www/html/solvex/database/solvex.sql
# MariaDB root uses socket auth: create an app user and put it into config/config.php
sudo mysql -e "CREATE USER 'solvex'@'localhost' IDENTIFIED BY 'change-me'; GRANT ALL ON solvex.* TO 'solvex'@'localhost';"
```
Quick test without Apache: `php -S localhost:8000 -t .` and set `BASE_URL` to `''` in `config/config.php`.

## Demo accounts (change the passwords!)
| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `Admin@123` |
| Student | `student1` | `Student@123` |

## How judging works
1. `submit.php` stores the code as **Pending** and starts `judge/worker.php <id>` in the background, so the web request never blocks.
2. The worker compiles with `g++ -O2 -std=c++17`, then runs the program on every test case (samples first) with a
   wall-clock limit, CPU limit (`ulimit -t`), memory limit (`ulimit -v`), file-size limit and an output cap.
3. Output is compared ignoring trailing whitespace. First failing test decides the verdict:
   *Accepted · Wrong Answer · Compilation Error · Time Limit Exceeded · Runtime Error*.
4. The submission page polls `api/status.php` and refreshes itself when judging finishes.

If the host blocks `exec()`/`popen()`, run `php judge/daemon.php` in a terminal — it judges Pending submissions in a loop.
Admins can also re-judge from **Admin → Submissions**.

## Scoring
Points are given for the **first Accepted** verdict per problem per contest. Ranking = total score (high first), then total
time (low first) = minutes from contest start to each accepted solve + 20 min per earlier wrong attempt (compile errors are free).

## Security notes
* PDO prepared statements everywhere; `password_hash()`/`password_verify()`; CSRF token on every POST form; output escaping with `e()`.
* Admin pages call `require_admin()`; non-admins get HTTP 403. `config/`, `includes/`, `storage/`, `judge/`, `database/` are blocked by `.htaccess`.
* Sandbox: compiled programs run without a shell, with a clean environment in a throw-away directory, under `ulimit` limits; quoted/absolute `#include`s are rejected.
  **This is adequate for a lab, but it is not a hardened sandbox.** For a public deployment run the judge inside Docker / nsjail / firejail —
  set `SANDBOX_PREFIX` in `config/config.php` (e.g. `['firejail','--quiet','--net=none']`). On Windows, memory limits are not enforced.

## Folder structure
```
solvex/
├── index.php, register.php, login.php, logout.php, profile.php
├── problems.php, problem.php, submit.php, submission.php, submissions.php
├── contests.php, contest.php, contest_register.php, leaderboard.php, announcements.php
├── admin/        dashboard, contests, problems, test cases, users, submissions, announcements
├── api/status.php  verdict polling
├── judge/        worker.php (one submission), daemon.php (loop)
├── includes/     db, functions (auth/CSRF/helpers), judge core, layout
├── config/config.php
├── database/solvex.sql   schema + demo data
├── assets/       Bootstrap 5 (bundled, works offline), CSS, JS
└── storage/judge/  temporary judge workspaces
```
