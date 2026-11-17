<div class="page-head"><div><h1>Duplicates</h1><p class="muted">The same learner or staff member entered twice. Pick the record to keep; everything from the other one moves into it.</p></div></div>
<h3><?= icon('users', 18) ?> Learners <span class="pill"><?= count($groups) ?></span></h3>
<?php if (!$groups): ?><div class="card empty"><p class="muted">No duplicate learners found.</p></div><?php endif; ?>
<?php foreach ($groups as $g): ?>
<form method="post" class="card dup-card" data-confirm="Merge these records into one? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="do" value="learner">
  <?php foreach ($g as $i => $l): ?><label class="dup-opt"><input type="radio" name="keep" value="<?= (int)$l['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>><span><b><?= e($l['last_name'] . ', ' . $l['first_name'] . ' ' . $l['middle_name']) ?></b><small>LRN <?= e($l['lrn'] ?: 'none') ?> · born <?= $l['birthdate'] ? e(date('M j, Y', strtotime($l['birthdate']))) : '—' ?> · <?= e($l['place'] ?: 'no class') ?> · <?= (int)$l['results'] ?> results</small></span></label><?php endforeach; ?>
  <input type="hidden" name="drop" value="<?= (int)$g[1]['id'] ?>" data-drop data-ids="<?= e(implode(',', array_column($g, 'id'))) ?>">
  <button class="btn primary sm"><?= icon('check', 14) ?> Keep the checked one, merge the other</button>
</form>
<?php endforeach; ?>
<h3><?= icon('user', 18) ?> Accounts <span class="pill"><?= count($ug) ?></span></h3>
<?php if (!$ug): ?><div class="card empty"><p class="muted">No duplicate accounts found.</p></div><?php endif; ?>
<?php foreach ($ug as $g): ?>
<form method="post" class="card dup-card" data-confirm="Merge these accounts? The other one will be turned off and its classes moved."><?= csrf_field() ?><input type="hidden" name="do" value="user">
  <?php foreach ($g as $i => $x): ?><label class="dup-opt"><input type="radio" name="keep" value="<?= (int)$x['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>><span><b><?= e(full_name($x)) ?></b> <code><?= e($x['user_code']) ?></code><small><?= e(role_name($x['role'])) ?> · <?= e($x['where']) ?> · <?= (int)$x['classes'] ?> classes · last sign-in <?= $x['last_login'] ? e(date('M j, Y', strtotime($x['last_login']))) : 'never' ?></small></span></label><?php endforeach; ?>
  <input type="hidden" name="drop" value="<?= (int)$g[1]['id'] ?>" data-drop data-ids="<?= e(implode(',', array_column($g, 'id'))) ?>">
  <button class="btn primary sm"><?= icon('check', 14) ?> Keep the checked one, merge the other</button>
</form>
<?php endforeach; ?>
