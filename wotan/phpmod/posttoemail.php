<?php
// *** Set post vars in $echo[posttoemail]
$echo['posttoemail']='';
  if (is_array($_POST)) foreach($_POST as $key => $val)
  $echo['posttoemail'].=ucfirst($key).": $val\n\r";

// *** Eval $email
$message=pw_htmlfile($_SERVER['DOCUMENT_ROOT']."/share/emails/$sendemail");

//*** Email $message
//echo "<pre>$message</pre>";//debug
pw_mail($mail['to'],$mail['subject'],$message,$mail['header']);

//PHP psywizard@mail.com **** PW220405
?>




<?php
// Debugging
//echo print_r($_POST,TRUE);

//// Settings ////
$send=1;
//$to="mailbox@psywizard.com";
$to="zerline@wonderbaarlijksamen.nl";
$from=$_POST['Email_REQ'];//put here the Email from var you use in your form
if(!isset($_POST['Subject'])) $subject="Form Emailer";else $subject=$_POST['Subject'];

$resulthtm='verstuurd.html';
$txt[1]=("Uw EmailAdres Mist, klik terug in u browser");
$txt[2]=("<b>Sorry Error in $from</b>");
$txt[3]=("Bedankt voor uw aanmelding.<br>Er wordt contact met u opgenomen.");
$txt[4]=("<b>Sorry er is iets fout gegaan met versturen.</b>");
$txt[5]=('Sorry Verplicht Veld <font color=\"red\" Size=\"3\"><b>$fname</b></font> is niet ingevuld.
          <br>Het Formulier is niet verstuurd.<br><br>
          <a href=\"javascript:history.back();\">Klik hier om terug te gaan.</a>');

// // End Settings //

//// PHP EMAILER SCRIPT /////
//Check $to
if(!strstr($to,"@") or !strstr($to,"."))
 { echo 'Script Error Settings: TO email address is missing or incorrect check email.php';exit;}

// Make $message from Posted Form vars and Check Required Fields
if(isset($_POST) and is_array($_POST) and isset($from))
{ reset($_POST);foreach($_POST as $fcname => $fdata)
 { $fdata=stripslashes($fdata);$fname=str_replace('_REQ','',$fcname);
   if (strstr($fcname,'_REQ') and !$fdata) { $info = str_replace('$fname', $fname, $txt[5]); $send=''; }
   $fdata=stripslashes($fdata);$fname=str_replace('_REQ','',$fname);
   if ($fname=='submit') { $fname=''; $fdata=''; }
   if ($fname=="message" or strstr($fname,'header')) $message.=("\n $fdata \n\n");
   else if ($fname) $message.=("$fname: $fdata \n\n");
 }
}

////  Send Mail ////
if($send)
{ if (strstr($from,"@") and strstr($from,"."))
  { //echo "<br><br>DEBUG MAILCMD ($to,$subject,$message,'From:$from\nReply-to: $from\nX-Mailer: PW190303MF PHP-v".phpversion()."' - PHPWotan.com ')";
   if (mail ($to,$subject,$message,"From:$from\nReply-to: $from\nX-Mailer: PW190303MF PHP-v".phpversion().' - PHPWotan.com '))
   $info=$txt[3];else $info=$txt[4];
  } else if ($from) $info=$txt[2];else $info=$txt[1];
}
//echo and eval $resulthtm
if (file_exists ($resulthtm))
 echo implode('', file($resulthtm));
 else echo "$resulthtm file not found<br>Check -settings- htm file - path";

// MailForm Script phpwotan.com PW190303-upd070706 for wonderbaarlijksamen.nl  //
?>

