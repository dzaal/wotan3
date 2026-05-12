<?php
// *** CHmod
if($set['chmod']) $chmod=$set['chmod'];else $chmod='0777';

// *** Make New WebPage
if ($_POST['addnewitem'])
{ unset($_POST['addnewitem']);
  if (file_exists($_SERVER['DOCUMENT_ROOT']."/$_POST[name]/"))
	$echo['msg'].="Error: Could not make page.<br>Dir: /$_POST[name]/ Allready Exists<br>";else
  if (!file_exists($_SERVER['DOCUMENT_ROOT']."/share/templates/$_POST[template]"))
	$echo['msg'].="Error: Could not make page.<br>Template: $_POST[template] Not Found<br>";else
   {	// *** Copy Template
    preg_match_all('/[a-zA-Z\-_0-9\s_]+/',$_POST['name'],$_POST['filename']);
    $_POST['filename']=str_replace(' ','',implode($_POST['filename'][0]));
	wotan('filewrite/filewrite','','pw_mkdirs("/$_POST[filename]/","'.$chmod.'")');
    $obj['filewrite']->pw_copydir("/share/templates/$_POST[template]/","/$_POST[filename]/",$chmod);
    $echo['msg'].="Made Page: $_POST[name] <br>";
	$_POST['addnewitem']='AddItem';
    //echo "DEBUG:<br>".nl2br(print_r($obj['varstomysql'],TRUE));exit;
   }
}

// *** Make extra Directorys
if ($_POST['mkdirs'])
{	$mkdirs=explode(' ',$_POST['mkdirs']);
	if(is_array($mkdirs)) foreach($mkdirs as $key => $val)
	{ wotan('filewrite/filewrite','pw_mkdirs("/$_POST[name]/$val",$chmod)');
      $echo['msg'].="Created Dir $val<br>"; }
}

// *** Delete Page
if ($_GET['delid']>0 and $_GET['dq'] and $GLOBALS['post']['deletedir'])
   { wotan('filewrite/filewrite','','pw_rmdir("/{$GLOBALS[post][deletedir]}/")');
      $echo['msg'].="<b>Deleted Dir /{$GLOBALS['post']['deletedir']}/</b><br>";  }

// *** Save HtmlFile and Resize Images
if ($_POST['savehtml']) {
 $echo['msg'].=wotan('filewrite/filewrite','','pw_fwrite("/$get[page]/$htmlfile",$_POST[savehtml],1)').' bytes Saved';
	pw_resizefromhtml($_POST['savehtml']);
}

// *** Set One HomePage
if ($_POST['home']) mysqli_query(_pw_mysqli(), "update webpages set home='0' where online='1'");

// PHP www.phpwotan.com  Webpages Settings v2.01 PW120905-250306
?>