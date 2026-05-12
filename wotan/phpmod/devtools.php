<?php $ses['verdate']['varstomysql3']=805;
class devtools
{ // *** Default Object vars (DO NOT CHANGE HERE!!!! > Change via the settings )
  var $system; var $errors; 	// * System and error messages
  var $table='Unknown'; 		// * Mysql Table

 // *** VARSTOMYSQL Function
 function mysql_addfields($input='')
 { if(!is_array($input)) return false;
   global $mysql,$ses,$echo; $this->system=&$echo['msg'];
   if($input['table']) { $this->table=$input['table'];unset($input['table']); }
   pw_systemlog('devtools/mysql_addfields',"Posted Mysql fields for table {$this->table}",$input);
   unset($input['loopnr'],$input['addnewitem'],$input['upditem'],$input['delitem']); // Do not add these fields
   foreach($input as $key=>$val) if($key)
   { //echo("Alter table {$this->table} add column $key varchar(255) not null").'<br />';
     mysqli_query(_pw_mysqli(), "Alter table {$this->table} add column $key varchar(255) not null").'<br />';
     if(!$error=mysqli_error(_pw_mysqli())) echo "DEVTOOLS=>mysql_addfields: Field $key added to {$this->table}<br />"; else $this->errors[]=$error;
   }
 }
var $ver='phpwotan.com Devtools v3.00 PW3.100508';
}
?>