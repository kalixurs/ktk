<?php
session_start();
include('logcheck.php');
include('includes/conn.php');

// Approve a submitted enrollment form
if (isset($_POST['approve'])) {
    db_run($conn, "UPDATE student_subject SET approved = '1' WHERE ID = ?", 'i', [(int)$_POST['approve']]);
    header('Location: report.php');
    exit;
}

$rows = db_rows($conn,
    'SELECT ss.ID, ss.studentID, ss.subjects, ss.totalunits, ss.approved, s.fname, s.minitial, s.lname, s.course, s.yearlvl
     FROM student_subject ss LEFT JOIN studenttbl s ON s.studnum = ss.studentID
     ORDER BY ss.ID DESC');

$pageTitle = 'Enrollment Report';
$current = 'report';
include('includes/header.php');
?>
<div class="page-head">
  <h1>Enrollment Report</h1>
  <input type="button" class="btn-gold no-print" value="Print" onclick="window.print()">
</div>
<div class="card">
  <div class="table-scroll">
  <table class="data">
    <thead>
      <tr><th>#</th><th>Student No.</th><th>Name</th><th>Course / Year</th><th>Subjects</th><th class="num">Units</th><th>Status</th><th class="no-print">Action</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="muted">No enrollment forms have been submitted yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo (int)$r['ID']; ?></td>
        <td><?php echo e($r['studentID']); ?></td>
        <td><?php echo $r['lname'] === null ? '<span class="muted">(student removed)</span>' : e($r['fname'] . ' ' . $r['minitial'] . ' ' . $r['lname']); ?></td>
        <td><?php echo e(strtoupper((string)$r['course'])); ?> <?php echo e($r['yearlvl']); ?></td>
        <td><?php echo e(trim($r['subjects'], ',')); ?></td>
        <td class="num"><?php echo (int)$r['totalunits']; ?></td>
        <td><?php echo $r['approved'] === '1' ? '<span class="badge ok">Approved</span>' : '<span class="badge">Pending</span>'; ?></td>
        <td class="no-print">
          <?php if ($r['approved'] !== '1'): ?>
            <form method="POST" style="display:inline;"><button class="btn-sm" name="approve" value="<?php echo (int)$r['ID']; ?>">Approve</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include('includes/footer.php'); ?>
