<?php $ses['verdate']['mysql3']=70505;
// *** Connect $mysql database **** PW100903 Pw3-041205 | Upgraded to mysqli PHP8 2026
class connect
{ var $mysql=array();
  var $errors=null;
  var $system=null;
  function __construct ($mysql=null)
 {  if (!$mysql) global $mysql; $this->mysql=&$mysql;
	if (isset($mysql['host'])) {
		$conn = @mysqli_connect($mysql['host'], $mysql['user'], $mysql['pass'], $mysql['selectdb'] ?? '');
		if (!$conn) {
			$this->errors = "<br><center>Sorry Cannot Connect to SqlServer<br>Please Come Back Later...</center><br>";
			return;
		}
		$GLOBALS['_mysqli'] = $conn;
		$mysql['res_'.$mysql['host'].($mysql['selectdb'] ?? '')] = $conn;
	}
	if (isset($mysql['selectdb'])) {
		if (!@mysqli_select_db($GLOBALS['_mysqli'], $mysql['selectdb']))
			$this->errors = "<br><center>Sorry Cannot Connect to DataBase {$mysql['selectdb']}<br>Please Come Back Later...</center><br>";
	}
 }
}

// *** Helper: get global mysqli connection
function _pw_mysqli() {
	if (!isset($GLOBALS['_mysqli']) || !$GLOBALS['_mysqli']) {
		trigger_error('No active MySQLi connection', E_USER_ERROR);
	}
	return $GLOBALS['_mysqli'];
}

// *** <Insert> $input Array to mysql $table   **** PW150604-L050106
// ** <Update> mysql $table width $input where $where or $input[where]
function pw_arraytodb ($table,$input,$where='',$addslashes=0,$keepts=0)
{ $conn = _pw_mysqli();
  if (!empty($input['id'])) unset ($input['id']);
  if (!empty($input['timestamp']) and $keepts==0) unset ($input['timestamp']);
  if (!empty($input['created'])) unset ($input['created']);
  if (!empty($GLOBALS['set']['created'])) $input['created']=&$GLOBALS['set']['created'];
  if (!isset($table)) return "error no table";
  if ($where=='input') $where=&$input['where'];
  // ** Query $table for fieldnames
  $query=mysqli_query($conn, "show fields from $table");
  if (!$query) return "Table $table not Found";
  $fields = [];
  while($result=mysqli_fetch_row($query)) $fields[$result[0]]=$result[1];
  mysqli_free_result($query);

 // ** Update
 if ($where)
  { $arraytodb=null;
	foreach ($fields as $field => $_) {
		if (isset($input[$field]) and $arraytodb) $arraytodb.=",";
		if (isset($input[$field]) && is_array($input[$field])) { $input[$field]='|'.implode("|",$input[$field]).'|'; }
		if (isset($input[$field])) {
			if ($addslashes) $input[$field]=addslashes($input[$field]);
			$arraytodb.=$field."='".mysqli_real_escape_string($conn, $input[$field])."'";
		}
	}
	$GLOBALS['mysql']['updatemysql']= ("update $table set $arraytodb where $where");
	mysqli_query($conn, "update $table set $arraytodb where $where");
	$GLOBALS['mysql']['error']=mysqli_error($conn);
	$GLOBALS['mysql']['affected']=mysqli_affected_rows($conn);
	$getid=array();
	if ($GLOBALS['mysql']['affected']>0) preg_match('/(id=)+("|\')+([\d\s]+)("|\')+/',$where,$getid);
	return $getid[3] ?? null;
	} else if ($GLOBALS['ses']['users_id']>0)
 // ** Insert
	{ $input['users_id']=$GLOBALS['ses']['users_id'];
      if(!isset($GLOBALS['set']['created'])) $input['created']=time();
      if(!isset($GLOBALS['set']['timestamp']) and !empty($input['timestamp'])) unset($input['timestamp']);
	$arraytodb="(";
	foreach ($fields as $field => $_) {
		if (isset($input[$field]) and $arraytodb<>"(") $arraytodb.=",";
		if (isset($input[$field])) $arraytodb.= $field;
	}
	$arraytodb.=") values (";
	foreach ($fields as $field => $_) {
		if (isset($input[$field]) and substr($arraytodb,strlen($arraytodb)-1,1)=="'") $arraytodb.=",";
		if (isset($input[$field]) && is_array($input[$field])) { $input[$field]='|'.implode("|",$input[$field]).'|'; }
		if (isset($input[$field])) {
			if ($addslashes) $input[$field]=addslashes($input[$field]);
			$arraytodb.="'".mysqli_real_escape_string($conn, $input[$field])."'";
		}
	}
	$arraytodb.=")";
	$GLOBALS['mysql']['insertmysql']= ("insert into $table $arraytodb");
	mysqli_query($conn, "insert into $table $arraytodb");
	$GLOBALS['mysql']['error']=mysqli_error($conn);
	return mysqli_insert_id($conn);
  }
}

// *** Mysqldata to Array **** PW100903-201206
// *** [ $col=put this field in $result [ $n='' seq. ]  [ exmpl: $n='id' id as array nr] ]
function pw_dbtoarray ($query,$col='*',$nfield='n')
{ $conn = _pw_mysqli();
  $result=array();
 $resource = mysqli_query($conn, $query); $GLOBALS['mysql']['error']=mysqli_error($conn);
 if ($resource instanceof mysqli_result)
 { $GLOBALS['mysql']['num_fields'] = @mysqli_num_fields ($resource);
   $GLOBALS['mysql']['num_rows'] = @mysqli_num_rows ($resource);
   if($nfield) $nfield=explode(' ',$nfield);
  for ($cntr=1;$cntr<=$GLOBALS['mysql']['num_rows'];$cntr++)
	{
  	$linedata = mysqli_fetch_array($resource,MYSQLI_ASSOC);
    foreach ($nfield as $key => $val) {
     if($val=='n') { if($col=='*') $result[$cntr]=$linedata; else $result[$cntr]=$linedata[$col]; }
     if($val!='' and $val!='n') { if($col=='*') $result[$linedata[$val]]=$linedata; else $result[$linedata[$val]]=$linedata[$col]; }
	}}
  @mysqli_free_result($resource);
 }
 return $result;
}

// *** ARRAYTOQUERY Mysql **** PW041104
// vb.: [$addtoqw=pw_arraytoqw(explode('\r\n',$prop),'properties like \'%$val%\'','or');]
function pw_arraytoqw($array,$qwadd,$logic='or')
{ $return=null; if (!is_array($array)) return;
  foreach ($array as $key => $val) if ($val)
  { if ($return) $return="$return $logic ";
    $return .= str_replace('$val', $val, $qwadd);
  }
  return $return;
}

function pw_array_combine($aykey,$ayval)
{ $return = [];
  foreach ($ayval as $key => $val) if ($key and $val) { $return[$aykey[$key]]=$val; }
  return $return;
}

// *** MultiArray to Txt [do not set 2e argument its in use for function loop multi array]
function pw_array2txt($array,$varname='1empty1')
{ $return='';
  if($varname!='1empty1') $varname=str_replace('1empty1','',$varname);
  else { $return='array2txt|';$varname=''; }
  foreach ($array as $key => $val)
  { if(is_array($val)) $return.=pw_array2txt($val,$varname."['$key']");
    else $return.=$varname."['$key']=|".str_replace('|','!',$val).'|';
  }
  return $return;
}

// *** Txt to MultiArray [invers pw_array2txt]
function pw_txt2array($txt)
{ $return=array();$array=explode('|',$txt);
  if($array[0]=='array2txt') unset($array[0]); else return $txt;
  foreach ($array as $key => $val) {
    if(($key/2)!=round($key/2))
      if($val) eval ('$return'.$val.'$array[($key+1)];');
  }
  return $return;
}

// *** phpwotan.com ** Mysql Functions V3.3 ** PW100903 LUPD201206-050507 | mysqli upgrade 2026
?>
