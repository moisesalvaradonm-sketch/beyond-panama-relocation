<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Utils, Personalize};

bpr_cors();
Auth::require(['admin','editor']);

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    if (!empty($_GET['id'])) {
        $t = DB::row('SELECT * FROM bpr_email_templates WHERE id = ?', [(int)$_GET['id']]);
        if (!$t) Response::error('Not found', 404);
        Response::ok(['template' => $t]);
    }
    $rows = DB::rows('SELECT id, slug, name, language, status, ai_generated, updated_at
                        FROM bpr_email_templates ORDER BY id DESC LIMIT 200');
    Response::ok(['templates' => $rows]);
}

if ($m === 'POST') {
    $in = bpr_json_input();
    $action = $in['action'] ?? 'save';

    if ($action === 'save') {
        $id = (int)($in['id'] ?? 0);
        $data = [
            'slug'      => Utils::s($in['slug'] ?? '', 120),
            'name'      => Utils::s($in['name'] ?? 'Untitled', 190),
            'language'  => in_array($in['language'] ?? 'en', ['en','es','fr'], true) ? $in['language'] : 'en',
            'subject'   => Utils::s($in['subject'] ?? '', 255),
            'preheader' => Utils::s($in['preheader'] ?? '', 255) ?: null,
            'body_html' => (string)($in['body_html'] ?? ''),
            'body_text' => $in['body_text'] ?? null,
            'status'    => in_array($in['status'] ?? 'draft', ['draft','approved','archived'], true) ? $in['status'] : 'draft',
        ];
        if ($id) {
            $set = [];$vals = [];
            foreach ($data as $k => $v) { $set[] = "$k = ?"; $vals[] = $v; }
            $vals[] = $id;
            DB::exec('UPDATE bpr_email_templates SET ' . implode(',', $set) . ' WHERE id = ?', $vals);
        } else {
            $cols = array_keys($data);
            $id = DB::insert('INSERT INTO bpr_email_templates (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
                array_values($data));
        }
        Response::ok(['id' => $id]);
    }

    if ($action === 'preview') {
        $tplHtml = (string)($in['body_html'] ?? '');
        $subject = (string)($in['subject'] ?? '');
        $contact = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [(int)($in['contact_id'] ?? 0)])
                   ?? ['first_name' => 'Patrick', 'last_name' => 'Smith', 'email' => 'test@example.com', 'language' => 'en'];
        Response::ok([
            'subject' => Personalize::render($subject, $contact),
            'html'    => Personalize::render($tplHtml, $contact),
        ]);
    }

    Response::error('Unknown action', 400);
}

Response::error('Method not allowed', 405);
