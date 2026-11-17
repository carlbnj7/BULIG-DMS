<?php $u = me(); $max = max(1, max($dist ?: [0])); $sum = array_sum($dist); ?>
<section class="hello">
  <div><p class="eyebrow">SY <?= e($sy) ?> · <?= e(scope_label($u)) ?></p><h1>Hello, <?= e($u['first_name']) ?>!</h1><p><?= $u['role'] === 'teacher' ? 'Upload your SF1, set the BULIG levels, and record each assessment period.' : 'Here is the reading picture for your area.' ?></p></div>
  <?php if ($u['role'] === 'teacher'): ?><a class="btn gold big" href="<?= url('upload') ?>"><?= icon('upload', 18) ?> Upload SF1</a><?php else: ?><a class="btn gold big" href="<?= url('report') ?>"><?= icon('print', 18) ?> Reports</a><?php endif; ?>
</section>
<div class="stat-row">
  <?php foreach (['schools' => ['Schools', 'school'], 'teachers' => ['Teachers', 'user'], 'classes' => ['Classes', 'users'], 'learners' => ['Learners', 'book']] as $k => [$l, $ic]): if (!isset($stats[$k])) continue; ?>
  <div class="stat"><span class="st-ic"><?= icon($ic, 20) ?></span><b><?= number_format($stats[$k]) ?></b><small><?= $l ?></small></div>
  <?php endforeach; ?>
</div>
<div class="two-col">
  <section class="card"><h3><?= icon('chart', 18) ?> Learners by BULIG level <small class="muted">latest period</small></h3>
    <?php if (!$sum): ?><p class="muted">No levels yet. Set the BULIG level of each learner after the pre-assessment.</p><?php endif; ?>
    <div class="bars"><?php foreach (LEVELS as $k => [$l, $c, $a]): $v = $dist[$k] ?? 0; ?>
      <div class="bar-row"><span class="bl"><?= e($l) ?><small><?= e($a) ?></small></span><span class="bt"><i class="lvl-<?= strtolower($k) ?> w<?= (int)round(20 * $v / $max) ?>"></i></span><b><?= $v ?></b></div><?php endforeach; ?></div>
  </section>
  <section class="card"><h3><?= icon('users', 18) ?> <?= $u['role'] === 'teacher' ? 'My classes' : 'Classes' ?></h3>
    <?php if (!$classes): ?><p class="muted"><?= $u['role'] === 'teacher' ? 'No class yet. Upload your SF1 to start.' : 'No classes yet for this school year.' ?></p><?php endif; ?>
    <div class="class-list"><?php foreach (array_slice($classes, 0, 12) as $c): ?>
      <a class="class-item" href="<?= url('class', ['id' => $c['id'], 'ap' => max(1, (int)$c['last_ap'])]) ?>"><span class="g">G<?= (int)$c['grade'] ?></span><span class="grow"><b><?= e($c['section']) ?></b><small><?= e($u['role'] === 'teacher' ? $c['school'] : $c['teacher'] . ' · ' . $c['school']) ?></small></span><span class="small muted"><?= (int)$c['n'] ?> learners<br><?= $c['last_ap'] ? 'AP ' . (int)$c['last_ap'] : ((int)$c['leveled'] . ' with level') ?></span></a>
    <?php endforeach; ?></div>
    <?php if (count($classes) > 12): ?><a href="<?= url('classes') ?>">See all <?= count($classes) ?> classes</a><?php endif; ?>
  </section>
</div>
