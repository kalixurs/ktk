<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$student = db_row($conn, 'SELECT studnum FROM studenttbl WHERE accountID = ? LIMIT 1', 'i', [(int)$_SESSION['userID']]);
$forms = $student ? db_rows($conn, 'SELECT * FROM student_subject WHERE studentID = ? ORDER BY ID DESC', 's', [$student['studnum']]) : [];

$pageTitle = 'My Subjects';
$current = 'mysubjects';
include('includes/header.php');
echo flash_show();
?>
<div class="page-head"><div><h1>My Subjects</h1><p>Subjects from the enrollment forms you submitted.</p></div><a class="btn btn-gold" href="enroll.php">Select Subjects</a></div>

<?php if (!$forms): ?>
  <div class="card"><p class="empty">You have not submitted a subject enrollment form yet.<br><br><a class="btn" href="enroll.php">Select Your Subjects</a></p></div>
<?php endif; ?>

<?php foreach ($forms as $f):
  $codes = array_values(array_filter(array_map('trim', explode(',', $f['subjects'])), 'strlen'));
  $subs = [];
  foreach ($codes as $c) { $s = db_row($conn, 'SELECT * FROM subjecttbl WHERE subjectcode = ? LIMIT 1', 's', [$c]); if ($s) { $subs[] = $s; } }
?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
      <h2>Enrollment Form #<?php echo (int)$f['ID']; ?></h2>
      <div><span class="badge <?php echo $f['approved'] === '1' ? 'approved' : ''; ?>"><?php echo $f['approved'] === '1' ? 'Approved' : 'Waiting for approval'; ?></span> <span class="muted">&middot; <?php echo (int)$f['totalunits']; ?> units</span></div>
    </div>
    <div class="table-scroll"><table class="data">
      <thead><tr><th>Code</th><th>Title</th><th class="num">Units</th><th>Room</th><th>Day</th><th>Time</th><th>Instructor</th></tr></thead>
      <tbody>
      <?php if (!$subs): ?><tr><td colspan="7" class="empty">No subject details found.</td></tr><?php endif; ?>
      <?php foreach ($subs as $s): ?>
        <tr><td><?php echo e($s['subjectcode']); ?></td><td><?php echo e($s['subjecttitle']); ?></td><td class="num"><?php echo e($s['numofunits']); ?></td>
            <td><?php echo e($s['room']); ?></td><td><?php echo e($s['day']); ?></td><td><?php echo e($s['time']); ?></td><td><?php echo e($s['instructor']); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endforeach; ?>
<?php include('includes/footer.php'); ?>
