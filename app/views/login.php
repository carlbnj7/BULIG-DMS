<div class="auth-panel">
  <section class="auth-hero">
    <div class="hero-top"><img class="seal" src="assets/img/division.png" alt="Division of Bukidnon seal" width="64" height="64"><span>DepEd · <?= e(setting('division_name', 'Division of Bukidnon')) ?><br><small><?= e(setting('region_name', 'Region X - Northern Mindanao')) ?></small></span></div>
    <div class="hero-logo"><img src="assets/img/bulig-logo.png" alt="BULIG — Bukidnon's Unified Literacy and Intervention Gateway" width="420" height="168"></div>
    <h2 class="hero-h">Data and Monitoring <em>System</em></h2>
    <p class="hero-p">Upload your SF1, set each pupil's BULIG level, record every assessment period, and print the Classroom and School Reading Monitoring Reports.</p>
    <div class="hero-pills"><span><?= icon('upload', 16) ?> SF1 upload</span><span><?= icon('chart', 16) ?> AP1 to AP15</span><span><?= icon('print', 16) ?> Printed reports</span></div>
    <svg class="hero-waves" viewBox="0 0 600 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 60C150 20 300 90 450 50S600 20 600 20V120H0z" fill="#ffd31a"/><path d="M0 85C160 55 320 110 470 75S600 55 600 55V120H0z" fill="#0b6b34"/></svg>
  </section>
  <section class="auth-sheet">
    <span class="welcome-pill"><i></i>WELCOME</span>
    <h1>Sign in to BULIG-DMS</h1>
    <p class="muted sub">One sign-in for teachers, school heads, district, municipal and division staff.</p>
    <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" action="<?= url('login') ?>" class="auth-form" autocomplete="on">
      <?= csrf_field() ?>
      <label for="u">Username or account ID</label>
      <div class="field"><?= icon('user', 18) ?><input id="u" name="username" value="<?= e($old) ?>" required autocomplete="username" autocapitalize="none" spellcheck="false"></div>
      <label for="pw">Password</label>
      <div class="field"><?= icon('key', 18) ?><input id="pw" type="password" name="password" required autocomplete="current-password"><button type="button" class="peek" data-peek="pw">Show</button></div>
      <button class="btn primary big"><span>Sign in</span><?= icon('arrow', 18) ?></button>
    </form>
    <p class="muted center small">No account yet? Ask your school head or district.<br>Forgot your password? They can make you a new one.</p>
  </section>
</div>
<script src="assets/js/auth.js?v=1"></script>
