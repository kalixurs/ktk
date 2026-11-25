<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$error = '';
$ok    = isset($_GET['ok']);

if (isset($_POST['register'])) {
    $f = [];
    foreach (['username','password','course','yrlvlsec','snum','fname','minitial','lname','gender','bdate','address',
              'cnumber','email','fathername','mothername','guardianname','cstatus','bplace','elemschool','yrgradelem',
              'hschool','yrgradhs','lastschool','yrlast','address2'] as $k) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $f['password'] = $_POST['password'] ?? '';

    if ($f['username'] === '' || $f['password'] === '' || $f['snum'] === '' || $f['fname'] === '' || $f['lname'] === '' || $f['yrlvlsec'] === '') {
        $error = 'Please fill in all required fields.';
    } elseif (db_row($conn, 'SELECT ID FROM usertbl WHERE username = ?', 's', [$f['username']])) {
        $error = 'That username is already taken.';
    } elseif (db_row($conn, 'SELECT ID FROM studenttbl WHERE studnum = ?', 's', [$f['snum']])) {
        $error = 'That student number is already registered.';
    } else {
        $fullname = $f['fname'] . ' ' . $f['minitial'] . ' ' . $f['lname'];
        $hash = password_hash($f['password'], PASSWORD_DEFAULT);

        $u = db_run($conn, "INSERT INTO usertbl (username, password, fullname, user_type) VALUES (?, ?, ?, 'student')", 'sss', [$f['username'], $hash, $fullname]);
        if (!$u) {
            $error = 'Could not create the account. If you imported an older database, run fix_database.sql first.';
        } else {
            $newID = (string)mysqli_insert_id($conn);
            $s = db_run($conn,
                'INSERT INTO studenttbl (accountID, studnum, fname, minitial, lname, address, contact, emailaddress, birthdate, course, yearlvl, gender,
                  fathername, mothername, guardianname, birthplace, civilstatus, elemschool, dategradelem, hsschool, datehsgrad, lastgrad, datelastgrad, addresslastgrad)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                str_repeat('s', 24),
                [$newID, $f['snum'], $f['fname'], $f['minitial'], $f['lname'], $f['address'], $f['cnumber'], $f['email'], $f['bdate'], $f['course'], $f['yrlvlsec'], $f['gender'],
                 $f['fathername'], $f['mothername'], $f['guardianname'], $f['bplace'], $f['cstatus'], $f['elemschool'], $f['yrgradelem'], $f['hschool'], $f['yrgradhs'],
                 $f['lastschool'], $f['yrlast'], $f['address2']]);
            if ($s) {
                header('Location: addnewuser.php?ok=1');
                exit;
            }
            // Student record failed: undo the login we just created so nothing is left half-saved
            db_run($conn, 'DELETE FROM usertbl WHERE ID = ?', 'i', [(int)$newID]);
            $error = 'Could not save the student details. Please check the entries and try again.';
        }
    }
}

$pageTitle = 'Add New User';
$current = 'users';
include('includes/header.php');
?>
<div class="page-head">
  <h1>Add New User</h1>
  <a class="btn btn-light" href="userreg.php">&larr; Back to list</a>
</div>
<?php if ($ok): ?><div class="msg msg-ok">Successfully created the new account.</div><?php endif; ?>
<?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>

<form method="POST">
<?php $v = $_POST; $isEdit = false; include('student_form.php'); ?>
  <div class="actions">
    <input type="submit" name="register" value="Add New User">
    <input type="reset" value="Clear">
  </div>
</form>
<?php include('includes/footer.php'); ?>
