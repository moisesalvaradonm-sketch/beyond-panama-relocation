<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Utils};

bpr_cors();
Auth::require(['admin']);
$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    Response::ok(['knowledge' => DB::rows('SELECT * FROM bpr_knowledge ORDER BY topic, key_name')]);
}
if ($m === 'POST') {
    $in = bpr_json_input();
    $action = $in['action'] ?? 'save';
    if ($action === 'save') {
        DB::exec(
            'INSERT INTO bpr_knowledge (topic, key_name, value_text, source, verified_at, confidence, language)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), source = VALUES(source),
               verified_at = VALUES(verified_at), confidence = VALUES(confidence)',
            [
                Utils::s($in['topic'] ?? '', 120),
                Utils::s($in['key_name'] ?? '', 190),
                (string)($in['value_text'] ?? ''),
                Utils::s($in['source'] ?? '', 255) ?: null,
                $in['verified_at'] ?? date('Y-m-d'),
                in_array($in['confidence'] ?? 'medium', ['low','medium','high'], true) ? $in['confidence'] : 'medium',
                in_array($in['language'] ?? 'any', ['en','es','fr','any'], true) ? $in['language'] : 'any',
            ]
        );
        Response::ok();
    }
    if ($action === 'delete') {
        DB::exec('DELETE FROM bpr_knowledge WHERE id = ?', [(int)$in['id']]);
        Response::ok();
    }
    Response::error('Unknown action', 400);
}
Response::error('Method not allowed', 405);
