<?php
// *** GDLibary ImageResize ****PW210104
function pw_imagecreatefromfile($src)
{
  $size = @getimagesize($src);
  if (!$size || empty($size['mime'])) return false;

  switch (strtolower($size['mime'])) {
    case 'image/jpeg':
    case 'image/pjpeg':
      return @imagecreatefromjpeg($src);
    case 'image/png':
      return @imagecreatefrompng($src);
    case 'image/gif':
      return @imagecreatefromgif($src);
    case 'image/webp':
      return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false;
    default:
      return false;
  }
}

function pw_imagecreatetruecolorwhite($width, $height)
{
  $img = imagecreatetruecolor($width, $height);
  $white = imagecolorallocate($img, 255, 255, 255);
  imagefill($img, 0, 0, $white);
  return $img;
}

function pw_jpgresize ($src,$dst,$mwidth=10,$mheight=10,$quality=90,$keepratio=1,$zx=0,$zy=0,$zoom=0)
{
  if ($mwidth<10 or $mheight<10) return "dstSize < 10";
  $size = getimagesize($src);
  if ($size[0]<10 or $size[1]<10) return "srcSize < 10";
  if ($keepratio==1)
   {
   $scalew=$mwidth/$size[0];$scaleh=$mheight/$size[1];
   if ($scalew<$scaleh) $scale=$scalew; else $scale=$scaleh;
   $size[3]=(int)($size[0]*$scale);$size[4]=(int)($size[1]*$scale);
   } else { $size[3]=$mwidth;$size[4]=$mheight; } 
   $src_img = pw_imagecreatefromfile($src);
   if (!$src_img) return "unsupported image type";
   $dst_img = pw_imagecreatetruecolorwhite($size[3],$size[4]);
   imagecopyresampled($dst_img, $src_img, -$zoom-$zx, -$zoom-$zy, $zx, $zy, $size[3]+$zoom*2, $size[4]+$zoom*2, $size[0], $size[1]);
   imagejpeg($dst_img,$dst, $quality); imagedestroy($src_img); imagedestroy($dst_img);@chmod($dst,0777);
   return "s:$src d:$dst w:$mwidth h:$mheight q:$quality r:$keepratio=1 zx:$zx zy:$zy,zm:$zoom<br>";
}

// *** Image resize from htmlpage ***PW180805-170306
function pw_resizefromhtml ($html)
{
 global $_SERVER,$echo;
 $width='';$height='';
 $html=stripslashes($html);
 $imgtags=str_replace('<img','pw|expl|pw',$html);
 $imgtags=str_replace("'",'"',$imgtags);
 $imgtags=str_replace('>','pw|expl|pw',$imgtags);
 $imgtags=explode('pw|expl|pw',$imgtags);reset($imgtags);
 foreach($imgtags as $key => $val)
	if (strstr($val,'src='))
	{	$fnimg=pw_grep('src="',$val,'"');               // * Get dirfilename from src tag
        $fnimg=str_replace('http://'.$_SERVER['HTTP_HOST'],'',$fnimg);// * Take out domainname
		$width=(int)pw_grep('width="',$val,'"');        // * Get width
		$height=(int)pw_grep('height="',$val,'"');      // * Get height
		$imgsize=@getimagesize($_SERVER['DOCUMENT_ROOT'].$fnimg); // * Get imageSize
		//echo " DEBUG - $val - $fnimg - $width x $height - $imgsize[0] x $imgsize[1] - t: $imgsize[2]";
		if ($imgsize[2]==2 and ($imgsize[0]>250 or $imgsize[1]>250))
	    if ($width>0 && $height>0 && (($imgsize[0]/$width)>1.5 || ($imgsize[1]/$height)>1.5))
		{	pw_jpgresize ($_SERVER['DOCUMENT_ROOT'].$fnimg,$_SERVER['DOCUMENT_ROOT'].$fnimg,$width,$height);
     		$echo['msg'].="<b>Image $fnimg is Resized to $width x $height</b><br>";
		}
		//echo"<textarea cols=80 rows=12>".htmlentities(print_r($html,TRUE))."</textarea><br>";
	}
return "$width x $height";
}

// *** Image rotate *** PW261105
function pw_rotate ($src,$dst,$degrees=90,$bgcolor=0,$quality=90)
{  //$size = getimagesize($src);$dst_img = imagecreatetruecolor($size[3],$size[4]);
   $src_img = pw_imagecreatefromfile($src);
   if (!$src_img) return "unsupported image type";
   $dst_img = imagerotate($src_img,$degrees,$bgcolor);
   imagejpeg($dst_img,$dst,$quality); imagedestroy($src_img); imagedestroy($dst_img);@chmod($dst,0777);
   return "s:$src d:$dst 0:$degrees bc:$bgcolor q:$quality<br>";
}

function pw_con2jpg ($src,$dst,$width=100,$height=100,$quality=90)
{
  $src_img = pw_imagecreatefromfile($src);
  if (!$src_img) {
    $srcstr=@implode(file($src));
    if ($srcstr) $src_img = @imagecreatefromstring($srcstr);
  }
  if (!$src_img) return false;

  $dst_img = pw_imagecreatetruecolorwhite($width, $height);
  imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $width, $height, imagesx($src_img), imagesy($src_img));
  imagejpeg($dst_img,$dst,$quality);
  imagedestroy($src_img);
  imagedestroy($dst_img);
  @chmod($dst,0777);
  return true;
}

// *** phpwotan.com Wotan<->GDLib V3.1B Images ****PW210104-200805-261105-100206-170306
?>
