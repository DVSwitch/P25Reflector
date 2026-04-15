<?php
/**
 * DVSwitch Dashboard ID Database Updater
 * Downloads DMR and NXDN user databases and builds a local SQLite cache.
 */

// Configuration
$dbFile = __DIR__ . "/db/users.db";
$sources = [
    'DMR' => 'https://database.radioid.net/static/user.csv',
    'NXDN' => 'https://database.radioid.net/static/nxdn.csv'
];

// Set execution limits for large files
set_time_limit(300);
ini_set('memory_limit', '256M');

echo "Initializing Database...\n";

try {
    $db = new SQLite3($dbFile);
    $db->exec("DROP TABLE IF EXISTS users"); // Refresh table
    $db->exec("CREATE TABLE users (id INTEGER, callsign TEXT, name TEXT, city TEXT, state TEXT, country TEXT)");
    $db->exec("CREATE INDEX idx_id ON users(id)");
    $db->exec("CREATE INDEX idx_call ON users(callsign)");
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}

foreach ($sources as $type => $url) {
    echo "Processing $type from $url...\n";
    
    $handle = fopen($url, "r");
    if (!$handle) {
        echo "  [!] Error: Could not download $type database.\n";
        continue;
    }

    $db->exec("BEGIN TRANSACTION");
    $stmt = $db->prepare("INSERT INTO users (id, callsign, name, city, state, country) VALUES (:id, :call, :name, :city, :state, :country)");
    
    $count = 0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (!is_numeric($data[0])) continue; // Skip headers

        $stmt->bindValue(':id', (int)$data[0]);
        $stmt->bindValue(':call', trim($data[1]));
        $stmt->bindValue(':name', trim($data[2]));
        $stmt->bindValue(':city', trim($data[3] ?? ''));
        $stmt->bindValue(':state', trim($data[4] ?? ''));
        $stmt->bindValue(':country', trim($data[5] ?? ''));
        $stmt->execute();
        
        $count++;
        if ($count % 5000 === 0) echo "  Processed $count records...\n";
    }
    
    fclose($handle);
    $db->exec("COMMIT");
    echo "  >> Finished $type. Total records: $count\n";
}

echo "\nOptimization...\n";
$db->exec("VACUUM");
echo "Done! The dashboard now has its own fast, local user database.\n";
?>
 Greenland 
