<?php
//echo"dtree loaded";
// *** Dep & set needed vars
require_once($_SERVER['DOCUMENT_ROOT'].'/wotan/phpmod/var_conv.php');
if ($wotan['dtree']!=1) $table=$wotan['dtree'];
if (!$get['did']) $get['did']=0;
if (!$get['sdid']) $get['sdid']=0;
$echo['catsdtree']='';$sid='';

// *** Querys
$dtmaincats=pw_dbtoarray("select id,category,count(category) as ncategory from $table group by category order by category");
$dtsubcats=pw_dbtoarray("select id,category,subcat,count(subcat) as nsubcat from $table where category='$get[category]' group by subcat order by subcat");
$dtscat2s=pw_dbtoarray("select id,category,subcat,scat2,count(scat2) as nscat2 from $table where category='$get[category]' and subcat='$get[subcat]' group by scat2 order by scat2");
$dtscat3s=pw_dbtoarray("select id,category,subcat,scat2,scat3,count(scat3) as nscat3 from $table where category='$get[category]' and subcat='$get[subcat]' and scat2='$get[scat2]' group by scat3 order by scat3");


//echo $dtmaincats[1]['ncategory'].'<br>';
//echo $dtsubcats[1]['nsubcat'].'<br>';
//echo $dtscat2s[1]['nscat2'].'<br>';
//echo $dtscat3s[1]['nscat3'].'<br>';

// *** Maincats
$echo['link']=$echo['subs'];
$n=0;
foreach($dtmaincats as $key => $val) if ($val['category'])
{ $n=$n+2000;$echo['catsdtree'].="d34_1.add($n,0,'".pw_pregsplit('/[\d\s]+[;:_]/',$val['category'],1,0)."','$echo[link]?did=$n&amp;category=".urlencode($val['category'])."&amp;subcat&amp;scat2&amp;scat3');\r\n"; }

// *** Subcats
$n=0;
foreach($dtsubcats as $key => $val) if ($val['subcat'])
{   if ($dtsubcats[$key]['nsubcat']==1 and $dtscat2s[1]['nscat2']==1)
    { $echo['link']=$echo['item'];$sid='sid='.$dtsubcats[$key]['id']; } else { $echo['link']=$echo['subs'];$sid=''; }
$n=$n+200;$echo['catsdtree'].="d34_1.add($n,$get[did],'".pw_pregsplit('/[\d\s]+[;:_]/',$val['subcat'],1,0)."','$echo[link]?$sid&amp;dsid=$n&amp;category=".urlencode($val['category'])."&amp;subcat=".urlencode($val['subcat'])."&amp;scat2&amp;scat3');\r\n";
}
// *** scat2s
$n=0;
foreach($dtscat2s as $key => $val) if ($val['scat2'])
{   if (($dtsubcats[$key]['nsubcat']==1 and $dtscat2s[1]['nscat2']==1) or $dtscat3s[1]['nscat3']==1 )
    { $echo['link']=$echo['item'];$sid='sid='.$dtscat2s[$key]['id']; } else { $echo['link']=$echo['subs'];$sid=''; }
$n=$n+20;$echo['catsdtree'].="d34_1.add($n,$get[dsid],'".pw_pregsplit('/[\d\s]+[;:_]/',$val['scat2'],1,0)."','$echo[link]?$sid&amp;dssid=$n&amp;category=".urlencode($val['category'])."&amp;subcat=".urlencode($val['subcat'])."&amp;scat2=".urlencode($val['scat2'])."&amp;scat3');\r\n";
}

// *** scat3s
$echo['link']=$echo['item'];
$n=0;
foreach($dtscat3s as $key => $val) if ($val['scat3'])
{ $n=$n+1;$echo['catsdtree'].="d34_1.add($n,$get[dssid],'".pw_pregsplit('/[\d\s]+[;:_]/',$val['scat3'],1,0)."','?category=".urlencode($val['category'])."&amp;subcat=".urlencode($val['subcat'])."&amp;scat2=".urlencode($val['scat2'])."&amp;scat3=".urlencode($val['scat3'])."');\r\n"; }

// foreach($dtsubcat as $key => $val) {$n=$n+10}
/*
Example htmlcode

<script type="text/javascript" src="/share/java/dtree/dtree.js"></script>
<script type="text/javascript">
d34_1 = new dTree('d34_1',"http://www.sinusjevi.nl/");
d34_1.config.useSelection=true;
d34_1.config.useLines=true;
d34_1.config.useIcons=true;
d34_1.config.useStatusText=false;
d34_1.config.closeSameLevel=false;
d34_1.add(0,-1,"Catalog","/catalog/","","")
$echo[catsdtree]
document.write(d34_1);
d34_1.closeAll();
//d34_1.openTo(0,true);
d34_1.oAll();
</script>
*/
// PHP dtree V1.53 pw100106-220206-140306-030406-160606
?>


