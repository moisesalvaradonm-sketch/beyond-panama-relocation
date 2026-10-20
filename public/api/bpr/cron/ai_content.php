<?php
/**
 * AI Content Engine — generates one article per run based on a rotating topic pool.
 * Runs weekly (e.g. Mon 9am). Set feature flag `ai_content_engine`.
 *
 * Auto-publish behaviour depends on features.auto_publish_ai:
 *   false → status = 'review' (needs human OK)
 *   true  → status = 'published'
 */
require __DIR__ . '/../bootstrap.php';
use BPR\{Auth, DB, AI, Utils, Response};

Auth::requireCronToken();

$cfg = bpr_config();
if (empty($cfg['features']['ai_content_engine'])) Response::ok(['skipped' => 'disabled']);

// Rotating topic pool. Extend from admin panel later.
$topics = [
    'Cost of living in Panama City vs Boquete: real monthly numbers for expats',
    'Panama Pensionado visa: who qualifies and what it actually gets you',
    'How Americans buy property in Panama step by step',
    'What healthcare in Panama really looks like as an expat',
    'Boquete vs Coronado: which one fits which lifestyle',
    'Starting a business in Panama as a foreigner: the practical version',
    'Panama Friendly Nations visa: what changed and what still works',
    'Planning your first Panama scouting trip: a 7-day framework',
    'Banking in Panama for new residents: what to prepare',
    'Retiring in Panama: neighbourhoods worth serious consideration',
];

// Choose a topic not used in the last N generated articles
$recent = array_column(DB::rows(
    'SELECT title FROM bpr_content WHERE ai_generated = 1 ORDER BY id DESC LIMIT 20'
), 'title');
$pool = array_values(array_filter($topics, fn($t) => !in_array($t, $recent, true)));
if (!$pool) $pool = $topics;
$topic = $pool[array_rand($pool)];

$res = AI::generateArticle($topic, 'en');
if (!$res['ok']) Response::json(['ok' => false, 'error' => $res['error'] ?? 'AI failed'], 502);

$autoPub = !empty($cfg['features']['auto_publish_ai']);
$slug = preg_replace('~[^a-z0-9]+~', '-', strtolower($res['slug'] ?? $res['title']));
$slug = trim($slug, '-') . '-' . substr(hash('sha1', $res['title'] . microtime(true)), 0, 6);

$id = DB::insert(
    'INSERT INTO bpr_content
       (slug, title, category, language, location_tag, interest_tags, summary, body_md,
        meta_title, meta_desc, hero_prompt, status, ai_generated, needs_review, published_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
    [
        $slug,
        Utils::s($res['title'], 255),
        Utils::s($res['category'] ?? 'lifestyle', 60),
        'en',
        Utils::s($res['location_tag'] ?? '', 60) ?: null,
        json_encode($res['interest_tags'] ?? []),
        Utils::s($res['summary'] ?? '', 500),
        (string)$res['body_md'],
        Utils::s($res['meta_title'] ?? $res['title'], 255),
        Utils::s($res['meta_desc'] ?? '', 500),
        Utils::s($res['hero_prompt'] ?? '', 500),
        $autoPub ? 'published' : 'review',
        1, $autoPub ? 0 : 1,
        $autoPub ? Utils::nowUtc() : null,
    ]
);

Response::ok(['content_id' => $id, 'slug' => $slug, 'status' => $autoPub ? 'published' : 'review']);
