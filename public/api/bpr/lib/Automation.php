<?php
namespace BPR;

/**
 * Automation runner.
 *   - enrollByTrigger(): called from route handlers on events.
 *   - tick(): called by cron/automations.php to advance active enrollments.
 *
 * Step types:
 *   wait        { hours: N } or { minutes: N }
 *   send_email  { template_slug: '...' }
 *   if_opened   { previous_step: N, else_step: N }   (skip forward if not opened)
 *   if_clicked  { previous_step: N, else_step: N }
 *   add_score   { delta: N }
 *   set_field   { field: 'x', value: 'y' }
 *   tag         { interest: 'residency' }
 *   goto        { step: N }
 *   end
 */
class Automation {
    public static function enrollByTrigger(int $contactId, string $trigger, array $context = []): array {
        $rows = DB::rows('SELECT * FROM bpr_automations WHERE is_active = 1 AND trigger_type = ?', [$trigger]);
        $started = [];
        foreach ($rows as $a) {
            $cfg = json_decode($a['trigger_config'] ?: '{}', true) ?: [];
            // Optional trigger config matching (e.g. interest_added -> require specific interest)
            if ($trigger === 'interest_added' && !empty($cfg['interest'])) {
                if (($context['interest'] ?? '') !== $cfg['interest']) continue;
            }
            $id = self::enroll((int)$a['id'], $contactId, $context);
            if ($id) $started[] = ['automation' => $a['slug'], 'enrollment' => $id];
        }
        return $started;
    }

    public static function enroll(int $automationId, int $contactId, array $context = []): ?int {
        // Skip if already active or completed (idempotent).
        $existing = DB::row('SELECT id, status FROM bpr_automation_enrollments WHERE automation_id = ? AND contact_id = ?', [$automationId, $contactId]);
        if ($existing) return (int)$existing['id'];
        return DB::insert(
            'INSERT INTO bpr_automation_enrollments (automation_id, contact_id, current_step, next_run_at, context_json)
             VALUES (?,?,?,?,?)',
            [$automationId, $contactId, 0, Utils::nowUtc(), json_encode($context)]
        );
    }

    /** Advance one enrollment. Returns true if it did work. */
    public static function advance(int $enrollmentId): bool {
        $e = DB::row('SELECT * FROM bpr_automation_enrollments WHERE id = ?', [$enrollmentId]);
        if (!$e || $e['status'] !== 'active') return false;
        if (strtotime($e['next_run_at']) > time()) return false;

        // Load steps in order
        $steps = DB::rows('SELECT * FROM bpr_automation_steps WHERE automation_id = ? ORDER BY position ASC', [$e['automation_id']]);
        $positions = array_column($steps, 'position');
        $currentIdx = 0;
        foreach ($positions as $i => $p) if ((int)$p >= (int)$e['current_step']) { $currentIdx = $i; break; }

        $step = $steps[$currentIdx] ?? null;
        if (!$step) {
            DB::exec('UPDATE bpr_automation_enrollments SET status = "completed", completed_at = ? WHERE id = ?', [Utils::nowUtc(), $enrollmentId]);
            return true;
        }
        $cfg = json_decode($step['config_json'] ?: '{}', true) ?: [];

        $advanceToNext = function() use ($enrollmentId, $steps, $currentIdx) {
            $next = $steps[$currentIdx + 1] ?? null;
            if (!$next) {
                DB::exec('UPDATE bpr_automation_enrollments SET status = "completed", completed_at = ? WHERE id = ?', [Utils::nowUtc(), $enrollmentId]);
            } else {
                DB::exec('UPDATE bpr_automation_enrollments SET current_step = ?, next_run_at = ? WHERE id = ?',
                    [$next['position'], Utils::nowUtc(), $enrollmentId]);
            }
        };

        switch ($step['step_type']) {
            case 'wait':
                $secs = (int)(($cfg['hours'] ?? 0) * 3600 + ($cfg['minutes'] ?? 0) * 60);
                $next = $steps[$currentIdx + 1] ?? null;
                if (!$next) {
                    DB::exec('UPDATE bpr_automation_enrollments SET status = "completed", completed_at = ? WHERE id = ?', [Utils::nowUtc(), $enrollmentId]);
                } else {
                    DB::exec('UPDATE bpr_automation_enrollments SET current_step = ?, next_run_at = ? WHERE id = ?',
                        [$next['position'], Utils::inFuture($secs), $enrollmentId]);
                }
                return true;

            case 'send_email':
                $slug = $cfg['template_slug'] ?? '';
                if ($slug) Mailer::queueTemplate((int)$e['contact_id'], $slug, 'now', $enrollmentId);
                $advanceToNext();
                return true;

            case 'add_score':
                LeadScorer::bump((int)$e['contact_id'], (int)($cfg['delta'] ?? 0));
                $advanceToNext();
                return true;

            case 'set_field':
                $field = preg_replace('/[^a-z_]/', '', (string)($cfg['field'] ?? ''));
                if ($field) DB::exec("UPDATE bpr_contacts SET $field = ? WHERE id = ?", [$cfg['value'] ?? null, $e['contact_id']]);
                $advanceToNext();
                return true;

            case 'tag':
                if (!empty($cfg['interest'])) {
                    DB::exec('INSERT IGNORE INTO bpr_contact_interests (contact_id, interest) VALUES (?, ?)',
                        [$e['contact_id'], $cfg['interest']]);
                    Segmenter::recompute((int)$e['contact_id']);
                }
                $advanceToNext();
                return true;

            case 'if_opened':
            case 'if_clicked':
                $prev = (int)($cfg['previous_step'] ?? 0);
                $type = $step['step_type'] === 'if_opened' ? 'open' : 'click';
                $hit = DB::scalar(
                    'SELECT 1 FROM bpr_email_events ev
                       JOIN bpr_email_queue q ON q.id = ev.queue_id
                      WHERE q.contact_id = ? AND q.enrollment_id = ? AND ev.event = ? LIMIT 1',
                    [$e['contact_id'], $enrollmentId, $type]
                );
                if ($hit) {
                    $advanceToNext();
                } else {
                    $elseStep = (int)($cfg['else_step'] ?? 0);
                    if ($elseStep > 0) {
                        DB::exec('UPDATE bpr_automation_enrollments SET current_step = ?, next_run_at = ? WHERE id = ?',
                            [$elseStep, Utils::nowUtc(), $enrollmentId]);
                    } else $advanceToNext();
                }
                return true;

            case 'goto':
                $target = (int)($cfg['step'] ?? 0);
                DB::exec('UPDATE bpr_automation_enrollments SET current_step = ?, next_run_at = ? WHERE id = ?',
                    [$target, Utils::nowUtc(), $enrollmentId]);
                return true;

            case 'end':
            default:
                DB::exec('UPDATE bpr_automation_enrollments SET status = "completed", completed_at = ? WHERE id = ?',
                    [Utils::nowUtc(), $enrollmentId]);
                return true;
        }
    }
}
