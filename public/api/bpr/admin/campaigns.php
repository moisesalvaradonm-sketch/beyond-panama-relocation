<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Campaign, Utils};

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
        $rec = in_array($in['recurrence'] ?? 'none', ['weekly', 'monthly'], true) ? $in['recurrence'] : 'none';
        $day = (int)($in['recur_day'] ?? 1);
        $time = preg_match('/^\d{1,2}:\d{2}$/', (string)($in['recur_time'] ?? '')) ? $in['recur_time'] : '09:00';
        if ($rec !== 'none') {
            $scheduled = Campaign::nextRun($rec, $day, $time);
            $status = 'scheduled';
        } else {
            $scheduled = $in['scheduled_at'] ?? null;
            $status = $scheduled ? 'scheduled' : 'draft';
        }
        $id = DB::insert(
            'INSERT INTO bpr_campaigns (name, template_id, segment_id, language, recurrence, recur_day, recur_time, status, scheduled_at)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                Utils::s($in['name'] ?? 'Untitled campaign', 190),
                (int)$in['template_id'],
                !empty($in['segment_id']) ? (int)$in['segment_id'] : null,
                $in['language'] ?? 'en',
                $rec,
                $rec !== 'none' ? $day : null,
                $rec !== 'none' ? $time : null,
                $status,
                $scheduled,
            ]
        );
        Response::ok(['id' => $id, 'next_run' => $scheduled]);
    }

    if ($action === 'send') {
        $id = (int)($in['id'] ?? 0);
        $c = DB::row('SELECT * FROM bpr_campaigns WHERE id = ?', [$id]);
        if (!$c) Response::error('Campaign not found', 404);
        if (!DB::row('SELECT id FROM bpr_email_templates WHERE id = ?', [$c['template_id']])) Response::error('Template missing', 400);
        // Recurring campaigns keep their schedule; one-off ones are marked sent.
        $queued = Campaign::dispatch($c, false);
        Response::ok(['queued' => $queued]);
    }

    if ($action === 'cancel') {
        DB::exec('UPDATE bpr_campaigns SET status = "cancelled" WHERE id = ? AND status IN ("draft","scheduled")', [(int)$in['id']]);
        Response::ok();
    }

    // Resume a paused (cancelled) recurring campaign from its next occurrence.
    if ($action === 'resume') {
        $c = DB::row('SELECT * FROM bpr_campaigns WHERE id = ? AND status = "cancelled" AND recurrence <> "none"', [(int)($in['id'] ?? 0)]);
        if (!$c) Response::error('Not a paused recurring campaign', 400);
        $next = Campaign::nextRun($c['recurrence'], (int)$c['recur_day'], (string)$c['recur_time']);
        DB::exec('UPDATE bpr_campaigns SET status = "scheduled", scheduled_at = ? WHERE id = ?', [$next, $c['id']]);
        Response::ok(['next_run' => $next]);
    }

    Response::error('Unknown action', 400);
}

Response::error('Method not allowed', 405);
