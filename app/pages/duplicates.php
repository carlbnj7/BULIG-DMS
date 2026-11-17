<?php
// Find the same learner or the same staff member entered twice, and merge them into one record.
$u = need('division', 'municipal', 'district', 'school');
$S = school_scope_sql($u);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keep = (int)($_POST['keep'] ?? 0); $drop = (int)($_POST['drop'] ?? 0);
    if (($_POST['do'] ?? '') === 'learner' && $keep !== $drop) {
        $vis = fn($id) => (bool)val("SELECT 1 FROM enrolments e JOIN classes c ON c.id = e.class_id JOIN schools s ON s.id = c.school_id WHERE e.learner_id = ? AND $S", [$id]);
        $a = one('SELECT * FROM learners WHERE id = ?', [$keep]); $b = one('SELECT * FROM learners WHERE id = ?', [$drop]);
        if ($a && $b && $vis($keep) && $vis($drop)) {
            db()->beginTransaction();
            foreach (all('SELECT * FROM enrolments WHERE learner_id = ?', [$drop]) as $e) {
                if (val('SELECT 1 FROM enrolments WHERE class_id = ? AND learner_id = ?', [$e['class_id'], $keep])) {
                    q('UPDATE enrolments k SET k.pre_level = COALESCE(k.pre_level, ?), k.key_stage = COALESCE(k.key_stage, ?), k.bosy = COALESCE(k.bosy, ?), k.mosy = COALESCE(k.mosy, ?), k.eosy = COALESCE(k.eosy, ?), k.active = GREATEST(k.active, ?) WHERE k.class_id = ? AND k.learner_id = ?', [$e['pre_level'], $e['key_stage'], $e['bosy'], $e['mosy'], $e['eosy'], $e['active'], $e['class_id'], $keep]);
                    q('INSERT IGNORE INTO assessments (class_id, learner_id, ap, remark) SELECT class_id, ?, ap, remark FROM assessments WHERE class_id = ? AND learner_id = ?', [$keep, $e['class_id'], $drop]);
                    q('DELETE FROM enrolments WHERE class_id = ? AND learner_id = ?', [$e['class_id'], $drop]);
                } else q('UPDATE enrolments SET learner_id = ? WHERE class_id = ? AND learner_id = ?', [$keep, $e['class_id'], $drop]);
                q('UPDATE IGNORE assessments SET learner_id = ? WHERE class_id = ? AND learner_id = ?', [$keep, $e['class_id'], $drop]);
                q('DELETE FROM assessments WHERE class_id = ? AND learner_id = ?', [$e['class_id'], $drop]);
            }
            $lrn = $a['lrn'] ?: $b['lrn'];
            q('DELETE FROM learners WHERE id = ?', [$drop]);
            q('UPDATE learners SET lrn = ?, birthdate = COALESCE(birthdate, ?), middle_name = IF(middle_name = "", ?, middle_name) WHERE id = ?', [$lrn, $b['birthdate'], $b['middle_name'], $keep]);
            db()->commit();
            flash('Merged. One record kept, with all classes and results.');
        }
    } elseif (($_POST['do'] ?? '') === 'user' && $keep !== $drop) {
        $a = one('SELECT * FROM users WHERE id = ?', [$keep]); $b = one('SELECT * FROM users WHERE id = ?', [$drop]);
        if ($a && $b && $a['role'] === $b['role'] && can_manage_user($u, $a) && can_manage_user($u, $b)) {
            q('UPDATE IGNORE classes SET teacher_id = ? WHERE teacher_id = ?', [$keep, $drop]);
            q('UPDATE users SET active = 0, merged_into = ?, username = CONCAT("merged-", id) WHERE id = ?', [$keep, $drop]);
            flash(full_name($b) . ' (' . $b['user_code'] . ') was merged into ' . $a['user_code'] . '. Use ' . $a['user_code'] . ' to sign in.');
        }
    }
    redirect('duplicates');
}
// learners: same last name, first name and birthdate (or same name with one LRN missing), inside your area
$lg = all("SELECT l.last_name, l.first_name, GROUP_CONCAT(DISTINCT l.id ORDER BY l.id) ids FROM learners l JOIN enrolments e ON e.learner_id = l.id JOIN classes c ON c.id = e.class_id JOIN schools s ON s.id = c.school_id
  WHERE $S GROUP BY l.last_name, l.first_name, COALESCE(l.birthdate, '') HAVING COUNT(DISTINCT l.id) > 1 LIMIT 100");
$groups = [];
foreach ($lg as $g) $groups[] = all('SELECT l.*, (SELECT CONCAT("Grade ", c.grade, " ", c.section, " · ", s.name, " · SY ", c.school_year) FROM enrolments e JOIN classes c ON c.id = e.class_id JOIN schools s ON s.id = c.school_id WHERE e.learner_id = l.id ORDER BY e.active DESC, c.school_year DESC LIMIT 1) place,
  (SELECT COUNT(*) FROM assessments a WHERE a.learner_id = l.id) results FROM learners l WHERE l.id IN (' . implode(',', array_map('intval', explode(',', $g['ids']))) . ')');
$ug = [];
foreach (all('SELECT role, first_name, last_name, GROUP_CONCAT(id ORDER BY id) ids FROM users WHERE merged_into IS NULL AND role IN ("' . implode('","', creatable_roles($u) ?: ['x']) . '") GROUP BY role, first_name, last_name HAVING COUNT(*) > 1 LIMIT 100') as $g) {
    $us = array_values(array_filter(all('SELECT * FROM users WHERE id IN (' . implode(',', array_map('intval', explode(',', $g['ids']))) . ')'), fn($x) => can_manage_user($u, $x)));
    if (count($us) > 1) { foreach ($us as &$x) { $x['where'] = scope_label($x); $x['classes'] = (int)val('SELECT COUNT(*) FROM classes WHERE teacher_id = ?', [$x['id']]); } unset($x); $ug[] = $us; }
}
render('duplicates', ['title' => 'Duplicates', 'groups' => $groups, 'ug' => $ug]);
