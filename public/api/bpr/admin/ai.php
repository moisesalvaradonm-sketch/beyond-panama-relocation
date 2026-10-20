<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB, AI, Utils};

bpr_cors();
Auth::require(['admin','editor']);
$in = bpr_json_input();
$action = $in['action'] ?? '';

if ($action === 'generate_email') {
    $contactId = (int)($in['contact_id'] ?? 0);
    $goal = (string)($in['goal'] ?? 'nurture');
    if (!$contactId) Response::error('contact_id required', 400);
    $res = AI::generateEmailFor($contactId, $goal);
    if (!$res['ok']) Response::error($res['error'] ?? 'AI failed', 502, ['raw' => $res['raw'] ?? null]);
    Response::ok(['email' => $res]);
}

if ($action === 'save_generated_template') {
    $slug = 'ai-' . substr(hash('sha1', microtime(true) . rand()), 0, 8);
    $id = DB::insert(
        'INSERT INTO bpr_email_templates (slug, name, language, subject, preheader, body_html, ai_generated, status)
         VALUES (?,?,?,?,?,?,1,"draft")',
        [
            $slug,
            Utils::s($in['name'] ?? 'AI Draft', 190),
            in_array($in['language'] ?? 'en', ['en','es','fr'], true) ? $in['language'] : 'en',
            Utils::s($in['subject'] ?? '', 255),
            Utils::s($in['preheader'] ?? '', 255) ?: null,
            (string)($in['html'] ?? ''),
        ]
    );
    Response::ok(['id' => $id, 'slug' => $slug]);
}

if ($action === 'generate_article') {
    $topic = trim((string)($in['topic'] ?? ''));
    $lang  = $in['language'] ?? 'en';
    if (!$topic) Response::error('topic required', 400);
    $res = AI::generateArticle($topic, $lang);
    if (!$res['ok']) Response::error($res['error'] ?? 'AI failed', 502);
    Response::ok(['article' => $res]);
}

if ($action === 'test') {
    $res = AI::complete(AI::contentSystemPrompt(), 'Say "AI engine online" and nothing else.', ['max_tokens' => 30, 'temperature' => 0]);
    Response::ok(['result' => $res]);
}

Response::error('Unknown action', 400);
