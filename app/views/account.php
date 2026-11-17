<?php $u = me(); ?>
<div class="card narrow">
  <div class="acct"><span class="av big"><?= e(mb_strtoupper(mb_substr($u['first_name'], 0, 1))) ?></span><div><h2><?= e(full_name($u)) ?></h2><p class="muted"><?= e(role_name($u['role'])) ?> · <?= e(scope_label($u)) ?></p></div></div>
  <dl class="kv"><dt>Account ID</dt><dd><code><?= e($u['user_code']) ?></code></dd><dt>Username</dt><dd><?= e($u['username']) ?></dd><dt>Position</dt><dd><?= e($u['position'] ?: '—') ?></dd>
    <dt>Last sign-in</dt><dd><?= $u['last_login'] ? e(date('M j, Y g:i A', strtotime($u['last_login']))) : '—' ?></dd></dl>
  <div class="row-btns"><a class="btn" href="<?= url('password') ?>"><?= icon('key', 16) ?> Change password</a>
    <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="btn ghost"><?= icon('logout', 16) ?> Sign out</button></form></div>
</div>
