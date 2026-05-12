<?php
// *** Module $wotan['settingsedit']
if (!is_array($wotan['settingsedit'])) $settingsedit['file']=$wotan['settingsedit'];
else $settingsedit=$wotan['settingsedit']	;

// *** Open Settings File
$cntfile=implode('',file($_SERVER['DOCUMENT_ROOT'].$settingsedit['file']));

// *** SeT Area to Edit
$setareas=explode('// *** ',$cntfile);

foreach($setareas as $key => $val) if (strstr($val,'<show>'))
$areanames[(substr($val,0,strpos($val,"<show>")-1))]=$key;
if (!$areanames[$settingsedit['area']]) 
	$echo['msg'].="Section ".$settingsedit['area']." Not Found In Settings $settingsedit[file]";
else $editarea=$areanames[$settingsedit['area']];

// *** Make VARS Array
$varsay=explode("';",$setareas[$editarea]);$result=array();$n=0;

// *** VARS
$count=count($varsay);
foreach($varsay as $key => $val)
{ if ($key<$count-1) $varsay[$key]=$varsay[$key]."';"; $key=$key+1;
  if (!$settingsedit['filter']) $settingsedit['filter']='=';
  if (strstr($val,'$') and strstr($val,$settingsedit['filter'])) 
  { $val=$val."';";
    if (strstr($val,'// ')) { $remark=substr($val,strpos($val,'//')+3,strpos($val,"\r")-3);$rnm=1; } else unset($remark);
    if (strstr($val,"\n// ")) { $remark=substr($val,strpos($val,'//')+3,strrpos($val,"\r")-6);$rnm=0; } 
    $varname=substr($val,strpos($val,'$')+1,strpos($val,'='));
    $varname=substr($varname,0,strpos($varname,'='));$varn='$'.$varname;
    if (strstr($varname,'[')) $varname=substr($varname,strpos($varname,'[')+1,99);
    $varname=str_replace("'",'',$varname);$varname=str_replace('"','',$varname);$varname=str_replace('.','',$varname);
    $varname=str_replace("[",'_',$varname);$varname=ucfirst(str_replace("]",'',$varname));
    $varvalue=substr($val,strpos($val,"='")+2,strpos($val,';'));
    $varvalue=substr($varvalue,0,strpos($varvalue,"';"));
    if (isset($_POST[$key]) and $_POST[$key]<>$varvalue) 
    	  {  $varsay[$key-1]=str_replace("'".$varvalue."'","'".$_POST[$key]."'",$varsay[$key-1]);$varvalue=$_POST[$key]; }
    if ($key>0) 
	{ $n=$n+1;$result[$n]['varn']=$varn;$result[$n]['varname']=$varname;
      $result[$n]['varkey']=$key;$result[$n]['varvalue']=$varvalue; 
      if ($remark) $result[($n-$rnm)]['remark']=str_replace('// ','',$remark); else $result[$n]['remark']=$varname;
      if ($remark and $rnm!=0) $result[$n]['remark']=$varname;
 	}
} } $setareas[$editarea]=implode('',$varsay);

// *** Save File $wotan['settingsedit']
if ($_POST['save']) $echo['msg']=pw_writecontent ($_SERVER['DOCUMENT_ROOT'].$wotan['settingsedit']['file'],implode('// *** ',$setareas),$maxsize=256400);

// Edit Settings with format [ var='value';// remark ] (other formats to be added)
// PHP psywizard@mail.com SettingsEditor V1 PW100605-280905 
?>