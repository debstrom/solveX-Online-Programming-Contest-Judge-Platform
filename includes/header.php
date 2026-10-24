<?php
require_once __DIR__ . '/functions.php';
$u = current_user();
$page_title = $page_title ?? APP_NAME;
$nav = basename($_SERVER['SCRIPT_NAME'], '.php');
$in_admin = str_contains($_SERVER['SCRIPT_NAME'], '/admin/');
function nav_link(string $href, string $label, bool $active): string {
    return '<li class="nav-item"><a class="nav-link' . ($active ? ' active' : '') . '" href="' . e(url($href)) . '">' . e($label) . '</a></li>';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> · <?= APP_NAME ?></title>
<link href="<?= url('assets/vendor/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sx-nav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>">&lt;/&gt; <?= APP_NAME ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?= nav_link('problems.php', 'Problems', $nav === 'problems' && !$in_admin) ?>
        <?= nav_link('contests.php', 'Contests', in_array($nav, ['contests', 'contest']) && !$in_admin) ?>
        <?php if ($u): ?>
          <?= nav_link('submissions.php', 'My Submissions', $nav === 'submissions' && !$in_admin) ?>
        <?php endif; ?>
        <?= nav_link('announcements.php', 'Announcements', $nav === 'announcements' && !$in_admin) ?>
        <?php if ($u && $u['role'] === 'admin'): ?>
          <?= nav_link('admin/index.php', 'Admin Panel', $in_admin) ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($u): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= e($u['username']) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= url('profile.php') ?>">My Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="post" action="<?= url('logout.php') ?>" class="px-3"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100">Logout</button></form>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <?= nav_link('login.php', 'Login', $nav === 'login') ?>
          <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?= url('register.php') ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php foreach (flash() as [$t, $m]): ?>
  <div class="alert alert-<?= e($t) ?> alert-dismissible fade show"><?= e($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endforeach; ?>
