<?php
header('Content-Type: text/plain');
echo "PHP Version: " . phpversion() . "\n";
echo "SQLite3 Support: " . (class_exists('SQLite3') ? 'ENABLED' : 'DISABLED') . "\n";
echo "DB File Exists: " . (file_exists('db/users.db') ? 'YES' : 'NO') . "\n";
if (file_exists('db/users.db')) {
    echo "DB File Readable: " . (is_readable('db/users.db') ? 'YES' : 'NO') . "\n";
}
?>
