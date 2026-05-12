<?php
// *** $id Control (Default _POST[id] has priority)
if (isset($_POST['id']) && $_POST['id']>0) $_GET['id']=$_POST['id'];
if (isset($_GET['id']) && $_GET['id']>0) $_SESSION['post']['id']=$_SESSION['get']['id']=$GLOBALS['id']=$_GET['id'];

// *** Loop array $_POST and apply Conversions Array where required
$postfilters=array();$postvarname=null;
pw_parray_walk($_POST);
function pw_parray_walk($array)
{	global $_POST,$postvarname,$postfilters;
    if (!is_array($postfilters)) $postfilters=array(); // PHP8: ensure array
 	if (is_array($array)) foreach($array as $key => $val)
	{	$skey=(string)$key;$fkey=is_array($val)?((string)key($val)):'';
		if (is_array($val) and strstr($fkey,'pw_') and !strstr($skey,'pw_') and !$postvarname) $postvarname=$skey;
		if (strstr($skey,'pw_')) $postfilters[]=$skey;
		if (is_array($val) and strstr($fkey,'pw_')) pw_parray_walk($val);
		if (is_array($postfilters) && count($postfilters)>0)
		{ $_POST[$postvarname]=$val;
			  $postfilters=array_reverse($postfilters,true);reset($postfilters);
	          foreach($postfilters as $fkey=>$filter) pw_postfilter($filter,$postvarname,$_POST[$postvarname]);
		}
	} 	$postfilters=array();$postvarname=null;
}

// *** Conversion/Filters
function pw_postfilter ($filter,$var,$val)
{ global $_POST,$echo;
 if (!strstr($var,'pw_'))
 {	if(!empty($GLOBALS['secvars']['db_post_conv'])) echo "DebugFilter POST_conv2: $filter $var $val<br>";
 if ($filter=='pw_iteminfo') { $_POST['iteminfo']=$val;$_POST[$var]=$val; }
 if ($filter=='pw_url' and strstr($val,'.')) $_POST[$var]='http://'.str_replace(' ','',str_replace('http://','',$val));
 if ($filter=='pw_req' and empty($_POST['delitem'])) if (strlen($val)<1)
	{ $echo['msg'].="<b>Required Field $var</b><br>";unset($_POST['upditem']);unset($_POST['addnewitem']); } else $_POST[$var]=$val;
 if ($filter=='pw_varconf' and is_array($val)) foreach($val as $var_conf => $varcname)
	 eval ('$_POST['.$varcname.']='.$var_conf.'('.$_POST[$filter][$var_conf][$varcname].')');
 if ($filter=='pw_stripslashes') $_POST[$var]=stripslashes($val);
 if ($filter=='pw_unid')  $_POST[str_replace('_'.$_POST['id'],'',$val)]=str_replace("'","&#39",$val);
 if ($filter=='pw_unbody') { if(strstr($val,'<body') and strstr($val,'</body>'))
    					    { $fbody=strstr($val,'<body');
					    	  $fbody=substr($fbody,strpos($fbody,'>')+1,999999);
    						  $_POST[$var]=substr($fbody,0,strpos($fbody,'</body>'));
   						   }}
 if ($filter=='pw_funix')   $_POST[$var]=pw_convtsform($val,$dateformat,'unix'); 
 if ($filter=='pw_mkunix')  $_POST[$var]=mktime($val['hour'] ?? 0,$val['minute'] ?? 0,$val['second'] ?? 0,$val['month'] ?? 0,$val['day'] ?? 0,$val['year'] ?? 0);
 if ($filter=='pw_utf8enc') $_POST[$var]=utf8_encode($val);
 if ($filter=='pw_utf8dec') $_POST[$var]=utf8_decode($val);
 if ($filter=='pw_urlenc')  $_POST[$var]=rawurlencode($val);
 if ($filter=='pw_urldec')  $_POST[$var]=rawurlencode($val);
 if ($filter=='pw_md5')     $_POST[$var]=md5($val);
 if ($filter=='pw_condlast') if ($_POST['last_'.$var]<>$val) $_POST[$var]=$val;
 if ($filter=='pw_chkemail') { preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$val,$emlres);
							if ($emlres[0]!=$val) { $echo['msg'].="<b>Check Your EmailAddress</b><br>"; } }
 if ($filter=='pw_reqemail') { preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$val,$emlres);
							if ($emlres[0]!=$val or !$val) { $echo['msg'].="<b>Check Your EmailAddress</b><br>";
													unset($_POST['upditem']);unset($_POST['addnewitem']); } }
 if ($filter=='pw_fchkemail') { $reschk=pw_chkemailaddr($webmastereml,$val); 
							    if ($reschk!=2 and $reschk!=4) $echo['msg'].='<b>Check Your EmailAddress</b><br>'; }
 if ($filter=='pw_sesusername') if(!$val) $_POST[$var]=$_SESSION['username'];
 if ($filter=='pw_select') { $varname=substr($var,strpos($var,'_')+1,99);// exmplvars: cat cat_old sel_cat[pw_select]
								if($val and $_POST[$varname.'_old']<>$_POST[$var]) $_POST[$varname]=$val; }
 }
}

// *** Add new Posts in session var $_SESSION['post']
$_SESSION['post_old']=$_SESSION['post'];
if (is_array($_SESSION['post']) and is_array($_POST)) $_SESSION['post']=$_POST+$_SESSION['post'];
else { echo "post_conv2.php: No POST array Detected"; exit; }
// phpwotan.com POST_CONF2 V2.22 Form Post vars Convertion via filter controller array *** PW270705-260805-100206-170306-050406
?>
