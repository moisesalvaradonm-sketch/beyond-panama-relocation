<?php
namespace BPR;

class Activity {
    /**
     * Log an activity and (if a rule matches) update the contact's lead score.
     * $event examples: newsletter_signup, quiz_completed, quiz_result,
     *  page_view, page_view_residency, page_view_property, page_view_business,
     *  guide_download, consultation_request, whatsapp_click, email_open,
     *  email_click, form_submit, repeat_visit, unsubscribe.
     */
    public static function log(?int $contactId, string $event, array $extra = []): int {
        $delta = LeadScorer::deltaFor($event);
        $id = DB::insert(
            'INSERT INTO bpr_activities
               (contact_id, event, category, url, title, meta, score_delta, ip, user_agent, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $contactId ?: null,
                $event,
                Utils::s($extra['category'] ?? '', 60) ?: null,
                Utils::s($extra['url']      ?? '', 500) ?: null,
                Utils::s($extra['title']    ?? '', 255) ?: null,
                isset($extra['meta']) ? json_encode($extra['meta']) : null,
                $delta,
                Utils::s($extra['ip']         ?? Utils::ip() ?? '', 45) ?: null,
                Utils::s($extra['user_agent'] ?? Utils::userAgent() ?? '', 255) ?: null,
                Utils::nowUtc(),
            ]
        );

        if ($contactId && $delta !== 0) {
            LeadScorer::bump($contactId, $delta);
        }
        if ($contactId) {
            DB::exec('UPDATE bpr_contacts SET last_activity_at = ? WHERE id = ?', [Utils::nowUtc(), $contactId]);
            // Increment interest scores if category matches an interest tag
            $cat = $extra['category'] ?? null;
            if ($cat && in_array($cat, Contact::INTERESTS, true)) {
                DB::exec(
                    'INSERT INTO bpr_contact_interests (contact_id, interest, score) VALUES (?,?,1)
                     ON DUPLICATE KEY UPDATE score = score + 1',
                    [$contactId, $cat]
                );
            }
            // Increment location scores likewise
            if ($cat && in_array($cat, Contact::LOCATIONS, true)) {
                DB::exec(
                    'INSERT INTO bpr_contact_locations (contact_id, location, score) VALUES (?,?,1)
                     ON DUPLICATE KEY UPDATE score = score + 1',
                    [$contactId, $cat]
                );
            }
            Segmenter::recompute($contactId);
            IntentDetector::evaluate($contactId, $event, $extra);
        }
        return $id;
    }
}
