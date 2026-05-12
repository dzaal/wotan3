<?php
// *** Wotan3 — Guest/Frontend Settings
// This file is loaded for all public-facing pages.

$loaded['settings'] = 'guests.php';
include __DIR__ . '/config.php';

// *** Redirect admin paths to login
if (pwcd('/admin/*')) { header('location: /login/'); exit; }
if (pwcd('/profile/* /members/*')) { header('location: /login/'); exit; }

// *** Language
$htmlfile = ($get['lan'] ?? 'english') . '.xhtml';
if (isset($get['htmlfile']))
    $htmlfile = preg_replace('/[^a-z0-9_-]/i', '', $get['htmlfile']) . '.xhtml';

// *** Log PHP errors from error file to systemlog
if ($logederror) pw_systemlog('guests.php errorfile', 'PHP error', $logederror);

// *** Load core modules
wotan('var_conv2');
wotan('mysql3/connect'); if ($obj['connect']->errors) { echo $obj['connect']->errors; exit; }
mysqli_set_charset($GLOBALS['_mysqli'], 'utf8mb4');
wotan('basicfunc');
wotan('chkusr3/chkusr');

$echo['domainname'] = $domainname = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.') + 1, 999);

// *** Default mail headers
$mail['header'] = "Received: from {$_SERVER['HTTP_HOST']}\r\n"
    . "Message-ID: <" . date('Ymdhis') . "." . rand(100, 999) . ">\r\n"
    . "From: website@$domainname\r\n"
    . "Reply-to: info@$domainname\r\n"
    . "Return-Path: mailerror@$domainname\r\n"
    . "Errors-To: mailerror@$domainname\r\n"
    . "X-Mailer: Wotan3\r\n";

// *** HTML layout parts
$set['headhtml']   = '/share/website/head.xhtml';
$set['menuhtml']   = '/share/website/menu.xhtml';
$set['tophtml']    = '/share/website/top.xhtml';
$set['bottomhtml'] = '/share/website/bottom.xhtml';
$set['foothtml']   = '/share/website/foot.xhtml';
$set['template']   = '/share/templates/text_only.xhtml';

// *** jQuery
$echo['javalink'] = '<script src="/js/jquery/jquery-3.7.1.min.js"></script>';

// *** Member login state
$echo['memlogin']  = 'block';
$echo['memlogout'] = 'none';
if (($ses['chkaccess']['usergroup'] ?? '') === 'members') {
    $echo['memlogin']  = 'none';
    $echo['memlogout'] = 'block';
}

// *** Navigation menu from webpages table
$result['hoofdmenu'] = pw_dbtoarray(
    "SELECT category, subcat FROM webpages WHERE SUBSTR(category,2,1)=':' AND online='1' AND users_id>'0' GROUP BY category,subcat ORDER BY category,subcat"
);

if (!file_exists($htmlfile)) $htmlfile = 'english.xhtml';

$echo['currentdir'] = $REDIRECT_URL;

session_write_close(); // release session lock for concurrent requests

// *** JSON / XML output — strip layout wrappers
if (stristr($REDIRECT_URL, 'json/')) {
    unset($set['headhtml'], $set['menuhtml'], $set['template'], $set['tophtml'], $set['bottomhtml'], $set['foothtml']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Content-type: application/json');
}
if (pwcd('*/xml/') || isset($set['xml']) || isset($_GET['xml'])) {
    header('Content-Type: application/xml;');
}

// *** Load page-specific settings file (e.g. share/settings/guests/news.php)
if ($pwcd < 1) {
    $exsetfile = pwsp('/share/settings/guests/' . $setflname[1] . '.php');
    if (file_exists($exsetfile)) require_once($exsetfile);
}

// *** Render output
if (empty($outdone)) {
    if (!empty($set['headhtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[head]', "evalfile('{$set['headhtml']}',1)");
    if (!empty($set['menuhtml']))
        $echo['menuhtml'] = wotan('pw_htmlfile4/pw_htmlfile', '$obj[menu]', ['htmlfile=$set[\'menuhtml\']', 'nocomments=1', 'runall()']);
    if (!empty($set['tophtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[top]', ['htmlfile=$set[\'tophtml\']', 'nocomments=1', 'runall()']);
    if (!empty($set['template']))
        $echo['template'] = wotan('pw_htmlfile4/pw_htmlfile', '$obj[template]', ['htmlfile=$set[\'template\']', 'nocomments=1', 'runall()']);
    if (!empty($htmlfile))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[webpage]', ['htmlfile=$htmlfile', 'incbody=$set[\'incbody\']', 'nocomments=1', 'runall()']);
    if (!empty($set['bottomhtml']))
        $echo['bottom'] = wotan('pw_htmlfile4/pw_htmlfile', '$obj[bottom]', ['htmlfile=$set[\'bottomhtml\']', 'incbody=$set[\'incbody\']', 'nocomments=1', 'runall()']);
    if ($is_webmaster_ip)
        $echo['debug'] = wotan('vardump/vardump', '', 'runall()');
    if (!empty($set['foothtml']))
        echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[foot]', "evalfile('{$set['foothtml']}')");
}
?>
