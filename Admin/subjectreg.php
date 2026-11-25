<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$subjects = db_rows($conn, 'SELECT ID, subjectcode, subjecttitle, numofunits FROM subjecttbl ORDER BY subjectcode');

$pageTitle = 'Manage Subjects';
$current = 'subjects';
$useDataTables = true;
include('includes/header.php');
?>
<div class="page-head">
  <h1>Subjects List</h1>
  <a class="icon-btn" href="addnewsubj.php" title="Add new subject"><img src="../images/iconadd2.png" alt="">Add Subject</a>
</div>
<?php if ($flash): ?><div class="msg msg-ok"><?php echo e($flash); ?></div><?php endif; ?>

<div class="card">
  <table id="datatables" class="data display">
    <thead>
      <tr><th>Subject Code</th><th>Subject Name</th><th>Units</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php foreach ($subjects as $s): ?>
      <tr>
        <td><?php echo e($s['subjectcode']); ?></td>
        <td><?php echo e($s['subjecttitle']); ?></td>
        <td><?php echo e($s['numofunits']); ?></td>
        <td><a class="btn btn-danger btn-sm" href="deletecurrsubj.php?accid=<?php echo (int)$s['ID']; ?>" onclick="return confirm('Delete this subject?');">Delete</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
$(document).ready(function () {
  $('#datatables').dataTable({
    "sPaginationType": "full_numbers",
    "aaSorting": [[0, "asc"]],
    "aoColumnDefs": [{ "bSortable": false, "aTargets": [3] }]
  });
});
</script>
<?php include('includes/footer.php'); ?>
