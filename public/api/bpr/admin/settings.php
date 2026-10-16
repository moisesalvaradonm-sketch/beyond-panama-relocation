<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, Utils};

bpr_cors();
Auth::require(['admin']);

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    $rows = DB::rows('SELECT name, value FROM bpr_settings');
    $out = [];
    foreach ($rows as $r) $out[$r['name']] = $r['value'];
    $rules = DB::rows('SELECT event, delta FROM bpr_score_rules ORDER BY event');
    Response::ok(['settings' => $out, 'score_rules' => $rules]);
}

if ($m === 'POST') {
    $in = bpr_json_input();
    if (!empty($in['settings']) && is_array($in['settings'])) {
        foreach ($in['settings'] as $k => $v) {
            $k = Utils::s($k, 80);
            DB::exec('INSERT INTO bpr_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
                [$k, (string)$v]);
        }
    }
    if (!empty($in['score_rules']) && is_array($in['score_rules'])) {
        foreach ($in['score_rules'] as $r) {
            $event = Utils::s($r['event'] ?? '', 60);
            if (!$event) continue;
            $delta = (int)($r['delta'] ?? 0);
            DB::exec('INSERT INTO bpr_score_rules (event, delta) VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE delta = VALUES(delta)', [$event, $delta]);
        }
    }
    Response::ok();
}

Response::error('Method not allowed', 405);
