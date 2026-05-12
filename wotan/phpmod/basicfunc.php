<?php $ses['verdate']['basicfunc']=70505;
// =========== Here can be places Basic Functions to make programming more easy =============
// * Put only functions here that will be used often! because the idea
// * of this file is that it will loaded by default so that these functions are always available

// *** Grep piece of text between start & end **** PW031102-170705
function pw_grep($start,$data,$end,$lenstart='a',$lenend=0)
{ $strstr=stristr($data,$start); // get data from start
  if ($lenstart=='a') $lenstart=strlen($start); // Auto len Start
  if ($start=='') { $lenstart=0;$strstr=$data; } // No start (inverse strstr)
  $substr=substr($strstr,$lenstart,999999);//Substr from start
  return substr($substr,0,strpos($substr,strtolower($end))+$lenend); // Return substr Result
}

function pw_pregmatch($regexp,$data)
{ preg_match($regexp,$data,$pres);return $pres[0]; }

// *** dz_translate Updated Erik added autoadd trim htmlentities..... PW100508
function dz_translate($input,$language='',$table='translations',$nfield='name_org')
{ global $result,$get;
  if(!$input) return; else trim($input);
  if (!$language) $language=$get['lan']; // * Set Def lan.
  if(!isset($result['translate'])) $result['translate']=pw_dbtoarray("select * from $table",'*',$nfield);
  /*
  if(!isset($result['translate'][$input][$nfield]))
  { mysqli_query(_pw_mysqli(), "insert into $table set $nfield='$input',online='1'"); } // * Not Found at all: Add tot database $table
  */
  $return=$result['translate'][$input][$language] ?? null; // * LookupTranslation
  //if($return) return htmlentities($return); else return htmlentities($input); // * Found translation else Not Found Translation
  if($return) return $return; else return $input; // * Found translation else Not Found Translation
}
 
function friendlyURL($phrase)
{

$phrase=str_replace(' &amp; ','_amp_',$phrase ?? '');
 

return Doctrine_Inflector::urlize($phrase);
}

 
function trim_text($input, $length, $ellipses = true, $strip_html = true) {
	//strip tags, if desired
	if ($strip_html) $input = strip_tags($input ?? '');

	//no need to trim, already shorter than trim length
	if (strlen($input) <= $length) 	return $input;

	//find last space within length
	$last_space = strrpos(substr($input, 0, $length), ' ');
	$trimmed_text = substr($input, 0, $last_space);

	//add ellipses (...)
	if ($ellipses) 	$trimmed_text .= '...';

	return $trimmed_text;
}

 

/**
 * trims text to a space then adds ellipses if desired
 * @param string $input text to trim
 * @param int $length in characters to trim to
 * @param bool $ellipses if ellipses (...) are to be added
 * @param bool $strip_html if html tags are to be stripped
 * @return string
 */

     // * ICO's or Images
  function pw_icoimg ($fn)
  { $ext=strtolower(substr($fn,strrpos($fn,'.')+1,4));
    if ($ext=='jpg' or $ext=='gif' or $ext=='png')
    return '/downloads/files/'.$fn ; else
    if ($ext) return '/downloads/ico/'.$ext.'.gif';
    return '/images/blank.gif';
  }
function ip2country($ip, $field="country_code") {
    try {
        $result['ip2country']=pw_dbtoarray("SELECT c.*,
( select f.name
from fips_regions f
where c.region_code = f.code
and c.country_code = f.country_code
) as region
FROM ip_group_city c
WHERE c.ip_start <= INET_ATON('$ip')
ORDER BY c.ip_start DESC
LIMIT 1");
        return $result['ip2country'][1][$field];
    } catch (\Exception $e) {
        return null;
    }
}

function dz_tweet($username) {

				//		$username = "YOUR.USERNAME";

						// Prefix - some text you want displayed before your latest tweet.
						// (HTML is OK, but be sure to escape quotes with backslashes: for example href=\"link.html\")
						$prefix = "";

						// Suffix - some text you want display after your latest tweet. (Same rules as the prefix.)
						$suffix = " ";

						$feed = "http://search.twitter.com/search.atom?q=from:" . $username . "&rpp=1";

						function parse_feed($feed) {
						    $stepOne = explode("<content type=\"html\">", $feed);
						    $stepTwo = explode("</content>", $stepOne[1]);
						    $tweet = $stepTwo[0];
						    $tweet = str_replace("&amp;lt;", "<", $tweet);
						    $tweet = str_replace("&amp;gt;", ">", $tweet);
							$tweet = str_replace("&amp;quot;", "\"", $tweet);
							$tweet = str_replace("&amp;", "&", $tweet);
							$tweet = str_replace("&lt;a href", "<a href", $tweet);
							$tweet = str_replace("\"&gt;", "\">", $tweet);
							$tweet = str_replace("&lt;/a&gt;", "</a>", $tweet);
						    return $tweet;
						}

						$twitterFeed = file_get_contents($feed);
						return stripslashes($prefix) . parse_feed($twitterFeed) . stripslashes($suffix);

}

function dz_select($array,$selection, $translation=1)
  {
  $out= '<option value="">'.dz_translate('select').'</option>';
  foreach ($array as $i => $value) 
    {
    if (is_array($value)) $val=$value[key($value)]; else  $val = $value;
    if ($selection==$val) $selected='selected="selected"'; else $selected="";
    if ($translation==1) $out .='<option value="'.htmlspecialchars($val, ENT_QUOTES).'" '.$selected.' >'.dz_translate($val).'</option>'; else $out .='<option '.$selected.' >'.htmlspecialchars($val, ENT_QUOTES).'</option>';
    }
  return $out;
  }
function dz_multiselect($array,$selection, $translation=1)
  {
  $out= '<option value="">'.dz_translate('select').'</option>';
  foreach ($array as $i => $value) 
    {
    if (is_array($value)) $val=$value[key($value)]; else  $val = $value;
    if (in_array($val,$selection)) $selected='selected="selected"'; else $selected="";
    if ($translation==1) $out .='<option value="'.htmlspecialchars($val, ENT_QUOTES).'" '.$selected.' >'.dz_translate($val).'</option>'; else $out .='<option '.$selected.' >'.htmlspecialchars($val, ENT_QUOTES).'</option>';
    }
  return $out;
  }


function createlist($array, $hrefvalue="id", $displayName="", $currentvalue="", $singleitems=0 )
  /* 
  maakt van een php array object een unordered  list and gves the currect class to the active list
  */
  {
    if (is_array($array) && count($array)>1 || $singleitems==1)
    {
    $string="<ul>
    ";
     foreach ($array as $i => $value) 
      {
        //if (is_array($value)) $val=$value[key($value)]; else  $val = $value;
        
       if (strlen(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0)) ==0) $visible='style="display:none"'; else  $visible="";   // onzichtbare opening page
       
       
        if ($value[$hrefvalue] == $currentvalue) 
              $string .='<li class="current" '.$visible.'>'; 
              else 
              $string .='<li '.$visible.'>';
       // $string .='<a href="?'.$hrefvalue.'='.$value[$hrefvalue].'">'.trim_text(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0),26).'</a></li>
        $string .='<a href="?'.$hrefvalue.'='.$value[$hrefvalue].'">'.pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0).'</a></li>
        ';
      }
      $string .='</ul>';
      return $string;
    }
  }
  
  
  function createSeolist($array, $prefix="", $displayName="", $currentvalue="", $singleitems=0 )
  /*
  SEO variant of createlist() that links to pretty URLs under a given prefix.
  */
  {
    if ((is_array($array) && count($array)>1) || $singleitems==1)
    {
      $string="<ul>
    ";
      foreach ($array as $i => $value)
      {
        $label = pw_pregsplit('/[0-9]+[:_]/', $value[$displayName] ?? '',1,0);
        if (strlen($label) == 0) $visible='style="display:none"'; else $visible="";
        if (($value['id'] ?? '') == $currentvalue)
              $string .='<li class="current" '.$visible.'>';
              else
              $string .='<li '.$visible.'>';
        $string .='<a href="'.$prefix.friendlyURL($label).'.html">'.$label.'</a></li>
        ';
      }
      $string .='</ul>';
      return $string;
    }
  }

function newslist($array, $hrefvalue="id", $displayName="", $currentvalue="", $singleitems=0 )
  /* 
  maakt van een php array object een unordered  list and gves the currect class to the active list
  */
  {
    if (is_array($array) && count($array)>1 || $singleitems==1)
    {
    $string="<ul>
    ";
     foreach ($array as $i => $value) 
      {
        //if (is_array($value)) $val=$value[key($value)]; else  $val = $value;
        
       if (strlen(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0)) ==0) $visible='style="display:none"'; else  $visible="";   // onzichtbare opening page
       
       
        if (($value[$hrefvalue] ?? '') == $currentvalue)
              $string .='<li class="current" '.$visible.'>';
              else
              $string .='<li '.$visible.'>';
       // $string .='<a href="?'.$hrefvalue.'='.$value[$hrefvalue].'">'.trim_text(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0),26).'</a></li>
        $string .='<a href="/news/'.($value[$hrefvalue] ?? '').'/'.friendlyURL(pw_pregsplit('/[0-9]+[:_]/',    $value[$displayName],1,0)).'.html">'.pw_pregsplit('/[0-9]+[:_]/',     $value[$displayName],1,0).'</a></li>
        ';
      }
      $string .='</ul>';
      return $string;
    }
  }
  
 
  
 
    

// Find thumbnail from an embed code
function get_video_thumbnail($videourl) {
 
		$new_thumbnail = null;
 
		$markup = $videourl;
 
 
		
		// Checks for a standard YouTube embed
		preg_match('#<object[^>]+>.+?//www.youtube.com/[ve]/([A-Za-z0-9\-_]+).+?</object>#s', $markup, $matches);
		
		// More comprehensive search for YouTube embed, redundant but necessary until more testing is completed
		if(!isset($matches[1])) {
			preg_match('#//www.youtube.com/[ve]/([A-Za-z0-9\-_]+)#s', $markup, $matches);
		}

		// Checks for YouTube iframe
		if(!isset($matches[1])) {
			preg_match('#//www.youtube.com/embed/([A-Za-z0-9\-_]+)#s', $markup, $matches);
		}
	
		// Checks for any YouTube URL
		if(!isset($matches[1])) {
			preg_match('#//w?w?w?.?youtube.com/watch\?v=([A-Za-z0-9\-_]+)#s', $markup, $matches);
		}
		
		// Checks for YouTube Lyte
		if(!isset($matches[1]) && function_exists('lyte_parse')) {
			preg_match('#<div class="lyte" id="([A-Za-z0-9\-_]+)"#s', $markup, $matches);
		}
		
		// If we've found a YouTube video ID, create the thumbnail URL
		if(isset($matches[1])) {
			$youtube_thumbnail = 'http://img.youtube.com/vi/' . $matches[1] . '/0.jpg';
			
			// Check to make sure it's an actual thumbnail
			if (!function_exists('curl_init')) {
				$new_thumbnail = $youtube_thumbnail;
			} else {
				$ch = curl_init($youtube_thumbnail);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_exec($ch);
				$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				// $retcode > 400 -> not found, $retcode = 200, found.
				curl_close($ch);
				if($retcode==200) {
					$new_thumbnail = $youtube_thumbnail;
				}
			}
		}
		
		// Vimeo
		if($new_thumbnail==null) {
		
			// Standard embed code
			preg_match('#<object[^>]+>.+?http://vimeo.com/moogaloop.swf\?clip_id=([A-Za-z0-9\-_]+)&.+?</object>#s', $markup, $matches);
			
			// Find Vimeo embedded with iframe code
			if(!isset($matches[1])) {
				preg_match('#http://player.vimeo.com/video/([0-9]+)#s', $markup, $matches);
			}
			
			// If we still haven't found anything, check for Vimeo embedded with JR_embed
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo id=([A-Za-z0-9\-_]+)]#s', $markup, $matches);
		    }
	
			// If we still haven't found anything, check for Vimeo URL
			if(!isset($matches[1])) {
		    	preg_match('#http://w?w?w?.?vimeo.com/([A-Za-z0-9\-_]+)#s', $markup, $matches);
		    }
	
			// If we still haven't found anything, check for Vimeo shortcode
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo clip_id="([A-Za-z0-9\-_]+)"[^>]*]#s', $markup, $matches);
		    }
			if(!isset($matches[1])) {
		    	preg_match('#\[vimeo video_id="([A-Za-z0-9\-_]+)"[^>]*]#s', $markup, $matches);
		    }
		
			// Now if we've found a Vimeo ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$vimeo_thumbnail = getVimeoInfo($matches[1], $info = 'thumbnail_large');
				if(isset($vimeo_thumbnail)) {
					$new_thumbnail = $vimeo_thumbnail;
				}
			}
		}
		
		// Blip.tv
		if($new_thumbnail==null) {
		
			// Blip.tv file URL
			preg_match('#http://blip.tv/play/([A-Za-z0-9]+)#s', $markup, $matches);

			// Now if we've found a Blip.tv file URL, let's set the thumbnail URL
			if(isset($matches[1])) {
				$blip_thumbnail = getBliptvInfo($matches[1]);
				$new_thumbnail = $blip_thumbnail;
			}
		}
		
		// Justin.tv
		if($new_thumbnail==null) {
		
			// Justin.tv archive ID
			preg_match('#archive_id=([0-9]+)#s', $markup, $matches);

			// Now if we've found a Justin.tv archive ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$justin_thumbnail = getJustintvInfo($matches[1]);
				$new_thumbnail = $justin_thumbnail;
			}
		}
		
		// Dailymotion
		if($new_thumbnail==null) {
		
			// Dailymotion flash
			preg_match('#<object[^>]+>.+?http://www.dailymotion.com/swf/video/([A-Za-z0-9]+).+?</object>#s', $markup, $matches);
			
			// Dailymotion url
			if(!isset($matches[1])) {
				preg_match('#http://www.dailymotion.com/video/([A-Za-z0-9]+)#s', $markup, $matches);
			}
			
			// Dailymotion iframe
			if(!isset($matches[1])) {
				preg_match('#http://www.dailymotion.com/embed/video/([A-Za-z0-9]+)#s', $markup, $matches);
			}

			// Now if we've found a Dailymotion video ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$dailymotion_thumbnail = getDailyMotionThumbnail($matches[1]);
				$new_thumbnail = strtok($dailymotion_thumbnail, '?');
			}
		}
		
		// Metacafe
		if($new_thumbnail==null) {
		
			// Find ID from Metacafe embed url
			preg_match('#http://www.metacafe.com/fplayer/([A-Za-z0-9\-_]+)/#s', $markup, $matches);

			// Now if we've found a Metacafe video ID, let's set the thumbnail URL
			if(isset($matches[1])) {
				$metacafe_thumbnail = getMetacafeThumbnail($matches[1]);
				$new_thumbnail = strtok($metacafe_thumbnail, '?');
			}
		}
		
		// Return the new thumbnail variable and update meta if one is found
		if($new_thumbnail!=null) {
		
 
		}
		return $new_thumbnail;

 
};

// Echo thumbnail
function video_thumbnail($post_id=null) {
	if( ( $video_thumbnail = get_video_thumbnail($post_id) ) == null ) { echo plugins_url() . "/video-thumbnails/default.jpg"; }
	else { echo $video_thumbnail; }
};
// Get Vimeo Thumbnail
function getVimeoInfo($id, $info = 'thumbnail_large') {
    if (!function_exists('curl_init')) {
    	return null;
    } else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://vimeo.com/api/v2/video/$id.php");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = unserialize(curl_exec($ch));
		$output = $output[0][$info];
		curl_close($ch);
		return $output;
    }
};

// Blip.tv Functions
function getBliptvInfo($id) {
	$xml = simplexml_load_file("http://blip.tv/players/episode/$id?skin=rss");
    $result = $xml->xpath("/rss/channel/item/media:thumbnail/@url");
    $thumbnail = (string) $result[0]['url'];
    return $thumbnail;
}

// Justin.tv Functions
function getJustintvInfo($id) {
	$xml = simplexml_load_file("http://api.justin.tv/api/clip/show/$id.xml");
	return (string) $xml->clip->image_url_large;
}

// Get DailyMotion Thumbnail
function getDailyMotionThumbnail($id) {
    if (!function_exists('curl_init')) {
    	return null;
    } else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.dailymotion.com/video/$id?fields=thumbnail_url");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		curl_close($ch);
		$output = json_decode($output);
		$output = $output->thumbnail_url;
		return $output;
    }
};

// Metacafe
function getMetacafeThumbnail($id) {
	$xml = simplexml_load_file("http://www.metacafe.com/api/item/$id/");
    $result = $xml->xpath("/rss/channel/item/media:thumbnail/@url");
    $thumbnail = (string) $result[0]['url'];
    return $thumbnail;
};
    
       function submenulist($array, $hrefvalue="id", $displayName="", $currentvalue="", $prefix="", $singleitems=0 )
  /* 
  maakt van een php array object een unordered  list and gves the currect class to the active list
  $prefix =naam eerdere folder, normaal is dat de huidige categorienaam
  
         
  $echo['submenu']=submenulist( $result['submenu'], $hrefvalue="subcat", "subcat", $_GET['msubcat'],$get['mcategory'],0);      

  */
  {
 
    if (is_array($array) && count($array)>1 || $singleitems==1)
    {
    
    $string="<ul class=\"sub\">
    ";
     foreach ($array as $i => $value) 
      {
        //if (is_array($value)) $val=$value[key($value)]; else  $val = $value;
        
       if (strlen(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0)) ==0) $visible='style="display:none"'; else  $visible="";   // onzichtbare opening page
       
        if ($prefix and !strstr($prefix,'/')) $prefix='/'.friendlyURL(pw_pregsplit('/[0-9]+[:_]/',$prefix,1,0)).'/';
        if ($value[$hrefvalue] == $currentvalue) 
              $string .='<li class="current" '.$visible.'>'; 
              else 
              $string .='<li '.$visible.'>';
       // $string .='<a href="?'.$hrefvalue.'='.$value[$hrefvalue].'">'.trim_text(pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0),26).'</a></li>
      
        $string .='<a href="'.$prefix . friendlyURL(pw_pregsplit('/[0-9]+[:_]/',$value[$hrefvalue],1,0)).'.html"><span>'.pw_pregsplit('/[0-9]+[:_]/', $value[$displayName],1,0).'</span></a></li>
        ';
      }
      $string .='</ul>';
      return $string;
    }
 
  }  
  
/*
 *  $Id: Inflector.php 3189 2007-11-18 20:37:44Z meus $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine inflector has static methods for inflecting text
 *
 * The methods in these classes are from several different sources collected
 * across several different php projects and several different authors. The
 * original author names and emails are not known
 *
 * @package     Doctrine
 * @subpackage  Inflector
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 3189 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Inflector
{
    /**
     * Convert word in to the format for a Doctrine table name. Converts 'ModelName' to 'model_name'
     *
     * @param  string $word  Word to tableize
     * @return string $word  Tableized word
     */
    public static function tableize($word)
    {
       //return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $word));
       return $word;
       
    }

    /**
     * Convert a word in to the format for a Doctrine class name. Converts 'table_name' to 'TableName'
     *
     * @param string  $word  Word to classify
     * @return string $word  Classified word
     */
    public static function classify($word)
    {
        static $cache = array();

        if (!isset($cache[$word])) {
            $word = preg_replace('/[$]/', '', $word);
            $classify = preg_replace_callback('~(_?)([-_])([\w])~', array("Doctrine_Inflector", "classifyCallback"), ucfirst(strtolower($word)));
            $cache[$word] = $classify;
        }
        return $cache[$word];
    }

    /**
     * Callback function to classify a classname properly.
     *
     * @param  array  $matches  An array of matches from a pcre_replace call
     * @return string $string   A string with matches 1 and mathces 3 in upper case.
     */
    public static function classifyCallback($matches)
    {
        return $matches[1] . strtoupper($matches[3]);
    }

    /**
     * Check if a string has utf7 characters in it
     *
     * By bmorel at ssi dot fr
     *
     * @param  string $string
     * @return boolean $bool
     */
    public static function seemsUtf8($string)
    {
      for ($i = 0; $i < strlen($string); $i++) {
        if (ord($string[$i]) < 0x80) continue; # 0bbbbbbb
        elseif ((ord($string[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif ((ord($string[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif ((ord($string[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif ((ord($string[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif ((ord($string[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
        else return false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
          if ((++$i == strlen($string)) || ((ord($string[$i]) & 0xC0) != 0x80))
          return false;
        }
      }
      return true;
    }

    /**
     * Remove any illegal characters, accents, etc.
     *
     * @param  string $string  String to unaccent
     * @return string $string  Unaccented string
     */
    public static function unaccent($string)
    {
        if ( ! preg_match('/[\x80-\xff]/', $string) ) {
          return $string;
      }

        if (self::seemsUtf8($string)) {
          $chars = array(
          // Decompositions for Latin-1 Supplement       
          chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
          chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
          chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
          chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
          chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
          chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
          chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
          chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
          chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
          chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
          chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
          chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
          chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
          chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
          chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
          chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
          chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
          chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
          chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
          chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
          chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
          chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
          chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
          chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
          chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
          chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
          chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
          chr(195).chr(191) => 'y',
          // Decompositions for Latin Extended-A
          chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
          chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
          chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
          chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
          chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
          chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
          chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
          chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
          chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
          chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
          chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
          chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
          chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
          chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
          chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
          chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
          chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
          chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
          chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
          chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
          chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
          chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
          chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
          chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
          chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
          chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
          chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
          chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
          chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
          chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
          chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
          chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
          chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
          chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
          chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
          chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
          chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
          chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
          chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
          chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
          chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
          chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
          chr(197).chr(148) => 'R', chr(197).chr(149) => 'r',
          chr(197).chr(150) => 'R', chr(197).chr(151) => 'r',
          chr(197).chr(152) => 'R', chr(197).chr(153) => 'r',
          chr(197).chr(154) => 'S', chr(197).chr(155) => 's',
          chr(197).chr(156) => 'S', chr(197).chr(157) => 's',
          chr(197).chr(158) => 'S', chr(197).chr(159) => 's',
          chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
          chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
          chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
          chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
          chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
          chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
          chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
          chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
          chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
          chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
          chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
          chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
          chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
          chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
          chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
          chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
          // Euro Sign
          chr(226).chr(130).chr(172) => 'E',
          // GBP (Pound) Sign
          chr(194).chr(163) => '',
          'Ä' => 'Ae', 'ä' => 'ae', 'Ü' => 'Ue', 'ü' => 'ue',
          'Ö' => 'Oe', 'ö' => 'oe', 'ß' => 'ss',
          // Norwegian characters
          'Å'=>'Aa','Æ'=>'Ae','Ø'=>'O','æ'=>'a','ø'=>'o','å'=>'aa'
          );

          $string = strtr($string, $chars);
        } else {
          // Assume ISO-8859-1 if not UTF-8
          $chars['in'] =  chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
            .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
            .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
            .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
            .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
            .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
            .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
            .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
            .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
            .chr(252).chr(253).chr(255);

          $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

          $string = strtr($string, $chars['in'], $chars['out']);
          $doubleChars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
          $doubleChars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
          $string = str_replace($doubleChars['in'], $doubleChars['out'], $string);
        }

        return $string;
    }

    /**
     * Convert any passed string to a url friendly string. Converts 'My first blog post' to 'my-first-blog-post'
     *
     * @param  string $text  Text to urlize
     * @return string $text  Urlized text
     */
    public static function urlize($text)
    {
        // Remove all non url friendly characters with the unaccent function
        $text = self::unaccent($text);
        
        if (function_exists('mb_strtolower'))
        {
            $text = mb_strtolower($text);
        } else {
            $text = strtolower($text);
        }   
      
        // Remove all none word characters
    
        $text = preg_replace('/\W/', '-', $text);

           
        // More stripping. Replace spaces with dashes
        $text = strtolower(preg_replace('/[^A-Z^a-z^0-9^\/-]+/', '-',
                           preg_replace('/([a-z\d])([A-Z])/', '\1_\2',
                           preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2',
                           preg_replace('/::/', '/', $text)))));
         
       // return trim($text, '-');
        return $text;
    }
 } 
function getcountrycode($countryname)
{ // BEGIN function getcountrycode

  $tst=pw_dbtoarray("SELECT UPPER(countrycode) as countrycode FROM countrys WHERE countryname_en ='$countryname'");
  if  (!empty($tst[1]['countrycode']))
      return ($tst[1]['countrycode']);
   else  return false;
} // END function getcountrycode

//   PW/ DZ extra functions 2011  2012.1


?>