<?php
// *** MysqltoVars3 width Paging System for large databases | mysqli/PHP8 upgrade 2026
#[\AllowDynamicProperties]
class mysqltovars {
    var $table='';
    var $query='';
    var $evalqw='';
    var $debugqw='';
    var $debug='';
    var $unsetid='';
    var $mitems='';
    var $itmppage=1;
    var $item='';
    var $chpage=0;
    var $chitem=0;
    var $page='';
    var $sort='';
    var $id='';
    var $lnfnc='';
    var $delid='';
    var $cpath='';
    var $system='';
    var $itmsonpage=0;
    var $pageinfo=array();

function runall ($input='')
{ global $ses,$echo;
  eval (arraytoclass('$input')); if(!$this->query) $mysqlqw=$this->query=$input;
  if ($this->unsetid>0) $this->id=0;
  $this->cpath=$cpath=str_replace('/','-',$GLOBALS['REDIRECT_URL']).'a';
  if ($this->evalqw>0) eval ('$mysqlqw="'.$this->query.'";');
  else $mysqlqw=&$this->query;
  if ($this->debugqw>0) { echo "Debug Query: $mysqlqw";exit;}
  if (!isset($GLOBALS['_mysqli'])) return;
  $conn = $GLOBALS['_mysqli'];

  if ($mysqlqw) $recourse=mysqli_query($conn, $mysqlqw);
  if ($error=mysqli_error($conn)) { echo "mysqltovars3.php -> $error<br>$mysqlqw<br>";exit; }
  $this->system="<b>Mysqltovars3</b>: Query Loaded SuccesFull";
  if ($recourse)
  {
  $loop=0;$loadresult=0;$itmsonpage=0;$result=array();
  $this->itmppage=(int)$this->itmppage;
  $this->item=(int)$this->item;
  $this->chpage=(int)$this->chpage;
  $this->chitem=(int)$this->chitem;
  $this->page=(int)$this->page;
  if ($this->itmppage<1) $this->itmppage=1;
  if ($this->item>0) { $ses["inr$cpath"]=$this->item; }
  if ($this->chpage<>0) $ses["inr$cpath"]=$ses["inr$cpath"]+($this->itmppage*$this->chpage);
  if ($this->chitem<>0) $ses["inr$cpath"]=$ses["inr$cpath"]+$this->chitem;
  if ($this->page>0) $ses["inr$cpath"]=1+(($this->page-1)*$this->itmppage);
  $this->itmsonpage=&$itmsonpage;
  if ($this->sort==1) $this->sort='sort';

  if (($ses["inr$cpath"] ?? 0)<=(1-$this->itmppage))
	$ses["inr$cpath"]=1+(ceil(($ses["toti$cpath"] ?? 0)/$this->itmppage)-1)*$this->itmppage;
  if (($ses["inr$cpath"] ?? 0)<1) $ses["inr$cpath"]=1;
  if (($ses["inr$cpath"] ?? 0)>($ses["toti$cpath"] ?? 0)) $ses["inr$cpath"]=1;

  if ($this->mitems>$this->itmppage) $this->mitems=$this->itmppage;
  if ($this->mitems>($ses["toti$cpath"] ?? 0)) $this->mitems=($ses["toti$cpath"] ?? 0);
	$firstitems=array();$sortay=array();$minitems=array();
	while ($linedata=mysqli_fetch_array($recourse))
	{ $loop++;$linedata['loopnr']=$loop;
	 if (!empty($linedata['id'])) $linedata[0]=$linedata['id'];
	 if ($this->sort and isset($linedata[$this->sort])
        and $linedata[$this->sort]<>$loop) $sortay[$linedata['id'] ?? 0]=$loop;
     if (($linedata['timestamp'] ?? '') > ($echo['lastupdate'] ?? '')) $echo['lastupdate']=$linedata['timestamp'] ?? '';
	 if ($this->id==$linedata[0]) { $ses["inr$cpath"]=$loop;$loadresult=1; }
	 if ($this->id<1 and $loop>=$ses["inr$cpath"]) $loadresult=1;
	 if ($itmsonpage>=$this->itmppage) $loadresult=0;
	 if ($loadresult==1) { $itmsonpage=$itmsonpage+1;
        if ($this->lnfnc) $linedata=$this->lnfnc($linedata);
        $result[$itmsonpage]=$linedata; }
	 if ($loop<=$this->itmppage) { $firstitems[$loop]=$linedata; }
	 if ($this->mitems>1 and $ses["toti$cpath"]-$loop<$this->mitems) { $minitems[($this->mitems-($ses["toti$cpath"]-$loop))]=$linedata; }
	}

  if ($itmsonpage<1 and is_array($firstitems))
	{ $result=$firstitems;$this->item=$ses["inr$cpath"]=1;$itmsonpage=count($result); }

  if ($itmsonpage<$this->mitems and is_array($minitems))
    { $result=$minitems;$itmsonpage=count($result); }

  if ($this->debug>0) { echo'<textarea cols="120" rows="10">'; print_r($result); echo'</textarea>'; }

  // ** Sort System
  if ($this->sort and is_array($sortay))
    foreach ($sortay as $key => $val)
	  { mysqli_query($conn, "UPDATE `$this->table` SET $this->sort='$val' WHERE id='$key'"); }
  if ($error=mysqli_error($conn)) { echo "mysqltovars3.php -> SORTerror: $error<br><br>";exit; }

  $this->pageinfo['num_rows']=$itmsonpage;
  $this->pageinfo['totalitems'] = $ses["toti$cpath"] = $loop;
  $this->pageinfo['pagenr'] = ceil(($ses["inr$cpath"]+$this->itmppage-1)/$this->itmppage);
  $this->pageinfo['totalpages'] = ceil(($ses["inr$cpath"]-1)/$this->itmppage) +
	ceil(($ses["toti$cpath"]-$ses["inr$cpath"]+1)/$this->itmppage);
  $echo['pageinfo']=$this->pageinfo;
  }
 if ($this->pageinfo['totalpages']>1 and $loop>$itmsonpage)
 { $echo['backnext']='visible';$echo['backnext2']='display: block'; }
 else
 { $echo['backnext']='hidden'; $echo['backnext2']='display:none'; }

 if ($this->pageinfo['totalitems']<1) $echo['msg']=($echo['msg'] ?? '')."No items Found ".($GLOBALS['_POST']['searchtxt'] ?? ''). "<br>";
 else if ($this->pageinfo['num_rows']<1 and $this->delid<1) $echo['msg']=($echo['msg'] ?? '')."Item not Found ".($GLOBALS['_POST']['searchtxt'] ?? '')."<br>";
 return $result;
}
var $ver='phpwotan.com Mysqltovars Module V3.221 | mysqli/PHP8 upgrade 2026';
}
?>
