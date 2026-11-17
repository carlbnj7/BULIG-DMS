<?php
$u = me();
$bare = $bare ?? !$u;
$nav = []; $navGroups = [];
if ($u) {
    $t = $u['role'] === 'teacher';
    $navGroups[''] = [['home', 'Dashboard', 'home']];
    $navGroups[$t ? 'My class' : 'Monitoring'] = $t
        ? [['classes', 'My classes', 'users'], ['upload', 'Upload SF1', 'upload'], ['report', 'Reports', 'print']]
        : [['classes', 'Classes', 'users'], ['report', 'Reports', 'print'], ['duplicates', 'Duplicates', 'refresh']];
    if (!$t) {
        $m = [['users', 'Accounts', 'user']];
        if (in_array($u['role'], ['division', 'municipal', 'district'], true)) $m[] = ['schools', 'Schools', 'school'];
        if ($u['role'] === 'division') { $m[] = ['places', 'Municipalities', 'map']; $m[] = ['settings', 'Settings', 'edit']; }
        $navGroups['Manage'] = $m;
    }
    $navGroups['Account'] = [['account', 'My account', 'key']];
    foreach ($navGroups as $g) foreach ($g as $x) $nav[] = $x;
}
$cur = $_GET['p'] ?? 'home';
?><!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b6b34">
<title><?= e(($title ?? 'Home') . ' · BULIG-DMS') ?></title>
<link rel="icon" href="assets/img/icons/favicon-64.png" type="image/png">
<link rel="apple-touch-icon" href="assets/img/icons/apple-touch-icon.png">
<link rel="manifest" href="manifest.webmanifest">
<link rel="stylesheet" href="assets/css/app.css?v=3">
<script src="assets/js/theme.js?v=2"></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>" data-csrf="<?= e($_SESSION['csrf'] ?? '') ?>">
<?php if ($bare): require $view_file; else: ?>
<div class="shell">
  <aside class="side noprint">
    <a class="brand" href="<?= url('home') ?>"><img src="assets/img/bulig-logo.png" alt="BULIG" width="220" height="88"><span>DATA AND MONITORING SYSTEM</span></a>
    <label class="side-search"><?= icon('search', 17) ?><input type="search" placeholder="Search" aria-label="Search the menu" data-navsearch></label>
    <nav>
      <?php foreach ($navGroups as $gname => $items): ?><?php if ($gname): ?><p class="nav-h"><?= e(strtoupper($gname)) ?></p><?php endif; ?>
        <?php foreach ($items as [$p, $label, $ic]): ?><a href="<?= url($p) ?>" class="<?= $cur === $p ? 'on' : '' ?>" data-navitem><span class="ni"><?= icon($ic, 18) ?></span><span><?= e($label) ?></span></a><?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <img class="seal" src="assets/img/division.png" alt="Division of Bukidnon" width="40" height="40"><small><b><?= e(full_name($u)) ?></b><?= e(role_name($u['role'])) ?></small>
      <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?><button class="linkish"><?= icon('logout', 18) ?><span>Sign out</span></button></form>
    </div>
  </aside>
  <div class="main">
    <header class="top noprint">
      <a class="brand sm" href="<?= url('home') ?>"><img src="assets/img/bulig-logo.png" alt="BULIG" width="92" height="45"></a>
      <div class="top-title"><?= e($title ?? '') ?></div>
      <div class="who"><span class="av"><?= e(mb_strtoupper(mb_substr($u['first_name'] ?: '?', 0, 1))) ?></span><span class="who-t"><b><?= e(full_name($u)) ?></b><small><?= e(role_name($u['role'])) ?> · <?= e(scope_label($u)) ?></small></span></div>
    </header>
    <main class="content">
      <?php foreach (flash() as [$t, $m]): ?><div class="flash <?= e($t) ?> noprint"><?= e($m) ?></div><?php endforeach; ?>
      <?php require $view_file; ?>
    </main>
  </div>
  <nav class="tabbar noprint">
    <?php foreach (array_slice($nav, 0, 5) as [$p, $label, $ic]): ?><a href="<?= url($p) ?>" class="<?= $cur === $p ? 'on' : '' ?>"><?= icon($ic, 22) ?><span><?= e($label) ?></span></a><?php endforeach; ?>
  </nav>
</div>
<?php endif; ?>
<?php foreach ($scripts ?? [] as $js): ?><script src="assets/js/<?= e($js) ?>?v=1"></script><?php endforeach; ?>
</body>
</html>
