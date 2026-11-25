<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stu = db_row($conn, 'SELECT ID, accountID, fname, minitial, lname, studnum, status, suspended_until, suspend_reason FROM studenttbl WHERE ID = ?', 'i', [$id]);
if (!$stu) {
    $_SESSION['flash'] = 'Student not found.';
    header('Location: userreg.php');
    exit;
}
if ($stu['status'] === 'Expelled') { flash_set('This student is expelled and cannot be suspended.', 'err'); header('Location: userreg.php'); exit; }
$name = $stu['fname'] . ' ' . $stu['minitial'] . ' ' . $stu['lname'];
$isSuspended = $stu['suspended_until'] !== null && $stu['suspended_until'] >= date('Y-m-d');
$error = '';

if (isset($_POST['suspend'])) {
    $amount = (int)($_POST['amount'] ?? 0);
    $unit   = $_POST['unit'] ?? 'days';
    $reason = trim($_POST['reason'] ?? '');
    if ($amount < 1 || $amount > 999 || !in_array($unit, ['days', 'weeks', 'months'], true)) {
        $error = 'Enter how long the suspension lasts (1 or more).';
    } else {
        $until = date('Y-m-d', strtotime("+$amount $unit"));
        db_run($conn, 'UPDATE studenttbl SET suspended_until = ?, suspend_reason = ? WHERE ID = ?', 'ssi', [$until, $reason, $id]);
        $_SESSION['flash'] = $name . ' is suspended until ' . date('F j, Y', strtotime($until)) . '.';
        header('Location: userreg.php');
        exit;
    }
}
if (isset($_POST['lift'])) {
    db_run($conn, "UPDATE studenttbl SET suspended_until = NULL, suspend_reason = '' WHERE ID = ?", 'i', [$id]);
    $_SESSION['flash'] = 'Suspension lifted for ' . $name . '.';
    header('Location: userreg.php');
    exit;
}

$pageTitle = 'Suspend Student';
$current = 'users';
include('includes/header.php');
?>
<div class="form-narrow">
  <div class="page-head"><h1>Suspend Student</h1></div>
  <?php if ($error): ?><div class="msg msg-err"><?php echo e($error); ?></div><?php endif; ?>

  <div class="card">
    <b><?php echo e($name); ?></b> <span class="muted">(<?php echo e($stu['studnum']); ?>)</span><br>
    <?php if ($isSuspended): ?>
      <span class="badge">Suspended until <?php echo e(date('F j, Y', strtotime($stu['suspended_until']))); ?></span>
      <?php if ($stu['suspend_reason'] !== ''): ?><div class="muted" style="margin-top:6px;">Reason: <?php echo e($stu['suspend_reason']); ?></div><?php endif; ?>
      <form method="POST" style="margin-top:12px;">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="submit" name="lift" value="Lift Suspension Now">
      </form>
    <?php else: ?>
      <span class="badge ok">Active</span>
    <?php endif; ?>
  </div>

  <form method="POST">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <fieldset>
      <legend><?php echo $isSuspended ? 'Change suspension' : 'Suspend for'; ?></legend>
      <div class="form-grid">
        <div class="field"><label for="amount">Length</label><input type="number" id="amount" name="amount" min="1" max="999" value="<?php echo e($_POST['amount'] ?? 7); ?>" required></div>
        <div class="field">
          <label for="unit">Unit</label>
          <select id="unit" name="unit">
            <?php foreach (['days' => 'Day(s)', 'weeks' => 'Week(s)', 'months' => 'Month(s)'] as $k => $l): ?>
              <option value="<?php echo $k; ?>"<?php echo ($_POST['unit'] ?? 'days') === $k ? ' selected' : ''; ?>><?php echo $l; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field full"><label for="reason">Reason (optional)</label><input type="text" id="reason" name="reason" maxlength="255" value="<?php echo e($_POST['reason'] ?? ''); ?>"></div>
      </div>
      <p class="muted" style="margin-bottom:0;">While suspended, the student cannot log in. The suspension ends automatically.</p>
    </fieldset>
    <div class="actions">
      <input type="submit" name="suspend" value="Suspend Student" onclick="return confirm('Suspend this student?');">
      <a class="btn btn-light" href="userreg.php">Cancel</a>
    </div>
  </form>
</div>
<?php include('includes/footer.php'); ?>
