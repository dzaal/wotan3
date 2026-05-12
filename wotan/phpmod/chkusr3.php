<?php $ses['verdate']['chkusr3']=70506;
// *** ChecK if $LiNe ConTains same data as one of the Text lines of $text **** PW200304 LUPD190305
function pw_cklncttxtln ($line,$text)
{ $strpos=0;
 $text=str_replace("\r",'',$text);$text=str_replace(' ','',$text);
 $text=str_replace('*','/',$text);$txtlns=explode("\n",$text);
 foreach ($txtlns as $data => $datb) { if ($datb) {
 if(strpos(' '.$line,$datb)==1) { $strpos=1;$txtlnx=0; }
 if($strpos<>1 and strpos(' '.$line,$datb)>0) $strpos=strpos(' '.$line,$datb);
 }} return $strpos;
}

// *** Access Control **** PW301203 LUpd100605-041205 | SQL injection fixes + bcrypt 2026
function pw_acctrl ()
{ $echo='';
 global $echo,$ses,$REDIRECT_URL,$system,$errors,$set;
 if (!isset($GLOBALS['_mysqli'])) return false;
 $conn = $GLOBALS['_mysqli'];
 $safe_table = mysqli_real_escape_string($conn, $set['userstable'] ?? 'users');

 // ** Build $ses[chkaccess] once per session (or when cleared on login/logout)
 if (!isset($ses['chkaccess']) || !is_array($ses['chkaccess']))
 {
  // ** Guest fallback
  if (empty($ses['username'])) { $ses['username']='guest'; }

  // ** Authenticated user: look up by session users_id (no password in query)
  if (!empty($ses['users_id']) && (int)$ses['users_id'] > 0) {
    $uid = (int)$ses['users_id'];
    $result = pw_dbtoarray("SELECT * FROM `$safe_table` WHERE id='$uid' AND online='1' LIMIT 1");
  } else {
    // ** Guest lookup by username='guest' only
    $safe_user = mysqli_real_escape_string($conn, $ses['username']);
    $result = pw_dbtoarray("SELECT * FROM `$safe_table` WHERE username='$safe_user' AND online='1' LIMIT 1");
  }

  if ($GLOBALS['mysql']['num_rows'] > 0) {
    $ses['chkaccess'] = $result[1];
  } else {
    $system[] = $echo['msg'] = "chkuser->Mysql: No Login user {$ses['username']}";
  }

  // ** Check AccIp
  if (($result[1]['acc_ip'] ?? '')=='1' && !empty($ses['guest_ip']) && !strstr((string)($result[1]['guest_ip'] ?? ''), (string)$ses['guest_ip']))
    { $echo['msg']="<br>{$result[1]['guest_ip']} Sorry there is an IP block on this Account.<br>Ip: {$ses['guest_ip']}<br>"; return FALSE; }

  // ** Make $ses[admin] $ses[access] $ses[deny] $ses['users_id'] and add usergroup settings
  $safe_group = mysqli_real_escape_string($conn, $ses['chkaccess']['usergroup'] ?? '');
  $resacc = pw_dbtoarray("SELECT * FROM `$safe_table` WHERE username='$safe_group'");
  $resacc1 = $resacc[1] ?? [];
  $ses['users_id'] = $ses['chkaccess']['id'] ?? null;
  $ses['admin']    = ($ses['chkaccess']['admin'] ?? '')."\n".($resacc1['admin'] ?? '');
  $ses['deny']     = ($ses['chkaccess']['deny']  ?? '')."\n".($resacc1['deny']  ?? '');
  $ses['access']   = ($ses['chkaccess']['access']?? '')."\n".($resacc1['access']?? '');
  if (!strstr($ses['chkaccess']['home']     ?? '', '/'))  $ses['chkaccess']['home']     = $resacc1['home']     ?? '';
  if (!strstr($ses['chkaccess']['settings'] ?? '', '.'))  $ses['chkaccess']['settings'] = $resacc1['settings'] ?? '';
  if (!strstr($ses['chkaccess']['config']   ?? '', '.'))  $ses['chkaccess']['config']   = $resacc1['config']   ?? '';
  if (!strstr($ses['chkaccess']['style']    ?? '', '.'))  $ses['chkaccess']['style']    = $resacc1['style']    ?? '';
  if (!strstr($ses['chkaccess']['host']     ?? '', '.'))  $ses['chkaccess']['host']     = $resacc1['host']     ?? '';
  $set['accesschanged'] = 1;
 } else $set['accesschanged'] = 0;

 // ** Chk Access
 $ses['editacc'] = &$ses['admacc'];
 if (pw_cklncttxtln($REDIRECT_URL, $ses['admin'])==1)          { $ses['admacc']=1; }
 if ($ses['chkaccess']['settings']=='admin.php')                { $ses['admacc']=1; }
 if (pw_cklncttxtln($REDIRECT_URL, $ses['deny'])==1)           { $ses['admacc']=0; return FALSE; }
 if (pw_cklncttxtln($REDIRECT_URL, $ses['access'])==1)         { return TRUE; }
 $ses['admacc']=null; return FALSE;
}

class chkusr
{ var $chkaccess = null;
  function __construct ()
{ GLOBAL $errors,$system,$REDIRECT_URL,$ses,$_POST,$_GET,$set,$echo;
 $this->chkaccess = &$ses['chkaccess'];
 if (!isset($set['userstable'])) $set['userstable'] = 'users';
 if (!isset($GLOBALS['_mysqli'])) { echo '<center><b>No DB connection in chkusr.</b></center>'; exit; }
 $conn = $GLOBALS['_mysqli'];
 $safe_table = mysqli_real_escape_string($conn, $set['userstable']);

 // *** Log UIDs — sanitize input
 if (isset($_GET['uid']) && (int)$_GET['uid'] > 0) {
   $uid    = (int)$_GET['uid'];
   $sessid = mysqli_real_escape_string($conn, $ses['phpsessid']);
   $ip     = mysqli_real_escape_string($conn, $ses['guest_ip']);
   $fwd    = mysqli_real_escape_string($conn, $ses['guest_fwd']);
   $dns    = mysqli_real_escape_string($conn, $ses['guest_dns']);
   mysqli_query($conn, "INSERT INTO uidlog (uid,phpsessid,guest_ip,guest_fwd,guest_dns) VALUES ('$uid','$sessid','$ip','$fwd','$dns')");
 }

 // *** Login User (bcrypt password_verify) ****
 if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['login']))
 {
   $login_ip     = mysqli_real_escape_string($conn, $ses['guest_ip'] ?? $_SERVER['REMOTE_ADDR']);
   $now          = time();
   $window_start = $now - 600; // 10-minute rolling window

   // Clean up old attempts to keep the table tidy
   mysqli_query($conn, "DELETE FROM login_attempts WHERE ts < '$window_start'");

   // Count recent failed attempts from this IP
   $att_res   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip='$login_ip' AND ts >= '$window_start'");
   $att_row   = mysqli_fetch_assoc($att_res);
   $att_count = (int)($att_row['cnt'] ?? 0);

   if ($att_count >= 10) {
     $system[] = $echo['msg'] = $ses['accesserror'] = "Too many failed login attempts. Please wait a few minutes and try again.<br><br>";
     pw_systemlog('chkusr3.php', 'BruteForce:Blocked', '', array('ip' => $login_ip, 'attempts' => $att_count));
   } else {
     $safe_u  = mysqli_real_escape_string($conn, $_POST['username']);
     // Query by username/email only — never put the password in the WHERE clause
     $accres  = pw_dbtoarray("SELECT * FROM `$safe_table` WHERE (username='$safe_u' OR email='$safe_u') AND online='1' LIMIT 1");
     $loginOk = false;

     if ($GLOBALS['mysql']['num_rows'] > 0) {
       $stored_hash = $accres[1]['hpassword'] ?? '';
       if ($stored_hash && password_verify($_POST['password'], $stored_hash)) {
         $loginOk = true;
         // Re-hash if cost factor is outdated
         if (password_needs_rehash($stored_hash, PASSWORD_BCRYPT)) {
           $new_hash  = password_hash($_POST['password'], PASSWORD_BCRYPT);
           $safe_hash = mysqli_real_escape_string($conn, $new_hash);
           $uid       = (int)$accres[1]['id'];
           mysqli_query($conn, "UPDATE `$safe_table` SET hpassword='$safe_hash' WHERE id='$uid'");
         }
       } elseif (!$stored_hash && isset($accres[1]['password']) && $accres[1]['password'] !== '*' && $accres[1]['password'] === $_POST['password']) {
         // Fallback: still has plaintext — hash on the fly and migrate
         $new_hash  = password_hash($_POST['password'], PASSWORD_BCRYPT);
         $safe_hash = mysqli_real_escape_string($conn, $new_hash);
         $uid       = (int)$accres[1]['id'];
         mysqli_query($conn, "UPDATE `$safe_table` SET hpassword='$safe_hash', password='*' WHERE id='$uid'");
         $loginOk = true;
       }
     }

     if ($loginOk) {
       $ses['username']   = $accres[1]['username'];
       $ses['users_id']   = $accres[1]['id'];
       unset($ses['password']); // never store the password in session
       unset($ses['chkaccess']);
       $loglogin            = 1;
       $ses['accesserror']  = '';
       // Clear failed attempts for this IP on successful login
       mysqli_query($conn, "DELETE FROM login_attempts WHERE ip='$login_ip'");
     } else {
       // Record failed attempt
       mysqli_query($conn, "INSERT INTO login_attempts (ip, ts) VALUES ('$login_ip', '$now')");
       $system[] = $echo['msg'] = $ses['accesserror'] = "Sorry UserName Password Not Found<br><br>";
       pw_systemlog('chkuser3.php', 'NotFound:UserPass', '', array('username' => $_POST['username']));
     }
   }
 }

 // *** Login User By usrid phpsessid (legacy — kept for compatibility)
 if (isset($_GET['usrid']))
 { $safe_sid = mysqli_real_escape_string($conn, $_GET['usrid']);
   $accres   = pw_dbtoarray("SELECT * FROM `$safe_table` WHERE phpsessid='$safe_sid' AND online='1'");
   if ($GLOBALS['mysql']['num_rows'] > 0) {
     $ses['username']  = $accres[1]['username'];
     $ses['users_id']  = $accres[1]['id'];
     unset($ses['password']);
     unset($ses['chkaccess']);
     $loglogin = 1;
     $ses['accesserror'] = '';
   } else {
     $system[] = $echo['msg'] = $ses['accesserror'] = "Sorry ID not found";
     pw_systemlog('chkuser.php3', 'NotFound:login ID', '', '', $_POST, $_GET);
   }
 }

 // *** Logout User — reset session auth state
 if (isset($_GET['logout']) or isset($logout) or isset($_POST['logout']))
 { unset($ses['username']); unset($ses['users_id']); unset($ses['password']); unset($ses['chkaccess']); $ses['admacc']=0; }

 // *** RUN ACCESS CONTROL And echo Exit when Denied
 if (!pw_acctrl()) { echo $echo['msg']."<br>Access Denied !<br>"; exit; }

 // *** LogLogin
 if (isset($loglogin)) pw_systemlog('chkuser3.php', 'Login', '', array('username' => $_POST['username'] ?? ''));

 // *** Set StyleSheet from Usertable
 if (strstr($ses['chkaccess']['style'] ?? '', '.')) $set['stylesheet'] = $ses['chkaccess']['style'];

 // *** Login redirect from Mysql
 if ($REDIRECT_URL != ($ses['chkaccess']['home'] ?? '') and strstr($ses['chkaccess']['home'] ?? '', '/') and $set['accesschanged'] > 0)
   { header("location:{$ses['chkaccess']['home']}"); exit; }

 // *** Login redirect from Settings
 if (!empty($set['loginredirect']) and $REDIRECT_URL != $set['loginredirect'] and strstr($set['loginredirect'],'/') and $set['accesschanged'] > 0)
   { header("location:{$set['loginredirect']}"); exit; }

}
var $ver='phpwotan.com CHKUSR Wotan3 Access Control V3.4 | bcrypt/PHP8 2026';
}
if (!is_array($ses)) { echo '<center><b>Wotan3 Access Control V3.4 Module: No Session Detected.</b></center>'; exit; }
?>
