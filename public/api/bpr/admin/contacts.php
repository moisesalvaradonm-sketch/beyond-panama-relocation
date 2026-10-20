<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Contact};

bpr_cors();
Auth::require();

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    // /contacts.php?id=123    → profile
    if (!empty($_GET['id'])) {
        $p = Contact::profile((int)$_GET['id']);
        if (!$p) Response::error('Not found', 404);
        Response::ok(['contact' => $p]);
    }
    // Listing with filters
    $q      = trim((string)($_GET['q'] ?? ''));
    $status = $_GET['status']  ?? '';
    $segment= $_GET['segment'] ?? '';
    $intent = $_GET['intent']  ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $pp     = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
    $offset = ($page - 1) * $pp;

    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
        $like = '%' . str_replace(['%','_'], ['\%','\_'], $q) . '%';
        array_push($params, $like, $like, $like);
    }
    if ($status)  { $where[] = 'lead_status = ?';   $params[] = $status; }
    if ($intent)  { $where[] = 'intent_level = ?';  $params[] = $intent; }
    if ($segment) {
        $where[] = 'id IN (SELECT contact_id FROM bpr_contact_segments cs JOIN bpr_segments s ON s.id = cs.segment_id WHERE s.slug = ?)';
        $params[] = $segment;
    }
    $sql = 'SELECT id, email, first_name, last_name, main_interest, timeline, lead_score, lead_status, intent_level,
                   marketing_consent, last_activity_at, created_at
              FROM bpr_contacts WHERE ' . implode(' AND ', $where) .
              ' ORDER BY id DESC LIMIT ' . (int)$pp . ' OFFSET ' . (int)$offset;
    $rows = DB::rows($sql, $params);
    $total = (int) DB::scalar('SELECT COUNT(*) FROM bpr_contacts WHERE ' . implode(' AND ', $where), $params);
    Response::ok(['contacts' => $rows, 'page' => $page, 'per_page' => $pp, 'total' => $total]);
}

if ($m === 'POST') {
    Auth::require(['admin','editor','sales']);
    $in = bpr_json_input();
    $id = (int)($in['id'] ?? 0);
    if (!$id) Response::error('id required', 400);
    $allowed = ['first_name','last_name','phone','country','current_country','current_city','language',
                'main_interest','timeline','buying_status','budget','preferred_area','property_type',
                'marketing_consent','notes','intent_level'];
    $set = []; $params = [];
    foreach ($allowed as $f) if (array_key_exists($f, $in)) { $set[] = "$f = ?"; $params[] = $in[$f]; }
    if (!$set) Response::error('nothing to update', 400);
    $params[] = $id;
    DB::exec('UPDATE bpr_contacts SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
    Response::ok();
}

Response::error('Method not allowed', 405);
