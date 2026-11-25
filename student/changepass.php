<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$getID = (int)$_SESSION['userID'];
$error = '';

if (isset($_POST['cpass'])) {
    $old  = $_POST['opassword'] ?? '';
    $new  = $_POST['password'] ?? '';
    $new2 = $_POST['password2'] ?? '';

    $acc = db_row($conn, 'SELECT password FROM usertbl WHERE ID = ? LIMIT 1', 'i', [$getID]);

    if (!$acc || !password_ok($old, $acc['password'])) {
        $error = 'The old password you entered is wrong. Please try again.';
    } elseif ($new === '') {
        $error = 'Please enter a new password.';
    } elseif ($new !== $new2) {
        $error = 'The new passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        if (db_run($conn, 'UPDATE usertbl SET password = ? WHERE ID = ?', 'si', [$hash, $getID])) {
            $_SESSION['flash'] = 'Password updated successfully.';
            header('Location: studentindex.php');
            exit;
        }
        $error = 'Could not update the password. If this keeps happening, run fix_database.sql.';
    }
}

$pageTitle = 'Change Password';
$current = 'profile';
include('includes/header.php');
?>
<div class="form-narrow">
  <div class="page-head"><h1>Change Password</h1></div>
  <?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>
  <form method="POST">
    <fieldset>
      <legend>Account Details</legend>
      <div class="field" style="margin-bottom:12px;"><label for="o">Old Password</label><input type="password" id="o" name="opassword" required></div>
      <div class="field" style="margin-bottom:12px;"><label for="n">New Password</label><input type="password" id="n" name="password" required></div>
      <div class="field"><label for="n2">Confirm New Password</label><input type="password" id="n2" name="password2" required></div>
    </fieldset>
    <div class="actions">
      <input type="submit" name="cpass" value="Change Password">
      <a class="btn btn-light" href="studentprofile.php">Cancel</a>
    </div>
  </form>
</div>
<?php include('includes/footer.php'); ?>
