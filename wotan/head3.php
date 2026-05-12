<?php
// *** Empty and set this vars when Register GLOBALS is set php.ini
$starttime=microtime(true); $logederror=$errors=''; $pwcd=0;
$vars=$pget=$ppost=$secvars=$set=$result=$errors=$system=$wotan=$echo=array();

// *** $REDIRECT_URL $_SERVER['DOCUMENT_ROOT']
if(substr($_SERVER['DOCUMENT_ROOT'],strlen($_SERVER['DOCUMENT_ROOT'])-1,1)=='/')
$_SERVER['DOCUMENT_ROOT']=substr($_SERVER['DOCUMENT_ROOT'],0,strlen($_SERVER['DOCUMENT_ROOT'])-1);
$REDIRECT_URL=strtolower(str_replace($_SERVER['DOCUMENT_ROOT'],'',getcwd()).'/');

// *** Set Vars
$set['httpaddr']="http://{$_SERVER['HTTP_HOST']}$REDIRECT_URL";
$set['hsdata']='����ۈ����ے������؈���ь������۠�������ط��������آ�������ߒ�����ؚ������������ќ����۠�������ط��������آ�ۈ���ݣ�����ے����ݣ����я�������۸������ӫ�������';
$set['cpath']=str_replace('/','-',$REDIRECT_URL);
$setflname=$set['cpathay']=explode('/',$REDIRECT_URL);

// *** Error Handling/Reporting
error_reporting(E_ALL);
$errorfile="$_SERVER[DOCUMENT_ROOT]/share/log/errors.log";
if (!file_exists($errorfile)) touch ($errorfile);
ini_set('error_log',$errorfile);
// Set WEBMASTER_HOSTNAME in config.php to your office hostname or IP for developer error display
$_is_webmaster = (!empty($webmastername) && $_SERVER['REMOTE_ADDR'] === gethostbyname($webmastername));
ini_set('display_errors', 0);
ini_set('log_errors', 1);
if ($_is_webmaster) {
    set_error_handler(function($no, $str, $file, $line) {
        if (error_reporting() === 0) return false;
        $GLOBALS['_vadump'][] = array('type'=>$no,'message'=>$str,'file'=>str_replace($_SERVER['DOCUMENT_ROOT'],'',$file),'line'=>$line);
        return false;
    }, E_ALL);
}

// *** Set website root also in include path ****
ini_set('include_path',ini_get('include_path').':'.$_SERVER['DOCUMENT_ROOT'].'/');

// *** Start Session
ini_set("session.gc_maxlifetime","3600");
ini_set("session.gc_probability","10");
ini_set("session.gc_divisor","100");
session_save_path($_SERVER['DOCUMENT_ROOT']."/share/tmp/");
session_start();
header("Connection: keep-alive");
if (empty($_SESSION['get'])) $_SESSION['get']=array();
if (empty($_SESSION['post'])) $_SESSION['post']=array();
if (empty($_SESSION['ses'])) $_SESSION['ses']=array();
if (empty($_SESSION['ses'][$set['cpath']])) $_SESSION['ses'][$set['cpath']]=array();
if (empty($_SESSION['ses'][$set['cpath']]['pget'])) $_SESSION['ses'][$set['cpath']]['pget']=array();
if (empty($_SESSION['ses'][$set['cpath']]['ppost'])) $_SESSION['ses'][$set['cpath']]['ppost']=array();
$get=&$_SESSION['get'];$post=&$_SESSION['post'];$ses=&$_SESSION['ses'];// * Set Old session vars
$pget=&$_SESSION['ses'][$set['cpath']]['pget'];$ppost=&$_SESSION['ses'][$set['cpath']]['ppost'];
if (!isset($ses['phpsessid'])) $PHPSESSID=$ses['phpsessid']=session_id();
pw_pg2ses();$ses['hits']=($ses['hits'] ?? 0)+1;

// *** Save $_GET $_POST in session for website and webpage
function pw_pg2ses()
{ global $_GET,$set,$pget,$ppost;
  if (count($_GET)>0)
  { $_SESSION['get']=$_GET+$_SESSION['get'];
    $_SESSION['ses'][$set['cpath']]['pget']=$_GET+$_SESSION['ses'][$set['cpath']]['pget']; }
  if (count($_POST)>0)
  { $_SESSION['post']=$_POST+$_SESSION['post'];
    $_SESSION['ses'][$set['cpath']]['ppost']=$_POST+$_SESSION['ses'][$set['cpath']]['ppost']; }
  if(isset($_GET['pset']) && $_GET['pset']==1) { $nget=$pget+$_SESSION['get']; $_SESSION['get']=$nget; } // *** Set pget in get for older pages
}

// *** Create Main SesVars — do not trust HTTP_CLIENT_IP (can be spoofed)
if (!isset($ses['guest_ip'])) {
    $ses['guest_ip'] = $_SERVER['REMOTE_ADDR'];
    // Only use forwarded IP if you are behind a trusted reverse proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
            $ses['guest_fwd'] = $forwarded;
        }
    }
    if (!isset($ses['guest_fwd'])) $ses['guest_fwd'] = $_SERVER['REMOTE_ADDR'];
}
if (!isset($ses['guest_ref']))   { $ses['guest_ref']=$_SERVER['HTTP_REFERER'] ?? ''; }
if (!isset($ses['guest_dns']))   { $ses['guest_dns']=gethostbyaddr($ses['guest_ip']); }
if (!isset($ses['timeses']))     { $ses['timeses']=time(); }

// *** Log PHP errors from error_log file (no full GLOBALS dump for security)
if (file_exists($errorfile) && filesize($errorfile)>0)
{   $logederror="IP: {$ses['guest_ip']}  DNS: {$ses['guest_dns']} \r\n\r\n".
    'PHP error on http://'.$_SERVER['HTTP_HOST'].($ses['pred_url'] ?? '')."\r\n".
    implode('',file($errorfile))."\r\n";
    // Optionally mail errors to your webmaster:
    // mail($webmastereml, "{$_SERVER['HTTP_HOST']} Error Detected", $logederror);
    $fopen=fopen ($errorfile,'wb');fwrite ($fopen,'',1);fclose($fopen);
} $ses['pred_url']=$REDIRECT_URL;

// *** Make systemlog in db systemlog **** Pw3-010106-240206
function pw_systemlog($fileline,$msg,$data=null,$pwPOST=null,$pwpost=null,$pwGET=null,$pwget=null)
{ global $ses,$set;
  if (!isset($GLOBALS['_mysqli'])) return;
  $conn = $GLOBALS['_mysqli'];
  $data    = mysqli_real_escape_string($conn, print_r($data,true));
  $pwPOST  = mysqli_real_escape_string($conn, print_r($pwPOST,true));
  $pwpost  = mysqli_real_escape_string($conn, print_r($pwpost,true));
  $pwGET   = mysqli_real_escape_string($conn, print_r($pwGET,true));
  $pwget   = mysqli_real_escape_string($conn, print_r($pwget,true));
  $uid     = (int)($ses['users_id'] ?? 0);
  $ip      = mysqli_real_escape_string($conn, $ses['guest_ip'] ?? '');
  $fl      = mysqli_real_escape_string($conn, $fileline);
  $mg      = mysqli_real_escape_string($conn, $msg);
  mysqli_query($conn, "INSERT INTO systemlog (users_id,guest_ip,properties,msg,ses,_post,post,_get,get) VALUES
   ('$uid','$ip','$fl','$mg','$data','$pwPOST','$pwpost','$pwGET','$pwget')");
  if (isset($set['systemlogdays']) && $set['systemlogdays']>6) {
    mysqli_query($conn, "DELETE LOW_PRIORITY FROM systemlog WHERE id>'19' AND UNIX_TIMESTAMP(timestamp)<'".(time()-24*3600*$set['systemlogdays'])."'");
  }
  return mysqli_error($conn);
}
$ses['verdate']['head3']=70707;
// *** PHPCode phpwotan.com head v3.31 | mysqli/PHP8 upgrade 2026
?>
