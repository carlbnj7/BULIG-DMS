<?php $ds = function ($sel, $form = '') use ($dists) { $h = '<select name="district_id"' . ($form ? ' form="' . $form . '"' : '') . ' required aria-label="District">'; foreach ($dists as $d) $h .= '<option value="' . (int)$d['id'] . '"' . ((int)$sel === (int)$d['id'] ? ' selected' : '') . '>' . e($d['name'] . ' · ' . $d['municipality']) . '</option>'; return $h . '</select>'; }; ?>
<div class="page-head"><div><h1>Schools</h1><p class="muted">The School ID must match the one on the SF1. The school head's name goes on the printed reports.</p></div></div>
<?php if (!$dists): ?><div class="flash warn">No districts yet. The division admin adds them on the Municipalities page.</div><?php endif; ?>
<form method="post" class="card form grid3"><?= csrf_field() ?><input type="hidden" name="do" value="add">
  <label>School ID<input name="school_code" required inputmode="numeric" maxlength="12"></label><label>School name<input name="name" required></label><label>District<?= $ds(0) ?></label>
  <label>School head<input name="head_name"></label><label>Position<input name="head_position" placeholder="e.g. Principal I"></label><button class="btn primary"><?= icon('plus', 16) ?> Add school</button>
</form>
<div class="card"><table class="tbl"><thead><tr><th>School ID</th><th>Name</th><th>District</th><th>School head · position</th><th>Teachers</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $f = 's' . (int)$r['id']; ?><tr>
  <td><input form="<?= $f ?>" name="school_code" value="<?= e($r['school_code']) ?>" class="w-code" aria-label="School ID"></td><td><input form="<?= $f ?>" name="name" value="<?= e($r['name']) ?>" aria-label="School name"></td><td><?= $ds($r['district_id'], $f) ?></td>
  <td><input form="<?= $f ?>" name="head_name" value="<?= e($r['head_name']) ?>" aria-label="School head"><input form="<?= $f ?>" name="head_position" value="<?= e($r['head_position']) ?>" aria-label="Position" class="mt4"></td><td><?= (int)$r['teachers'] ?></td>
  <td><form id="<?= $f ?>" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm">Save</button></form></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">No schools yet.</td></tr><?php endif; ?>
</tbody></table></div>
