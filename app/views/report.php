<?php $u = me(); $pn = $period ? 'AP ' . $period : 'PRE-ASSESSMENT'; $n = fn($a, $k) => (int)($a[$k] ?? 0); ?>
<form class="card filters rep-pick noprint" method="get"><input type="hidden" name="p" value="report">
  <select name="scope" data-scope>
    <option value="class" <?= $scope === 'class' ? 'selected' : '' ?>>Class</option>
    <?php if ($u['role'] !== 'teacher'): ?><option value="school" <?= $scope === 'school' ? 'selected' : '' ?>>School</option><?php endif; ?>
    <?php if (in_array($u['role'], ['division', 'municipal', 'district'], true)): ?><option value="district" <?= $scope === 'district' ? 'selected' : '' ?>>District</option><?php endif; ?>
    <?php if (in_array($u['role'], ['division', 'municipal'], true)): ?><option value="municipality" <?= $scope === 'municipality' ? 'selected' : '' ?>>Municipality</option><?php endif; ?>
    <?php if ($u['role'] === 'division'): ?><option value="division" <?= $scope === 'division' ? 'selected' : '' ?>>Whole division</option><?php endif; ?>
  </select>
  <select name="id" aria-label="Which one">
    <?php if ($scope === 'class') foreach ($myClasses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $id == $c['id'] ? 'selected' : '' ?>><?= e($c['school_year'] . ' · Grade ' . $c['grade'] . ' – ' . $c['section'] . ($u['role'] !== 'teacher' ? ' · ' . $c['school'] : '')) ?></option><?php endforeach; ?>
    <?php if ($scope === 'school') foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $id == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    <?php if ($scope === 'district') { if ($u['role'] === 'district') echo '<option value="' . (int)$u['scope_id'] . '">' . e(scope_label($u)) . '</option>'; else foreach ($dists as $d) echo '<option value="' . (int)$d['id'] . '"' . ($id == $d['id'] ? ' selected' : '') . '>' . e($d['name'] . ' · ' . $d['municipality']) . '</option>'; } ?>
    <?php if ($scope === 'municipality') { if ($u['role'] === 'municipal') echo '<option value="' . (int)$u['scope_id'] . '">' . e(scope_label($u)) . '</option>'; else foreach ($muns as $m) echo '<option value="' . (int)$m['id'] . '"' . ($id == $m['id'] ? ' selected' : '') . '>' . e($m['name']) . '</option>'; } ?>
    <?php if ($scope === 'division') echo '<option value="0">' . e(setting('division_name')) . '</option>'; ?>
  </select>
  <select name="period"><option value="0">Pre-assessment</option><?php for ($i = 1; $i <= AP_COUNT; $i++): ?><option value="<?= $i ?>" <?= $period === $i ? 'selected' : '' ?>>AP <?= $i ?></option><?php endfor; ?></select>
  <?php if ($scope !== 'class'): ?><select name="sy"><?php foreach (school_years() as $y): ?><option <?= $sy === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select>
  <select name="grade"><option value="0">All grades</option><?php for ($g = 1; $g <= 12; $g++): ?><option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>>Grade <?= $g ?></option><?php endfor; ?></select><?php endif; ?>
  <button class="btn">Show</button><button type="button" class="btn primary" data-print><?= icon('print', 16) ?> Print</button>
</form>
<p class="muted small noprint">Changed the report type? Press Show once to load the list for it.</p>
<article class="sheet">
  <header class="rep-h">
    <img src="assets/img/division.png" alt="" class="rep-seal">
    <div class="rep-c"><p>Department of Education</p><p><?= e(setting('region_name')) ?></p><p><?= e(setting('division_name')) ?></p><?php if ($head['school']): ?><p><b><?= e($head['school']) ?></b></p><?php endif; ?><?php if ($head['district']): ?><p><?= e($head['district']) ?></p><?php endif; ?>
      <h3>BUKIDNON'S UNIFIED LITERACY AND INTERVENTION GATEWAY</h3><p class="rep-tag">Building Up Literacy, Inspiring Growth</p><h2><?= e($rtitle) ?></h2></div>
    <img src="assets/img/bulig-logo.png" alt="" class="rep-logo">
  </header>
  <table class="rep-info"><tr>
    <td><?= $scope === 'class' ? 'Adviser' : ucfirst($scope === 'division' ? 'Division' : $scope) ?></td><td><b><?= e($scope === 'class' ? $head['teacher'] : ($scope === 'school' ? $head['school'] : ($scope === 'district' ? $head['district'] : ($scope === 'municipality' ? $head['municipality'] : setting('division_name'))))) ?></b></td>
    <td>Assessment period</td><td><b><?= e($pn) ?></b></td></tr>
    <tr><td>Grade level</td><td><b><?= e($head['grade']) ?><?= $head['section'] ? ' – ' . e($head['section']) : '' ?></b></td><td>Assessment tool</td><td><b><?= e($head['tool'] ?: 'CRLA / PHIL-IRI') ?></b></td></tr>
    <tr><td>School year</td><td><b><?= e($head['sy']) ?></b></td><td>Subject</td><td><b>ENGLISH (BULIG)</b></td></tr>
  </table>
  <table class="rep-t lv">
    <thead><tr><th></th><?php foreach (LEVELS as $k => [$l]): ?><th><?= e($l) ?></th><?php endforeach; ?><th>Total</th></tr>
    <tr class="sub"><th></th><?php foreach (LEVELS as $k => [$l, $c, $a]): ?><th><?= e($a) ?></th><?php endforeach; ?><th></th></tr></thead>
    <tbody><?php foreach (['M' => 'Male', 'F' => 'Female'] as $sx => $sl): $t = 0; ?><tr><th><?= $sl ?></th><?php foreach (LEVELS as $k => $_): $v = (int)($lv[$k][$sx] ?? 0); $t += $v; ?><td><?= $v ?></td><?php endforeach; ?><td><b><?= $t ?></b></td></tr><?php endforeach; ?>
    <tr class="tot"><th>Total</th><?php $g = 0; foreach (LEVELS as $k => $_): $v = (int)($lv[$k]['M'] ?? 0) + (int)($lv[$k]['F'] ?? 0); $g += $v; ?><td><?= $v ?></td><?php endforeach; ?><td><b><?= $g ?></b></td></tr></tbody>
  </table>
  <?php $none = ($tot['M'] + $tot['F']) - $g; if ($none > 0): ?><p class="rep-note"><?= $none ?> learner<?= $none === 1 ? '' : 's' ?> with no BULIG level yet (not counted above).</p><?php endif; ?>
  <div class="ks-grid">
  <?php foreach (KEY_STAGES as $ks => $ksn): if ($ks > 1 && empty($prof[$ks])) continue; ?>
  <table class="rep-t ks">
    <thead><tr><th colspan="<?= $ks === 1 ? 10 : 10 ?>"><?= e(strtoupper($ksn)) ?></th></tr>
    <tr><?php foreach (PERIODS as $p => $pl): ?><th><?= $pl ?></th><th>Male</th><th>Female</th><?php endforeach; ?><?php if ($ks === 1): ?><th>Comparative data</th><?php endif; ?></tr></thead>
    <tbody><?php foreach (PROFILES[$ks] as $i => $o): ?><tr><?php foreach (PERIODS as $p => $pl): ?><th><?= e($o) ?></th><td><?= (int)($prof[$ks][$p][$o]['M'] ?? 0) ?></td><td><?= (int)($prof[$ks][$p][$o]['F'] ?? 0) ?></td><?php endforeach; ?>
      <?php if ($ks === 1): $ck = ['bm' => 'BOSY → MOSY', 'me' => 'MOSY → EOSY', 'be' => 'BOSY → EOSY'][['bm', 'me', 'be'][$i] ?? ''] ?? ''; $ix = ['bm', 'me', 'be'][$i] ?? null; ?><td class="cmp"><?php if ($ix): ?><?= e($ck) ?>: <b><?= $imp[$ix][1] ? round(100 * $imp[$ix][0] / $imp[$ix][1]) : 0 ?>%</b> moved up<?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?>
    <tr class="tot"><?php foreach (PERIODS as $p => $pl): $t = 0; foreach (PROFILES[$ks] as $o) $t += (int)($prof[$ks][$p][$o]['M'] ?? 0) + (int)($prof[$ks][$p][$o]['F'] ?? 0); ?><th>Total</th><td colspan="2"><b><?= $t ?></b></td><?php endforeach; ?><?php if ($ks === 1): ?><td></td><?php endif; ?></tr></tbody>
  </table>
  <?php endforeach; ?>
  </div>
  <?php if ($brkKey && $brk): ?>
  <table class="rep-t brk">
    <thead><tr><th>No.</th><th><?= e(['class' => 'Class (Adviser)', 'school' => 'School', 'district' => 'District', 'municipality' => 'Municipality'][$brkKey]) ?></th><th>Male</th><th>Female</th><th>Total</th><?php foreach (LEVELS as $k => [$l]): ?><th><?= e($l) ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php $i = 0; foreach ($brk as $b): $i++; ?><tr><td><?= $i ?></td><td class="l"><?= e($b['name']) ?></td><td><?= $n($b['sex'], 'M') ?></td><td><?= $n($b['sex'], 'F') ?></td><td><b><?= $n($b['sex'], 'M') + $n($b['sex'], 'F') ?></b></td><?php foreach (LEVELS as $k => $_): ?><td><?= (int)($b['lv'][$k] ?? 0) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
  </table>
  <?php endif; ?>
  <div class="sign"><div><span>Prepared by:</span><b><?= e($scope === 'class' ? strtoupper($head['teacher']) : strtoupper(full_name($u))) ?></b><small><?= $scope === 'class' ? 'Class Adviser' : e(role_name($u['role'])) ?></small></div>
    <?php if (in_array($scope, ['class', 'school'], true)): ?><div><span>Noted:</span><b><?= e(strtoupper($head['head'] ?: '________________________')) ?></b><small><?= e($head['head_pos'] ?: 'School Head') ?></small></div><?php endif; ?></div>
  <p class="rep-foot">BULIG-DMS · printed <?= e(date('F j, Y')) ?> · <?= $nClasses ?> class<?= $nClasses === 1 ? '' : 'es' ?>, <?= $tot['M'] + $tot['F'] ?> learners</p>
</article>
