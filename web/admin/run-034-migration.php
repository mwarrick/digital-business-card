<?php
require_once __DIR__ . '/../api/includes/Database.php';

echo "Running migration 034...\n";
$sql = file_get_contents(__DIR__ . '/../config/migrations/034_add_os_city_country.sql');
$db = Database::getInstance();
$db->getConnection()->exec($sql);
echo "Migration 034 applied.\n";
