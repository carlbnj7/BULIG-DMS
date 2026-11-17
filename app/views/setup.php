<?php $u = me(); ?>
<div class="card narrow setup">
  <div class="setup-head"><span class="setup-ic"><?= icon('key', 26) ?></span><div><h2>Set up your account</h2><p class="muted">Do this once. Only you should know your password.</p></div></div>
  <div class="id-strip"><span>Your account ID</span><b><?= e($u['user_code']) ?></b><small><?= e(role_name($u['role'])) ?> · <?= e(scope_label($u)) ?></small></div>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" action="<?= url('setup') ?>" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <label><?= $needPw ? 'Password you were given' : 'Your password' ?><input type="password" name="current" required autocomplete="current-password"></label>
    <?php if ($needUn): ?>
    <fieldset class="step"><legend><span>1</span> Your username</legend>
      <label>Pick a username<input name="username" value="<?= e($oldUn) ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._\-]{2,29}" autocapitalize="none" spellcheck="false" placeholder="e.g. maria.santos"></label>
    </fieldset>
    <?php endif; ?>
    <?php if ($needPw): ?>
    <fieldset class="step"><legend><span><?= $needUn ? 2 : 1 ?></span> Your new password</legend>
      <label>New password (at least 8 characters)<input type="password" name="new1" required minlength="8" autocomplete="new-password"></label>
      <label>Type it again<input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
    </fieldset>
    <?php endif; ?>
    <button class="btn primary big">Save and continue</button>
  </form>
  <form method="post" action="<?= url('logout') ?>" class="center"><?= csrf_field() ?><button class="linkish small">Sign out</button></form>
</div>
