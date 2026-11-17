<?php
// Accounts for everyone below you: municipal, district, school heads / reading coordinators, teachers.
$me = need('division', 'municipal', 'district', 'school');
$roles = creatable_roles($me);
$schools = visible_schools($me); $sids = array_map('intval', array_column($schools, 'id'));
$dists = array_values(array_filter(districts(), fn($d) => $me['role'] === 'division' || ($me['role'] === 'municipal' && (int)$d['municipality_id'] === (int)$me['scope_id'])));
$muns = $me['role'] === 'division' ? municipalities() : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? '';
    if ($do === 'add') {
        $role = in_array($_POST['role'] ?? '', $roles, true) ? $_POST['role'] : '';
        $sc = ['division' => null, 'municipal' => (int)($_POST['s_mun'] ?? 0), 'district' => (int)($_POST['s_dist'] ?? 0), 'school' => (int)($_POST['s_school'] ?? 0), 'teacher' => (int)($_POST['s_school'] ?? 0)][$role] ?? 0;
        $okScope = $role === 'division' || ($role === 'municipal' && in_array($sc, array_map('intval', array_column($muns, 'id')), true)) || ($role === 'district' && in_array($sc, array_map('intval', array_column($dists, 'id')), true)) || (in_array($role, ['school', 'teacher'], true) && in_array($sc, $sids, true));
        $d = ['first_name' => proper((string)$_POST['first_name']), 'middle_name' => proper((string)($_POST['middle_name'] ?? '')), 'last_name' => proper((string)$_POST['last_name']), 'sex' => in_array($_POST['sex'] ?? '', ['M', 'F'], true) ? $_POST['sex'] : '', 'position' => trim((string)($_POST['position'] ?? '')), 'email' => trim((string)($_POST['email'] ?? ''))];
        if (!$role || !$okScope) flash('Choose the account type and where it belongs.', 'err');
        elseif ($d['first_name'] === '' || $d['last_name'] === '') flash('Type the first and last name.', 'err');
        else {
            $dup = one('SELECT user_code FROM users WHERE role = ? AND first_name = ? AND last_name = ? AND active = 1 AND merged_into IS NULL', [$role, $d['first_name'], $d['last_name']]);
            [$nid, $code, $pw] = add_user($me, $role, $sc, $d);
            $_SESSION['shown_pw'] = [full_name($d), $code, $pw, role_name($role)];
            flash('Account made. Give them the ID and password below.' . ($dup ? ' Note: ' . $dup['user_code'] . ' has the same name. If it is the same person, merge them on the Duplicates page.' : ''), $dup ? 'warn' : 'ok');
        }
        redirect('users');
    }
    $t = one('SELECT * FROM users WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
    if ($t && can_manage_user($me, $t)) {
        if ($do === 'reset') { $pw = make_password(); q('UPDATE users SET password_hash = ?, must_change = 1 WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $t['id']]); $_SESSION['shown_pw'] = [full_name($t), $t['user_code'], $pw, role_name($t['role'])]; flash('New password made for ' . full_name($t) . '.'); }
        elseif ($do === 'toggle') { q('UPDATE users SET active = 1 - active WHERE id = ?', [$t['id']]); flash(full_name($t) . ($t['active'] ? ' can no longer sign in.' : ' can sign in again.')); }
    }
    redirect('users', array_filter(['role' => $_POST['frole'] ?? '']));
}
$f = ['role' => in_array($_GET['role'] ?? '', $roles, true) ? $_GET['role'] : '', 'q' => trim((string)($_GET['q'] ?? ''))];
$w = ['u.merged_into IS NULL', 'u.id <> ' . (int)$me['id'], 'u.role IN ("' . implode('","', $roles ?: ['x']) . '")']; $a = [];
if ($me['role'] !== 'division') {
    $s = school_scope_sql($me, 'sx');
    $w[] = "((u.role IN ('school','teacher') AND u.scope_id IN (SELECT sx.id FROM schools sx WHERE $s))" . ($me['role'] === 'municipal' ? ' OR (u.role = "district" AND u.scope_id IN (SELECT id FROM districts WHERE municipality_id = ' . (int)$me['scope_id'] . '))' : '') . ')';
}
if ($f['role']) { $w[] = 'u.role = ?'; $a[] = $f['role']; }
if ($f['q'] !== '') { $w[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.user_code LIKE ? OR u.username LIKE ?)'; array_push($a, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
$rows = all('SELECT u.* FROM users u WHERE ' . implode(' AND ', $w) . ' ORDER BY FIELD(u.role, "division","municipal","district","school","teacher"), u.active DESC, u.last_name, u.first_name LIMIT 500', $a);
foreach ($rows as &$r) $r['where'] = scope_label($r); unset($r);
$shown = $_SESSION['shown_pw'] ?? null; unset($_SESSION['shown_pw']);
render('users', ['title' => 'Accounts', 'rows' => $rows, 'roles' => $roles, 'schools' => $schools, 'dists' => $dists, 'muns' => $muns, 'shown' => $shown, 'f' => $f, 'scripts' => ['users.js']]);
