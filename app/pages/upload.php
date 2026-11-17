<?php
// Teacher: upload the SF1, check the list, set each learner's BULIG level and key stage, then save.
$u = need('teacher');
$school = one('SELECT * FROM schools WHERE id = ?', [(int)$u['scope_id']]);
if (!$school) { render('error', ['title' => 'No school yet', 'msg' => 'Your account is not linked to a school. Ask your school head or district.']); return; }
$step = $_POST['step'] ?? '';
if ($step === 'read') {
    $data = json_decode((string)($_POST['rows'] ?? ''), true);
    if (!is_array($data) || empty($data['rows'])) { flash('Choose the SF1 file first.', 'err'); redirect('upload'); }
    $head = array_map(fn($v) => trim((string)$v), (array)($data['head'] ?? []));
    $rows = []; $seen = [];
    foreach (array_slice((array)$data['rows'], 0, 120) as $r) {
        $lrn = preg_replace('/\D/', '', (string)($r['lrn'] ?? ''));
        if (strlen($lrn) !== 12 || isset($seen[$lrn])) continue; $seen[$lrn] = 1;
        $parts = array_map('trim', explode(',', (string)$r['name']));
        $bd = null;
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})$/', (string)($r['bd'] ?? ''), $m)) { $y = strlen($m[3]) === 2 ? 2000 + (int)$m[3] : (int)$m[3]; if (checkdate((int)$m[1], (int)$m[2], $y)) $bd = sprintf('%04d-%02d-%02d', $y, $m[1], $m[2]); }
        $row = ['lrn' => $lrn, 'last' => proper($parts[0] ?? ''), 'first' => proper($parts[1] ?? ''), 'middle' => proper(implode(' ', array_slice($parts, 2))), 'sex' => ($r['sex'] ?? 'M') === 'F' ? 'F' : 'M', 'bd' => $bd];
        if ($row['last'] === '' || $row['first'] === '') continue;
        // duplicate checks: same LRN already in the system, or same name and birthdate under another record
        $row['dup'] = null;
        $ex = one('SELECT l.*, (SELECT CONCAT("Grade ", c.grade, " ", c.section, " · ", u.first_name, " ", u.last_name, " · ", s.name) FROM enrolments e JOIN classes c ON c.id = e.class_id JOIN users u ON u.id = c.teacher_id JOIN schools s ON s.id = c.school_id WHERE e.learner_id = l.id AND e.active = 1 ORDER BY c.school_year DESC, e.added_at DESC LIMIT 1) place FROM learners l WHERE l.lrn = ?', [$lrn]);
        if ($ex) $row['dup'] = ['type' => 'lrn', 'id' => (int)$ex['id'], 'place' => $ex['place']];
        elseif ($bd && ($ex = one('SELECT id, lrn FROM learners WHERE last_name = ? AND first_name = ? AND birthdate = ?', [$row['last'], $row['first'], $bd]))) $row['dup'] = ['type' => 'name', 'id' => (int)$ex['id'], 'place' => 'LRN ' . ($ex['lrn'] ?: 'none')];
        $rows[] = $row;
    }
    usort($rows, fn($a, $b) => [$a['sex'] === 'F', $a['last'], $a['first']] <=> [$b['sex'] === 'F', $b['last'], $b['first']]);
    $grade = (int)preg_replace('/\D/', '', $head['grade'] ?? '');
    $_SESSION['sf1'] = ['head' => $head, 'rows' => $rows];
    render('upload_preview', ['title' => 'Check the list', 'head' => $head, 'rows' => $rows, 'school' => $school, 'grade' => $grade, 'scripts' => ['monitor.js']]);
    return;
}
if ($step === 'save' && !empty($_SESSION['sf1'])) {
    $S = $_SESSION['sf1']; $sy = in_array($_POST['sy'] ?? '', school_years(), true) ? $_POST['sy'] : setting('school_year', '2026-2027');
    $grade = max(1, min(12, (int)($_POST['grade'] ?? 0))); $section = trim((string)($_POST['section'] ?? '')) ?: 'Section';
    $tool = in_array($_POST['tool'] ?? '', TOOLS, true) ? $_POST['tool'] : 'CRLA';
    db()->beginTransaction();
    q('INSERT INTO classes (teacher_id, school_id, school_year, grade, section, tool) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE tool = VALUES(tool), id = LAST_INSERT_ID(id)', [$u['id'], $school['id'], $sy, $grade, $section, $tool]);
    $cid = (int)db()->lastInsertId(); $n = 0; $moved = 0; $merged = 0;
    foreach ($S['rows'] as $i => $r) {
        $act = $_POST['act'][$i] ?? 'add';
        if ($act === 'skip') continue;
        $lvl = isset(LEVELS[$_POST['level'][$i] ?? '']) ? $_POST['level'][$i] : null;
        $ks = isset(KEY_STAGES[(int)($_POST['ks'][$i] ?? 0)]) ? (int)$_POST['ks'][$i] : null;
        if ($r['dup'] && $act !== 'new') {
            $lid = $r['dup']['id'];
            if ($r['dup']['type'] === 'name') { q('UPDATE learners SET lrn = COALESCE(lrn, ?) WHERE id = ?', [$r['lrn'], $lid]); $merged++; }
            $prev = one('SELECT pre_level, key_stage FROM enrolments WHERE learner_id = ? AND class_id <> ? AND active = 1 LIMIT 1', [$lid, $cid]);
            if ($prev) { $lvl = $lvl ?? $prev['pre_level']; $ks = $ks ?? ($prev['key_stage'] !== null ? (int)$prev['key_stage'] : null); }
            $old = (int)q('UPDATE enrolments SET active = 0 WHERE learner_id = ? AND class_id <> ? AND active = 1', [$lid, $cid])->rowCount(); if ($old) $moved++;
        } else {
            q('INSERT INTO learners (lrn, last_name, first_name, middle_name, sex, birthdate) VALUES (?, ?, ?, ?, ?, ?)', [$r['lrn'], $r['last'], $r['first'], $r['middle'], $r['sex'], $r['bd']]);
            $lid = (int)db()->lastInsertId();
        }
        q('INSERT INTO enrolments (class_id, learner_id, pre_level, key_stage) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE pre_level = COALESCE(VALUES(pre_level), pre_level), key_stage = COALESCE(VALUES(key_stage), key_stage), active = 1', [$cid, $lid, $lvl, $ks]);
        $n++;
    }
    db()->commit();
    unset($_SESSION['sf1']);
    flash("$n learners saved to Grade $grade – $section." . ($moved ? " $moved moved here from another class." : '') . ($merged ? " $merged matched to an existing record." : ''));
    redirect('class', ['id' => $cid]);
}
render('upload', ['title' => 'Upload SF1', 'school' => $school, 'scripts' => ['xlsx.full.min.js', 'sf1.js']]);
