<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$getID = (string)$_SESSION['userID'];
$r = db_row($conn, 'SELECT * FROM studenttbl WHERE accountID = ? LIMIT 1', 's', [$getID]);
$acc = db_row($conn, 'SELECT username FROM usertbl WHERE ID = ? LIMIT 1', 'i', [(int)$getID]);

// Small helper to print one "label: value" row
function row($label, $value) {
    return '<tr><td class="k">' . e($label) . '</td><td><b>' . e($value) . '</b></td></tr>';
}

$pageTitle = 'My Profile';
$current = 'profile';
include('includes/header.php');
?>
<div class="page-head">
  <h1>My Profile</h1>
  <a class="btn btn-light" href="changepass.php">Change Password</a>
</div>

<?php if (!$r): ?>
  <div class="msg msg-err">Your account has no student record yet. Please contact the administrator.</div>
<?php else: ?>
<div class="grid2">
  <div class="card">
    <h2>Account &amp; College</h2>
    <table class="info-table">
      <?php
      echo row('Username', $acc['username'] ?? '');
      echo row('Student Number', $r['studnum']);
      echo row('Course', strtoupper($r['course']));
      echo row('Section / Year Level', $r['yearlvl']);
      ?>
    </table>
  </div>
  <div class="card">
    <h2>Contact</h2>
    <table class="info-table">
      <?php
      echo row('Address', $r['address']);
      echo row('Contact Number', $r['contact']);
      echo row('Email Address', $r['emailaddress']);
      ?>
    </table>
  </div>
</div>

<div class="card">
  <h2>Personal Details</h2>
  <div class="grid2">
    <table class="info-table">
      <?php
      echo row('First Name', $r['fname']);
      echo row('Middle Initial', $r['minitial']);
      echo row('Last Name', $r['lname']);
      echo row('Gender', ucfirst($r['gender']));
      echo row('Birth Date', $r['birthdate']);
      ?>
    </table>
    <table class="info-table">
      <?php
      echo row('Civil Status', ucfirst($r['civilstatus']));
      echo row("Guardian's Name", $r['guardianname']);
      echo row("Father's Name", $r['fathername']);
      echo row("Mother's Name", $r['mothername']);
      echo row('Birth Place', $r['birthplace']);
      ?>
    </table>
  </div>
</div>

<div class="card">
  <h2>Educational Background</h2>
  <table class="data">
    <thead><tr><th>Level</th><th>School</th><th>Year Graduated</th></tr></thead>
    <tbody>
      <tr><td>Elementary</td><td><?php echo e($r['elemschool']); ?></td><td><?php echo e($r['dategradelem']); ?></td></tr>
      <tr><td>High School</td><td><?php echo e($r['hsschool']); ?></td><td><?php echo e($r['datehsgrad']); ?></td></tr>
      <tr><td>Last School Attended</td><td><?php echo e($r['lastgrad']); ?><br><span class="muted"><?php echo e($r['addresslastgrad']); ?></span></td><td><?php echo e($r['datelastgrad']); ?></td></tr>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php include('includes/footer.php'); ?>
