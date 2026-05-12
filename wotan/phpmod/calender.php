<?php
class calender 
{ // *** Default Settings + Info  *** NOT NECESSARY TO EDIT THIS FILE *** (prevent errors keep class original)
  // *** All settings can be set in the object var you create from the Class
  var $mkheader=1; 											// * Make Row a With buttons input/echo Month Year
  var $mkdays=1;   											// * Make Row b With DayNames in language setlocal
  var $a='style="text-decoration: none; font-weight: 900; font-size: 24px; color: #8B00C2"';// * Style Links Row a
  var $inputa='style="border-style: none; background-color: transparent; font-weight: 900; text-align: center;"'; // * Style DateInput Row a
  var $rowb='align="center"';								// * Style Row b DayNames
  var $deftd='width="28" height="29" align="center"'; 		// * TD prop. days
  var $days='style="color: Black;cursor: hand; font-weight: 900;"';	// * Style TD days
  var $divdays='';											// * Div Day exmpl: style="position: relative; top: -2px;right: 1px;"
  var $dayformat='d';										// * d 01-02->31 j 1-2-3-31 etc..
  var $dayso='style="color: #BABABA;font-style: italic;"';	// * Style Days out the Selected Month
  var $selday='style="color: White; background-color: red;"';// * Style Selected Day
  var $today='style="color: White; background-color: #6B00A2;"';// * Style Today
  var $time=0;												// * Selected Unix Timestamp
  var $day=0;											    // * Selected Unix DayStamp
  var $date='';												// * Seleted date in strtotime format
  var $chmonth=0;											// * Change month from Selected Time
  // ** Marking Periods
  var $styles=array('style="color: black; background-color: #E0E0F0;"');// * Style array
  var $dates=array();										// * $dates[unixds]=1 chmarking 1 style up $dates[unixds]=-1 chmarking 1 style back
  var $mark=0;												// * Marking status ( 0:$day >0:$style[$this->mark] )
  var $titles=array();										// * Title Set works same as $dates only content of array will be the title
  var $startweek=0;                                         // * Startweek 0=sunday 1=monday

  // *** MakeCalender
  function mkcal($date='',$chmonth=0) // SelectedDate,ChangeMonth
  {	if ($date) $this->date=&$date;  							// * Function input 
    if ($chmonth<>0) $this->chmonth=&$chmonth; 					// * Function Input
	if ($this->date) { $this->time=strtotime($this->date); } 	// * Set time to Selected Date
 	if ($this->time<1 or !$this->date) { $this->time=time(); } 	// * Set time to today when time<1
	if ($this->chmonth<>0) { $this->time=mktime(0,0,12,(date('m',$this->time)+$this->chmonth),1,date('Y',$this->time)); } // * Set Time to Chmonth


    // ** MkHeader Echo/Input Month Year back Next buttons
    if ($this->mkheader) 
	{ $cal='<tr><td align="center"><a '.$this->a.' href="?date='.$this->date.'&chmonth='.($this->chmonth-1).'"><</a>
			<td colspan="5" align="center"><b><input name="date" type="text" value="'.date('F Y',$this->time).'"'.$this->inputa.'></b></td>
			<td align="center"><a '.$this->a.' href="?date='.$this->date.'&chmonth='.($this->chmonth+1).'">></a></td></tr>';
	} else $cal='';
    
	// ** Create Calender
	if(!isset($this->titles[time()/86400])) 
		{ $this->titles[time()/86400]='Today';$this->titles[((time()/86400)+1)]=''; } // Set Today title
	// * Calcalate Timestamp for 1 Calender day
    if($this->startweek>0) $start=86400; else $start=0;
	$unxts=strtotime('1-'.date('M-Y',$this->time));$w=date('w',$unxts);$unxts=$unxts-(86400*$w)+43200+$start;
	$this->day=round($this->time/86400); // Set unix day stamp
    if ($this->mkdays<1) $trows=7;else $trows=8;
	for ($rows=1;$rows<$trows;$rows++)
	{ $cal.='<tr>';for ($days=1;$days<8;$days++)
		{	$unixds=round($unxts/86400);
			if (date('m',$unxts)!=date('m',$this->time)) $td=$this->dayso;					// * Days out of Selected month
   			else {
		 	 if ($this->dates[$unixds]<>0) $this->mark=$this->mark+$this->dates[$unixds];	// * Check Marking date
			 if ($this->mark<1) { $td=$this->days;$this->mark=0; }								// * Set Style to days
			   else { if($this->styles[$this->mark]) $td=$this->styles[$this->mark]; else $td=$this->styles[0]; } // * Set Style to Marking
             if (isset($this->titles[$unixds])) $this->title=$this->titles[$unixds];				// Set Title
            }
			if (date('d-m-Y',$unxts)==date('d-m-Y',strtotime($this->date)) and strstr($this->date,'/')) 
				{ $td=$this->selday;$this->day=round($this->time/86400); } 					// * Selected Days
			if (date('d-m-Y',$unxts)==date('d-m-Y')) $td=$this->today; 						// * Today
			if($this->mkdays>0) $cal.='<td '.$this->rowb.'>'.date('D',(($days+2)*86400)).'</td>';// * DayNames
			else { $cal.="<td title='$this->title' $this->deftd $td onclick=\"window.location=('?date=".date('m/d/Y',$unxts)."');\">
					<div $this->divdays>".date($this->dayformat,$unxts).'</div></td>'; $unxts=$unxts+86400; } 													// * Calender Days
		}	$this->mkdays=0;$cal.='</tr>';
	} return $cal;
  }

  function example ($date='',$chmonth=0) // SelectedDate,ChangeMonth
  { return '<html><body bgcolor="#F7F0FF"><form method="get"><center><br><br><br><br>
	 		<table background="background.jpg" border="1" bordercolordark="#656565" bordercolorlight="#E0E0FF">
		   '.$this->mkcal($date,$chmonth).'</table></form></center><br><br><pre>'.print_r($this,true).'</pre></body></html>';
  }
var $ver='phpwotan.com Calender-ClassV1 Pw3-261105-L201205';
}
// *** !$ses Run Example
if(!is_array($ses)) { $cal=new Calender();echo $cal->example($_GET['date'],$_GET['chmonth']); }
?>
