<?php
namespace BPR;

/**
 * Renders a template with {{variables}}.
 *   - Missing vars fall back to a per-key default, then empty string.
 *   - Adds tracking pixel & rewrites links when a queue row is provided.
 */
class Personalize {
    public static function render(string $tpl, array $contact, array $extra = []): string {
        $vars = self::variablesFor($contact, $extra);
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function($m) use ($vars) {
            $k = $m[1];
            return isset($vars[$k]) ? (string)$vars[$k] : '';
        }, $tpl);
    }

    public static function variablesFor(array $contact, array $extra = []): array {
        $settings = self::settings();
        $quiz = DB::row('SELECT result_key, result_label, match_percent FROM bpr_quiz_results WHERE contact_id = ? ORDER BY id DESC LIMIT 1', [$contact['id'] ?? 0]);
        $fn = $contact['first_name'] ?: 'there';

        $vars = [
            'first_name'           => $fn,
            'last_name'            => $contact['last_name'] ?? '',
            'email'                => $contact['email'] ?? '',
            'recommended_location' => $quiz['result_label'] ?? ($contact['preferred_area'] ?? 'Panama'),
            'quiz_match'           => $quiz['match_percent'] ?? '',
            'timeline'             => self::humanTimeline($contact['timeline'] ?? null),
            'budget'               => self::humanBudget($contact['budget'] ?? null),
            'main_interest'        => self::humanInterest($contact['main_interest'] ?? null),
            'preferred_area'       => $contact['preferred_area'] ?? '',
            'lead_score'           => $contact['lead_score'] ?? 0,
            'business_name'        => $settings['business_name'] ?? 'Beyond Panama Relocation',
            'website_url'          => $settings['website_url'] ?? 'https://beyondpanamarelocation.com',
            'whatsapp_url'         => $settings['whatsapp_url'] ?? 'https://wa.me/50760844256',
            'consultation_url'     => $settings['consultation_url'] ?? '',
            'unsubscribe_url'      => isset($contact['id']) ? Utils::unsubscribeUrl((int)$contact['id']) : '#',
            'year'                 => date('Y'),
        ];
        return array_replace($vars, $extra);
    }

    public static function wrapForSending(string $html, int $queueId): string {
        // Rewrite hrefs to go through click tracker
        $html = preg_replace_callback('/href\s*=\s*"([^"#]+)"/i', function($m) use ($queueId) {
            $url = $m[1];
            // Don't rewrite mailto/tel or already-tracked links
            if (preg_match('/^(mailto:|tel:|javascript:)/i', $url)) return $m[0];
            return 'href="' . htmlspecialchars(Utils::trackClickUrl($queueId, $url), ENT_QUOTES) . '"';
        }, $html);
        // Add 1x1 tracking pixel
        $pixel = '<img src="' . htmlspecialchars(Utils::trackOpenUrl($queueId), ENT_QUOTES) . '" alt="" width="1" height="1" style="display:none;border:0;outline:none;text-decoration:none" />';
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $pixel . '</body>', $html);
        } else {
            $html .= $pixel;
        }
        return $html;
    }

    public static function settings(): array {
        static $cache = null;
        if ($cache !== null) return $cache;
        $rows = DB::rows('SELECT name, value FROM bpr_settings');
        $cache = [];
        foreach ($rows as $r) $cache[$r['name']] = $r['value'];
        return $cache;
    }

    private static function humanTimeline(?string $v): string {
        return [
            'researching'    => 'just researching',
            '2plus_years'    => '2+ years',
            '1_2_years'      => '1–2 years',
            '6_12_months'    => '6–12 months',
            '3_6_months'     => '3–6 months',
            'under_3_months' => 'less than 3 months',
            'ready'          => 'ready to move',
        ][$v] ?? '';
    }
    private static function humanBudget(?string $v): string {
        return [
            'under_100k' => 'Under $100K',
            '100_200k'   => '$100K–$200K',
            '200_300k'   => '$200K–$300K',
            '300_500k'   => '$300K–$500K',
            '500k_1m'    => '$500K–$1M',
            '1m_plus'    => '$1M+',
        ][$v] ?? '';
    }
    private static function humanInterest(?string $v): string {
        return $v ? ucwords(str_replace('_', ' ', $v)) : '';
    }
}
