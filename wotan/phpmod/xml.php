<?php
function array2xml ($element='',$array)
{ if ($element) $return="<$element>";else $return='';
  if (!is_array($array) or count($array)==0) return '<error>No Input Data</error>';
  foreach($array as $key => $val)
    $return.="<$key>".htmlentities(stripslashes($val))."</$key>\r\n";
  if ($element) $return=$return."</$element>";
  return $return;
}

//PHP psywizard@mail.com xml V1.02 PW160304-170906
?>