<?php
include "config/config.php";
include "api.php"; // This will include the functions

$count = 50;
$heardList = [];
$logs = glob(P25REFLECTORLOGPATH . "/" . P25REFLECTORLOGPREFIX . "-*.log");
echo "Found ".count($logs)." logs\n";
if (!empty($logs)) {
    usort($logs, function($a, $b) { return filemtime($b) - filemtime($a); });
    foreach ($logs as $logPath) {
        echo "Searching $logPath\n";
        $file = new SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        echo "Total lines: $totalLines\n";
        
        $chunkSize = 1000;
        for ($pos = $totalLines; $pos >= 0; $pos -= $chunkSize) {
            $start = max(0, $pos - $chunkSize);
            $file->seek($start);
            $lines = [];
            for ($i = 0; $i < $chunkSize && !$file->eof(); $i++) {
                $lines[] = $file->fgets();
            }
            
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = $lines[$i];
                if (strpos($line, "Transmission from") !== false) {
                    echo "Found line: $line";
                    preg_match('/M: ([\d-]+ [\d:.]+) Transmission from (\S+) at (\S+) to (.*)/', $line, $matches);
                    if ($matches) {
                        echo "  Match: " . $matches[2] . "\n";
                    } else {
                        echo "  NO MATCH FOR REGEX\n";
                    }
                }
            }
        }
    }
}
?>
