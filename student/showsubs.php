<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

// Fee settings (same amounts as the original system)
const PER_UNIT = 296;
const ENTRANCE = 2050;
const INTERNET = 150;
const MISC     = 50;
const LAB_FEE  = 1500;
const TUT_FEE  = 1500;

$student = db_row($conn, 'SELECT * FROM studenttbl WHERE accountID = ? LIMIT 1', 's', [(string)$_SESSION['userID']]);
if (!$student) {
    flash_set('Your account has no student record yet. Please contact the administrator.', 'err');
    header('Location: studentindex.php');
    exit;
}
$stid = $student['studnum'];

// Selected subject codes (comes from enroll.php, and is sent again when the form is submitted)
$codes = array_values(array_unique(array_filter((array)($_POST['subject'] ?? []), 'strlen')));
$sem   = ($_POST['sem'] ?? '') === '2nd Sem' ? '2nd Sem' : '1st Sem';

if (!$codes) {
    header('Location: enroll.php');
    exit;
}

// Load each selected subject from the database and compute fees on the server
$rows = [];
$totalUnits = 0;
$extras = 0;
foreach ($codes as $code) {
    $s = db_row($conn, 'SELECT * FROM subjecttbl WHERE subjectcode = ? LIMIT 1', 's', [$code]);
    if (!$s) { continue; }
    $s['lab'] = ((int)$s['islab'] === 1) ? LAB_FEE : 0;
    $s['tut'] = ((int)$s['istutorial'] === 1) ? TUT_FEE : 0;
    $s['row_total'] = $s['numofunits'] * PER_UNIT + $s['lab'] + $s['tut'];
    $totalUnits += (int)$s['numofunits'];
    $extras += $s['lab'] + $s['tut'];
    $rows[] = $s;
}
if (!$rows) {
    header('Location: enroll.php');
    exit;
}
$grandTotal = $totalUnits * PER_UNIT + ENTRANCE + INTERNET + MISC + $extras;

// Step 2: student confirmed -> save the enrollment form
if (isset($_POST['reg'])) {
    $codeList = implode(',', array_column($rows, 'subjectcode'));
    $ok = db_run($conn,
        "INSERT INTO student_subject (studentID, subjects, totalunits, approved) VALUES (?, ?, ?, '0')",
        'ssi', [$stid, $codeList, $totalUnits]);
    $_SESSION['flash'] = $ok ? 'Enrollment form was successfully sent to the system.' : 'Sorry, the enrollment could not be saved. Please try again.';
    header('Location: studentindex.php');
    exit;
}

// School year (June starts a new school year)
$y = (int)date('Y');
$sy = ((int)date('n') >= 6) ? "$y - " . ($y + 1) : ($y - 1) . " - $y";

$pageTitle = 'Enrollment Form';
$current = 'enroll';
include('includes/header.php');
?>
<div class="print-page">
  <div class="print-head">
    <img src="../logo.jpg" alt="Logo">
    <h2>John Paul College</h2>
    <div class="muted">MG Andaya Compound, Odiong, Roxas, Oriental Mindoro, Philippines</div>
    <h3 style="margin-top:12px;">UNOFFICIAL ENROLLMENT FORM</h3>
    <div><?php echo e($sem); ?> &middot; School Year <?php echo e($sy); ?></div>
  </div>

  <p>
    <b>Student Name:</b> <?php echo e($student['fname'] . ' ' . $student['minitial'] . ' ' . $student['lname']); ?> &nbsp;|&nbsp;
    <b>Course:</b> <?php echo e(strtoupper($student['course'])); ?> &nbsp;|&nbsp;
    <b>Year Level:</b> <?php echo e($student['yearlvl']); ?>
  </p>

  <div class="table-scroll">
  <table class="data">
    <thead>
      <tr>
        <th>Subject</th><th class="num">Units</th><th class="num">Per Unit</th><th class="num">Tutorial</th><th class="num">Lab</th>
        <th>Room</th><th>Day</th><th>Time</th><th>Instructor</th><th class="num">Total</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $s): ?>
      <tr>
        <td><?php echo e($s['subjectcode']); ?></td>
        <td class="num"><?php echo e($s['numofunits']); ?></td>
        <td class="num"><?php echo PER_UNIT; ?></td>
        <td class="num"><?php echo $s['tut'] ? number_format($s['tut']) : ''; ?></td>
        <td class="num"><?php echo $s['lab'] ? number_format($s['lab']) : ''; ?></td>
        <td><?php echo e($s['room']); ?></td>
        <td><?php echo e($s['day']); ?></td>
        <td><?php echo e($s['time']); ?></td>
        <td><?php echo e($s['instructor']); ?></td>
        <td class="num"><?php echo number_format($s['row_total'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <table class="fees" style="margin:16px 0 0 auto; border-collapse:collapse; min-width:300px;">
    <tr><td>Entrance Fee</td><td class="num" style="text-align:right;"><?php echo number_format(ENTRANCE, 2); ?></td></tr>
    <tr><td>Internet</td><td style="text-align:right;"><?php echo number_format(INTERNET, 2); ?></td></tr>
    <tr><td>Misc</td><td style="text-align:right;"><?php echo number_format(MISC, 2); ?></td></tr>
    <tr><td><b>Total Units</b></td><td style="text-align:right;"><b><?php echo $totalUnits; ?></b></td></tr>
    <tr><td><b>TOTAL AMOUNT</b></td><td style="text-align:right;"><b>P <?php echo number_format($grandTotal, 2); ?></b></td></tr>
  </table>

  <div class="print-tools no-print">
    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? The form will be submitted to the system.');">
      <?php foreach ($rows as $s): ?>
        <input type="hidden" name="subject[]" value="<?php echo e($s['subjectcode']); ?>">
      <?php endforeach; ?>
      <input type="hidden" name="sem" value="<?php echo e($sem); ?>">
      <input type="submit" name="reg" value="Submit Enrollment Form">
    </form>
    <input type="button" class="btn-gold" value="Print" onclick="window.print()">
    <a class="btn btn-light" href="enroll.php">Back</a>
  </div>
</div>
<?php include('includes/footer.php'); ?>
