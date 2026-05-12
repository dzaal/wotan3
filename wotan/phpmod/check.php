<?php
// *** Check $chkfiles
if(!is_array($chkfiles))
{ echo 'File Dir Version Accessrights Checker V1.0 -280607-<br> Missing Filelist'; exit; }
// *** Start Check
foreach($chkfiles as $key => $file)
{ $file=explode('|',$file);
  echo '<tr><td align="left">'.$file[0].'</td><td align="right" valign="top">';
  // ** Chk Exists & Read Acces
  if(!is_readable(pwsp($file[0]))) { echo '<font color="red"><b> is MISSING! X</b></font>'; }
  else // ** Include error check Version Check
  {  echo '<font color="#009900"><b> FIND</b></font>';// * Find File
     // ** Error Check via Include File
     if($file[1]=='inc')
     { echo'<font style="color:red;position: relative;left:-388px;background-color: #FFEEEE ;">';
       include_once(pwsp($file[0]));echo'</font>';
     }
     // ** Version Check
     if($file[2]>0)
     {   $filecnt=implode(file(pwsp($file[0])));
         $verlines=stristr($filecnt,'var $ver');
         if(!$verlines) { $verlines=stristr($filecnt,'phpwotan.com'); }
         preg_match('/\sV([\d\s]+\.[\d\s]+)\s/i',$verlines,$pres);
         if($pres[1]<1) $pres[1]=' <b>?</b>&nbsp; </font>';
         if($pres[1]<$file[2]) echo ' V'.$pres[1].'<font color="#F05000"><b> Req: V'.$file[2].'</b></font>';
         else echo '<font color="#009900"> V'.$pres[1].'</font>';
     }
  }
  // ** Chk Write Access
  if($file[3]=='w') if(!is_writable(pwsp($file[0]))) { echo '<font color="red"><b> NOACCESS! X </b></font></td></tr>';$nok=1; }
    else echo'<font color="#009900"> & <b>Writeable</b> </font>';
  echo '</td></tr>';
} echo '<tr><td colspan=2>'.str_replace("Array<br />",'',nl2br(print_r($errors,TRUE))).'</td></tr>';
//var $ver='phpwotan.com CheckFiles V1.0 PW280607';
?>