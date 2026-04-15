<?php
/**
 * DVSwitch Dashboard Interactive Setup
 * Usage: php setup.php [path/to/Reflector.ini]
 */

echo "\n--- DVSwitch Universal Dashboard Setup ---\n\n";

// 1. Get INI Path
$iniPath = $argv[1] ?? "";
if (!$iniPath) {
    echo "Path to Reflector.ini [./Reflector.ini]: ";
    $iniPath = trim(fgets(STDIN)) ?: "./Reflector.ini";
}

if (!file_exists($iniPath)) {
    echo "Warning: Reflector.ini not found at $iniPath. You will need to enter settings manually.\n";
    $iniContent = "";
    $iniDir = "./";
    $iniFile = "Reflector.ini";
} else {
    echo "Found INI at $iniPath. Auto-detecting settings...\n";
    $iniContent = file_get_contents($iniPath);
    $iniDir = dirname(realpath($iniPath)) . "/";
    $iniFile = basename($iniPath);
}

// Extraction Helper
function getVal($content, $key) {
    if (preg_match('/^\s*' . $key . '\s*=\s*(.*)/m', $content, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

// 2. Auto-detect core settings
$detectedPrefix = getVal($iniContent, "FileRoot") ?: "P25_NA";
$detectedLogPath = getVal($iniContent, "FilePath") ?: "./logs";
$mode = strtoupper(explode('_', $detectedPrefix)[0]);

// 3. Interactive Prompts
function prompt($label, $default) {
    echo "$label [$default]: ";
    $input = trim(fgets(STDIN));
    return $input ?: $default;
}

echo "\n--- Dashboard Identity ---\n";
$title = prompt("Dashboard Title", "$mode Reflector");
$subtitle = prompt("Dashboard Subtitle", "Real-time Network Monitoring");
$logo = prompt("Logo Filename (in root)", "DVSwitch.png");

echo "\n--- Reflector Connection ---\n";
$prefix = prompt("Log Prefix", $detectedPrefix);
$logPath = prompt("Log File Directory", $detectedLogPath);
$binPath = prompt("Reflector Binary Directory", $iniDir);
$iniDirPath = prompt("Reflector INI Directory", $iniDir);
$iniFileName = prompt("Reflector INI Filename", $iniFile);

echo "\n--- UI Features ---\n";
$showQRZ = prompt("Enable QRZ.com links? (1/0)", "1");
$showStats = prompt("Show System Stats card? (1/0)", "1");
$showPulse = prompt("Show Network Pulse log? (1/0)", "1");

// 4. Generate Config
$configTemplate = <<<EOD
<?php
/**
 * DVSwitch Universal Reflector Dashboard Configuration
 * Created via Interactive Setup on %s
 */

date_default_timezone_set('UTC');

// --- 1. Branding & Identity ---
define("DASHBOARD_TITLE", "%s");
define("DASHBOARD_SUBTITLE", "%s");
define("LOGO", "%s");

// --- 2. Reflector Connection ---
define("REFLECTOR_LOG_PREFIX", "%s");
define("REFLECTOR_LOG_PATH", "%s");
define("REFLECTOR_INI_PATH", "%s");
define("REFLECTOR_INI_FILE", "%s");
define("REFLECTOR_BIN_PATH", "%s");

// --- 3. UI Features ---
define("SHOWQRZ", "%s");
define("SHOW_SYSTEM_STATS", "%s");
define("SHOW_NETWORK_PULSE", "%s");

// --- 4. Advanced Settings ---
define("API_REFRESH_INTERVAL", "2000");
define("LAST_HEARD_COUNT", "50");
define("TEMPERATUREHIGHLEVEL", "60");
?>
EOD;

$configOutput = sprintf(
    $configTemplate,
    date('Y-m-d H:i:s'),
    $title,
    $subtitle,
    $logo,
    $prefix,
    $logPath,
    $iniDirPath,
    $iniFileName,
    $binPath,
    $showQRZ,
    $showStats,
    $showPulse
);

$targetFile = __DIR__ . "/config/config.php";
echo "\nWriting configuration to $targetFile...\n";

if (file_put_contents($targetFile, $configOutput)) {
    echo "Success! Your dashboard is configured and ready.\n\n";
} else {
    echo "Error: Could not write to $targetFile. Please check permissions.\n\n";
}
?>
