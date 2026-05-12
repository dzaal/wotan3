<?php
class fileread
{ var $filename='';			// * Filename Or FilePath to open
  var $getimgsize=1; 		// * Get Image Size when file .jpg
  var $excl='_';			// * Simple Filter files that contain Char $excl
  var $result=array(); 	    // * Results Array

  function openfile($filename='')
  { if ($filename) $this->filename=&$filename;
    $sfilename=pwsp($filename);
    if (file_exists($sfilename)) return implode('',file($sfilename));
    else return $this->errors[]="File $sfilename not found<br>";
  }

  // *** Old dirtoarray
  function dirtoarray($path='',$excl='_')
  { $path=pwsp($path);$n=0;
    if (!file_exists($path)) { $this->errors['dirtoarray']='Could not open '.basename($path).'<br>';return; }
 	  $opendir=opendir($path); $return=array(); while($readdir = readdir($opendir))
    {  $this->result[$n]="$readdir ";
    if (strstr($readdir,$excl)) $readdir=null;
	  if ($readdir and $readdir!=='.' and $readdir!=='..' and is_dir($path.$readdir))
	 	 	 { $n++;$return['dirs'][$n]=$readdir;$return['dirctime'][$n]=filectime($path.$readdir); }
	  if ($readdir and $readdir!=='.' and $readdir!=='..' and !is_dir($path.$readdir))
	 		{ $n++;$return['files'][$n]=$readdir;$return['filectime'][$n]=filectime($path.$readdir);
	 			$return['filesize'][$n]=filesize($path.$readdir);
	 			if ($this->getimgsize and (strstr(strtolower($readdir),'.jpg') or strstr(strtolower($readdir),'.gif')))
	 			$return['imgsize'][$n]=getimagesize($path.$readdir);
	 		}
    }
  closedir($opendir);return $return;
  }

  // *** New dir2array
  function dir2array($filename='',$excl='')
  { if($this->filename) $path=pwsp($this->filename);else $this->filename=$path=pwsp($filename);
    if($excl) $this->excl=$excl;$n=0;
    if (!file_exists($path)) { $this->errors['dir2array']="Could not open $path <br>";return; }
 	  $opendir=opendir($path); $return=array(); while($readdir = readdir($opendir))
    { if (strstr($readdir,$this->excl)) $readdir=null;
	  if ($readdir and $readdir!='.' and $readdir!='..' and is_dir($path.$readdir))
	 	 	 { $n++;$return[$n]['dirs']=$readdir;$return[$n]['dirctime']=filectime($path.$readdir); }
	  if ($readdir and $readdir!='.' and $readdir!='..' and !is_dir($path.$readdir))
	 		{ $n++;$return[$n]['file']=$readdir;$return[$n]['filectime']=filectime($path.$readdir);
	 			$return[$n]['filesize']=filesize($path.$readdir);
	 			if ($this->getimgsize and (strstr(strtolower($readdir),'.jpg') or strstr(strtolower($readdir),'.gif')))
	 			$return[$n]=$return[$n]+getimagesize($path.$readdir);
	 		}
    }
  closedir($opendir);$this->result=$return;return $return;
  }
var $ver='phpwotan.com FileRead V3.101 Pw3-211205-100106-241106';
}
?>
