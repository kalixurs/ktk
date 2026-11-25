<?php
session_start();
include('conn.php');

// ---------- Field list (name => label) used for reading, validating and re-filling the form ----------
$fields = ['username','fname','minitial','lname','gender','birthdate','birthplace','civilstatus','course','yearlvl',
           'contact','email','street','barangay','city','province','zip','fathername','mothername',
           'guardianname','guardianrelation','guardiancontact','elemschool','elemyear','hsschool','hsyear',
           'lastschool','lastyear','lastaddress'];
$v = [];
foreach ($fields as $f) { $v[$f] = trim($_POST[$f] ?? ''); }
$reqs   = ['PSA Birth Certificate', 'Form 138 / Report Card', 'Certificate of Good Moral Character', '2x2 ID Photos'];
$picked = array_values(array_intersect($reqs, (array)($_POST['requirements'] ?? [])));
$errors = [];

if (isset($_POST['apply'])) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $nameOk = function ($s) { return preg_match("/^[\\p{L}][\\p{L} .'-]*$/u", $s); };
    $phone  = function ($s) { return preg_match('/^09\d{9}$/', $s); };
    $yearOk = function ($s) { return preg_match('/^(19|20)\d{2}$/', $s) && (int)$s <= (int)date('Y'); };

    // ----- Backend validation (the browser also checks, but this is the one that counts) -----
    if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $v['username'])) { $errors[] = 'Username must be 4-30 characters: letters, numbers, dot or underscore.'; }
    if (strlen($password) < 6)      { $errors[] = 'Password must be at least 6 characters.'; }
    if ($password !== $password2)   { $errors[] = 'The two passwords do not match.'; }
    if (!$nameOk($v['fname']))      { $errors[] = 'Please enter a valid first name.'; }
    if ($v['minitial'] !== '' && !preg_match('/^\p{L}\.?$/u', $v['minitial'])) { $errors[] = 'Middle initial should be a single letter.'; }
    if (!$nameOk($v['lname']))      { $errors[] = 'Please enter a valid last name.'; }
    if (!in_array($v['gender'], ['male', 'female'], true)) { $errors[] = 'Please choose a gender.'; }
    $bd = DateTime::createFromFormat('Y-m-d', $v['birthdate']);
    if (!$bd || $bd->format('Y-m-d') !== $v['birthdate']) {
        $errors[] = 'Please enter a valid birth date.';
    } else {
        $age = $bd->diff(new DateTime('today'))->y;
        if ($bd > new DateTime('today') || $age < 10 || $age > 80) { $errors[] = 'Birth date looks wrong (applicant age must be between 10 and 80).'; }
    }
    if ($v['birthplace'] === '')    { $errors[] = 'Please enter the place of birth.'; }
    if (!in_array($v['civilstatus'], ['single', 'married', 'widow'], true)) { $errors[] = 'Please choose a civil status.'; }
    if (!in_array($v['course'], ['bsit', 'bscs'], true))     { $errors[] = 'Please choose a course.'; }
    if (!in_array($v['yearlvl'], ['1st Year', '2nd Year', '3rd Year', '4th Year'], true)) { $errors[] = 'Please choose a year level.'; }
    if (!$phone($v['contact']))     { $errors[] = 'Mobile number must be 11 digits and start with 09 (example: 09171234567).'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    foreach (['street' => 'house no. / street', 'barangay' => 'barangay', 'city' => 'city / municipality', 'province' => 'province'] as $k => $l) {
        if ($v[$k] === '') { $errors[] = "Please enter your $l."; }
    }
    if ($v['zip'] !== '' && !preg_match('/^\d{4}$/', $v['zip'])) { $errors[] = 'ZIP code must be 4 digits.'; }
    if (!$nameOk($v['guardianname'])) { $errors[] = "Please enter the parent/guardian's full name."; }
    if ($v['guardianrelation'] === '') { $errors[] = 'Please enter the guardian\'s relationship to the applicant.'; }
    if (!$phone($v['guardiancontact'])) { $errors[] = "Guardian's mobile number must be 11 digits and start with 09."; }
    if ($v['elemschool'] === '' || !$yearOk($v['elemyear'])) { $errors[] = 'Please enter the elementary school and a valid year graduated.'; }
    if ($v['hsschool'] === ''   || !$yearOk($v['hsyear']))   { $errors[] = 'Please enter the high school and a valid year graduated.'; }
    if ($v['lastyear'] !== '' && !$yearOk($v['lastyear']))   { $errors[] = 'Year graduated (last school) is not valid.'; }
    if (empty($_POST['certify']))   { $errors[] = 'Please tick the box to confirm that your information is true.'; }

    // ----- Duplicate checks -----
    if (!$errors) {
        if (db_row($conn, 'SELECT ID FROM usertbl WHERE username = ?', 's', [$v['username']])) {
            $errors[] = 'That username is already taken. Please choose another one.';
        }
        $dupApp = db_row($conn, "SELECT ref_no FROM applications WHERE status <> 'Declined' AND ((LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND birthdate = ?) OR LOWER(email) = LOWER(?)) LIMIT 1",
                         'ssss', [$v['fname'], $v['lname'], $v['birthdate'], $v['email']]);
        if ($dupApp) { $errors[] = 'An application with this name and birth date (or email) already exists (Reference ' . $dupApp['ref_no'] . '). Please log in to track it instead of applying again.'; }
        if (db_row($conn, 'SELECT ID FROM studenttbl WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND birthdate = ? LIMIT 1', 'sss', [$v['fname'], $v['lname'], $v['birthdate']])) {
            $errors[] = 'This person is already an enrolled student. Please log in with your student account.';
        }
    }

    // ----- Save everything together or nothing (transaction) -----
    if (!$errors) {
        mysqli_begin_transaction($conn);
        $okAll = false; $appID = 0;
        $fullname = trim($v['fname'] . ' ' . $v['minitial'] . ' ' . $v['lname']);
        $u = db_run($conn, "INSERT INTO usertbl (username, password, fullname, user_type) VALUES (?, ?, ?, 'applicant')", 'sss',
                    [$v['username'], password_hash($password, PASSWORD_DEFAULT), $fullname]);
        if ($u) {
            $accID = mysqli_insert_id($conn);
            $tmpRef = 'TMP' . bin2hex(random_bytes(7));
            $reqText = implode(', ', $picked);
            $a = db_run($conn,
                "INSERT INTO applications (ref_no, account_id, status, fname, minitial, lname, gender, birthdate, birthplace, civilstatus, course, yearlvl, contact, email,
                   street, barangay, city, province, zip, fathername, mothername, guardianname, guardianrelation, guardiancontact,
                   elemschool, elemyear, hsschool, hsyear, lastschool, lastyear, lastaddress, requirements)
                 VALUES (?, ?, 'Pending', " . rtrim(str_repeat('?,', 29), ',') . ')',
                's' . 'i' . str_repeat('s', 29),
                [$tmpRef, $accID, $v['fname'], $v['minitial'], $v['lname'], $v['gender'], $v['birthdate'], $v['birthplace'], $v['civilstatus'], $v['course'], $v['yearlvl'],
                 $v['contact'], $v['email'], $v['street'], $v['barangay'], $v['city'], $v['province'], $v['zip'], $v['fathername'], $v['mothername'],
                 $v['guardianname'], $v['guardianrelation'], $v['guardiancontact'], $v['elemschool'], $v['elemyear'], $v['hsschool'], $v['hsyear'],
                 $v['lastschool'], $v['lastyear'], $v['lastaddress'], $reqText]);
            if ($a) {
                $appID = mysqli_insert_id($conn);
                $ref = sprintf('APP-%s-%05d', date('Y'), $appID);
                $okAll = (bool)db_run($conn, 'UPDATE applications SET ref_no = ? WHERE ID = ?', 'si', [$ref, $appID]);
            }
        }
        if ($okAll) {
            mysqli_commit($conn);
            session_regenerate_id(true);                      // log the applicant in so they can track right away
            $_SESSION['logcheck'] = 1;
            $_SESSION['username'] = $v['username'];
            $_SESSION['userID']   = (int)$accID;
            $_SESSION['type']     = 'applicant';
            $_SESSION['just_applied'] = $ref;
            header('Location: applicant/applicantindex.php');
            exit;
        }
        mysqli_rollback($conn);
        $errors[] = 'Sorry, we could not save your application. Please try again, or visit the registrar\'s office if the problem continues.';
    }
}

function opt($value, $current, $label) {
    return '<option value="' . e($value) . '"' . ($value === $current ? ' selected' : '') . '>' . e($label) . '</option>';
}
function yr_opts($cur) {
    $o = '<option value="">Select year</option>';
    for ($y = (int)date('Y'); $y >= 1980; $y--) { $o .= '<option value="' . $y . '"' . ((string)$y === (string)$cur ? ' selected' : '') . '>' . $y . '</option>'; }
    return $o;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Enrollment Application | John Paul College</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="pub-top">
  <div class="bar">
    <a class="brand" href="index.php"><img src="logo.jpg" alt="John Paul College logo"><span><b>John Paul College</b><small>Online Enrollment &middot; New Applicant</small></span></a>
    <a class="btn btn-light btn-sm" href="index.php">&larr; Back to Login</a>
  </div>
</header>

<main class="container" style="max-width: 920px;">
  <div class="page-head"><div><h1>Enrollment Application</h1><p>Fill in the form below. Fields marked <em class="req">*</em> are required.</p></div></div>

  <div class="steps-note">
    <b>How it works</b>
    <ol>
      <li>Fill out this form and create your own username and password.</li>
      <li>You will get a <b>reference number</b>. Keep it safe.</li>
      <li>Log in anytime to see if your application is Pending, Under Review, Approved or Declined.</li>
      <li>Once approved, log in again to open your Student Dashboard.</li>
    </ol>
  </div>

  <?php if ($errors): ?>
    <div class="msg msg-err"><b>Please fix the following:</b><ul style="margin:6px 0 0; padding-left:20px;"><?php foreach ($errors as $er): ?><li><?php echo e($er); ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <fieldset>
      <legend>1. Create Your Login</legend>
      <p class="muted" style="margin-top:0;">You will use this to check your application status later.</p>
      <div class="form-grid">
        <div class="field"><label for="username">Username <em class="req">*</em></label><input type="text" id="username" name="username" required minlength="4" maxlength="30" pattern="[A-Za-z0-9_.]{4,30}" placeholder="e.g. juan.delacruz" value="<?php echo e($v['username']); ?>"><span class="hint">Letters, numbers, dot or underscore. At least 4 characters.</span></div>
        <div class="field"><label for="password">Password <em class="req">*</em></label><input type="password" id="password" name="password" required minlength="6" maxlength="50" autocomplete="new-password"><span class="hint">At least 6 characters.</span></div>
        <div class="field"><label for="password2">Confirm Password <em class="req">*</em></label><input type="password" id="password2" name="password2" required minlength="6" maxlength="50" autocomplete="new-password"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>2. Student Information</legend>
      <div class="form-grid">
        <div class="field"><label for="fname">First Name <em class="req">*</em></label><input type="text" id="fname" name="fname" required maxlength="50" placeholder="e.g. Juan" value="<?php echo e($v['fname']); ?>"></div>
        <div class="field"><label for="minitial">Middle Initial</label><input type="text" id="minitial" name="minitial" maxlength="2" placeholder="e.g. D" value="<?php echo e($v['minitial']); ?>"></div>
        <div class="field"><label for="lname">Last Name <em class="req">*</em></label><input type="text" id="lname" name="lname" required maxlength="50" placeholder="e.g. Dela Cruz" value="<?php echo e($v['lname']); ?>"></div>
        <div class="field"><label for="gender">Gender <em class="req">*</em></label><select id="gender" name="gender" required><option value="">Select</option><?php echo opt('male', $v['gender'], 'Male') . opt('female', $v['gender'], 'Female'); ?></select></div>
        <div class="field"><label for="birthdate">Birth Date <em class="req">*</em></label><input type="date" id="birthdate" name="birthdate" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($v['birthdate']); ?>"></div>
        <div class="field"><label for="birthplace">Place of Birth <em class="req">*</em></label><input type="text" id="birthplace" name="birthplace" required maxlength="100" placeholder="e.g. Roxas, Oriental Mindoro" value="<?php echo e($v['birthplace']); ?>"></div>
        <div class="field"><label for="civilstatus">Civil Status <em class="req">*</em></label><select id="civilstatus" name="civilstatus" required><option value="">Select</option><?php echo opt('single', $v['civilstatus'], 'Single') . opt('married', $v['civilstatus'], 'Married') . opt('widow', $v['civilstatus'], 'Widow'); ?></select></div>
        <div class="field"><label for="course">Course Applying For <em class="req">*</em></label><select id="course" name="course" required><option value="">Select</option><?php echo opt('bsit', $v['course'], 'BS Information Technology') . opt('bscs', $v['course'], 'BS Computer Science'); ?></select></div>
        <div class="field"><label for="yearlvl">Year Level <em class="req">*</em></label><select id="yearlvl" name="yearlvl" required><option value="">Select</option><?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $y) { echo opt($y, $v['yearlvl'], $y); } ?></select><span class="hint">Choose 1st Year if you are a new freshman.</span></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>3. Contact Information</legend>
      <div class="form-grid">
        <div class="field"><label for="contact">Mobile Number <em class="req">*</em></label><input type="tel" id="contact" name="contact" required inputmode="numeric" maxlength="11" pattern="09[0-9]{9}" placeholder="09171234567" value="<?php echo e($v['contact']); ?>"><span class="hint">11 digits, starts with 09.</span></div>
        <div class="field"><label for="email">Email Address <em class="req">*</em></label><input type="email" id="email" name="email" required maxlength="100" placeholder="juan@example.com" value="<?php echo e($v['email']); ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>4. Complete Home Address</legend>
      <div class="form-grid">
        <div class="field full"><label for="street">House No. / Street <em class="req">*</em></label><input type="text" id="street" name="street" required maxlength="150" placeholder="e.g. 123 Rizal Street" value="<?php echo e($v['street']); ?>"></div>
        <div class="field"><label for="barangay">Barangay <em class="req">*</em></label><input type="text" id="barangay" name="barangay" required maxlength="100" value="<?php echo e($v['barangay']); ?>"></div>
        <div class="field"><label for="city">City / Municipality <em class="req">*</em></label><input type="text" id="city" name="city" required maxlength="100" value="<?php echo e($v['city']); ?>"></div>
        <div class="field"><label for="province">Province <em class="req">*</em></label><input type="text" id="province" name="province" required maxlength="100" value="<?php echo e($v['province']); ?>"></div>
        <div class="field"><label for="zip">ZIP Code</label><input type="text" id="zip" name="zip" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="5212" value="<?php echo e($v['zip']); ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>5. Parent / Guardian</legend>
      <div class="form-grid">
        <div class="field"><label for="fathername">Father's Name</label><input type="text" id="fathername" name="fathername" maxlength="100" value="<?php echo e($v['fathername']); ?>"></div>
        <div class="field"><label for="mothername">Mother's Name</label><input type="text" id="mothername" name="mothername" maxlength="100" value="<?php echo e($v['mothername']); ?>"><span class="hint">Maiden name is fine.</span></div>
        <div class="field"><label for="guardianname">Guardian's Full Name <em class="req">*</em></label><input type="text" id="guardianname" name="guardianname" required maxlength="100" value="<?php echo e($v['guardianname']); ?>"></div>
        <div class="field"><label for="guardianrelation">Relationship <em class="req">*</em></label><input type="text" id="guardianrelation" name="guardianrelation" required maxlength="50" placeholder="e.g. Mother, Aunt" value="<?php echo e($v['guardianrelation']); ?>"></div>
        <div class="field"><label for="guardiancontact">Guardian's Mobile Number <em class="req">*</em></label><input type="tel" id="guardiancontact" name="guardiancontact" required inputmode="numeric" maxlength="11" pattern="09[0-9]{9}" placeholder="09171234567" value="<?php echo e($v['guardiancontact']); ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>6. Previous Education</legend>
      <div class="form-grid">
        <div class="field"><label for="elemschool">Elementary School <em class="req">*</em></label><input type="text" id="elemschool" name="elemschool" required maxlength="100" value="<?php echo e($v['elemschool']); ?>"></div>
        <div class="field"><label for="elemyear">Year Graduated <em class="req">*</em></label><select id="elemyear" name="elemyear" required><?php echo yr_opts($v['elemyear']); ?></select></div>
        <div class="field"><label for="hsschool">High School <em class="req">*</em></label><input type="text" id="hsschool" name="hsschool" required maxlength="100" value="<?php echo e($v['hsschool']); ?>"></div>
        <div class="field"><label for="hsyear">Year Graduated <em class="req">*</em></label><select id="hsyear" name="hsyear" required><?php echo yr_opts($v['hsyear']); ?></select></div>
        <div class="field"><label for="lastschool">Last School Attended</label><input type="text" id="lastschool" name="lastschool" maxlength="100" value="<?php echo e($v['lastschool']); ?>"><span class="hint">Only if you are a transferee. Leave blank if you are a new freshman.</span></div>
        <div class="field"><label for="lastyear">Year Graduated / Last Attended</label><select id="lastyear" name="lastyear"><?php echo yr_opts($v['lastyear']); ?></select></div>
        <div class="field full"><label for="lastaddress">Address of Last School</label><input type="text" id="lastaddress" name="lastaddress" maxlength="150" value="<?php echo e($v['lastaddress']); ?>"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>7. Requirements</legend>
      <p class="muted" style="margin-top:0;">Tick the documents you already have. You will bring the original copies to the registrar&rsquo;s office.</p>
      <div class="check">
        <?php foreach ($reqs as $r): ?>
          <label><input type="checkbox" name="requirements[]" value="<?php echo e($r); ?>"<?php echo in_array($r, $picked, true) ? ' checked' : ''; ?>> <?php echo e($r); ?></label>
        <?php endforeach; ?>
      </div>
      <div class="check" style="margin-top:14px; border-top:1px solid var(--line); padding-top:12px;">
        <label><input type="checkbox" name="certify" value="1" required<?php echo !empty($_POST['certify']) ? ' checked' : ''; ?>> I confirm that all the information above is true and correct. <em class="req">*</em></label>
      </div>
    </fieldset>

    <div class="actions">
      <input type="submit" name="apply" value="Submit Application" class="btn-gold" style="background:var(--gold); border-color:var(--gold); color:#3b2a00; padding:12px 30px; font-size:15px;">
      <a class="btn btn-light" href="index.php">Cancel</a>
    </div>
  </form>
</main>
<div class="footer">&copy; <?php echo date('Y'); ?> John Paul College &middot; Online Enrollment System</div>
</body>
</html>
