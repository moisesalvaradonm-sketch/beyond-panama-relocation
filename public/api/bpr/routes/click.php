<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{DB, Utils, Activity};

$data = Utils::unsign($_GET['t'] ?? '');
$target = '/';
if ($data && !empty($data['q']) && !empty($data['u'])) {
    $q = DB::row('SELECT id, contact_id, campaign_id FROM bpr_email_queue WHERE id = ?', [(int)$data['q']]);
    if ($q) {
        $url = (string)$data['u'];
        // Only allow http/https
        if (preg_match('~^https?://~i', $url)) $target = $url;

        DB::insert(
            'INSERT INTO bpr_email_events (queue_id, campaign_id, contact_id, event, url) VALUES (?,?,?, "click", ?)',
            [$q['id'], $q['campaign_id'], $q['contact_id'], $target]
        );
        DB::exec('UPDATE bpr_contacts SET last_click_at = ? WHERE id = ?', [Utils::nowUtc(), $q['contact_id']]);
        if (!empty($q['campaign_id'])) DB::exec('UPDATE bpr_campaigns SET total_clicks = total_clicks + 1 WHERE id = ?', [$q['campaign_id']]);

        Activity::log((int)$q['contact_id'], 'email_click', ['url' => $target, 'meta' => ['queue_id' => $q['id']]]);

        // If the target host matches WhatsApp, also record whatsapp_click
        if (stripos($target, 'wa.me') !== false || stripos($target, 'whatsapp.com') !== false) {
            Activity::log((int)$q['contact_id'], 'whatsapp_click', ['url' => $target]);
        }
    }
}

header('Location: ' . $target, true, 302);
