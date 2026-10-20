<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth};

bpr_cors();
$u = Auth::require();
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (stripos($token, 'Bearer ') === 0) Auth::logout(trim(substr($token, 7)));
Response::ok();
