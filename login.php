<?php
require __DIR__ . '/includes/functions.php';
if (current_user()) redirect('index.php');
$err = '';
if (is_post()) {
    csrf_verify();
    $now = time();
    if (($_SESSION['lock_until'] ?? 0) > $now) {
        $err = 'Too many failed attempts. Wait ' . ($_SESSION['lock_until'] - $now) . ' seconds and try again.';
    } else {
        $id = trim((string)($_POST['identity'] ?? '')); $pw = (string)($_POST['password'] ?? '');
        $row = q('SELECT id, password_hash FROM users WHERE username = ? OR email = ?', [$id, $id])->fetch();
        // verify against a dummy hash when user is unknown, so timing does not reveal valid usernames
        $hash = $row['password_hash'] ?? password_hash('dummy-password', PASSWORD_DEFAULT);
        if (password_verify($pw, $hash) && $row) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$row['id']; $_SESSION['last'] = time(); unset($_SESSION['fails'], $_SESSION['lock_until']);
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT))
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $row['id']]);
            $back = $_SESSION['back'] ?? ''; unset($_SESSION['back']);
            if ($back && str_starts_with($back, BASE_URL . '/') && !str_contains($back, "\n")) { header('Location: ' . $back); exit; }
            redirect('index.php');
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = $now + 60; $_SESSION['fails'] = 0; }
        $err = 'Invalid username/email or password.';
    }
}
$page_title = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5">
<div class="card"><div class="card-body p-4">
  <h3 class="mb-3">Login</h3>
  <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Username or email</label><input class="form-control" name="identity" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
    <button class="btn btn-primary w-100">Login</button>
    <div class="text-center mt-3 small">New here? <a href="<?= url('register.php') ?>">Create an account</a></div>
  </form>
</div></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
