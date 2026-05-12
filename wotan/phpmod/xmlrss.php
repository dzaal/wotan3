<?php $ses['verdate']['xmlrss']=801;
// RSS2.0 specification: http://www.rssboard.org/rss-specification
// RSS2.0 Validator http://rss.scripting.com/

class xmlrss
{ var $template='/share/xmlrss/xmltempl.xml'; // * Location XML Template
  var $utf8=1; // * 1 = Use UTF8 encoding , 0 = keep as is
  var $element='result'; // * XML Root element format2 only
  var $setformat=2; // * >2=xmlV2 , 2=xml pwv1 Output (Flashxmlv1) , 1=Rss Output
  var $rss=0; // * if (rss>0) format = rss (rss is old varname....)
  var $rsslink='/moreinfo/'; // * Rss Auto Link add

  // *** Runall // * 050707
  function runall($array,$element='',$setformat=2)
  { global $echo;
    if(!$element) $element=$this->element;
    if($this->setformat) $setformat=$this->setformat;
	if($this->rss>0) $setformat=$this->rss;
    if($setformat==1) $echo['rssdata']=$this->array2rss ($array);
    if($setformat==2) $echo['xmldata']=$this->array2xml ($element,$array);
    if($setformat>2) $echo['xmldata']=$this->array2xml2 ($array,$setformat);
	return $this->loadxmltempl();
  }

  // *** (Multidem) Array to XML // * 050507 format2 pwXMLV1
  function array2xml ($element='',$array)
  { if ($element) $return="<$element>\r\n";else $return='';
    if (!is_array($array) or count($array)==0) return '<error>No Input Data</error>';
    foreach($array as $key => $val)
    { if(is_array($val))
        { if (is_int($key)) $key='sub'.$key; $return.=$this->array2xml($key,$val);$key=0; }
        if(!is_int($key))
        { $val=htmlspecialchars(stripslashes($val));
        if($this->utf8==1) { $val=utf8_encode($val);$key=utf8_encode($key); }
        $return.="  <$key>".$val."</$key>\r\n";
        }
    }
  if ($element) $return=$return."</$element>\r\n\r\n";
  return $return;
  }

  // *** (Multidem) Array to XML // * 050707-211207 format>3 pwXMLV2
  function array2xml2 ($array,$setformat=4,$parent='',$echototal=0)
  { $closetags='';$spar=0;
    if(!is_array($array) or count($array)==0) return '<error>No Input Data</error>';
    if(!$parent)
    { $parent=$topelm=end(array_keys($array));  // Get (last) key from array for toplevel element
      $array=$array[$topelm];$return="<$topelm>\r\n";
      if(is_int($array['setformat'])) { $setformat=$array['setformat']; unset($array['setformat']); }
      //$return.=" <info>\r\n";
      //$return.="  <pwxmlrss>V3.04 211207</pwxmlrss>\r\n";
      //$return.="  <setformat>$setformat</setformat>\r\n";
      //$return.=" </info>\r\n";
    }
    $totalelm=count($array);
    if($echototal!=0) $return.="  <totalelm>$totalelm</totalelm>\r\n";
    foreach($array as $key => $val) // Get elements from toplevel
    { if(is_array($val) and !is_int($key)) // ** ELEMENTS
      { $return.=" <$key>\r\n";$closetags.=" </$key>\r\n";
        $return.=$this->array2xml2($val,$setformat,$key);
        $return.=$closetags;$closetags='';
      }
      if(is_array($val) and is_int($key)) // * Looped ELEMENT from array 1 2 3 4 .......
      { $partag=substr($parent,0,strlen($parent)-1);
        if($setformat==3)
        { $partag="{$partag}{$key}"; } // * Set N in Subelement (V1)
        if(($totalelm<2 and $setformat==5)) $partag=''; // format 5 when 1 element don't set subelement
        if($partag) { $return.="  <$partag>\r\n"; $closetags.="  </$partag>\r\n"; }
        $return.=$this->array2xml2($val,$setformat,$key);
        $return.="   <loopnr>$key</loopnr>\r\n";
        $return.=$closetags;$closetags='';
      }
      if(!is_array($val) and !is_int($key)) // * Last level get values and encode them for xml
      { $val=htmlspecialchars(stripslashes($val));
        if($this->utf8==1) $val=utf8_encode($val);
        if($key[0]!='0' and intval($key[0])==0 and $key!='0' and $key!='loopnr')
        $return.="   <$key>$val</$key>\r\n";
      }
      if(!is_array($val) and is_int($key)) // * Last is a number use parentnumber as element name
      { $val=htmlspecialchars(stripslashes($val));
        if($this->utf8==1) $val=utf8_encode($val);
        if($key!='0' and intval($parent)==0) $return.="   <{$parent}n{$key}>$val</{$parent}n{$key}>\r\n";
      }
    }
    if($topelm) $return.="</$topelm>\r\n"; // Close top level element tag
    return $return;
  }

  // *** (Multidem) Array to RSS2.0 title link description optional langauge // * 050507 Format1 pwRSSV1
  // *** Pubdate format mysql DATE_FORMAT(timestamp,'%a, %d %b %Y %H:%I:%S')
  function array2rss ($array)
  { $return='';
    if (!is_array($array) or count($array)==0) return '<error>No Input Data</error>';
    foreach($array as $key => $val)
    { if(is_array($val)) $rss=$val; else { $rss=$array; $break=1; }
      // ** Autolink
      if(!$rss['title']) $rss['title']=htmlentities(stripslashes($rss['name']));
      if(!$rss['link'])
      $rss['link']=htmlentities(stripslashes('http://'.$_SERVER['HTTP_HOST'].'/'.$GLOBALS['setflname'][1].$this->rsslink.'?id='.$rss['id']));
      // ** Required
      $return.="<item>\r\n";
      $return.="<title>".htmlentities(stripslashes($rss['title']))."</title>\r\n";
      $return.="<link>".htmlentities(stripslashes($rss['link']))."</link>\r\n";
      $return.="<description>".htmlentities(stripslashes($rss['description']))."</description>\r\n";
      // ** Optional
      if($rss['category']) $return.="<category>".htmlentities(stripslashes($rss['category']))."</category>\r\n";
      if($rss['url']) $return.="<comments>".htmlentities(stripslashes($rss['url']))."</comments>\r\n";
      if($rss['date'])  $return.="<pubDate>".$rss['date']."</pubDate>"; else
      if($rss['pubdate'])  $return.="<pubDate>".$rss['pubdate']."</pubDate>"; else
      if($rss['timestampu']) $return.="<pubDate>".date('D, d M Y G:i:s e',$rss['timestampu'])."</pubDate>\r\n";
      if($rss['author']) $return.="<author>".htmlentities(stripslashes($rss['author']))."</author>\r\n";
      if($rss['enclosure']) $return.="<enclosure>".htmlentities(stripslashes($rss['enclosure']))."</enclosure>\r\n";
      if($rss['guid']) $return.="<guid>".htmlentities(stripslashes($rss['guid']))."</guid>\r\n";
      if($rss['source']) $return.="<source>".htmlentities(stripslashes($rss['source']))."</source>\r\n";
      $return.="</item>\r\n\r\n";
      if($break==1) break;
    }
  return $return;
  }

  // *** Load and Parse Template
  function loadxmltempl ($template='')
  { if ($template) $this->$template=$template;
    header('Content-Type: application/xml;');
    return wotan ('pw_htmlfile4/pw_htmlfile','$obj[xmltempl]',"evalfile('$this->template',1)");
  }
}

//PHP psywizard@mail.com xml & rss V3.06 PW160304-170906-050507-050707-211207-280108
?>