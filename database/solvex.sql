-- SolveX database schema + demo data (MySQL 5.7+ / MariaDB 10.3+)
-- Import with phpMyAdmin (XAMPP) or:  mysql -u root < database/solvex.sql
CREATE DATABASE IF NOT EXISTS solvex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solvex;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS submissions, registrations, test_cases, contest_problems, announcements, problems, contests, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  email         VARCHAR(150) NOT NULL UNIQUE,
  university_id VARCHAR(30)  NOT NULL,
  department    VARCHAR(80)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_university_id (university_id)
) ENGINE=InnoDB;

CREATE TABLE contests (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  description TEXT,
  start_time  DATETIME NOT NULL,
  end_time    DATETIME NOT NULL,
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contest_time (start_time, end_time),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE problems (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(150) NOT NULL,
  statement       TEXT NOT NULL,
  input_format    TEXT,
  output_format   TEXT,
  sample_input    TEXT,
  sample_output   TEXT,
  difficulty      ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
  time_limit_ms   INT UNSIGNED NOT NULL DEFAULT 1000,
  memory_limit_mb INT UNSIGNED NOT NULL DEFAULT 256,
  is_public       TINYINT(1) NOT NULL DEFAULT 1,
  created_by      INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_problem_diff (difficulty),
  FULLTEXT KEY ft_problem (title),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE contest_problems (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contest_id INT UNSIGNED NOT NULL,
  problem_id INT UNSIGNED NOT NULL,
  label      VARCHAR(5) NOT NULL,
  points     INT UNSIGNED NOT NULL DEFAULT 100,
  UNIQUE KEY uq_cp_problem (contest_id, problem_id),
  UNIQUE KEY uq_cp_label (contest_id, label),
  FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
  FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE test_cases (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  problem_id      INT UNSIGNED NOT NULL,
  input_data      MEDIUMTEXT NOT NULL,
  expected_output MEDIUMTEXT NOT NULL,
  is_sample       TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tc_problem (problem_id),
  FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contest_id    INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_registration (contest_id, user_id),
  FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE submissions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  problem_id    INT UNSIGNED NOT NULL,
  contest_id    INT UNSIGNED NULL,
  source_code   MEDIUMTEXT NOT NULL,
  verdict       ENUM('Pending','Judging','Accepted','Wrong Answer','Compilation Error','Time Limit Exceeded','Runtime Error') NOT NULL DEFAULT 'Pending',
  time_ms       INT UNSIGNED NOT NULL DEFAULT 0,
  score         INT UNSIGNED NOT NULL DEFAULT 0,
  judge_message TEXT,
  submitted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  judged_at     DATETIME NULL,
  INDEX idx_sub_user (user_id, submitted_at),
  INDEX idx_sub_board (contest_id, user_id, problem_id, verdict),
  INDEX idx_sub_problem (problem_id, verdict),
  INDEX idx_sub_verdict (verdict, id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE,
  FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(150) NOT NULL,
  body       TEXT NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------- demo data ----------------
-- Admin login:   admin    / Admin@123
-- Student login: student1 / Student@123   (change both passwords after first login!)
INSERT INTO users (full_name, username, email, university_id, department, password_hash, role) VALUES
('SolveX Administrator','admin','admin@solvex.local','ADMIN-001','Computer Science and Engineering','$2y$10$2qqa4Ru30LkR0KyFl0chGeF1dx1wXCqHGlQ9yvWUZ4Zr3xa3P3LVW','admin'),
('Demo Student','student1','student1@solvex.local','1120-0001','Computer Science and Engineering','$2y$10$Jba1BeWWhzovonjGMK/ceOGGtQAz3FhEIyp9Twu9AZf8IFcboaefu','student');

INSERT INTO problems (title, statement, input_format, output_format, sample_input, sample_output, difficulty, time_limit_ms, memory_limit_mb, is_public, created_by) VALUES
('Sum of Two Numbers','Given two integers a and b, print their sum.','A single line containing two integers a and b (-10^9 <= a, b <= 10^9).','Print a single integer: a + b.','5 7','12','Easy',1000,256,1,1),
('Even or Odd','Given an integer n, decide whether it is even or odd.','A single integer n (-10^9 <= n <= 10^9).','Print "Even" if n is even, otherwise print "Odd".','7','Odd','Easy',1000,256,1,1),
('Maximum of Array','Given an array of n integers, print the largest value.','The first line contains n (1 <= n <= 10^5). The second line contains n integers.','Print the maximum element.','5\n3 9 2 7 4','9','Medium',1000,256,0,1);

INSERT INTO test_cases (problem_id, input_data, expected_output, is_sample) VALUES
(1,'5 7\n','12\n',1),(1,'-3 3\n','0\n',0),(1,'1000000000 1000000000\n','2000000000\n',0),(1,'0 0\n','0\n',0),
(2,'7\n','Odd\n',1),(2,'10\n','Even\n',0),(2,'-3\n','Odd\n',0),(2,'0\n','Even\n',0),
(3,'5\n3 9 2 7 4\n','9\n',1),(3,'3\n-5 -2 -9\n','-2\n',0),(3,'1\n42\n','42\n',0);

INSERT INTO contests (title, description, start_time, end_time, created_by) VALUES
('SolveX Demo Contest','Warm-up contest for the lab. Solve as many problems as you can - points are awarded for the first Accepted verdict on each problem.',NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 3 DAY, 1);
INSERT INTO contest_problems (contest_id, problem_id, label, points) VALUES (1,1,'A',100),(1,2,'B',100),(1,3,'C',200);
INSERT INTO registrations (contest_id, user_id) VALUES (1,2);

INSERT INTO announcements (title, body, created_by) VALUES
('Welcome to SolveX!','Code. Compete. Conquer. Register for the demo contest, submit your C++ solutions and watch the leaderboard update live.',1);
