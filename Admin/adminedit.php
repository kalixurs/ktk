<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$adminID = (int)$_SESSION['userID'];
$admin = db_row($conn, "SELECT username FROM usertbl WHERE ID = ? AND user_type = 'admin'", 'i', [$adminID]);
$error = '';

if ($admin && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $dupe = db_row($conn, 'SELECT ID FROM usertbl WHERE username = ? AND ID <> ?', 'si', [$username, $adminID]);
    if ($username === '') {
        $error = 'Username is required.';
    } elseif ($dupe) {
        $error = 'That username is already taken.';
    } else {
        $ok = db_run($conn, 'UPDATE usertbl SET username = ? WHERE ID = ?', 'si', [$username, $adminID]);
        // Password is only changed when something is typed in the box
        if ($ok && $password !== '') {
            $ok = db_run($conn, 'UPDATE usertbl SET password = ? WHERE ID = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), $adminID]);
        }
        if ($ok) {
            $_SESSION['username'] = $username;
            $_SESSION['flash'] = 'Successfully updated the admin account.';
            header('Location: userreg.php');
            exit;
        }
        $error = 'Could not save the changes. Run fix_database.sql if you imported an older database.';
    }
    $admin['username'] = $username;
}

$pageTitle = 'Edit Admin Account';
$current = 'users';
include('includes/header.php');
?>
<div class="form-narrow">
  <div class="page-head"><h1>Edit Admin Account</h1></div>
  <?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>
  <?php if (!$admin): ?>
    <div class="msg msg-err">Admin account not found.</div>
  <?php else: ?>
  <form method="POST">
    <fieldset>
      <legend>Account Details</legend>
      <div class="field" style="margin-bottom:12px;">
        <label for="u">Username <em class="req">*</em></label>
        <input type="text" id="u" name="username" required maxlength="50" value="<?php echo e($admin['username']); ?>">
      </div>
      <div class="field">
        <label for="p">New Password <span class="muted">(leave blank to keep the current password)</span></label>
        <input type="password" id="p" name="password" maxlength="50" autocomplete="new-password">
      </div>
    </fieldset>
    <div class="actions">
      <input type="submit" name="register" value="Save Changes">
      <a class="btn btn-light" href="userreg.php">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php include('includes/footer.php'); ?>
