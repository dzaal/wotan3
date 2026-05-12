<?php
class emaillib
{ var $result=array(); 							// * Results Array
  var $system=array(); 							// * System Messages

 function grepemailadrs ($emailadrs)
 { preg_match_all ('/([a-zA-Z0-9_%\-\.]+)@([a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4})/',$emailadrs,$emladrs);// * Filter Emails
   return $emladrs;
 }

 // *** Convert html $_POST['savehtml'] to htmlemail
 function htmltoemail ($post)
 {	 // ** Check $post['savehtml'] for certain needed tags/info
     // * From:
	 if (!pw_grep('from:',$post['savehtml'],'</title>',$lenstart='a',$lenend=0))
      $post['savehtml']=str_replace('</title>',"$post[title] from:$post[from]</title>",$post['savehtml']);
     // * Put stylesheet link between <!-- -->:
	 	//$post['savehtml']=preg_replace('/(\s<)+((link href)+(.)+(>))/','<!-- \2 -->',$post['savehtml']);
     // * Link CSS
	 if (!pw_grep('link href',$post['savehtml'],'>',$lenstart='a',$lenend=0))
        $post['savehtml']=str_replace('</head>','<!-- link href="'.$post['linkstyle'].'"> --></head>',$post['savehtml']);
     // * Basehref:
	 if (!pw_grep('<base href=',$post['savehtml'],'>',$lenstart='a',$lenend=0))
     	$post['savehtml']=str_replace('</head>','<base href="'.$post['basehref'].'"></head>',$post['savehtml']);

     // ** Place updates in $post['savehtml']
     if ($post['title'] and $post['old_title'] and $post['title']!=$post['old_title']) // Title or Subject
     {   $post['savehtml']=str_replace($post['old_title'],$post['title'],$post['savehtml']);
         $this->result[4]='Subject Changed to '.$post['title']; }
     if ($post['from'] and $post['old_from'] and $post['from']!=$post['old_from'])  // * FROM
	 {	 $post['savehtml']=str_replace($post['old_from'],$post['from'],$post['savehtml']);
    	 $this->result[1]="FromEmailAddr Changed to $post[from] &nbsp;"; }
	 if ($post['linkstyle'] and $post['old_linkstyle'] and $post['linkstyle']!=$post['old_linkstyle']) // * STYLE
	 {	 $post['savehtml']=str_replace($post['old_linkstyle'],$post['linkstyle'],$post['savehtml']);
		 $post['savehtml']=preg_replace('/(<style)+(.|\n)+(style>)/','',$post['savehtml']);
		 $this->result[2]='StyleChanged to '.basename($post['linkstyle']).'&nbsp;'; }
	 if ($post['basehref'] and $post['old_basehref'] and $post['basehref']!=$post['old_basehref']) // * BASEHREF
	 {	 $post['savehtml']=str_replace($post['old_basehref'],$post['basehref'],$post['savehtml']);
		 $this->result[3]='BaseHref Changed to '.$post['basehref']; }

     // ** Set $post from $post['savehtml']
     $post['title']=pw_grep('<title>',$post['savehtml'],' from:',$lenstart='a',$lenend=0);
	 $post['from']=pw_grep('from:',$post['savehtml'],'</title>',$lenstart='a',$lenend=0);
     $post['linkstyle']=pw_grep('link href="',$post['savehtml'],'"',$lenstart='a',$lenend=0);
     $post['basehref']=pw_grep('<base href="',$post['savehtml'],'"',$lenstart='a',$lenend=0);

     // ** Convert body to table and add body style to table
     //if (!strstr($post['savehtml'],'<body>') and strstr($post['savehtml'],'<body'))
     //{ $post['savehtml']=preg_replace('/(<body){1}([^>]*)(>){1}/','<table width="100%" \2><tr><td class="body" align="center">',$post['savehtml']);
     //  	$post['savehtml']=str_replace('</body>','</td></tr></table></body>',$post['savehtml']);
     //}

	 // ** Take Out Scripts
 	 $post['savehtml']=preg_replace('/(<script)+([^s]+[^c]+[^r]+[^i]+[^p]+[^t]+[^>])+(script>)+/','',$post['savehtml']);

     // ** Convert background= to style background:
    $post['savehtml']=preg_replace('/(background)+(=)+(\\\)*(\'|")+([^\'"]+)(\'|")/','style="\1-image: url(\5);"',$post['savehtml']);

	 // ** Covert Stylesheet to TagStyles only where a class is set
     if (!$stylesheet=@implode(file($post['linkstyle']))) $this->result[4]='Could not Open/inline stylesheet '.basename($post['linkstyle']).' ';
     { $n=0;
	   while (strpos($post['savehtml'],'class=')>0) { $n=$n+1;if($n>255) break;
        preg_match('/(class=){1}("|\')+([^"\']+)("|\')+/',$post['savehtml'],$style);
		preg_match("/($style[3]){1}(\s|\.|,)+[^{]*{+([^}]+)}+/",str_replace('{',' {',$stylesheet),$css);
		$post['savehtml']=str_replace($style[0],'style="'.$css[3].'"',$post['savehtml']);
	 }}

	 // ** Include Stylesheet Inline Turned off cause many emailers do not support this
     // Inline tags works with more emailers and much better. Also other code is dep on inline system
    //	 if (!pw_grep('<style',$post['savehtml'],'/style>',$lenstart='a',$lenend=0) and $post['linkstyle'])
   //	 { if (!$stylesheet=@implode(file($post['linkstyle']))) $this->result[4]='Could not Open/inline stylesheet '.basename($post['linkstyle']).' ';
  //       else {
 //   	   $post['savehtml']=str_replace('</head>','<style type="text/css">'.$stylesheet.'</style></head>',$post['savehtml']);
//	   $this->result[5]='StyleSheet '.basename($post['linkstyle']).' Placed Inline ';}
//	 }
//     $this->system=$this->result;return $this->result;

  	 // ** PLACE $post['basehref'] in links from jpg|gif|swf|png|jpeg|ico|pdf|zip| files
    preg_match('/(http:\/\/+[^\/]+)\/{0,1}(.*)/',$post['basehref'],$domain);
    $regfn='([^:\'"\/]+[^:\'"]+(?i)(\.mov|\.wmf|\.mpg|\.mp3|\.pdf|\.zip|\.jpg|\.gif|\.swf|\.png|\.jpeg|\.ico){1})';
    $post['savehtml']=preg_replace('/(\'|"|\()+(\/)+'.$regfn.'/','\1'.$domain[1].'/\3',$post['savehtml']);
    $post['savehtml']=preg_replace('/(\'|"|\()+'.$regfn.'/','\1'.$domain[1].'/'.$domain[2].'\2',$post['savehtml']);
 }

 function actions($input='',$table='',$listfield='',$maillist='',$action='',$acttxt='',$debug='',$logflpath='/share/log/')
 { if (is_array($input)) extract($input,EXTR_SKIP);
   if ($debug) { echo $action; echo $_POST['acttxt']; }
   if ($action=="mverrorlist" and $acttxt) { mysqli_query(_pw_mysqli(), "update $table set $listfield='$acttxt' where users_id>'0' and $listfield='$maillist' and (chkemail='1' or chkemail='3')");
       $return=mysqli_affected_rows(_pw_mysqli())." Emails Moved to $acttxt"; }
   if ($action=="delerror") { mysqli_query(_pw_mysqli(), "update $table set users_id='0' where users_id>'0' and $listfield='$maillist' and (chkemail='1' or chkemail='3')");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails Deleted <br>'; }
   if ($action=="delchkeml") { mysqli_query(_pw_mysqli(), "update $table set chkemail='0',chkemlserv='' where users_id>'0' and  $listfield='$maillist'");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails where Check Status is reset <br>';
       rename(pwsp($logflpath.$maillist.'_chkemails.log'),pwsp($logflpath.$maillist.'_chkemails_'.date('mdhis').'.log')); }
   if ($action=="renamesrch") $return="Sorry This Action is not yet Available <br>";
   if ($action=="sofflinesrch") $return="Sorry This Action is not yet Available <br>";
   if ($action=="sonline") { mysqli_query(_pw_mysqli(), "update $table set online='1' where users_id>'0' and  $listfield='$maillist' and online='0'");
       $return=mysqli_affected_rows(_pw_mysqli()).' Emails set back online <br>'; }
   if ($action=="renamelist") { mysqli_query(_pw_mysqli(), "update $table set $listfield='$acttxt' where $listfield='$maillist'");
       $return=mysqli_affected_rows(_pw_mysqli())." Emails moved from $maillist to $acttxt <br>"; }
   if ($action=="delemllist") { mysqli_query(_pw_mysqli(), "update $table set users_id='0' where users_id>'0' and  $listfield='$maillist'");
       $return=mysqli_affected_rows(_pw_mysqli())." Emails Deleted in $maillist<br>"; }
   if ($action=="deldoubles") {
       mysqli_query(_pw_mysqli(), "delete emails from (select id,timestamp,email,count(email) as cnteml from emails where $listfield='$maillist' group by email) as countemails left join emails using (id) where countemails.cnteml>'1'");
       $return=mysqli_affected_rows(_pw_mysqli())." Double Emails Deleted from $maillist<br>( tip: Run Double Check again when Double > 0 )<br>"; }

   // ** CleanUp Deleted Items
   if ($action) { mysqli_query(_pw_mysqli(), "delete from $table where $listfield='$maillist' and users_id='0' and UNIX_TIMESTAMP(timestamp)<".(time()-24*3600*14));
    if ($delemls=mysqli_affected_rows(_pw_mysqli())>0) $return.=$delemls.' Emails Cleaned from Trash<br>'; }
  return $return;
 }

var $ver='phpwotan.com ClassEmailLib Pw3-070106 200106-161206';
}  if(!isset($ses)) echo "<center><b>Wotan Email Libary (Extra Email.Maillist Functions) Class</b></center>";
?>
