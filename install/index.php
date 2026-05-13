<?php
/**
 * Wotan3 — Web Installer
 * Browse to /install/ to set up your site.
 * DELETE or rename this directory after installation.
 */

define('WEBROOT',  dirname(__DIR__));
define('CONFIG',   WEBROOT . '/share/settings/config.php');
define('SCHEMA',   __DIR__ . '/schema.sql');

session_start();
if (!isset($_SESSION['install'])) $_SESSION['install'] = [];
$data = &$_SESSION['install'];

$step  = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$error = '';

// ── Already installed? ──────────────────────────────────────────────────────
if (file_exists(CONFIG) && $step < 5) {
    $step = 0; // show "already installed" screen
}

// ── Step handlers ───────────────────────────────────────────────────────────

// Step 2 → validate DB + write to session
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['db_host'] = trim($_POST['db_host'] ?? 'localhost');
    $data['db_port'] = trim($_POST['db_port'] ?? '3306');
    $data['db_user'] = trim($_POST['db_user'] ?? '');
    $data['db_pass'] = $_POST['db_pass'] ?? '';
    $data['db_name'] = trim($_POST['db_name'] ?? 'wotan3');

    if (!$data['db_user']) { $error = 'Database username is required.'; $step = 1; }
    if (!$data['db_name']) { $error = 'Database name is required.';     $step = 1; }

    if (!$error) {
        $conn = @mysqli_connect($data['db_host'], $data['db_user'], $data['db_pass'], '', (int)$data['db_port']);
        if (!$conn) {
            $error = 'Cannot connect to MySQL: ' . mysqli_connect_error();
            $step = 1;
        } else {
            mysqli_close($conn);
        }
    }
}

// Step 3 → validate site config
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['domain']      = trim($_POST['domain']      ?? '');
    $data['company']     = trim($_POST['company']     ?? '');
    $data['admin_email'] = trim($_POST['admin_email'] ?? '');
    $data['webmaster']   = trim($_POST['webmaster']   ?? '');

    if (!$data['domain'])  { $error = 'Domain name is required.';  $step = 2; }
    if (!$data['company']) { $error = 'Company name is required.'; $step = 2; }
}

// Step 4 → validate users
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = [];
    $names = $_POST['u_username'] ?? [];
    foreach ($names as $i => $username) {
        $username = trim($username);
        if (!$username) continue;
        $email     = trim($_POST['u_email'][$i]     ?? '');
        $group     = trim($_POST['u_usergroup'][$i] ?? 'members');
        $pass      = $_POST['u_password'][$i]       ?? '';
        $pass2     = $_POST['u_password2'][$i]      ?? '';
        if (strlen($pass) < 8)     { $error = "Password for '$username' must be at least 8 characters."; $step = 3; break; }
        if ($pass !== $pass2)      { $error = "Passwords for '$username' do not match.";                 $step = 3; break; }
        $users[] = compact('username', 'email', 'group', 'pass');
    }
    if (!$error && empty($users)) { $error = 'At least one user is required.'; $step = 3; }
    if (!$error) $data['users'] = $users;
}

// Step 5 → run install
if ($step === 5 && !empty($data['db_host']) && !empty($data['users'])) {
    $log = [];
    $ok  = true;

    try {
        // Connect
        $conn = mysqli_connect($data['db_host'], $data['db_user'], $data['db_pass'], '', (int)$data['db_port']);
        if (!$conn) throw new RuntimeException('MySQL connect failed: ' . mysqli_connect_error());
        mysqli_set_charset($conn, 'utf8mb4');

        // Create DB
        $db = mysqli_real_escape_string($conn, $data['db_name']);
        mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (mysqli_error($conn)) throw new RuntimeException('Create DB: ' . mysqli_error($conn));
        mysqli_select_db($conn, $data['db_name']);
        $log[] = '✓ Database <b>' . htmlspecialchars($data['db_name']) . '</b> ready.';

        // Run schema
        $sql = file_get_contents(SCHEMA);
        foreach (array_filter(explode(';', $sql)) as $q) {
            $q = trim($q);
            if ($q) {
                mysqli_query($conn, $q);
                if (mysqli_error($conn) && !str_contains(mysqli_error($conn), 'already exists'))
                    throw new RuntimeException('Schema: ' . mysqli_error($conn) . ' — ' . substr($q, 0, 80));
            }
        }
        $log[] = '✓ Tables created.';

        // Create users
        foreach ($data['users'] as $u) {
            $hash  = password_hash($u['pass'], PASSWORD_BCRYPT);
            $uname = mysqli_real_escape_string($conn, $u['username']);
            $email = mysqli_real_escape_string($conn, $u['email']);
            $grp   = mysqli_real_escape_string($conn, $u['group']);
            $hash  = mysqli_real_escape_string($conn, $hash);

            switch ($u['group']) {
                case 'admin':
                    $adm = '/admin/*'; $acc = '/'; $stt = 'admin.php'; $hom = '/admin/info/'; break;
                case 'editor': case 'manage':
                    $adm = '/admin/*'; $acc = '/'; $stt = 'admin.php'; $hom = '/admin/info/'; break;
                default:
                    $adm = '';        $acc = '/'; $stt = 'guests.php'; $hom = '/';            break;
            }

            mysqli_query($conn,
                "INSERT INTO users (username,email,password,hpassword,usergroup,admin,access,settings,home,online)
                 VALUES ('$uname','$email','*','$hash','$grp','$adm','$acc','$stt','$hom',1)"
            );
            if (mysqli_error($conn)) throw new RuntimeException('Insert user: ' . mysqli_error($conn));
            $log[] = '✓ User <b>' . htmlspecialchars($u['username']) . '</b> (' . htmlspecialchars($u['group']) . ') created.';
        }

        mysqli_close($conn);

        // Write config.php
        $domain    = addslashes($data['domain']);
        $company   = addslashes($data['company']);
        $admemail  = addslashes($data['admin_email'] ?: 'admin@' . $data['domain']);
        $wmaster   = addslashes($data['webmaster']);
        $dbhost    = addslashes($data['db_host']);
        $dbuser    = addslashes($data['db_user']);
        $dbpass    = addslashes($data['db_pass']);
        $dbname    = addslashes($data['db_name']);

        $cfg = <<<PHP
<?php
// Wotan3 Configuration — generated by web installer on {$_SERVER['HTTP_HOST']} · } . date('Y-m-d H:i:s') . {
// Do NOT commit this file to version control.

\$mysql['host']     = '$dbhost';
\$mysql['user']     = '$dbuser';
\$mysql['pass']     = '$dbpass';
\$mysql['selectdb'] = '$dbname';

\$echo['company_name'] = '$company';
\$domainname           = '$domain';

\$webmastername = '$wmaster';
\$webmasterip   = \$webmastername ? gethostbyname(\$webmastername) : '';
\$forwarded_ip  = '';
if (!empty(\$_SERVER['HTTP_X_FORWARDED_FOR'])) {
    \$forwarded_ip = trim(explode(',', \$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}
\$visitor_ips     = array_filter(array(\$_SERVER['REMOTE_ADDR'] ?? '', \$forwarded_ip));
\$is_webmaster_ip = \$webmastername && in_array(\$webmasterip, \$visitor_ips, true);

if (\$is_webmaster_ip) { error_reporting(E_ALL); ini_set('display_errors',1); ini_set('log_errors',0); }
else                   { error_reporting(E_ALL); ini_set('display_errors',0); ini_set('log_errors',1); }

\$webmastereml = 'admin@$domain';
\$clientmail   = '$admemail';
\$analytics_ID = '';
\$languages    = 'nl, en';

if (!isset(\$get['lan']) || !\$get['lan']) \$get['lan'] = \$_GET['lan'] = 'english';

\$protocol   = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
\$websiteurl = \$echo['websiteurl'] = \$protocol . \$domainname;
PHP;
        // Fix the date() call that can't be in heredoc directly
        $cfg = str_replace(
            "generated by web installer on {$_SERVER['HTTP_HOST']} · } . date('Y-m-d H:i:s') . {",
            'generated by web installer on ' . $_SERVER['HTTP_HOST'] . ' · ' . date('Y-m-d H:i:s'),
            $cfg
        );

        if (!file_put_contents(CONFIG, $cfg))
            throw new RuntimeException('Could not write config.php — check directory permissions.');
        chmod(CONFIG, 0640);
        $log[] = '✓ <b>config.php</b> written.';

        // File permissions
        foreach (['share/tmp', 'share/log'] as $dir) {
            $path = WEBROOT . '/' . $dir;
            if (!is_dir($path)) mkdir($path, 0770, true);
            chmod($path, 0770);
        }
        $log[] = '✓ <b>share/tmp/</b> and <b>share/log/</b> set to 770.';

    } catch (RuntimeException $e) {
        $ok    = false;
        $error = $e->getMessage();
    }
}

// ── HTML ────────────────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Wotan3 Installer</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
body   { font: 15px/1.6 system-ui, sans-serif; background: #f0f2f5; color: #222; padding: 30px 16px }
.wrap  { max-width: 620px; margin: 0 auto }
h1     { font-size: 1.5rem; margin-bottom: 4px }
.sub   { color: #666; margin-bottom: 28px; font-size: .9rem }
.card  { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 32px }
.steps { display: flex; gap: 0; margin-bottom: 28px; border-radius: 6px; overflow: hidden }
.steps span { flex: 1; text-align: center; padding: 8px 4px; font-size: .8rem; background: #e8eaed; color: #888 }
.steps span.done   { background: #c8f0d8; color: #1a7f4b }
.steps span.active { background: #1a73e8; color: #fff; font-weight: 600 }
label  { display: block; font-weight: 600; font-size: .85rem; margin: 18px 0 5px }
input[type=text], input[type=email], input[type=password], input[type=number], select {
    width: 100%; padding: 9px 12px; border: 1px solid #ccc; border-radius: 5px; font-size: .95rem }
input:focus, select:focus { outline: none; border-color: #1a73e8; box-shadow: 0 0 0 3px rgba(26,115,232,.15) }
.hint  { font-size: .8rem; color: #777; margin-top: 3px }
.btn   { display: inline-block; margin-top: 24px; padding: 10px 28px; background: #1a73e8;
         color: #fff; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer }
.btn:hover { background: #1558b0 }
.btn-sec { background: #e8eaed; color: #333 }
.btn-sec:hover { background: #d0d3d8 }
.error { background: #fde8e8; border: 1px solid #f5c6c6; border-radius: 5px; padding: 12px 16px; margin-bottom: 20px; color: #a00 }
.ok    { background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 5px; padding: 12px 16px; margin-bottom: 12px; color: #1a6e2e }
.user-block { border: 1px solid #e0e0e0; border-radius: 6px; padding: 18px; margin-top: 16px; position: relative }
.user-block h3 { font-size: .9rem; color: #555; margin-bottom: 4px }
.remove-user { position: absolute; top: 12px; right: 14px; background: none; border: none;
               font-size: 1.1rem; cursor: pointer; color: #aaa }
.remove-user:hover { color: #c00 }
.add-user { background: none; border: 1px dashed #aaa; border-radius: 5px; width: 100%;
            padding: 10px; margin-top: 12px; cursor: pointer; color: #555; font-size: .9rem }
.add-user:hover { border-color: #1a73e8; color: #1a73e8 }
.done-icon { font-size: 3rem; text-align: center; margin-bottom: 12px }
.warn  { background: #fff8e1; border: 1px solid #ffe082; border-radius: 5px; padding: 12px 16px; margin-top: 16px; font-size: .88rem; color: #795548 }
.req   { color: #c00 }
.two   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
@media (max-width: 480px) { .two { grid-template-columns: 1fr } }
</style>
</head>
<body>
<div class="wrap">
<h1>Wotan3 Installer</h1>
<p class="sub">Set up your Wotan3 site in a few steps.</p>

<?php if ($step === 0): ?>
<!-- Already installed -->
<div class="card">
  <div style="text-align:center;padding:20px 0">
    <div style="font-size:2.5rem">🔒</div>
    <h2 style="margin:12px 0 8px">Already installed</h2>
    <p style="color:#555"><code>share/settings/config.php</code> already exists.<br>
    Delete or rename the <code>install/</code> directory to proceed.</p>
    <a href="/" style="display:inline-block;margin-top:20px;color:#1a73e8">← Go to site</a>
  </div>
</div>

<?php elseif ($step === 5 && isset($ok)): ?>
<!-- Step 5: Result -->
<div class="card">
<?php if ($ok): ?>
  <div class="done-icon">✅</div>
  <h2 style="text-align:center;margin-bottom:20px">Installation complete!</h2>
  <?php foreach ($log as $line): ?><div class="ok"><?= $line ?></div><?php endforeach ?>
  <div class="warn">
    <strong>Security:</strong> Delete or rename the <code>install/</code> directory now —
    leaving it accessible is a security risk.
    <pre style="margin-top:6px;font-size:.85rem">rm -rf <?= htmlspecialchars(WEBROOT) ?>/install/</pre>
  </div>
  <a href="/" class="btn" style="display:block;text-align:center;text-decoration:none;margin-top:20px">Go to site →</a>
<?php else: ?>
  <div class="error"><strong>Installation failed:</strong><br><?= htmlspecialchars($error) ?></div>
  <p style="margin-top:12px">Please fix the error and <a href="?step=1">start over</a>.</p>
<?php endif ?>
</div>

<?php else: ?>
<!-- Steps 1–4 -->
<div class="steps">
  <?php
  $labels = ['1 Requirements', '2 Database', '3 Site', '4 Users'];
  for ($i = 1; $i <= 4; $i++) {
      $cls = $i < $step ? 'done' : ($i === $step ? 'active' : '');
      echo "<span class=\"$cls\">{$labels[$i-1]}</span>";
  }
  ?>
</div>

<div class="card">
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif ?>

<?php if ($step === 1): ?>
<!-- Step 1: Requirements -->
<?php
$checks = [
    'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' (8.0+ required)' => PHP_MAJOR_VERSION >= 8,
    'MySQLi extension'                  => extension_loaded('mysqli'),
    'GD extension'                      => extension_loaded('gd'),
    'share/settings/ writable'          => is_writable(WEBROOT . '/share/settings'),
    'share/tmp/ exists or creatable'    => is_dir(WEBROOT . '/share/tmp') || is_writable(WEBROOT . '/share'),
    'share/log/ exists or creatable'    => is_dir(WEBROOT . '/share/log') || is_writable(WEBROOT . '/share'),
];
$allOk = !in_array(false, $checks, true);
?>
<h2 style="margin-bottom:16px">Requirements</h2>
<table style="width:100%;border-collapse:collapse">
<?php foreach ($checks as $label => $pass): ?>
<tr>
  <td style="padding:7px 0;border-bottom:1px solid #f0f0f0"><?= htmlspecialchars($label) ?></td>
  <td style="padding:7px 0;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:600;color:<?= $pass ? '#1a7f4b' : '#c00' ?>"><?= $pass ? '✓ OK' : '✗ Fail' ?></td>
</tr>
<?php endforeach ?>
</table>
<?php if (!$allOk): ?>
  <div class="error" style="margin-top:16px">Fix the failed requirements before continuing.</div>
<?php else: ?>
  <form method="post" action="">
    <input type="hidden" name="step" value="2">
    <button type="submit" class="btn">Continue →</button>
  </form>
<?php endif ?>

<?php elseif ($step === 2): ?>
<!-- Step 2: Database -->
<h2 style="margin-bottom:4px">Database</h2>
<p class="hint" style="margin-bottom:16px">The database will be created if it doesn't exist yet.</p>
<form method="post" action="">
  <input type="hidden" name="step" value="3">
  <div class="two">
    <div>
      <label>Host <span class="req">*</span></label>
      <input type="text" name="db_host" value="<?= htmlspecialchars($data['db_host'] ?? 'localhost') ?>" required>
    </div>
    <div>
      <label>Port</label>
      <input type="number" name="db_port" value="<?= htmlspecialchars($data['db_port'] ?? '3306') ?>">
    </div>
  </div>
  <label>Username <span class="req">*</span></label>
  <input type="text" name="db_user" value="<?= htmlspecialchars($data['db_user'] ?? '') ?>" required>
  <label>Password</label>
  <input type="password" name="db_pass" value="<?= htmlspecialchars($data['db_pass'] ?? '') ?>">
  <div class="hint">Leave blank if your MySQL user has no password.</div>
  <label>Database name <span class="req">*</span></label>
  <input type="text" name="db_name" value="<?= htmlspecialchars($data['db_name'] ?? 'wotan3') ?>" required>
  <button type="submit" class="btn">Continue →</button>
</form>

<?php elseif ($step === 3): ?>
<!-- Step 3: Site config -->
<h2 style="margin-bottom:16px">Site configuration</h2>
<form method="post" action="">
  <input type="hidden" name="step" value="4">
  <label>Domain name <span class="req">*</span></label>
  <input type="text" name="domain" placeholder="example.com" value="<?= htmlspecialchars($data['domain'] ?? $_SERVER['HTTP_HOST']) ?>" required>
  <label>Company / site name <span class="req">*</span></label>
  <input type="text" name="company" placeholder="My Company" value="<?= htmlspecialchars($data['company'] ?? '') ?>" required>
  <label>Admin e-mail</label>
  <input type="email" name="admin_email" placeholder="admin@example.com" value="<?= htmlspecialchars($data['admin_email'] ?? '') ?>">
  <label>Webmaster hostname <span style="font-weight:400;color:#777">(optional)</span></label>
  <input type="text" name="webmaster" placeholder="office.example.com" value="<?= htmlspecialchars($data['webmaster'] ?? '') ?>">
  <div class="hint">Visitors from this hostname see PHP errors. Leave blank to disable.</div>
  <button type="submit" class="btn">Continue →</button>
</form>

<?php elseif ($step === 4): ?>
<!-- Step 4: Users -->
<h2 style="margin-bottom:4px">Users</h2>
<p class="hint" style="margin-bottom:8px">Add at least one admin user. Passwords must be at least 8 characters.</p>
<form method="post" action="" id="userForm">
  <input type="hidden" name="step" value="5">
  <div id="users">
    <div class="user-block" id="user-0">
      <h3>User 1</h3>
      <label>Username <span class="req">*</span></label>
      <input type="text" name="u_username[]" required>
      <label>E-mail</label>
      <input type="email" name="u_email[]">
      <label>Usergroup</label>
      <select name="u_usergroup[]">
        <option value="admin">admin</option>
        <option value="editor">editor</option>
        <option value="manage">manage</option>
        <option value="members">members</option>
      </select>
      <div class="two">
        <div>
          <label>Password <span class="req">*</span></label>
          <input type="password" name="u_password[]" required minlength="8">
        </div>
        <div>
          <label>Confirm password <span class="req">*</span></label>
          <input type="password" name="u_password2[]" required minlength="8">
        </div>
      </div>
    </div>
  </div>
  <button type="button" class="add-user" onclick="addUser()">+ Add another user</button>
  <button type="submit" class="btn">Install →</button>
</form>
<script>
var count = 1;
function addUser() {
    count++;
    var tpl = document.getElementById('user-0').cloneNode(true);
    tpl.id = 'user-' + count;
    tpl.querySelector('h3').textContent = 'User ' + count;
    tpl.querySelectorAll('input').forEach(function(i){ i.value=''; });
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'remove-user'; btn.textContent = '✕';
    btn.onclick = function(){ tpl.remove(); renumber(); };
    tpl.appendChild(btn);
    document.getElementById('users').appendChild(tpl);
}
function renumber() {
    document.querySelectorAll('.user-block').forEach(function(b, i){
        b.querySelector('h3').textContent = 'User ' + (i+1);
    });
}
</script>

<?php endif ?>
</div><!-- .card -->
<?php endif ?>

</div><!-- .wrap -->
</body>
</html>
