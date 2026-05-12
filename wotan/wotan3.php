<?php
// *** Get Serverpath
function pwsp($filename)
{ if ($filename[0]=='/') return $_SERVER['DOCUMENT_ROOT'].$filename; else return getcwd().'/'.$filename; }

// *** If Cwd return true [caseinsensitive multiple paths]  [V2.0 Changed to regexp]
// */ or /* recurcive dirs | +/ /+ one level dirs | Everythings behind ! is NOT
function pwcd($cwds,$debug='')
{ global $errors,$pwcd;$pwcd='0';
  $ru=' '.str_replace($_SERVER['DOCUMENT_ROOT'],'',getcwd()).'/ ';
  $cwds='\s'.str_replace(' ','\s|\s',$cwds).'\s';// * Replace spaces by regexp or
  $cwds=str_replace('/*\s','/',$cwds); // /* at end
  $cwds=str_replace('\s*/','/',$cwds); // */ at start
  $cwds=str_replace('\s/*/','/',$cwds);   // /*/ at start
  $cwds=str_replace('/*/\s','/',$cwds); // /*/ at end
  if(strstr($cwds,'/*/')) $errors[]="pwcd: Not Supported PWCD cmd /*/ ";
  $cwds=str_replace('/+\s','/(?:[^/]+/){0,1}\s',$cwds); // /+ at end
  $cwds=str_replace('\s+/','\s(?:/[^/]+){0,1}/',$cwds); // /+ at start
  $cwds=str_replace('/','\/',$cwds); // * escape / char
  $pnot=strpos($cwds,'!');// * Check if there is a not part
  if($pnot>0) $cwds='(\s'.substr($cwds,$pnot+1,99).')|'.substr($cwds,0,$pnot-3);
  if($debug) echo'<br>regexp:/'.$cwds."/ ru:|$ru|<br>";
  preg_match('/'.$cwds.'/i',$ru,$pres); // * REGEXP match
  if($debug) { echo '<br>DEBUG:'.nl2br(print_r($pres,TRUE)); }
  if(!empty($pres[1])) return false;// * Match at ! part
  if(!empty($pres[0])) { $pwcd=1;return true; } // * Match
  return false; // * No match at all
}

if (!isset($ses))
{// *** LOAD HEAD3.PHP
 require_once ($_SERVER['DOCUMENT_ROOT'].'/wotan/head3.php'); // Start Session set mainvars Load mainfunctions Error Handling
 $wotan[3]=1;

 // *** Check for Settings
 if (empty($ses['chkaccess']['settings']))
 {
 $getcwd=getcwd();
 if (empty($ses['sharept'])) $ses['sharept']=$_SERVER['DOCUMENT_ROOT'];
 if ($getcwd!=$ses['sharept'])
 if (file_exists($getcwd.'/share/')) $ses['sharept']=$getcwd;
 else if ($ses['sharept']!=dirname($getcwd))
	  if (file_exists(dirname($getcwd).'/share/')) $ses['sharept']=dirname($getcwd);
 else if ($ses['sharept']!=dirname(dirname($getcwd)))
	  if (file_exists(dirname(dirname($getcwd)).'/share/')) $ses['sharept']=dirname(dirname($getcwd));
 // *** Load Settings
 if (empty($ses['admacc']) || $ses['admacc']<1) require_once ($ses['sharept'].'/share/settings/guests.php');
 if (!empty($ses['admacc']) && $ses['admacc']>0) require_once ($ses['sharept'].'/share/settings/admin.php');
 } else require_once(pwsp('/share/settings/'.$ses['chkaccess']['settings']));
}

// *** Security Warning
if (!isset($obj['chkusr'])) { $system[]='<font color="red">WARNING chkusr3.php NOT LOADED</font>'; }

// *** arraytoclass
function arraytoclass ($input='$input')
{ return'if(is_array('.$input.')) { foreach('.$input.' as $key => $val)
  if (is_int($key)) eval (\'$this->$val;\');
  else eval (\'$this->\'.$key.\'=$val;\'); } ';
}

// *** Wotan Module Class Handler
function wotan ($modclass,$object='',$input='',$path='/wotan/phpmod/')
{ global $echo,$system,$errors,$obj,$set,$ses,$result;// Global Vars
  extract ($GLOBALS,EXTR_SKIP);$cmd='';$add='';$class='';$return=null;
  if(!$system) $system[]='<br>---==== System Messages ====---'; // Set system var
  if (strstr($modclass,'/')) { $modcls=explode('/',$modclass);$mod=$modcls[0];$class=$modcls[1]; }
  else { $mod=$modclass;if(is_array($input)) { $wotan=$input;extract($input); } } // Set Mod/Class
  if (!$class) $objname=$mod;else $objname=$class;
  if (!strstr($object,'[')) { $cmd=$object;$object='$obj[\''.$objname.'\']'; } // Check if $object is array
  else { eval ('global '.substr($object,0,strpos($object,'[')).';'); // Set $$object var Global
	$object=str_replace('[',"['",str_replace(']',"']",$object)); }
  if(!include_once ($_SERVER['DOCUMENT_ROOT'].$path.$mod.'.php')) //LoadModule $mod
   $errors[]=$system[]='<b>Wotan:</b> <font color="red">'.$path.$mod.'.php could not be loaded.</font>'; // Error module not found
  else $system[]="<b>Wotan:</b> Run/Load Mod $mod.php ".substr(print_r($input,true),0,255);
    if (strstr($modclass,'/') and class_exists($class))
	{	eval ('if(empty('.$object.') || !is_object('.$object.')) '.$object.' = new '.$class.'; // Set Object $object class
		else $add=" + |";'); // * Object exists already
       if($input and !is_array($input)) eval ('$return='.$object.'->'.$input.';'); // * Set $input in Object
		if(is_array($input)) foreach($input as $var => $val)
		{ if(strstr($val ?? '','=Array')) { $system[]="<b>Wotan:</b><font color='red'>$class Error: $val</font> ";$val=null; }
          if($val and is_int($var) and !is_array($val)) { eval ('$return='.$object.'->'.$val.';'); } // * Set Array $input as ObjVars in Object
 		  if(isset($val) and !is_int($var)) { eval ($object.'->'.$var.'=$val;'); }
		  if(isset($val) and is_int($var) and is_array($val)) { eval ($object.'->Unknown=$val;'); }
		  $system[]="<b>Wotan:</b> Mod $mod Class $class |$add  $object -> ".substr($val ?? '',0,255).';'; // * Add System Msg
		}
	    eval ('if (!empty('.$object.'->errors)) $system[]=$errors[]="<font color=\"red\"><b>Error(s): </b>".print_r('.$object.'->errors,true)."</font>";'); // * Set class->errors in Array Errors
	    eval ('if (!empty('.$object.'->system)) $system[]='.$object.'->system;'); // * Set class->system msgs in Array system
    } else { if ($class) $system[]="<b>Wotan:</b><font color='red'>Class $class does not exists</font> "; }
	if ($cmd=='print_r') echo nl2br(print_r($obj,true));else // * print_r $obj
    return $return;
}

// *** Load Wotan2 as well for backward compatibility
if (isset($wotan[2])) { require ($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/wotan2.php');$system[]="$ver LOADED";}
if (isset($set['print_r'])) { $pr=$set['print_r']; eval ('$resdeb=print_r('.$pr.',true);');
    echo '<br><br><table style="background:white; color:black;">
    <tr><td>'.nl2br(htmlentities($resdeb)).'</td></tr></table>'; } // * Debugging
if (isset($set['systemmsg']))
    echo '<br><br><table style="background:white; color:black;">
    <tr><td>'.nl2br(print_r($system,true)).'</td></tr></table>'; // * System Msgs

// *** phpwotan.com PHPWOTAN Kernel V3.14 | PHP8/mysqli upgrade 2026
?>
