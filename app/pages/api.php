<?php
// Saves one change from the monitoring screen. Returns JSON.
header('Content-Type: application/json');
$u = me();
if (!$u || $u['role'] !== 'teacher' || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); echo json_encode(['error' => 'Not allowed.']); exit; }
$c = class_of($u, (int)($_POST['class'] ?? 0));
if (!$c) { http_response_code(404); echo json_encode(['error' => 'Class not found.']); exit; }
$lid = (int)($_POST['learner'] ?? 0); $f = (string)($_POST['field'] ?? ''); $v = (string)($_POST['value'] ?? ''); $ap = (int)($_POST['ap'] ?? 0);
if ($f === 'term') {
    if ($ap < 1 || $ap > AP_COUNT) exit(json_encode(['error' => 'Bad period.']));
    if (in_array($v, TERMS, true)) q('INSERT INTO ap_terms (class_id, ap, term) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE term = VALUES(term)', [$c['id'], $ap, $v]);
    else q('DELETE FROM ap_terms WHERE class_id = ? AND ap = ?', [$c['id'], $ap]);
    exit(json_encode(['ok' => 1]));
}
$e = one('SELECT * FROM enrolments WHERE class_id = ? AND learner_id = ? AND active = 1', [$c['id'], $lid]);
if (!$e) { http_response_code(404); exit(json_encode(['error' => 'Learner not in this class.'])); }
if ($f === 'pre_level') { if ($v !== '' && !isset(LEVELS[$v])) exit(json_encode(['error' => 'Bad level.'])); q('UPDATE enrolments SET pre_level = ? WHERE class_id = ? AND learner_id = ?', [$v ?: null, $c['id'], $lid]); }
elseif ($f === 'key_stage') { $k = (int)$v; q('UPDATE enrolments SET key_stage = ?, bosy = IF(? = key_stage, bosy, NULL), mosy = IF(? = key_stage, mosy, NULL), eosy = IF(? = key_stage, eosy, NULL) WHERE class_id = ? AND learner_id = ?', [isset(KEY_STAGES[$k]) ? $k : null, $k, $k, $k, $c['id'], $lid]); }
elseif (isset(PERIODS[$f])) { $ks = (int)$e['key_stage']; if ($v !== '' && !in_array($v, PROFILES[$ks] ?? [], true)) exit(json_encode(['error' => 'Choose the key stage first.'])); q("UPDATE enrolments SET $f = ? WHERE class_id = ? AND learner_id = ?", [$v ?: null, $c['id'], $lid]); }
elseif ($f === 'remark') {
    if ($ap < 1 || $ap > AP_COUNT) exit(json_encode(['error' => 'Bad period.']));
    if (in_array($v, ['PASSED', 'NOT READY', '-'], true)) q('INSERT INTO assessments (class_id, learner_id, ap, remark) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE remark = VALUES(remark)', [$c['id'], $lid, $ap, $v]);
    else q('DELETE FROM assessments WHERE class_id = ? AND learner_id = ? AND ap = ?', [$c['id'], $lid, $ap]);
} else exit(json_encode(['error' => 'Unknown field.']));
$lv = levels_at((int)$c['id'], max(0, $ap))[$lid] ?? null;
$pre = val('SELECT pre_level FROM enrolments WHERE class_id = ? AND learner_id = ?', [$c['id'], $lid]);
$lvB = $ap > 0 ? (levels_at((int)$c['id'], $ap - 1)[$lid] ?? null) : $pre;
echo json_encode(['ok' => 1, 'level' => level_label($lv), 'area' => $lv ? LEVELS[$lv][2] : '', 'before' => level_label($lvB)]);
