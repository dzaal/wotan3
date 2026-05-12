<?php
// Dependencies
//require_once ($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/fileread.php');
//require_once ($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/gd_lib.php');

class ftpwrite
{ var $host='localhost';        // * Server Address
  var $user='anonymous';        // * Username
  var $pass='ftp@phpwotan.com'; // * Password
  var $pasv='TRUE';             // * PASV (FTPdata connect methode)
  var $homedir='/';             // * HomeDir
  var $res='';                  // * FTP Connection Resource
  var $result=array(); 	        // * Results Array
  var $system=array();	        // * System Messages
  //var $errors=notset;             // * Errors
  var $systemlog=1;		        // * Log to Database systemlog
  var $filename='';		        // * Filename to Write
  var $content='';		        // * Content to write
  var $stripslashes=0;	        // * Stripslashes use when content comes from post and magicquotes=on
  var $maxsize=327680;	        // * FileSize Limit (def 320k)
  var $mkdir='';                // * Make Directory('s)
  var $mkfile='';               // * Create file('s) from srcfile
  var $srcfile='';              // * Source File (empty file)
  var $gifjpg=1;                // * Convert Gifs to jpg and resize (gif animation will be lost!)
  var $pngjpg=1;                // * Convert pngs to jpg and resize
  var $uplall=1;                // * 1=Upload all Incoming File Types 0=images only
  var $copyorg=0;               // * Copy Original Image aswell (this only makes sence for resize images)

  // *** Pw3-111106
  function connect ($input)
  { eval (arraytoclass('$input'));
    $this->res=ftp_connect ($this->host);  // * Connect FTP Server
    if(!$this->res) $this->errors[]="&nbsp; Could not connect server $this->host";
    if (@ftp_login ($this->res,$this->user,$this->pass)) // * Login (Auth)
    {  ftp_pasv($this->res,$this->pasv);  // * Set Pasv mode on
       if(!ftp_chdir($this->res,$this->homedir)) $this->errors[]="FTP ERROR: Could not open Homedir $ftp[homedir]"; // * chdir homedir
       $this->pwd=ftp_pwd($this->res); // * pwd
    } else $this->errors[]="FTP ERROR: Auth Failed check user pass"; // * Auth failed
    if (is_array($this['errors'])) return FALSE; return TRUE; // * Return
  }

  // *** Pw3-211205-181106
  function pw_mkdirs($mkdir,$chmod='0755')
  { if (!$mkdir) $mkdir=$this->mkdir; else $this->mkdir=$mkdir;
    if (!$mkdir) return false;
    if (!$this->res) { $this->errors[]='Error mkdir: No FTP resource'; return false; }
    if (is_array($mkdir)) $mkdirs=$mkdir; else $mkdirs[0]=$mkdir;
    foreach($mkdirs as $key => $val)
    { // if (!file_exists(pwsp($val)))
      // $this->size=ftp_size($this->res,$this->homedir.$val); // * Does not work on dirs :|
      if (!ftp_mkdir ($this->res,$this->homedir.$val))
      { $this->errors[]="FTP ERROR: Could not mkdir $val";return false; }
      else { //@ftp_chmod ($this->res,$this->homedir.$val,octdec($chmod)); PHP5 function!
             if (!ftp_site($this->res,'CHMOD '.$chmod.' '.$this->homedir.$mkdir))
             { $this->errors[]="$val CHMOD $chmod Failed"; }
             $this->result['pw_mkdirs']="$val $chmod";
           }
    }
    return true;
  }

  // *** Pw3-181106 UNDERCONS
  function pw_mkfiles ($mkfile,$chmod='0755',$srcfile='../wotan/phpmod/fw_blankfile')
  { if (!$this->srcfile) $this->srcfile=$srcfile; // * FTP needs sourcefile to make new files
    if (!$mkfile) $mkfile=$this->mkfile; else $this->mkfile=$mkfile;
    if (!$mkfile) return false;
    if (!$this->res) { $this->errors[]='Error mkfile: No FTP resource'; return false; }
    if (is_array($mkfile)) $mkfiles=$mkfile;else $mkfiles[0]=$mkfile;
    foreach($mkfiles as $key => $val)
    { $this->size=ftp_size($this->res,$this->homedir.$val); // * Get File Size
      if (!ftp_put($this->res,$this->homedir.$val,$this->homedir.$srcfile,FTP_BINARY))
      { $this->errors[]="FTP ERROR: Could not mkfile $val from $srcfile";return false; }
      else { //@ftp_chmod ($this->res,$this->homedir.$val,octdec($chmod)); PHP5 function!
             if (!ftp_site($this->res,'CHMOD '.$chmod.' '.$this->homedir.$val))
             { $this->errors[]="$val CHMOD $chmod Failed"; }
             $this->result['pw_mkfiles']="$val $chmod";
           }
    }
    return true;
  }

  // *** REGEXP FILENAME FILTER , $spc2und=1 > space to _ , extrasymbols , Filter exsym as nd | 280107
  function pw_fnfltr($filename='',$spc2und=0,$exsym='',$exsynnd='')
  { if (!$filename) $filename=$this->filename; // * Set OBJ filename 
    if($spc2und>0) { $filename=str_replace(' ','_',$filename); $exsym=$exsym.'_*'; }
    preg_match_all("/([a-z0-9]+$exsym)\.*$exsynf/i",$filename,$pres);
    //echo '<br>DEBUG:'.nl2br(print_r($pres,TRUE));
    $return['help']='flnm=Filename &nbsp; ext=Extention &nbsp; flnmext=FileNameExtention &nbsp; nd=NoDots';
    $return['flnmext']=implode($pres[0]);
    $return['ext']=$pres[0][(count($pres[0])-1)];
    $return['flnm']=str_replace('.'.$return['ext'],'',$return['flnmext']);
    $return['flnmextnd']=implode($pres[1]);
    $return['flnmnd']=str_replace($return['ext'],'',$return['flnmextnd']);
  return $return;
  }

var $ver='phpwotan.com FTPWrite V1.004B UNDERCONSTRUCTION Pw3-181106';
}
?>
