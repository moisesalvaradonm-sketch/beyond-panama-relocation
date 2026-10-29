<?php
namespace BPR;

/**
 * Campaign dispatch + recurrence (weekly / monthly newsletters).
 * Send times are expressed in Panama time (UTC-5, no DST) and stored as UTC.
 */
class Campaign {
    public const TZ = 'America/Panama';

    /** Next run (UTC 'Y-m-d H:i:s') strictly after now. $day: weekly 1=Mon..7=Sun, monthly 1..28. */
    public static function nextRun(string $recurrence, int $day, string $time): string {
        $tz  = new \DateTimeZone(self::TZ);
        $now = new \DateTimeImmutable('now', $tz);
        if (!preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) { $m = [0, 9, 0]; }
        [$h, $min] = [min(23, (int)$m[1]), min(59, (int)$m[2])];

        if ($recurrence === 'weekly') {
            $day = max(1, min(7, $day));
            $d = $now->setTime($h, $min, 0);
            $d = $d->modify('+' . (($day - (int)$d->format('N') + 7) % 7) . ' days');
            if ($d <= $now) $d = $d->modify('+7 days');
        } else { // monthly
            $day = max(1, min(28, $day));
            $d = $now->setDate((int)$now->format('Y'), (int)$now->format('n'), $day)->setTime($h, $min, 0);
            if ($d <= $now) $d = $d->setDate((int)$now->format('Y'), (int)$now->format('n') + 1, $day);
        }
        return $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /** Render + queue the campaign's template for every consenting recipient. Returns count queued. */
    public static function queueRecipients(array $c): int {
        $t = DB::row('SELECT * FROM bpr_email_templates WHERE id = ?', [$c['template_id']]);
        if (!$t) return 0;

        if (!empty($c['segment_id'])) {
            $recipients = DB::rows(
                'SELECT c.* FROM bpr_contacts c
                   JOIN bpr_contact_segments cs ON cs.contact_id = c.id
                  WHERE cs.segment_id = ? AND c.marketing_consent = 1 AND c.unsubscribed_at IS NULL',
                [$c['segment_id']]
            );
        } else {
            $recipients = DB::rows('SELECT * FROM bpr_contacts WHERE marketing_consent = 1 AND unsubscribed_at IS NULL');
        }

        $queued = 0;
        foreach ($recipients as $rc) {
            $subject = Personalize::render($t['subject'], $rc);
            $html    = Personalize::render($t['body_html'], $rc);
            $text    = $t['body_text'] ? Personalize::render($t['body_text'], $rc) : null;
            if (Mailer::queueRaw((int)$rc['id'], $subject, $html, $text, (int)$c['id'])) $queued++;
        }
        return $queued;
    }

    /**
     * Send a campaign now. One-off campaigns end as "sent"; recurring ones stay
     * "scheduled" and (when $advance) roll scheduled_at to the next occurrence.
     */
    public static function dispatch(array $c, bool $advance = false): int {
        $recurring = ($c['recurrence'] ?? 'none') !== 'none';
        $queued = self::queueRecipients($c);

        if ($recurring) {
            $next = $advance ? self::nextRun($c['recurrence'], (int)$c['recur_day'], (string)$c['recur_time']) : $c['scheduled_at'];
            DB::exec('UPDATE bpr_campaigns SET sent_at = ?, total_sent = total_sent + ?, scheduled_at = ? WHERE id = ?',
                [Utils::nowUtc(), $queued, $next, $c['id']]);
        } else {
            DB::exec('UPDATE bpr_campaigns SET status = "sent", sent_at = ?, total_sent = ? WHERE id = ?',
                [Utils::nowUtc(), $queued, $c['id']]);
        }
        return $queued;
    }
}
