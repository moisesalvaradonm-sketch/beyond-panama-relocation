<?php
/**
 * End-to-end sanity check. Requires cron_token.
 *   https://your-site/api/bpr/test.php?token=YOUR_CRON_TOKEN
 *
 * It creates the "Patrick" test lead, runs a quiz, advances the automation,
 * simulates opens/clicks/whatsapp, and prints a pass/fail report.
 * Deletes any previous test contact with email test@example.com first.
 */
require __DIR__ . '/bootstrap.php';
use BPR\{Auth, DB, Contact, Quiz, Activity, Automation, Mailer, LeadScorer, Segmenter, IntentDetector};

Auth::requireCronToken();
header('Content-Type: text/plain; charset=utf-8');

$report = [];
$ok = fn(string $name, bool $pass, string $note = '') => $report[] = ($pass ? '✓ ' : '✗ ') . $name . ($note ? ' — ' . $note : '');

$email = 'test@example.com';
DB::exec('DELETE FROM bpr_contacts WHERE email = ?', [$email]);

// 1. Upsert contact
$c = Contact::upsert([
    'email' => $email,
    'first_name' => 'Patrick', 'last_name' => 'Smith',
    'country' => 'United States', 'current_country' => 'United States',
    'language' => 'en', 'lead_source' => 'quiz',
    'marketing_consent' => 1, 'consent_source' => 'quiz',
]);
$ok('Contact created',            !empty($c['id']));
$ok('Marketing consent recorded', (int)$c['marketing_consent'] === 1);

// 2. Quiz
$answers = [
    'climate' => 'hot', 'lifestyle' => 'urban', 'healthcare' => 'high',
    'spanish' => 'medium', 'remote_work' => 'yes', 'business' => 'yes',
    'retirement' => 'no', 'budget' => '200_300k', 'timeline' => '1_2_years',
];
$q = Quiz::scoreAndStore((int)$c['id'], $answers);
$ok('Quiz result computed',       $q['key'] === 'panama_city', 'got=' . $q['key']);
$ok('Quiz saved to DB',           (bool) DB::scalar('SELECT 1 FROM bpr_quiz_results WHERE contact_id = ?', [$c['id']]));

// 3. Activities + scoring
Activity::log((int)$c['id'], 'quiz_completed', []);
Activity::log((int)$c['id'], 'quiz_result', ['category' => $q['key']]);
Activity::log((int)$c['id'], 'page_view_residency', ['category' => 'residency', 'url' => '/en/residency/']);
Activity::log((int)$c['id'], 'guide_download',      ['category' => 'residency', 'url' => '/downloads/panama-guide.pdf']);
Activity::log((int)$c['id'], 'whatsapp_click',      ['category' => 'contact', 'url' => 'https://wa.me/50760844256']);
$score = (int) DB::scalar('SELECT lead_score FROM bpr_contacts WHERE id = ?', [$c['id']]);
$ok('Lead score > 30 after activity', $score > 30, 'score=' . $score);
$status = (string) DB::scalar('SELECT lead_status FROM bpr_contacts WHERE id = ?', [$c['id']]);
$ok('Lead status upgraded from cold', $status !== 'cold', 'status=' . $status);

// 4. Segments
Segmenter::recompute((int)$c['id']);
$segs = array_column(DB::rows(
    'SELECT s.slug FROM bpr_contact_segments cs JOIN bpr_segments s ON s.id = cs.segment_id WHERE cs.contact_id = ?', [$c['id']]
), 'slug');
$ok('Segment: business',    in_array('business', $segs, true));
$ok('Segment: panama_city', in_array('panama_city', $segs, true));

// 5. Intent
IntentDetector::fromText((int)$c['id'], 'I am planning to move next year and need residency information.');
$intent = (string) DB::scalar('SELECT intent_level FROM bpr_contacts WHERE id = ?', [$c['id']]);
$ok('Intent detection from text', in_array($intent, ['medium','high','ready'], true), 'intent=' . $intent);

// 6. Automation triggered
$started = Automation::enrollByTrigger((int)$c['id'], 'quiz_completed', []);
$ok('Quiz automation enrolled', !empty($started));

// 7. Advance automation once (sends first email into queue)
$enrollment = DB::row('SELECT id FROM bpr_automation_enrollments WHERE contact_id = ? ORDER BY id DESC LIMIT 1', [$c['id']]);
if ($enrollment) {
    Automation::advance((int)$enrollment['id']);
    $queued = (int) DB::scalar('SELECT COUNT(*) FROM bpr_email_queue WHERE contact_id = ?', [$c['id']]);
    $ok('First email queued', $queued > 0, 'queued=' . $queued);
}

// 8. Unsubscribe token round-trip
$unsub = \BPR\Utils::unsubscribeUrl((int)$c['id']);
$parsed = parse_url($unsub);
parse_str($parsed['query'] ?? '', $qs);
$claim = \BPR\Utils::unsign($qs['t'] ?? '');
$ok('Unsubscribe URL is signed correctly', $claim && ($claim['c'] ?? null) == $c['id']);

// 9. Config sanity
$cfg = bpr_config();
$ok('Anthropic API key present', !empty($cfg['anthropic']['api_key']) && strpos($cfg['anthropic']['api_key'], 'REPLACE') !== 0);
$ok('Brevo API key present',     !empty($cfg['brevo']['api_key'])     && strpos($cfg['brevo']['api_key'], 'REPLACE') !== 0);

echo "Beyond Panama Lead Engine — end-to-end test\n";
echo str_repeat('-', 60) . "\n";
foreach ($report as $line) echo $line . "\n";
echo str_repeat('-', 60) . "\n";
$fail = count(array_filter($report, fn($l) => str_starts_with($l, '✗')));
echo $fail ? "$fail check(s) failed.\n" : "All checks passed. ✓\n";
