<?php
/**
 * Apply pending SQL migrations from database/migrations.
 *
 * Usage: php bin/migrate.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = Database::getInstance()->getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `migration` VARCHAR(255) NOT NULL PRIMARY KEY,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$applied = $db->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
$files = glob(BASE_PATH . '/database/migrations/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying {$name}... ";
    $sql = file_get_contents($file);
    // Strip comment lines, then run statement by statement (MySQL DDL is not transactional)
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        $db->exec($statement);
    }

    $stmt = $db->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
    $stmt->execute([$name]);
    echo "done\n";
    $count++;
}

echo $count ? "{$count} migration(s) applied.\n" : "Nothing to migrate.\n";
