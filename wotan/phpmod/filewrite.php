<?php
// Dependencies
require_once ($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/fileread.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/gd_lib.php');

class filewrite
{ var $result=array(); 	// * Results Array
  var $system=array();	// * System Messages
  var $systemlog=1;		// * Log to Database systemlog
  var $filename='';		// * Filename to Write
  var $content='';		// * Content to write
  var $stripslashes=0;	// * Stripslashes use when content comes from post and magicquotes=on
  var $maxsize=327680;	// * FileSize Limit (def 320k)
  var $gifjpg=1;        // * Convert Gifs to jpg and resize (gif animation will be lost!)
  var $pngjpg=1;        // * Convert pngs to jpg and resize
  var $webpjpg=1;       // * Convert webp to jpg and resize
  var $uplall=1;        // * Upload all Incoming File Types
  var $copyorg=0;       // * Copy Original Image aswell (this only makes sence for resize images)

  // *** PW190404 Pw3-211205  
  function pw_mkdirs($mkdir,$chmod='0777')
  { if (!$mkdir) $mkdir=$this->mkdir; else $this->mkdir=$mkdir;
    if (!$mkdir) return false;
    if (is_array($mkdir)) $mkdirs=$mkdir; else $mkdirs[0]=$mkdir;
    foreach($mkdirs as $key => $val)
    if (!file_exists(pwsp($val)))
    if (!mkdir (pwsp($val))) { $this->errors[]="Could not mkdir $val";return false; }
    else { @chmod (pwsp($val),octdec($chmod));
	$this->result['pw_mkdirs']="$mkdir $chmod"; }
    return true;
  }

  // *** PW190404 Pw3-211205
  function pw_mkfiles ($mkfile,$chmod='0777')
  { if (!$mkfile) $mkfile=$this->mkfile; else $this->mkdir=$mkfile;
    if (!$mkfile) return false;
    if (is_array($mkfile)) $mkfiles=$mkfile;else $mkfiles[0]=$mkfile;
    foreach($mkfiles as $key => $val)
    if (!touch (pwsp($val))) { $this->errors[]="Could not mkfile: $val";return false; }
    else { @chmod (pwsp($val),octdec($chmod));
	$this->result['pw_mkfiles']="$mkfile $chmod"; }
    return true;
  }

  // *** PW190404 Pw3-211205
  function pw_fwrite ($filename,$content,$stripslashes='',$maxsize='')
  { if (!$filename) $filename=&$this->filename;
    if (!$content) $content=&$this->content;
    if ($stripslashes>0) $content=stripslashes($content);
    if ($maxsize) $this->maxsize=&$maxsize;else $maxsize=&$this->maxsize;
    if ($maxsize<1) $maxsize=327680;
    $filename=pwsp($filename);
    if (!is_writable($filename)) return "No Write Permission to $filename";
    $fopen=fopen ($filename,'wb');$size=fwrite ($fopen,$content,$maxsize);fclose($fopen);
    $this->result['pw_fwrite']="File Saved ".basename($filename)." $size bytes";
    return $size;
  }

  // *** Copy Files PW190404-100406
  function pw_copyfiles($dir,$dest,$chmod='0777')
  { $return=null;$sdir=pwsp($dir);$sdest=pwsp($dest);
	$files=fileread::dirtoarray($dir,'file','');
	if (!is_array($files['list'])) return "No Files Found in $dir";
	foreach($files['list'] as $key => $val) if (!file_exists($sdest.$val))
	if (copy ($sdir.$val,$sdest.$val)) { @chmod ($sdest.$val,octdec($chmod));
    $return.=$dest.$val.'<br>'; } else $return.='File '.$dest.$val.' Allready Exists.<br>';
    return $return;
  }

  // *** Copy dir(s)/file(s) recursive ** PW190404 Pw3-281205-040106
  function pw_copydir($dir,$dest,$chmod='0777')
  { $return='';$files=fileread::dirtoarray($dir,'showall');
    $sdir=pwsp($dir);$sdest=pwsp($dest);
    if (!file_exists($sdest)) { mkdir($sdest);@chmod($sdest,octdec($chmod));$return="mkDir: $dest<br>"; }
    if(count($files)<1) $return.="No files/dirs found in $dir<br>";
	if (is_array($files['dirs']))
	 { foreach($files['dirs'] as $key => $val) if (!file_exists($sdest.$val.'/'))
		{ $return.=filewrite::pw_copydir($dir.$val.'/',$dest.$val.'/'); }
	 }
	if (is_array($files['files']))
	 { foreach($files['files'] as $key => $val) if (!file_exists($sdest.$val))
		{ copy ($sdir.$val,$sdest.$val); @chmod ($sdest.$val,octdec($chmod)); $return.="cpFile $dir$val<br>"; }
	 }
    if(!$return) $return="No Dirs/Files Copied in $dest<br>";
	return $return;
  }

  // *** Remove dir(s)/file(s) recursive ** PW190404 Pw3-281205
  function pw_rmdir($dir)
  { $return='';$files=fileread::dirtoarray($dir,'showall');$sdir=pwsp($dir);
    if(count($files)<1) $return.="No files/dirs found in $dir<br>";
	if (is_array($files['dirs'])) 
	 { foreach($files['dirs'] as $key => $val) if (file_exists($sdir.$val.'/'))
		{ $return.=filewrite::pw_rmdir($dir.$val.'/'); }
	 }
	if (is_array($files['files']))
	 { foreach($files['files'] as $key => $val) if (file_exists($sdir.$val))
		{ unlink ($sdir.$val); $return.="rmFile $dir$val<br>"; }
	 }
    if (file_exists($sdir)) { rmdir($sdir);$return="rmDir: $dir<br>"; } 
    if(!$return) $return="No Dirs/Files Removed in $dir<br>";
	return $return;
  }

  // *** Cleanup Unused Images/Files PW150705 Pw3b-211205
  function cleanupfiles ($table,$chkfields,$destpath)
  {	$return='';$destpath=pwsp($destpath);
    $GLOBALS['ses']['imgcleanup'][$table]=1;$allimages=null;
	$fields=explode(',',$chkfields);
	$mysql_query=mysqli_query(_pw_mysqli(), "select $chkfields from $table");
    if ($error=mysqli_error(_pw_mysqli())) $this->errors[]=$error;
    if ($mysql_query)
	{ while($linedata=mysqli_fetch_row($mysql_query)) foreach($linedata as $key => $val) $allimages.=" $val ";
	  $opendir=opendir($destpath.$fields[0].'/');while($readdir = readdir($opendir))
      if (!strstr($allimages," $readdir ") and $readdir!='noimg.jpg' and $readdir!='blank.gif' )
		if (is_file($destpath.$fields[0].'/'.$readdir))
	  { unlink($destpath.$fields[0].'/'.$readdir); $return.="$readdir<br>"; $this->result[]="Cleanup: File $readdir Deleted<br>"; }
	  closedir($opendir);
	}
    return $return;
  }

  // *** Delete Files
  function deletefiles($destpath,$filenames)
  { if (!is_array($filenames)) $filenames[1]=$filenames;
	foreach($filenames as $key => $val)
	if (@unlink($destpath.$val)) $this->result[]="Deleted: File $val";
	else $this->result[]="Unable to Deleted: File $val";
    return $key;
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

  // *** FileUpload Image Resize PW110205 Pw3b-211205
  function uploadfiles ($database='',$table='',$id='',$resizes='',$destpath='',$fileinfo='')
  { global $ses,$_FILES,$_POST,$echo;
    if (count($_FILES)>0)
    { // ** Set General vars
    $this->system[]=$_FILES;
    $fileinfo=chr(rand(97,122)).chr(rand(49,57)).chr(rand(97,122)); //Refresh Fix
    if(!$database) $database=$GLOBALS['mysql']['selectdb'];
    if(empty($exid)) $exid=$ses['users_id'] ?? '';
    if(!$destpath) { $destpath=str_replace('/admin','',str_replace('/manage','',str_replace('edit','',getcwd()) )).'/'; }
    else $destpath=pwsp($destpath);

    // ** Get next Autoincrement ID when there is no $id
    if (!$id and $database and $table)
	{  $idquery=mysqli_query(_pw_mysqli(), "SHOW TABLE STATUS FROM $database like '$table'");
       if ($error=mysqli_error(_pw_mysqli())) $this->errors[]=$error;
       if ($idquery) $tableinfo=mysqli_fetch_array($idquery); $id=$tableinfo['Auto_increment'];
       if ($id<1) $id="nid";
    }

    // ** File Upload
    if ($destpath) foreach($_FILES as $key => $val) if ($val['size'])
    {  // * Check $destpath
	   $destpath=$destpath."$key/";$uploadmsg=0;
	   if (!file_exists($destpath)) { filewrite::pw_mkdirs($destpath);
	   //pw_systemlog('filewrite::uploadfiles ',"CMD: mkdir /$key/"); 
     }
	   // * Log
       $filedata="Filedata:\n".print_r($_FILES[$key],TRUE)."\n".$destpath."\n".$destpath."\n";
       //if($val[type]) pw_systemlog('filewrite.php',"FileUpload $uplfile_type $uplfile_name",$filedata);

       // * Orginal Filename FileInfoSet $ext
       if (empty($_POST[$key.'ofn']) and $val['name']) $_POST[$key.'ofn']=$val['name'];
       if (!empty($_POST[$key.'inf'])) $fileinfo=$_POST[$key.'inf'];
	   $ext=strstr($val['name'],'.');$_POST[$key.'ico']=strtolower(substr($ext,1,4));
       if (empty($_POST[$key.'_alt'])) $_POST[$key.'_alt']=substr($val['name'],0,strpos($val['name'],'.'));

       // * GifJpg Convert
	   if (strstr($val['type'],'gif') and $this->gifjpg>0)
       {  $src = $val['tmp_name']; $size = getimagesize($src);
          pw_con2jpg ($val['tmp_name'],$val['tmp_name'],$size[0],$size[1],$quality=90);
          $val['type']='jp';
       }

       // * Upload Gif as is (animation is kept)
       if (strstr($val['type'],'gif') and $this->gifjpg<1) $this->uplall=1;

       // * PngJpg Convert
	   if (strstr($val['type'],'png') and $this->pngjpg>0)
       {  $src = $val['tmp_name']; $size = getimagesize($src);
          pw_con2jpg ($val['tmp_name'],$val['tmp_name'],$size[0],$size[1],$quality=90);
          $val['type']='jp';
       }

       // * Upload png as is
       if (strstr($val['type'],'png') and $this->pngjpg<1) $this->uplall=1;

       // * WebpJpg Convert
       if ((strstr($val['type'],'webp') || strtolower($ext)=='.webp') and $this->webpjpg>0)
       {  $src = $val['tmp_name']; $size = getimagesize($src);
          if ($size && !empty($size[0]) && !empty($size[1])) pw_con2jpg($val['tmp_name'],$val['tmp_name'],$size[0],$size[1],$quality=90);
          $val['type']='jp';
       }

       // * Upload webp as is
       if ((strstr($val['type'],'webp') || strtolower($ext)=='.webp') and $this->webpjpg<1) $this->uplall=1;

       // * Jpg Upload + Resize
       if (strstr($val['type'],'jp') and is_array($resizes))
       { if ($this->copyorg<1) $imageuploaded=1;
         reset($resizes);foreach($resizes as $resname => $resize)
         if ($resize['mwidth']>9 and $resize['mheight']>9)
         { if ($resname=='0') $resname='';
 		   if (empty($_POST[$key.$resname])) $_POST[$key.$resname]="$exid-$id-$resize[mwidth]-$fileinfo.jpg";
		   if (!isset($resize['quality'])) $resize['quality']=90;
 		   if (!isset($resize['keepratio'])) $resize['keepratio']=1;
 		   $this->system[]=$resizelog=pw_jpgresize ($val['tmp_name'],$destpath.$_POST[$key.$resname],$resize['mwidth'],$resize['mheight'],$resize['quality'],$resize['keepratio'],0,0,0);
         }
         $this->result[]="Jpg Image $key $val[name] Uploaded<br>";$uploadmsg=1;
	     //if ($this->systemlog>0) pw_systemlog('filewrite::uploadfile',"Jpg $var[name] resize Log",$resizelog);
	   }

       // * Upload File
       $_blocked_exts = ['php','php3','php4','php5','php7','phtml','phar','shtml','cgi','pl','py','sh','htaccess'];
       $_upload_ext = strtolower(ltrim($ext,'.'));
       if (in_array($_upload_ext, $_blocked_exts)) { $this->errors[] = 'Blocked: .' . $_upload_ext . ' files are not allowed.'; continue; }
	   if ($val['type'] and !$imageuploaded and $this->uplall>0)
	   {  $filename=$exid."-$id-".$fileinfo.$ext;
          if (empty($_POST[$key]) or $this->copyorg<1) $_POST[$key]=$filename;
	      if (copy ($val['tmp_name'],$destpath.$filename))
          { if($uploadmsg==0) $this->result[]="File $key $val[name] Uploaded<br>";@chmod($destpath.$filename,0777); }
          //if ($this->systemlog>0) pw_systemlog('filewrite::uploadfile',"File $var[name] $var[type]");
       }
       //reset $destpath for multifileupload
	   $destpath=dirname($destpath).'/';
       $echo['msg']=implode('',$this->result);
     } // * End if $destpath while loop $_FILES
     } // * End if count $_FILES
  } // * END Function Uploadfile
var $ver='phpwotan.com FileWrite V3.141 PW190404 Pw3-221205-150107-280107';
}
?>
