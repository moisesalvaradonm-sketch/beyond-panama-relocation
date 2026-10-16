<?php
/**
 * Process pending emails from bpr_email_queue. Called by Hostinger cron every 5 min.
 *   /api/bpr/cron/email_queue.php?token=YOUR_CRON_TOKEN
 */
require __DIR__ . '/../bootstrap.php';
use BPR\{Auth, DB, Mailer, Response};

Auth::requireCronToken();

$limit = min(100, (int)($_GET['limit'] ?? 40));
$rows = DB::rows(
    'SELECT id FROM bpr_email_queue
     WHERE status = "pending" AND scheduled_at <= UTC_TIMESTAMP()
     ORDER BY scheduled_at ASC LIMIT ' . (int)$limit
);

$sent = 0; $failed = 0;
foreach ($rows as $r) {
    if (Mailer::deliver((int)$r['id'])) $sent++;
    else $failed++;
    // small pause to be nice to Brevo
    usleep(120000);
}

Response::ok(['processed' => count($rows), 'sent' => $sent, 'failed' => $failed]);
