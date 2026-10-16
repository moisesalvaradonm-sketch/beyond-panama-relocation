<?php
namespace BPR;

/**
 * Evaluates dynamic segments against a contact.
 * Rule JSON shape:
 *   { "all": [ {field,op,value}, ... ] } — every clause must match
 *   { "any": [ ... ] }                    — at least one clause matches
 *
 * Fields supported:
 *   main_interest, timeline, buying_status, budget, language,
 *   country, lead_score, lead_status,
 *   quiz_result   (last quiz result_key)
 *   interest      (op "has"/"in" against contact_interests)
 *   location      (op "has"/"in" against contact_locations)
 *
 * Ops: eq, neq, in, gte, lte, gt, lt, has, contains.
 */
class Segmenter {
    public static function recompute(int $contactId): void {
        $c = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [$contactId]);
        if (!$c) return;

        $c['interests'] = array_column(
            DB::rows('SELECT interest FROM bpr_contact_interests WHERE contact_id = ?', [$contactId]),
            'interest'
        );
        $c['locations'] = array_column(
            DB::rows('SELECT location FROM bpr_contact_locations WHERE contact_id = ?', [$contactId]),
            'location'
        );
        $c['quiz_result'] = DB::scalar(
            'SELECT result_key FROM bpr_quiz_results WHERE contact_id = ? ORDER BY id DESC LIMIT 1',
            [$contactId]
        );

        $segments = DB::rows('SELECT id, rule_json FROM bpr_segments WHERE is_active = 1');
        // Wipe and reinsert (cheap for our scale; batches would be fine too).
        DB::exec('DELETE FROM bpr_contact_segments WHERE contact_id = ?', [$contactId]);
        foreach ($segments as $s) {
            $rule = json_decode($s['rule_json'], true) ?: [];
            if (self::matches($c, $rule)) {
                DB::exec('INSERT IGNORE INTO bpr_contact_segments (contact_id, segment_id) VALUES (?,?)',
                    [$contactId, (int)$s['id']]);
            }
        }
    }

    public static function matches(array $c, array $rule): bool {
        if (isset($rule['all']) && is_array($rule['all'])) {
            foreach ($rule['all'] as $clause) if (!self::clause($c, $clause)) return false;
            return true;
        }
        if (isset($rule['any']) && is_array($rule['any'])) {
            foreach ($rule['any'] as $clause) if (self::clause($c, $clause)) return true;
            return false;
        }
        return false;
    }

    private static function clause(array $c, array $clause): bool {
        $field = $clause['field'] ?? '';
        $op    = $clause['op'] ?? 'eq';
        $val   = $clause['value'] ?? null;

        $subject = match ($field) {
            'interest' => $c['interests'] ?? [],
            'location' => $c['locations'] ?? [],
            default    => $c[$field] ?? null,
        };

        return match ($op) {
            'eq'       => (string)$subject === (string)$val,
            'neq'      => (string)$subject !== (string)$val,
            'in'       => is_array($val) && in_array($subject, $val, true),
            'gte'      => is_numeric($subject) && (float)$subject >= (float)$val,
            'lte'      => is_numeric($subject) && (float)$subject <= (float)$val,
            'gt'       => is_numeric($subject) && (float)$subject >  (float)$val,
            'lt'       => is_numeric($subject) && (float)$subject <  (float)$val,
            'has'      => is_array($subject) && in_array($val, $subject, true),
            'contains' => is_string($subject) && $val !== null && stripos($subject, (string)$val) !== false,
            default    => false,
        };
    }
}
