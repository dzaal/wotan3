<?php $ses['verdate']['email']=801;
class email
{ var $id='';									// * Email ID
  var $email='';								// * 1 To email Address
  var $from='';									// * 1 From email Address
  var $toemail='';								// * 1 To Email Address array for multiple Email Addresses
  var $toname='';							    // * 1 To Name array for multiple names
  var $fromemail='';							// * 1 From EmailAddress array for multiple Email Addresses
  var $fromname='';							    // * 1 From Name array for multiple names
  var $usepear=0;                               // * Use PEAR::MAIL package
  var $smtphost='';                             // * SMTP HOST (works only in PEAR::mail mode) Set to use 1 smtp server to send email forex: 'localhost'
  var $smtpport='25';                           // * SMTP PORT (works only in PEAR::mail mode)
  var $smtpuser='';                             // * SMTP AUTH username (works only in PEAR::mail mode)
  var $smtppass='';                             // * SMTP AUTH password (works only in PEAR::mail mode)
  var $smtpdebug='';                            // * Set PEAR smtp module debugging
  var $log=0;									// * Log actions to systemlog
  // * Email Header (array) set html email forexmple.
  var $header="Content-Type: text/html; charset=\"iso-8859-1\"\nMIME-Version: 1.0\n";
  // Exmpl: var $header.='Cc: address@email.com\n Disposition-Notification-To: \"Admin\" <admin@email.com>\n";
  var $subject='Mail from the website';			// * Email Subject
  var $message='Sorry No Message';				// * The (html) Email message or messages (array)
  var $msgfile='';								// * Openfile $msgfile and use as message (array)
  // ** mverrors()
  var $updqw='';								// * Update Query
  var $result=array(); 							// * Results Array
  var $system=array(); 							// * System Messages

 // *** Check 1 Email Address with PregMatch
 function chkemladdr ($email='')
 { if(!$email and $this->email) $email=&$this->email;
   if($email and $this->email)  $this->email=&$email;
   preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$email,$emlres);
   $return=0; if ($email and $email==$emlres[0]) $return=2; else if ($email) $return=1;
   $this->system['chkemladdr']='chkemladdr: $email'.$return;
   return $return;
 }

 function grepemailadrs ($emailadrs)
 { preg_match_all ('/([a-zA-Z0-9_%\-\.]+)@([a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4})/',$emailadrs,$emladrs);// * Filter Emails
   return $emladrs;
 }

 // *** Check 1 Email Address. Dest. Mailserv user exists lookup **** PW150504 L180904-201205
 //     return null-Emailaddr not checked 1-PregMatchNotOk 2-PregMatchOk 3-User does Not Exist 4-User Exist EmailAddrOK
 function chkemlserv ($from='',$email='')
 { if(!$email) $email=&$this->email;else $this->email=&$email;
   if(!$from) $from=&$this->from;else $this->from=&$from;
   //Check Email At Destination Server
   $return=$this->chkemladdr($email);
   if ($return==2) { list ($username, $domain ) = explode ("@",$email);
   getmxrr($domain, $mxhost);
   if($mxhost[0]) $mailserv = $mxhost[0]; else $mailserv = $domain;
   $connect = @fsockopen ( $mailserv, 25 );
   if ($connect) {
    stream_set_timeout($connect,20); $echo=fread ($connect,2048);
	fputs ($connect, "HELO $_SERVER[HTTP_HOST]\r\n" );
    $echo.=fread ($connect,2048);
	fputs ($connect, "MAIL FROM: <$from>\r\n");$echo.=fread ($connect,2048);
	fputs ($connect, "RCPT TO: <$email>\r\n" );$res=fread ($connect,2048);
	fputs ($connect, "QUIT\r\n");fclose($connect);
	if (strstr ($res,'550') or strstr ($res,'450')) $return=3;
	if (strstr ($res,'250')) $return=4;
 	$this->result['chkemlserv']=$echo.$res;
 	$this->system['chkemlserv']=$email.' stat='.$return;
  if ($this->log>0) pw_systemlog('file:email.php',"email::chkemailserv($email)",$echo.$res);
   }}
 return $return;
 }

 // *** SendEmail(s) PW280705 Pw3-231205
 function sendemails($input='',$usepear=0)
 { eval (arraytoclass('$input'));$n='0';
   if (!$this->toemail) $this->toemail=&$this->email;
   if (!$this->fromemail) $this->fromemail=&$this->from;
   if ($this->usepear>0) $usepear=$this->usepear;
   if (!is_array($this->toemail)) { $toemail=$this->toemail;$this->toemail=array();$this->toemail[1]=$toemail; }
   if (!is_array($this->toname))    $toname=$this->toname;
   if (!is_array($this->fromemail)) $fromemail=$this->fromemail;
   if (!is_array($this->fromname))  $fromname=$this->fromname;
   if (!is_array($this->subject))   $subject=$this->subject;
   if (!is_array($this->header))    $header=$this->header;
   if (!is_array($this->message))   $message=$this->message;
   if (!is_array($this->msgfile))   $msgfile=$this->msgfile;



  if ($usepear==1) { 
     $includePath = ini_get('include_path');
  //  ini_set('include_path',$includePath.':/usr/share/pear'); 
   require_once("Mail.php");
  
    } // * Load pear Mail.php

   // ** Loop Array $this->toemail
   foreach($this->toemail as $n => $toemail)
   {  if (is_array($this->toname) and $this->toname[$n]) $toname=$this->toname[$n];
      if (is_array($this->fromname) and $this->fromname[$n]) $fromname=$this->fromname[$n];
      if (is_array($this->fromemail) and $this->fromemail[$n]) $fromemail=$this->fromemail[$n];
	  if (is_array($this->subject) and $this->subject[$n])   $subject=$this->subject[$n];
	  if (is_array($this->header) and $this->header[$n])    $header=$this->header[$n];
	  if (is_array($this->message) and $this->message[$n])   $message=$this->message[$n];
	  if (is_array($this->msgfile) and $this->msgfile[$n])   $msgfile=$this->msgfile[$n];
      if ($msgfile) $message=implode(file(pwsp($msgfile)));

	  // ** Correct or block Errors in $toemail $fromemail $fromname block injections AntiSpam etc...
		  preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$toemail,$preg_match);
	      $toemail=$preg_match[0] ?? '';
		  preg_match ('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/',$fromemail,$preg_match);
	      $fromemail=$preg_match[0] ?? '';
      preg_match('/[0-9a-z\'\é\È\É\Ë\Ì\Í\Ï\Ò\Ó\Ö\ß\à\á\â\ä\è\é\ë\ì\í\ï\ò\ó\ö\ù\ú\ü\-\s]+/i',$fromname,$preg_match);
	      $fromname=$preg_match[0] ?? '';
      preg_match('/[0-9a-z\'\é\È\É\Ë\Ì\Í\Ï\Ò\Ó\Ö\ß\à\á\â\ä\è\é\ë\ì\í\ï\ò\ó\ö\ù\ú\ü\-\s]+/i',$toname,$preg_match);
	      $toname=$preg_match[0] ?? '';

	  // ** Send Email
      $emailay=explode("@",$toemail);
      if($fromname) $fromemail=$fromname.' <'.$fromemail.'>';
      if($toname) $toemail=$toname.' <'.$toemail.'>';
	  $headerf="From: $fromemail\nReply-To: $fromemail\n".$header;
      $this->result['sendemail_cmd'][]=($toemail.' '.stripslashes($subject).' '.str_replace("\r",'',stripslashes($headerf)));
      if ($usepear>0)
      { if(strstr($header,'html'))
        { $headers['Content-Type']='text/html; charset="ISO-8859-1"';$headers['MIME-Version']='1.0'; }
        else $headers['Content-Type']='text/plain; charset="ISO-8859-1"';
        $headers['To']=$toemail;$headers['From']=$fromemail;
        $headers['Subject']=stripslashes($subject);$body=stripslashes($message);
	        $mxhost = array();
	        if(!$this->smtphost) { $params['host'] = $emailay[1] ?? ''; if (!empty($emailay[1])) getmxrr($emailay[1], $mxhost); }
	        else $params['host']=$this->smtphost;
	        if(!empty($mxhost[0])) $params['host']=$mxhost[0];
        if($this->smtpdebug) $params['debug']=TRUE; $params['port']=$this->smtpport;
        if($this->smtpuser) { $params['auth']=TRUE; $params['username']=$this->smtpuser;$params['password']=$this->stmppass; }
        if($params['host']) { $pear_mail=&Mail::factory('smtp',$params);
                              $sendresult=$pear_mail->send($toemail, $headers, $body); }
        if (PEAR::isError($sendresult)) $this->result['sendemail_err'][]='PEAR::SMTP '.$sendresult->getMessage();
        else $this->result['sendemail_send'][]="Email {$this->id[$n]} $n $toemail is Send\r\n";
        //echo '<br>'.nl2br(print_r($pear_mail,TRUE));//echo count($pear_mail->_smtp);
      } else
      { 
      if($toemail and mail ($toemail,stripslashes($subject),stripslashes($message),str_replace("\r",'',stripslashes($headerf))))
      { 
      
      sleep(1);  // door dirk gezet op stoned ssaturday
      
	    $this->result['sendemail_send'][]="Email {$this->id[$n]} $n $toemail is Send\r\n";} else
	    $this->result['sendemail_err'][]="MAILERROR: Email {$this->id[$n]} $n $toemail is Not Send\r\n";
      } if($n>0) $emlsend=$n;
   }
   return $emlsend;
 }

 // *** Convert html $_POST['savehtml'] to htmlemail and save in html from email address and from name
 // Vars: $_POST['title'] $_POST['old_title'] $_POST['basehref'] $_POST['old_basehref']
 // $_POST['from'] $_POST['old_from'] $_POST['fromname'] $_POST['old_fromname'] $_POST['linkstyle'] $_POST['old_linkstyle']
 function htmltoemail ($postvalue)
 {     global $_POST;
      //echo 'DEBUG:'.nl2br(print_r($_POST,TRUE));
     // ** Check $_POST['savehtml'] for certain needed tags/info
      $_POST['savehtml']=stripslashes($_POST['savehtml']);// * when magicquotes are on
      preg_match('/[a-z0-9-\'&%*!`#$\s\\\]+/i',$_POST['title'],$pres);$_POST['title']=$pres[0];
      preg_match('/[a-z0-9\'-\s\\\]+/i',$_POST['fromname'],$pres);$_POST['fromname']=$pres[0];
      $_POST['old_title']=htmlentities(stripslashes(str_replace('"',"''",$_POST['old_title'])));
      $_POST['title']=htmlentities(stripslashes(str_replace('"',"''",$_POST['title'])));
      $_POST['old_fromname']=htmlentities(stripslashes(str_replace('"',"''",$_POST['old_fromname'])));
      $_POST['fromname']=htmlentities(stripslashes(str_replace('"',"''",$_POST['fromname'])));

     // * From:
	 if (!pw_grep('from:',strtolower($_POST['savehtml']),'</title>',$lenstart='a',$lenend=0))
      $_POST['savehtml']=preg_replace('/<\/title>/i',"$_POST[title]from:$_POST[from]</title>",$_POST['savehtml']);
     // * Fromname:
	 if (!pw_grep('fromname:',strtolower($_POST['savehtml']),'</title>',$lenstart='a',$lenend=0))
      $_POST['savehtml']=preg_replace('/from:/i',"fromname:$_POST[fromname] from:",$_POST['savehtml']);
     // * Link CSS
	 if (!pw_grep('link href=',strtolower($_POST['savehtml']),'>',$lenstart='a',$lenend=0) and $_POST['linkstyle'])
      $_POST['savehtml']=preg_replace('/<head>/i',"<head>\r\n".' <link href="'.$_POST['linkstyle'].'">',$_POST['savehtml']);
     // * Basehref:
	 if (!pw_grep('<base href=',strtolower($_POST['savehtml']),'>',$lenstart='a',$lenend=0) and $_POST['basehref'])
      $_POST['savehtml']=preg_replace('/<\/head>/i','<base href="'.$_POST['basehref'].'"></head>',$_POST['savehtml']);

     // ** Place updates in $_POST['savehtml']
     if ($_POST['title'] and $_POST['old_title'] and $_POST['title']!=$_POST['old_title']) // Title or Subject
     {   $_POST['savehtml']=str_replace('<title>'.$_POST['old_title'],'<title>'.$_POST['title'],$_POST['savehtml']);
         $this->result[4]='Subject Changed to '.$_POST['title']; }
     if ($_POST['from'] and $_POST['old_from'] and $_POST['from']!=$_POST['old_from'])  // * FROM
	 {	 $_POST['savehtml']=str_replace('from:'.$_POST['old_from'],'from:'.$_POST['from'],$_POST['savehtml']);
    	 $this->result[1]="FromEmailAddr Changed to $_POST[from] &nbsp;"; }
     if ($_POST['fromname'] and $_POST['old_fromname'] and $_POST['fromname']!=$_POST['old_fromname'])  // * FROM
	 {	 $_POST['savehtml']=str_replace('fromname:'.$_POST['old_fromname'],'fromname:'.$_POST['fromname'],$_POST['savehtml']);
         $this->result[5]="From Name Changed to $_POST[fromname] &nbsp;"; }
	 if ($_POST['linkstyle'] and $_POST['old_linkstyle'] and $_POST['linkstyle']!=$_POST['old_linkstyle']) // * STYLE
	 {	 $_POST['savehtml']=str_replace('"'.$_POST['old_linkstyle'].'"','"'.$_POST['linkstyle'].'"',$_POST['savehtml']);
		 $_POST['savehtml']=preg_replace('/(<style)+(.|\n)+(style>)/','',$_POST['savehtml']);
		 $this->result[2]='StyleChanged to '.basename($_POST['linkstyle']).'&nbsp;'; }
	 if ($_POST['basehref'] and $_POST['old_basehref'] and $_POST['basehref']!=$_POST['old_basehref']) // * BASEHREF
	 {	 $_POST['savehtml']=str_replace('href="'.$_POST['old_basehref'],'href="'.$_POST['basehref'],$_POST['savehtml']);
		 $this->result[3]='BaseHref Changed to '.$_POST['basehref']; }

     // ** Set $_POST from $_POST['savehtml']
     $_POST['title']=pw_grep('<title>',html_entity_decode($_POST['savehtml']),'fromname:',$lenstart='a',$lenend=0);
	 $_POST['from']=pw_grep('from:',$_POST['savehtml'],'</title>',$lenstart='a',$lenend=0);
	 $_POST['fromname']=pw_grep('fromname:',html_entity_decode($_POST['savehtml']),'from:',$lenstart='a',$lenend=0);
     $_POST['linkstyle']=pw_grep('link href="',$_POST['savehtml'],'"',$lenstart='a',$lenend=0);
     $_POST['basehref']=pw_grep('<base href="',$_POST['savehtml'],'"',$lenstart='a',$lenend=0);

     // ** Convert body to table and add body style to table
     if (!stristr($_POST['savehtml'],'<body>') and stristr($_POST['savehtml'],'<body')
         and !stristr($_POST['savehtml'],'table id="bodytbl"') )
     {  $_POST['savehtml']=preg_replace('/(<body){1}([^>]*)(>){1}/','\0'."\r\n".'<table id="bodytbl" width="100%" cellpadding="0" cellspacing="0" \2><tr><td class="body" align="center">',$_POST['savehtml']);
        $_POST['savehtml']=str_replace('</body>','</td></tr></table></body>',$_POST['savehtml']);
     }

	 // ** Take Out Scripts
 	 //$_POST['savehtml']=preg_replace('/(?:<script>)+.+(?:<script>)+/i','',$_POST['savehtml']);

     // ** Convert background= to style background:
     $_POST['savehtml']=preg_replace('/(background)+(=)+(\\\)*(\'|")+([^\'"]+)(\'|")/i','style="\1-image: url(\5);"',$_POST['savehtml']);

	 // ** Covert Stylesheet to TagStyles only where a class is set
     if (!$stylesheet=@implode(file($_POST['linkstyle']))) $this->result[4]='Could not Open/inline stylesheet '.basename($_POST['linkstyle']).' ';
     { $n=0;
	   while (strpos($_POST['savehtml'],'class=')>0) { $n=$n+1;if($n>255) break;
        preg_match('/(class=){1}("|\')+([^"\']+)("|\')+/i',$_POST['savehtml'],$style);
		preg_match("/($style[3]){1}(\s|\.|,)+[^{]*{+([^}]+)}+/i",str_replace('{',' {',$stylesheet),$css);
		$_POST['savehtml']=str_replace($style[0],'style="'.$css[3].'"',$_POST['savehtml']);
	 }}

  	 // ** PLACE $_POST['basehref'] in links from |mov|wmf|mpg|mp3|pdf|zip|jpg|gif|swf|png|jpeg|ico| files
    preg_match('/(http:\/\/+[^\/]+)\/{0,1}(.*)/i',$_POST['basehref'],$domain);
    $regfn='([^:\'"\/]+[^:\'"]+(?i)(\.mov|\.wmf|\.mpg|\.mp3|\.pdf|\.zip|\.jpg|\.gif|\.swf|\.png|\.jpeg|\.ico){1})';
    $_POST['savehtml']=preg_replace('/(\'|"|\()+(\/)+'.$regfn.'/','\1'.$domain[1].'/\3',$_POST['savehtml']);
    $_POST['savehtml']=preg_replace('/(\'|"|\()+'.$regfn.'/','\1'.$domain[1].'/'.$domain[2].'\2',$_POST['savehtml']);
 }

var $ver='phpwotan.com ClassEmail V3.21 PW150504 Pw3-070106-150107-050507-290108';
}  if(!isset($ses)) echo "<center><b>Wotan Email Class V3.21 PW150504 Pw3-070106-150107-050507-290108</b></center>";
?>