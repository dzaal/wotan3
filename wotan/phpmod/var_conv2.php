<?php $ses['verdate']['var_conv2']=70505;
//Convert int to n x symbol(s) **** PW100804
function pw_inttosym($mp,$symb)
{ $return='';
for ($loop=1;$loop<=$mp;$loop++) $return.=$symb;
return $return;
}

// *** Urls
function pw_unhttpurl ($url) { return str_replace("http://","",$url); }
function pw_httpurl   ($url) { return 'http://'.str_replace("http://","",$url); }

// *** Condition's
function pw_if ($condition,$true,$false)					{ if ($condition) return $true; else return $false; }
function pw_ifdata ($beforedata,$data,$afterdata) 			{ if ($data) return $beforedata.$data.$afterdata; }
function pw_ifdataer ($data,$echo) 							{ if ($data) return $echo; }
function pw_ifeval ($condition,$true,$false)				{ eval ("if ($condition) \$result=\"$true\"; else \$result=\"$false\";");return $result; }
function pw_iflendata ($beforedata,$data,$afterdata,$len)	{ if (strlen($data)>$len) return $beforedata.$data.$afterdata; }


function pw_checked ($checker) 								{ if ($checker) return 'checked="checked"'; }
function pw_ifdatachange ($beforedata,$data,$afterdata) 	{ global $convar;if ($data<>($convar['ifdatachange'] ?? null)) { $convar['ifdatachange']=$data; return $beforedata.$data.$afterdata;} }
function pw_stripnrchars ($data,$br='') 					{ return str_replace("\n","$br",str_replace("\r",'',str_replace("'",'',$data))); }

// *** Splits
function pw_pregsplit ($preg,$data,$part,$nopart)
	{ $prs=preg_split($preg,$data ?? '');if(!empty($prs[$part])) return $prs[$part];else return ($prs[$nopart] ?? null); }
function pw_splitexpl($beforedata,$split,$data,$afterdata)
	{ $spl=explode($split,$data);foreach($spl as $key => $val) $return.=$beforedata.$val.$afterdata;return $return; }
function pw_pregexpl($beforedata,$preg,$data,$afterdata)
	{ $spl=preg_split($preg,$data);foreach($spl as $key => $val) $return.=$beforedata.$val.$afterdata;return $return; }

// *** Multiselect ($arrayval is for to catch array format fault in grouping)
function pw_multiselect ($data,$split,$selected,$arrayval='')
{ if (!is_array($data)) $spl=explode($split,$data); else $spl=$data;
  foreach($spl as $key => $val)
  { if ($arrayval) $val=$val[$arrayval];
	if (strstr($selected,'|'.$val.'|')) $setsel=' selected>'; else $setsel='>';
    if ($val and $selected==$val) $setsel=' selected>';
 	//echo "$selected , '|'.$val.'|' <br>";
    if ($val) $return.='<option'.$setsel.$val.'</option>';
  } return $return;
}

// *** FCKeditor 2.4 textarea Pw080207 http://www.fckeditor.net/
function pw_FCKedit ($data='',$width='100%',$height=100,$toolbar='mini',$instance='FCKedit',$br='',$FullPage='true',$EditorAreaCSS='',$BaseHref='',$ServerPath='',$config='')
{ global $REDIRECT_URL;
  if (!$ServerPath) $ServerPath=$REDIRECT_URL;
  if ($EditorAreaCSS) $EditorAreaCSS="oFCKeditor.Config['EditorAreaCSS']='$EditorAreaCSS';";
  if ($BaseHref) $BaseHref="oFCKeditor.Config['BaseHref']='$BaseHref';";
  if ($FullPage=='false' and $body=strstr($data,'<body>')) $data=$body;
  return "<script type='text/javascript'>
		<!--
		var oFCKeditor = new FCKeditor( '$instance' ) ;
		oFCKeditor.Width	  = '$width' ;
		oFCKeditor.Height     = '$height' ;
		oFCKeditor.BasePath     = '/wotan/FCKedit/' ;
		oFCKeditor.ToolbarSet	= '$toolbar' ;
		oFCKeditor.Value = '".str_replace("\n","\\n",str_replace("\r","\\r",str_replace("'","\'",$data)))."';
		oFCKeditor.Config['FullPage']='$FullPage';
		$EditorAreaCSS $BaseHref
   		//oFCKeditor.Config['EditorAreaCSS'] = 'http://$GLOBALS[HTTP_HOST]{$GLOBALS[wotan][stylesheet]}' ;
		oFCKeditor.Config['LinkBrowserURL']  = oFCKeditor.BasePath+'editor/filemanager/browser/default/browser.html?Type=File&Connector=connectors/php/connector.php&ServerPath=$ServerPath';
		oFCKeditor.Config['ImageBrowserURL'] = oFCKeditor.BasePath+'editor/filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php&ServerPath=$ServerPath';
		oFCKeditor.Config['FlashBrowserURL'] = oFCKeditor.BasePath+'editor/filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/php/connector.php&ServerPath=$ServerPath';
        oFCKeditor.Config['LinkUploadURL']   = oFCKeditor.BasePath+'editor/filemanager/upload/php/upload.php?Type=File&ServerPath={$ServerPath}file/';
        oFCKeditor.Config['ImageUploadURL']  = oFCKeditor.BasePath+'editor/filemanager/upload/php/upload.php?Type=Image&ServerPath={$ServerPath}image/';
        oFCKeditor.Config['FlashUploadURL']  = oFCKeditor.BasePath+'editor/filemanager/upload/php/upload.php?Type=Flash&ServerPath={$ServerPath}flash/';
        $config
		oFCKeditor.Create() ;
		//-->
		</script>";
}

// *** Prices Transfer Rates
function pw_transferrates ($price,$valutain,$valutaout,$decimals=2,$decimal=',',$thousends='.')
{	if ($GLOBALS['convrates'][$valuta]>0)
	{	$rate_price_euro=str_replace($thousends,'',$price)/$GLOBALS['convrates'][$valutain];
		return number_format($rate_price_euro*$GLOBALS['convrates'][$valutaout],$decimals,$decimal,$thousends);
	} else return '?';
}

// *** Linebreaks to <br> and Ucfirst
function pw_ucnltobr ($text) { return ucfirst(nl2br($text)); }

// *** Make Date Selector
function pw_timedateopt ($in='')
{ 
global $echo;
if (!is_array($in)) unset ($in);
if (empty($in['Yto'])) $in['Yto']=6;
$echo['opt_hours']=null;for ($cnt=1;$cnt<25;$cnt++) $echo['opt_hours'].="<option>$cnt:00</option>";
$echo['opt_days']=null; for ($cnt=1;$cnt<32;$cnt++) $echo['opt_days'].="<option>$cnt</option>";
$echo['opt_month']=null;for ($cnt=1;$cnt<13;$cnt++) $echo['opt_month'].="<option>$cnt</option>";
$echo['opt_months']=null;for ($cnt=1;$cnt<13;$cnt++)
	$echo['opt_months'].="<option value=$cnt>".date('M',mktime(0,0,0,$cnt,14,2006))."</option>";
$echo['opt_years']=null;
if (($in['Yfrom'] ?? 0) <= ($in['Yto'] ?? 6))
  {
    for ($cnt=date('Y')+($in['Yfrom']??0);$cnt<(date('Y')+($in['Yto']??6));$cnt++) $echo['opt_years'].="<option>$cnt</option>";
  }
  else
  {
      for ($cnt=date('Y')+($in['Yfrom']??0);$cnt>(date('Y')+($in['Yto']??6));$cnt--)  $echo['opt_years'].="<option>$cnt</option>";
 
  }
} if (!empty($wotan['timedateopt'])) pw_timedateopt ($wotan['timedateopt']);

// *** Images
function pw_getimgwidth ($filename)
    { $imginfo=getimagesize($_SERVER['DOCUMENT_ROOT'].$filename); return $imginfo[0]; }
function pw_getimgheight ($filename)
    { $imginfo=getimagesize($_SERVER['DOCUMENT_ROOT'].$filename); return $imginfo[1]; }
function pw_getimgsize ($filename,$sep='X')
    { $imginfo=getimagesize($_SERVER['DOCUMENT_ROOT'].$filename); return "$imginfo[0] $sep $imginfo[1]"; }

// *** Pages
function pw_pagerange ($range=10,$page=1,$totalpages=1,$add2tag='',$thistag='')
  { $spage=$page;
    if ($totalpages<$range) $range=$totalpages;
    if ($page<=($range/2)) $page=($range/2)+1; $return='';
    if ($page>($totalpages-($range/2))) $page=($totalpages-($range/2)+1);
    for ($n=0;$n<$range;$n++)
    { $rangnr=round($page-($range/2)+$n); if ($rangnr==$spage)
     { $return.="<a $thistag href='?page=".$rangnr."'>$rangnr</a>"; } else
     { $return.="<a $add2tag href='?page=".$rangnr."'>$rangnr</a>"; }
    } return $return;
  }

// PHP phpwotan.com Variable Convertion V2.5 (width FCKedit V2.4 ) PW210605-180406-080207-050507
?>