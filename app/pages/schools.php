<?php
$u = need('division', 'municipal', 'district');
$dists = array_values(array_filter(districts(), fn($d) => $u['role'] === 'division' || ($u['role'] === 'municipal' && (int)$d['municipality_id'] === (int)$u['scope_id']) || ($u['role'] === 'district' && (int)$d['id'] === (int)$u['scope_id'])));
$dids = array_map('intval', array_column($dists, 'id'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? ''; $code = preg_replace('/\D/', '', (string)($_POST['school_code'] ?? '')); $name = trim((string)($_POST['name'] ?? '')); $did = (int)($_POST['district_id'] ?? 0);
    $hn = trim((string)($_POST['head_name'] ?? '')); $hp = trim((string)($_POST['head_position'] ?? ''));
    if (!in_array($did, $dids, true)) flash('Choose a district in your area.', 'err');
    elseif ($code === '' || $name === '') flash('Type the School ID and name.', 'err');
    elseif ($do === 'add') { if (val('SELECT 1 FROM schools WHERE school_code = ?', [$code])) flash('School ID ' . $code . ' is already on the list.', 'err'); else { q('INSERT INTO schools (school_code, name, district_id, head_name, head_position) VALUES (?, ?, ?, ?, ?)', [$code, $name, $did, $hn, $hp]); flash('School added.'); } }
    elseif ($do === 'save') { $id = (int)$_POST['id']; if (val('SELECT 1 FROM schools s WHERE s.id = ? AND ' . school_scope_sql($u), [$id])) { try { q('UPDATE schools SET school_code = ?, name = ?, district_id = ?, head_name = ?, head_position = ? WHERE id = ?', [$code, $name, $did, $hn, $hp, $id]); flash('School saved.'); } catch (PDOException $ex) { flash('That School ID is used by another school.', 'err'); } } }
    redirect('schools');
}
$rows = all('SELECT s.*, (SELECT COUNT(*) FROM users t WHERE t.role = "teacher" AND t.scope_id = s.id AND t.active = 1 AND t.merged_into IS NULL) teachers, (SELECT COUNT(*) FROM classes c WHERE c.school_id = s.id) classes FROM schools s WHERE ' . school_scope_sql($u) . ' ORDER BY s.name');
render('schools', ['title' => 'Schools', 'rows' => $rows, 'dists' => $dists]);
