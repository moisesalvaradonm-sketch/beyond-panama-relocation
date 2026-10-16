<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB};

bpr_cors();
Auth::require(['admin','editor']);

$m = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($m === 'GET') {
    $rows = DB::rows('SELECT * FROM bpr_automations ORDER BY id DESC');
    foreach ($rows as &$a) {
        $a['steps'] = DB::rows('SELECT id, position, step_type, config_json FROM bpr_automation_steps WHERE automation_id = ? ORDER BY position', [$a['id']]);
        $a['active_enrollments'] = (int) DB::scalar('SELECT COUNT(*) FROM bpr_automation_enrollments WHERE automation_id = ? AND status = "active"', [$a['id']]);
    }
    Response::ok(['automations' => $rows]);
}

if ($m === 'POST') {
    $in = bpr_json_input();
    $action = $in['action'] ?? 'toggle';

    if ($action === 'toggle') {
        DB::exec('UPDATE bpr_automations SET is_active = ? WHERE id = ?', [(int)!!$in['is_active'], (int)$in['id']]);
        Response::ok();
    }

    if ($action === 'set_steps') {
        $id = (int)$in['id'];
        DB::exec('DELETE FROM bpr_automation_steps WHERE automation_id = ?', [$id]);
        foreach ($in['steps'] ?? [] as $step) {
            DB::exec('INSERT INTO bpr_automation_steps (automation_id, position, step_type, config_json) VALUES (?,?,?,?)',
                [$id, (int)$step['position'], $step['step_type'], json_encode($step['config'] ?? [])]);
        }
        Response::ok();
    }

    Response::error('Unknown action', 400);
}

Response::error('Method not allowed', 405);
