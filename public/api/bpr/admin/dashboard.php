<?php
require __DIR__ . '/../bootstrap.php';
use BPR\{Response, Auth, DB};

bpr_cors();
Auth::require();

$stats = [
    'contacts_total'       => (int) DB::scalar('SELECT COUNT(*) FROM bpr_contacts'),
    'new_this_month'       => (int) DB::scalar('SELECT COUNT(*) FROM bpr_contacts WHERE created_at >= DATE_FORMAT(UTC_TIMESTAMP(), "%Y-%m-01")'),
    'quiz_leads'           => (int) DB::scalar('SELECT COUNT(DISTINCT contact_id) FROM bpr_quiz_results'),
    'newsletter_subs'      => (int) DB::scalar('SELECT COUNT(*) FROM bpr_contacts WHERE marketing_consent = 1 AND unsubscribed_at IS NULL'),
    'hot_leads'            => (int) DB::scalar('SELECT COUNT(*) FROM bpr_contacts WHERE lead_status IN ("hot","very_hot")'),
    'consultation_requests'=> (int) DB::scalar('SELECT COUNT(*) FROM bpr_activities WHERE event = "consultation_request"'),

    'emails_sent_30d'  => (int) DB::scalar('SELECT COUNT(*) FROM bpr_email_events WHERE event = "sent" AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'),
    'opens_30d'        => (int) DB::scalar('SELECT COUNT(*) FROM bpr_email_events WHERE event = "open" AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'),
    'clicks_30d'       => (int) DB::scalar('SELECT COUNT(*) FROM bpr_email_events WHERE event = "click" AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'),
    'unsubs_30d'       => (int) DB::scalar('SELECT COUNT(*) FROM bpr_email_events WHERE event = "unsubscribe" AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'),
];

$byInterest = DB::rows(
    'SELECT main_interest AS k, COUNT(*) AS n FROM bpr_contacts
      WHERE main_interest IS NOT NULL GROUP BY main_interest ORDER BY n DESC'
);
$bySegment = DB::rows(
    'SELECT s.slug AS k, s.name AS name, COUNT(cs.contact_id) AS n
       FROM bpr_segments s LEFT JOIN bpr_contact_segments cs ON cs.segment_id = s.id
       GROUP BY s.id ORDER BY n DESC'
);
$byQuiz = DB::rows(
    'SELECT result_label AS k, COUNT(DISTINCT contact_id) AS n FROM bpr_quiz_results
      GROUP BY result_label ORDER BY n DESC'
);
$topSource = DB::rows(
    'SELECT COALESCE(lead_source,"unknown") AS k, COUNT(*) AS n FROM bpr_contacts
      GROUP BY lead_source ORDER BY n DESC LIMIT 10'
);

Response::ok([
    'stats'   => $stats,
    'by_interest' => $byInterest,
    'by_segment'  => $bySegment,
    'by_quiz'     => $byQuiz,
    'by_source'   => $topSource,
]);
