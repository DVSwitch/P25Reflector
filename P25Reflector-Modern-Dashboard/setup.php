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
// Extraction Helper
function prompt($label, $default) {
    echo "$label [$default]: ";
    $input = trim(fgets(STDIN));
    return $input ?: $default;
}

// Check for Bulk Mode vs Single File
$path = $argv[1] ?? "./";
$reflectorsFound = [];

if (is_dir($path)) {
    echo "Scanning '$path' for reflectors...\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $file) {
        if ($file->getExtension() === 'ini') {
            $content = file_get_contents($file->getPathname());
            
            // The Reflector Signature: Has [Log], FileRoot, a Mode Section, NO [Modem]
            $hasLog = (strpos($content, '[Log]') !== false && strpos($content, 'FileRoot=') !== false);
            $hasMode = (preg_match('/\[(P25|YSF|NXDN|DMR)\]/', $content));
            $isNotNode = (strpos($content, '[Modem]') === false);
            
            if ($hasLog && $hasMode && $isNotNode) {
                // Parse basic info
                $ini = parse_ini_string($content, true);
                $mode = 'Generic';
                if (isset($ini['P25'])) $mode = 'P25';
                if (isset($ini['YSF'])) $mode = 'YSF';
                if (isset($ini['NXDN'])) $mode = 'NXDN';
                if (isset($ini['DMR'])) $mode = 'DMR';

                $reflectorsFound[] = [
                    'path' => $file->getRealPath(),
                    'dir' => $file->getPath(),
                    'file' => $file->getFilename(),
                    'prefix' => $ini['Log']['FileRoot'] ?? basename($file->getFilename(), '.ini'),
                    'mode' => $mode
                ];
            }
        }
    }
} else {
    // Single file logic (legacy)
    $reflectorsFound[] = [
        'path' => realpath($path),
        'dir' => dirname(realpath($path)),
        'file' => basename($path),
        'prefix' => 'Reflector',
        'mode' => 'P25'
    ];
}

if (empty($reflectorsFound)) {
    die("Error: No reflectors found in '$path'.\n");
}

echo "\nDiscovered " . count($reflectorsFound) . " Reflector(s):\n";
foreach ($reflectorsFound as $i => $r) {
    echo "[" . ($i + 1) . "] {$r['mode']} - {$r['file']} (Prefix: {$r['prefix']})\n";
}

$selection = prompt("\nSelect reflectors to configure (e.g. 1,2 or 'all')", "all");
$selectedIndices = [];
if ($selection === 'all') {
    $selectedIndices = range(0, count($reflectorsFound) - 1);
} else {
    foreach (explode(',', $selection) as $idx) $selectedIndices[] = (int)trim($idx) - 1;
}

$logPathDefault = "/var/log/mmdvm";
$logPath = prompt("\nBase directory for logs", $logPathDefault);

foreach ($selectedIndices as $idx) {
    if (!isset($reflectorsFound[$idx])) continue;
    $r = $reflectorsFound[$idx];
    
    echo "\nConfiguring {$r['file']}...\n";
    $confName = prompt("  Configuration Profile Name", $r['prefix']);
    $title = prompt("  Dashboard Title", "{$r['mode']} Reflector");
    $prefix = prompt("  Log Prefix", $r['prefix']);
    
    $configTemplate = <<<EOD
<?php
/**
 * DVSwitch Universal Reflector Dashboard Configuration
 * Created via Interactive Setup on %s
 */

date_default_timezone_set('UTC');

define("DASHBOARD_TITLE", "%s");
define("DASHBOARD_SUBTITLE", "Real-time %s Monitoring");
define("LOGO", "DVSwitch.png");

define("REFLECTOR_LOG_PREFIX", "%s");
define("REFLECTOR_LOG_PATH", "%s");
define("REFLECTOR_INI_PATH", "%s/");
define("REFLECTOR_INI_FILE", "%s");
define("REFLECTOR_BIN_PATH", "%s/");

define("SHOWQRZ", "1");
define("SHOW_SYSTEM_STATS", "1");
define("SHOW_NETWORK_PULSE", "1");

define("API_REFRESH_INTERVAL", "2000");
define("LAST_HEARD_COUNT", "50");
define("TEMPERATUREHIGHLEVEL", "60");
?>
EOD;

    $configOutput = sprintf(
        $configTemplate,
        date('Y-m-d H:i:s'),
        $title,
        $r['mode'],
        $prefix,
        $logPath,
        $r['dir'],
        $r['file'],
        $r['dir']
    );

    $targetFile = __DIR__ . "/config/{$confName}.php";
    file_put_contents($targetFile, $configOutput);
    echo "  >> Generated config/{$confName}.php\n";
}

echo "\nDone! All selected reflectors have been configured.\n\n";

?>
