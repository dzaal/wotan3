<?php
class vardump
{
    function runall($ivardump = '', $tpos = 0, $dpos = 620)
    {
        global $get, $ses, $post;

        $printr = '';

        $normalize_tokens = static function ($raw) {
            $raw = trim((string)$raw);
            if ($raw === '') {
                return array('', array());
            }

            $tokens = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            $unique = array();
            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }
                if (strpos($token, '$-') === 0) {
                    $token = '$_' . substr($token, 2);
                } elseif (strpos($token, '$result[') !== 0) {
                    $token = preg_replace('/^\$-/', '$_', $token);
                }
                // Convert hyphens to underscores in variable name (e.g. $safe-letter → $safe_letter)
                if (strpos($token, '$') === 0) {
                    $bracket = strpos($token, '[');
                    $base = $bracket === false ? substr($token, 1) : substr($token, 1, $bracket - 1);
                    $rest = $bracket === false ? '' : substr($token, $bracket);
                    $token = '$' . str_replace('-', '_', $base) . $rest;
                }
                if (!in_array($token, $unique, true)) {
                    $unique[] = $token;
                }
            }

            return array(implode(' ', $unique), $unique);
        };

        // Wotan auto-persists $get['vardump'] from ?vardump= across pages via $_SESSION['get']
        // POST form overrides: write back into $get so it also persists
        if (isset($_POST['vardump_clear'])) {
            $get['vardump'] = '';
            if (isset($_GET['vardump'])) unset($_GET['vardump']);
            if (isset($_REQUEST['vardump'])) unset($_REQUEST['vardump']);
            if (isset($_SESSION['get']['vardump'])) unset($_SESSION['get']['vardump']);
            if (isset($_SESSION['ses']['get']['vardump'])) unset($_SESSION['ses']['get']['vardump']);
        } elseif (isset($_POST['vardump'])) {
            list($normalizedInput) = $normalize_tokens($_POST['vardump']);
            $get['vardump'] = $normalizedInput;
            $_SESSION['get']['vardump'] = $normalizedInput;
            if (isset($_SESSION['ses']['get'])) $_SESSION['ses']['get']['vardump'] = $normalizedInput;
        }

        list($selected, $selectedTokens) = $normalize_tokens($get['vardump'] ?? '');
        $get['vardump'] = $selected;

        $vardumpay = $selectedTokens;
        foreach ($vardumpay as $val) {
            if (!$val) {
                continue;
            }
            if (!preg_match('/^\$[a-zA-Z_]\w*(\[[^\]]+\])*$/', $val)) {
                continue;
            }

            preg_match_all('/\[([^\]]+)\]/', $val, $matches);
            $base = preg_replace('/\[.*$/', '', $val);
            $vname = ltrim($base, '$');
            $dumpvalue = isset($GLOBALS[$vname]) ? $GLOBALS[$vname] : null;

            if (!empty($matches[1])) {
                foreach ($matches[1] as $segment) {
                    $segment = trim($segment, chr(34) . chr(39));
                    if (is_array($dumpvalue) && array_key_exists($segment, $dumpvalue)) {
                        $dumpvalue = $dumpvalue[$segment];
                    } else {
                        $dumpvalue = null;
                        break;
                    }
                }
            }

            $printr .= "\n\n" . $val . "\n" . print_r($dumpvalue, true);
        }

        preg_match('/v[\d.]+/', $this->ver ?? '', $_vm);
        $_vLabel = $_vm[0] ?? '';

        if (in_array('phpinfo', $selectedTokens)) {
            $info  = "\n\n=== PHP INFO ===\n";
            $info .= sprintf("%-22s %s\n", 'Version',         PHP_VERSION . ' (' . PHP_OS . ')');
            $info .= sprintf("%-22s %s\n", 'SAPI',            php_sapi_name());
            $info .= sprintf("%-22s %s\n", 'Timezone',        ini_get('date.timezone'));
            $info .= sprintf("%-22s %s\n", 'memory_limit',    ini_get('memory_limit'));
            $info .= sprintf("%-22s %s\n", 'max_exec_time',   ini_get('max_execution_time'));
            $info .= sprintf("%-22s %s\n", 'upload_max',      ini_get('upload_max_filesize'));
            $info .= sprintf("%-22s %s\n", 'post_max_size',   ini_get('post_max_size'));
            $info .= sprintf("%-22s %s\n", 'display_errors',  ini_get('display_errors'));
            $info .= sprintf("%-22s %s\n", 'error_reporting', ini_get('error_reporting'));
            $info .= sprintf("%-22s %s\n", 'default_charset', ini_get('default_charset'));
            $info .= sprintf("%-22s %s\n", 'max_input_vars',  ini_get('max_input_vars'));
            $info .= "\n-- Loaded Extensions (" . count(get_loaded_extensions()) . ") --\n";
            $exts = get_loaded_extensions(); sort($exts);
            $chunks = array_chunk($exts, 6);
            foreach ($chunks as $chunk) { $info .= '  ' . implode('  ', $chunk) . "\n"; }
            $printr .= $info;
        }

        $printr = preg_replace('/\n\s+\)\n\s+\)\n\n/', "\n))\n\n", $printr);


        // *** PHP Warnings panel
        $phpWarn = isset($GLOBALS['_vadump']) ? (array)$GLOBALS['_vadump'] : array();
        $warnCnt = count($phpWarn);
        $_wtn = array(E_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_WARNING=>'User Warning',
                      E_USER_NOTICE=>'User Notice',E_DEPRECATED=>'Deprecated',
                      E_USER_DEPRECATED=>'User Deprecated',E_STRICT=>'Strict',
                      E_ERROR=>'Error',E_USER_ERROR=>'User Error');
        $_grp = array();
        foreach ($phpWarn as $_w) {
            $_kmsg = preg_replace('/\\[\d+\\]/', '[n]', $_w['message']);
            $_k = $_kmsg.'|'.$_w['file'].'|'.$_w['line'];
            if (isset($_grp[$_k])) { $_grp[$_k]['count']++; }
            else { $_grp[$_k] = array_merge($_w, array('count'=>1,'message'=>$_kmsg)); }
        }
        $_wItems = ''; $_wText = '';
        foreach ($_grp as $_w) {
            $_tn = isset($_wtn[$_w['type']]) ? $_wtn[$_w['type']] : 'PHP('.$_w['type'].')';
            $_cx = ' <span class="vd-wb2">'.$_w['count'].'x</span>';
            $_wItems .= '<div class="vd-wi">'
              . '<span class="vd-wt">'.htmlspecialchars($_tn,ENT_QUOTES,'UTF-8').'</span>'.$_cx
              . '<span class="vd-wm">'.htmlspecialchars($_w['message'],ENT_QUOTES,'UTF-8').'</span>'
              . '<div class="vd-wl">'.htmlspecialchars($_w['file'],ENT_QUOTES,'UTF-8').':'.(int)$_w['line'].' ('.$_w['count'].'x)'.'</div>'
              . '</div>';
            $_wText .= '['.strtoupper($_tn).'] '.$_w['message']."
".'  '.$_w['file'].':'.$_w['line'].' ('.$_w['count'].'x)'."

";
        }
        $_wCopy = htmlspecialchars('URL: '.(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'').(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'')."

".$_wText, ENT_QUOTES,'UTF-8');

        $warnBtnHtml = '<button type="button" class="vd-warn-btn'.($warnCnt>0?' vd-warn-has':'').'" id="vd-wbtn" title="PHP Warnings &amp; Notices">'
            . '<span>PHP</span><span class="vd-wb">'.($warnCnt>0?(string)$warnCnt:'&#10003;').'</span></button>';
        $isOpen = !empty($_REQUEST['vardump_open']) || isset($_GET['vardump']) || isset($_POST['vardump']) || (!empty($_COOKIE['vd_pin']) && $selected !== '');
        $escapedSelected = htmlspecialchars($selected, ENT_QUOTES, 'UTF-8');
        $escapedDump = htmlspecialchars(trim($printr), ENT_QUOTES, 'UTF-8');
        $rootClass = $isOpen ? 'wotan-vardump is-open' : 'wotan-vardump';
        $formAction = htmlspecialchars((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/'), ENT_QUOTES, 'UTF-8');

        $links = array(
            '$post', '$catquery', '$artist', '$get', '$ppost', '$pget',
            '$result[items]', '$result[webpage]', '$result[cats]', '$query',
            '$result', '$echo', '$obj', '$system', '$sitemsgs', '$countryinfo',
            '$mysql', '$ses', '$_POST', '$_GET', '$_COOKIE', '$_SERVER',
            '$_SESSION', '$_REQUEST', '$GLOBALS',
            'phpinfo'
        );

        $selectedLookup = array_fill_keys($selectedTokens, true);

        $chipGroups = array(
            'Page'    => array('$post','$get','$ppost','$pget','$ses','$echo','$set'),
            'Result'  => array('$result','$result[items]','$result[webpage]','$result[cats]','$query','$obj'),
            'Site'    => array('$system','$sitemsgs','$countryinfo','$mysql','$artist','$catquery'),
            'Globals' => array('$_POST','$_GET','$_COOKIE','$_SERVER','$_SESSION','$_REQUEST','$GLOBALS','phpinfo'),
        );
        $chips = '';
        foreach ($chipGroups as $cgLabel => $cgLinks) {
            $chips .= '<div class="vd-cg"><span class="vd-cg-lbl">'.htmlspecialchars($cgLabel,ENT_QUOTES,'UTF-8').'</span><div class="vd-cg-row">';
            foreach ($cgLinks as $link) {
                $label = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
                $isActive = isset($selectedLookup[$link]);
                if ($isActive) {
                    $newTokens = array_values(array_filter($selectedTokens, function($t) use ($link) { return $t !== $link; }));
                    $newSel = implode(' ', $newTokens);
                    $chipHref = '?vardump=' . rawurlencode($newSel);
                } else {
                    $newSel = implode(' ', array_merge($selectedTokens, array($link)));
                    $chipHref = '?vardump=' . rawurlencode($newSel);
                }
                $chips .= '<a class="wotan-vardump__chip' . ($isActive ? ' is-active' : '') . '" href="' . $chipHref . '">'
                        . ($isActive ? '<span class="vd-chip-x">&times;</span>' : '') . $label . '</a>';
            }
            $chips .= '</div></div>';
        }

        $html = '';
        $html .= "\n<!-- WOTAN VARDUMP PART -->\n";
        $html .= '<style type="text/css">';
        $html .= '.wotan-vardump{position:fixed !important;left:12px !important;top:12px !important;right:auto !important;bottom:auto !important;inset:12px auto auto 12px !important;transform:none !important;margin:0 !important;z-index:2147483000;font:14px/1.35 Arial,sans-serif;color:#102114;}';
        $html .= '.wotan-vardump *{box-sizing:border-box;}';
        $html .= '.wotan-vardump__trigger{display:flex;align-items:center;justify-content:center;width:20px;height:20px;border:0;border-radius:999px;background:rgba(255,255,255,0.01);box-shadow:none;color:transparent;font-weight:bold;cursor:pointer;padding:0;opacity:.02;transition:opacity .18s ease, background-color .18s ease, box-shadow .18s ease;}';
        $html .= '.wotan-vardump__trigger span{display:block;font-size:0;line-height:1;}';
        $html .= '.wotan-vardump__panel{display:none;position:fixed !important;left:0 !important;top:40px !important;right:0 !important;bottom:auto !important;inset:40px 0 auto 0 !important;transform:none !important;width:100vw !important;height:calc(100vh - 40px);min-height:calc(100vh - 40px);max-height:calc(100vh - 40px);overflow:hidden;background:#e6ebe8;border:0;border-radius:0;box-shadow:none;z-index:2147483001;}';
        $html .= '.wotan-vardump:hover .wotan-vardump__trigger,.wotan-vardump:focus-within .wotan-vardump__trigger,.wotan-vardump.is-open .wotan-vardump__trigger{opacity:.92;background:linear-gradient(180deg,#e74a3b 0%,#b71c1c 100%);box-shadow:0 10px 24px rgba(0,0,0,.22);} .wotan-vardump.is-open .wotan-vardump__panel{display:flex;flex-direction:column;}';
        $html .= '.wotan-vardump__header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;background:linear-gradient(180deg,#183b22 0%,#0f2817 100%);color:#fff;}';
        $html .= '.wotan-vardump__title{margin:0;font-size:15px;font-weight:700;}';
        $html .= '.vd-ver{font-size:10px;opacity:.5;font-weight:400;margin-left:6px;align-self:flex-end;letter-spacing:.02em;}.wotan-vardump__subtitle{display:block;font-size:12px;opacity:.78;margin-top:2px;}';
        $html .= '.wotan-vardump__header-actions{display:flex;align-items:center;gap:8px;}';
        $html .= '.vd-warn-btn{display:flex;align-items:center;gap:5px;min-height:32px;padding:4px 12px;border:0;border-radius:10px;background:#1a6b2e;color:#fff;font:700 12px Arial,sans-serif;cursor:pointer;transition:background .15s;}';
        $html .= '.vd-warn-btn.vd-warn-has{background:#b84c00;}';
        $html .= '.vd-warn-btn:hover{filter:brightness(1.15);}';
        $html .= '#vd-wpin{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;padding:0;border-radius:4px;border:1px solid rgba(255,255,255,.25);background:transparent;color:rgba(255,255,255,.55);cursor:pointer;transition:background .15s,color .15s,border-color .15s;flex-shrink:0;}#vd-wpin:hover{background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.5);}#vd-wpin[aria-pressed="true"]{background:rgba(255,180,0,.28);border-color:rgba(255,200,60,.6);color:#ffd060;box-shadow:0 0 0 1px rgba(255,200,60,.3);}#vd-wpin .vd-pin-on{display:none;}#vd-wpin[aria-pressed="true"] .vd-pin-off{display:none;}#vd-wpin[aria-pressed="true"] .vd-pin-on{display:inline-flex;}';
        $html .= '.wotan-vardump__bar-btn{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;border-radius:10px;background:rgba(255,255,255,.14);color:#fff;cursor:pointer;padding:0;}';
        $html .= '.wotan-vardump__bar-btn:hover{background:rgba(255,255,255,.22);}';
        $html .= '.wotan-vardump__close{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border:0;border-radius:10px;background:rgba(255,255,255,.12);color:#fff;font-size:18px;cursor:pointer;}';
        $html .= '.wotan-vardump__body{display:flex;flex-direction:column;padding:12px;overflow:hidden;-webkit-overflow-scrolling:touch;flex:1 1 auto;min-height:0;height:100%;}';
        $html .= '.wotan-vardump__layout{display:flex;flex:1 1 auto;width:100%;height:100%;min-height:0;}';
        $html .= '.wotan-vardump__layout[data-dock="left"]{flex-direction:row;}';
        $html .= '.wotan-vardump__layout[data-dock="right"]{flex-direction:row-reverse;}';
        $html .= '.wotan-vardump__layout[data-dock="top"]{flex-direction:column;}';
        $html .= '.wotan-vardump__layout[data-dock="bottom"]{flex-direction:column-reverse;}';
        $html .= '.wotan-vardump__side,.wotan-vardump__main{min-width:0;min-height:0;}';
        $html .= '.wotan-vardump__layout[data-dock="left"] .wotan-vardump__side,.wotan-vardump__layout[data-dock="right"] .wotan-vardump__side{flex:0 0 calc(var(--wotan-side-percent,32) * 1%);width:calc(var(--wotan-side-percent,32) * 1%);height:auto;overflow:auto;padding-right:8px;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .wotan-vardump__side,.wotan-vardump__layout[data-dock="bottom"] .wotan-vardump__side{flex:0 0 auto;width:auto;height:auto;overflow:visible;padding:0 0 8px 0;}';
        $html .= '.wotan-vardump__main{display:block;flex:1 1 auto;overflow:auto;min-height:0;}';
        $html .= '.wotan-vardump__section + .wotan-vardump__section{margin-top:10px;}';
        $html .= '.wotan-vardump__chips{display:flex;flex-direction:column;gap:10px;}';
        $html .= '.vd-cg{}';
        $html .= '.vd-cg-lbl{display:block;font-size:9px;font-weight:700;text-transform:uppercase;color:var(--vd-chip,#6a8870);letter-spacing:.1em;margin-bottom:6px;padding-bottom:4px;border-bottom:1px solid var(--wotan-dump-border,#c8d9cc);}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .wotan-vardump__chips,.wotan-vardump__layout[data-dock="bottom"] .wotan-vardump__chips{flex-direction:row;flex-wrap:wrap;align-items:flex-start;gap:4px 10px;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .vd-cg,.wotan-vardump__layout[data-dock="bottom"] .vd-cg{display:flex;flex-direction:row;align-items:center;gap:4px;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .vd-cg-lbl,.wotan-vardump__layout[data-dock="bottom"] .vd-cg-lbl{margin-bottom:0;padding-bottom:0;border-bottom:0;border-right:1px solid var(--wotan-dump-border,#c8d9cc);padding-right:4px;white-space:nowrap;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .vd-cg-row,.wotan-vardump__layout[data-dock="bottom"] .vd-cg-row{flex-wrap:nowrap;gap:3px 4px;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .wotan-vardump__section+.wotan-vardump__section,.wotan-vardump__layout[data-dock="bottom"] .wotan-vardump__section+.wotan-vardump__section{margin-top:4px;}';
        $html .= '.vd-cg-row{display:flex;flex-wrap:wrap;gap:5px 8px;}';
        $html .= '.wotan-vardump__chip{display:inline-flex;align-items:center;gap:3px;padding:3px 1px;border:0;background:none;color:var(--vd-chip,#1e5a33);font:500 13px/1.3 Menlo,Consolas,monospace;text-decoration:none;cursor:pointer;white-space:nowrap;}';
        $html .= '.wotan-vardump__chip:hover{color:var(--vd-chip,#1e5a33);opacity:.75;text-decoration:underline;}';
        $html .= '.wotan-vardump__chip:visited{color:var(--vd-chip,#1e5a33);}';
        $html .= '.wotan-vardump__chip.is-active{padding:4px 10px;border-radius:5px;background:var(--vd-chip,#1e5a33);color:#fff;text-decoration:none;font-weight:700;}';
        $html .= '.wotan-vardump__chip.is-active:hover{background:var(--vd-chip,#164528);filter:brightness(0.82);color:#fff;}';
        $html .= '.wotan-vardump__chip.is-active:visited{color:#fff;}';
        $html .= '.vd-chip-x{font-size:14px;line-height:1;opacity:.65;margin-right:1px;}';
        $html .= '.wotan-vardump__form{display:flex;gap:8px;flex-wrap:wrap;}';
        $html .= '.wotan-vardump__input{flex:1 1 220px;min-height:40px;padding:10px 12px;border:1px solid #b7cbbb;border-radius:10px;background:#fff;color:#102114;font:13px Arial,sans-serif;}';
        $html .= '.wotan-vardump__submit{min-height:40px;padding:10px 14px;border:0;border-radius:10px;background:var(--vd-chip,#1e5a33);color:#fff;font:700 13px Arial,sans-serif;cursor:pointer;}';
        $html .= '.wotan-vardump__submit--clear{background:#dde6df;color:#183b22;}';
        $html .= '.wotan-vardump__dump{margin:0;flex:1 1 auto;height:100%;min-height:0;padding:12px;border-radius:10px;background:var(--wotan-dump-bg,#eef2ef);color:var(--wotan-dump-color,#17311e);box-shadow:inset 0 0 0 1px var(--wotan-dump-border,#d7e4db);font:12px/1.45 Menlo,Consolas,monospace;white-space:pre-wrap;word-break:break-word;overflow:auto;}';
        $html .= '.wotan-vardump__empty{padding:12px;border-radius:10px;background:#edf4ef;color:#4e6655;font-size:12px;}';
        $html .= '.wotan-vardump__resizer{flex:0 0 10px;position:relative;background:transparent;}';
        $html .= '.wotan-vardump__resizer:before{content:"";position:absolute;inset:0;}';
        $html .= '.wotan-vardump__layout[data-dock="left"] .wotan-vardump__resizer,.wotan-vardump__layout[data-dock="right"] .wotan-vardump__resizer{cursor:col-resize;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .wotan-vardump__resizer,.wotan-vardump__layout[data-dock="bottom"] .wotan-vardump__resizer{cursor:row-resize;flex-basis:10px;}';
        $html .= '.wotan-vardump__layout[data-dock="left"] .wotan-vardump__resizer:before,.wotan-vardump__layout[data-dock="right"] .wotan-vardump__resizer:before{left:4px;right:4px;top:0;bottom:0;background:linear-gradient(180deg,rgba(22,49,30,.06),rgba(22,49,30,.16),rgba(22,49,30,.06));border-radius:999px;}';
        $html .= '.wotan-vardump__layout[data-dock="top"] .wotan-vardump__resizer:before,.wotan-vardump__layout[data-dock="bottom"] .wotan-vardump__resizer:before{top:4px;bottom:4px;left:0;right:0;background:linear-gradient(90deg,rgba(22,49,30,.06),rgba(22,49,30,.16),rgba(22,49,30,.06));border-radius:999px;}';
        $html .= '.wotan-vardump__dock{position:relative;}';
        $html .= '.wotan-vardump__dock-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;width:32px;height:32px;border:0;border-radius:10px;background:#dbe8de;color:#16311e;cursor:pointer;padding:0;}';
        $html .= '.wotan-vardump__dock-btn-dot{display:block;width:4px;height:4px;border-radius:999px;background:currentColor;}';
        $html .= '.wotan-vardump__dock-menu{display:none;position:absolute;top:38px;right:0;width:102px;padding:8px;background:#fff;border:1px solid #c7d8cb;border-radius:12px;box-shadow:0 14px 26px rgba(0,0,0,.18);z-index:5;}';
        $html .= '.wotan-vardump__dock.is-open .wotan-vardump__dock-menu{display:grid;grid-template-columns:repeat(2,42px);grid-auto-rows:42px;justify-content:center;gap:6px;}';
        $html .= '.wotan-vardump__dock-option{display:flex;align-items:center;justify-content:center;width:42px;height:42px;padding:0;border:0;border-radius:9px;background:#edf4ef;color:#16311e;cursor:pointer;}';
        $html .= '.wotan-vardump__dock-option.is-active{background:var(--vd-chip,#1e5a33);color:#fff;}';
        $html .= '.wotan-vardump__dock-icon{position:relative;display:block;width:18px;height:18px;border:2px solid currentColor;border-radius:3px;opacity:.95;background:transparent;}';
        $html .= '.wotan-vardump__dock-icon:before{content:"";position:absolute;background:currentColor;opacity:.9;}';
        $html .= '.wotan-vardump__dock-icon:after{content:"";position:absolute;left:50%;top:50%;width:4px;height:4px;margin:-2px 0 0 -2px;border-radius:999px;background:currentColor;opacity:.25;}';
        $html .= '.wotan-vardump__dock-option[data-dock-option="top"] .wotan-vardump__dock-icon:before{left:2px;right:2px;top:2px;height:4px;}';
        $html .= '.wotan-vardump__dock-option[data-dock-option="right"] .wotan-vardump__dock-icon:before{top:2px;bottom:2px;right:2px;width:4px;}';
        $html .= '.wotan-vardump__dock-option[data-dock-option="bottom"] .wotan-vardump__dock-icon:before{left:2px;right:2px;bottom:2px;height:4px;}';
        $html .= '.wotan-vardump__dock-option[data-dock-option="left"] .wotan-vardump__dock-icon:before{top:2px;bottom:2px;left:2px;width:4px;}';
        $html .= '.wotan-vardump__palette{position:relative;}';
        $html .= '.wotan-vardump__palette-menu{display:none;position:absolute;top:38px;right:0;width:108px;padding:8px;background:#fff;border:1px solid #c7d8cb;border-radius:12px;box-shadow:0 14px 26px rgba(0,0,0,.18);z-index:5;}';
        $html .= '.wotan-vardump__palette.is-open .wotan-vardump__palette-menu{display:grid;grid-template-columns:repeat(3,28px);grid-auto-rows:28px;justify-content:center;gap:6px;}';
        $html .= '.wotan-vardump__swatch{display:flex;align-items:center;justify-content:center;width:28px;height:28px;min-width:28px;min-height:28px;padding:0;border:1px solid rgba(16,33,20,.18);border-radius:999px;cursor:pointer;font:700 10px/1 Arial,sans-serif;overflow:hidden;}';
        $html .= '@media only screen and (max-width: 657px){.wotan-vardump{left:10px !important;top:10px !important;right:auto !important;bottom:auto !important;inset:10px auto auto 10px !important;}.wotan-vardump__trigger{width:24px;height:24px;border-radius:999px;opacity:.08;}.wotan-vardump__panel{left:0 !important;right:0 !important;top:42px !important;bottom:auto !important;inset:42px 0 auto 0 !important;width:100vw !important;height:calc(100vh - 42px);min-height:calc(100vh - 42px);max-height:calc(100vh - 42px);border-radius:0;}.wotan-vardump__header{padding:10px 12px;}.wotan-vardump__body{padding:10px;min-height:0;}.wotan-vardump__layout{flex-direction:column !important;}.wotan-vardump__side{flex:0 0 auto !important;width:auto !important;height:min(42vh,calc(var(--wotan-side-percent,32) * 1vh)) !important;padding:0 0 8px 0 !important;}.wotan-vardump__resizer{cursor:row-resize !important;flex:0 0 10px !important;}.wotan-vardump__main{min-height:0;}.wotan-vardump__chips{gap:6px;}.wotan-vardump__chip{font-size:11px;padding:6px 9px;}.wotan-vardump__form{flex-direction:column;}.wotan-vardump__input,.wotan-vardump__submit,.wotan-vardump__submit--clear{width:100%;}}';
        $html .= '.vd-arr{border:0;margin:0;padding:0;display:inline;}';
        $html .= '.vd-arr > summary{display:inline;list-style:none;cursor:pointer;color:var(--vd-chip,#1e6639);font-weight:bold;}';
        $html .= '.vd-arr > summary::-webkit-details-marker{display:none;}';
        $html .= '.vd-arr > summary::marker{display:none;}';
        $html .= '.vd-arr:not([open]) > summary::before{content:"\25b8 ";}';
        $html .= '.vd-search{display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-shrink:0;position:sticky;top:0;z-index:2;background:#e6ebe8;padding-bottom:6px;}';
        $html .= '.vd-search-input{flex:1;min-height:34px;padding:7px 10px;border:1px solid #b7cbbb;border-radius:8px;background:#fff;color:#102114;font:13px Arial,sans-serif;}';
        $html .= '.vd-search-count{font-size:12px;color:#4e6655;white-space:nowrap;min-width:70px;}';
        $html .= '.vd-search-nav{display:flex;gap:4px;}';
        $html .= '.vd-search-nav button{min-height:30px;padding:4px 10px;border:0;border-radius:7px;background:#dbe8de;color:#16311e;font:700 12px Arial,sans-serif;cursor:pointer;}';
        $html .= 'mark.vd-mark{background:#ffec40;color:#1a1a00;border-radius:2px;padding:0 1px;}';
        $html .= 'mark.vd-mark.vd-mark-current{background:#ff9800;color:#fff;}';
        $html .= '.vd-expand-btn,.vd-json-btn,.vd-html-btn{border:0;margin:0 4px;padding:2px 6px;border-radius:4px;font:700 10px/1.4 Arial,sans-serif;cursor:pointer;vertical-align:baseline;}';
        $html .= '.vd-expand-btn{background:#dbe8de;color:#16311e;}';
        $html .= '.vd-json-btn{background:#dbeaf7;color:#0d3352;}';
        $html .= '.vd-json-btn.is-active{background:#0d3352;color:#fff;}';
        $html .= '.vd-html-btn{background:#fdebd0;color:#5d3606;}';
        $html .= '.vd-html-btn.is-active{background:#5d3606;color:#fff;}';
        $html .= '.vd-json-pre{display:none;margin:4px 0 0;padding:6px 8px;border-radius:6px;background:#dbeaf7;color:#0d3352;font:11px/1.45 Menlo,Consolas,monospace;white-space:pre-wrap;word-break:break-word;}';
        $html .= '.vd-html-preview{display:none;margin:4px 0 0;padding:6px 8px;border-radius:6px;background:#fff !important;color:#222 !important;border:1px solid #f0c07a;font:13px/1.5 Arial,sans-serif;}';
        $html .= '.vd-wb{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:rgba(255,255,255,.25);font:700 10px Arial,sans-serif;}';
        $html .= '.vd-wpc{border:0;background:rgba(255,255,255,.15);color:#fff;border-radius:7px;height:28px;padding:0 10px;font:700 11px Arial,sans-serif;cursor:pointer;}';
        $html .= '.vd-wpc:hover{background:rgba(255,255,255,.25);}';
        $html .= '.vd-wi{padding:5px 0;border-bottom:1px solid var(--wotan-dump-border,#d7e4db);font-size:12px;}';
        $html .= '.vd-wt{display:inline-block;padding:1px 6px;border-radius:4px;background:var(--vd-chip,#c86000);color:#fff;font:700 10px Arial,sans-serif;text-transform:uppercase;margin-right:4px;}';
        $html .= '.vd-wm{color:var(--wotan-dump-color,#17311e);}';
        $html .= '.vd-wl{color:var(--wotan-dump-color,#17311e);opacity:.65;font-size:11px;margin-top:2px;}';
        $html .= '.vd-wb2{font-size:10px;background:#555;border-radius:4px;padding:1px 5px;margin-left:4px;}';
        $html .= '.vd-wb2{display:inline-flex;align-items:center;padding:1px 5px;border-radius:4px;background:rgba(255,255,255,.2);color:#fff;font:700 10px Arial,sans-serif;margin-right:4px;}';
        $html .= '.vd-wm{display:block;color:var(--wotan-dump-color,#17311e);word-break:break-word;margin-top:4px;font-size:12px;line-height:1.5;}';
        $html .= '.vd-wl{margin-top:3px;color:#a08060;font-size:11px;}.vd-warn-float{position:fixed !important;top:12px !important;right:12px !important;left:auto !important;bottom:auto !important;display:flex;align-items:center;gap:4px;white-space:nowrap;padding:4px 12px;border:0;border-radius:10px;background:#b84c00;color:#fff;font:700 12px Arial,sans-serif;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.35);z-index:2147483002;}.vd-warn-float:hover{filter:brightness(1.15);}.wotan-vardump.is-open .vd-warn-float{display:none;}';
        $html .= '</style>';

        $html .= '<div id="wotan-vardump" class="' . $rootClass . '">';
        $html .= '<button type="button" class="wotan-vardump__trigger" aria-controls="wotan-vardump-panel" aria-expanded="' . ($isOpen ? 'true' : 'false') . '" onclick="(function(btn){var root=document.getElementById(\'wotan-vardump\');if(!root)return;root.classList.toggle(\'is-open\');btn.setAttribute(\'aria-expanded\',root.classList.contains(\'is-open\')?\'true\':\'false\');})(this)"><span></span></button>';
        if ($warnCnt>0) $html .= '<button type="button" id="vd-wfloat" class="vd-warn-float" title="PHP Warnings &amp; Notices">'
            . '<span>PHP</span><span class="vd-wb">'.($warnCnt).'</span></button>';
        $html .= '<div id="wotan-vardump-panel" class="wotan-vardump__panel">';
        $html .= '<div class="wotan-vardump__header"><div><h3 class="wotan-vardump__title">Digizaal VarDump</h3><span class="wotan-vardump__subtitle">Wotan debug console</span>' . ($_vLabel ? '<span class="vd-ver">'.$_vLabel.'</span>' : '') . '</div><div class="wotan-vardump__header-actions">' . $warnBtnHtml . '<button type="button" id="vd-wpin" class="wotan-vardump__bar-btn" title="Pin: keep panel open on every page load" aria-pressed="false"><span class="vd-pin-off"><svg width="13" height="15" viewBox="0 0 13 15" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="1.5" y="1.5" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/><line x1="6.5" y1="8.5" x2="6.5" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><line x1="4" y1="11" x2="9" y2="11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></span><span class="vd-pin-on"><svg width="13" height="15" viewBox="0 0 13 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><rect x="1.5" y="1.5" width="10" height="7" rx="1.5"/><rect x="5.5" y="8" width="2" height="6" rx="1"/><line x1="4" y1="11" x2="9" y2="11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg></span></button>' . '<div class="wotan-vardump__palette"><button type="button" class="wotan-vardump__bar-btn wotan-vardump__palette-btn" aria-label="Change vardump background" aria-expanded="false">◐</button><div class="wotan-vardump__palette-menu"><button type="button" class="wotan-vardump__swatch" data-dump-bg="#eef2ef" data-dump-color="#17311e" data-dump-border="#d7e4db" data-dump-chip="#1e5a33" style="background:linear-gradient(135deg,#1e5a33 40%,#eef2ef 40%);color:#17311e" title="Sage">A</button><button type="button" class="wotan-vardump__swatch" data-dump-bg="#141a16" data-dump-color="#c0dcc4" data-dump-border="#243028" data-dump-chip="#58c880" style="background:linear-gradient(135deg,#58c880 40%,#141a16 40%);color:#c0dcc4" title="Night">A</button><button type="button" class="wotan-vardump__swatch" data-dump-bg="#0d1830" data-dump-color="#7cc0e8" data-dump-border="#1a3060" data-dump-chip="#4898d8" style="background:linear-gradient(135deg,#4898d8 40%,#0d1830 40%);color:#7cc0e8" title="Dusk">A</button><button type="button" class="wotan-vardump__swatch" data-dump-bg="#faf5e8" data-dump-color="#2a1e08" data-dump-border="#d8c898" data-dump-chip="#7a4a10" style="background:linear-gradient(135deg,#7a4a10 40%,#faf5e8 40%);color:#2a1e08" title="Paper">A</button><button type="button" class="wotan-vardump__swatch" data-dump-bg="#1e2228" data-dump-color="#ccd8e0" data-dump-border="#303c48" data-dump-chip="#70aad0" style="background:linear-gradient(135deg,#70aad0 40%,#1e2228 40%);color:#ccd8e0" title="Charcoal">A</button><button type="button" class="wotan-vardump__swatch" data-dump-bg="#281400" data-dump-color="#f0b840" data-dump-border="#4a2800" data-dump-chip="#e09030" style="background:linear-gradient(135deg,#e09030 40%,#281400 40%);color:#f0b840" title="Ember">A</button></div></div><div class="wotan-vardump__dock"><button type="button" class="wotan-vardump__dock-btn" aria-label="Dock controls" aria-expanded="false"><span class="wotan-vardump__dock-btn-dot"></span><span class="wotan-vardump__dock-btn-dot"></span><span class="wotan-vardump__dock-btn-dot"></span></button><div class="wotan-vardump__dock-menu"><button type="button" class="wotan-vardump__dock-option" data-dock-option="top"><span class="wotan-vardump__dock-icon"></span></button><button type="button" class="wotan-vardump__dock-option is-active" data-dock-option="right"><span class="wotan-vardump__dock-icon"></span></button><button type="button" class="wotan-vardump__dock-option" data-dock-option="bottom"><span class="wotan-vardump__dock-icon"></span></button><button type="button" class="wotan-vardump__dock-option" data-dock-option="left"><span class="wotan-vardump__dock-icon"></span></button></div></div><button type="button" class="wotan-vardump__close" aria-label="Close vardump" onclick="(function(){var root=document.getElementById(\'wotan-vardump\');if(!root)return;root.classList.remove(\'is-open\');var trigger=root.querySelector(\'.wotan-vardump__trigger\');if(trigger)trigger.setAttribute(\'aria-expanded\',\'false\');})()">×</button></div></div>';
        $html .= '<div class="wotan-vardump__body">';
        $html .= '<div class="wotan-vardump__layout" data-dock="right" style="--wotan-side-percent:32;">';
        $html .= '<div class="wotan-vardump__side">';
        $html .= '<div class="wotan-vardump__section"><div class="wotan-vardump__chips">' . $chips . '</div></div>';
        $html .= '<div class="wotan-vardump__section"><form class="wotan-vardump__form" method="post" action="' . $formAction . '"><input class="wotan-vardump__input" type="text" name="vardump" value="' . $escapedSelected . '" placeholder="$result $echo $_GET ..." /><button class="wotan-vardump__submit" type="submit">Update</button><button class="wotan-vardump__submit wotan-vardump__submit--clear" type="submit" name="vardump_clear" value="1">Clear</button></form></div>';
        $html .= '</div>';
        $html .= '<div class="wotan-vardump__resizer" aria-hidden="true"></div>';
        $html .= '<div class="wotan-vardump__main"><div class="wotan-vardump__section" style="display:flex;flex-direction:column;min-height:100%;">';
        if ($warnCnt>0) {
            $html .= '<div id="vd-wtop" style="display:none;background:var(--wotan-dump-bg,#eef2ef);color:var(--wotan-dump-color,#17311e);border-bottom:1px solid var(--wotan-dump-border,#d7e4db);padding:10px 14px;font:13px Arial,sans-serif;overflow-y:auto;max-height:40vh;-webkit-overflow-scrolling:touch;">'
                   . '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">'
                   . '<span style="font-weight:700;font-size:12px;color:var(--vd-chip,#1e5a33);">PHP Warnings &amp; Notices (' . $warnCnt . ')</span>'
                   . '<button type="button" id="vd-wcopy2" style="border:0;background:var(--vd-chip,#1e5a33);color:#fff;border-radius:6px;padding:3px 10px;font:700 11px Arial,sans-serif;cursor:pointer;">Copy</button>'
                   . '</div>'
                   . '<div>' . $_wItems . '</div>'
                   . '<div id="vd-wtxt2" data-t="' . $_wCopy . '" style="display:none"></div>'
                   . '</div>';
        }
        if ($escapedDump !== '') {
            $html .= '<div class="vd-search"><input type="search" class="vd-search-input" placeholder="Find in dump… (Ctrl+F)" /><span class="vd-search-count"></span><div class="vd-search-nav"><button class="vd-prev" title="Previous">↑</button><button class="vd-next" title="Next">↓</button></div></div>';
            $html .= '<pre class="wotan-vardump__dump" id="wotan-vardump-pre">' . $escapedDump . '</pre>';
        } else {
            $html .= '<div class="wotan-vardump__empty">No dump output yet. Select one or more vars to inspect.</div>';
        }
        $html .= '</div></div></div></div>';
        $html .= '<script type="text/javascript">(function(){var root=document.getElementById("wotan-vardump");if(!root)return;var layout=root.querySelector(".wotan-vardump__layout");var dockWrap=root.querySelector(".wotan-vardump__dock");var dockBtn=root.querySelector(".wotan-vardump__dock-btn");var paletteWrap=root.querySelector(".wotan-vardump__palette");var paletteBtn=root.querySelector(".wotan-vardump__palette-btn");var swatches=root.querySelectorAll("[data-dump-bg]");var resizer=root.querySelector(".wotan-vardump__resizer");var options=root.querySelectorAll("[data-dock-option]");var storageDock="wotanVardumpDock";var storagePercent="wotanVardumpSidePercent";var storageTheme="wotanVardumpDumpTheme";var dock=(sessionStorage.getItem(storageDock)||layout.getAttribute("data-dock")||"right");var percent=parseFloat(sessionStorage.getItem(storagePercent)||layout.style.getPropertyValue("--wotan-side-percent")||"32");var themeRaw=sessionStorage.getItem(storageTheme)||"#eef2ef|#17311e|#d7e4db|#1e5a33";function bounds(nextDock){var vertical=(nextDock==="top"||nextDock==="bottom"||window.innerWidth<=657);return vertical?{min:16,max:50,def:24}:{min:18,max:48,def:32};}function clampPercent(value,nextDock){var limit=bounds(nextDock||dock);if(isNaN(value))value=limit.def;return Math.max(limit.min,Math.min(limit.max,value));}function applyTheme(raw){var parts=(raw||"#eef2ef|#17311e|#d7e4db|#1e5a33").split("|");root.style.setProperty("--wotan-dump-bg",parts[0]||"#eef2ef");root.style.setProperty("--wotan-dump-color",parts[1]||"#17311e");root.style.setProperty("--wotan-dump-border",parts[2]||"#d7e4db");root.style.setProperty("--vd-chip",parts[3]||"#1e5a33");sessionStorage.setItem(storageTheme,[parts[0]||"#eef2ef",parts[1]||"#17311e",parts[2]||"#d7e4db",parts[3]||"#1e5a33"].join("|"));}function applyDock(nextDock){dock=nextDock;layout.setAttribute("data-dock",dock);options.forEach(function(option){option.classList.toggle("is-active",option.getAttribute("data-dock-option")===dock);});if(dock==="top"||dock==="bottom"||window.innerWidth<=657){layout.style.removeProperty("--wotan-side-percent");}else{percent=clampPercent(percent,dock);layout.style.setProperty("--wotan-side-percent",percent.toFixed(2));}sessionStorage.setItem(storageDock,dock);sessionStorage.setItem(storagePercent,String(percent));}dockBtn.addEventListener("click",function(){var open=dockWrap.classList.toggle("is-open");dockBtn.setAttribute("aria-expanded",open?"true":"false");paletteWrap.classList.remove("is-open");paletteBtn.setAttribute("aria-expanded","false");});paletteBtn.addEventListener("click",function(){var open=paletteWrap.classList.toggle("is-open");paletteBtn.setAttribute("aria-expanded",open?"true":"false");dockWrap.classList.remove("is-open");dockBtn.setAttribute("aria-expanded","false");});document.addEventListener("click",function(event){if(!dockWrap.contains(event.target)){dockWrap.classList.remove("is-open");dockBtn.setAttribute("aria-expanded","false");}if(!paletteWrap.contains(event.target)){paletteWrap.classList.remove("is-open");paletteBtn.setAttribute("aria-expanded","false");}});options.forEach(function(option){option.addEventListener("click",function(){applyDock(option.getAttribute("data-dock-option"));dockWrap.classList.remove("is-open");dockBtn.setAttribute("aria-expanded","false");});});swatches.forEach(function(swatch){swatch.addEventListener("click",function(){applyTheme([swatch.getAttribute("data-dump-bg"),swatch.getAttribute("data-dump-color"),swatch.getAttribute("data-dump-border"),swatch.getAttribute("data-dump-chip")||"#1e5a33"].join("|"));paletteWrap.classList.remove("is-open");paletteBtn.setAttribute("aria-expanded","false");});});var drag=null;resizer.addEventListener("mousedown",function(event){event.preventDefault();drag={startX:event.clientX,startY:event.clientY,startPercent:percent,dock:dock};document.body.style.userSelect="none";});window.addEventListener("mousemove",function(event){if(!drag)return;var activeDock=window.innerWidth<=657?"top":drag.dock;if(activeDock==="top"||activeDock==="bottom"){return;}var next=drag.startPercent;if(activeDock==="left"){next=drag.startPercent+((event.clientX-drag.startX)/window.innerWidth)*100;}else if(activeDock==="right"){next=drag.startPercent-((event.clientX-drag.startX)/window.innerWidth)*100;}percent=clampPercent(next,activeDock);layout.style.setProperty("--wotan-side-percent",percent.toFixed(2));sessionStorage.setItem(storagePercent,String(percent));});window.addEventListener("mouseup",function(){drag=null;document.body.style.userSelect="";});window.addEventListener("resize",function(){percent=clampPercent(percent,dock);if(dock!=="top"&&dock!=="bottom"&&window.innerWidth>657){layout.style.setProperty("--wotan-side-percent",percent.toFixed(2));}sessionStorage.setItem(storagePercent,String(percent));});applyTheme(themeRaw);applyDock(dock);(function(){var wb=document.getElementById("vd-wbtn");var wt=document.getElementById("vd-wtop");if(!wb||!wt)return;wb.addEventListener("click",function(){var vis=wt.style.display==="block";wt.style.display=vis?"none":"block";});})();(function(){var wf=document.getElementById("vd-wfloat");if(!wf)return;wf.addEventListener("click",function(){var root=document.getElementById("wotan-vardump");if(!root)return;root.classList.add("is-open");var trig=root.querySelector(".wotan-vardump__trigger");if(trig)trig.setAttribute("aria-expanded","true");var wt=document.getElementById("vd-wtop");if(wt)wt.style.display="block";});})();(function(){var wcp=document.getElementById("vd-wcopy2");var wt2=document.getElementById("vd-wtxt2");if(!wcp||!wt2)return;wcp.addEventListener("click",function(){var txt=wt2.getAttribute("data-t");navigator.clipboard.writeText(txt).then(function(){wcp.textContent="Copied!";setTimeout(function(){wcp.textContent="Copy";},2000);}).catch(function(){wcp.textContent="Failed";});});})();(function(){var pinBtn=document.getElementById("vd-wpin");if(!pinBtn)return;var storagePin="wotanVardumpWarnPin";var warnPinned=sessionStorage.getItem(storagePin)==="1";var hasWarnings=!!document.getElementById("vd-wtop");pinBtn.setAttribute("aria-pressed",warnPinned?"true":"false");if(warnPinned){root.classList.add("is-open");var trig=root.querySelector(".wotan-vardump__trigger");if(trig)trig.setAttribute("aria-expanded","true");if(hasWarnings){var wt=document.getElementById("vd-wtop");if(wt)wt.style.display="block";}}pinBtn.addEventListener("click",function(){warnPinned=!warnPinned;sessionStorage.setItem(storagePin,warnPinned?"1":"0");pinBtn.setAttribute("aria-pressed",warnPinned?"true":"false");if(warnPinned){document.cookie="vd_pin=1;path=/;samesite=lax";}else{document.cookie="vd_pin=;path=/;max-age=0;samesite=lax";}});})();})();</script>';
        $html .= <<<'JSNOWDOC'
<script type="text/javascript">
(function(){
var root=document.getElementById('wotan-vardump');
if(!root)return;
(function(){
  var pre = root.querySelector('#wotan-vardump-pre');
  if (!pre) return;
  var raw = pre.textContent;
  var lines = raw.split('\n');
  var html = [];
  var stack = [];
  function esc(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function ind(line){ var m=line.match(/^(\s*)/); return m?m[1].length:0; }
  var i=0;
  while(i<lines.length){
    var line=lines[i], tr=line.trim(), di=ind(line);
    if(/(?:Array|Object)$/.test(tr)){
      var ni=i+1;
      while(ni<lines.length && !lines[ni].trim()) ni++;
      if(ni<lines.length && lines[ni].trim()==='('){
        var oi=ind(lines[ni]);
        var pfx=esc(line.replace(/(?:Array|Object)$/,''));
        var word=tr.match(/(?:Array|Object)$/)[0];
        html.push(pfx+'<details open class="vd-arr"><summary class="vd-arr-s">'+word+'</summary>');
        stack.push(oi); i=ni+1; continue;
      }
    }
    if(tr===')' && stack.length && stack[stack.length-1]===di){
      html.push('</details>'); stack.pop(); i++; continue;
    }
    if(/^\$/.test(tr)){html.push('<strong style="display:block;margin-top:10px;font-size:13px;color:var(--vd-chip,#1e5a33)">'+esc(tr)+'</strong>');i++;continue;}
    html.push(esc(line)); i++;
  }
  pre.innerHTML = html.join('\n');
})();
(function(){
  var pre = root.querySelector('#wotan-vardump-pre');
  if (!pre) return;
  function isJson(s){var t=s.trim();if(!t.length||(t[0]!='{'&&t[0]!='['))return false;try{JSON.parse(t);return true;}catch(e){return false;}}
  function isHtml(s){return /<[a-z][^>]*>/i.test(s);}
  function makeEl(value){
    if(!value)return null;
    var long=value.length>100,json=isJson(value),html=!json&&isHtml(value);
    if(!long&&!json&&!html)return null;
    var span=document.createElement('span');span.className='vd-val vd-noindex';
    span.appendChild(document.createTextNode(value.substring(0,100).replace(/\n/g,'\u21b5')+(value.length>100?'\u2026':'')));
    if(long&&!json&&!html){
      var btn=document.createElement('button');btn.className='vd-expand-btn vd-noindex';
      var rem=value.length-100;btn.textContent='+'+rem+' chars';
      var full=document.createElement('span');full.className='vd-noindex';full.style.display='none';
      full.textContent=value.substring(100).replace(/\n/g,'\u21b5\n');
      btn.onclick=function(){var o=full.style.display==='none';full.style.display=o?'inline':'none';btn.textContent=o?'\u2191':'+'+rem+' chars';};
      span.appendChild(btn);span.appendChild(full);
    }
    if(json){
      var jBtn=document.createElement('button');jBtn.className='vd-json-btn vd-noindex';jBtn.textContent='JSON';
      var jPre=document.createElement('pre');jPre.className='vd-json-pre vd-noindex';jPre.style.display='none';
      try{jPre.textContent=JSON.stringify(JSON.parse(value.trim()),null,2);}catch(e){jPre.textContent=value;}
      jBtn.onclick=function(){var o=jPre.style.display==='none';jPre.style.display=o?'block':'none';jBtn.classList.toggle('is-active',o);};
      span.appendChild(jBtn);span.appendChild(jPre);
    }
    if(html){
      var hBtn=document.createElement('button');hBtn.className='vd-html-btn vd-noindex';hBtn.textContent='HTML\u25be';
      var hDiv=document.createElement('div');hDiv.className='vd-html-preview vd-noindex';hDiv.style.display='none';
      hDiv.innerHTML=value;
      hBtn.onclick=function(){var o=hDiv.style.display==='none';hDiv.style.display=o?'block':'none';hBtn.classList.toggle('is-active',o);hBtn.textContent=o?'HTML\u25b4':'HTML\u25be';};
      span.appendChild(hBtn);span.appendChild(hDiv);
    }
    return span;
  }
  function walk(node){
    if(node.nodeType===3){
      var text=node.nodeValue;if(text.indexOf(' => ')===-1)return;
      var lines=text.split('\n'),anyChanged=false,frag=document.createDocumentFragment();
      for(var i=0;i<lines.length;i++){
        var line=lines[i],ai=line.indexOf(' => ');
        if(ai!==-1){
          var val=line.substring(ai+4);
          while(i+1<lines.length){var nx=lines[i+1];if(/^\s+\[/.test(nx)||/^\s*\)\s*$/.test(nx)||/^\s*Array\s*$/.test(nx)||/^\s*\w.*Object\s*$/.test(nx))break;val+='\n'+nx;i++;}
          var el=makeEl(val);
          if(el){frag.appendChild(document.createTextNode(line.substring(0,ai+4)));frag.appendChild(el);anyChanged=true;if(i<lines.length-1)frag.appendChild(document.createTextNode('\n'));continue;}
        }
        frag.appendChild(document.createTextNode(line));if(i<lines.length-1)frag.appendChild(document.createTextNode('\n'));
      }
      if(anyChanged)node.parentNode.replaceChild(frag,node);
      return;
    }
    if(node.classList&&node.classList.contains('vd-noindex'))return;
    Array.prototype.slice.call(node.childNodes).forEach(walk);
  }
  walk(pre);
})();
(function(){
  var pre = root.querySelector('#wotan-vardump-pre');
  var input = root.querySelector('.vd-search-input');
  var countEl = root.querySelector('.vd-search-count');
  var btnPrev = root.querySelector('.vd-prev');
  var btnNext = root.querySelector('.vd-next');
  if (!pre || !input) return;
  var currentIdx = 0;
  var marks = [];
  function clearMarks(){
    marks.forEach(function(m){ var t=document.createTextNode(m.textContent); m.parentNode.replaceChild(t,m); });
    marks=[]; pre.normalize();
  }
  function markAll(term){
    clearMarks();
    if(!term) return;
    var walker=document.createTreeWalker(pre,NodeFilter.SHOW_TEXT,{acceptNode:function(node){var p=node.parentNode;while(p&&p!==pre){if(p.classList&&p.classList.contains('vd-noindex'))return NodeFilter.FILTER_SKIP;p=p.parentNode;}return NodeFilter.FILTER_ACCEPT;}},false);
    var nodes=[];
    while(walker.nextNode()) nodes.push(walker.currentNode);
    var re=new RegExp('('+term.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi');
    nodes.forEach(function(node){
      var parts=node.nodeValue.split(re);
      if(parts.length<2) return;
      var frag=document.createDocumentFragment();
      parts.forEach(function(p,idx){
        if(idx%2===1){ var mk=document.createElement('mark'); mk.className='vd-mark'; mk.textContent=p; marks.push(mk); frag.appendChild(mk); }
        else if(p) frag.appendChild(document.createTextNode(p));
      });
      node.parentNode.replaceChild(frag,node);
    });
  }
  function highlight(idx){
    marks.forEach(function(m){ m.classList.remove('vd-mark-current'); });
    if(!marks.length){ countEl.textContent='no match'; return; }
    currentIdx=(idx+marks.length)%marks.length;
    marks[currentIdx].classList.add('vd-mark-current');
    marks[currentIdx].scrollIntoView({block:'nearest'});
    countEl.textContent=(currentIdx+1)+' / '+marks.length;
  }
  input.addEventListener('input',function(){
    markAll(this.value.trim());
    highlight(0);
    if(!this.value.trim()) countEl.textContent='';
  });
  if(btnNext) btnNext.addEventListener('click',function(){ highlight(currentIdx+1); });
  if(btnPrev) btnPrev.addEventListener('click',function(){ highlight(currentIdx-1); });
  root.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&root.classList.contains('is-open')){
      e.preventDefault();
      root.classList.remove('is-open');
      var trig=root.querySelector('.wotan-vardump__trigger');
      if(trig)trig.setAttribute('aria-expanded','false');
      return;
    }
    if(e.target===input){
      if(e.key==='Enter'){ e.preventDefault(); highlight(e.shiftKey?currentIdx-1:currentIdx+1); }
    }
    if((e.ctrlKey||e.metaKey)&&e.key==='f'&&root.classList.contains('is-open')){
      e.preventDefault(); input.focus(); input.select();
    }
  });
})();
})();
</script>
JSNOWDOC;
        $html .= "\n<!-- END WOTAN VARDUMP PART -->\n";

        return $html;
    }

    var $ver = 'phpwotan.com VarDump v3.12 dockable drawer';
}
?>
