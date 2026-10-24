<?php
require __DIR__ . '/includes/functions.php';
if (current_user()) redirect('index.php');
$err = []; $v = ['full_name' => '', 'username' => '', 'email' => '', 'university_id' => '', 'department' => ''];
if (is_post()) {
    csrf_verify();
    foreach ($v as $k => $_) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
    if (strlen($v['full_name']) < 3 || strlen($v['full_name']) > 100) $err[] = 'Full name must be 3–100 characters.';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $v['username'])) $err[] = 'Username: 3–30 letters, digits or underscore.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || strlen($v['email']) > 150) $err[] = 'Enter a valid email address.';
    if ($v['university_id'] === '' || strlen($v['university_id']) > 30) $err[] = 'University ID is required (max 30 characters).';
    if ($v['department'] === '' || strlen($v['department']) > 80) $err[] = 'Department is required (max 80 characters).';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err[] = 'Password must be at least 8 characters with a letter and a digit.';
    if ($pw !== $pw2) $err[] = 'Passwords do not match.';
    if (!$err) {
        if (q('SELECT 1 FROM users WHERE username = ? OR email = ?', [$v['username'], $v['email']])->fetch()) $err[] = 'That username or email is already registered.';
    }
    if (!$err) {
        q('INSERT INTO users (full_name, username, email, university_id, department, password_hash) VALUES (?,?,?,?,?,?)',
          [$v['full_name'], $v['username'], $v['email'], $v['university_id'], $v['department'], password_hash($pw, PASSWORD_DEFAULT)]);
        flash('Registration successful! You can log in now.');
        redirect('login.php');
    }
}
$page_title = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card"><div class="card-body p-4">
  <h3 class="mb-3">Create your account</h3>
  <?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
  <form method="post" autocomplete="off"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?= e($v['full_name']) ?>" required maxlength="100"></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Username</label><input class="form-control" name="username" value="<?= e($v['username']) ?>" required maxlength="30"></div>
      <div class="col-md-6 mb-3"><label class="form-label">University ID</label><input class="form-control" name="university_id" value="<?= e($v['university_id']) ?>" required maxlength="30"></div>
    </div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($v['email']) ?>" required maxlength="150"></div>
    <div class="mb-3"><label class="form-label">Department</label><input class="form-control" name="department" value="<?= e($v['department']) ?>" placeholder="e.g. Computer Science and Engineering" required maxlength="80"></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required minlength="8"></div>
      <div class="col-md-6 mb-3"><label class="form-label">Confirm password</label><input type="password" class="form-control" name="password2" required minlength="8"></div>
    </div>
    <button class="btn btn-primary w-100">Register</button>
    <div class="text-center mt-3 small">Already registered? <a href="<?= url('login.php') ?>">Login</a></div>
  </form>
</div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
