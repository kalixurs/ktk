<?php
// Shared page header + navigation. Set $pageTitle and $current (and $useDataTables = true if needed) before including.
$pageTitle = $pageTitle ?? 'Online Enrollment';
$current   = $current ?? '';
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
    <a class="brand" href="applicantindex.php"><img src="../logo.jpg" alt="John Paul College logo"><span><b>John Paul College</b><small>Online Enrollment &middot; Applicant</small></span></a>
    <button type="button" class="menu-toggle btn-light btn-sm" onclick="document.body.classList.toggle('nav-open')">&#9776; Menu</button>
    <nav class="main-nav">
      <a href="applicantindex.php" class="<?php echo $current === 'home' ? 'active' : ''; ?>">My Application</a>
    </nav>
    <div class="user"><span><?php echo e($_SESSION['username'] ?? ''); ?></span><a class="btn btn-light btn-sm" href="includes/logout.php">Log Out</a></div>
  </div>
</header>
<main class="container">
