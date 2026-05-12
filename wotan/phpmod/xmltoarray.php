<?php $ses['verdate']['xmltoarray']=901;
class xmltoarray
{
  var $xmldata='';            // * XML data input forexmpl.: file_get_contents("test.xml"); or file_get_contents("http://url.com/file.xml");
  var $utf8enc='';            // * Set 1 for utf8 encoding on when your xml data is not UTF8 and output utf8 is needed
  var $utf8dec='';            // * Set 1 for utf8 decoding on when your xml data is UTF8 and your output you do not want utf8
  var $htmlentenc='';         // * Set 1 for html entity encoding in your output HTML
  var $htmlentdec='';         // * Set 1 for html entity decoding in your output HTML
  var $removecdata='';        // * Set 1 for removing <![CDATA[]]> tags from your output array
  var $chkatt=0;              // * 0 do not check for data in tag. 1 check for 1 att 2 check for 2att
                              //   How higher this number is set how longer it takes to parse xml and prob how more php mem it takes..
  var $maxfieldlength='9999'; // * This is needed to save php memory for parsing big xml files.
  var $xmlarray=array();      // * The xml array
  var $nm=1;                  // * Max Level controller (no setting!)
  // big xml file parsing try to avoid using the convertion options. (convert it if needed at later stadium...)
  // keep chkatt as low as possible best 0 when the xml has no inline data tags

  function run($xmldata='',$removecdata='')
  { if(!$xmldata) $xmldata=$this->xmldata;
    if(!$removecdata) $removecdata=$this->removecdata;
    # ** Option Remove CDATA tags
    if($removecdata) $xml=str_replace('<![CDATA[','',str_replace(']]>','',$xmldata)); // * remove <![CDATA[]]> tags
    else $xml=$xmldata;
    for($an=1;$an<=$this->chkatt;$an++)
    { $xml=preg_replace('/\s([^=\s]+)=(\"|\')([^\"\']+)(\"|\')\s*>/','><\1>\3</\1>',$xml); }
    return $this->xml2array($xml);
  }

  function xml2array($xmlrn,$level='',$n=1,$k=0)
  {  $newlevel=''; // * Set new level
     $xmln=str_replace("\r",'|return|',$xmlrn); // * Remove newlines for regexp
     $xml=str_replace("\n",'|newline|',$xmln);  // * Remove returns for regexp
     preg_match_all('/<([^\/>\?]+)>(.*?)(<\/\\1>)/',$xml,$opres); // * Get <xmltag> alldata in between </xmltag> with regexp
     // return($opres); // Debug regexp result
     # Loop found XML tags detected by regexp
     if(is_array($opres[1]))
     foreach($opres[1] as $key=>$val)
     { # * Set levels
       if(!$level) $newlevel='[\''.$val.'\']';
       if($level and $n==2)
       { $level=str_replace('[0]','['.($k+1).']',$level);
         $level=str_replace('['.($k-1).']','['.($k).']',$level);
         $newlevel=$level.'[\''.$val.'\']';$n++;
       }
       if($level and $n>2) { $newlevel=$level.'[\''.$val.'\']';$n++; }
       if($level and $n==1) { $newlevel=$level.'['.($n-1).']'.'[\''.$val.'\']'; $n++; }
       // return "\r\n".$n.' '.$k."\r\n".$newlevel.' = '.$opres[2][$key]; // Debug levels and data

       # * Convert data
       if($datac=str_replace('|newline|',"\n",$opres[2][$key]))
       $datav=$datac; else $datav=$opres[2][$key];                              // * |newline|
       if($datac=str_replace('|return|',"\r",$datav))
       $datav=$datac; else $datav=$opres[2][$key];                              // * |return|
       if($this->utf8dec and !$this->utf8enc) $datav=utf8_decode($datav);       // * utf8dec
       if($this->htmlentenc) $datav=htmlentities($datav); else                  // * htmlentenc
       if($this->htmlentdec) $datav=html_entity_decode($datav);                 // * htmlentdec
       if($this->utf8enc and !$this->utf8dec) $datav=utf8_encode($datav);       // * utf8enc

       # Set value in newlevel of the array
       if($newlevel and strlen($val)<$this->maxfieldlength)
       { eval('if(!is_array($this->xmlarray'.$level.')) $this->xmlarray'.$level.' = array();');
         eval('$this->xmlarray'.$newlevel.'=$datav;');
       }
       # run function again to check/get next/more deeper levels
       if($opres[2][$key]) $this->xmlarray=$this->xml2array($opres[2][$key],$newlevel,$n,$k);
       if($n==2) $k++; $n=$n-1; // * Child and levels numbering
     }
  return $this->xmlarray;
  }
  var $ver='phpwotan.com xmltoarray v1.2B PW5-140109-050209-170209';
}
?>