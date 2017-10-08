<?php
// This is the server-side script.

// Set the content type.
header('Content-Type: text/plain');

// Send the data back.
//echo "This is the returned text.";

$xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>PHP: Behind the Parser</title>
  <characters>
   <character>
    <name>Ms. Coder</name>
    <actor>Onlivia Actora</actor>
   </character>
   <character>
    <name>Mr. Coder</name>
    <actor>El Act&#211;r</actor>
   </character>
  </characters>
  <plot>
   So, this language. It's like, a programming language. Or is it a
   scripting language? All is revealed in this thrilling horror spoof
   of a documentary.
  </plot>
  <great-lines>
   <line>PHP solves all my web problems</line>
  </great-lines>
  <rating type="thumbs">7</rating>
  <rating type="stars">5</rating>
 </movie>
</movies>
XML;

define("LOG_NAME","1507416904_log.txt");
define("OFF_PEAK_RATE",0.07182); //unit kw/h
define("NORMAL_RATE",0.13083); //unit kw/h
define("POWER_CONSUMP",300.0); // unit w
define("SECONDS_FOR_1_KWH",12000); //unit seconds

define("LOGS_DIR","C:\\Users\\simon\\Desktop\\Claymore's Dual Ethereum+Decred_Siacoin_Lbry_Pascal AMD+NVIDIA GPU Miner v10.0\\toProcess\\");

//get list of log files
$files = scandir( LOGS_DIR);
$logs = [];
for($i=0; $i < count($files);$i++){
	if(preg_match ( '/_log.txt$/' , $files[$i]) == 1){
		array_push($logs,$files[$i]);
	}
		
 }
//print_r($logs);

//get timestamp from log name
$logName = GetTimeStamp(LOG_NAME);

//create file resource handler
$handle = fopen(LOGS_DIR ."1507416904_log.txt", "r");

if($handle == false){
	echo "fail\n";
}
else{
	echo "success\n";
}

$line1 = fgets($handle);
$prevLineHour = -1;
$prevLine = null;
$days = 0;
$line = null;


while(($line = fgets($handle)) != false ){	
	
	//fix for blank lines in log
	if($line == "\n"||$line == "\r\n"){
		continue;
	}
	
	//fix for lines without timestamp
	if(is_numeric(substr($line,0,2)) == false){
		continue;
	}
	
	$lineHour = intVal(substr($line,0,2));
	
	
	//echo substr($line,0,2)."\n";

	
	if($lineHour < $prevLineHour){
		//echo "$lineHour	$prevLineHour	\n";
		$days++;
	}
	
	$prevLineHour = $lineHour;
	$prevLine = $line;
}

$line = $prevLine;

echo "$line	days:$days\n";

$pos = strpos($line,"\t");

$atime  = substr($line,0,$pos);

$tStamp1 = strtotime("23.55.04.726");

$tStamp2 = strtotime("07.41.35.038");

date_default_timezone_set('Europe/London');
$tStamp3 = date("c",1507416904);

$tStamp4 = date('Y-m-d',1507416904);

$tStamp5 = date('Y-m-d H:i:s',1507416904);


$tStampNum = strtotime($tStamp4);

$tStampNumPlusDays = (60*60*24*$days)+$tStampNum;

$timeArray = explode(":",$atime);
$eofStamp = strtotime("$timeArray[0].$timeArray[1].$timeArray[2].$timeArray[3]",$tStampNumPlusDays);
$eofStr = date("c",$eofStamp);

echo "\neofStamp:$eofStamp\n";

echo "$tStamp3 then $eofStr\n";

echo "1507416904 then start of day is $tStampNum";

echo "\n$tStamp1	$tStamp2  $tStamp3 $tStamp4\n";


$dTStart = new DateTime();
$dTStart->setTimestamp(1507416904);
$dTStart->setTimezone(new DateTimeZone('Europe/London'));
$dTEnd = new DateTime();
$dTEnd->setTimestamp($eofStamp);
$dTEnd->setTimezone(new DateTimeZone('Europe/London'));
$interval = $dTStart->diff($dTEnd);
echo $interval->format('%h hours %i mins %s seconds')."\n";

//find how much proportion off-peak
$propOfPeak = ProportionOffPeak($dTStart,$dTEnd);
echo "$propOfPeak\n";

//the duration in hours
$duration = $dTEnd->getTimestamp() - $dTStart->getTimestamp();
//$duration = $duration /60 / 60;
$kwhQty = $duration / SECONDS_FOR_1_KWH;
$kwhQtyOffpeak = $propOfPeak * $kwhQty;
$kwhQtyNormal = $kwhQty - $kwhQtyOffpeak;
$priceOffPeak = $kwhQtyOffpeak * OFF_PEAK_RATE;
$priceNormal = $kwhQtyNormal * NORMAL_RATE;
echo "\nduration:$duration\n offpeak:$priceOffPeak	normal:$priceNormal";



//close file resource handler
fclose($handle);

function Process($file){
	
	//return File Result
}

function IsDayLightSavings($dateTime){

	return (($dateTime->format("I")) == "1") ? true : false;
}

function IsOffPeak($dateTime){
	if(IsDayLightSavings($dateTime)){
		$hour = intVal($dateTime->format("G"));
		if($hour < 7){
			return true;
		}
		else{
			return false;
		}
	}
	else{
		$hour = intVal($dateTime->format("G"));
		if($hour == 23 || $hour < 6){
			return true;
		}
		else{
			return false;
		}
	}
}

function GetTimeStamp($filename){
	
	$timestampStr = substr($filename,0,strpos($filename,"_"));
	echo "$timestampStr\n";
	$timestamp = intVal($timestampStr);

	return $timestamp;
}

function ProportionOffPeak($start,$end){

	$startClone = new DateTime();
	$startClone->setTimestamp($start->getTimestamp());
	$startClone->setTimezone(new DateTimeZone('Europe/London'));
	$start = $startClone;

	$totalSecs = 0;
	$offPeakSecs = 0;

	while($start <= $end){
		if(IsOffPeak($start)){
			$totalSecs++;
			$offPeakSecs++;
		}
		else{
			$totalSecs++;
		}			
		
		$start->add(new DateInterval('PT1S'));
	}
	
	//echo "\ntotalsecs:$totalSecs	offPeakSecs:$offPeakSecs\n";
	
	return $offPeakSecs / $totalSecs;

}

//stores result of processing log
class ResultALog
{
    // property declaration
    public $start; //start datetime
	public $end;  //end datetime
	public $proportionOffPeak;
	public $priceOffPeak;
	public $PriceNormal;
	public $logFName;
	
	function __construct(){		
	}

	//new DateTime("2015-11-01 00:00:00"

    // method declaration
    public function displayVar() {
        echo $this->var;
    }
}
?>