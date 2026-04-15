<?php
/**
 * DVSwitch Dashboard Interactive Setup
 * Usage: php setup.php [path/to/Reflector.ini]
 */

echo "\n--- DVSwitch Universal Dashboard Setup ---\n\n";

// Extraction Helper
function prompt($label, $default)
{
    echo "$label [$default]: ";
    $input = trim(fgets(STDIN));
    return $input ?: $default;
}

// 1. Scan for Reflectors
$path = $argv[1] ?? "./";
$reflectorsFound = [];

if (is_dir($path)) {
    echo "Scanning '$path' for reflectors...\n";
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $file) {
        if ($file->getExtension() === 'ini') {
            $content = file_get_contents($file->getPathname());

            // The Reflector Signature (Flexible): Has [Log], FileRoot, a Mode Section, NO [Modem]
            $hasLog = (preg_match('/\[Log\]/i', $content) && preg_match('/FileRoot\s*=/i', $content));
            $hasMode = (preg_match('/\[(P25|YSF|NXDN|DMR|General)\]/i', $content));
            $isNotNode = (strpos($content, '[Modem]') === false);

            if ($hasLog && $hasMode && $isNotNode) {
                // Initialize explicitly
                $foundMode = 'Generic';
                $contentUpper = strtoupper($content);
                $fileUpper = strtoupper($file->getFilename());
                $dirUpper = strtoupper($file->getPath());

                // Priority 1: Binary Neighbor (Highest Confidence)
                $binPath = $file->getPath();
                if (file_exists("$binPath/P25Reflector")) $foundMode = 'P25';
                elseif (file_exists("$binPath/YSFReflector")) $foundMode = 'YSF';
                elseif (file_exists("$binPath/NXDNReflector")) $foundMode = 'NXDN';
                elseif (file_exists("$binPath/DMRReflector")) $foundMode = 'DMR';

                // Priority 2: Check Content for Section Headers (If binary missing)
                if ($foundMode === 'Generic') {
                    if (stripos($content, '[P25') !== false) $foundMode = 'P25';
                    elseif (stripos($content, '[YSF') !== false) $foundMode = 'YSF';
                    elseif (stripos($content, '[NXDN') !== false) $foundMode = 'NXDN';
                    elseif (stripos($content, '[DMR') !== false) $foundMode = 'DMR';
                }

                // Priority 3: Check Filename Fallback
                if ($foundMode === 'Generic') {
                    if (strpos($fileUpper, 'P25') !== false) $foundMode = 'P25';
                    elseif (strpos($fileUpper, 'YSF') !== false) $foundMode = 'YSF';
                    elseif (strpos($fileUpper, 'NXDN') !== false) $foundMode = 'NXDN';
                    elseif (strpos($fileUpper, 'DMR') !== false) $foundMode = 'DMR';
                }

                // Priority 4: Final Keywords and Directory Fallback
                if ($foundMode === 'Generic') {
                    if (stripos($content, 'P25Id') !== false) $foundMode = 'P25';
                    elseif (stripos($content, 'YSFId') !== false) $foundMode = 'YSF';
                    elseif (stripos($content, 'NXDNId') !== false) $foundMode = 'NXDN';
                    elseif (stripos($content, 'DMRId') !== false) $foundMode = 'DMR';
                    elseif (strpos($dirUpper, 'P25') !== false) $foundMode = 'P25';
                    elseif (strpos($dirUpper, 'YSF') !== false) $foundMode = 'YSF';
                    elseif (strpos($dirUpper, 'NXDN') !== false) $foundMode = 'NXDN';
                    elseif (strpos($dirUpper, 'DMR') !== false) $foundMode = 'DMR';
                }

                // Extract actual prefix from FileRoot
                $prefix = basename($file->getFilename(), '.ini');
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    if (preg_match('/^\s*FileRoot\s*=\s*(.*)/i', $line, $pm)) {
                        $prefix = trim($pm[1]);
                        break;
                    }
                }

                $reflectorsFound[] = [
                    'path' => $file->getRealPath(),
                    'dir' => $file->getPath(),
                    'file' => $file->getFilename(),
                    'prefix' => $prefix,
                    'mode' => $foundMode
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
    foreach (explode(',', $selection) as $idx) {
        $v = (int) trim($idx);
        if ($v > 0)
            $selectedIndices[] = $v - 1;
    }
}

$logPathDefault = "/var/log/mmdvm";
$logPath = prompt("\nBase directory for logs", $logPathDefault);

foreach ($selectedIndices as $idx) {
    if (!isset($reflectorsFound[$idx]))
        continue;
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