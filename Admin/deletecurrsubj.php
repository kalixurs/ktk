<?php
session_start();
include('logcheck.php');          // admins only
include('includes/conn.php');

$num = (int)($_GET['accid'] ?? 0);
if ($num > 0) {
    db_run($conn, 'DELETE FROM subjecttbl WHERE ID = ?', 'i', [$num]);
    $_SESSION['flash'] = 'Successfully removed the selected subject.';
}
header('Location: subjectreg.php');
exit;
