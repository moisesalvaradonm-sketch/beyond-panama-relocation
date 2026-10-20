<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Mailer, Personalize, Utils};

bpr_cors();
Auth::require(['admin','editor']);

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    $rows = DB::rows('SELECT c.*, s.slug AS segment_slug, t.name AS template_name
                        FROM bpr_campaigns c
                        LEFT JOIN bpr_segments s ON s.id = c.segment_id
                        LEFT JOIN bpr_email_templates t ON t.id = c.template_id
                        ORDER BY c.id DESC LIMIT 100');
    $templates = DB::rows('SELECT id, slug, name, language, status FROM bpr_email_templates ORDER BY id DESC');
    $segments = DB::rows('SELECT id, slug, name FROM bpr_segments WHERE is_active = 1');
    Response::ok(['campaigns' => $rows, 'templates' => $templates, 'segments' => $segments]);
}

if ($m === 'POST') {
    $in = bpr_json_input();
    $action = $in['action'] ?? 'create';

    if ($action === 'create') {
        $id = DB::insert(
            'INSERT INTO bpr_campaigns (name, template_id, segment_id, language, status, scheduled_at)
             VALUES (?,?,?,?,?,?)',
            [
                Utils::s($in['name'] ?? 'Untitled campaign', 190),
                (int)$in['template_id'],
                !empty($in['segment_id']) ? (int)$in['segment_id'] : null,
                $in['language'] ?? 'en',
                $in['scheduled_at'] ? 'scheduled' : 'draft',
                $in['scheduled_at'] ?? null,
            ]
        );
        Response::ok(['id' => $id]);
    }

    if ($action === 'send') {
        $id = (int)($in['id'] ?? 0);
        $c = DB::row('SELECT * FROM bpr_campaigns WHERE id = ?', [$id]);
        if (!$c) Response::error('Campaign not found', 404);
        $t = DB::row('SELECT * FROM bpr_email_templates WHERE id = ?', [$c['template_id']]);
        if (!$t) Response::error('Template missing', 400);

        // Build recipient set
        if ($c['segment_id']) {
            $recipients = DB::rows(
                'SELECT c.* FROM bpr_contacts c
                   JOIN bpr_contact_segments cs ON cs.contact_id = c.id
                  WHERE cs.segment_id = ? AND c.marketing_consent = 1 AND c.unsubscribed_at IS NULL',
                [$c['segment_id']]
            );
        } else {
            $recipients = DB::rows(
                'SELECT * FROM bpr_contacts WHERE marketing_consent = 1 AND unsubscribed_at IS NULL'
            );
        }

        DB::exec('UPDATE bpr_campaigns SET status = "sending" WHERE id = ?', [$id]);
        $queued = 0;
        foreach ($recipients as $rc) {
            $subject = Personalize::render($t['subject'], $rc);
            $html    = Personalize::render($t['body_html'], $rc);
            $text    = $t['body_text'] ? Personalize::render($t['body_text'], $rc) : null;
            $qid = Mailer::queueRaw((int)$rc['id'], $subject, $html, $text, $id);
            if ($qid) $queued++;
        }
        DB::exec('UPDATE bpr_campaigns SET status = "sent", sent_at = ?, total_sent = ? WHERE id = ?',
            [Utils::nowUtc(), $queued, $id]);
        Response::ok(['queued' => $queued]);
    }

    if ($action === 'cancel') {
        DB::exec('UPDATE bpr_campaigns SET status = "cancelled" WHERE id = ? AND status IN ("draft","scheduled")', [(int)$in['id']]);
        Response::ok();
    }

    Response::error('Unknown action', 400);
}

Response::error('Method not allowed', 405);
