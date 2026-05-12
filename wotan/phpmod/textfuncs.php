<?php
// *** Grep piece of text between start & end **** PW031102-170705
function pw_grep($start,$data,$end,$lenstart='a',$lenend=0)
{ $strstr=strstr($data,$start); // get data from start
  if ($lenstart=='a') $lenstart=strlen($start); // Auto len Start
  if ($start=='') { $lenstart=0;$strstr=$data; } // No start (inverse strstr)
  $substr=substr($strstr,$lenstart,999999);//Substr from start
  return substr($substr,0,strpos($substr,$end)+$lenend); // Return substr Result
}

// *** Replace parts (greps) of Text(data) by new Text(data) [vb.: metaedit settingsedit etc.]
// $replace array original texts - $through array replacement texts - $indent identification Array
// Example: replace[style]='hallo.css' through[style]='janje.css' indent[style]='<link href="'
function pw_editgrep ($data,$replace,$through,$indent=array())
{ if (!is_array($replace)) $replaceay[1]=$replace;else $replaceay=$replace;
	if (!is_array($though)) $throughay[1]=$through;else $throughay=$through;
	foreach($replaceay as $key => $val)
	$data=str_replace($indent[$key].$val,$indent[$key].$throughay[$key],$data);
}

//PHP psywizard@mail.com TextEdit Functions PW170705
?>