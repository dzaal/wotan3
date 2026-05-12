<?php
class pw_htmlfile
{ var $result=array(''); 	// * Results Array
  var $incbody=0;		// *	0=body only filter 1=fullpage
  var $autonp=1;		// *	1 [n+1] set also loop +1
  var $htmlfile='';		// *	htmlfile to be processed
  var $nocomments='';   // *    =1 Strip Loop Comments

 function __construct () { wotan('fileread'); } // *** Dependencies

 // ** runall functions = Process a htmlfile $this->htmlfile
 function runall ($input='',$incbody=0)
 {  eval (arraytoclass('$input'));
    if(!$this->htmlfile) $this->htmlfile=$input;
    if($incbody>0) $this->incbody=$incbody;
	$code=$this->openfile ($this->htmlfile,$this->incbody);
    $code=$this->chkforloops ($code);
	return $this->evalcodetags ($code);
 }

 // ** evalhtmlonly functions = Process a htmlfile $this->htmlfile
 function evalfile ($input='',$incbody=0)
 {  eval (arraytoclass('$input'));
    if(!$this->htmlfile) $this->htmlfile=$input;
    if($incbody>0) $this->incbody=$incbody;
	$code=$this->openfile ($this->htmlfile,$this->incbody);
	return $this->evalcode ($code);
 }

 // ** Openhtmlfile $htmlfile $incbody 1/0
 function openfile ($htmlfile,$incbody=0)
 {	if ($htmlfile) $this->htmlfile=pwsp($htmlfile);
    else $this->htmlfile=pwsp('default.html');
    $content=@implode('', (array)@file($this->htmlfile));
    if(!$content) $this->errors[]="File $this->htmlfile not found or no content";
	if ($incbody<1 and $bfcnt=strstr($content,'<body')) 
    { $fcntb=substr($bfcnt,strpos($bfcnt,'>')+1,499999); 
    if (strstr($fcntb,'</body>')) $content=substr($fcntb,0,strpos($fcntb,'</body>'));else $content=$fcntb; }
    return $content;
 }
 // ** Find <!-- php pw_loop --> <!-- php pw_loop end --> Tags
 function chkforloops ($code,$totaln=999)
 {	$code=str_replace('pw_loop','pw_looppwloop',$code);$return='';
    if ($this->nocomments>0) $codeay=(preg_split ('/(<!--)(\s)*(php pw_loop)/',$code));
	else $codeay=(preg_split ('/(php pw_loop)/',$code));
	if (count($codeay)<2) return $code;
    $loopcode=0;$save=array(0=>'',1=>'',2=>'',3=>'',4=>'');
	foreach($codeay as $key => $val)
	{   $chkpwtag=substr($val,0,strpos($val,'-->'));
        if (strstr($chkpwtag,'pwloop') and !strstr($chkpwtag,' end '))
        { if ($this->nocomments>0) $val=str_replace($chkpwtag.'-->','',$val);
          else $val=str_replace('pwloop','',$val);
          $loopcode++;
        }
        if (strstr($chkpwtag,' end '))
        { if ($this->nocomments>0) $val=str_replace($chkpwtag.'-->','',$val);
          else $val=str_replace('pwloop','',$val);
		  $val=$this->loopcode($save[$loopcode]).$val;$save[$loopcode]=null;$loopcode=$loopcode-1;
        }
        if ($loopcode>0) { $save[$loopcode].=$val;$val=null; }
    	$return.=$val;
    }
    return $return;
 }

 // ** Loop Code between $loopcode [n] times size of array
 function loopcode ($loopcode)
 {	$code='';
	$arrayname=strstr($loopcode,'$'); // Find arrayname
	$strname=substr($arrayname,0,strpos($arrayname,'[')); // echo $strname;
	if (strstr($arrayname,'[n]') or strstr($arrayname,'[n+'))
 		$arrayname=substr($arrayname,0,strpos($arrayname,'[n')); // echo $arrayname;
	    else $arrayname=substr($arrayname,0,strpos($arrayname,'}'));
	// Normalize bare array keys (e.g. $result[vip] -> $result['vip']) for PHP 8 compatibility
	$arrayname=preg_replace('/\[([a-zA-Z_]\w*)\]/', "['$1']", $arrayname);
	eval ('global '.$strname.';$array=&'.$arrayname.';');// set $arrayname Global and in $array
	$nrlp=is_array($array) ? count($array) : 0; for ($n=1;$n<=$nrlp;$n++)
    { eval ('$ifay=isset('.$arrayname.'['.$n.']) ? '.$arrayname.'['.$n.'] : null;');
     if ($ifay)
	 { $code.=str_replace('[n]',"[$n]",$loopcode);
      $code=str_replace('[n+',"[$n+",$code);
        if ($this->autonp>0)
		if ($getnp=substr($loopcode,(strlen($loopcode)-strpos(strrev($loopcode),'+n[')),9999))
		{ $getnp=substr($getnp,0,strpos($getnp,']'));$n=$n+$getnp; }
    }}
    return $code;
 }

 // ** eval echo $code [resolf vars]
 function evalcode ($code)
 {  global $_POST,$_GET,$ppost,$post,$pget,$get,$ses,$echo,$result,$REDIRECT_URL,$HTTP_HOST,$set,$obj;
	 if (is_array($echo)) extract($echo,EXTR_SKIP);
     if (is_array($result)) extract($result,EXTR_SKIP);
     if (!empty($echo['pageinfo']) && is_array($echo['pageinfo'])) extract($echo['pageinfo'],EXTR_SKIP);
	@eval ('$code="'.str_replace('"','\"',$code).'";');return $code;
 }

 // ** Look for embeded pw & php code and run (varconf) [ <!--|  |--> ] & [ \<? ?\> ]
 function evalcodetags ($code)
 {	global $_POST,$_GET,$ppost,$post,$pget,$get,$ses,$echo,$result,$REDIRECT_URL,$HTTP_HOST,$set,$obj;$return='';
	 if (is_array($echo)) extract($echo,EXTR_SKIP);
     if (is_array($result)) extract($result,EXTR_SKIP);
     if (!empty($echo['pageinfo']) && is_array($echo['pageinfo'])) extract($echo['pageinfo'],EXTR_SKIP);
    $code=str_replace('<!--|','<!--|$return.=',$code);
	$codeay=(preg_split ('/(<!\-\-\||\|\-\->|<\?|\?>)/',$code));
	foreach($codeay as $key => $val)
	{	if ($key/2==round($key/2)) {
			set_error_handler(function(){return true;},E_WARNING|E_NOTICE);
			@eval ('$return.="'.str_replace('"','\"',$val).'";');
			restore_error_handler();
		} else { set_error_handler(function($no,$str,$file,$line) use(&$val){if(error_reporting()===0)return false;$GLOBALS['_vadump'][]=array('type'=>$no,'message'=>$str.' | code: '.substr($val,0,200),'file'=>str_replace($_SERVER['DOCUMENT_ROOT'],'',$file),'line'=>$line);return false;},E_WARNING|E_NOTICE); @eval ($val.';'); restore_error_handler(); }
	}	return $return;
 }
var $ver='phpwotan.com **** pw_htmlfile V4.323 **** Pw3-261105-L200206-170306-200306-030307';
}
if(!is_array($ses)) echo "<center>No session Detected  pw_htmlfile V4.323 </center>";
?>
