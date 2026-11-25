<?php
session_start();
include('logcheck.php');                 // applicants only
include('includes/conn.php');

// An applicant can only ever see the application that belongs to their own account
$app = db_row($conn, 'SELECT * FROM applications WHERE account_id = ? LIMIT 1', 'i', [(int)$_SESSION['userID']]);
$justApplied = $_SESSION['just_applied'] ?? '';
unset($_SESSION['just_applied']);

$queuePos = $queueTotal = 0;
if ($app && in_array($app['status'], ['Pending', 'Under Review'], true)) {
    // Real queue: all applications still waiting, in the order they were submitted (ties broken by ID)
    $queuePos   = (int)db_row($conn, "SELECT COUNT(*) AS n FROM applications WHERE status IN ('Pending','Under Review') AND (submitted_at < ? OR (submitted_at = ? AND ID <= ?))",
                              'ssi', [$app['submitted_at'], $app['submitted_at'], $app['ID']])['n'];
    $queueTotal = (int)db_row($conn, "SELECT COUNT(*) AS n FROM applications WHERE status IN ('Pending','Under Review')")['n'];
}

// Progress tracker: which step is done / current / failed
$steps = ['Application Submitted', 'Under Review', 'Decision', 'Enrolled'];
$state = ['done', '', '', ''];
if ($app) {
    switch ($app['status']) {
        case 'Pending':      $state = ['done', 'now', '', '']; break;
        case 'Under Review': $state = ['done', 'done', 'now', '']; break;
        case 'Approved':     $state = ['done', 'done', 'done', 'done']; break;
        case 'Declined':     $state = ['done', 'done', 'fail', '']; break;
    }
    if ($app['status'] === 'Declined') { $steps[2] = 'Declined'; }
}
$badge = ['Pending' => '', 'Under Review' => 'review', 'Approved' => 'approved', 'Declined' => 'declined'];

$pageTitle = 'My Application';
$current = 'home';
include('includes/header.php');
?>
<div class="page-head"><div><h1>My Application</h1><p>Follow the progress of your enrollment application here.</p></div></div>

<?php if (!$app): ?>
  <div class="msg msg-err">We could not find an application for your account. Please contact the registrar&rsquo;s office.</div>
<?php else: ?>

<?php if ($justApplied): ?>
  <div class="card" style="border-color:#bfe3cc;">
    <h2>Application submitted!</h2>
    <p>Thank you. Your application was received and is now <b>Pending</b>. Please write down or screenshot your reference number:</p>
    <div class="refbox"><span class="muted">Reference Number</span><b><?php echo e($app['ref_no']); ?></b></div>
    <p class="muted" style="margin-bottom:0;">You can log in anytime with the username and password you just created to check your status.</p>
  </div>
<?php endif; ?>

<div class="card">
  <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px; align-items:flex-start;">
    <div>
      <span class="muted">Reference Number</span><br>
      <b style="font:700 22px Georgia,serif; color:var(--blue-dark);"><?php echo e($app['ref_no']); ?></b>
    </div>
    <div><span class="badge <?php echo $badge[$app['status']]; ?>" style="font-size:14px; padding:6px 16px;"><?php echo e($app['status']); ?></span></div>
  </div>

  <ol class="tracker" style="margin-top:26px;">
    <?php foreach ($steps as $i => $label): ?>
      <li data-n="<?php echo $i + 1; ?>" class="<?php echo $state[$i]; ?>"><?php echo e($label); ?></li>
    <?php endforeach; ?>
  </ol>

  <div class="kv" style="margin-top:26px;">
    <div><span>Date Submitted</span><b><?php echo e(date('F j, Y g:i A', strtotime($app['submitted_at']))); ?></b></div>
    <div><span>Last Status Update</span><b><?php echo e(date('F j, Y g:i A', strtotime($app['updated_at']))); ?></b></div>
    <?php if ($queuePos > 0): ?>
      <div><span>Your Place in Line</span><b>Number <?php echo $queuePos; ?> of <?php echo $queueTotal; ?> waiting</b></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($app['status'] === 'Pending'): ?>
  <div class="msg msg-info">Your application is waiting to be checked by the registrar. You do not need to do anything. Please check again later.</div>
<?php elseif ($app['status'] === 'Under Review'): ?>
  <div class="msg msg-info">The registrar is now reviewing your application. Please prepare your original documents just in case they are requested.</div>
<?php elseif ($app['status'] === 'Declined'): ?>
  <div class="msg msg-err"><b>Your application was declined.</b> Please visit the registrar&rsquo;s office if you have questions.</div>
<?php elseif ($app['status'] === 'Approved'): ?>
  <div class="msg msg-ok"><b>Congratulations! Your application was approved.</b> Please log out and log in again to open your Student Dashboard.</div>
<?php endif; ?>

<?php if ($app['remarks'] !== ''): ?>
  <div class="card"><h2>Message from the Registrar</h2><p style="margin:0;"><?php echo nl2br(e($app['remarks'])); ?></p></div>
<?php endif; ?>

<div class="card">
  <h2>What You Submitted</h2>
  <div class="kv">
    <div><span>Name</span><b><?php echo e($app['fname'] . ' ' . $app['minitial'] . ' ' . $app['lname']); ?></b></div>
    <div><span>Course</span><b><?php echo e(strtoupper($app['course'])); ?> &middot; <?php echo e($app['yearlvl']); ?></b></div>
    <div><span>Mobile Number</span><b><?php echo e($app['contact']); ?></b></div>
    <div><span>Email</span><b><?php echo e($app['email']); ?></b></div>
    <div><span>Documents Ready</span><b><?php echo $app['requirements'] !== '' ? e($app['requirements']) : 'None ticked'; ?></b></div>
  </div>
</div>
<?php endif; ?>
<?php include('includes/footer.php'); ?>
