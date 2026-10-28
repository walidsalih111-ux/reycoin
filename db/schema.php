<?php
// db/schema.php - Upgrades the database schema in place, one version at a time.
// config.php calls this on every request; once the database is current it costs a single SELECT.
// When you add a migration, update db/recycoin_db.sql (used for fresh installs) to match.

function ensure_schema($pdo) {
    // Statements use IF [NOT] EXISTS so an interrupted upgrade can safely run again
    $migrations = [
        1 => [
            // Previously run on every request by api.php
            "CREATE TABLE IF NOT EXISTS system_lock (id INT PRIMARY KEY, user_qr VARCHAR(50) DEFAULT NULL, expires_at INT DEFAULT 0)",
            "INSERT IGNORE INTO system_lock (id, user_qr, expires_at) VALUES (1, NULL, 0)",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        ],
        2 => [
            // Residents are identified by a random per-device token (stored as a SHA-256 hash)
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS device_token CHAR(64) DEFAULT NULL",
            "ALTER TABLE users ADD UNIQUE INDEX IF NOT EXISTS device_token (device_token)",
            // The hotspot reassigns IP addresses, so they can no longer be unique
            "ALTER TABLE users DROP INDEX IF EXISTS ip_address",
            "ALTER TABLE users MODIFY ip_address VARCHAR(45) DEFAULT NULL",
            "ALTER TABLE users ADD INDEX IF NOT EXISTS ip_address (ip_address)",
            // One deposit row per verified bottle; event_id ensures each bottle is credited once
            "ALTER TABLE deposits ADD COLUMN IF NOT EXISTS event_id CHAR(32) DEFAULT NULL",
            "ALTER TABLE deposits ADD UNIQUE INDEX IF NOT EXISTS event_id (event_id)",
            // When the current scanning session started (for the resident's running total)
            "ALTER TABLE system_lock ADD COLUMN IF NOT EXISTS started_at INT NOT NULL DEFAULT 0",
        ],
    ];

    try {
        $current = (int)$pdo->query("SELECT MAX(version) FROM schema_migrations")->fetchColumn();
    } catch (PDOException $e) {
        $current = 0; // The table doesn't exist yet
    }
    if ($current >= max(array_keys($migrations))) return;

    // Only one request runs the upgrade; any others wait here and then see the new version
    $pdo->query("SELECT GET_LOCK('recycoin_schema', 30)")->fetchColumn();
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version INT PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $current = (int)$pdo->query("SELECT MAX(version) FROM schema_migrations")->fetchColumn();
        foreach ($migrations as $version => $statements) {
            if ($version <= $current) continue;
            foreach ($statements as $sql) {
                $pdo->exec($sql);
            }
            $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)")->execute([$version]);
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('recycoin_schema')")->fetchColumn();
    }
}
