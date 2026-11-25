<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stu = db_row($conn, 'SELECT s.*, u.username FROM studenttbl s LEFT JOIN usertbl u ON u.ID = s.accountID WHERE s.ID = ?', 'i', [$id]);
if (!$stu) {
    $_SESSION['flash'] = 'Student not found.';
    header('Location: userreg.php');
    exit;
}
$error = '';

// Map database columns -> form field names
$map = ['snum' => 'studnum', 'course' => 'course', 'yrlvlsec' => 'yearlvl', 'fname' => 'fname', 'minitial' => 'minitial', 'lname' => 'lname',
        'gender' => 'gender', 'bdate' => 'birthdate', 'address' => 'address', 'cnumber' => 'contact', 'email' => 'emailaddress',
        'fathername' => 'fathername', 'mothername' => 'mothername', 'guardianname' => 'guardianname', 'cstatus' => 'civilstatus',
        'bplace' => 'birthplace', 'elemschool' => 'elemschool', 'yrgradelem' => 'dategradelem', 'hschool' => 'hsschool',
        'yrgradhs' => 'datehsgrad', 'lastschool' => 'lastgrad', 'yrlast' => 'datelastgrad', 'address2' => 'addresslastgrad'];

$v = ['username' => $stu['username']];
foreach ($map as $form => $col) { $v[$form] = $stu[$col]; }

if (isset($_POST['save'])) {
    $v = [];
    foreach (array_merge(['username'], array_keys($map)) as $k) { $v[$k] = trim($_POST[$k] ?? ''); }
    $newpass = $_POST['password'] ?? '';
    $accID = (int)$stu['accountID'];

    if ($v['username'] === '' || $v['snum'] === '' || $v['fname'] === '' || $v['lname'] === '' || $v['yrlvlsec'] === '') {
        $error = 'Please fill in all required fields.';
    } elseif (db_row($conn, 'SELECT ID FROM usertbl WHERE username = ? AND ID <> ?', 'si', [$v['username'], $accID])) {
        $error = 'That username is already taken.';
    } elseif (db_row($conn, 'SELECT ID FROM studenttbl WHERE studnum = ? AND ID <> ?', 'si', [$v['snum'], $id])) {
        $error = 'That student number is already registered.';
    } else {
        $cols = array_values($map);
        $set  = implode(', ', array_map(function ($c) { return "`$c` = ?"; }, $cols));
        $vals = [];
        foreach (array_keys($map) as $form) { $vals[] = $v[$form]; }
        $vals[] = $id;
        $ok = db_run($conn, "UPDATE studenttbl SET $set WHERE ID = ?", str_repeat('s', count($cols)) . 'i', $vals);

        if ($ok) {
            $fullname = $v['fname'] . ' ' . $v['minitial'] . ' ' . $v['lname'];
            $ok = db_run($conn, 'UPDATE usertbl SET username = ?, fullname = ? WHERE ID = ?', 'ssi', [$v['username'], $fullname, $accID]);
            if ($ok && $newpass !== '') {
                db_run($conn, 'UPDATE usertbl SET password = ? WHERE ID = ?', 'si', [password_hash($newpass, PASSWORD_DEFAULT), $accID]);
            }
            // Keep the student's past enrollment forms linked if the student number changed
            if ($v['snum'] !== $stu['studnum']) {
                db_run($conn, 'UPDATE student_subject SET studentID = ? WHERE studentID = ?', 'ss', [$v['snum'], $stu['studnum']]);
            }
        }
        if ($ok) {
            $_SESSION['flash'] = 'Student details updated.';
            header('Location: userreg.php');
            exit;
        }
        $error = 'Could not save the changes. Please check the entries and try again.';
    }
}

$pageTitle = 'Edit Student';
$current = 'users';
include('includes/header.php');
?>
<div class="page-head">
  <h1>Edit Student</h1>
  <a class="btn btn-light" href="userreg.php">&larr; Back to list</a>
</div>
<?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>

<form method="POST">
<input type="hidden" name="id" value="<?php echo $id; ?>">
<?php $isEdit = true; include('student_form.php'); ?>
  <div class="actions">
    <input type="submit" name="save" value="Save Changes">
    <a class="btn btn-light" href="userreg.php">Cancel</a>
  </div>
</form>
<?php include('includes/footer.php'); ?>
