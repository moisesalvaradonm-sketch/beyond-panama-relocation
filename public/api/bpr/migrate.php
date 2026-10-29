<?php
/**
 * Idempotent migration for already-installed databases: adds recurring-campaign columns.
 *   /api/bpr/migrate.php?token=YOUR_CRON_TOKEN
 * Safe to run more than once. Fresh installs already get these from schema.sql.
 */
require __DIR__ . '/bootstrap.php';
use BPR\{Auth, DB, Response};

Auth::requireCronToken();

$cols = [
    'recurrence' => "ADD COLUMN recurrence ENUM('none','weekly','monthly') NOT NULL DEFAULT 'none' AFTER language",
    'recur_day'  => "ADD COLUMN recur_day TINYINT UNSIGNED NULL AFTER recurrence",
    'recur_time' => "ADD COLUMN recur_time TIME NULL AFTER recur_day",
];
$done = [];
foreach ($cols as $name => $ddl) {
    $exists = DB::scalar(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "bpr_campaigns" AND COLUMN_NAME = ?',
        [$name]
    );
    if (!$exists) { DB::exec("ALTER TABLE bpr_campaigns $ddl"); $done[] = $name; }
}
Response::ok(['added' => $done]);
