<?php
$u = need();
$c = class_of($u, (int)($_GET['id'] ?? 0));
if (!$c) { render('error', ['title' => 'Not found', 'msg' => 'That class is not in your area.']); return; }
$edit = $u['role'] === 'teacher';
$ap = max(1, min(AP_COUNT, (int)($_GET['ap'] ?? 1)));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit) {
    $do = $_POST['do'] ?? '';
    if ($do === 'add') {
        $d = ['lrn' => preg_replace('/\D/', '', (string)$_POST['lrn']), 'last' => proper((string)$_POST['last_name']), 'first' => proper((string)$_POST['first_name']), 'middle' => proper((string)($_POST['middle_name'] ?? '')), 'sex' => ($_POST['sex'] ?? '') === 'F' ? 'F' : 'M'];
        if ($d['last'] === '' || $d['first'] === '' || ($d['lrn'] !== '' && strlen($d['lrn']) !== 12)) flash('Type the name, and a 12-digit LRN if there is one.', 'err');
        else {
            $lid = $d['lrn'] !== '' ? (int)val('SELECT id FROM learners WHERE lrn = ?', [$d['lrn']]) : 0;
            if ($lid) { q('UPDATE enrolments SET active = 0 WHERE learner_id = ? AND class_id <> ?', [$lid, $c['id']]); flash('This LRN was already in the system, so the learner was moved here (one record kept).'); }
            else { q('INSERT INTO learners (lrn, last_name, first_name, middle_name, sex) VALUES (?, ?, ?, ?, ?)', [$d['lrn'] ?: null, $d['last'], $d['first'], $d['middle'], $d['sex']]); $lid = (int)db()->lastInsertId(); flash('Learner added.'); }
            q('INSERT INTO enrolments (class_id, learner_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE active = 1', [$c['id'], $lid]);
        }
    } elseif ($do === 'remove') {
        q('UPDATE enrolments SET active = 0 WHERE class_id = ? AND learner_id = ?', [$c['id'], (int)$_POST['learner']]); flash('Learner removed from this class. Their record stays in the system.');
    } elseif ($do === 'class') {
        q('UPDATE classes SET section = ?, tool = ? WHERE id = ?', [trim((string)$_POST['section']) ?: $c['section'], in_array($_POST['tool'] ?? '', TOOLS, true) ? $_POST['tool'] : $c['tool'], $c['id']]); flash('Class saved.');
    }
    redirect('class', ['id' => $c['id'], 'ap' => $ap]);
}
$rows = all('SELECT l.*, e.pre_level, e.key_stage, e.bosy, e.mosy, e.eosy, (SELECT remark FROM assessments a WHERE a.class_id = e.class_id AND a.learner_id = l.id AND a.ap = ?) remark
  FROM enrolments e JOIN learners l ON l.id = e.learner_id WHERE e.class_id = ? AND e.active = 1 ORDER BY l.sex = "F", l.last_name, l.first_name', [$ap, $c['id']]);
$before = levels_at((int)$c['id'], $ap - 1); $after = levels_at((int)$c['id'], $ap);
$term = val('SELECT term FROM ap_terms WHERE class_id = ? AND ap = ?', [$c['id'], $ap]) ?: '';
$done = []; foreach (all('SELECT ap, COUNT(*) n FROM assessments WHERE class_id = ? AND remark <> "-" GROUP BY ap', [$c['id']]) as $r) $done[$r['ap']] = (int)$r['n'];
render('class', ['title' => 'Grade ' . $c['grade'] . ' – ' . $c['section'], 'c' => $c, 'rows' => $rows, 'ap' => $ap, 'before' => $before, 'after' => $after, 'term' => $term, 'done' => $done, 'edit' => $edit, 'scripts' => ['monitor.js']]);
