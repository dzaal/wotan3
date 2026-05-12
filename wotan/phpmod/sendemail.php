<?php
//Check Email Address with PregMatch and mailservew user exists lookup **** PW150504 LUpd180904
//return null-Emailaddr not checked 1-PregMatchNotOk 2-PregMatchOk 3-User does Not Exist 4-User Exist EmailAddrOK
function pw_chkemailaddr($from,$email,$debug=0)
{
 global $HTTP_HOST,$result;
 //Check Emailaddr
 preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$email,$emlres);
 if ($email and $email==$emlres[0]) $return=2; else if ($email) $return=1;

 //Check Email Address
 if ($return==2) { list ($username, $domain) = explode("@", $email, 2); 
  if (@getmxrr($domain, $mxhost)) $mailserv = $mxhost[0]; else $mailserv = $domain;
  $connect = @fsockopen ( $mailserv, 25 ); 
  if ($connect) { $echo=fread ($connect,2048);
	fputs ($connect, "HELO $HTTP_HOST\r\n" );$echo.=fread ($connect,2048);
	fputs ($connect, "MAIL FROM: $from\r\n");$echo.=fread ($connect,2048);
	fputs ($connect, "RCPT TO: $email\r\n" );$res=fread ($connect,2048);
	fputs ($connect, "QUIT\r\n");fclose($connect); 
	if (strstr ($res,'553')) $return=3;
	if (strstr ($res,'250')) $return=4;
 	if ($debug>0) echo $echo.'- '.$res;
 	if ($return<4) $result['chkemailaddr']=$res;
    pw_systemlog('file:share/share.php','pw_chkemailaddr() '.$email,$echo.$res);}}
 return $return;
}

//Mailer Send copy to $GLOBALS[webmastereml] to check/debug **** PW170205
function pw_mail($to,$subject,$message,$headers,$param=null)
{
mail($GLOBALS['webmastereml'],"ChkMailto: $to Subj: $subject",$message,str_replace("\r",'',$headers),$param=null);
if (mail($to,$subject,$message,str_replace("\r",'',$headers),$param)) return true;
pw_systemlog('file:share/share.php',"pw_mail() Failed send email to $to");
return false;
}

function pw_clean_email($email)
{
    $email = str_replace(array("\r", "\n"), '', trim((string)$email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

// *** SendEmail trigger $_POST['sendmail'] to: $_POST['email'] message $wotan['sendemail']
// from from message header PW200605-280705
if (isset($_POST['sendemail']))
{	$htmlemail=pw_htmlfile($wotan['sendemail'],0,0,1); // Open html Email
    // ---- Check for hdr file and open as header else grep header from emailhtml
	if (file_exists(dirname($wotan['sendemail']).'/email.hdr'))
    	 { $message=$htmlemail;$header=str_replace("\r",'',implode('',file(dirname($wotan['sendemail']).'/email.hdr'))); }
	else { $message=strstr($htmlemail,"\r\n\r\n");$header=str_replace("\r",'',str_replace($message,'',$htmlemail));}
	// ---- Preg Match Sendto Email Address from header
	if (!preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',stristr($header,'from:'),$preg_match))
	{ echo 'Error in '.basename($wotan['sendemail']).'<br>From: email Address not found in header';exit; }
	$post_email = pw_clean_email($_POST['email'] ?? '');//Check email Address
	$resultchkemail = $post_email ? pw_chkemailaddr($preg_match[0], $post_email) : 1;//Check email Address
    // ---- Get Subject from POST subject or from header
    if ($_POST['subject']) $subject=str_replace(array("\r", "\n"), '', $_POST['subject']); // from post
	else { $subject=pw_grep('Subject:',$header,"\n");
		   $header=str_replace('Subject:'.$subject."\n",'',$header); }
    // ---- Email To - Email From
	if ($post_email && mail($post_email,$subject,$message,$header) and
		mail($preg_match[0],$subject,$message,str_replace($preg_match[0],'website@'.$_SERVER['HTTP_HOST'],$header)) )
    if ($resultchkemail==3 or $resultchkemail<2) $echo['msg'].="<b>Check your Email Address</b></br>";
	if ($resultchkemail>1) $echo['msg'].="<b>Email is Send to $post_email</b></br>";
	mail($webmastereml,$subject,$message,$header); // Copy Email to Webmaster
}

// *** SendEmails trigger $_POST['sendmails'] Send 1 or more Emails Dep Arrays  PW280705 Lupd300805
if ($_POST['sendemails']>0)
{ //Vars:
  if (!is_array($_POST['toemail'])) $_POST['toemail'][1]=$_POST['toemail'];
  if (!is_array($_POST['fromemail'])) $fromemail=$_POST['fromemail'];
  if (!is_array($_POST['subject'])) $subject=$_POST['subject'];
  if (!is_array($_POST['header'])) $header=$_POST['header'];
  if (!is_array($_POST['message'])) $message=$_POST['message'];
  if (!is_array($_POST['msgfile'])) $msgfile=$_POST['msgfile'];

  //Loop Array $_POST['toemail']
  foreach($_POST['toemail'] as $n => $toemail)
  {   if (is_array($_POST['fromemail']) and $_POST['fromemail'][$n]) $fromemail=$_POST['fromemail'][$n];
	  if (is_array($_POST['subject']) and $_POST['subject'][$n])   $subject=$_POST['subject'][$n];
	  if (is_array($_POST['header']) and $_POST['header'][$n])    $header=$_POST['header'][$n];
	  if (is_array($_POST['message']) and $_POST['message'][$n])   $message=$_POST['message'][$n];
	  if (is_array($_POST['msgfile']) and $_POST['msgfile'][$n])   $msgfile=$_POST['msgfile'][$n];
      if ($msgfile) $message=pw_htmlfile($msgfile,$pw_pageinfo,999,1);

	  $toemail = pw_clean_email($toemail);
	  $fromemail = pw_clean_email($fromemail);
	  //Check To Email Address
	  preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$toemail,$preg_match);
	  $resultchkemail = ($toemail && $fromemail) ? pw_chkemailaddr($preg_match[0],$fromemail) : 1;//Check email Address
      if ($resultchkemail==3 or $resultchkemail<2) $echo['msg'].="<b>Check your Email Address $toemail</b></br>";
   	  if ($resultchkemail>1 and $_POST['confirm']>0) $echo['msg'].="<b>Email is Send to $toemail</b></br>";

	  // Send Email
	  $subject = str_replace(array("\r", "\n"), '', (string)$subject);
	  $header = str_replace(array("\r", "\n"), '', (string)$header);
	  $headerf="From: $fromemail\nReply-To: $fromemail\n".$header;
      if($toemail && $fromemail && !mail ($toemail,stripslashes($subject),stripslashes($message),str_replace("\r",'',stripslashes($headerf)))) $echo['msg']="MAILERROR: Email $toemail is Not Send<br>";
  }
}

//Set postemail
if(!$post['email']) $post['email']=$echo['headerfrom'];

//PHP psywizard@mail.com Chk an EmailAddress SendMail ** PW170705 Lupd260905
?>
