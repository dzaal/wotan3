<?php $ses['verdate']['varstomysql3']=70505;
class varstomysql
{ var $system; var $errors; var $mysql; var $unsetid=0;
  var $vars=array();
  var $table='Unknown';
  var $log=0;
  var $where='';
  var $usevarswhere='';
  var $locked=0;
  var $chkuser=0;
  var $online=0;
  var $nodel='0';
  var $setdelete='';
  var $mysqldelete='';
  var $cleanup='60';
  var $autouid=0;
  var $delmsg='<script LANGUAGE=\"JavaScript\">var delitem=window.confirm(\"Delete This Item?\");
               if (delitem) window.location=\"?delid=$id&dq=yes\";</script>';

 function runall ($input='')
 { if(is_array($input)) $this->vars=$input;
   global $mysql,$ses,$echo;$vars=&$this->vars;
   $this->system=&$echo['msg'];$id=$this->vars['id'] ?? null;
   if($this->autouid>0) $this->vars['users_id']=$GLOBALS['ses']['users_id'];
   $table=&$this->table;$this->mysql=&$mysql;$this->errors=&$mysql['error'];
   if (!isset($GLOBALS['_mysqli'])) return;
   $conn = $GLOBALS['_mysqli'];

   if ($this->nodel>0) $this->vars['deleted']=$this->delitm=$this->dq=$this->delid=null;
   if (!empty($this->vars['online']) && $this->vars['online']==1) $this->vars['deleted']=0;
   if (!empty($this->vars['deleted']) && $this->vars['deleted']==1) $this->vars['online']=0;

   // ** Add Item
   if (!empty($this->vars['addnewitem']))
   { $id=pw_arraytodb ($table,($this->vars+$ses));
     if ($id>0)
     { $echo['msg'] = ($echo['msg'] ?? '') . "Item id: $id ".($this->vars['iteminfo'] ?? '')." Added <br>";
       if ($this->log>0) pw_systemlog('/varstomysql3/',"AddItem $table",$ses,$this->vars);
       $GLOBALS['_GET']['id']=$id;return 1;
     } $this->error=$id;return 0;
   }

   // ** Create where for Update or Delete
   if (!empty($this->vars['delid']) && $this->vars['delid']>0 and $id<1) $id=$this->vars['delid'];
   if (empty($where)) $where="id='$id'";
   if ($this->where) $where=$this->where;
   if ($this->usevarswhere>0) $where.=$this->vars['where'];
   if ($this->chkuser>0) $where.=" and users_id='".mysqli_real_escape_string($conn,$ses['users_id'])."'";
   if ($this->locked>0) $where.=" and locked='0'";
   if ($this->online>0) $where.=" and online='1'";

   // ** Update
   if (!empty($this->vars['upditem']) and $id>0)
   { $id=pw_arraytodb ($table,($this->vars+$ses),$where);
     if ($this->log>0) pw_systemlog('/varstomysql3/',"UpdItem $table",$ses,$this->vars);
     if ($mysql['affected']<1) { $echo['msg'].="Item ".($this->vars['id'] ?? '').($this->vars['iteminfo'] ?? '')." not changed<br>"; return 2; }
     if ($mysql['affected']>0) { $echo['msg'].="Item $id ".($this->vars['iteminfo'] ?? '')." updated<br>"; return 3; }
	 if ($mysql['affected']>1)
     { pw_systemlog('/varstomysql3/','WARNING Update Affected '.$mysql['affected'].' rows',$where);
		 $echo['msg'].="{$mysql['affected']} Extra Items updated<br>";return 4;
   } }

  // *** Delete Item Confirmation
  if (!empty($this->vars['delitem']) and $id>0)
  { eval ('$echo[\'msg\'].="'.$this->delmsg.'";');return 5; }

  // *** Cleanup Deleted Items (users_id=0 or deleted=1)
  if (!empty($this->vars['deleted']) or (!empty($this->vars['dq']) && ($this->vars['dq']==1 or $this->vars['dq']=='yes')))
  { $fldel='';
    if($this->setdelete>0) $fldel="or deleted='1'";
    if($this->cleanup<2) $this->cleanup=2;
    $aff_before = mysqli_affected_rows($conn);
    mysqli_query($conn, "DELETE LOW_PRIORITY FROM `$table` WHERE DATEDIFF(timestamp,now())<-$this->cleanup AND (users_id='0' $fldel) LIMIT 25");
    $this->errors=mysqli_error($conn);
    $aff = mysqli_affected_rows($conn);
    if ($aff>0) { pw_systemlog('/varstomysql3/',"CLEANUP: Deleted $aff items from $table"); }
  }

  // *** Delete Item by setting users_id=0
  if ((!empty($this->vars['dq']) && ($this->vars['dq']==1 or $this->vars['dq']=='yes')) and $id>0 and !$this->mysqldelete and !$this->setdelete)
  { mysqli_query($conn, "UPDATE `$table` SET users_id='0',online='0' WHERE $where");
    $this->errors=mysqli_error($conn);
    $aff = mysqli_affected_rows($conn);
	if ($aff<1) { $echo['msg'].="Delete id {$this->vars['delid']} ".($this->vars['iteminfo'] ?? '')." Failed.<br>";
        pw_systemlog('/varstomysql3/',"Delete Item Failed $table",$where);return 6; }
	if ($aff==1) { $echo['msg'].="Item $id ".($this->vars['iteminfo'] ?? '')." moved to trash<br>";
        pw_systemlog('/varstomysql3/',"Deleted Item $id $table",$where);return 7; }
	if ($aff>1) { $echo['msg'].="$aff Items ".($this->vars['iteminfo'] ?? '')." moved to trash<br>";
        pw_systemlog('/varstomysql3/',"DELUPD: WARNING AFFECTED ROWS >1 $table",$where);return 8; }
  }

  // *** Delete Item by setting deleted=1
  if ((!empty($this->vars['dq']) && ($this->vars['dq']==1 or $this->vars['dq']=='yes')) and $id>0 and $this->setdelete)
  { mysqli_query($conn, "UPDATE `$table` SET deleted='1',online='0' WHERE $where");
    $this->errors=mysqli_error($conn);
    $aff = mysqli_affected_rows($conn);
	if ($aff<1) { $echo['msg'].="Delete id {$this->vars['delid']} ".($this->vars['iteminfo'] ?? '')." Failed.<br>";
        pw_systemlog('/varstomysql3/',"Delete Item Failed $table",$where);return 9; }
	if ($aff==1) { $echo['msg'].="Item $id ".($this->vars['iteminfo'] ?? '')." moved to trash<br>";
        pw_systemlog('/varstomysql3/',"Deleted Item $table",$where);return 10; }
	if ($aff>1) { $echo['msg'].="$aff Items ".($this->vars['iteminfo'] ?? '')." moved to trash<br>";
        pw_systemlog('/varstomysql3/',"DELUPD: WARNING AFFECTED ROWS >1 $table",$where);return 11; }
  }

  // *** Delete Item by mysql Delete
  if ((!empty($this->vars['dq']) && ($this->vars['dq']==1 or $this->vars['dq']=='yes')) and $id>0 and $this->mysqldelete)
  { mysqli_query($conn, "DELETE FROM `$table` WHERE $where");
    $this->errors=mysqli_error($conn);
    $aff = mysqli_affected_rows($conn);
	if ($aff<1) { $echo['msg'].="Delete id {$this->vars['delid']} ".($this->vars['iteminfo'] ?? '')." Failed.<br>";
        pw_systemlog('/varstomysql3/',"Delete Item Failed $table",$where);return 12; }
	if ($aff==1) { $echo['msg'].="Item $id ".($this->vars['iteminfo'] ?? '')." Deleted<br>";
        pw_systemlog('/varstomysql3/',"Deleted Item $table",$where);return 13; }
	if ($aff>1) { $echo['msg'].="$aff Items ".($this->vars['iteminfo'] ?? '')." Deleted<br>";
        pw_systemlog('/varstomysql3/',"DELUPD: WARNING AFFECTED ROWS >1 $table",$where);return 14; }
  }
 }
var $ver='phpwotan.com VarstoMysql v3.24 | mysqli/PHP8 upgrade 2026';
}
?>
