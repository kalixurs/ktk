<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$users = db_rows($conn, 'SELECT ID, studnum, fname, minitial, lname, course, yearlvl, status, suspended_until FROM studenttbl ORDER BY lname, fname');
$badge = ['Active' => 'ok', 'Suspended' => 'suspended', 'Expelled' => 'expelled'];

$pageTitle = 'Students';
$current = 'users';
$useDataTables = true;
include('includes/header.php');
?>
<div class="page-head">
  <div><h1>Students</h1><p>Edit, suspend or expel students. New students come from approved applications.</p></div>
  <div>
    <a class="icon-btn" href="adminedit.php" title="Change your admin username or password"><img src="../images/adminedit.png" alt="">My Account</a>
    <a class="icon-btn" href="addnewuser.php" title="Add a student directly"><img src="../images/iconadd.png" alt="">Add Student</a>
  </div>
</div>
<?php echo flash_show(); ?>

<div class="card">
  <?php if (!$users): ?>
    <p class="empty">No students yet. Approve an application to create one.</p>
  <?php else: ?>
  <div class="table-scroll">
  <table id="datatables" class="data display">
    <thead><tr><th>Student No.</th><th>Name</th><th>Course</th><th>Year / Section</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $state = student_state($u); ?>
      <tr>
        <td><?php echo e($u['studnum']); ?></td>
        <td><?php echo e($u['lname'] . ', ' . $u['fname'] . ' ' . $u['minitial']); ?></td>
        <td><?php echo e(strtoupper($u['course'])); ?></td>
        <td><?php echo e($u['yearlvl']); ?></td>
        <td>
          <span class="badge <?php echo $badge[$state]; ?>"><?php echo e($state); ?></span>
          <?php if ($state === 'Suspended'): ?><br><small class="muted">until <?php echo e(date('M j, Y', strtotime($u['suspended_until']))); ?></small><?php endif; ?>
        </td>
        <td style="white-space:nowrap;">
          <a class="btn btn-light btn-sm" href="editstudent.php?id=<?php echo (int)$u['ID']; ?>">Edit</a>
          <?php if ($state !== 'Expelled'): ?>
            <a class="btn btn-sm" href="suspendstudent.php?id=<?php echo (int)$u['ID']; ?>">Suspend</a>
          <?php endif; ?>
          <a class="btn btn-danger btn-sm" href="expelstudent.php?id=<?php echo (int)$u['ID']; ?>"><?php echo $state === 'Expelled' ? 'Expelled record' : 'Expel'; ?></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <script>
  $(document).ready(function () {
    $('#datatables').dataTable({ "sPaginationType": "full_numbers", "aaSorting": [[1, "asc"]], "aoColumnDefs": [{ "bSortable": false, "aTargets": [5] }] });
  });
  </script>
  <?php endif; ?>
</div>
<?php include('includes/footer.php'); ?>
