<?php
$resource=mysqli_query(_pw_mysqli(), "SELECT * FROM banners where online='1'");unset($banners);
if ($resource) while ($line = mysqli_fetch_array($resource)) $banners[]=$line;
	else { echo mysqli_error(_pw_mysqli());exit; }

$banner=$banners[rand(0,mysqli_num_rows($resource)-1)];
if ($banner['img'])	echo "<center><a href='$banner[url]' target='_blank'>
	<img src='/banners/img/$banner[img]' border=0 alt='$banner[name]'></a></center>";

//PHP psywizard@mail.com Banners echo direct Banners PW030504
?>	
