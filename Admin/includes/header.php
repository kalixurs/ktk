<?php
// Shared page header + navigation. Set $pageTitle and $current (and $useDataTables = true if needed) before including.
$pageTitle = $pageTitle ?? 'Online Enrollment';
$current   = $current ?? '';
$pendingApps = db_row($conn, "SELECT COUNT(*) AS n FROM applications WHERE status IN ('Pending','Under Review')")['n'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($pageTitle); ?> | John Paul College</title>
<link rel="stylesheet" href="../style.css">
<?php if (!empty($useDataTables)): ?>
<script src="../Admin/js/jquery-1.7.min.js"></script>
<script src="../Admin/js/jquery.dataTables.js"></script>
<?php endif; ?>
</head>
<body>
<header class="site-header no-print">
  <div class="bar">
    <a class="brand" href="adminindex.php"><img src="../logo.jpg" alt="John Paul College logo"><span><b>John Paul College</b><small>Online Enrollment &middot; Registrar</small></span></a>
    <button type="button" class="menu-toggle btn-light btn-sm" onclick="document.body.classList.toggle('nav-open')">&#9776; Menu</button>
    <nav class="main-nav">
      <a href="adminindex.php" class="<?php echo $current === 'home' ? 'active' : ''; ?>">Home</a>
      <a href="applications.php" class="<?php echo $current === 'apps' ? 'active' : ''; ?>">Applications<?php if (!empty($pendingApps)) echo '<span class="count">'.(int)$pendingApps.'</span>'; ?></a>
      <a href="userreg.php" class="<?php echo $current === 'users' ? 'active' : ''; ?>">Students</a>
      <a href="subjectreg.php" class="<?php echo $current === 'subjects' ? 'active' : ''; ?>">Subjects</a>
      <a href="report.php" class="<?php echo $current === 'report' ? 'active' : ''; ?>">Subject Enrollments</a>
    </nav>
    <div class="user"><span><?php echo e($_SESSION['username'] ?? ''); ?></span><a class="btn btn-light btn-sm" href="includes/logout.php">Log Out</a></div>
  </div>
</header>
<main class="container">
