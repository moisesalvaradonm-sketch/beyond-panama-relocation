<?php
/**
 * Detect stale leads (no activity in 90 days) with consent and re-enrol them
 * in the "reactivation" automation. Runs daily.
 */
require __DIR__ . '/../bootstrap.php';
use BPR\{Auth, DB, Automation, Response};

Auth::requireCronToken();

$days = (int)($_GET['days'] ?? 90);
$rows = DB::rows(
    "SELECT id FROM bpr_contacts
     WHERE marketing_consent = 1
       AND unsubscribed_at IS NULL
       AND (last_activity_at IS NULL OR last_activity_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL $days DAY))
       AND created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
     LIMIT 500"
);

$auto = DB::row('SELECT id FROM bpr_automations WHERE slug = "reactivation" AND is_active = 1');
if (!$auto) Response::ok(['skipped' => 'no automation']);

$enrolled = 0;
foreach ($rows as $r) {
    // Skip if already enrolled once
    $has = DB::scalar('SELECT 1 FROM bpr_automation_enrollments WHERE automation_id = ? AND contact_id = ?',
        [$auto['id'], $r['id']]);
    if ($has) continue;
    if (Automation::enroll((int)$auto['id'], (int)$r['id'], ['stale_days' => $days])) $enrolled++;
}
Response::ok(['candidates' => count($rows), 'enrolled' => $enrolled]);
