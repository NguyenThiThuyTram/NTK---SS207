<?php
require_once 'src/config/database.php';

$sqls = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS current_points INT(11) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS accumulated_points INT(11) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS tier VARCHAR(20) DEFAULT 'Member'",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) DEFAULT 0",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS reward_coupon_id CHAR(5) DEFAULT NULL",
];

foreach ($sqls as $sql) {
    try {
        $conn->exec($sql);
        echo "[OK] $sql\n";
    } catch (PDOException $e) {
        echo "[ERR] $sql : " . $e->getMessage() . "\n";
    }
}
echo "Migration done.\n";
?>
