<?php
// Shared home content (student + admin). Pages live one folder down, so images load from ../images/
// Expects: $welcome (heading) and $welcomeSub (small line under it).
?>
<section class="banner">
  <img src="../images/header.jpg" alt="John Paul College campus">
  <div class="cap"><h1><?php echo e($welcome); ?></h1><p><?php echo e($welcomeSub ?? ''); ?></p></div>
</section>
