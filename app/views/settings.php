<div class="card narrow"><h2>Settings</h2><p class="muted">These show on the sign-in page and every printed report.</p>
<form method="post" class="form"><?= csrf_field() ?>
  <label>Division name<input name="division_name" value="<?= e(setting('division_name')) ?>" required></label>
  <label>Region<input name="region_name" value="<?= e(setting('region_name')) ?>" required></label>
  <label>Current school year<select name="school_year"><?php foreach (school_years() as $y): ?><option <?= setting('school_year') === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select></label>
  <button class="btn primary">Save</button></form></div>

<div class="card narrow cycles-card"><h2>Cycles</h2><p class="muted">Group the assessment periods into cycles, for example Cycle 1 = AP 1 to AP 5. Teachers see the cycle on the class page, and reports can be shown per cycle (compared with the period just before the cycle starts).</p>
<form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="do" value="cycles">
  <div class="cyc-head"><span>Name</span><span>From</span><span>To</span></div>
  <?php $cs = cycles(); for ($i = 0; $i < max(6, count($cs) + 2); $i++): $c = $cs[$i] ?? null; ?>
  <div class="cyc-row">
    <input name="cname[]" value="<?= e($c['name'] ?? '') ?>" placeholder="<?= $i < 3 ? 'e.g. Cycle ' . ($i + 1) : '' ?>" aria-label="Cycle name">
    <select name="cfrom[]" aria-label="From AP"><option value="0">—</option><?php for ($a = 1; $a <= AP_COUNT; $a++): ?><option value="<?= $a ?>" <?= ($c['from'] ?? 0) === $a ? 'selected' : '' ?>>AP <?= $a ?></option><?php endfor; ?></select>
    <select name="cto[]" aria-label="To AP"><option value="0">—</option><?php for ($a = 1; $a <= AP_COUNT; $a++): ?><option value="<?= $a ?>" <?= ($c['to'] ?? 0) === $a ? 'selected' : '' ?>>AP <?= $a ?></option><?php endfor; ?></select>
  </div>
  <?php endfor; ?>
  <small class="muted">Leave a row empty to remove it. Each AP can be in only one cycle.</small>
  <button class="btn primary">Save cycles</button>
</form></div>
