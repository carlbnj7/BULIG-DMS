<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$cfgFile = ROOT . '/config/config.php';
if (!is_file($cfgFile)) {
    http_response_code(500);
    exit('BULIG-DMS is not set up yet. Copy config/config.sample.php to config/config.php and add your database details.');
}
$CFG = require $cfgFile;
foreach (['db_host' => 'BULIGDMS_DB_HOST', 'db_name' => 'BULIGDMS_DB_NAME', 'db_user' => 'BULIGDMS_DB_USER', 'db_pass' => 'BULIGDMS_DB_PASS'] as $k => $env) {
    if (getenv($env) !== false) $CFG[$k] = getenv($env);
}
date_default_timezone_set($CFG['timezone'] ?? 'Asia/Manila');
ini_set('display_errors', !empty($CFG['debug']) ? '1' : '0');
error_reporting(E_ALL);

const APP_NAME = 'BULIG-DMS';
const APP_FULL_NAME = 'BULIG Data and Monitoring System';
const BULIG_FULL = "Bukidnon's Unified Literacy and Intervention Gateway";
/** BULIG reading levels: code => [label, component name, reading area]. */
const LEVELS = [
    'L1'  => ['Level 1',  'Orientation to Print',     'Oral Language'],
    'L2A' => ['Level 2A', 'Letter and Initial Sound', 'Phonological Awareness'],
    'L2B' => ['Level 2B', 'Letter and Initial Sound', 'Phonological Awareness'],
    'L3A' => ['Level 3A', 'Letter Name Knowledge',    'Word Recognition'],
    'L3B' => ['Level 3B', 'Letter Name Knowledge',    'Word Recognition'],
    'L4'  => ['Level 4',  'Invented Word Reading',    'Fluency'],
    'L5'  => ['Level 5',  'Familiar Word Reading',    'Vocabulary and Listening Comprehension'],
    'L6'  => ['Level 6',  'Listening Comprehension',  'Graded Reading Comprehension'],
    'L7'  => ['Level 7',  'Dictation',                'Genuine Love for Reading and Writing'],
];
const KEY_STAGES = [1 => 'Key Stage 1', 2 => 'Key Stage 2', 3 => 'Key Stage 3', 4 => 'Key Stage 4'];
const PROFILES = [
    1 => ['Low Emerging', 'High Emerging', 'Developing', 'Transitioning', 'Grade Level Ready'],
    2 => ['Frustration', 'Instructional', 'Independent'],
    3 => ['Frustration', 'Instructional', 'Independent'],
    4 => ['Frustration', 'Instructional', 'Independent'],
];
const PERIODS = ['bosy' => 'BOSY', 'mosy' => 'MOSY', 'eosy' => 'EOSY'];
const AP_COUNT = 15;
const TERMS = ['1ST', '2ND', '3RD', '4TH', 'SUMMER'];
const TOOLS = ['CRLA', 'PHIL-IRI'];
/** Roles from the top down: code => [label, ID prefix, what scope_id points to]. */
const ROLES = [
    'division'  => ['Division Admin', 'DIV', ''],
    'municipal' => ['Municipal Coordinator', 'MUN', 'municipality'],
    'district'  => ['District Supervisor', 'DIS', 'district'],
    'school'    => ['School Head / Reading Coordinator', 'SCH', 'school'],
    'teacher'   => ['Teacher', 'TCH', 'school'],
];

function db(): PDO {
    static $pdo = null;
    global $CFG;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . $CFG['db_host'] . ';dbname=' . $CFG['db_name'] . ';charset=utf8mb4', $CFG['db_user'], $CFG['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+08:00'");
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function one(string $sql, array $p = []) { return q($sql, $p)->fetch(); }
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { return q($sql, $p)->fetchColumn(); }

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $page, array $args = []): string {
    return 'index.php?' . http_build_query(['p' => $page] + $args);
}
function redirect(string $page, array $args = []): void { header('Location: ' . url($page, $args)); exit; }
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function setting(string $k, string $def = ''): string {
    static $c = null;
    if ($c === null) { $c = []; foreach (all('SELECT k, v FROM settings') as $r) $c[$r['k']] = $r['v']; }
    return $c[$k] ?? $def;
}

// ---------- session, CSRF ----------
function start_session(): void {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('buligdms');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
function check_csrf(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(419);
        exit('Your session expired. Please go back and try again.');
    }
}

// ---------- auth ----------
function me(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = one('SELECT * FROM users WHERE id = ? AND active = 1', [$_SESSION['uid']]) ?: null;
            if (!$u) unset($_SESSION['uid']);
        }
    }
    return $u;
}
function need(string ...$roles): array {
    $u = me();
    if (!$u) redirect('login');
    if ($roles && !in_array($u['role'], $roles, true)) { http_response_code(403); render('error', ['title' => 'Not allowed', 'msg' => 'This page is not for your account.']); exit; }
    return $u;
}
function full_name(array $u): string {
    $m = trim($u['middle_name'] ?? '');
    return trim($u['first_name'] . ' ' . ($m !== '' ? mb_substr($m, 0, 1) . '. ' : '') . $u['last_name']);
}
function teacher_clc_ids(int $tid): array {
    return array_map('intval', array_column(all('SELECT clc_id FROM teacher_clc WHERE teacher_id = ?', [$tid]), 'clc_id'));
}
function can_manage_learner(array $u, array $learner): bool {
    if ($learner['role'] !== 'learner') return false;
    if ($u['role'] === 'admin') return true;
    return $u['role'] === 'teacher' && in_array((int)$learner['clc_id'], teacher_clc_ids((int)$u['id']), true);
}

// ---------- views ----------
function render(string $view, array $vars = []): void {
    extract($vars);
    $view_file = ROOT . '/app/views/' . $view . '.php';
    require ROOT . '/app/views/layout.php';
}
function icon(string $n, int $s = 20): string {
    $p = [
        'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5"/><path d="M8 7h7"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 10h8"/>',
        'flask' => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/><path d="M7.5 15h9"/>',
        'calc' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h2M14 12h2M8 16h2M14 16h2"/>',
        'brief' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'people' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14a5 5 0 0 1 5 6"/>',
        'laptop' => '<rect x="4" y="5" width="16" height="11" rx="1.5"/><path d="M2 19h20"/>',
        'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'trophy' => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8 21h8M9 17h6"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14L7 22l5-3 5 3-1.5-8"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'school' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/>',
        'map' => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13 7l4 4"/>',
        'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
        'download' => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M15 4h4v16h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'check' => '<path d="M5 12l5 5L20 7"/>',
        'x' => '<path d="M18 6L6 18M6 6l12 12"/>',
        'heart' => '<path d="M12 20s-7-4.5-9-9a4.5 4.5 0 0 1 9-2 4.5 4.5 0 0 1 9 2c-2 4.5-9 9-9 9z"/>',
        'bulb' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-4 10.5c.7.7 1 1.5 1 2.5h6c0-1 .3-1.8 1-2.5A6 6 0 0 0 12 3z"/>',
        'flame' => '<path d="M12 3c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2-4.5 0 2 1 3 2 3 0-3-1-5.5 1-8.5z"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'play' => '<path d="M7 4l13 8-13 8z"/>',
        'print' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'game' => '<rect x="2.5" y="7" width="19" height="11" rx="5"/><path d="M7.5 10.5v4M5.5 12.5h4"/><circle cx="15.5" cy="11" r=".6"/><circle cx="17.5" cy="14" r=".6"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h3M21 18v3h-1M17 17h.01"/>',
        'pin' => '<circle cx="6" cy="6" r="1.4"/><circle cx="12" cy="6" r="1.4"/><circle cx="18" cy="6" r="1.4"/><circle cx="6" cy="12" r="1.4"/><circle cx="12" cy="12" r="1.4"/><circle cx="18" cy="12" r="1.4"/><circle cx="12" cy="18" r="1.4"/>',
        'city' => '<path d="M3 21h18M5 21V8l5-3v16M10 21V3h9v18"/><path d="M13 7h3M13 11h3M13 15h3M7 11h1M7 15h1"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'zoom' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4M11 8v6M8 11h6"/>',
    ][$n] ?? '';
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function make_password(): string {
    $w = ['Mango', 'River', 'Sunny', 'Tiger', 'Eagle', 'Lemon', 'Cloud', 'Star', 'Coral', 'Maple', 'Pearl', 'Ocean', 'Bamboo', 'Falcon'];
    return $w[random_int(0, count($w) - 1)] . random_int(1000, 9999) . '!';
}


// ---------- accounts ----------
/** Give an account its short ID (TCH0001, SCH0001, DIS0001, MUN0001, DIV0001). The ID is also its first username. */
function assign_user_code(int $id, string $role): string {
    $pre = ROLES[$role][1];
    for ($i = 0; $i < 5; $i++) {
        $max = (int)val('SELECT MAX(CAST(SUBSTRING(user_code, 4) AS UNSIGNED)) FROM users WHERE user_code REGEXP ?', ['^' . $pre . '[0-9]{4}$']);
        $c = $pre . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
        try { q('UPDATE users SET user_code = ?, username = IF(setup_done = 1, username, ?) WHERE id = ?', [$c, $c, $id]); return $c; }
        catch (PDOException $ex) { if ($ex->getCode() !== '23000') throw $ex; }
    }
    throw new RuntimeException('Could not make an account ID.');
}
/** One time: change old long IDs (TCH2026…) to the short format. */
function renumber_codes_once(): void {
    if (setting('id_format') === '2') return;
    foreach (all("SELECT id, role FROM users WHERE user_code IS NULL OR user_code REGEXP '^(DIV|MUN|DIS|SCH|TCH)20[0-9]{6,}$' ORDER BY id") as $r) assign_user_code((int)$r['id'], $r['role']);
    q("INSERT INTO settings (k, v) VALUES ('id_format', '2') ON DUPLICATE KEY UPDATE v = '2'");
}
function add_user(array $by, string $role, ?int $scope, array $d): array {
    $pw = make_password();
    q('INSERT INTO users (role, scope_id, username, password_hash, first_name, middle_name, last_name, sex, position, email, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
      [$role, $scope, 'new-' . bin2hex(random_bytes(8)), password_hash($pw, PASSWORD_DEFAULT), $d['first_name'], $d['middle_name'] ?? '', $d['last_name'], $d['sex'] ?? '', $d['position'] ?? '', $d['email'] ?? '', $by['id']]);
    $id = (int)db()->lastInsertId();
    return [$id, assign_user_code($id, $role), $pw];
}
function username_problem(string $un, int $self): string {
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,29}$/', $un)) return 'Use 3 to 30 small letters, numbers, dot, dash or underscore. Start with a letter or number.';
    if (preg_match('/^(div|mun|dis|sch|tch)\d/', $un)) return 'That looks like an account ID. Pick a different username.';
    if (val('SELECT 1 FROM users WHERE (username = ? OR user_code = ?) AND id <> ?', [$un, $un, $self])) return 'That username is already taken. Try another one.';
    return '';
}
function role_name(string $r): string { return ROLES[$r][0] ?? ucfirst($r); }

// ---------- places and scope ----------
function municipalities(): array { return all('SELECT * FROM municipalities ORDER BY name'); }
function districts(): array { return all('SELECT d.*, m.name municipality FROM districts d JOIN municipalities m ON m.id = d.municipality_id ORDER BY m.name, d.name'); }
/** SQL condition limiting a schools alias to what this user may see. */
function school_scope_sql(array $u, string $s = 's'): string {
    $id = (int)$u['scope_id'];
    switch ($u['role']) {
        case 'division': return '1';
        case 'municipal': return "$s.district_id IN (SELECT id FROM districts WHERE municipality_id = $id)";
        case 'district': return "$s.district_id = $id";
        default: return "$s.id = $id";
    }
}
function visible_schools(array $u): array {
    return all('SELECT s.*, d.name district, m.name municipality, m.id municipality_id FROM schools s LEFT JOIN districts d ON d.id = s.district_id LEFT JOIN municipalities m ON m.id = d.municipality_id WHERE ' . school_scope_sql($u) . ' ORDER BY s.name');
}
/** Where an account belongs, in words. */
function scope_label(array $u): string {
    $id = (int)$u['scope_id'];
    switch ($u['role']) {
        case 'division': return setting('division_name', 'Division of Bukidnon');
        case 'municipal': return (string)(val('SELECT name FROM municipalities WHERE id = ?', [$id]) ?: '—');
        case 'district': return (string)(val('SELECT CONCAT(d.name, " · ", m.name) FROM districts d JOIN municipalities m ON m.id = d.municipality_id WHERE d.id = ?', [$id]) ?: '—');
        default: return (string)(val('SELECT name FROM schools WHERE id = ?', [$id]) ?: '—');
    }
}
/** Roles this user may make accounts for. */
function creatable_roles(array $u): array {
    $order = array_keys(ROLES); $i = array_search($u['role'], $order, true);
    if ($u['role'] === 'teacher') return [];
    return array_slice($order, $u['role'] === 'division' ? 0 : $i + 1);
}
/** Can this user change / see another account? Higher roles manage those inside their area. */
function can_manage_user(array $u, array $t): bool {
    if ((int)$t['id'] === (int)$u['id']) return false;
    if (!in_array($t['role'], creatable_roles($u), true)) return false;
    if ($u['role'] === 'division') return true;
    $sql = school_scope_sql($u);
    switch ($t['role']) {
        case 'district': return $u['role'] === 'municipal' && (bool)val('SELECT 1 FROM districts WHERE id = ? AND municipality_id = ?', [$t['scope_id'], $u['scope_id']]);
        case 'school': case 'teacher': return (bool)val("SELECT 1 FROM schools s WHERE s.id = ? AND $sql", [$t['scope_id']]);
    }
    return false;
}
function class_of(array $u, int $id): ?array {
    $c = one('SELECT c.*, s.name school, s.school_code, s.head_name, s.head_position, s.district_id, d.name district, m.name municipality, u.first_name, u.middle_name, u.last_name
      FROM classes c JOIN schools s ON s.id = c.school_id LEFT JOIN districts d ON d.id = s.district_id LEFT JOIN municipalities m ON m.id = d.municipality_id
      JOIN users u ON u.id = c.teacher_id WHERE c.id = ?', [$id]);
    if (!$c) return null;
    if ($u['role'] === 'teacher') return (int)$c['teacher_id'] === (int)$u['id'] ? $c : null;
    return val('SELECT 1 FROM schools s WHERE s.id = ? AND ' . school_scope_sql($u), [$c['school_id']]) ? $c : null;
}

// ---------- BULIG levels ----------
function level_label(?string $c): string { return $c && isset(LEVELS[$c]) ? LEVELS[$c][0] : ''; }
/** Level after a number of passed assessments, starting from the pre-assessment level. */
function level_after(?string $pre, int $passed): ?string {
    if (!$pre || !isset(LEVELS[$pre])) return null;
    $k = array_keys(LEVELS); $i = array_search($pre, $k, true);
    return $k[min(count($k) - 1, $i + $passed)];
}
/** Each learner's level after assessment period $ap (0 = pre-assessment). [learner_id => level code] */
function levels_at(int $class, int $ap): array {
    $out = [];
    foreach (all('SELECT e.learner_id, e.pre_level, (SELECT COUNT(*) FROM assessments a WHERE a.class_id = e.class_id AND a.learner_id = e.learner_id AND a.ap <= ? AND a.remark = "PASSED") passed
      FROM enrolments e WHERE e.class_id = ? AND e.active = 1', [$ap, $class]) as $r) $out[$r['learner_id']] = level_after($r['pre_level'], (int)$r['passed']);
    return $out;
}
/** Cycles set by the Division admin: [['name' => 'Cycle 1', 'from' => 1, 'to' => 5], ...] in AP order. */
function cycles(): array {
    $c = json_decode(setting('cycles', '[]'), true);
    return is_array($c) ? array_values(array_filter($c, fn($x) => isset($x['name'], $x['from'], $x['to']))) : [];
}
function cycle_of(int $ap): ?array { foreach (cycles() as $c) if ($ap >= $c['from'] && $ap <= $c['to']) return $c; return null; }
function school_years(): array { $y = (int)date('Y'); $o = []; for ($i = $y - 2; $i <= $y + 2; $i++) $o[] = $i . '-' . ($i + 1); return $o; }
function proper(string $s): string { return mb_convert_case(mb_strtolower(trim(preg_replace('/\s+/', ' ', $s))), MB_CASE_TITLE, 'UTF-8'); }
