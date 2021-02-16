<?php
function getP25ReflectorVersion() {
	// returns creation-time of P25Reflector as version-number
	$filename = P25REFLECTORPATH."/P25Reflector";
	exec($filename." -v 2>&1", $output);
	if (!startsWith(substr($output[0],21,8),"20")) {
		return getP25ReflectorFileVersion();
	} else {
		return substr($output[0],21,8)." (compiled ".getP25ReflectorFileVersion().")";
	}
}


function getP25ReflectorFileVersion() {
	// returns creation-time of P25Reflector as version-number
	$filename = P25REFLECTORPATH."/P25Reflector";
	if (file_exists($filename)) {
		return date("d M Y", filectime($filename));
	}
}

function getP25ReflectorConfig() {
	// loads P25Reflector.ini into array for further use
	$conf = array();
	if ($configs = fopen(P25REFLECTORINIPATH."/".P25REFLECTORINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getConfigItem($section, $key, $configs) {
	// retrieves the corresponding config-entry within a [section]
	$sectionpos = array_search("[" . $section . "]", $configs) + 1;
	$len = count($configs);
	while(startsWith($configs[$sectionpos],$key."=") === false && $sectionpos <= ($len) ) {
		if (startsWith($configs[$sectionpos],"[")) {
			return null;
		}
		$sectionpos++;
	}

	return substr($configs[$sectionpos], strlen($key) + 1);
}

function getP25ReflectorLog() {
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	if ($log = fopen(P25REFLECTORLOGPATH."/".P25REFLECTORLOGPREFIX."-".date("Y-m-d").".log", 'r')) {
		while ($logLine = fgets($log)) {
			array_push($logLines, $logLine);
		}
		fclose($log);
	}
	return $logLines;
}

function getShortP25ReflectorLog() {
// YSF
//M: 2018-04-12 18:11:10.807 Received data from N4IRS      to ALL        at N4IRS
//M: 2018-04-12 18:11:11.602 Received end of transmission
// P25
//M: 2018-04-12 18:39:20.784 Transmission started from KC5MMW     (166.173.61.41:6247)
//M: 2018-04-12 18:39:20.795 Transmission from K5BRM at KC5MMW     to TG 10200
//M: 2018-04-12 18:39:23.455 Received end of transmission

	// Open Logfile and copy loglines into LogLines-Array()
	$logPath = P25REFLECTORLOGPATH."/".P25REFLECTORLOGPREFIX."-".date("Y-m-d").".log";
	//$logLines = explode("\n", `tail -n100 $logPath`);
	$logLines = explode("\n", `egrep -h "Transmission|Received|watchdog" $logPath | tail -1`);
	return $logLines;
}

function getConnectedGateways($logLines) {
	$gateways = Array();
	foreach ($logLines as $logLine) {
		if(strpos($logLine,"P25Reflector")){
			$gateways = Array();
		}
		if(strpos($logLine,"Adding")) {
			$lineParts = explode(" ", $logLine);
			if (!array_search($gateways, $lineParts[4])) {
				array_push($gateways, Array('callsign'=>$lineParts[4],'timestamp'=>$lineParts[1]." ".substr($lineParts[2],0,8)));
			}
		}
		if(strpos($logLine,"Removing")) {
			$lineParts = explode(" ", $logLine);
			$pos = array_search($lineParts[4],array_column($gateways, 'callsign'));
			array_splice($gateways, $pos, 1);
		}
	}
	return $gateways;
}

function getLinkedGateways($logLines) {
// YSF
//0000000000111111111122222222223333333333444444444455555555556666666666
//0123456789012345678901234567890123456789012345678901234567890123456789
//M: 2016-06-24 11:11:41.787 Currently linked repeaters/gateways:
//M: 2016-06-24 11:11:41.787     GATEWAY   : 217.82.212.214:42000 2/60
//M: 2016-06-24 11:11:41.787     DM0GER    : 217.251.59.165:42000 5/60

// P25
//0000000000111111111122222222223333333333444444444455555555556666666666
//0123456789012345678901234567890123456789012345678901234567890123456789
//M: 2016-10-11 19:47:19.081 Currently linked repeaters:
//M: 2016-10-11 19:47:19.081     GB7KH      (178.21.236.222:42010) 2/120
//M: 2016-10-11 19:47:19.081     DL5BQ      (178.201.193.183:42010) 10/120

	$gateways = Array();
	for ($i = count($logLines); $i>0; $i--) {
		if(isset($logLines[$i])){
			$logLine = $logLines[$i];

			if (strpos($logLine, "Starting P25Reflector")) {
				return $gateways;
			}
			if (strpos($logLine, "No repeaters/gateways linked")) {
				return $gateways;
			}
			if (strpos($logLine, "Currently linked repeaters")) {
				for ($j = $i+1; $j <= count($logLines); $j++) {
					if(isset($logLines[$j])){
						$logLine = $logLines[$j];
						if (!startsWith(substr($logLine,27), "   ")) {
							return $gateways;
						} else {
							$timestamp = substr($logLine, 3, 19);
							$callsign = substr($logLine, 31, 11);
							$ipport = substr($logLine, 43);
							$key = searchForKey("ipport",$ipport, $gateways);
							if ($key === NULL) {
								array_push($gateways, Array('callsign'=>$callsign,'timestamp'=>$timestamp,'ipport'=>$ipport));
							}
						}
					}
				}
			}
		}
	}
	return $gateways;
}

function getHeardList($logLines) {
// P25
//0000000000111111111122222222223333333333444444444455555555556666666666
//0123456789012345678901234567890123456789012345678901234567890123456789
//M: 2016-10-11 19:45:38.033 Transmission from DL5BQ at DL5BQ      to TG 10100

	$heardList = array();
	$dttxend = "";
	foreach ($logLines as $logLine) {
		$duration = "transmitting";
		$timestamp = substr($logLine, 3, 19);
		$dttimestamp = new DateTime($timestamp);
		if ($dttxend !== "") {
			$duration = $dttimestamp->diff($dttxend)->format("%s");
		}
		$callsign2 = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"at") - strpos($logLine,"from") - 6);
		$callsign = trim($callsign2);
		$target = substr($logLine, strpos($logLine, "to") + 3);
		$gateway = trim(substr($logLine, strpos($logLine,"at") + 3, strpos($logLine,"to") - strpos($logLine,"at") - 4));
		// Callsign or ID should be less than 11 chars long, otherwise it could be errorneous
		if ( (strlen($callsign) < 11) && (strlen($callsign) > 0) ) {
			array_push($heardList, array($timestamp, $callsign, $target, $gateway, $duration));
		}
		if(strpos($logLine,"end of") || strpos($logLine,"watchdog has expired") || strpos($logLine,"ended RF data") || strpos($logLine,"ended network") || strpos($logLine,"end of transmission")) {
			$txend = substr($logLine, 3, 19);
			$dttxend = new DateTime($txend);
		}
	}
	return $heardList;
}

function getLastHeard($logLines) {
	//returns last heard list from log
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	$counter = 0;
	foreach ($heardList as $listElem) {
		if(!(array_search($listElem[1], $heardCalls) > -1)) {
			array_push($heardCalls, $listElem[1]);
			array_push($lastHeard, $listElem);
			$counter++;
		}
	}
	return $lastHeard;
}

function getSize($filesize, $precision = 2) {
	$units = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
	foreach ($units as $idUnit => $unit) {
		if ($filesize > 1024)
			$filesize /= 1024;
		else
			break;
	}
	return round($filesize, $precision).' '.$units[$idUnit].'B';
}
?>
