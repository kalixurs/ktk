<?php
// Shared student form (used by addnewuser.php and editstudent.php).
// Expects $v (array of field values) and $isEdit (true/false).
function year_options($selected) {
    $out = '';
    $top = (int)date('Y');
    if ($selected !== '' && (int)$selected > $top) { $top = (int)$selected; }
    for ($y = $top; $y >= 1950; $y--) {
        $out .= '<option value="' . $y . '"' . ((string)$y === (string)$selected ? ' selected' : '') . '>' . $y . '</option>';
    }
    return $out;
}
?>
  <fieldset>
    <legend>Account Details</legend>
    <div class="form-grid">
      <div class="field"><label for="username">Username <em class="req">*</em></label><input type="text" id="username" name="username" required maxlength="50" value="<?php echo e($v['username'] ?? ''); ?>"></div>
      <div class="field"><label for="password">Password <?php echo $isEdit ? '<span class="muted">(leave blank to keep current)</span>' : '<em class="req">*</em>'; ?></label><input type="password" id="password" name="password"<?php echo $isEdit ? '' : ' required'; ?> maxlength="50" autocomplete="new-password"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>College Details</legend>
    <div class="form-grid">
      <div class="field"><label for="snum">Student Number <em class="req">*</em></label><input type="text" id="snum" name="snum" required maxlength="20" value="<?php echo e($v['snum'] ?? ''); ?>"></div>
      <div class="field">
        <label for="course">Course <em class="req">*</em></label>
        <select id="course" name="course">
          <option value="bsit"<?php echo ($v['course'] ?? '') === 'bsit' ? ' selected' : ''; ?>>BSIT</option>
          <option value="bscs"<?php echo ($v['course'] ?? '') === 'bscs' ? ' selected' : ''; ?>>BSCS</option>
        </select>
      </div>
      <div class="field"><label for="yrlvlsec">Section / Year Level <em class="req">*</em></label><input type="text" id="yrlvlsec" name="yrlvlsec" required maxlength="50" value="<?php echo e($v['yrlvlsec'] ?? ''); ?>"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Personal Details</legend>
    <div class="form-grid">
      <div class="field"><label for="fname">First Name <em class="req">*</em></label><input type="text" id="fname" name="fname" required maxlength="50" value="<?php echo e($v['fname'] ?? ''); ?>"></div>
      <div class="field"><label for="minitial">Middle Initial</label><input type="text" id="minitial" name="minitial" maxlength="5" value="<?php echo e($v['minitial'] ?? ''); ?>"></div>
      <div class="field"><label for="lname">Last Name <em class="req">*</em></label><input type="text" id="lname" name="lname" required maxlength="50" value="<?php echo e($v['lname'] ?? ''); ?>"></div>
      <div class="field">
        <label>Gender <em class="req">*</em></label>
        <div class="radio-row">
          <label><input type="radio" name="gender" value="male" required<?php echo ($v['gender'] ?? '') === 'male' ? ' checked' : ''; ?>> Male</label>
          <label><input type="radio" name="gender" value="female"<?php echo ($v['gender'] ?? '') === 'female' ? ' checked' : ''; ?>> Female</label>
        </div>
      </div>
      <div class="field"><label for="bdate">Birth Date</label><input type="date" id="bdate" name="bdate" value="<?php echo e($v['bdate'] ?? ''); ?>"></div>
      <div class="field"><label for="bplace">Birth Place</label><input type="text" id="bplace" name="bplace" maxlength="50" value="<?php echo e($v['bplace'] ?? ''); ?>"></div>
      <div class="field">
        <label for="cstatus">Civil Status</label>
        <select id="cstatus" name="cstatus">
          <?php foreach (['single' => 'Single', 'married' => 'Married', 'widow' => 'Widow'] as $v => $l): ?>
            <option value="<?php echo $v; ?>"<?php echo ($v['cstatus'] ?? '') === $v ? ' selected' : ''; ?>><?php echo $l; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label for="guardianname">Guardian's Name</label><input type="text" id="guardianname" name="guardianname" maxlength="50" value="<?php echo e($v['guardianname'] ?? ''); ?>"></div>
      <div class="field"><label for="fathername">Father's Name</label><input type="text" id="fathername" name="fathername" maxlength="50" value="<?php echo e($v['fathername'] ?? ''); ?>"></div>
      <div class="field"><label for="mothername">Mother's Name</label><input type="text" id="mothername" name="mothername" maxlength="50" value="<?php echo e($v['mothername'] ?? ''); ?>"></div>
      <div class="field full"><label for="address">Address</label><textarea id="address" name="address" maxlength="500"><?php echo e($v['address'] ?? ''); ?></textarea></div>
      <div class="field"><label for="cnumber">Contact Number</label><input type="text" id="cnumber" name="cnumber" maxlength="11" value="<?php echo e($v['cnumber'] ?? ''); ?>"></div>
      <div class="field"><label for="email">Email Address</label><input type="email" id="email" name="email" maxlength="100" value="<?php echo e($v['email'] ?? ''); ?>"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Educational Background</legend>
    <div class="form-grid">
      <div class="field"><label for="elemschool">Elementary School</label><input type="text" id="elemschool" name="elemschool" maxlength="100" value="<?php echo e($v['elemschool'] ?? ''); ?>"></div>
      <div class="field"><label for="yrgradelem">Year Graduated</label><select id="yrgradelem" name="yrgradelem"><?php echo year_options($v['yrgradelem'] ?? ''); ?></select></div>
      <div class="field"><label for="hschool">High School</label><input type="text" id="hschool" name="hschool" maxlength="100" value="<?php echo e($v['hschool'] ?? ''); ?>"></div>
      <div class="field"><label for="yrgradhs">Year Graduated</label><select id="yrgradhs" name="yrgradhs"><?php echo year_options($v['yrgradhs'] ?? ''); ?></select></div>
      <div class="field"><label for="lastschool">School Last Attended</label><input type="text" id="lastschool" name="lastschool" maxlength="100" value="<?php echo e($v['lastschool'] ?? ''); ?>"></div>
      <div class="field"><label for="yrlast">Year Graduated</label><select id="yrlast" name="yrlast"><?php echo year_options($v['yrlast'] ?? ''); ?></select></div>
      <div class="field full"><label for="address2">Address of Last School Attended</label><textarea id="address2" name="address2" maxlength="100"><?php echo e($v['address2'] ?? ''); ?></textarea></div>
    </div>
  </fieldset>

