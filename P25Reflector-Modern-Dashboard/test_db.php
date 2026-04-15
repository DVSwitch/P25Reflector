<?php
$db = new SQLite3("db/users.db");
$search = "N4IRS";
$res = $db->query("SELECT * FROM users WHERE callsign = '$search' OR id = '$search' LIMIT 1");
$row = $res->fetchArray(SQLITE3_ASSOC);
if ($row) {
    print_r($row);
} else {
    echo "User $search not found in database.\n";
}
?>
