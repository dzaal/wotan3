<?php
// *** Check Access
if (!session_id() or $ses['users_id']<1) 
{ echo '<br><center><b>Access Denied<br>Please try to Login (again). </b></center>';exit; }


// *** Filesystem Functions **** 190404

// *** $path Mkdirs  **** PW221203
function pw_mkdirs($mkdir,$chmod='0777')
{ 
 if (!$mkdir) return;
 if (is_array($mkdir)) $mkdirs=$mkdir; else $mkdirs[0]=$mkdir;
 foreach($mkdirs as $key => $val)
 if (!file_exists($val))
 if (!mkdir ($val)) return "Could not make $val<br>";else
 @chmod ($val,octdec($chmod));
}

function pw_mkfiles($mkfile,$chmod='0777')
{ 
 if (!$mkfile) return;
 if (is_array($mkfile)) $mkfiles=$mkfile;else $mkfiles[0]=$mkfile;
 foreach($mkfiles as $key => $val)
 if (!touch ($val)) return "Could not make $val<br>"; else
 @chmod ($val,octdec($chmod));
}

function pw_writecontent ($pfname,$content,$maxsize=102400)
{if (!is_writable($pfname)) return "No Write Permission to $pfname<br>";
 $fopen=fopen ($pfname,'wb');$size=fwrite ($fopen,$content,$maxsize);
 fclose($fopen);
 return "File Saved ".basename($pfname)." $size bytes<br>";
}

function pw_readfile($pfname)
{ 
 if (file_exists($pfname))
 return implode('',file($pfname));else
 $GLOBALS['echo']['msg'].="File $pfname not found<br>";
}

function pw_copyfiles($dir,$dest,$chmod='0777')
{ $return=null;
$files=pw_dirtoarray($dir,'file','');
if (!is_array($files['list'])) return "No Files Found in $dir";
foreach($files['list'] as $key => $val)
if (copy ($dir.$val,"$dest/$val")) { @chmod ("$dest/$val",octdec($chmod));$return.="$dest/$val<br>"; }
return $return;
}

function pw_copydir($dir,$dest,$chmod='0777')
{ $return="No Files Found in $dir";
$files=pw_dirtoarray($dir,'dirfile','showall');
if (is_array($files['list'])) 
 { 	$return=null;foreach($files['list'] as $key => $val) 
	if ($val and $val!='.' and $val!='..')
	{	if (@is_dir($dir.$val.'/')) { pw_mkdirs($dest.$val.'/');@chmod ($dest.$val.'/',octdec($chmod));$return.=pw_copydir($dir.$val.'/',$dest.$val.'/');$return.="Dir: $dir$val/ <br>"; }
		if (@is_file($dir.$val)) { copy ($dir.$val,$dest.$val);$return.="$dest$val<br>"; }
 }	}
return $return;
}

function pw_rmdir($dir)
{ $return="No Files Found in $dir";
$files=pw_dirtoarray($dir,'dirfile','showall');
if (is_array($files['list'])) 
 { 	$return=null;foreach($files['list'] as $key => $val) 
	if ($val and $val!='.' and $val!='..')
	{	if (@is_dir($dir.$val.'/')) { $return.=pw_rmdir($dir.$val.'/');$return.="Dir: $dir$val/ <br>"; }
		if (@is_file($dir.$val)) { unlink ($dir.$val);$return.="File: $dir$val <br>"; }
 }	}
rmdir ($dir);return $return;
}

// *** END Filesystem Functions ****

// *** Set General vars
$fileinfo=chr(rand(97,122)).chr(rand(49,57)).chr(rand(97,122)).$fileinfo; //Refresh Fix
if(!$exid) $exid=$ses['users_id']; else $exid=(string)$exid;
if($imgpath and !$destpath) $destpath=$imgpath;
if(!$imgpath and !$destpath) { 
$destpath=str_replace('/admin','',str_replace('/Member','',str_replace('/manage','',str_replace('Edit','',getcwd()) ))).'/';
//pw_systemlog('/php/filehandler/','Warning: No destination path');
}

//Get next Autoincrement ID when there is no $id
if (($_POST['addnewitem'] or $_POST['upditem'] or $_POST['setfnid']) and $table and $id<1) { unset($idquery);
  $idquery=mysqli_query(_pw_mysqli(), "SHOW TABLE STATUS FROM {$mysql['selectdb']} like '$table'");echo mysqli_error(_pw_mysqli());
  if($idquery) $tableinfo=mysqli_fetch_array($idquery);
  $id=$tableinfo['Auto_increment'];
} if($id<1) $id="nid";

// *** File Upload
//print_r($_FILES);//Debugline
if (count($_FILES)>0 and $destpath) foreach($_FILES as $key => $val) {

	//Check $destpath
	$destpath=$destpath."$key/";//echo $destpath.'<br>';
	if (!file_exists($destpath)) { pw_mkdirs($destpath);
	pw_systemlog('/php/filehandler/',"CMD: mkdir /$key/"); }

	//Log
	$filedata="Filedata:\n".print_r($_FILES[$key],TRUE)."\n".$destpath."\n".$destpath."\n";
	if($val['type']) pw_systemlog('/php/filehandler/',"FileUpload $uplfile_type $uplfile_name",$filedata);

    //Orginal Filename FileInfoSet $ext $_POST[ico]
    if (!$_POST[$key.'ofn'] and $val['name']) $_POST[$key.'ofn']=$val['name'];
    if ($_POST[$key.'info']) $fileinfo='-'.$_POST[$key.'inf'];
	  $ext=strstr($val['name'],'.');$_POST[$key.'ico']=strtolower(substr($ext,1,4));

    //Images
    if ( (strstr($val['type'],'jp') or strstr($val['type'],'gif')) and $mwidth>10 ) { $imageuploaded=1;
    if (!$_POST[$key.'_alt']) $_POST[$key.'_alt']=substr($val['name'],0,strpos($val['name'],'.'));}

	
  //Jpg Upload + Resize
  if (strstr($val['type'],'jp') and $mwidth>10) {
	 if ($tnwidth>10) { if(!$_POST[$key.'tn']) $_POST[$key.'tn']="$exid-$id-$tnwidth$fileinfo.jpg";
 			$resizelog.=pw_jpgresize ($val['tmp_name'],$destpath.$_POST[$key.'tn'],$tnwidth,$tnheight,90,1,0,0,0); }
   if ($mwidth>10)  { if(!$_POST[$key]) $_POST[$key]="$exid-$id-$mwidth$fileinfo.jpg"; 
  		$resizelog.=pw_jpgresize ($val['tmp_name'],$destpath.$_POST[$key],$mwidth,$mheight,90,1,0,0,0); }
   if ($bmwidth>10) { if(!$_POST[$key.'b']) $_POST[$key.'b']="$exid-$id-$bmwidth$fileinfo.jpg";
  		$resizelog.=pw_jpgresize ($val['tmp_name'],$destpath.$_POST[$key.'b'],$bmwidth,$bmheight,80,1,0,0,0); }
   $echo['msg'].="Jpg Image $key $val[name] Uploaded<br>";
	 pw_systemlog('/php/filehandler/',"Jpg resize Log $uplfile_name","$resizelog\n\r");}
	
	//Gif Upload + Size Check
	if (strstr($val['type'],'gif') and $mwidth>10) {
	 //Make gif Filenames for mysql
   if(!$_POST[$key.'tn']) $_POST[$key.'tn']="$exid-$id-$mwidth$fileinfo.gif";
   if(!$_POST[$key]) 			$_POST[$key]="$exid-$id-$mwidth$fileinfo.gif";
   if(!$_POST[$key.'b'])	$_POST[$key.'b']="$exid-$id-$mwidth$fileinfo.gif";
   //Check Size
   $imgsize=getimagesize($val['tmp_name']);
   if ($imgsize[0]<=$mwidth and $imgsize[1]<=$mheight and copy ($val['tmp_name'],$destpath.$_POST[$key]))
 	 $echo['msg']="Gif $key $val[name] Uploaded<br>";
   else { $echo['msg']="Gif $key $val[name] not Uploaded. maxwidth:$mwidth px maxheight:$mheight px<br>
		Uploaded Image Size: $imgsize[0]/$imgsize[1]. Gifs are not resized by the website.<br>
		Please resize your gif before uploading or use jpg.<br>";unset ($_POST);unset($upditem); }}

	// *** Upload File
	if ($val['type'] and !$imageuploaded){
	 $_POST[$key]=$exid."-$id-".$fileinfo.$ext;
	 if (copy ($val['tmp_name'],$destpath.$_POST[$key]))
     $echo['msg']="File $key $val[name] Uploaded<br>"; }

	//reset $destpath for multifileupload
	$destpath=dirname($destpath).'/';
 } // End FileUpload

// *** Delete Files
if (($_GET['delid']>0 or $_POST['delid']>0) and is_array($post['delfile']) and $mysqldelete>0) 
	foreach($post['delfile'] as $key => $val)
	if (@unlink($destpath.$val)) $echo['msg'].="Deleted: $val | ";

// *** Cleanup Unused Images/Files PW150705
if ($secvars['filehandeler']['cleanup'] and !$ses['imgdeleted'][$table])
{	$ses['imgdeleted'][$table]=1;$allimages=null;unset($mysql_query);
	$fields=explode(',',$secvars['filehandeler']['cleanup']);
	$mysql_query=mysqli_query(_pw_mysqli(), "select {$secvars[filehandeler][cleanup]} from $table");
    if ($mysql_query)
	{ while($linedata=mysqli_fetch_row($mysql_query)) foreach($linedata as $key => $val) $allimages.=" $val ";
	  $opendir=opendir($destpath.$fields[0]);while($readdir = readdir($opendir))
      if (!strstr($allimages," $readdir ") and $readdir!='noimg.jpg' and $readdir!='blank.gif' )
		if (is_file($destpath.$fields[0].'/'.$readdir)) 
	  { unlink($destpath.$fields[0].'/'.$readdir); $echo['msg'].="Cleanup: File $readdir Deleted<br>"; } 
	  closedir($opendir);
	}
}

// *** Remove Image
if ($remimg) { $img=$ses['lastresult'][$remimg];$ofn=$ses['lastresult'][$remimg.'ofn'];
if (strstr($img,'-'.$id.'-') and strstr($img,$ses['users_id'].'-'))
{ $_POST[$remimg.'ofn']='';$_POST[$remimg.'_alt']='';
	$_POST[$remimg.'tn']='';$_POST[$remimg]='';$_POST[$remimg.'b']='';
  $imgtn=$ses['lastresult'][$remimg.'tn'];$imgb=$ses['lastresult'][$remimg.'b'];
  @rename ($destpath.$imgtn,$destpath.'0'.substr($imgtn,strpos($imgtn,'-'),99));
  @rename ($destpath.$img,$destpath.'0'.substr($img,strpos($img,'-'),99));
  @rename ($destpath.$imgb,$destpath.'0'.substr($imgb,strpos($imgb,'-'),99));
  $echo['msg'].="Image $remimg $ofn Removed<br>";
}}

// *** Make Files
if ($secvars['fh_makefiles']) 
{ $echo['msg'].=pw_mkfiles($secvars['fh_makefiles']); }

// *** Save data to file
if ($secvars['fh_savedata'])
{ $content=str_replace("\'","'",str_replace('\"','"',$secvars['fh_savedata']['data']));
  $error=pw_writecontent ($secvars['fh_savedata']['file'],$content,$maxsize=102400);
  if ($error) $echo['msg'].=" $error <br>"; else $echo['msg'].=" FileSaved <br>";
}

// *** Load data from file
if ($secvars['fh_loaddata']) 
{ $result[basename(str_replace('.','',$secvars['fh_loaddata']))]=pw_readfile($secvars['fh_loaddata']); }

// *** Set New $_POST vars in $post
$post=$_POST+$post;

//PHP psywizard@mail.com File IO + jpg resizer (gd_lib) PW110205 LUPD260705-181105
?>