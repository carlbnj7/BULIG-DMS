<?php
need('division');
$keys = ['division_name' => 'Division name', 'region_name' => 'Region', 'school_year' => 'Current school year'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'cycles') {
    $out = []; $err = '';
    foreach ((array)($_POST['cname'] ?? []) as $i => $n) {
        $n = trim((string)$n); $f = (int)($_POST['cfrom'][$i] ?? 0); $t = (int)($_POST['cto'][$i] ?? 0);
        if ($n === '' && !$f && !$t) continue;
        if ($n === '' || $f < 1 || $t > AP_COUNT || $f > $t) { $err = 'Each cycle needs a name and an AP range from AP 1 to AP ' . AP_COUNT . ' (from must not be after to).'; break; }
        foreach ($out as $o) if ($f <= $o['to'] && $t >= $o['from']) { $err = mb_substr($n, 0, 40) . ' overlaps ' . $o['name'] . '. Each AP can belong to only one cycle.'; break 2; }
        $out[] = ['name' => mb_substr($n, 0, 40), 'from' => $f, 'to' => $t];
    }
    if ($err) flash($err, 'err');
    else { usort($out, fn($a, $b) => $a['from'] <=> $b['from']); q('REPLACE INTO settings (k, v) VALUES ("cycles", ?)', [json_encode($out)]); flash(count($out) . ' cycle' . (count($out) === 1 ? '' : 's') . ' saved.'); }
    redirect('settings');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') { foreach ($keys as $k => $_) { $v = trim((string)($_POST[$k] ?? '')); if ($k !== 'school_year' || in_array($v, school_years(), true)) q('REPLACE INTO settings (k, v) VALUES (?, ?)', [$k, $v]); } flash('Settings saved.'); redirect('settings'); }
render('settings', ['title' => 'Settings', 'keys' => $keys]);
