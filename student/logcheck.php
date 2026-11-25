<?php
// Include AFTER session_start(). Role-based access (see require_role in conn.php).
include_once __DIR__ . "/../conn.php";
require_role("student");
