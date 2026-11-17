<?php
$u = need();
$needPw = (bool)$u['must_change']; $needUn = !$u['setup_done'];
if (!$needPw && !$needUn) redirect('home');
$err = '';
$oldUn = preg_match('/^(DIV|MUN|DIS|SCH|TCH)\d/i', $u['username']) ? '' : $u['username'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $un = strtolower(trim((string)($_POST['username'] ?? ''))); $cur = (string)($_POST['current'] ?? '');
    $n1 = (string)($_POST['new1'] ?? ''); $n2 = (string)($_POST['new2'] ?? ''); $oldUn = $un;
    if (!password_verify($cur, $u['password_hash'])) $err = $needPw ? 'The password you were given is wrong.' : 'Your password is wrong.';
    elseif ($needUn && ($e = username_problem($un, (int)$u['id'])) !== '') $err = $e;
    elseif ($needPw && mb_strlen($n1) < 8) $err = 'Your new password needs at least 8 characters.';
    elseif ($needPw && $n1 !== $n2) $err = 'The two new passwords are not the same.';
    elseif ($needPw && $n1 === $cur) $err = 'Pick a new password that is different from the one you were given.';
    else {
        if ($needPw) q('UPDATE users SET password_hash = ?, must_change = 0 WHERE id = ?', [password_hash($n1, PASSWORD_DEFAULT), $u['id']]);
        if ($needUn) q('UPDATE users SET username = ?, setup_done = 1 WHERE id = ?', [$un, $u['id']]);
        session_regenerate_id(true);
        flash('All set! Next time, sign in with your username or your ID ' . $u['user_code'] . '.');
        redirect('home');
    }
}
render('setup', ['title' => 'Set up your account', 'err' => $err, 'needPw' => $needPw, 'needUn' => $needUn, 'oldUn' => $oldUn]);
