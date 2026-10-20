<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{Response, Contact, Activity, Automation, IntentDetector, DB, Utils, Brevo};

bpr_cors();
Response::requirePost();

$in = bpr_json_input();
if (!empty($in['website'])) Response::ok([]); // honeypot
if (!Utils::rateLimit('form:' . (Utils::ip() ?: 'x'), 20, 3600)) Response::error('Too many requests', 429);

$formKey = strtolower(preg_replace('/[^a-z0-9_]/', '', (string)($in['form_key'] ?? 'contact')));
$email   = Utils::email($in['email'] ?? '');
if (!$email) Response::error('Valid email required', 400);

// Map form_key → lead_source, main_interest, event
$profile = [
    'property_inquiry' => ['source' => 'property_form', 'interest' => 'property',   'event' => 'form_submit'],
    'residency_form'   => ['source' => 'residency_form','interest' => 'residency',  'event' => 'form_submit'],
    'consultation'     => ['source' => 'consultation',  'interest' => 'relocation', 'event' => 'consultation_request'],
    'guide_download'   => ['source' => 'guide',         'interest' => null,         'event' => 'guide_download'],
    'webinar'          => ['source' => 'webinar',       'interest' => 'relocation', 'event' => 'webinar_registration'],
    'contact'          => ['source' => 'contact_form',  'interest' => null,         'event' => 'form_submit'],
];
$meta = $profile[$formKey] ?? $profile['contact'];

$contact = Contact::upsert([
    'email'             => $email,
    'first_name'        => $in['first_name'] ?? $in['name'] ?? '',
    'last_name'         => $in['last_name'] ?? '',
    'phone'             => $in['phone'] ?? $in['whatsapp'] ?? '',
    'language'          => $in['language'] ?? 'en',
    'main_interest'     => $in['main_interest'] ?? $meta['interest'],
    'timeline'          => $in['timeline'] ?? null,
    'budget'            => $in['budget'] ?? null,
    'preferred_area'    => $in['preferred_area'] ?? '',
    'property_type'     => $in['property_type'] ?? null,
    'buying_status'     => $in['buying_status'] ?? null,
    'interests'         => $in['interests'] ?? ($meta['interest'] ? [$meta['interest']] : []),
    'locations'         => $in['locations'] ?? [],
    'lead_source'       => $meta['source'],
    'utm_source'        => $in['utm_source'] ?? '',
    'utm_medium'        => $in['utm_medium'] ?? '',
    'utm_campaign'      => $in['utm_campaign'] ?? '',
    'utm_content'       => $in['utm_content'] ?? '',
    'marketing_consent' => !empty($in['consent']) ? 1 : 0,
    'consent_source'    => $formKey,
]);

// Persist the raw submission
DB::insert(
    'INSERT INTO bpr_form_submissions (contact_id, form_key, payload_json, ip, user_agent) VALUES (?,?,?,?,?)',
    [$contact['id'], $formKey, json_encode($in, JSON_UNESCAPED_UNICODE), Utils::ip(), Utils::userAgent()]
);

Activity::log((int)$contact['id'], $meta['event'], [
    'category' => $meta['interest'],
    'meta'     => ['form' => $formKey, 'message' => Utils::s($in['message'] ?? '', 500)],
]);

if (!empty($in['message'])) IntentDetector::fromText((int)$contact['id'], (string)$in['message']);

Brevo::upsertContact($email, [
    'FIRSTNAME' => $contact['first_name'] ?? '',
    'LASTNAME'  => $contact['last_name'] ?? '',
    'LANGUAGE'  => $contact['language'] ?? 'en',
]);

// Trigger automations
$started = Automation::enrollByTrigger((int)$contact['id'], 'contact_created', []);
if ($meta['interest']) {
    array_push($started, ...Automation::enrollByTrigger((int)$contact['id'], 'interest_added', ['interest' => $meta['interest']]));
}

Response::ok(['contact_id' => (int)$contact['id'], 'automation' => $started]);
