<?php
//Get Random Image from mysql $query **** PW150504
function pw_randimg($query,$imgpath) { global $DOCUMENT_ROOT;
	$result=pw_dbtoarray("$query");echo mysqli_error(_pw_mysqli());
	$randres=$result[mt_rand(1,count($result)-2)];
	if ($randres['img_ov'] and file_exists($DOCUMENT_ROOT.$imgpath.$randres['img_ov'])) 
		$result['randimg']=$imgpath.$randres['img_ov'];
	else $result['randimg']="/share/images/blank.gif";
return $result;}

//PHP psywizard@mail.com **** PW220304
?>
