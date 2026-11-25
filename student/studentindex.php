<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$student = db_row($conn, 'SELECT * FROM studenttbl WHERE accountID = ? LIMIT 1', 'i', [(int)$_SESSION['userID']]);
$name = $student ? $student['fname'] . ' ' . $student['lname'] : $_SESSION['username'];

// Good news banner for a recently approved application (14 days)
$approvedApp = db_row($conn, "SELECT ref_no FROM applications WHERE account_id = ? AND status = 'Approved' AND updated_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)", 'i', [(int)$_SESSION['userID']]);
$forms = $student ? db_rows($conn, 'SELECT approved FROM student_subject WHERE studentID = ?', 's', [$student['studnum']]) : [];
$formsApproved = count(array_filter($forms, function ($f) { return $f['approved'] === '1'; }));

$welcome = 'Welcome, ' . $name;
$welcomeSub = 'John Paul College Student Dashboard';
$pageTitle = 'Home';
$current = 'home';
include('includes/header.php');
echo flash_show();
if ($approvedApp): ?>
  <div class="msg msg-ok"><b>Congratulations!</b> Your application (<?php echo e($approvedApp['ref_no']); ?>) was approved. You are now an officially enrolled student.</div>
<?php endif;
include('../home_content.php');
?>
<?php if ($student): ?>
<div class="card">
  <h2>My Enrollment</h2>
  <div class="kv">
    <div><span>Enrollment Status</span><b><span class="badge ok">Officially Enrolled</span></b></div>
    <div><span>Student Number</span><b><?php echo e($student['studnum']); ?></b></div>
    <div><span>Course / Year</span><b><?php echo e(strtoupper($student['course']) . ' - ' . $student['yearlvl']); ?></b></div>
    <div><span>Subject Forms</span><b><?php echo count($forms); ?> submitted, <?php echo $formsApproved; ?> approved</b></div>
  </div>
</div>
<?php endif; ?>
<div class="grid2">
  <div class="card"><h2>My Subjects</h2><p class="muted">See the subjects you enrolled in, with schedule and instructor.</p><a class="btn" href="mysubjects.php">View My Subjects</a></div>
  <div class="card"><h2>Select Subjects</h2><p class="muted">Pick your subjects, review the fees, and submit your enrollment form.</p><a class="btn btn-gold" href="enroll.php">Start</a></div>
  <div class="card"><h2>My Profile</h2><p class="muted">View your information or change your password.</p><a class="btn btn-light" href="studentprofile.php">Open Profile</a></div>
</div>
<?php include('../home_sections.php'); include('includes/footer.php'); ?>
