<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$students = (int)db_row($conn, 'SELECT COUNT(*) AS n FROM studenttbl')['n'];
$subjects = (int)db_row($conn, 'SELECT COUNT(*) AS n FROM subjecttbl')['n'];
$waiting  = (int)db_row($conn, "SELECT COUNT(*) AS n FROM applications WHERE status IN ('Pending','Under Review')")['n'];
$forms    = (int)db_row($conn, "SELECT COUNT(*) AS n FROM student_subject WHERE approved = '0'")['n'];

$welcome = 'Registrar Dashboard';
$welcomeSub = 'Review applications, manage students and subjects.';
$pageTitle = 'Home';
$current = 'home';
include('includes/header.php');
echo flash_show();
include('../home_content.php');
?>
<div class="grid2">
  <div class="card"><h2>Applications Waiting</h2><p class="stat"><?php echo $waiting; ?></p><a class="btn" href="applications.php?status=Pending">Review Applications</a></div>
  <div class="card"><h2>Students</h2><p class="stat"><?php echo $students; ?></p><a class="btn btn-light" href="userreg.php">Manage Students</a></div>
  <div class="card"><h2>Subjects</h2><p class="stat"><?php echo $subjects; ?></p><a class="btn btn-light" href="subjectreg.php">Manage Subjects</a></div>
  <div class="card"><h2>Subject Forms to Approve</h2><p class="stat"><?php echo $forms; ?></p><a class="btn btn-light" href="report.php">Open Report</a></div>
</div>
<?php include('../home_sections.php'); include('includes/footer.php'); ?>
