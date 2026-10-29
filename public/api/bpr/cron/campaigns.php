<?php
/**
 * Dispatch due campaigns (one-off scheduled + recurring newsletters).
 * Queues the emails; cron/email_queue.php delivers them. Run every 5-15 min:
 *   /api/bpr/cron/campaigns.php?token=YOUR_CRON_TOKEN
 */
require __DIR__ . '/../bootstrap.php';
use BPR\{Auth, DB, Campaign, Response, Utils};

Auth::requireCronToken();

$due = DB::rows('SELECT * FROM bpr_campaigns WHERE status = "scheduled" AND scheduled_at <= UTC_TIMESTAMP() ORDER BY scheduled_at ASC LIMIT 10');

$dispatched = [];
foreach ($due as $c) {
    $recurring = ($c['recurrence'] ?? 'none') !== 'none';

    // Claim atomically so overlapping cron runs can't double-send.
    if ($recurring) {
        $next = Campaign::nextRun($c['recurrence'], (int)$c['recur_day'], (string)$c['recur_time']);
        $claimed = DB::exec('UPDATE bpr_campaigns SET scheduled_at = ? WHERE id = ? AND status = "scheduled" AND scheduled_at = ?',
            [$next, $c['id'], $c['scheduled_at']]);
        $c['scheduled_at'] = $next;
    } else {
        $claimed = DB::exec('UPDATE bpr_campaigns SET status = "sending" WHERE id = ? AND status = "scheduled"', [$c['id']]);
    }
    if (!$claimed) continue;

    $dispatched[$c['id']] = Campaign::dispatch($c, false);
}

Response::ok(['due' => count($due), 'dispatched' => $dispatched]);
