<?php
$u = need();
$sy = setting('school_year', '2026-2027');
$sql = $u['role'] === 'teacher' ? 'c.teacher_id = ' . (int)$u['id'] : school_scope_sql($u);
$classes = all("SELECT c.*, s.name school, CONCAT(t.first_name, ' ', t.last_name) teacher, (SELECT COUNT(*) FROM enrolments e WHERE e.class_id = c.id AND e.active = 1) n,
  (SELECT COUNT(*) FROM enrolments e WHERE e.class_id = c.id AND e.active = 1 AND e.pre_level IS NOT NULL) leveled, (SELECT MAX(ap) FROM assessments a WHERE a.class_id = c.id) last_ap
  FROM classes c JOIN schools s ON s.id = c.school_id JOIN users t ON t.id = c.teacher_id WHERE c.school_year = ? AND $sql ORDER BY s.name, c.grade, c.section", [$sy]);
// learners by their latest level, across all classes shown
$dist = []; $learners = 0;
foreach ($classes as $c) { $learners += $c['n']; foreach (levels_at((int)$c['id'], (int)$c['last_ap']) as $L) if ($L) $dist[$L] = ($dist[$L] ?? 0) + 1; }
$stats = ['classes' => count($classes), 'learners' => $learners];
if ($u['role'] !== 'teacher') {
    $stats['schools'] = (int)val('SELECT COUNT(*) FROM schools s WHERE ' . school_scope_sql($u));
    $stats['teachers'] = (int)val('SELECT COUNT(*) FROM users t JOIN schools s ON s.id = t.scope_id WHERE t.role = "teacher" AND t.active = 1 AND t.merged_into IS NULL AND ' . school_scope_sql($u));
}
render('home', ['title' => 'Dashboard', 'classes' => $classes, 'dist' => $dist, 'stats' => $stats, 'sy' => $sy]);
