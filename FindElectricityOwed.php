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

define("LOG_NAME","1507357879_log.txt");
define("OFF_PEAK_RATE",0.07182); //unit kw/h
define("NORMAL_RATE",0.13083); //unit kw/h
define("POWER_CONSUMP",300.0); // unit w
define("SECONDS_FOR_1_KWH",12000); //unit seconds

define("LOGS_DIR","C:\\Users\\simon\\Desktop\\Claymore's Dual Ethereum+Decred_Siacoin_Lbry_Pascal AMD+NVIDIA GPU Miner v10.0\\");

//get list of log files
$files = scandir( LOGS_DIR);
$logs = [];
for($i=0; $i < count($files);$i++){
	if(preg_match ( '/_log.txt$/' , $files[$i]) == 1){
		array_push($logs,$files[$i]);
	}
		
}
//print_r($logs);

$results = []; //array of log file result

foreach($logs as $item){
	$aLog = Process($item);
	array_push($results,$aLog);
}

//$aLog = Process(LOG_NAME);
//array_push($results,$aLog);
sum($results);


function sum($results){
	
	$total = 0;
	
	foreach($results as $item){
		$total = $total + $item->PriceNormal + $item->priceOffPeak;
	}
	
	echo "\n--------------------------------\nThe result is:£$total\n-------------------------\n";

	return $total;
}

function Process($file){
	
	//get timestamp from log name
	$logTimestamp = GetTimeStamp($file);
	echo "\nlogTimestamp:$logTimestamp\n";

	//create file resource handler
	$handle = fopen(LOGS_DIR . $file, "r");

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
		
		//fix - skip lines without timestamp
		if(is_numeric(substr($line,0,2)) == false){
			continue;
		}
		
		$lineHour = intVal(substr($line,0,2));		
		
		if($lineHour < $prevLineHour){
			//echo "$lineHour	$prevLineHour	\n";
			$days++;
		}
		
		$prevLineHour = $lineHour;
		$prevLine = $line;
	}

	$line = $prevLine;

	echo "$line\ndays:$days\n";

	$pos = strpos($line,"\t");
	$atime  = substr($line,0,$pos);
	$timeArray = explode(":",$atime);

	//$tStamp1 = strtotime("23.55.04.726");
	
	date_default_timezone_set('Europe/London');//needed for procedural
	
	$startDateTime = new DateTime();
	$startDateTime->setTimestamp($logTimestamp);
	$startDateTime->setTimezone(new DateTimeZone('Europe/London'));
	
	$endDateTime = new DateTime();
	$endDateTime->setTimestamp($logTimestamp);
	$endDateTime->setTimezone(new DateTimeZone('Europe/London'));	
	$endDateTime->modify("today");
	$endDateTime->add(new DateInterval('P'. $days . 'D' ));
	$endDateTime->setTime ( intval($timeArray[0]) , intval($timeArray[1]) ,intval($timeArray[2]), intval($timeArray[3])*1000 );	
	//$endDateTime->setTimestamp(strtotime("$timeArray[0].$timeArray[1].$timeArray[2].$timeArray[3]", $endDateTime->getTimestamp()));
	
	echo $startDateTime->format("c") . " then " . $endDateTime->format("c") . "\n";
	
	$interval = $startDateTime->diff($endDateTime);
	//echo $interval->format('%h hours %i mins %s seconds')."\n";
	
	//find how much proportion off-peak
	$propOfPeak = ProportionOffPeak($startDateTime,$endDateTime);
	echo "$propOfPeak\n";

	//the duration in hours
	$duration = $endDateTime->getTimestamp() - $startDateTime->getTimestamp();
	//$duration = $duration /60 / 60;
	$kwhQty = $duration / SECONDS_FOR_1_KWH;
	$kwhQtyOffpeak = $propOfPeak * $kwhQty;
	$kwhQtyNormal = $kwhQty - $kwhQtyOffpeak;
	$priceOffPeak = $kwhQtyOffpeak * OFF_PEAK_RATE;
	$priceNormal = $kwhQtyNormal * NORMAL_RATE;
	echo "file:$file";
	echo "\nduration:$duration\n--------------offpeak:$priceOffPeak	normal:$priceNormal \n";

	//close file resource handler
	fclose($handle);

	$alog = new ResultALog();
	$alog->start = $startDateTime;
	$alog->end = $endDateTime;
	$alog->propOffPeak = $propOfPeak;
	$alog->priceOffPeak = $priceOffPeak;
	$alog->PriceNormal = $priceNormal;
	$alog->logFName = $file;	
		
	//return File Result
	return $alog;
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

    // method declaration
    public function displayVar() {
        echo $this->var;
    }
}
?>