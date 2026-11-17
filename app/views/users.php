<div class="page-head"><div><h1>Accounts</h1><p class="muted">The system makes each account ID and starter password. On first sign-in, they pick their own username and password.</p></div></div>
<?php if ($shown): ?><div class="pw-slip"><b><?= e($shown[0]) ?></b> <span class="pill"><?= e($shown[3]) ?></span><br><b>Account ID:</b> <code><?= e($shown[1]) ?></code> <b>Password:</b> <code><?= e($shown[2]) ?></code><small>This password shows only once. Write it down now.</small></div><?php endif; ?>
<details class="card add" <?= !$rows ? 'open' : '' ?>>
  <summary><?= icon('plus', 18) ?> Make an account</summary>
  <form method="post" class="form grid3" data-userform><?= csrf_field() ?><input type="hidden" name="do" value="add">
    <label>Account type<select name="role" data-role required><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $r === 'teacher' ? 'selected' : '' ?>><?= e(role_name($r)) ?></option><?php endforeach; ?></select></label>
    <label data-for="municipal">Municipality<select name="s_mun"><?php foreach ($muns as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select></label>
    <label data-for="district">District<select name="s_dist"><?php foreach ($dists as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name'] . ' · ' . $d['municipality']) ?></option><?php endforeach; ?></select></label>
    <label data-for="school teacher">School<select name="s_school"><?php foreach ($schools as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name'] . ' (' . $s['school_code'] . ')') ?></option><?php endforeach; ?></select></label>
    <label>First name<input name="first_name" required></label><label>Middle name<input name="middle_name"></label><label>Last name<input name="last_name" required></label>
    <label>Sex<select name="sex"><option value="">—</option><option value="M">Male</option><option value="F">Female</option></select></label>
    <label>Position<input name="position" placeholder="e.g. Teacher III, Principal I"></label><label>Email (optional)<input type="email" name="email"></label>
    <button class="btn primary">Make account</button>
  </form>
</details>
<form class="filters" method="get"><input type="hidden" name="p" value="users"><input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search name or ID">
  <select name="role"><option value="">All account types</option><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $f['role'] === $r ? 'selected' : '' ?>><?= e(role_name($r)) ?></option><?php endforeach; ?></select><button class="btn">Show</button></form>
<div class="card"><table class="tbl"><thead><tr><th>Name</th><th>ID · Username</th><th>Type · Where</th><th>Last sign-in</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $t): ?><tr class="<?= $t['active'] ? '' : 'off' ?>">
  <td><b><?= e(full_name($t)) ?></b><?= $t['active'] ? '' : ' <span class="pill">Off</span>' ?><br><small class="muted"><?= e($t['position']) ?></small></td>
  <td><code><?= e($t['user_code']) ?></code><br><small class="muted"><?= $t['setup_done'] ? e($t['username']) : 'Not set up yet' ?></small></td>
  <td><span class="pill role-<?= e($t['role']) ?>"><?= e(role_name($t['role'])) ?></span><br><small class="muted"><?= e($t['where']) ?></small></td>
  <td class="small muted"><?= $t['last_login'] ? e(date('M j, Y', strtotime($t['last_login']))) : 'Never' ?></td>
  <td class="acts-cell">
    <form method="post" data-confirm="Make a new password for this account?"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn sm ghost">New password</button></form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn sm ghost"><?= $t['active'] ? 'Turn off' : 'Turn on' ?></button></form>
  </td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">No accounts yet.</td></tr><?php endif; ?>
</tbody></table></div>
