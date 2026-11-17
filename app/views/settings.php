<div class="card narrow"><h2>Settings</h2><p class="muted">These show on the sign-in page and every printed report.</p>
<form method="post" class="form"><?= csrf_field() ?>
  <label>Division name<input name="division_name" value="<?= e(setting('division_name')) ?>" required></label>
  <label>Region<input name="region_name" value="<?= e(setting('region_name')) ?>" required></label>
  <label>Current school year<select name="school_year"><?php foreach (school_years() as $y): ?><option <?= setting('school_year') === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select></label>
  <button class="btn primary">Save</button></form></div>
