<?php
/**
 * P25Reflector Modern API
 * Centralized data retrieval with JSON output
 */

header('Content-Type: application/json');
include "config/config.php";

// Set error handling to return JSON instead of HTML
error_reporting(0);

/**
 * Security: Sanitize all outputs for XSS prevention
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if (is_bool($data)) return $data;
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Optimization: Efficiently tail the log file
 */
function tailLog($lines = 500) {
    $logPath = P25REFLECTORLOGPATH . "/" . P25REFLECTORLOGPREFIX . "-" . date("Y-m-d") . ".log";
    
    // Fallback: If today's log doesn't exist, find the most recent one matching the prefix
    if (!file_exists($logPath)) {
        $logs = glob(P25REFLECTORLOGPATH . "/" . P25REFLECTORLOGPREFIX . "-*.log");
        if (empty($logs)) return [];
        usort($logs, function($a, $b) { return filemtime($b) - filemtime($a); });
        $logPath = $logs[0];
    }
    
    if (!file_exists($logPath)) return [];
    
    // Using PHP's native file functions with a limit is safer than exec/backticks
    $file = new SplFileObject($logPath, 'r');
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key();
    
    $start = max(0, $totalLines - $lines);
    $file->seek($start);
    
    $output = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line)) $output[] = $line;
    }
    return $output;
}

/**
 * Robust log scanner: Finds the last N transmissions by scanning backwards
 */
function getRecentTransmissions($count = 50) {
    $heardList = [];
    $logs = glob(P25REFLECTORLOGPATH . "/" . P25REFLECTORLOGPREFIX . "-*.log");
    if (empty($logs)) return [];
    usort($logs, function($a, $b) { return filemtime($b) - filemtime($a); });

    foreach ($logs as $logPath) {
        $file = new SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        // Read in chunks of 1000 lines backwards
        $chunkSize = 1000;
        $tempEndTime = null; 

        for ($pos = $totalLines; $pos >= 0; $pos -= $chunkSize) {
            $start = max(0, $pos - $chunkSize);
            $file->seek($start);
            $lines = [];
            for ($i = 0; $i < $chunkSize && !$file->eof(); $i++) {
                $lines[] = $file->fgets();
            }
            
            // Process lines in reverse for this chunk
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = $lines[$i];

                // Capture end-of-transmission timestamp
                if (strpos($line, "Received end of transmission") !== false) {
                    $tempEndTime = substr($line, 3, 19);
                }

                if (strpos($line, "Transmission from") !== false) {
                    // Match Time, Callsign (after from), Gateway (after at), and Target (after to)
                    if (preg_match('/M: ([\d\s:.-]+) Transmission from\s+(.*?)\s+at\s+(.*?)\s+to\s+(.*)/', $line, $matches)) {
                        $startTimeStr = trim($matches[1]);
                        $duration = "--";
                        
                        if ($tempEndTime) {
                            $start = new DateTime(substr($startTimeStr, 0, 19), new DateTimeZone('UTC'));
                            $end = new DateTime($tempEndTime, new DateTimeZone('UTC'));
                            $diff = $end->getTimestamp() - $start->getTimestamp();
                            if ($diff >= 0 && $diff < 3600) { // Sanity check: same transmission
                                $duration = $diff . "s";
                            }
                        }

                        $heardList[] = [
                            'time' => $startTimeStr,
                            'callsign' => trim($matches[2]),
                            'gateway' => trim($matches[3]),
                            'target' => trim($matches[4]),
                            'active' => false,
                            'duration' => $duration
                        ];
                        $tempEndTime = null; // Reset for next one
                        if (count($heardList) >= $count) break 3;
                    }
                }
            }
        }
    }
    return $heardList;
}

/**
 * Parsing Logic (Combined)
 */
function getDashboardData() {
    $logLines = tailLog(500); // For active status and gateways
    
    $gateways = [];
    $transmitting = null;
    $now = new DateTime('now', new DateTimeZone('UTC'));

    $events = [];

    // 1. Get History (spanning logs)
    $heardList = getRecentTransmissions(LAST_HEARD_COUNT);

    // 2. Identify events and status from tail
    foreach ($logLines as $line) {
        // Events
        if (strpos($line, "Adding") !== false || strpos($line, "Removing") !== false) {
            $events[] = [
                'time' => substr($line, 3, 19),
                'msg' => str_replace('M: ', '', trim($line))
            ];
        }

        // Transmission started (Active)
        if (strpos($line, "Transmission from") !== false) {
             if (preg_match('/M: ([\d\s:.-]+) Transmission from\s+(.*?)\s+at\s+(.*?)\s+to\s+(.*)/', $line, $matches)) {
                 $transmitting = [
                    'time' => trim($matches[1]),
                    'callsign' => trim($matches[2]),
                    'gateway' => trim($matches[3]),
                    'target' => trim($matches[4]),
                    'active' => true
                 ];
             }
        }

        // Transmission ended
        if (strpos($line, "Received end of transmission") !== false) {
            $transmitting = null;
        }

        // Gateway tracking
        if (preg_match('/Adding (\S+)/', $line, $m)) {
            $gateways[$m[1]] = ['callsign' => trim($m[1]), 'last_seen' => substr($line, 3, 19)];
        }
        if (preg_match('/Removing (\S+)/', $line, $m)) {
            unset($gateways[$m[1]]);
        }
        if (preg_match('/M: [\d\s:.-]+\s+(\S+)\s+:\s+([\d.:]+)/', $line, $m)) {
             $gateways[$m[1]] = ['callsign' => trim($m[1]), 'last_seen' => substr($line, 3, 19)];
        }
    }

    return [
        'transmitting' => $transmitting,
        'heard' => $heardList,
        'gateways' => array_values($gateways),
        'events' => array_slice(array_reverse($events), 0, 10),
        'timestamp' => $now->format('Y-m-d H:i:s')
    ];
}

/**
 * System Data: Get CPU temp, load, etc.
 */
function getSystemData() {
    $data = [
        'temp' => '--',
        'load' => '--',
        'uptime' => '--'
    ];
    
    // CPU Temp
    if (file_exists("/sys/class/thermal/thermal_zone0/temp")) {
        $temp = file_get_contents("/sys/class/thermal/thermal_zone0/temp");
        $data['temp'] = round($temp / 1000, 1) . '°C';
    }
    
    // Load Average
    if (file_exists("/proc/loadavg")) {
        $load = explode(' ', file_get_contents("/proc/loadavg"));
        $data['load'] = $load[0] . ' (1m)';
    }

    // Uptime
    if (file_exists("/proc/uptime")) {
        $uptime = explode(' ', file_get_contents("/proc/uptime"))[0];
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $data['uptime'] = ($days > 0 ? $days . 'd ' : '') . $hours . 'h';
    }
    
    return $data;
}

$data = getDashboardData();
$data['system'] = getSystemData();
echo json_encode(sanitize($data));
?>