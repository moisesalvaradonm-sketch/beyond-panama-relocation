<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{Response, Activity, Utils, DB, IntentDetector};

bpr_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') Response::error('Method not allowed', 405);

$in = bpr_json_input();
if (Utils::isBotUA(Utils::userAgent())) Response::ok(['skipped' => 'bot']);
if (!Utils::rateLimit('track:' . (Utils::ip() ?: 'x'), 120, 60)) Response::ok(['skipped' => 'rl']);

$event   = strtolower((string)($in['event'] ?? ''));
$allowed = ['page_view','page_view_residency','page_view_property','page_view_business','page_view_retirement',
            'guide_download','whatsapp_click','consultation_request','form_submit','repeat_visit'];
if (!in_array($event, $allowed, true)) Response::error('Unknown event', 400);

$contactId = null;
if (!empty($in['contact_id'])) {
    $contactId = (int) DB::scalar('SELECT id FROM bpr_contacts WHERE id = ?', [(int)$in['contact_id']]);
} elseif (!empty($in['email'])) {
    $contactId = (int) DB::scalar('SELECT id FROM bpr_contacts WHERE email = ?', [Utils::email($in['email'])]);
}

Activity::log($contactId ?: null, $event, [
    'category' => $in['category'] ?? null,
    'url'      => $in['url'] ?? null,
    'title'    => $in['title'] ?? null,
    'meta'    => $in['meta'] ?? null,
]);

if ($contactId && !empty($in['message'])) {
    IntentDetector::fromText($contactId, (string)$in['message']);
}

Response::ok();
