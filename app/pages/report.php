<?php
// Reading Monitoring Report for a class, school, district, municipality or the whole division.
$u = need();
$scope = in_array($_GET['scope'] ?? '', ['class', 'school', 'district', 'municipality', 'division'], true) ? $_GET['scope'] : '';
$id = (int)($_GET['id'] ?? 0); $period = max(0, min(AP_COUNT, (int)($_GET['period'] ?? 0)));
$sy = in_array($_GET['sy'] ?? '', school_years(), true) ? $_GET['sy'] : setting('school_year', '2026-2027');
$grade = max(0, min(12, (int)($_GET['grade'] ?? 0)));
$schools = visible_schools($u);
if ($scope === '') {   // default view for each role
    if ($u['role'] === 'teacher') { $cid = (int)val('SELECT id FROM classes WHERE teacher_id = ? ORDER BY school_year DESC, id DESC LIMIT 1', [$u['id']]); $scope = 'class'; $id = $cid; }
    elseif ($u['role'] === 'school') { $scope = 'school'; $id = (int)$u['scope_id']; }
    elseif ($u['role'] === 'district') { $scope = 'district'; $id = (int)$u['scope_id']; }
    elseif ($u['role'] === 'municipal') { $scope = 'municipality'; $id = (int)$u['scope_id']; }
    else $scope = 'division';
}
$sids = array_map('intval', array_column($schools, 'id')) ?: [0];
$head = ['school' => '', 'district' => '', 'municipality' => '', 'head' => '', 'head_pos' => '', 'teacher' => '', 'grade' => $grade ? 'Grade ' . $grade : 'All grades', 'section' => '', 'tool' => '', 'sy' => $sy];
$where = 'c.school_year = ?'; $args = [$sy]; $ok = true;
if ($scope === 'class') {
    $c = class_of($u, $id); $ok = (bool)$c;
    if ($c) { $where = 'c.id = ?'; $args = [$c['id']]; $sy = $c['school_year']; $head = ['school' => $c['school'], 'district' => $c['district'], 'municipality' => $c['municipality'], 'head' => $c['head_name'], 'head_pos' => $c['head_position'], 'teacher' => trim($c['first_name'] . ' ' . $c['last_name']), 'grade' => 'Grade ' . $c['grade'], 'section' => $c['section'], 'tool' => $c['tool'], 'sy' => $c['school_year']]; }
} elseif ($scope === 'school') {
    $ok = in_array($id, $sids, true); $s = $ok ? one('SELECT s.*, d.name district, m.name municipality FROM schools s LEFT JOIN districts d ON d.id = s.district_id LEFT JOIN municipalities m ON m.id = d.municipality_id WHERE s.id = ?', [$id]) : null;
    if ($s) { $where .= ' AND c.school_id = ?'; $args[] = $id; $head = array_merge($head, ['school' => $s['name'], 'district' => $s['district'], 'municipality' => $s['municipality'], 'head' => $s['head_name'], 'head_pos' => $s['head_position']]); }
} elseif ($scope === 'district') {
    $d = one('SELECT d.*, m.name municipality FROM districts d JOIN municipalities m ON m.id = d.municipality_id WHERE d.id = ?', [$id]);
    $ok = $d && in_array($u['role'], ['division', 'municipal', 'district'], true) && ($u['role'] === 'division' || ($u['role'] === 'municipal' && (int)$d['municipality_id'] === (int)$u['scope_id']) || ($u['role'] === 'district' && $id === (int)$u['scope_id']));
    if ($ok) { $where .= ' AND s.district_id = ?'; $args[] = $id; $head['district'] = $d['name']; $head['municipality'] = $d['municipality']; }
} elseif ($scope === 'municipality') {
    $m = one('SELECT * FROM municipalities WHERE id = ?', [$id]);
    $ok = $m && ($u['role'] === 'division' || ($u['role'] === 'municipal' && $id === (int)$u['scope_id']));
    if ($ok) { $where .= ' AND s.district_id IN (SELECT id FROM districts WHERE municipality_id = ?)'; $args[] = $id; $head['municipality'] = $m['name']; }
} else $ok = $u['role'] === 'division';
if (!$ok) { render('error', ['title' => 'Not allowed', 'msg' => 'That report is outside your area.']); return; }
if ($grade && $scope !== 'class') { $where .= ' AND c.grade = ?'; $args[] = $grade; }
$classes = all("SELECT c.*, s.name school, d.id did, d.name district, m.id mid, m.name municipality, CONCAT(u.first_name, ' ', u.last_name) teacher
  FROM classes c JOIN schools s ON s.id = c.school_id LEFT JOIN districts d ON d.id = s.district_id LEFT JOIN municipalities m ON m.id = d.municipality_id JOIN users u ON u.id = c.teacher_id
  WHERE $where AND " . school_scope_sql($u) . ' ORDER BY s.name, c.grade, c.section', $args);
// count
$lv = []; $prof = []; $brk = []; $imp = ['bm' => [0, 0], 'me' => [0, 0], 'be' => [0, 0]]; $tot = ['M' => 0, 'F' => 0];
$brkKey = ['class' => null, 'school' => 'class', 'district' => 'school', 'municipality' => 'district', 'division' => 'municipality'][$scope];
foreach ($classes as $c) {
    $levels = levels_at((int)$c['id'], $period);
    foreach (all('SELECT e.*, l.sex FROM enrolments e JOIN learners l ON l.id = e.learner_id WHERE e.class_id = ? AND e.active = 1', [$c['id']]) as $e) {
        $sx = $e['sex']; $tot[$sx]++; $L = $levels[$e['learner_id']] ?? null;
        if ($L) $lv[$L][$sx] = ($lv[$L][$sx] ?? 0) + 1;
        $ks = (int)$e['key_stage'];
        if ($ks) foreach (PERIODS as $p => $pl) if ($e[$p]) $prof[$ks][$p][$e[$p]][$sx] = ($prof[$ks][$p][$e[$p]][$sx] ?? 0) + 1;
        if ($ks) foreach (['bm' => ['bosy', 'mosy'], 'me' => ['mosy', 'eosy'], 'be' => ['bosy', 'eosy']] as $k => [$a, $b]) if ($e[$a] && $e[$b]) { $imp[$k][1]++; if (array_search($e[$b], PROFILES[$ks]) > array_search($e[$a], PROFILES[$ks])) $imp[$k][0]++; }
        if ($brkKey) {
            [$bk, $bn] = ['class' => [$c['id'], 'Grade ' . $c['grade'] . ' – ' . $c['section'] . ' (' . $c['teacher'] . ')'], 'school' => [$c['school_id'], $c['school']], 'district' => [$c['did'], $c['district'] ?: 'No district'], 'municipality' => [$c['mid'], $c['municipality'] ?: 'No municipality']][$brkKey];
            $brk[$bk]['name'] = $bn; $brk[$bk]['sex'][$sx] = ($brk[$bk]['sex'][$sx] ?? 0) + 1; if ($L) $brk[$bk]['lv'][$L] = ($brk[$bk]['lv'][$L] ?? 0) + 1;
        }
    }
}
uasort($brk, fn($a, $b) => strcmp($a['name'], $b['name']));
$title = ['class' => 'CLASSROOM', 'school' => 'SCHOOL', 'district' => 'DISTRICT', 'municipality' => 'MUNICIPAL', 'division' => 'DIVISION'][$scope] . ' READING MONITORING REPORT';
render('report', ['title' => 'Reports', 'scope' => $scope, 'id' => $id, 'period' => $period, 'sy' => $sy, 'grade' => $grade, 'head' => $head, 'lv' => $lv, 'prof' => $prof, 'brk' => $brk, 'brkKey' => $brkKey, 'imp' => $imp, 'tot' => $tot,
  'rtitle' => $title, 'nClasses' => count($classes), 'schools' => $schools, 'myClasses' => all('SELECT c.id, c.grade, c.section, c.school_year, s.name school FROM classes c JOIN schools s ON s.id = c.school_id WHERE ' . ($u['role'] === 'teacher' ? 'c.teacher_id = ' . (int)$u['id'] : school_scope_sql($u)) . ' ORDER BY c.school_year DESC, s.name, c.grade, c.section LIMIT 400'),
  'muns' => $u['role'] === 'division' ? municipalities() : [], 'dists' => in_array($u['role'], ['division', 'municipal'], true) ? array_filter(districts(), fn($d) => $u['role'] === 'division' || (int)$d['municipality_id'] === (int)$u['scope_id']) : []]);
