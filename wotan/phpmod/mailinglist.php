<?php
// *** Dependencies:
//     | email.php V3.02 or higher | filewrite.php V3.x | pw_htmlfile4.php |
//     | post_conv2.php | mysqltovars3.php | varstomysql3.php | mysql3.php  |

/*   Remember for phpwotan.com Programmer:
     wotan() calls in a function or class.
     When you use for settings localvars, they need to be:
     1.Setglobal 2.Open 3.With object reference.
     The object var can be a setting aswell so for 3. the right object var need to be obtained.
     Because of that methode 1. or 2. are more easy and recommended.
*/

class mailinglist
{ var $result=array(); 							// * Results Array
  var $system=array(); 							// * System Messages
  var $errors;                                  // * Errors
  var $table='emails';                          // * Name Emails Database table
  var $itmppage=8;                              // * Items Per Page
  var $listpath='/mailinglist2/';                // * (Un)subscribe Emails Comfirmations
  var $emlflpath='/mailinglist2/emails/';        // * EmailFilesPath
  var $logflpath='/share/log/mailinglist/';      // * LogFiles Path
  var $debug='';                                // * Debug data
  var $mailist='category';                      // * FieldName that Stores MailistNames
  // ** dbtomail()
  var $header="Content-Type: text/html; charset=\"iso-8859-1\"\nMIME-Version: 1.0\n";
                                                // * Email Header Default HtmlEmail
  var $email='';								// * 1 To email Address
  var $from='';									// * 1 From email Address
  var $subject='';                              // * Email Subject
  var $toemail='';								// * To Email Address(es) array for Sending multiple Emails
  var $fromemail='';							// * From EmailAddress(es) array for Sending multiple Emails
  var $logfile='';		                        // * Logfile where dbtomail keeps the mailing status
  var $query='';                                // * Maillist $query with autoadded:[ order by id limit $this->loglim,$this->limit ]
  var $emladdrfld='email'; 						// * DbTbl field which contains EmailAddress
  var $evalmessage='';							// * Eval Message for Dynamic email message (if='' then use this->message)
  var $loglim=0;								// * Last dbitem that has been send
  var $limit=10;								// * Send $limit email(s) per page request.
  var $totalemls=0;                             // * Total Emails found in $query
  var $testemail=0;                             // * when =1 dbtomail doesnot send mail but runs chkemlserv
  var $emlssend=0;                              // * Number Emails send (in last Refresh)
  var $fwrite=0;                                // * Number Bytes written to Logfile

 // *** mailinglists > opens dbtbl $GLOBALS[table] (emails) and list emails in cats (mailinglists) with Search and Order Addnewitem UpdateItem
 function mailinglists($itmppage=20,$mitems=10)
 { global $table,$ses,$get,$post,$_GET,$_POST;
   if($table) $this->table=$table;
   if($this->itmppage!=8 and $itmppage==20) $itmppage=$this->itmppage; else $this->itmppage=$itmppage;

   // ** Categories=MailingLists Filename Filter
   $_POST['category']=$this->fnfilter($_POST['category']);

   // * Add Item
   if ($_POST['addnewitem'])
   { $_POST['online']=1;
     wotan('filewrite/filewrite','','pw_mkdirs("'.$this->emlflpath.'$_POST[category]/")');
   }
   // ** Update item
   if ($_POST['upditem'] and $_POST['email']!=$_POST['oldemail'])
   { $_POST['chkemail']=0;$_POST['chkemlserv']=''; }
   wotan('varstomysql3/varstomysql','',array('table=$table','runall($_POST+$_GET)'));

   // ** Cats(maillists) Sorting search
   if (!$get['order']) $get['order']='email';                // * Default Sort
   if (!isset($get['category'])) $get['category']='All';     // * Default List
   if ($get['category']=='All') unset($get['category']);     // * All
   if ($get['category']) $qwcat="category='$get[category]' and"; // * Categorys
   if ($post['search']) {
	$post['search']=str_replace('*','%',$post['search']); // * Search
    $searchqw="(email like '%$post[search]%' or name like '%$post[search]%' or telnr like '%$post[search]%') and"; // * Search Query
    if($post['search']=='online' or $post['search']=='active') $searchqw="online='1' and"; // * Search Online
    if($post['search']=='offline' or $post['search']=='notactive') $searchqw="online='0' and"; } // * Search offlineline
    if($post['search']=='errors') $searchqw="(chkemail='1' or chkemail='3') and"; // * Search Errors
   // ** Emaillist Query
   $GLOBALS['query']="select * from $table where $searchqw $qwcat users_id>'0' order by $get[order] $get[desc]";
   if($post['search']=='deleted')
   $GLOBALS['query']="select * from $table where $qwcat users_id='0' order by $get[order] $get[desc]";
   return wotan('mysqltovars3/mysqltovars','',$_GET+array('table=$table','itmppage='.$itmppage,'mitems='.$mitems,'query=$query','runall()'));
 }

 // *** Import Email address textarea file url Run Actions ( see function actions() )
 function extraoptions()
 { global $table,$ses,$get,$post,$_GET,$_POST,$echo,$_FILES;
   // ** Import Email Addresses
   wotan('email');$n=0; // Load Email Module
   if ($_POST['impemladr'] and $get['category'])
   { $emladrs=email::grepemailadrs ($_POST['emladrs']);
     foreach($emladrs[0] as $key => $eml)
     { $name = $emladrs[1][$key];
       $todb['email']=$eml; $todb['name']=$name;
       $todb['category']=$get['category'];$todb['online']=1;
       pw_arraytodb($table,$todb); $n++;
     } $echo['msg']="$n Email Addresses Added to Maillist $get[category]";
   }

   // ** Import Email Addresses from Local File
   if ($_POST['getemlfile'] and $_FILES['emladrfile']['tmp_name'])
   { $emladrs=email::grepemailadrs (implode(file($_FILES['emladrfile']['tmp_name']))); }

   // ** Import Email Addresses from URL
   if ($_POST['getemlurl'] and $_POST['emladrurl'])
   { $emladrs=email::grepemailadrs(implode(file($post['emladrurl']))); }

   // ** Show Results in input Field
   if (is_array($emladrs[0])) { $post['emladrs']=implode('; ',$emladrs[0]); }

   // ** Run Actions
   $echo['msg'].=$this->actions('',$table,'category',$get['category'],$_POST['action'],$_POST['acttxt'],0,$this->logflpath);
 }

 // *** View Log Files with Delete possibilty for resetting counter; ReSending/ReChecking
 function viewlogs()
 { global $_POST,$echo;wotan('fileread');
   // ** Delete LogFile
   if ($_POST['dellog'] and $_POST['logfile'] and strstr($_POST['logfile'],'_old'))
    { unlink(pwsp($this->logflpath.$_POST['logfile']));$_POST['dellog']=''; }
   if ($_POST['dellog'] and $_POST['logfile'])
    rename(pwsp($this->logflpath.$_POST['logfile']),pwsp($this->logflpath.substr($_POST['logfile'],0,strpos($_POST['logfile'],'.log')).'_old.log'));
  // ** Readdir
  return fileread::dirtoarray($this->logflpath,'errors.log');
 }

 // *** File Browser for Emails
 function emailbrowser()
 {  global $get,$post,$_POST,$echo,$htmlfile,$content;
    wotan('fileread');

    // ** Dir/File Switch
	if(!$get['category']) { $echo['area']='emails/?category='; $_POST=array(); }
	    else $echo['area']='editemail/?email=';

    // ** Import Email from Url -> set addnewemail
    if ($_POST['getemlurl'] and $_POST['emlurl']) $_POST['addneweml']=1;

    // ** Add New Email
	if ($_POST['addneweml'])
		{ wotan('filewrite/filewrite','','pw_mkdirs("'.$this->emlflpath.'$get[category]/newemail/")');
          $echo['msg']=wotan('filewrite/filewrite','','pw_copydir("newemail/","'.$this->emlflpath.'$get[category]/newemail/")'); }

    // ** Delete Email
	if ($_POST['deleteml'])
		{ $echo['msg']=wotan('filewrite/filewrite','','pw_rmdir("'.$this->emlflpath.'$get[category]/$_POST[oldfn]/")'); }

    // ** Filename Filter
    $_POST['newfn']=$this->fnfilter($_POST['newfn']);

    // ** Copy Email
	if ($_POST['copy'])
		{ wotan('filewrite/filewrite');
          if (filewrite::pw_copydir($this->emlflpath."$get[category]/$_POST[oldfn]/",$this->emlflpath."$get[category]/$_POST[newfn]/"));
          $emailfile=implode(file(pwsp($this->emlflpath."$get[category]/$_POST[newfn]/$htmlfile")));
          $emailfile=str_replace("/$_POST[oldfn]/","/$_POST[newfn]/",$emailfile);
          filewrite::pw_fwrite($this->emlflpath."$get[category]/$_POST[newfn]/$htmlfile",$emailfile);
		  $echo['msg']="Email $_POST[oldfn] Copied to $_POST[newfn]<br>"; }

    // ** Import Email from Url
    if ($_POST['getemlurl'] and $_POST['emlurl'])
    { $_POST['newfn']=str_replace('www','',str_replace('http','',$this->fnfilter($_POST['emlurl'])));
      rename(pwsp($this->emlflpath."$get[category]/newemail/"),pwsp($this->emlflpath."$get[category]/$_POST[newfn]/"));
      //$content=addslashes(implode(file($_POST['emlurl'])));
      $_POST['savehtml']=@implode(file($_POST['emlurl']));
      if(!$_POST['savehtml']) { $echo['msg']=$_POST['emlurl'].' 404 Page not found'; } else
      { if (!pw_grep('<base href="',strtolower($_POST['savehtml']),'>',$lenstart='a',$lenend=0))
        $_POST['savehtml']=preg_replace('/<\/head>/i','<base href="'.$_POST['emlurl'].'"></head>',$_POST['savehtml']);
        //$_POST['savehtml']=str_replace('</head>','<base href="'.$_POST['emlurl'].'"></head>',$_POST['savehtml']);
        if ($_POST['savehtml']) $size=wotan('filewrite/filewrite','','pw_fwrite("'.$this->emlflpath.'$get[category]/$_POST[newfn]/$htmlfile",$_POST[savehtml])');
        $echo['msg']="Email $_POST[newfn] Size ".round($size/1024).'kB Created';
      }
    }

   // ** List Emails & Cats
   return fileread::dirtoarray($this->emlflpath."$get[category]/",'errors.log');
 }

 // *** HtmlEmailEdit controls: basehref (filepaths), CssLink, from, title, subject
 // runs [email::htmltoemail] [pw_resizefromhtml() (gd_lib.php)] [Save File] [send testemail]
 function htmlemailedit ()
 { global $echo,$ses,$get,$_POST,$post,$htmlfile,$htmlemlfile,$savehtml;
   wotan('fileread');$echo['msg']='';$ses['nrsendemls']=0;$savehtml=$_POST['savehtml'];
   $htmlemlfile=$this->emlflpath."$get[category]/$get[email]/$htmlfile";

   // ** Open Email
   if(!$_POST['savehtml']) $_POST['savehtml']=implode(file(pwsp($htmlemlfile)));
   else $_POST['savehtml']=stripslashes($_POST['savehtml']);

   // ** Add Unsubscribe Link
   if ($_POST['unsub'])
   { $_POST['savehtml']=str_replace('</body>',
	   '<br><center><a href="http://'.$_SERVER['HTTP_HOST'].$this->listpath.'unsubscribe/?email=$email&category='.$get['category'].'">
		UnSubScribe from this Emaillist</a><br><br></body>',$_POST['savehtml']);
		$echo['msg'].="UnSubScribe Link Added to Email ";
   }

   // ** Set Default $_POST when isn't set
   if(!$_POST['savehtml']) $_POST['savehtml']=implode(file(pwsp($htmlemlfile))); // * Open EmailFile
   if(!$_POST['title']) $_POST['title']="$get[category] $get[email]";
   if(!$_POST['from']) $_POST['from']="maillist@phpwotan.com";
   if(!$_POST['testemail'] and !$post['testemail']) $_POST['testemail']='maillist@phpwotan.com';
   if(!$_POST['linkstyle']) $_POST['linkstyle']='http://'.$_SERVER['HTTP_HOST'].'/share/styles/default.css';
   if(!$_POST['basehref']) $_POST['basehref']='http://'.$_SERVER['HTTP_HOST'].$this->listpath."emails/$get[category]/$get[email]/";

   // ** Convert Html to EmailHtml
   $result=wotan('email/email','','htmltoemail($_POST)');$echo['msg'].=@implode(' ',$result);

   // ** Save Html
   if($_POST['upditem'] or $_POST['unsub'])
   { $size=wotan('filewrite/filewrite','','pw_fwrite ($htmlemlfile,$_POST[savehtml],1)');
     pw_resizefromhtml($_POST['savehtml']); // * Resize Image to size set in $_POST[savehtml]
	 if ($size>0) $echo['msg'].="<b>Email $get[email] Saved $size Bytes</b>";
     $_POST['savehtml']=implode(file(pwsp($htmlemlfile))); // * ReOpen EmailFile To see results of changes
   }

   // ** Send Test Email
   if ($_POST['sendtest'])
   { //eval ('$savehtml=$_POST[\'savehtml\'];');
     $savehtml=wotan('pw_htmlfile4/pw_htmlfile','','evalcode($_POST[savehtml])');
     $savehtml=wotan('pw_htmlfile4/pw_htmlfile','','evalcodetags($savehtml)');
	 wotan('email/email','',array('toemail=$_POST[testemail]','fromemail=$_POST[from]','subject=$_POST[title]','message=$savehtml','sendemails()'));
	 $echo['msg']='TestEmail Send to '.$_POST['testemail'];
   }
   if(is_array($_POST) and is_array($post)) { $post=$_POST;$post['subject']=$post['title']; }
   else { echo'!mailinglist.php FATAL ERROR Missing PHP POST array and/or Session post array!';exit; }  // * Save New $_POST in sesvar $post
 }

 // *** Maillist Send HtmlEmails to emailaddressess stored in database
 // HtmlEmails processed via pw_htmlfile4 module
 // a refresh iframe is needed that sends every refresh x emails
 // used: function dbtomail() | pw_htmlfile4
 // dbtoemail[testemail]=1 emails will be not send but checked
 function sendemails()
 { global $get,$post,$htmlfile,$echo;
   //wotan('fileread'); // * Load FileRead
   $this->logfile=$this->logflpath."$get[category]_$get[email].log"; // * Status LOG
   if(!$this->from) $this->from=$post['from'];
   if(!$this->subject) $this->subject=$post['subject'];
   //$this->testemail=1; // * TESTMODE
   if ($this->testemail==1)
   { $this->logfile=$this->logflpath."$get[category]_chkemails.log"; // * Status LOG
     $this->limit=1;
   } else
   if(!$this->evalmessage) $this->evalmessage='wotan(\'pw_htmlfile4/pw_htmlfile\',\'$obj[htmleml]\',array(\'htmlfile="'.$this->listpath.'emails/$get[category]/$get[email]/$htmlfile"\',\'incbody=1\',\'runall()\'));'; // * Open EmailFile and parse with pw_html4
   if(!$this->query) $this->query="select * from emails where users_id>'0' and category='$get[category]' and online='1'"; // * DbQuery
   $this->emlssend=$this->dbtomail();     // * Run email->dbtomail()
   $echo['nrsendemls']=$this->loglim+$this->emlssend;          // * Nr email send
   $echo['totalemls']=$this->totalemls;                  // * Total Emails
   if ($echo['nrsendemls']>$echo['totalemls']) $echo['totalemls']=$echo['nrsendemls'];
   if ($echo['nrsendemls']==$echo['totalemls']) { $echo['refresh']='00';$echo['done']='Mailing is Done'; } // * Stop
   else { $echo['refresh']='';$echo['done']=''; }
   return wotan('pw_htmlfile4/pw_htmlfile','$obj[mailframe]',array('evalcode($obj[mailframe]->openfile($htmlfile,1))')); // * Open Emailer html with status
 }

 // *** Query mysql for email addresses and sendmails to this addressess
 function dbtomail ($input='')
 { global $echo,$obj;
   eval (arraytoclass('$input'));$n=0;wotan('email/email');
   if($this->logfile) $this->logfile=pwsp($this->logfile);
   if(!$this->evalmessage) $this->evalmessage="'$this->message'";

   // ** Open LogFile
   if(!file_exists($this->logfile)) { touch($this->logfile);chmod($this->logfile,0777); }
   $this->loglim=implode(file($this->logfile));
   if(!$this->loglim) $this->loglim=0;

   // ** Count total Emails
   $cntname=str_replace('.','',basename($this->logfile));
   if ($this->loglim<1) $GLOBALS['ses'][$cntname]='';
   if (!$GLOBALS['ses'][$cntname]) {
     $resourse=mysqli_query(_pw_mysqli(), "select count(id) as nremails ".strstr($this->query,'from'));
    if ($resourse)
    { $count=mysqli_fetch_row($resourse); $GLOBALS['ses'][$cntname]=$this->totalemls=$count[0]; }}
   else $this->totalemls=$GLOBALS['ses'][$cntname];

   // ** Run $this->query SendEmail or Check EmailAddress if $this->testemail isset
   $email=array();$email['fromemail']=&$this->from;$email['subject']=&$this->subject;$email['header']=$this->header;
   $resourse=mysqli_query(_pw_mysqli(), "$this->query order by id limit $this->loglim,$this->limit");
   if ($resourse) while ($result=@mysqli_fetch_assoc($resourse))
   { $n++;$email['email']=$result[$this->emladdrfld];$email['id']=$result['id'];
     $GLOBALS['result']=$this->result['dbtomail']=$result;
     eval ('$email[message]='.$this->evalmessage.';');
     if($this->testemail!=1) { email::sendemails($email); }
     else if ($result['chkemlserv']=='')
      { $email['toemail']=email::grepemailadrs ($email['email']);
        $email['toemail']=$email['toemail'][0][0];
        $this->result['chkemail']=wotan('email/email','','chkemlserv("'.$email['fromemail'].'","'.$email['toemail'].'")');
        $this->result['chkemlserv']=$echo['chkemlserv']=$obj['email']->result['chkemlserv'];
          preg_match('/from\s+([^\s]+)\s+/',$this->query,$table);
          pw_arraytodb($table[1],$this->result,"id='{$this->result[dbtomail][id]}'",1);
          //echo '<br>DEBUG:'.nl2br(print_r($obj,TRUE));exit;
      } else $echo['chkemlserv']='Allready Checked<br><br>'.$result['chkemlserv'];
     $fopen=fopen($this->logfile, 'wb'); $this->fwrite=fwrite($fopen,($this->loglim+$n)).'bytes'; fclose($fopen);
   } else $this->errors=mysqli_error(_pw_mysqli())." | query: $this->query order by timestamp limit $this->loglim,$this->limit";

   // ** Results
   $this->result['mailssend']=$n;
   $this->system['dbtomail']='email->dbtomail '."$n Emails Send\r\n";
   return $n;
 }

 // *** Check Email Addresses
 function chkemails()
 { global $_POST,$get,$echo,$obj,$_SERVER;
   $table=$this->table;
   if(!$get['category']) $echo['msg']="<br>Select Maillist";
   // ** Count Querys for Status
   $result['checked']=pw_dbtoarray("select count(chkemail) as nr from $table where users_id>'0' and category='$get[category]' and chkemail>'0'");
   $result['correct']=pw_dbtoarray("select count(chkemail) as nr from $table where users_id>'0' and category='$get[category]' and chkemail='4'");
   $result['incorr'] =pw_dbtoarray("select count(chkemail) as nr from $table where users_id>'0' and category='$get[category]' and (chkemail='1' or chkemail='3')");
   $echo['unknown']=$result['checked'][1]['nr']-$result['correct'][1]['nr']-$result['incorr'][1]['nr'];
   $domainname=str_replace('www.','',$_SERVER['HTTP_HOST']);
   // ** ChkEmail
   if(!$_POST['chkemail'] and $_GET['chkemail']) { $_POST['chkemail']=$_GET['chkemail'];$_POST['sendtest']=1; }
   $result['chkemlserv']=wotan('email/email','','chkemlserv("test@'.$domainname.'",$_POST[chkemail])');
   $todb['chkemail']=$result['chkemlserv'];$todb['chkemlserv']=$obj['email']->result['chkemlserv'];
   if($todb['chkemail']>0) pw_arraytodb($table,$todb,"email='$_POST[chkemail]'",1); // * Save to database
   if($result['chkemlserv']==4) $echo['chkemlserv']='Address OK';
   if($result['chkemlserv']==3 or $result['chkemlserv']==1) $echo['chkemlserv']='Address Incorrect';
   $echo['action']=$this->actions("",$table,"category",$get["category"],$_POST["action"],$_POST["acttxt"],0); // * Run Actions
   if(!$echo['action']) $echo['server']=nl2br($obj['email']->result['chkemlserv']);
   if(!$_POST['sendtest']) $echo['servstat']='none'; // * Display result on/off
   return $result;
 }

 // *** Actions Mainly dbtable $table Maintenance
 function actions($input='',$table='',$listfield='',$maillist='',$action='',$acttxt='',$debug='',$logflpath='')
 { global $post,$get;
   if (is_array($input)) extract($input,EXTR_SKIP);
   if ($debug) { echo'<br>Debug maillist.php line ~327'.$action.' '.$_POST['acttxt'].'<br>'; }
   $_POST['acttxt']=$this->fnfilter($_POST['acttxt']);
   if ($post['search'])
   { $post['search']=str_replace('*','%',$post['search']); // * Search
     $searchqw="(email like '%$post[search]%' or name like '%$post[search]%' or telnr like '%$post[search]%') and"; // * Search Query
     if($post['search']=='online' or $post['search']=='active') $searchqw="online='1' and"; // * Search Online
     if($post['search']=='offline' or $post['search']=='notactive') $searchqw="online='0' and";
     if($post['search']=='errors') $searchqw="(chkemail='1' or chkemail='3') and";
   }
   $this->query="update $table set $listfield='$acttxt' where $searchqw $listfield='$maillist' and users_id>'0'";
   if($post['search']=='deleted')
   $this->query="update $table set $listfield='$acttxt',users_id='1' where $listfield='$maillist' and users_id='0'";

   if ($action=="mverrorlist" and $acttxt) { mysqli_query(_pw_mysqli(), "update $table set $listfield='$acttxt' where users_id>'0' and $listfield='$maillist' and (chkemail='1' or chkemail='3')");
       $return=mysqli_affected_rows(_pw_mysqli())." Emails Moved to $acttxt"; }

   if ($action=="delerror") { mysqli_query(_pw_mysqli(), "update $table set users_id='0' where users_id>'0' and $listfield='$maillist' and (chkemail='1' or chkemail='3')");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails Deleted <br>'; }

   if ($action=="delchkeml") { mysqli_query(_pw_mysqli(), "update $table set chkemail='0',chkemlserv='' where users_id>'0' and  $listfield='$maillist'");
       @rename(pwsp($this->logflpath.$maillist.'_chkemails.log'),pwsp($this->logflpath.$maillist.'_chkemails_'.date('mdhis').'.log'));
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails where Check Status is reset <br>'; }

   if ($post['search'] and $action=="renamesrch" and $acttxt) { mysqli_query(_pw_mysqli(), $this->query);
       mkdir(pwsp($this->emlflpath.$acttxt.'/',0777));
       $return=mysqli_affected_rows(_pw_mysqli())." Emails Moved to $acttxt"; }

   if ($action=="sonline") { mysqli_query(_pw_mysqli(), "update $table set online='1' where users_id>'0' and  $listfield='$maillist' and online='0'");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails set back online <br>'; }

   if ($action=="soffline") { mysqli_query(_pw_mysqli(), "update $table set online='0' where users_id>'0' and  $listfield='$maillist' and online='1'");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails set Offline <br>'; }

   if ($action=="renamelist" and $acttxt) { mysqli_query(_pw_mysqli(), "update $table set $listfield='$acttxt' where $listfield='$maillist'");
       if(file_exists(pwsp($this->emlflpath.$get['category'])))
       rename(pwsp($this->emlflpath.$get['category']),pwsp($this->emlflpath.$acttxt.'/'));else
       mkdir(pwsp($this->emlflpath.$acttxt.'/',0777));
       $return=mysqli_affected_rows(_pw_mysqli())." Emails moved from $maillist to $acttxt <br>"; }

   if ($action=="delemllist") { mysqli_query(_pw_mysqli(), "update $table set users_id='0' where users_id>'0' and  $listfield='$maillist'");
       $return=mysqli_affected_rows(_pw_mysqli())." Emails Deleted in $maillist<br>"; }

   if ($action=="deldoubles") {
       mysqli_query(_pw_mysqli(), "delete emails from (select id,timestamp,email,count(email) as cnteml from emails where $listfield='$maillist' group by email) as countemails left join emails using (id) where countemails.cnteml>'1'");
       $return=mysqli_affected_rows(_pw_mysqli())." Double Emails Deleted from $maillist<br>( tip: Run Double Check again when Double > 0 )<br>"; }

   // ** CleanUp Deleted Items older then 2 weeks
   if ($action) { mysqli_query(_pw_mysqli(), "delete from $table where $listfield='$maillist' and users_id='0' and UNIX_TIMESTAMP(timestamp)<".(time()-24*3600*14));
    if ($delemls=mysqli_affected_rows(_pw_mysqli())>0) $return.=$delemls.' Emails Cleaned from Trash<br>'; }
   return $return;
 }

 // *** Filename Filter
 function fnfilter($fn)
 { $fn=str_replace(' ','_',$fn);
   preg_match_all('/[0-9a-z\-_]+/i',$fn,$filename);
   return implode($filename[0]);
 }

var $ver='phpwotan.com Maillinglist V3.03B Pw3-070106 281206-170107-090207';
}  if(!isset($ses)) echo "<center><b>Wotan V3.03B Maillinglist Class</b></center>";
?>
