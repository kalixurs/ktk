<?php
session_start();
include('conn.php');

$notice = '';
$error  = '';
if (isset($_GET['blocked'])) { $error = 'Your account cannot be used right now. Please log in again to see the reason, or contact the registrar.'; }
if (isset($_GET['out']))     { $notice = 'You have been logged out.'; }

// Already logged in? Go straight to the right dashboard (role comes from the database).
if (!empty($_SESSION['logcheck']) && !empty($_SESSION['userID'])) {
    $u = db_row($conn, 'SELECT user_type FROM usertbl WHERE ID = ?', 'i', [(int)$_SESSION['userID']]);
    if ($u) { header('Location: ' . role_home($u['user_type'])); exit; }
}

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = db_row($conn, 'SELECT ID, username, password, user_type FROM usertbl WHERE username = ? LIMIT 1', 's', [$username]);
    $ok = $user && password_ok($password, $user['password']);

    if (!$ok) {
        $error = 'Wrong username or password. Please try again.';   // same message for both, on purpose
    } else {
        // Old plain-text passwords are upgraded to secure hashes at login
        if (strpos($user['password'], '$2y$') !== 0) {
            db_run($conn, 'UPDATE usertbl SET password = ? WHERE ID = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), $user['ID']]);
        }
        // Suspended / expelled students cannot log in
        $block = $user['user_type'] === 'student' ? account_block_message($conn, $user['ID']) : '';
        if ($block !== '') {
            $error = $block;
        } else {
            session_regenerate_id(true);
            $_SESSION['logcheck'] = 1;
            $_SESSION['username'] = $user['username'];
            $_SESSION['userID']   = (int)$user['ID'];
            $_SESSION['type']     = $user['user_type'];
            header('Location: ' . role_home($user['user_type']));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | John Paul College</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="pub">
  <div class="pub-photo">
    <img class="bg" src="images/header.jpg" alt="John Paul College campus">
    <div class="cap">
      <h2>A center of learning and academic excellence in Oriental Mindoro.</h2>
      <p>Enrollment is open. Apply online, track your application, and view your subjects &mdash; all in one place.</p>
    </div>
  </div>

  <div class="pub-form">
    <div class="login-box">
      <img class="logo" src="logo.jpg" alt="John Paul College">
      <h1>Welcome back</h1>
      <p>Log in to your John Paul College account.</p>

      <?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>
      <?php if ($notice): ?><div class="msg msg-ok"><?php echo e($notice); ?></div><?php endif; ?>

      <form method="POST" autocomplete="on">
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required autofocus value="<?php echo e($_POST['username'] ?? ''); ?>">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <input type="submit" name="submit" value="Log In" class="btn-block">
      </form>

      <div class="enroll-cta">
        <b>Are you a new enrollee?</b>
        <p>Apply for enrollment online. It takes about 10 minutes.</p>
        <a class="btn btn-gold" href="apply.php">Enroll Now</a>
      </div>
      <p class="muted" style="text-align:center; font-size:13px; margin-top:14px;">Already applied? Log in with the username and password you created in your application.</p>
    </div>
  </div>
</div>
</body>
</html>
