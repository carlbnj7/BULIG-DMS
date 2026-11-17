<?php $levelOpts = function () { $h = '<option value="">Choose level</option>'; foreach (LEVELS as $k => [$l, $c, $a]) $h .= '<option value="' . $k . '">' . e($l . ' · ' . $a) . '</option>'; return $h; }; $lo = $levelOpts(); $nNew = count(array_filter($rows, fn($r) => !$r['dup'])); ?>
<div class="page-head"><div><h1>Check the list</h1><p class="muted"><?= count($rows) ?> learners · <?= $nNew ?> new · <?= count($rows) - $nNew ?> already in the system. Set each learner's BULIG level from the pre-assessment, then save.</p></div></div>
<form method="post" action="<?= url('upload') ?>" class="card"><?= csrf_field() ?><input type="hidden" name="step" value="save">
  <div class="steps"><span class="done"><b>1</b> Choose SF1</span><span class="on"><b>2</b> Check list and set BULIG levels</span><span><b>3</b> Save</span></div>
  <?php if (($head['school_id'] ?? '') !== '' && $head['school_id'] !== $school['school_code']): ?><div class="flash warn">This SF1 is for School ID <?= e($head['school_id']) ?>, but your school is <?= e($school['school_code']) ?> (<?= e($school['name']) ?>). Check that it is the right file.</div><?php endif; ?>
  <div class="form grid4">
    <label>School year<select name="sy"><?php foreach (school_years() as $y): ?><option <?= ($head['school_year'] ?? setting('school_year')) === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach; ?></select></label>
    <label>Grade level<select name="grade"><?php for ($g = 1; $g <= 12; $g++): ?><option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>>Grade <?= $g ?></option><?php endfor; ?></select></label>
    <label>Section<input name="section" value="<?= e($head['section'] ?? '') ?>" required maxlength="80"></label>
    <label>Assessment tool<select name="tool"><?php foreach (TOOLS as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></label>
  </div>
  <div class="fill-all"><span>Set all to</span><select data-fill="level"><?= $lo ?></select><select data-fill="ks"><option value="">Key stage</option><?php foreach (KEY_STAGES as $k => $n): ?><option value="<?= $k ?>"><?= e($n) ?></option><?php endforeach; ?></select></div>
  <div class="tbl-wrap"><table class="tbl grid">
  <thead><tr><th>#</th><th>Learner</th><th>LRN</th><th>Birthdate</th><th>BULIG level</th><th>Key stage</th><th>Status</th></tr></thead><tbody>
  <?php $sex = ''; $n = 0; foreach ($rows as $i => $r): if ($r['sex'] !== $sex): $sex = $r['sex']; $n = 0; ?><tr class="sex-row"><th colspan="7"><?= $sex === 'M' ? 'MALE' : 'FEMALE' ?></th></tr><?php endif; $n++; ?>
  <tr class="<?= $r['dup'] ? 'is-dup' : '' ?>">
    <td><?= $n ?></td><td><b><?= e($r['last']) ?></b>, <?= e($r['first'] . ' ' . $r['middle']) ?></td><td class="mono"><?= e($r['lrn']) ?></td><td class="small"><?= $r['bd'] ? e(date('M j, Y', strtotime($r['bd']))) : '—' ?></td>
    <td><select name="level[<?= $i ?>]" data-col="level"><?= $lo ?></select></td>
    <td><select name="ks[<?= $i ?>]" data-col="ks"><option value="">—</option><?php foreach (KEY_STAGES as $k => $nm): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?></select></td>
    <td><?php if (!$r['dup']): ?><span class="pill ok">New</span><input type="hidden" name="act[<?= $i ?>]" value="add">
      <?php elseif ($r['dup']['type'] === 'lrn'): ?><span class="pill warn">Same LRN in system</span><small class="dup-where"><?= e($r['dup']['place'] ?: 'No class yet') ?></small>
        <select name="act[<?= $i ?>]" class="sm"><option value="merge">Move to this class (keep one record)</option><option value="skip">Skip this learner</option></select>
      <?php else: ?><span class="pill warn">Possible duplicate</span><small class="dup-where">Same name and birthdate · <?= e($r['dup']['place']) ?></small>
        <select name="act[<?= $i ?>]" class="sm"><option value="merge">Same person: merge</option><option value="new">Different person: add new</option><option value="skip">Skip</option></select>
      <?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <div class="row-btns end"><a class="btn ghost" href="<?= url('upload') ?>">Back</a><button class="btn primary big"><?= icon('check', 18) ?> Save <?= count($rows) ?> learners</button></div>
</form>
