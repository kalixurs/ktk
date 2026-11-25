<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stu = db_row($conn, 'SELECT * FROM studenttbl WHERE ID = ?', 'i', [$id]);
if (!$stu) { flash_set('Student not found.', 'err'); header('Location: userreg.php'); exit; }
$name = trim($stu['fname'] . ' ' . $stu['minitial'] . ' ' . $stu['lname']);
$expelled = $stu['status'] === 'Expelled';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'expel' && !$expelled) {
        // The record and all past enrollments are KEPT (history); only the status changes and login is blocked.
        db_run($conn, "UPDATE studenttbl SET status = 'Expelled', expelled_at = CURDATE(), expel_reason = ?, suspended_until = NULL, suspend_reason = '' WHERE ID = ?",
               'si', [substr(trim($_POST['reason'] ?? ''), 0, 255), $id]);
        flash_set($name . ' has been expelled. Their records were kept.');
    } elseif ($action === 'reinstate' && $expelled) {
        db_run($conn, "UPDATE studenttbl SET status = 'Active', expelled_at = NULL, expel_reason = '' WHERE ID = ?", 'i', [$id]);
        flash_set($name . ' has been reinstated.');
    } elseif ($action === 'delete' && $expelled) {
        // Permanent removal: only possible for expelled students. Their login is removed too.
        db_run($conn, 'DELETE FROM studenttbl WHERE ID = ?', 'i', [$id]);
        db_run($conn, "DELETE FROM usertbl WHERE ID = ? AND user_type = 'student'", 'i', [(int)$stu['accountID']]);
        flash_set('The record of ' . $name . ' was permanently deleted.');
    } else {
        flash_set('That action is not available.', 'err');
    }
    header('Location: userreg.php');
    exit;
}

$pageTitle = 'Expel Student';
$current = 'users';
include('includes/header.php');
?>
<div class="form-narrow">
  <div class="page-head"><h1><?php echo $expelled ? 'Expelled Student' : 'Expel Student'; ?></h1></div>
  <div class="card">
    <b><?php echo e($name); ?></b> <span class="muted">(<?php echo e($stu['studnum']); ?>)</span>
    <?php if ($expelled): ?>
      <p><span class="badge expelled">Expelled</span> since <?php echo e(date('F j, Y', strtotime($stu['expelled_at']))); ?>
        <?php if ($stu['expel_reason'] !== ''): ?><br><span class="muted">Reason: <?php echo e($stu['expel_reason']); ?></span><?php endif; ?></p>
      <p class="muted">This student cannot log in. Their records are still kept.</p>
      <form method="POST" class="actions" style="justify-content:flex-start;">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <button class="btn btn-ok" name="action" value="reinstate" onclick="return confirm('Reinstate this student?');">Reinstate Student</button>
        <button class="btn btn-danger" name="action" value="delete" onclick="return confirm('PERMANENTLY delete this student record and login? This cannot be undone.');">Delete Record Permanently</button>
      </form>
    <?php else: ?>
      <p class="muted">Expelling ends the student&rsquo;s enrollment. They will no longer be able to log in. Their profile and past enrollment forms are kept for school records.</p>
      <form method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="field" style="margin-bottom:14px;"><label for="reason">Reason (optional)</label><input type="text" id="reason" name="reason" maxlength="255"></div>
        <div class="actions" style="justify-content:flex-start; margin:0;">
          <button class="btn btn-danger" name="action" value="expel" onclick="return confirm('Expel <?php echo e(addslashes($name)); ?>? They will no longer be able to log in.');">Expel Student</button>
          <a class="btn btn-light" href="userreg.php">Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include('includes/footer.php'); ?>
