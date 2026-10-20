<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, Utils};

bpr_cors();
Response::requirePost();
$in = bpr_json_input();
$token = Auth::login((string)($in['email'] ?? ''), (string)($in['password'] ?? ''));
if (!$token) Response::error('Invalid credentials', 401);
Response::ok(['token' => $token, 'expires_in' => 60*60*24*14]);
