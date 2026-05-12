<?php
// *** Module $wotan['metaeditor']
$cntfile=implode('',file($_SERVER['DOCUMENT_ROOT'].$wotan['metaeditor']));

// *** Make tag seperators
$cntfile=str_replace('<','{|}<',$cntfile);
$cntfile=str_replace('>','>{|}',$cntfile);

// *** Make Tags Array
$tags=explode('{|}',$cntfile);
$metaedit=0;$n=0;

// *** Tags
foreach($tags as $key => $val)
{
 if ($metaedit==1 and stristr($val,'<meta')) 
 {	$metakeyay=explode('"',$val);$n=$n+1;

	// *** Save New Settings
	if ($_POST[$metakeyay[1]]) { $metakeyay[3]=$_POST[$metakeyay[1]];
    $tags[$key]='<META NAME="'.$metakeyay[1].'" CONTENT="'.$metakeyay[3].'">'; }

	// *** Make result
	$result[$n]['metaname']=$metakeyay[1];
	$result[$n]['fmetaname']=ucfirst(strtolower($metakeyay[1]));
	$result[$n]['metavalue']=$metakeyay[3];
 }
if (strstr($val,"<!-- pw_metaedit")) if ($metaedit<1) $metaedit=1;else $metaedit=0;
}

// *** Save File $wotan['metaeditor']
if ($_POST['save']) $echo['msg']=pw_writecontent ($_SERVER['DOCUMENT_ROOT'].$wotan['metaeditor'],implode('',$tags),$maxsize=102400);

//PHP psywizard@mail.com MetaEditor PW100605
?>