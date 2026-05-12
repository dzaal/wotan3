<?php
// *** Category Links en Options
if ($echocategories>0)
{	$catqw=mysqli_query(_pw_mysqli(), "select category from $table where category!='' group by category order by category");$n=0;
	if ($catqw) while($linedata=mysqli_fetch_row($catqw))
		if ($linedata[0]) { if (!$firstcat) $firstcat=$linedata[0];$n++;
		$echo[$n]['cat']=urlencode($linedata[0]);$echo[$n]['cattext']=ucfirst($linedata[0]);
		$echo['catoptions'].="<option>$linedata[0]</option>\r\n";}
}

// *** Sub Category Links
if($echosubcats>0)
{	$echo['subcats']='<a href="?id=&category='.urlencode($get['category']).'">[All]</a> &nbsp;';
	$scatqw=mysqli_query(_pw_mysqli(), "select subcat from $table where category='$get[category]' group by subcat order by subcat");
	if ($scatqw) while ($linedata=mysqli_fetch_row($scatqw))  
		if ($linedata[0]) { if (!$firstscat) $firstscat=$linedata[0];
		$echo['subcats'].='<a href="?id=&subcat='.urlencode($linedata[0]).'">'.ucfirst($linedata[0]).'</a> &nbsp; ';
}	}

// *** Mysql $group['field'] for categorizing/grouping make links and dropdown options
if (is_array($group['field'])) 
{ $grpres=mysqli_query(_pw_mysqli(), $group['query']);$grparray=array();$setgrppost=0;
  if ($grpres) while ($linedata=mysqli_fetch_array($grpres)) { 
   	  reset($group['field']);foreach($group['field'] as $key => $val) {
      if ($linedata[$val]) { $grparray[$val]=$linedata[$val]; eval('if(!$n_'.$val.') $n_'.$val.'=0;');
							 eval('$n_'.$val.'=$n_'.$val.'+1;');eval('$n=$n_'.$val.';'); } else $n=0;
       //if ($linedata[$val]==$group['setpost']) $setgrppost=1;
       //if ($linedata[$val] and !$_POST[$val] and $setgrppost>0) $_POST[$val]=$linedata[$val];
       //echo $val.' | '.$_POST[$val].' = '.$linedata[$val].' <br>';
      if ($_POST[$val] and $_POST[$val]==$linedata[$val] ) { $setgrppost=1; }
      $echo[$n][$val]=$linedata[$val];
	  $echo[$n]['link_'.$val]=urlencode($linedata[$val]);
	  $echo[$n]['ucf_'.$val]=ucfirst($linedata[$val]);
	  if ($linedata[$val]) $echo['opt_'.$val].="<option value='".$linedata[$group['optval'][$key]]."'>$linedata[$val]</option>\r\n";
} if ($setgrppost>0) { $_POST=$_POST+$grparray;$setgrppost=0; }}}

//PHP psywizard@mail.com categorys.php *** PW030305 *** [for new sites USE grouping.php!]
?>