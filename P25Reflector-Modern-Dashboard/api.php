<?php
/**
 * P25Reflector Modern API
 * Centralized data retrieval with JSON output
 */

header('Content-Type: application/json');

// Multi-Config Loader
$conf = isset($_GET['conf']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['conf']) : 'config';
$configFile = __DIR__ . "/config/{$conf}.php";

if (file_exists($configFile)) {
    include $configFile;
} else {
    // Attempt to load the first available config if default 'config.php' is missing
    $altConfigs = glob(__DIR__ . "/config/*.php");
    if (!empty($altConfigs)) {
        include $altConfigs[0];
    } else {
        die(json_encode(['error' => 'No configuration found. Please run setup.php']));
    }
}

// Set error handling to return JSON instead of HTML
error_reporting(0);

// User Database (Optional)
$db = null;
if (class_exists('SQLite3') && file_exists(__DIR__ . "/db/users.db")) {
    try {
        $db = new SQLite3(__DIR__ . "/db/users.db", SQLITE3_OPEN_READONLY);
    } catch (Exception $e) {
        $db = null;
    }
}

function getUserInfo($search) {
    global $db;
    if (!$db) return null;
    
    try {
        $query = "SELECT name, city, state, country FROM users WHERE callsign = :val OR id = :val LIMIT 1";
        $stmt = $db->prepare($query);
        if (!$stmt) return null;
        $stmt->bindValue(':val', $search);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Security: Sanitize all outputs for XSS prevention
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if (is_bool($data))
        return $data;
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Optimization: Efficiently tail the log file
 */
function tailLog($lines = 500)
{
    $logPath = REFLECTOR_LOG_PATH . "/" . REFLECTOR_LOG_PREFIX . "-" . date("Y-m-d") . ".log";

    // Fallback: If today's log doesn't exist, find the most recent one matching the prefix
    if (!file_exists($logPath)) {
        $logs = glob(REFLECTOR_LOG_PATH . "/" . REFLECTOR_LOG_PREFIX . "-*.log");
        if (empty($logs))
            return [];
        usort($logs, function ($a, $b) {
            return filemtime($b) - filemtime($a); });
        $logPath = $logs[0];
    }

    if (!file_exists($logPath))
        return [];

    // Using PHP's native file functions with a limit is safer than exec/backticks
    $file = new SplFileObject($logPath, 'r');
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key();

    $start = max(0, $totalLines - $lines);
    $file->seek($start);

    $output = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line))
            $output[] = $line;
    }
    return $output;
}

/**
 * Robust log scanner: Finds the last N transmissions by scanning backwards
 */
function getRecentTransmissions($count = 50)
{
    $heardList = [];
    $logs = glob(REFLECTOR_LOG_PATH . "/" . REFLECTOR_LOG_PREFIX . "-*.log");
    if (empty($logs))
        return [];
    usort($logs, function ($a, $b) {
        return filemtime($b) - filemtime($a); });

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

                if (strpos($line, "Transmission from") !== false || strpos($line, "Received data from") !== false) {
                    // Match Time, Callsign, Gateway, and Target. Support both P25/NXDN and YSF formats.
                    if (preg_match('/M: ([\d\s:.-]+) (?:Transmission from|Received data from)\s+(.*?)\s+(?:at|to)\s+(.*?)\s+(?:to|at)\s+(.*)/', $line, $matches)) {
                        $startTimeStr = trim($matches[1]);
                        $callsign = trim($matches[2]);
                        
                        // YSF format: from (2) to (3) at (4)
                        // P25 format: from (2) at (3) to (4)
                        if (strpos($line, "Received data") !== false) {
                            $target = trim($matches[3]);
                            $gateway = trim($matches[4]);
                        } else {
                            $gateway = trim($matches[3]);
                            $target = trim($matches[4]);
                        }
                        $duration = "--";

                        if ($tempEndTime) {
                            $start = new DateTime(substr($startTimeStr, 0, 19), new DateTimeZone('UTC'));
                            $end = new DateTime($tempEndTime, new DateTimeZone('UTC'));
                            $diff = $end->getTimestamp() - $start->getTimestamp();
                            if ($diff >= 0 && $diff < 3600) { // Sanity check: same transmission
                                $duration = $diff . "s";
                            }
                        }

                        $userInfo = getUserInfo($callsign);
                        $city = $userInfo['city'] ?? '';
                        $state = $userInfo['state'] ?? '';
                        $location = ($city && $state) ? "$city, $state" : trim("$city $state");
                        
                        $heardList[] = [
                            'time' => $startTimeStr,
                            'callsign' => $callsign,
                            'name' => $userInfo['name'] ?? '',
                            'location' => $location,
                            'target' => $target,
                            'gateway' => $gateway,
                            'active' => false,
                            'duration' => $duration
                        ];
                        $tempEndTime = null; // Reset for next one
                        if (count($heardList) >= $count)
                            break 3;
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
function getDashboardData()
{
    $prefix = REFLECTOR_LOG_PREFIX;
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
        if (strpos($line, "Transmission from") !== false || strpos($line, "Received data from") !== false) {
            if (preg_match('/M: ([\d\s:.-]+) (?:Transmission from|Received data from)\s+(.*?)\s+(?:at|to)\s+(.*?)\s+(?:to|at)\s+(.*)/', $line, $matches)) {
                $callsign = trim($matches[2]);
                if (strpos($line, "Received data") !== false) {
                    $target = trim($matches[3]);
                    $gateway = trim($matches[4]);
                } else {
                    $gateway = trim($matches[3]);
                    $target = trim($matches[4]);
                }

                $userInfo = getUserInfo($callsign);
                $city = $userInfo['city'] ?? '';
                $state = $userInfo['state'] ?? '';
                $location = ($city && $state) ? "$city, $state" : trim("$city $state");

                $transmitting = [
                    'time' => trim($matches[1]),
                    'callsign' => $callsign,
                    'name' => $userInfo['name'] ?? '',
                    'location' => $location,
                    'gateway' => $gateway,
                    'target' => $target,
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
             $c = trim($m[1]);
             $u = getUserInfo($c);
             $gateways[$c] = [
                 'callsign' => $c, 
                 'name' => $u['name'] ?? '',
                 'last_seen' => substr($line, 3, 19)
             ];
        }

        // Track DVREFCK specifically
        if (strpos($line, "DVREFCK") !== false) {
            $lastCheckin = substr($line, 3, 19);
        }
    }

    $mode = explode('_', $prefix)[0];

    return [
        'mode' => strtoupper($mode),
        'last_checkin' => isset($lastCheckin) ? $lastCheckin : null,
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
function getSystemData()
{
    $data = [
        'temp' => '--',
        'load' => '--',
        'uptime' => '--',
        'port' => '--'
    ];

    // Reflector Port from INI
    if (defined("REFLECTOR_INI_PATH") && defined("REFLECTOR_INI_FILE")) {
        $iniPath = rtrim(REFLECTOR_INI_PATH, '/') . '/' . REFLECTOR_INI_FILE;
        if (file_exists($iniPath)) {
            $iniContent = file_get_contents($iniPath);
            if (preg_match('/^\s*Port\s*=\s*(\d+)/m', $iniContent, $matches)) {
                $data['port'] = $matches[1];
            }
        }
    }
    
    // CPU Temp (Smart Search for x86_pkg_temp or acpitz)
    $tempValue = null;
    $zones = glob("/sys/class/thermal/thermal_zone*");
    foreach ($zones as $zone) {
        $type = @file_get_contents("$zone/type");
        if ($type !== false) {
            $type = trim($type);
            if ($type === 'x86_pkg_temp' || $type === 'acpitz') {
                $t = @file_get_contents("$zone/temp");
                if ($t !== false && is_numeric(trim($t))) {
                    $tempValue = (int)trim($t);
                    if ($type === 'x86_pkg_temp') break; // Prioritize Package Temp
                }
            }
        }
    }

    if ($tempValue !== null) {
        $data['temp'] = round($tempValue / 1000, 1) . '°C';
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

$data = getDashboardData(REFLECTOR_LOG_PREFIX);
$data['system'] = getSystemData();
echo json_encode(sanitize($data));
?>