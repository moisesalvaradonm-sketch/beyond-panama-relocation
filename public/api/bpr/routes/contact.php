<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{Response, Contact, Activity, Automation, Utils, Brevo};

bpr_cors();
Response::requirePost();

$in = bpr_json_input();
if (!Utils::rateLimit('contact:' . (Utils::ip() ?: 'x'), 30, 3600)) Response::error('Too many requests', 429);

$email = Utils::email($in['email'] ?? '');
if (!$email) Response::error('Valid email required', 400);

// Honeypot
if (!empty($in['website'])) Response::ok(['contact_id' => null]);

try {
    $contact = Contact::upsert([
        'email'             => $email,
        'first_name'        => $in['first_name'] ?? '',
        'last_name'         => $in['last_name']  ?? '',
        'phone'             => $in['phone']      ?? '',
        'country'           => $in['country']    ?? '',
        'current_country'   => $in['current_country'] ?? '',
        'current_city'      => $in['current_city'] ?? '',
        'language'          => $in['language']   ?? 'en',
        'main_interest'     => $in['main_interest'] ?? null,
        'timeline'          => $in['timeline'] ?? null,
        'buying_status'     => $in['buying_status'] ?? null,
        'budget'            => $in['budget'] ?? null,
        'preferred_area'    => $in['preferred_area'] ?? '',
        'property_type'     => $in['property_type'] ?? null,
        'interests'         => $in['interests'] ?? [],
        'locations'         => $in['locations'] ?? [],
        'lead_source'       => $in['lead_source'] ?? 'signup',
        'utm_source'        => $in['utm_source'] ?? '',
        'utm_medium'        => $in['utm_medium'] ?? '',
        'utm_campaign'      => $in['utm_campaign'] ?? '',
        'utm_content'       => $in['utm_content'] ?? '',
        'marketing_consent' => !empty($in['consent']) ? 1 : 0,
        'consent_source'    => $in['consent_source'] ?? 'signup',
    ]);
} catch (\Throwable $e) {
    Response::error('Could not save contact', 400);
}

Activity::log((int)$contact['id'], 'newsletter_signup', ['meta' => ['source' => $contact['lead_source']]]);

// Sync to Brevo
Brevo::upsertContact($email, [
    'FIRSTNAME' => $contact['first_name'] ?? '',
    'LASTNAME'  => $contact['last_name'] ?? '',
    'LANGUAGE'  => $contact['language'] ?? 'en',
]);

// Trigger contact_created + any interest-based automations
$started = Automation::enrollByTrigger((int)$contact['id'], 'contact_created', []);
foreach (($in['interests'] ?? []) as $interest) {
    if (is_string($interest)) {
        array_push($started, ...Automation::enrollByTrigger((int)$contact['id'], 'interest_added', ['interest' => $interest]));
    }
}

Response::ok(['contact_id' => (int)$contact['id'], 'automation' => $started]);
