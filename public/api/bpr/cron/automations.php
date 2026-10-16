<?php
/**
 * Advance active automation enrollments whose next_run_at has passed.
 * Recommended cron: every 5 minutes.
 */
require __DIR__ . '/../bootstrap.php';
use BPR\{Auth, DB, Automation, Response};

Auth::requireCronToken();

$rows = DB::rows(
    'SELECT id FROM bpr_automation_enrollments
     WHERE status = "active" AND next_run_at <= UTC_TIMESTAMP()
     ORDER BY next_run_at ASC LIMIT 200'
);

$advanced = 0;
foreach ($rows as $r) {
    if (Automation::advance((int)$r['id'])) $advanced++;
}

Response::ok(['checked' => count($rows), 'advanced' => $advanced]);
