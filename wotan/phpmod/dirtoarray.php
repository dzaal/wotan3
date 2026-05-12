<?php
// *** Wotan4 — PHP 8.3 upgrade Griffioen Grafiek 2026
// *** Make $html from readdirs in $dir and when in $access **** PW170404 LUPD250605
function pw_dirtoarray($path,$dirfile='dir',$excl='_')
{ global $echo,$dateformat;
 if (!file_exists($path)) { $echo['msg']='Could not open '.basename($path).'<br>';return; }
  $opendir=opendir($path);$return['list'][0]='';$return['ctime'][0]='';
 while($readdir = readdir($opendir))
  {//echo "if (strstr($excl,$readdir))<br>";
   if (strstr($excl,$readdir)) $readdir=null;
	 if ($readdir and strstr($dirfile,'dir') and $readdir!=='.' and $readdir!=='..' and is_dir($path.$readdir)) 
	 		{ $return['list'][]=$readdir;$return['ctime'][]=date($dateformat,filectime($path.$readdir)); }
	 if ($readdir and strstr($dirfile,'file') and is_file($path.$readdir)) 
	 		{ $return['list'][]=$readdir;$return['ctime'][]=date($dateformat,filectime($path.$readdir)); }
  }
 closedir($opendir);return $return;
}

// *** Dir to Vars - forexample Automenus -
$ddenydirs = $ddenydirs ?? '_'; $ddenyfiles = $ddenyfiles ?? '_';
if (!empty($wotan['dirtoarray']) && $wotan['dirtoarray']=='1') $path=getcwd().'/'; else $path=$_SERVER['DOCUMENT_ROOT'].($wotan['dirtoarray'] ?? '');
$cwddirs=pw_dirtoarray($path,'dir',$ddenydirs);
$cwdfiles=pw_dirtoarray($path,'file',$ddenyfiles);
$result['dirtoarray']['files']=$cwdfiles['list'];
$result['dirtoarray']['dirs']=$cwddirs['list'];
$result['dirtoarray']['ctime']=$cwdfiles['ctime']+$cwddirs['ctime'];

// *** Debug
//echo $path.$ddenydirs;
//echo'<pre>';print_r($cwdfiles);echo'</pre><br>';
//echo'<pre>';print_r($cwddirs);echo'</pre>';exit;

//PHP psywizard@mail.com PW170404 LUPD300105-010705
?>