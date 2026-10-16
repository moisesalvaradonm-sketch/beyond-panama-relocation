<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{Response, Contact, Quiz, Activity, Mailer, Automation, Utils, Brevo};

bpr_cors();
Response::requirePost();

$in = bpr_json_input();
if (!Utils::rateLimit('quiz:' . (Utils::ip() ?: 'x'), 20, 3600)) Response::error('Too many requests', 429);

$email = Utils::email($in['email'] ?? '');
if (!$email) Response::error('Valid email required', 400);

try {
    $contact = Contact::upsert([
        'email'             => $email,
        'first_name'        => $in['first_name'] ?? '',
        'last_name'         => $in['last_name']  ?? '',
        'phone'             => $in['phone']      ?? '',
        'country'           => $in['country']    ?? '',
        'current_country'   => $in['current_country'] ?? '',
        'language'          => $in['language']   ?? 'en',
        'lead_source'       => $in['lead_source'] ?? 'quiz',
        'utm_source'        => $in['utm_source'] ?? '',
        'utm_medium'        => $in['utm_medium'] ?? '',
        'utm_campaign'      => $in['utm_campaign'] ?? '',
        'utm_content'       => $in['utm_content'] ?? '',
        'marketing_consent' => !empty($in['consent']) ? 1 : 0,
        'consent_source'    => 'quiz',
    ]);
} catch (\Throwable $e) {
    Response::error('Could not save contact', 400);
}

$answers = is_array($in['answers'] ?? null) ? $in['answers'] : [];
$result  = Quiz::scoreAndStore((int)$contact['id'], $answers);

// Log activities
Activity::log((int)$contact['id'], 'quiz_completed', [
    'meta' => ['result' => $result['key'], 'match' => $result['match']],
]);
Activity::log((int)$contact['id'], 'quiz_result', [
    'category' => $result['key'],
]);

// Push contact to Brevo (so their audience is in sync)
Brevo::upsertContact($email, [
    'FIRSTNAME'   => $contact['first_name'] ?? '',
    'LASTNAME'    => $contact['last_name'] ?? '',
    'QUIZ_RESULT' => $result['label'],
    'LANGUAGE'    => $contact['language'] ?? 'en',
]);

// Trigger the quiz nurture automation (sends first email immediately)
$started = Automation::enrollByTrigger((int)$contact['id'], 'quiz_completed', [
    'result'   => $result['key'],
    'answers'  => $answers,
]);

Response::ok([
    'contact_id' => (int)$contact['id'],
    'result'     => $result,
    'automation' => $started,
]);
