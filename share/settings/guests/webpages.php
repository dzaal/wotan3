<?php
// *** Wotan3 — Generic webpages routing
// Routes ?page= and ?id= to templates defined in the webpages DB table.

$result['webpage'] = pw_dbtoarray(
    "SELECT * FROM webpages WHERE online='1' AND (category='" .
    mysqli_real_escape_string($GLOBALS['_mysqli'], $get['mcategory'] ?? '') .
    "' OR id='" . (int)($get['id'] ?? 0) . "') LIMIT 1"
);

$_wp = $result['webpage'][1] ?? null;

// *** SEO title / description from webpage record
if (!empty($_wp['seotitle_' . $get['lan']]) && !isset($echo['seo_title']))
    $echo['seo_title'] = strip_tags($_wp['seotitle_' . $get['lan']]);
elseif (!isset($echo['seo_title']))
    $echo['seo_title'] = strip_tags($_wp['name_' . ($get['lan'] ?? 'english')] ?? '');

if (!isset($echo['seo_description']))
    $echo['seo_description'] = substr(strip_tags($_wp['description_' . ($get['lan'] ?? 'english')] ?? ''), 0, 200);

// *** Template from DB
if (!empty($_wp['template']))
    $set['template'] = $_wp['template'];
?>
