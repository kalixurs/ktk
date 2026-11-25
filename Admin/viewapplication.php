<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$app = db_row($conn, 'SELECT * FROM applications WHERE ID = ?', 'i', [$id]);
if (!$app) { flash_set('Application not found.', 'err'); header('Location: applications.php'); exit; }
$adminID = (int)$_SESSION['userID'];

// ---------------------------------------------------------------- actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $remarks = substr(trim($_POST['remarks'] ?? ''), 0, 500);

    if ($action === 'review' && $app['status'] === 'Pending') {
        db_run($conn, "UPDATE applications SET status = 'Under Review', reviewed_by = ? WHERE ID = ?", 'ii', [$adminID, $id]);
        flash_set('Application marked as Under Review.');

    } elseif ($action === 'decline' && in_array($app['status'], ['Pending', 'Under Review'], true)) {
        db_run($conn, "UPDATE applications SET status = 'Declined', remarks = ?, reviewed_by = ? WHERE ID = ?", 'sii', [$remarks, $adminID, $id]);
        flash_set('Application declined.');

    } elseif ($action === 'approve') {
        // Everything below happens together or not at all (transaction)
        mysqli_begin_transaction($conn);
        $err = '';
        $a = db_row($conn, 'SELECT * FROM applications WHERE ID = ? FOR UPDATE', 'i', [$id]);    // lock the row: no double approval
        if (!$a || !in_array($a['status'], ['Pending', 'Under Review'], true)) {
            $err = 'This application was already decided.';
        } elseif (!$a['account_id'] || !db_row($conn, "SELECT ID FROM usertbl WHERE ID = ? AND user_type = 'applicant'", 'i', [(int)$a['account_id']])) {
            $err = 'The applicant account is missing, so a student account cannot be created.';
        } elseif (db_row($conn, 'SELECT ID FROM studenttbl WHERE accountID = ? OR (LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND birthdate = ?) LIMIT 1',
                         'isss', [(int)$a['account_id'], $a['fname'], $a['lname'], $a['birthdate']])) {
            $err = 'A student with the same name and birth date already exists. Please check the Students list first.';
        } else {
            // Next free student number: YEAR-0001, YEAR-0002, ...
            $prefix = date('Y') . '-';
            $n = (int)db_row($conn, 'SELECT COUNT(*) AS n FROM studenttbl WHERE studnum LIKE ?', 's', [$prefix . '%'])['n'] + 1;
            while (db_row($conn, 'SELECT ID FROM studenttbl WHERE studnum = ?', 's', [sprintf('%s%04d', $prefix, $n)])) { $n++; }
            $studnum = sprintf('%s%04d', $prefix, $n);

            $address = $a['street'] . ', ' . $a['barangay'] . ', ' . $a['city'] . ', ' . $a['province'] . ($a['zip'] !== '' ? ' ' . $a['zip'] : '');
            $s = db_run($conn,
                'INSERT INTO studenttbl (accountID, studnum, fname, minitial, lname, address, contact, emailaddress, birthdate, course, yearlvl, gender,
                   fathername, mothername, guardianname, birthplace, civilstatus, elemschool, dategradelem, hsschool, datehsgrad, lastgrad, datelastgrad, addresslastgrad)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                'i' . str_repeat('s', 23),
                [(int)$a['account_id'], $studnum, $a['fname'], $a['minitial'], $a['lname'], $address, $a['contact'], $a['email'], $a['birthdate'], $a['course'], $a['yearlvl'], $a['gender'],
                 $a['fathername'], $a['mothername'], $a['guardianname'], $a['birthplace'], $a['civilstatus'], $a['elemschool'], $a['elemyear'], $a['hsschool'], $a['hsyear'],
                 $a['lastschool'], $a['lastyear'], $a['lastaddress']]);
            if (!$s) {
                $err = 'The student record could not be created.';
            } else {
                $studentRowID = mysqli_insert_id($conn);
                $fullname = trim($a['fname'] . ' ' . $a['minitial'] . ' ' . $a['lname']);
                $u = db_run($conn, "UPDATE usertbl SET user_type = 'student', fullname = ? WHERE ID = ?", 'si', [$fullname, (int)$a['account_id']]);
                $p = db_run($conn, "UPDATE applications SET status = 'Approved', student_id = ?, remarks = ?, reviewed_by = ? WHERE ID = ?", 'isii', [$studentRowID, $remarks, $adminID, $id]);
                if (!$u || !$p) { $err = 'The account could not be activated.'; }
            }
        }
        if ($err === '') {
            mysqli_commit($conn);
            flash_set('Application approved. Student number ' . $studnum . ' was created, and the applicant can now open the Student Dashboard.');
        } else {
            mysqli_rollback($conn);
            flash_set($err, 'err');
        }
    } else {
        flash_set('That action is not available for this application.', 'err');
    }
    header('Location: viewapplication.php?id=' . $id);
    exit;
}

$studentNo = '';
if ($app['student_id']) {
    $st = db_row($conn, 'SELECT studnum FROM studenttbl WHERE ID = ?', 'i', [(int)$app['student_id']]);
    $studentNo = $st['studnum'] ?? '';
}
$cls = ['Pending' => '', 'Under Review' => 'review', 'Approved' => 'approved', 'Declined' => 'declined'];
$open = in_array($app['status'], ['Pending', 'Under Review'], true);
function item($label, $value) { return '<div><span>' . e($label) . '</span><b>' . ($value !== '' && $value !== null ? e($value) : '&mdash;') . '</b></div>'; }

$pageTitle = 'Application ' . $app['ref_no'];
$current = 'apps';
include('includes/header.php');
?>
<div class="page-head">
  <div><h1><?php echo e($app['lname'] . ', ' . $app['fname'] . ' ' . $app['minitial']); ?></h1>
  <p>Reference <b><?php echo e($app['ref_no']); ?></b> &middot; submitted <?php echo e(date('F j, Y g:i A', strtotime($app['submitted_at']))); ?></p></div>
  <a class="btn btn-light" href="applications.php">&larr; Back to Applications</a>
</div>
<?php echo flash_show(); ?>

<div class="card">
  <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; align-items:center;">
    <div>Status: <span class="badge <?php echo $cls[$app['status']]; ?>" style="font-size:14px; padding:5px 14px;"><?php echo e($app['status']); ?></span>
      <span class="muted" style="margin-left:10px;">Last update: <?php echo e(date('M j, Y g:i A', strtotime($app['updated_at']))); ?></span></div>
    <?php if ($studentNo): ?><div>Student No.: <b><?php echo e($studentNo); ?></b> &middot; <a href="userreg.php">View in Students</a></div><?php endif; ?>
  </div>

  <?php if ($open): ?>
  <form method="POST" style="margin-top:16px; border-top:1px solid var(--line); padding-top:16px;">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="field" style="margin-bottom:12px;">
      <label for="remarks">Remarks for the applicant (optional &mdash; the applicant can read this)</label>
      <textarea id="remarks" name="remarks" maxlength="500" placeholder="e.g. Please bring your original Form 138 on Monday."></textarea>
    </div>
    <div class="actions" style="justify-content:flex-start; margin:0;">
      <?php if ($app['status'] === 'Pending'): ?>
        <button class="btn btn-light" name="action" value="review" onclick="return confirm('Mark this application as Under Review?');">Mark as Under Review</button>
      <?php endif; ?>
      <button class="btn btn-ok" name="action" value="approve" onclick="return confirm('Approve this application?\n\nA student record and student account will be created.');">Approve</button>
      <button class="btn btn-danger" name="action" value="decline" onclick="return confirm('Decline this application?');">Decline</button>
    </div>
  </form>
  <?php elseif ($app['remarks'] !== ''): ?>
    <p style="margin:14px 0 0;"><span class="muted">Remarks:</span> <?php echo nl2br(e($app['remarks'])); ?></p>
  <?php endif; ?>
</div>

<div class="card"><h2>Student Information</h2><div class="kv">
  <?php echo item('First Name', $app['fname']) . item('Middle Initial', $app['minitial']) . item('Last Name', $app['lname']) . item('Gender', ucfirst($app['gender']))
    . item('Birth Date', date('F j, Y', strtotime($app['birthdate']))) . item('Place of Birth', $app['birthplace']) . item('Civil Status', ucfirst($app['civilstatus']))
    . item('Course', strtoupper($app['course'])) . item('Year Level', $app['yearlvl']); ?>
</div></div>

<div class="card"><h2>Contact &amp; Address</h2><div class="kv">
  <?php echo item('Mobile Number', $app['contact']) . item('Email', $app['email']) . item('House No. / Street', $app['street']) . item('Barangay', $app['barangay'])
    . item('City / Municipality', $app['city']) . item('Province', $app['province']) . item('ZIP Code', $app['zip']); ?>
</div></div>

<div class="card"><h2>Parent / Guardian</h2><div class="kv">
  <?php echo item("Father's Name", $app['fathername']) . item("Mother's Name", $app['mothername']) . item('Guardian', $app['guardianname'])
    . item('Relationship', $app['guardianrelation']) . item("Guardian's Mobile", $app['guardiancontact']); ?>
</div></div>

<div class="card"><h2>Previous Education</h2><div class="kv">
  <?php echo item('Elementary School', $app['elemschool']) . item('Year Graduated', $app['elemyear']) . item('High School', $app['hsschool']) . item('Year Graduated', $app['hsyear'])
    . item('Last School Attended', $app['lastschool']) . item('Year', $app['lastyear']) . item('Address of Last School', $app['lastaddress']); ?>
</div></div>

<div class="card"><h2>Requirements</h2><div class="kv"><?php echo item('Documents the applicant has ready', $app['requirements']); ?></div></div>
<?php include('includes/footer.php'); ?>
