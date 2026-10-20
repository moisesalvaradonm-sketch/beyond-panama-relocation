<?php
namespace BPR;

class LeadScorer {
    private static ?array $rules = null;

    public static function rules(): array {
        if (self::$rules !== null) return self::$rules;
        $rows = DB::rows('SELECT event, delta FROM bpr_score_rules');
        $out = [];
        foreach ($rows as $r) $out[$r['event']] = (int)$r['delta'];
        return self::$rules = $out;
    }

    public static function deltaFor(string $event): int {
        return self::rules()[$event] ?? 0;
    }

    public static function bump(int $contactId, int $delta): void {
        if ($delta === 0) return;
        DB::exec('UPDATE bpr_contacts SET lead_score = GREATEST(0, lead_score + ?) WHERE id = ?', [$delta, $contactId]);
        $score = (int) DB::scalar('SELECT lead_score FROM bpr_contacts WHERE id = ?', [$contactId]);
        $status = self::statusFor($score);
        DB::exec('UPDATE bpr_contacts SET lead_status = ? WHERE id = ?', [$status, $contactId]);
    }

    public static function statusFor(int $score): string {
        if ($score >= 81) return 'very_hot';
        if ($score >= 51) return 'hot';
        if ($score >= 21) return 'engaged';
        return 'cold';
    }
}
