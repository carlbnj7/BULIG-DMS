<?php $u = me(); ?>
<div class="page-head"><div><h1><?= $u['role'] === 'teacher' ? 'My classes' : 'Classes' ?></h1><p class="muted"><?= count($rows) ?> shown · SY <?= e($sy) ?></p></div><?php if ($u['role'] === 'teacher'): ?><a class="btn primary" href="<?= url('upload') ?>"><?= icon('upload', 16) ?> Upload SF1</a><?php endif; ?></div>
<form class="filters" method="get"><input type="hidden" name="p" value="classes">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search section or teacher">
  <select name="sy"><?php foreach (school_years() as $y): ?><option <?= $sy === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select>
  <?php if ($schools): ?><select name="school"><option value="0">All schools</option><?php foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $sid == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn">Show</button>
</form>
<div class="card"><table class="tbl"><thead><tr><th>Class</th><th>Adviser</th><th>School</th><th>Learners</th><th>Latest AP</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><a href="<?= url('class', ['id' => $r['id'], 'ap' => max(1, (int)$r['last_ap'])]) ?>"><b>Grade <?= (int)$r['grade'] ?> – <?= e($r['section']) ?></b></a><br><small class="muted"><?= e($r['tool']) ?></small></td><td><?= e($r['teacher']) ?></td><td class="small"><?= e($r['school']) ?></td><td><?= (int)$r['n'] ?></td><td><?= $r['last_ap'] ? 'AP ' . (int)$r['last_ap'] : '—' ?></td>
<td class="acts-cell"><a class="btn sm" href="<?= url('report', ['scope' => 'class', 'id' => $r['id'], 'period' => (int)$r['last_ap']]) ?>"><?= icon('print', 14) ?> Report</a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">No classes yet.</td></tr><?php endif; ?>
</tbody></table></div>
