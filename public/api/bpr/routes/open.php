<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{DB, Utils, Activity};

$data = Utils::unsign($_GET['t'] ?? '');
if ($data && !empty($data['q'])) {
    $q = DB::row('SELECT id, contact_id, campaign_id FROM bpr_email_queue WHERE id = ?', [(int)$data['q']]);
    if ($q) {
        DB::insert('INSERT INTO bpr_email_events (queue_id, campaign_id, contact_id, event) VALUES (?,?,?, "open")',
            [$q['id'], $q['campaign_id'], $q['contact_id']]);
        DB::exec('UPDATE bpr_contacts SET last_email_opened_at = ? WHERE id = ?', [Utils::nowUtc(), $q['contact_id']]);
        if (!empty($q['campaign_id'])) DB::exec('UPDATE bpr_campaigns SET total_opens = total_opens + 1 WHERE id = ?', [$q['campaign_id']]);
        Activity::log((int)$q['contact_id'], 'email_open', ['meta' => ['queue_id' => $q['id']]]);
    }
}

// 1×1 transparent gif
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
