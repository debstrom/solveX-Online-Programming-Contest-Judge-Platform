<?php $an = basename($_SERVER['SCRIPT_NAME'], '.php'); ?>
<ul class="nav nav-pills mb-4">
  <?php foreach (['index' => 'Dashboard', 'contests' => 'Contests', 'problems' => 'Problems', 'submissions' => 'Submissions', 'users' => 'Users', 'announcements' => 'Announcements'] as $f => $l): ?>
    <li class="nav-item"><a class="nav-link <?= $an === $f || ($f === 'problems' && in_array($an, ['problem_form', 'testcases'])) || ($f === 'contests' && $an === 'contest_form') ? 'active' : '' ?>" href="<?= url('admin/' . $f . '.php') ?>"><?= $l ?></a></li>
  <?php endforeach; ?>
</ul>
