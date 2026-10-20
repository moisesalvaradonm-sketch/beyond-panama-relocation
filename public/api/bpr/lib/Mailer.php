<?php
namespace BPR;

/**
 * Queue-first mailer. queue() writes to bpr_email_queue and cron/email_queue.php
 * picks them up. sendNow() is used only for immediate transactional flows.
 */
class Mailer {
    /**
     * Queue an email from a template slug.
     * $when: 'now' or seconds delay.
     */
    public static function queueTemplate(int $contactId, string $templateSlug, $when = 'now', ?int $enrollmentId = null): ?int {
        $contact = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [$contactId]);
        if (!$contact) return null;
        if ((int)$contact['marketing_consent'] !== 1) return null;
        if (!empty($contact['unsubscribed_at'])) return null;

        // Prefer template in contact's language; fall back to default.
        $t = DB::row('SELECT * FROM bpr_email_templates WHERE slug = ? AND language = ? AND status = "approved"',
            [$templateSlug, $contact['language'] ?? 'en']);
        if (!$t) $t = DB::row('SELECT * FROM bpr_email_templates WHERE slug = ? AND status = "approved" ORDER BY (language="en") DESC LIMIT 1', [$templateSlug]);
        if (!$t) { error_log("[Mailer] template not found: $templateSlug"); return null; }

        $subject = Personalize::render($t['subject'], $contact);
        $html    = Personalize::render($t['body_html'], $contact);
        $text    = $t['body_text'] ? Personalize::render($t['body_text'], $contact) : null;

        $sched = ($when === 'now' || $when === 0) ? Utils::nowUtc() : Utils::inFuture((int)$when);

        return DB::insert(
            'INSERT INTO bpr_email_queue (contact_id, template_id, enrollment_id, subject, body_html, body_text, scheduled_at)
             VALUES (?,?,?,?,?,?,?)',
            [$contactId, (int)$t['id'], $enrollmentId, $subject, $html, $text, $sched]
        );
    }

    /** Queue a raw (already-rendered) email. */
    public static function queueRaw(int $contactId, string $subject, string $html, ?string $text = null, ?int $campaignId = null, ?int $enrollmentId = null, string $when = 'now'): ?int {
        $sched = ($when === 'now') ? Utils::nowUtc() : Utils::inFuture((int)$when);
        return DB::insert(
            'INSERT INTO bpr_email_queue (contact_id, campaign_id, enrollment_id, subject, body_html, body_text, scheduled_at)
             VALUES (?,?,?,?,?,?,?)',
            [$contactId, $campaignId, $enrollmentId, $subject, $html, $text, $sched]
        );
    }

    /** Send one queued row. Called by cron. */
    public static function deliver(int $queueId): bool {
        $q = DB::row('SELECT * FROM bpr_email_queue WHERE id = ?', [$queueId]);
        if (!$q || $q['status'] !== 'pending') return false;

        $c = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [$q['contact_id']]);
        if (!$c) {
            DB::exec('UPDATE bpr_email_queue SET status = "failed", error = "no contact" WHERE id = ?', [$queueId]);
            return false;
        }
        if ((int)$c['marketing_consent'] !== 1 || !empty($c['unsubscribed_at'])) {
            DB::exec('UPDATE bpr_email_queue SET status = "skipped", error = "no consent" WHERE id = ?', [$queueId]);
            return false;
        }

        DB::exec('UPDATE bpr_email_queue SET status = "sending", attempts = attempts + 1 WHERE id = ?', [$queueId]);

        $html = Personalize::wrapForSending($q['body_html'], $queueId);
        $to   = ['email' => $c['email'], 'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))];
        $res  = Brevo::sendEmail($to, $q['subject'], $html, $q['body_text']);

        if ($res['ok']) {
            $messageId = $res['body']['messageId'] ?? null;
            DB::exec('UPDATE bpr_email_queue SET status = "sent", sent_at = ?, provider_id = ? WHERE id = ?',
                [Utils::nowUtc(), $messageId, $queueId]);
            DB::exec('UPDATE bpr_contacts SET last_email_sent_at = ? WHERE id = ?', [Utils::nowUtc(), $c['id']]);
            DB::exec('INSERT INTO bpr_email_events (queue_id, campaign_id, contact_id, event) VALUES (?,?,?, "sent")',
                [$queueId, $q['campaign_id'], $c['id']]);
            if (!empty($q['campaign_id'])) {
                DB::exec('UPDATE bpr_campaigns SET total_sent = total_sent + 1 WHERE id = ?', [$q['campaign_id']]);
            }
            return true;
        }

        $errMsg = mb_substr(json_encode($res['body'] ?? $res['error'] ?? ''), 0, 500);
        DB::exec('UPDATE bpr_email_queue SET status = "failed", error = ? WHERE id = ?', [$errMsg, $queueId]);
        DB::exec('INSERT INTO bpr_email_events (queue_id, contact_id, event, meta) VALUES (?,?, "failed", ?)',
            [$queueId, $c['id'], json_encode(['brevo' => $res])]);
        return false;
    }
}
