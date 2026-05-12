<?php
class pager
{ var $page=1;          // * Page Number
  var $chpage=0;        // * Change page - back + next  $chpage=-2 go 2pages back
  var $item=1;          // * Item Number
  var $chitem=0;        // * Change Item - back + next  $chitem=3  go 3 items further
  var $itmppage=10;     // * ItemsPer Page
  var $range=10;        // * func Page range: range total clickable page numbers

  function arraytopages ($array,$pagename)
  { $itmsonpage=0;$this->itmsonpage=&$itmsonpage;										                                    // * Set Vars
    if ($this->itmppage<1) $this->itmppage=1;										 			// * $itmppage lowlimit
    if ($this->item>0) { $ses["inr$pagename"]=$this->item; }									// * if $item Set sesItem
    if ($this->chpage<>0) $ses["inr$pagename"]=$ses["inr$pagename"]+($this->itmppage*$this->chpage);// * Back Next Page $chpage
    if ($this->chitem<>0) $ses["inr$pagename"]=$ses["inr$pagename"]+$this->chitem;					// * Back Next Item $chitem
    if ($this->page>0) $ses["inr$pagename"]=1+(($this->page-1)*$this->itmppage);					// * Set sesItem from $page

    if ($ses["inr$pagename"]<=(1-$this->itmppage))
	$ses["inr$pagename"]=1+(ceil($ses["toti$pagename"]/$this->itmppage)-1)*$this->itmppage;		// * Lowlimit sesItem Goto Last Page

    if ($ses["inr$pagename"]<1) $ses["inr$pagename"]=1;									// * Lowlimit sesItem Return to 1
    if ($ses["inr$pagename"]>$ses["toti$pagename"]) $ses["inr$pagename"]=1;				// * Highlimit sesItem Return to 1

    $this->pageinfo['num_rows']=$itmsonpage;
    $this->pageinfo['totalitems'] = $ses["toti$pagename"] = $loop;
    $this->pageinfo['pagenr'] = ceil(($ses["inr$pagename"]+$this->itmppage-1)/$this->itmppage);
    $this->pageinfo['totalpages'] = ceil(($ses["inr$pagename"]-1)/$this->itmppage) +
    	ceil(($ses["toti$pagename"]-$ses["inr$pagename"]+1)/$this->itmppage);// * count pages before and after sesItem
    $echo['pageinfo']=$this->pageinfo; // * $this->result=&$result;

    // ** $echo['backnext']
    if ($this->pageinfo['totalpages']>1) $echo['backnext']='visible';else $echo['backnext']='hidden';

    // ** No Items Found Messages
    if ($this->pageinfo['totalitems']<1) $echo['msg'].="No items Found {$GLOBALS[_POST][searchtxt]}<br>";
    else if ($this->pageinfo['num_rows']<1 and $this->delid<1) $echo['msg'].="Item not Found {$GLOBALS[_POST][searchtxt]}<br>";
  }

  function pagerange ($range,$page,$totalpages)
  { for ($n=1;$n<=$totalpages;$n++) { $return[$n]=($page-($range/2))+$n; }
    return $return;
  }
var $ver='phpwotan.com Pager v1.0 UnderConstruction pw3_170306';
}
if (!is_array($ses)) echo "Pager Module No Wotan Session Detected.<br>
ModuleInfo:<br>
When a result query directory or whatever array
has to many values you maybe like to make pages. <br>
This module is doing the job for you and split your array in pages.<br>
It will return totalpages items itemnumber (is in sessionvar per webpage)<br>
Pagenumber itmsonpage (when a page is not full (lastpage) itmsonpage< itmppage)<br>
and the array containing only the items for 1 page.";
?>
