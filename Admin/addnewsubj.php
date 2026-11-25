<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$error = '';
$ok    = isset($_GET['ok']);
function old($key) { return e($_POST[$key] ?? ''); }

if (isset($_POST['register'])) {
    $course  = trim($_POST['course'] ?? 'bsit');
    $scode   = trim($_POST['scode'] ?? '');
    $stitle  = trim($_POST['stitle'] ?? '');
    $desc    = trim($_POST['cdescription'] ?? '');
    $units   = (string)(int)($_POST['numunits'] ?? 1);
    $sroom   = trim($_POST['sroom'] ?? '');
    $stime   = trim($_POST['stime'] ?? '');
    $sday    = trim($_POST['sday'] ?? '');
    $sinstr  = trim($_POST['sinstructor'] ?? '');
    $islab   = isset($_POST['islab']) ? 1 : 0;
    $istut   = isset($_POST['istutorial']) ? 1 : 0;

    if ($scode === '' || $stitle === '') {
        $error = 'Subject code and title are required.';
    } elseif (db_row($conn, 'SELECT ID FROM subjecttbl WHERE subjectcode = ?', 's', [$scode])) {
        $error = 'That subject code already exists.';
    } else {
        // Column names are listed on purpose so the values always land in the right column
        $done = db_run($conn,
            'INSERT INTO subjecttbl (subjectcode, subjecttitle, numofunits, room, `time`, instructor, `day`, islab, istutorial, description, course_con)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            'sssssssiiss', [$scode, $stitle, $units, $sroom, $stime, $sinstr, $sday, $islab, $istut, $desc, $course]);
        if ($done) {
            header('Location: addnewsubj.php?ok=1');
            exit;
        }
        $error = 'Could not save the subject. Please check the entries and try again.';
    }
}

$pageTitle = 'Add New Subject';
$current = 'subjects';
include('includes/header.php');
?>
<div class="page-head">
  <h1>Add New Subject</h1>
  <a class="btn btn-light" href="subjectreg.php">&larr; Back to list</a>
</div>
<?php if ($ok): ?><div class="msg msg-ok">Successfully created the new subject.</div><?php endif; ?>
<?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>

<form method="POST">
  <fieldset>
    <legend>Course Details</legend>
    <div class="field" style="max-width:300px;">
      <label for="course">Select Course <em class="req">*</em></label>
      <select id="course" name="course"><option value="bsit">BSIT</option></select>
    </div>
  </fieldset>

  <fieldset>
    <legend>Subject Details</legend>
    <div class="form-grid">
      <div class="field"><label for="scode">Subject Code <em class="req">*</em></label><input type="text" id="scode" name="scode" required maxlength="50" value="<?php echo old('scode'); ?>"></div>
      <div class="field"><label for="stitle">Subject Title <em class="req">*</em></label><input type="text" id="stitle" name="stitle" required maxlength="100" value="<?php echo old('stitle'); ?>"></div>
      <div class="field">
        <label for="numunits">Number of Units</label>
        <select id="numunits" name="numunits">
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?php echo $i; ?>"<?php echo ((int)($_POST['numunits'] ?? 3) === $i) ? ' selected' : ''; ?>><?php echo $i; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="field"><label for="sroom">Room</label><input type="text" id="sroom" name="sroom" maxlength="100" value="<?php echo old('sroom'); ?>"></div>
      <div class="field"><label for="sday">Day</label><input type="text" id="sday" name="sday" maxlength="100" placeholder="e.g. MWF" value="<?php echo old('sday'); ?>"></div>
      <div class="field"><label for="stime">Time</label><input type="text" id="stime" name="stime" maxlength="100" placeholder="e.g. 8:00-9:00 AM" value="<?php echo old('stime'); ?>"></div>
      <div class="field"><label for="sinstructor">Instructor</label><input type="text" id="sinstructor" name="sinstructor" maxlength="100" value="<?php echo old('sinstructor'); ?>"></div>
      <div class="field full"><label for="cdescription">Subject Description</label><textarea id="cdescription" name="cdescription" maxlength="100"><?php echo old('cdescription'); ?></textarea></div>
      <div class="field radio-row">
        <label><input type="checkbox" name="islab"<?php echo isset($_POST['islab']) ? ' checked' : ''; ?>> With Lab</label>
        <label><input type="checkbox" name="istutorial"<?php echo isset($_POST['istutorial']) ? ' checked' : ''; ?>> With Tutorial</label>
      </div>
    </div>
  </fieldset>

  <div class="actions">
    <input type="submit" name="register" value="Add New Subject">
    <input type="reset" value="Clear">
  </div>
</form>
<?php include('includes/footer.php'); ?>
