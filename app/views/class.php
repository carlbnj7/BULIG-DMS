<?php $dis = $edit ? '' : ' disabled'; $teacher = trim($c['first_name'] . ' ' . $c['last_name']); ?>
<div class="class-head card">
  <div><p class="eyebrow"><?= e($c['school']) ?> · SY <?= e($c['school_year']) ?></p><h1>Grade <?= (int)$c['grade'] ?> – <?= e($c['section']) ?></h1><p class="muted">Adviser: <?= e($teacher) ?> · <?= e($c['tool']) ?> · English · <?= count($rows) ?> learners</p></div>
  <div class="row-btns"><a class="btn" href="<?= url('report', ['scope' => 'class', 'id' => $c['id'], 'period' => 0]) ?>"><?= icon('print', 16) ?> Pre-assessment report</a><a class="btn primary" href="<?= url('report', ['scope' => 'class', 'id' => $c['id'], 'period' => $ap]) ?>"><?= icon('print', 16) ?> AP <?= $ap ?> report</a></div>
</div>
<nav class="ap-tabs" aria-label="Assessment period">
  <?php $prevC = null; for ($i = 1; $i <= AP_COUNT; $i++): $cy = cycle_of($i); if ($cy && $cy !== $prevC): ?><span class="cyc-tag"><?= e($cy['name']) ?></span><?php endif; $prevC = $cy; ?><a href="<?= url('class', ['id' => $c['id'], 'ap' => $i]) ?>" class="<?= $i === $ap ? 'on' : '' ?> <?= isset($done[$i]) ? 'has' : '' ?>">AP <?= $i ?></a><?php endfor; ?>
</nav>
<div class="card monitor" data-monitor data-class="<?= (int)$c['id'] ?>" data-ap="<?= $ap ?>" data-profiles='<?= e(json_encode(PROFILES)) ?>'>
  <div class="mon-bar">
    <b>Assessment period <?= $ap ?><?php if ($cy = cycle_of($ap)): ?> · <?= e($cy['name']) ?> (AP <?= (int)$cy['from'] ?>–<?= (int)$cy['to'] ?>)<?php endif; ?></b>
    <label class="inline-lbl">Term <select data-term<?= $dis ?>><option value="">—</option><?php foreach (TERMS as $t): ?><option <?= $term === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
    <span class="legend"><i class="lg-pass"></i> Passed moves the learner up one level <i class="lg-nr"></i> Not ready stays</span>
    <span class="save-state" data-save-state></span>
  </div>
  <div class="tbl-wrap"><table class="tbl grid mon">
  <thead>
    <tr><th rowspan="2">No.</th><th rowspan="2">Name of learner</th><th rowspan="2" class="c-pre">Pre-assessment<br>level</th><th rowspan="2">Key<br>stage</th><th colspan="3" class="c-prof">Reading profile</th><th colspan="3" class="c-ap">AP <?= $ap ?></th></tr>
    <tr><th class="c-prof">BOSY</th><th class="c-prof">MOSY</th><th class="c-prof">EOSY</th><th class="c-ap">Level before</th><th class="c-ap">Result</th><th class="c-ap">Level now · reading area</th></tr>
  </thead><tbody>
  <?php $sex = ''; $n = 0; foreach ($rows as $r): if ($r['sex'] !== $sex): $sex = $r['sex']; $n = 0; ?><tr class="sex-row"><th colspan="10"><?= $sex === 'M' ? 'MALE' : 'FEMALE' ?></th></tr><?php endif; $n++; $lid = (int)$r['id']; $now = $after[$lid] ?? null; ?>
  <tr data-learner="<?= $lid ?>">
    <td><?= $n ?></td>
    <td class="nm"><b><?= e($r['last_name']) ?></b>, <?= e($r['first_name'] . ' ' . $r['middle_name']) ?><?php if ($r['lrn']): ?><small class="mono"><?= e($r['lrn']) ?></small><?php endif; ?></td>
    <td><select data-f="pre_level"<?= $dis ?>><option value="">—</option><?php foreach (LEVELS as $k => [$l]): ?><option value="<?= $k ?>" <?= $r['pre_level'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></td>
    <td><select data-f="key_stage"<?= $dis ?>><option value="">—</option><?php foreach (KEY_STAGES as $k => $x): ?><option value="<?= $k ?>" <?= (int)$r['key_stage'] === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?></select></td>
    <?php foreach (PERIODS as $p => $pl): ?><td class="c-prof"><select data-f="<?= $p ?>"<?= $dis ?> data-val="<?= e($r[$p]) ?>"><option value="">—</option><?php foreach (PROFILES[(int)$r['key_stage']] ?? [] as $o): ?><option <?= $r[$p] === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></td><?php endforeach; ?>
    <td class="c-ap" data-before><?= e(level_label($before[$lid] ?? null)) ?: '—' ?></td>
    <td class="c-ap"><div class="seg-res" role="group" aria-label="Result">
      <?php foreach (['PASSED' => 'Passed', 'NOT READY' => 'Not ready', '-' => '–'] as $k => $l): ?><button type="button" data-remark="<?= e($k) ?>" class="<?= $r['remark'] === $k ? 'on' : '' ?> r-<?= $k === 'PASSED' ? 'p' : ($k === '-' ? 'n' : 'x') ?>"<?= $dis ?>><?= e($l) ?></button><?php endforeach; ?>
    </div></td>
    <td class="c-ap now"><b data-level><?= e(level_label($now)) ?: '—' ?></b><small data-area><?= $now ? e(LEVELS[$now][2]) : '' ?></small></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="10" class="muted center">No learners yet. <?= $edit ? '<a href="' . url('upload') . '">Upload your SF1</a> or add one below.' : '' ?></td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php if ($edit): ?>
<div class="two-col">
<details class="card"><summary><?= icon('plus', 18) ?> Add a learner</summary>
  <form method="post" class="form grid2"><?= csrf_field() ?><input type="hidden" name="do" value="add">
    <label>Last name<input name="last_name" required></label><label>First name<input name="first_name" required></label>
    <label>Middle name<input name="middle_name"></label><label>LRN (12 digits)<input name="lrn" inputmode="numeric" maxlength="12"></label>
    <label>Sex<select name="sex"><option value="M">Male</option><option value="F">Female</option></select></label><button class="btn primary">Add</button>
  </form></details>
<details class="card"><summary><?= icon('edit', 18) ?> Class details and remove a learner</summary>
  <form method="post" class="form grid2"><?= csrf_field() ?><input type="hidden" name="do" value="class">
    <label>Section<input name="section" value="<?= e($c['section']) ?>"></label><label>Assessment tool<select name="tool"><?php foreach (TOOLS as $t): ?><option <?= $c['tool'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label><button class="btn">Save</button>
  </form>
  <form method="post" class="form inline-form" data-confirm="Remove this learner from the class?"><?= csrf_field() ?><input type="hidden" name="do" value="remove">
    <label>Learner<select name="learner"><?php foreach ($rows as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['last_name'] . ', ' . $r['first_name']) ?></option><?php endforeach; ?></select></label><button class="btn ghost">Remove</button>
  </form></details>
</div>
<?php endif; ?>
