<?php
need('division');
$keys = ['division_name' => 'Division name', 'region_name' => 'Region', 'school_year' => 'Current school year'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') { foreach ($keys as $k => $_) { $v = trim((string)($_POST[$k] ?? '')); if ($k !== 'school_year' || in_array($v, school_years(), true)) q('REPLACE INTO settings (k, v) VALUES (?, ?)', [$k, $v]); } flash('Settings saved.'); redirect('settings'); }
render('settings', ['title' => 'Settings', 'keys' => $keys]);
