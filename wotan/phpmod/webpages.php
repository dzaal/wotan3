<?php
// *** SetPageTypes
$echo['pagetypes']="<option>HtmlPage</option>";

// *** Read Templates
if (function_exists('pw_dirtoarray'))
{ $files=pw_dirtoarray("$_SERVER[DOCUMENT_ROOT]/share/templates/$_POST[template]/",'dir');
  if (is_array($files['list'])) foreach($files['list'] as $key => $val)
  if ($val) { $echo['opt_templates'].="<option value='$val'>".ucfirst($val).'</option>'; }
}

// *** chmod
if($secvars['webpages']['chmod']) $chmod=$secvars['webpages']['chmod'];else $chmod='0777';

// *** Make New WebPage
if ($_POST['addnewitem']) 
{ unset($_POST['addnewitem']);
 if (file_exists($_SERVER['DOCUMENT_ROOT']."/$_POST[name]/")) 
	$echo['msg'].="Error: Could not make page.<br>Dir: /$_POST[name]/ Allready Exists<br>";else
 if (!file_exists($_SERVER['DOCUMENT_ROOT']."/share/templates/$_POST[template]")) 
	$echo['msg'].="Error: Could not make page.<br>Template: $_POST[template] Not Found<br>";else
  {	// *** Copy Template
    preg_match_all('/[a-zA-Z\-_0-9\s_]+/',$_POST['name'],$_POST['name']);
    $_POST['name']=str_replace(' ','_',implode($_POST['name'][0]));
	pw_mkdirs($_SERVER['DOCUMENT_ROOT']."/$_POST[name]/",$chmod);
	pw_copydir("{$_SERVER['DOCUMENT_ROOT']}/share/templates/$_POST[template]/","{$_SERVER['DOCUMENT_ROOT']}/$_POST[name]/");
    $echo['msg'].="Made Page: $_POST[name] <br>";
	// *** Make New Settings
	// *** Make Mysql Database
	$_POST['addnewitem']='AddItem';
  }
}

// *** Make extra Directorys
if ($_POST['mkdirs']) 
{	$mkdirs=explode(' ',$_POST['mkdirs']);
	if(is_array($mkdirs)) foreach($mkdirs as $key => $val)
	{ pw_mkdirs($_SERVER['DOCUMENT_ROOT']."/$_POST[name]/$val",$chmod); $echo['msg'].="Created Dir $val<br>"; }
}

// *** Delete Page
if ($_GET['delid']>0 and $_GET['dq'] and $post['deletedir']) 
    { pw_rmdir($_SERVER['DOCUMENT_ROOT']."/$post[deletedir]/");$echo['msg'].="<b>Deleted Dir /$post[deletedir]/</b><br>";  }

// *** Save HtmlFile and Resize Images
if ($_POST['savehtml'])
{   $savehtml=stripslashes($_POST['savehtml']);
    $savehtml=str_replace('."','.\"',$savehtml);$savehtml=str_replace('".','\".',$savehtml);
	$echo['msg'].=pw_writecontent ($_SERVER['DOCUMENT_ROOT']."/$get[page]/$htmlfile",$savehtml,$maxsize=102400);
	pw_resizefromhtml($savehtml);
}

// *** Set One HomePage
if ($_POST['home']) mysqli_query(_pw_mysqli(), "update webpages set home='0' where online='1'");

//PHP psywizard@mail.com PW120905
?>
