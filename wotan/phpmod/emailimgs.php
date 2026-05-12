<html><body><pre>
<?
// Test Script Settings
$REDIRECT_URL='/manage/Photos/';
rquire ($DOCUMENT_ROOT.'/share/settings/settings_admin.php');
//require ($DOCUMENT_ROOT.'/share/php/pw_lib.php');

// *** Runtime
//if (is_array($emlimg) and $chkemlforimg>0)

// *** Open Imap Connection
if ($mbox=imap_open ( $emlimg['server'], $emlimg['user'], $emlimg['pass'] )) {

/*
// *** Set log file in path $emlimg[logpath]
if (!$emlimg[logpath]) $emlimg[logpath]='/share/log/';
$logfile=$DOCUMENT_ROOT.$emlimg[logpath].'emailphotos.log';
if (!file_exists($logfile)) touch($logfile);
*/

// *** Check Email for New images
$headers=imap_headers ($mbox);
//print_r($headers);
foreach($headers as $key => $val)
{
 $imgbin=null;$msgnr=$key+1;$struc=imap_fetchstructure($mbox,$msgnr);

 // Decode Image
 if ($struc->type==5) //Image
 {
  if($struc->encoding==3) $imgbin=imap_base64(imap_fetchbody($mbox,$msgnr,'1'));//Base64
 }

 // Test
 //if ($imgbin) { 	$fopen=fopen ($DOCUMENT_ROOT.$emlimg[phdir].'test_'.$msgnr.'.jpg','wb');
 //	fwrite ($fopen,$imgbin,$maxsize=4096000);fclose($fopen); }
 
 // *** Make tmp image file
 if ($imgbin) 
 { 	$fopen=fopen ('/tmp/tmpimg_'.$msgnr,'wb');
 	fwrite ($fopen,$imgbin,$maxsize=4096000);fclose($fopen);

	// *** Make $_FILES
	if (!$emlimg[ins]['online']) $_POST['online']=$emlimg[ins]['online'];
	$_FILES[$emlimg['imgfield']][tmp_name]='tmpimg_'.$msgnr.'.jpg';
	$_FILES[$emlimg['imgfield']][name]=substr($val,strpos($val,')')+1,strpos($val,'(')-strpos($val,')')-2);
 	$_FILES[$emlimg['imgfield']][type]=strtolower($struc->subtype);
 }

 //Debug prints
 echo "$key - $val <br><br>";
 //print_r ($struc);echo'<br><hr>';
 //echo $struc->parts.'<br><hr>';//multimessage $parts is array for image(s) text htmk
 print_r($_FILES);
}

// *** Email to $webmastereml when Images is added
// if ($webmastereml and $emlimg[imgname]) pw_mail($webmastereml,'Emailimgs_ImageAdded',$emlimg[imgname]);

imap_close($mbox);} 

//PHP psywizard@mail.com emailimages **** PU310305 UNDERCONSTRUCTION
?>
</pre></body></html>