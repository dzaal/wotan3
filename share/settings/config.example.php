<?php
// *** Wotan3 Configuration File
// Copy this file to config.php and fill in your values.
// NEVER commit config.php to version control.

// *** MySQL
$mysql['host']     = 'localhost';
$mysql['user']     = 'your_db_user';
$mysql['pass']     = 'your_db_password';
$mysql['selectdb'] = 'your_db_name';

// *** Site Identity
$echo['company_name'] = "Your Company Name";
$domainname           = "example.com";

// *** Webmaster — hostname or IP that gets developer error output
$webmastername = "office.example.com";   // or set to '' to disable
$webmasterip   = $webmastername ? gethostbyname($webmastername) : '';
$forwarded_ip  = '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $forwarded_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}
$visitor_ips       = array_filter(array($_SERVER['REMOTE_ADDR'] ?? '', $forwarded_ip));
$is_webmaster_ip   = $webmasterip && in_array($webmasterip, $visitor_ips, true);

if ($is_webmaster_ip) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', '1');
}

// *** Mail
$webmastereml = "admin@example.com";
$clientmail   = "info@example.com";

// *** Analytics (optional)
$analytics_ID = '';

// *** Languages available on this site
$languages = "nl, en";

// *** Default language fallback
if (!isset($get['lan']) || !$get['lan']) $get['lan'] = $_GET['lan'] = 'english';

// *** Protocol
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$websiteurl = $echo['websiteurl'] = $protocol . $domainname;
?>
