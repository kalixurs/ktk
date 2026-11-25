<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$student = db_row($conn, 'SELECT * FROM studenttbl WHERE accountID = ? LIMIT 1', 's', [(string)$_SESSION['userID']]);
if ($student) {
    $_SESSION['studnum'] = $student['studnum'];   // used by showsubs.php when saving the enrollment
}
$subjects = db_rows($conn, 'SELECT * FROM subjecttbl ORDER BY subjectcode');

$pageTitle = 'Enroll Now';
$current = 'enroll';
include('includes/header.php');
?>
<div class="page-head"><div><h1>Select Your Subjects</h1><p>Tick the subjects you want, then continue to review the fees.</p></div></div>

<?php if (!$student): ?>
  <div class="msg msg-err">Your account has no student record yet. Please contact the administrator.</div>
<?php elseif (!$subjects): ?>
  <div class="msg msg-err">No subjects are available yet. Please check again later.</div>
<?php else: ?>
<form method="POST" action="showsubs.php" onsubmit="return checkSelected();">
  <fieldset>
    <legend>Student Details</legend>
    <div class="form-grid">
      <div class="field"><label>Student</label><b><?php echo e($student['fname'] . ' ' . $student['minitial'] . ' ' . $student['lname']); ?></b></div>
      <div class="field"><label>Course</label><b><?php echo e(strtoupper($student['course'])); ?></b></div>
      <div class="field"><label>Year Level / Section</label><b><?php echo e($student['yearlvl']); ?></b></div>
      <div class="field">
        <label for="sem">Semester</label>
        <select name="sem" id="sem">
          <option value="1st Sem">1st Semester</option>
          <option value="2nd Sem">2nd Semester</option>
        </select>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>List of Subjects</legend>
    <div class="table-scroll">
    <table class="data">
      <thead>
        <tr><th>Subject Code</th><th>Subject Title</th><th>Description</th><th class="num">Units</th><th>Select</th></tr>
      </thead>
      <tbody>
      <?php foreach ($subjects as $s): ?>
        <tr>
          <td><?php echo e($s['subjectcode']); ?></td>
          <td><?php echo e($s['subjecttitle']); ?></td>
          <td><?php echo e($s['description']); ?></td>
          <td class="num"><?php echo e($s['numofunits']); ?></td>
          <td><input type="checkbox" name="subject[]" value="<?php echo e($s['subjectcode']); ?>"></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </fieldset>

  <div class="actions">
    <input type="submit" name="register" value="Enroll Selected">
    <input type="reset" value="Clear">
  </div>
</form>
<script>
function checkSelected() {
  if (!document.querySelector('input[name="subject[]"]:checked')) {
    alert('Please select at least one subject.');
    return false;
  }
  return true;
}
</script>
<?php endif; ?>
<?php include('includes/footer.php'); ?>
