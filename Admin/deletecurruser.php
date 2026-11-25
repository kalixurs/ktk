<?php
// Old direct-delete link: deleting now goes through the Expel page (with confirmation and the "keep records" rule).
session_start();
include('logcheck.php');
$id = (int)($_GET['accid'] ?? 0);
header('Location: ' . ($id ? 'expelstudent.php?id=' . $id : 'userreg.php'));
exit;
