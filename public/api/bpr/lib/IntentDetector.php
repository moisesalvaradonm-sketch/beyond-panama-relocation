<?php
namespace BPR;

/**
 * Turns individual signals into a coarse intent_level on the contact.
 *  - none  : fresh
 *  - low   : signed up, some activity
 *  - medium: engaged (opens/clicks/blog reads)
 *  - high  : reads residency/property/business + WhatsApp click
 *  - ready : consultation_request OR score >= 81
 */
class IntentDetector {
    /** Called after every Activity::log(). */
    public static function evaluate(int $contactId, string $event, array $extra = []): void {
        $c = DB::row('SELECT lead_score, intent_level FROM bpr_contacts WHERE id = ?', [$contactId]);
        if (!$c) return;

        $score = (int)$c['lead_score'];
        $current = $c['intent_level'];
        $new = $current;

        if ($event === 'consultation_request' || $score >= 81) {
            $new = 'ready';
        } elseif ($event === 'whatsapp_click' || $score >= 51) {
            $new = self::higher($current, 'high');
        } elseif (in_array($event, ['page_view_residency','page_view_property','page_view_business','page_view_retirement','guide_download'], true)) {
            $new = self::higher($current, 'medium');
        } elseif (in_array($event, ['email_open','email_click','page_view','quiz_completed','newsletter_signup'], true)) {
            $new = self::higher($current, 'low');
        }

        if ($new !== $current) {
            DB::exec('UPDATE bpr_contacts SET intent_level = ? WHERE id = ?', [$new, $contactId]);
        }
    }

    /** Text/keyword based intent from free text a contact submitted (form message, quiz notes). */
    public static function fromText(int $contactId, string $text): void {
        $text = mb_strtolower($text);
        $signals = [
            'ready'  => ['ready to move','moving next month','moving next year','how do i start','can you help me now'],
            'high'   => ['how much does residency cost','what documents','need visa','need residency','buy a house','find a home','looking for property','pensionado','friendly nations'],
            'medium' => ['thinking about','considering','planning to move','possibly'],
        ];
        $level = null;
        foreach ($signals as $lvl => $phrases) {
            foreach ($phrases as $p) {
                if (str_contains($text, $p)) { $level = $lvl; break 2; }
            }
        }
        if ($level) {
            $current = DB::scalar('SELECT intent_level FROM bpr_contacts WHERE id = ?', [$contactId]) ?? 'none';
            $new = self::higher((string)$current, $level);
            if ($new !== $current) {
                DB::exec('UPDATE bpr_contacts SET intent_level = ? WHERE id = ?', [$new, $contactId]);
            }
        }
    }

    private static function higher(string $a, string $b): string {
        $order = ['none' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'ready' => 4];
        return ($order[$b] ?? 0) > ($order[$a] ?? 0) ? $b : $a;
    }
}
