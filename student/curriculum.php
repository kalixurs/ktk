<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$student = db_row($conn, 'SELECT course FROM studenttbl WHERE accountID = ? LIMIT 1', 's', [(string)$_SESSION['userID']]);
$course = $student ? $student['course'] : '';
$subjects = db_rows($conn, 'SELECT * FROM subjecttbl WHERE course_con = ? ORDER BY subjectcode', 's', [$course]);

$pageTitle = 'Curriculum';
$current = 'curriculum';
include('includes/header.php');
?>
<div class="page-head"><h1>Curriculum<?php echo $course ? ' &ndash; ' . e(strtoupper($course)) : ''; ?></h1></div>
<div class="card">
  <div class="table-scroll">
  <table class="data">
    <thead><tr><th>Code</th><th>Title</th><th>Description</th><th class="num">Units</th><th>Room</th><th>Day</th><th>Time</th><th>Instructor</th></tr></thead>
    <tbody>
    <?php if (!$subjects): ?>
      <tr><td colspan="8" class="muted">No subjects found for your course yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($subjects as $s): ?>
      <tr>
        <td><?php echo e($s['subjectcode']); ?></td>
        <td><?php echo e($s['subjecttitle']); ?></td>
        <td><?php echo e($s['description']); ?></td>
        <td class="num"><?php echo e($s['numofunits']); ?></td>
        <td><?php echo e($s['room']); ?></td>
        <td><?php echo e($s['day']); ?></td>
        <td><?php echo e($s['time']); ?></td>
        <td><?php echo e($s['instructor']); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include('includes/footer.php'); ?>
