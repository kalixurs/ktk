<?php
if (defined('DB_LOADED')) { return; }   // safe to include more than once
define('DB_LOADED', 1);
// ------------------------------------------------------------------
// Database connection (mysqli) + small shared helper functions.
// This is the ONLY place to edit if your MySQL settings are different.
// ------------------------------------------------------------------
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', '0');          // never show technical errors to users

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'online_enroll';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    http_response_code(500);
    die('<h3>Cannot connect to the database.</h3><p>Please make sure MySQL is running and that the database '
      . '<b>' . htmlspecialchars($DB_NAME) . '</b> was imported. (Settings are at the top of conn.php)</p>');
}
mysqli_set_charset($conn, 'utf8');

// Escape text before printing it in HTML.
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Run a prepared query. $types e.g. 'ssi'. Returns the statement (or false on error).
function db_run($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { return false; }
    if ($types !== '') { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); return false; }
    return $stmt;
}
// SELECT -> array of rows
function db_rows($conn, $sql, $types = '', $params = []) {
    $stmt = db_run($conn, $sql, $types, $params);
    if (!$stmt) { return []; }
    $res  = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}
// SELECT -> first row or null
function db_row($conn, $sql, $types = '', $params = []) {
    $rows = db_rows($conn, $sql, $types, $params);
    return $rows ? $rows[0] : null;
}

// Typed password vs stored password (supports old plain-text and new hashes).
function password_ok($typed, $stored) {
    if (strpos($stored, '$2y$') === 0) { return password_verify($typed, $stored); }
    return hash_equals($stored, $typed);
}

// ---------------- Student status (Active / Suspended / Expelled) ----------------
// Suspension ends automatically: a student is "Suspended" only while suspended_until >= today.
function student_state($row) {
    if (($row['status'] ?? 'Active') === 'Expelled') { return 'Expelled'; }
    if (!empty($row['suspended_until']) && $row['suspended_until'] >= date('Y-m-d')) { return 'Suspended'; }
    return 'Active';
}
// Message shown when a student account may not log in (empty string = allowed).
function account_block_message($conn, $userID) {
    $s = db_row($conn, 'SELECT status, suspended_until, suspend_reason, expel_reason FROM studenttbl WHERE accountID = ? LIMIT 1', 'i', [(int)$userID]);
    if (!$s) { return ''; }
    $state = student_state($s);
    if ($state === 'Expelled') {
        return 'Your enrollment has been terminated.' . ($s['expel_reason'] !== '' ? ' Reason: ' . $s['expel_reason'] . '.' : '') . ' Please visit the registrar\'s office.';
    }
    if ($state === 'Suspended') {
        return 'Your account is suspended until ' . date('F j, Y', strtotime($s['suspended_until'])) . '.'
             . ($s['suspend_reason'] !== '' ? ' Reason: ' . $s['suspend_reason'] . '.' : '') . ' You can log in again after that date.';
    }
    return '';
}

// ---------------- Role-based access control ----------------
// Call after session_start(). $role = 'admin' | 'student' | 'applicant'.
// The role is re-checked against the database on every page, not just the session.
function role_home($type) {
    return ['admin' => 'Admin/adminindex.php', 'student' => 'student/studentindex.php', 'applicant' => 'applicant/applicantindex.php'][$type] ?? 'index.php';
}
function require_role($role) {
    global $conn;
    if (empty($_SESSION['logcheck']) || empty($_SESSION['userID'])) {
        header('Location: ../index.php'); exit;
    }
    $u = db_row($conn, 'SELECT user_type FROM usertbl WHERE ID = ?', 'i', [(int)$_SESSION['userID']]);
    if (!$u) { $_SESSION = []; session_destroy(); header('Location: ../index.php'); exit; }
    $actual = $u['user_type'];
    $_SESSION['type'] = $actual;                    // keep session in step with the database (e.g. applicant approved)
    if ($actual !== $role) {                        // wrong area for this account -> send to its own dashboard
        header('Location: ../' . role_home($actual)); exit;
    }
    if ($role === 'student') {
        $msg = account_block_message($conn, $_SESSION['userID']);
        if ($msg !== '') {
            $_SESSION = []; session_destroy();
            header('Location: ../index.php?blocked=1'); exit;
        }
    }
}

// Flash message helpers (one-time messages after a redirect)
function flash_set($msg, $type = 'ok') { $_SESSION['flash'] = $msg; $_SESSION['flash_type'] = $type; }
function flash_show() {
    if (empty($_SESSION['flash'])) { return ''; }
    $cls = (($_SESSION['flash_type'] ?? 'ok') === 'err') ? 'msg-err' : 'msg-ok';
    $html = '<div class="msg ' . $cls . '">' . e($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash'], $_SESSION['flash_type']);
    return $html;
}
