<html><head>
<?php
// *** Set PHP workdir
chdir ("$_SERVER[DOCUMENT_ROOT]/".dirname($htmlfile));

// *** DEFAUL $htmlfile
if (!$htmlfile) { echo "Don't know which file to open";exit; }
$content=(implode('',file("$_SERVER[DOCUMENT_ROOT]/$htmlfile")));
$title=substr($content,strpos($content,'<title>'),(strpos($content,'</title>')-strpos($content,'<title>')));
$content=str_replace($title,'',$content);
$content=str_replace(' class="tablelabel"','><b><td ',$content);
$content=str_replace('<a','<b><a',$content);$content=str_replace('</a>','</a></b>',$content);
$content=str_replace('<td>','',$content);
$content=str_replace('</td>','</b>',$content);
$content=str_replace('<tr>','<br><br>',$content);
$content=strip_tags($content,'<li><br></li><b></b><u></u>');
$content=substr($content,strpos($content,'OVERVIEW')+8,99999);
$content=substr($content,strpos($content,'<br><br>')+8,99999);

echo $title.'</title><style type="text/css">
		ul,li {list-style-image: url(http://contestyachts.projekt.nl/images/blackdash.gif);	margin-left: 12px; margin-top: 1px; }
		</style></head><body onload="javascript:window.print();"><font face="arial" size="2"><ul>'.$content.'
		</ul></font><font size="1"><center>ContestYachts Printed on '.date('d-m-Y',time()).'</center></font></body></html>';

/* *** html Print Button code
	<a class="tablelabel" href="/include/printpage.php?htmlfile=$GLOBALS[htmlfile]" target="_blank">
	<img src="/images/popup_print.gif" align="bottom" border="0" width="15" height="15"> 
	PRINT</a>
*/

//PHP psywizard@mail.com **** Print Page filter images PW111204
?>