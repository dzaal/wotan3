<?php
// *** Wotan3 — Admin Settings
// Loaded for all /admin/* pages.

if (!pwcd('/admin/*')) { $pwcd = 0; require_once(pwsp('/share/settings/guests.php')); return; }

$loaded['settings'] = 'admin.php';
include __DIR__ . '/config.php';

// *** Language
if (!isset($get['lan'])) $htmlfile = 'english.xhtml'; else $htmlfile = "{$get['lan']}.xhtml";
if (isset($get['htmlfile'])) $htmlfile = preg_replace('/[^a-z0-9_-]/i', '', $get['htmlfile']) . '.xhtml';

// *** Load core modules
wotan('var_conv2');
wotan('mysql3/connect'); if ($obj['connect']->errors) { echo $obj['connect']->errors; exit; }
mysqli_set_charset($GLOBALS['_mysqli'], 'utf8mb4');
wotan('basicfunc');
wotan('chkusr3/chkusr');
if ($logederror) pw_systemlog('admin.php errorfile', 'PHP error', $logederror);
$set['systemlogdays'] = 31;

$result['translate'] = pw_dbtoarray("SELECT * FROM translations", '*', $nfield = 'name_org');

$domainname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.') + 1, 999);
$mail['header'] = "Received: from {$_SERVER['HTTP_HOST']}\r\n"
    . "Message-ID: <" . date('Ymdhis') . "." . rand(100, 999) . ">\r\n"
    . "From: website@$domainname\r\n"
    . "Reply-to: info@$domainname\r\n"
    . "Return-Path: mailerror@$domainname\r\n"
    . "Errors-To: mailerror@$domainname\r\n"
    . "X-Mailer: Wotan3\r\n";

// *** Admin access level
$echo['adminaccess'] = ($ses['chkaccess']['usergroup'] === 'admin') ? 'inline' : 'none';

// *** Redirect bare /admin/ to dashboard
if ($REDIRECT_URL === '/admin/') { header("location: /admin/info/"); exit; }

// *** Layout
$set['stylesheet'] = '/share/styles/admin.css';
$set['menuhtml']   = '/admin/menu/english.xhtml';
$set['headhtml']   = '/share/website/cmshead.xhtml';
$set['foothtml']   = '/share/website/cmsfoot.xhtml';
$set['incbody']    = 0;

$echo['head'] = $echo['seo_title'] = $echo['javascript'] = $echo['bottom'] = $echo['debug'] = '';

if (!file_exists($htmlfile)) $htmlfile = 'english.xhtml';

// *** Frame pages (bare /admin/ and /admin/menu/) — strip chrome
if (pwcd('/admin/ /admin/menu/ /admin/welcome/')) {
    unset($set['headhtml'], $set['foothtml']);
    $htmlfile = 'english.xhtml';
}

// *** Settings viewer/editor
if ($REDIRECT_URL === '/admin/settings/')
    $result['items'] = wotan('settings/settings', '', ['setfile="/share/settings/admin.php"', 'listitems()']);
if ($REDIRECT_URL === '/admin/settings/edit/') {
    wotan('var_conv');
    $result['items'] = wotan('settings/settings', '', ['setfile="/share/settings/admin.php"', "viewedit($get[sid])"]);
}

// *** Load page-specific admin settings (e.g. share/settings/admin/users.php)
if ($pwcd < 1) {
    $exsetfile = pwsp('/share/settings/admin/' . $setflname[2] . '.php');
    if (file_exists($exsetfile)) require_once($exsetfile);
}

// *** Render output
if (empty($outdone)) {
    if (!empty($set['headhtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[head]', "evalfile('{$set['headhtml']}',1)");
    if (!empty($set['menuhtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[menu]', "evalfile('{$set['menuhtml']}')");
    if (!empty($htmlfile))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[webpage]', ['htmlfile=$htmlfile', 'incbody=$set[\'incbody\']', 'runall()']);
    if ($is_webmaster_ip)
        $echo['debug'] = wotan('vardump/vardump', '', 'runall()');
    if (!empty($set['foothtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[foot]', "evalfile('{$set['foothtml']}')");
}

// *** Session/log cleanup
if ($ses['hits'] < 2 || $errors || $logederror) {
    $logay = $ses;
    $logay['runttime'] = microtime(true) - ($starttime ?? 0);
    if ($errors || $logederror) { $logay['error'] = 1; $logay['errors'] = print_r($errors, true) . print_r($logederror, true); }
    $logay['info'] = 'admin';
    pw_arraytodb('users_log', $logay, '', 1);
    mysqli_query(_pw_mysqli(), "DELETE FROM users_log WHERE id>'9' AND UNIX_TIMESTAMP(timestamp)<'" . (time() - 24 * 3600 * 7) . "'");
    mysqli_query(_pw_mysqli(), "DELETE FROM systemlog WHERE id>'9' AND UNIX_TIMESTAMP(timestamp)<'" . (time() - 24 * 3600 * 30) . "'");
}
if ($errors) pw_systemlog('admin.php', 'Log errors', $errors);
?>
