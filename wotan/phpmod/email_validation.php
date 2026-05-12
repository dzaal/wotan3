<?php
/*
email_validation.php
Coded by: Pentest ROMANIA; Dan Catalin VASILE; http://www.pentest.ro
*/ 
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);// * Dev mode


$email=$_GET['email'];
$return =validate_email($email);
header('Content-Type: application/json');
print json_encode($return);
 // var_dump($return);


function validate_email($email){
    $rcpt_text= '';
   $mailparts=explode("@",$email);
   $hostname = $mailparts[1];

   // validate email address syntax
   $exp = "/^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$/i";
   //eregi($exp, $email);
   $b_valid_syntax=preg_match($exp, $email);

   // get mx addresses by getmxrr
   $b_mx_avail=getmxrr( $hostname, $mx_records, $mx_weight );
   //var_dump($mx_records, $mx_weight);
   $b_server_found=0;

   if($b_valid_syntax && $b_mx_avail){
     // copy mx records and weight into array $mxs
     $mxs=array();

     for($i=0;$i<count($mx_records);$i++){
       $mxs[$mx_weight[$i]]=$mx_records[$i];
     }

     // sort array mxs to get servers with highest prio
     ksort ($mxs, SORT_NUMERIC );
     reset ($mxs);

     foreach($mxs as $mx_weight => $mx_host) {
       if($b_server_found == 0){

         //try connection on port 25
         $fp = @fsockopen($mx_host,25, $errno, $errstr, 2);
         if($fp){
           $ms_resp="";
           // say HELO to mailserver
           $ms_resp.=send_command($fp, "HELO example.com");

           // initialize sending mail
           $ms_resp.=send_command($fp, "MAIL FROM:<support@example.com>");

           // try receipent address, will return 250 when ok..
           $rcpt_text=send_command($fp, "RCPT TO:<".$email.">");
           $ms_resp.=$rcpt_text;
          
           if(substr( $rcpt_text, 0, 3) == "250")
             $b_server_found=1;

           // quit mail server connection
           $ms_resp.=send_command($fp, "QUIT");

         fclose($fp);

         }

       }
    }
  }
  $object[0]['valid']= $b_server_found;
  if($mx_records) $object[0]['server']= $mx_records[0];
  if($rcpt_text) $object[0]['rcpt_text']= $rcpt_text;
  return $object[0];
}

function send_command($fp, $out){

  fwrite($fp, $out . "\r\n");
  return get_data($fp);
}

function get_data($fp){
  $s="";
  stream_set_timeout($fp, 2);

  for($i=0;$i<2;$i++)
    $s.=fgets($fp, 1024);

  return $s;
}

// support windows platforms
if (!function_exists ('getmxrr') ) {
  function getmxrr($hostname, &$mxhosts, &$mxweight) {
    if (!is_array ($mxhosts) ) {
      $mxhosts = array ();
    }

    if (!empty ($hostname) ) {
      $output = "";
      @exec ("nslookup.exe -type=MX $hostname.", $output);
      $imx=-1;

      foreach ($output as $line) {
        $imx++;
        $parts = "";
        if (preg_match ("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.*)$/", $line, $parts) ) {
          $mxweight[$imx] = $parts[1];
          $mxhosts[$imx] = $parts[2];
        }
      }
      return ($imx!=-1);
    }
    return false;
  }
}

?>