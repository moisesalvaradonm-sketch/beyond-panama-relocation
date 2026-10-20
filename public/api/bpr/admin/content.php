<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Utils};

bpr_cors();
Auth::require(['admin','editor']);

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    if (!empty($_GET['id'])) {
        $c = DB::row('SELECT * FROM bpr_content WHERE id = ?', [(int)$_GET['id']]);
        if (!$c) Response::error('Not found', 404);
        Response::ok(['content' => $c]);
    }
    $status = $_GET['status'] ?? '';
    $where = '1=1'; $params = [];
    if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
    $rows = DB::rows('SELECT id, slug, title, category, language, status, ai_generated, needs_review, updated_at, published_at
                        FROM bpr_content WHERE ' . $where . ' ORDER BY id DESC LIMIT 200', $params);
    Response::ok(['content' => $rows]);
}

if ($m === 'POST') {
    $in = bpr_json_input();
    $action = $in['action'] ?? 'save';
    $id = (int)($in['id'] ?? 0);

    if ($action === 'publish' && $id) {
        DB::exec('UPDATE bpr_content SET status = "published", needs_review = 0, published_at = ? WHERE id = ?',
            [Utils::nowUtc(), $id]);
        Response::ok();
    }
    if ($action === 'archive' && $id) {
        DB::exec('UPDATE bpr_content SET status = "archived" WHERE id = ?', [$id]);
        Response::ok();
    }
    if ($action === 'save') {
        $data = [
            'slug'          => Utils::s($in['slug'] ?? '', 190),
            'title'         => Utils::s($in['title'] ?? '', 255),
            'category'      => Utils::s($in['category'] ?? 'lifestyle', 60),
            'language'      => in_array($in['language'] ?? 'en', ['en','es','fr'], true) ? $in['language'] : 'en',
            'location_tag'  => Utils::s($in['location_tag'] ?? '', 60) ?: null,
            'interest_tags' => json_encode($in['interest_tags'] ?? []),
            'summary'       => Utils::s($in['summary'] ?? '', 500) ?: null,
            'body_md'       => (string)($in['body_md'] ?? ''),
            'meta_title'    => Utils::s($in['meta_title'] ?? '', 255) ?: null,
            'meta_desc'     => Utils::s($in['meta_desc'] ?? '', 500) ?: null,
            'hero_prompt'   => Utils::s($in['hero_prompt'] ?? '', 500) ?: null,
            'status'        => in_array($in['status'] ?? 'draft', ['draft','review','published','archived'], true) ? $in['status'] : 'draft',
        ];
        if ($id) {
            $set = [];$vals = [];
            foreach ($data as $k => $v) { $set[] = "$k = ?"; $vals[] = $v; }
            $vals[] = $id;
            DB::exec('UPDATE bpr_content SET ' . implode(',', $set) . ' WHERE id = ?', $vals);
        } else {
            $cols = array_keys($data);
            $id = DB::insert('INSERT INTO bpr_content (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
                array_values($data));
        }
        Response::ok(['id' => $id]);
    }

    Response::error('Unknown action', 400);
}

Response::error('Method not allowed', 405);
