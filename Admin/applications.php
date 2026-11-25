<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

$statuses = ['Pending', 'Under Review', 'Approved', 'Declined'];
$filter   = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';

$counts = ['' => 0];
foreach ($statuses as $s) { $counts[$s] = 0; }
foreach (db_rows($conn, 'SELECT status, COUNT(*) AS n FROM applications GROUP BY status') as $r) { $counts[$r['status']] = (int)$r['n']; $counts[''] += (int)$r['n']; }

$rows = $filter === ''
    ? db_rows($conn, 'SELECT ID, ref_no, fname, minitial, lname, course, yearlvl, submitted_at, status FROM applications ORDER BY submitted_at DESC, ID DESC')
    : db_rows($conn, 'SELECT ID, ref_no, fname, minitial, lname, course, yearlvl, submitted_at, status FROM applications WHERE status = ? ORDER BY submitted_at DESC, ID DESC', 's', [$filter]);
$cls = ['Pending' => '', 'Under Review' => 'review', 'Approved' => 'approved', 'Declined' => 'declined'];

$pageTitle = 'Applications';
$current = 'apps';
$useDataTables = true;
include('includes/header.php');
?>
<div class="page-head"><div><h1>Enrollment Applications</h1><p>Review new applicants. Click <b>View</b> to see the complete form.</p></div></div>
<?php echo flash_show(); ?>

<div class="chips">
  <a class="chip <?php echo $filter === '' ? 'on' : ''; ?>" href="applications.php">All <small><?php echo $counts['']; ?></small></a>
  <?php foreach ($statuses as $s): ?>
    <a class="chip <?php echo $filter === $s ? 'on' : ''; ?>" href="applications.php?status=<?php echo urlencode($s); ?>"><?php echo e($s); ?> <small><?php echo $counts[$s]; ?></small></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if (!$rows): ?>
    <p class="empty">No applications<?php echo $filter ? ' with status &ldquo;' . e($filter) . '&rdquo;' : ' yet'; ?>.</p>
  <?php else: ?>
  <div class="table-scroll">
  <table id="datatables" class="data display">
    <thead><tr><th>Reference</th><th>Applicant</th><th>Course / Year</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?php echo e($r['ref_no']); ?></b></td>
        <td><?php echo e($r['lname'] . ', ' . $r['fname'] . ' ' . $r['minitial']); ?></td>
        <td><?php echo e(strtoupper($r['course']) . ' - ' . $r['yearlvl']); ?></td>
        <td><?php echo e(date('M j, Y g:i A', strtotime($r['submitted_at']))); ?></td>
        <td><span class="badge <?php echo $cls[$r['status']]; ?>"><?php echo e($r['status']); ?></span></td>
        <td><a class="btn btn-sm" href="viewapplication.php?id=<?php echo (int)$r['ID']; ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <script>
  $(document).ready(function () {
    $('#datatables').dataTable({ "sPaginationType": "full_numbers", "aaSorting": [[3, "desc"]], "aoColumnDefs": [{ "bSortable": false, "aTargets": [5] }] });
  });
  </script>
  <?php endif; ?>
</div>
<?php include('includes/footer.php'); ?>
