<?php
// *** Grouping $wotan['grouping'][<fieldname>]="groupquiry"; |> $result[grp_<fieldname>][n]=result
$echo[$key]['options']='';
if(is_array($wotan['grouping'])) foreach($wotan['grouping'] as $key => $val)
{   eval ('$mysqlqw="'.$val.'";');
	if (isset($secvars['debugdb'])) echo "grouping.php: $mysqlqw<br>";
	$mysql_query=mysqli_query(_pw_mysqli(), "$mysqlqw");if ($error=mysqli_error(_pw_mysqli())) echo"grouping.php -> $error<br>$mysqlqw<br>";
	if ($mysql_query) for ($n=1;$linedata=mysqli_fetch_assoc($mysql_query);$n++)
	{ $result['grouping'][$key][$n]=$linedata;
	  foreach($linedata as $optname => $optval) if ($optval)
      { preg_match_all('/(\w+)/',$optname.$optval,$varname);$varname[0]=implode($varname[0]);
        if ($varname[0] and !isset($checkdbl[$varname[0]])) { $checkdbl[$varname[0]]=1;
		$echo[$optname]['options']=$echo[$optname]['options'].'<option>'.$optval.'</option>'; }
      }
      if ($linedata[$key]) $result['grouping'][$key][$linedata[$key]]=$linedata;
	  if ($linedata['id']>0) $result['grouping'][$key]['id_'.$linedata['id']]=$linedata;
	  if ($_POST[$key] and $_POST[$key]==$linedata[$key]) $_POST=$_POST+$linedata;
      //echo "$_POST[$val]==$linedata[$val]";
	}
}

// *** PHP psywizard@mail.com Query for extra data other tables -  Cats Groups Multi and/or Referenced Groups
// *** PW200605 LUPD300805-260905-180106
?>
