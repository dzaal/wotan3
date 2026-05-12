<?php
// *** HTMLAREA Function Runs wotan/java/htmlarea **** PW100903
function pw_htmlarea($htmlfile,$width='50%',$height='50%')
{ global $post,$content,$DOCUMENT_ROOT,$file,$file_name;
 //Save Uploaded file $file
 if ($file) copy ($file,getcwd()."/$file_name");
 //Save Changes
 if (isset($content)) 
 {$content=str_replace('\"','"',$content);$content=str_replace("\'","'",$content);
  $content=str_replace('&#36;','$',$content);$contary=explode(' ',$content);$content=null;
  foreach($contary as $key => $val) if (strstr($val,'=$')) 
  { $content.=str_replace('=','="',$val).'" '; } else $content.=$val." ";
  if($fopen=@fopen(getcwd()."/$htmlfile",'w'))
  {
  $fwrite=fwrite($fopen,$content);
  fclose($fopen);return pw_htmlfile(getcwd()."/$htmlfile");
  } else echo "Could not open file ".$htmlfile;
 }
 //HtmlArea
 $htmlarea['content']=implode('',file(getcwd()."/$htmlfile"));
 $htmlarea['content']=str_replace('$','&#36;',$htmlarea['content']);
 $htmlarea['width']=$width;$htmlarea['height']=$height;unset($post['content']);
 return pw_htmlfile("$DOCUMENT_ROOT/wotan/java/htmlarea/htmlarea.html",$htmlarea);
}

//PHP psywizard@mail.com htmlArea htmledit **** PW100903
?>