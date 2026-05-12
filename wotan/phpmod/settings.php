<?php
class settings
{
 var $setfile='/share/settngs/guests.php';// * File To Edit

 // ** Show Item Links
 function listitems ()
 { $items=explode('// ***',implode(file(pwsp($this->setfile)))); // * Load Settings File
   $return=array();$n=1;
   foreach($items as $key => $val) if($x=strpos($val,'<show>'))
   { $return[$n]['itname']=substr($val,1,$x-1);$return[$n]['sid']=$key;$n=$n+1; }
   return $return;
 }

 // ** ViewEdit Settings
 function viewedit($sid)
 { $setcode=implode(file(pwsp($this->setfile))); // * Load Settings File
   $items=explode('// ***',$setcode); // * Split in Items
   $return=array();$editarray=explode("\r\n",$items[$sid]);$n=1; // * Load Settings Item $sid

   // ** Edit $sid
   //echo 'DEBUG:<br>'.nl2br(print_r($_POST,TRUE));
   // ** New Line (text) -> make also for other fields idea: ($newcode=text=textarea=savehtml$_POST[line])
   echo str_replace($_POST['text'],$_POST['ntext'],$editarray[$_POST['line']]);
   //$newline=str_replace($_POST['text'],$_POST['ntext'],$editarray[$_POST['line']]);
   // ** replace settingitem
   // $newitem=str_replace($editarray[$_POST['line'],$newline,$items[$sid]);
   // ** replace in settings
   $nsetcode=str_replace($items[$sid],$newitem,$setcode);
   // ** Save New Settings file
   // $fileopen=fileopen(pwsp($this->setfile),w);
   // filewrite($fileopen,$nsetcode);fileclose($fileopen);

   if ($_POST['newcode']!=$edit)
   { $this->setcode[$line]['old']=$input;  $this->setcode[$line]['new']=str_replace($edit,$_POST['newcode'],$input); }

   // ** View $sid
   foreach($editarray as $key => $val)
   { //echo "$key - $val <br>";
     //$return[$n]['select']="<option>test</option><option>test2</option>";
    $return[$n]['selectd']=$return[$n]['checkboxd']=$return[$n]['textd']=$return[$n]['textaread']='none';
    $return[$n]['line']=$key;
    if($key==0) { $return[$n]['name']='Settings';$return[$n]['view']=$val;$n=$n+1; } // * Show Item Name
    if(strpos($val,'*<editpath>'))  { $return[$n]['name']='Dir(s)';$return[$n]['text'].=$this->viewpath($val);$return[$n]['textd']='inline';$n=$n+1; } else // * Edit Path
    if(strpos($val,'REDIRECT_URL==') or strpos($val,'pwcd(')) { $return[$n]['name']='Dir(s)';$return[$n]['view']=$this->viewpath($val);$n=$n+1; } // * Show Path
    if(strpos($val,'*<editstr>'))   { $return.=$this->setstr($val);$n=$n+1; } else      // * Edit String
    if(strpos($val,'*<viewstr>'))  { $return.=$this->showstr($val);$n=$n+1; }           // * View String
    if(strpos($val,'*<editwotan>')) { $return.=$this->setwotan($val);$n=$n+1; } else    // * Edit Wotan Obj
    if(strpos($val,'*<viewwotan>')){ $return.=$this->showwotan($val);$n=$n+1; }         // * View Wotan Obj
   }
 return $return;
 }

 function viewpath($input)
 { $return[2]="Unknown";
   preg_match_all ('/(REDIRECT_URL==)+[\'|"]+([^\'|^"]+)+[\'|"]+/',$input,$resredurl);// * Redirect_URL path
   preg_match ('/(pwcd\()+[\'|"]([^\'|^"]+)+[\'|"]+/',$input,$return);// * pwcd path
   if($resredurl[2]) $return[2]=implode(' ',$resredurl[2]);
   return $return[2];
 }

var $ver='phpwotan.com Settings V0.1 Pw3-100506';
}
?>
