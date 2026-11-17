<?php
$u = need();
$sy = in_array($_GET['sy'] ?? '', school_years(), true) ? $_GET['sy'] : setting('school_year', '2026-2027');
$sid = (int)($_GET['school'] ?? 0); $q = trim((string)($_GET['q'] ?? ''));
$w = ['c.school_year = ?', $u['role'] === 'teacher' ? 'c.teacher_id = ' . (int)$u['id'] : school_scope_sql($u)]; $a = [$sy];
if ($sid) { $w[] = 'c.school_id = ?'; $a[] = $sid; }
if ($q !== '') { $w[] = '(c.section LIKE ? OR t.last_name LIKE ? OR t.first_name LIKE ?)'; array_push($a, "%$q%", "%$q%", "%$q%"); }
$rows = all('SELECT c.*, s.name school, CONCAT(t.first_name, " ", t.last_name) teacher, (SELECT COUNT(*) FROM enrolments e WHERE e.class_id = c.id AND e.active = 1) n, (SELECT MAX(ap) FROM assessments x WHERE x.class_id = c.id) last_ap
  FROM classes c JOIN schools s ON s.id = c.school_id JOIN users t ON t.id = c.teacher_id WHERE ' . implode(' AND ', $w) . ' ORDER BY s.name, c.grade, c.section LIMIT 500', $a);
render('classes', ['title' => 'Classes', 'rows' => $rows, 'sy' => $sy, 'sid' => $sid, 'q' => $q, 'schools' => $u['role'] === 'teacher' ? [] : visible_schools($u)]);
