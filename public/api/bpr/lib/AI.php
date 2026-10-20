<?php
namespace BPR;

/**
 * Claude API client (Anthropic) — used by the AI Content Engine.
 * Keeps hallucinations bounded by feeding the model the Knowledge Base as context.
 *
 * Prompts are anti-generic and require the model to refuse rather than invent
 * numbers, laws, fees, or immigration rules that aren't in the KB.
 */
class AI {
    /** Low-level call. Returns [ok, text|null, raw]. */
    public static function complete(string $system, string $user, array $opts = []): array {
        $cfg = bpr_config()['anthropic'];
        if (empty($cfg['api_key']) || strpos($cfg['api_key'], 'REPLACE') === 0) {
            return ['ok' => false, 'text' => null, 'raw' => null, 'error' => 'Anthropic API key not configured'];
        }
        $payload = [
            'model'      => $opts['model']    ?? $cfg['model'] ?? 'claude-sonnet-5',
            'max_tokens' => (int)($opts['max_tokens'] ?? 1500),
            'system'     => $system,
            'messages'   => [[
                'role'    => 'user',
                'content' => $user,
            ]],
            'temperature' => (float)($opts['temperature'] ?? 0.5),
        ];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $cfg['api_key'],
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code >= 400) {
            error_log('[AI] HTTP ' . $code . ' ' . $err . ' ' . substr((string)$raw, 0, 500));
            return ['ok' => false, 'text' => null, 'raw' => $raw, 'error' => $err ?: 'HTTP ' . $code];
        }
        $body = json_decode($raw, true) ?: [];
        $text = '';
        foreach ($body['content'] ?? [] as $blk) {
            if (($blk['type'] ?? '') === 'text') $text .= $blk['text'];
        }
        return ['ok' => true, 'text' => trim($text), 'raw' => $body];
    }

    /** Assemble the shared system prompt for content generation. */
    public static function contentSystemPrompt(): string {
        $kb = self::knowledgeBaseText();
        return <<<SYS
You are the content writer for Beyond Panama Relocation, a company that helps people
relocate to Panama (residency, property, retirement, business, remote work, lifestyle).

Voice: warm, specific, practical, expert — never generic. Write like a human who has
actually helped clients move here, not like a tourism brochure.

Rules — never break these:
1. DO NOT invent numbers, fees, tax rates, visa costs, government processing times,
   healthcare prices, or property prices. If the Knowledge Base below does not
   contain a fact, say so honestly ("check current requirements with your advisor"
   or omit the specific number entirely) rather than making one up.
2. DO NOT claim official partnerships or affiliations that are not stated in the KB.
3. DO NOT reproduce testimonials or client names that are not provided to you.
4. Prefer concrete, useful details from the KB. Prefer questions over unfounded claims.
5. Keep sentences readable. Avoid "Panama is a beautiful country" style filler.

Beyond Panama Relocation Knowledge Base (authoritative):
$kb

Contact info for CTAs (always safe to include):
- WhatsApp: +507 6084-4256
- Website: https://beyondpanamarelocation.com
- Email: contact@beyondpanamarelocation.com
SYS;
    }

    public static function knowledgeBaseText(): string {
        $rows = DB::rows('SELECT topic, key_name, value_text, source, verified_at, confidence, language FROM bpr_knowledge');
        $out = '';
        foreach ($rows as $r) {
            $out .= "- [{$r['topic']}::{$r['key_name']}] ({$r['confidence']}) {$r['value_text']}\n";
        }
        return $out ?: '(empty)';
    }

    /** Generate a personalised email (subject + html) for a contact. */
    public static function generateEmailFor(int $contactId, string $goal = 'nurture', array $opts = []): array {
        $c = Contact::profile($contactId);
        if (!$c) return ['ok' => false, 'error' => 'contact not found'];

        $ctx = [
            'first_name'   => $c['first_name'] ?? '',
            'language'     => $c['language'] ?? 'en',
            'main_interest'=> $c['main_interest'] ?? null,
            'timeline'     => $c['timeline'] ?? null,
            'budget'       => $c['budget'] ?? null,
            'preferred_area'=> $c['preferred_area'] ?? null,
            'interests'    => array_column($c['interests'] ?? [], 'interest'),
            'locations'    => array_column($c['locations'] ?? [], 'location'),
            'quiz'         => $c['quiz'] ?? null,
            'intent'       => $c['intent_level'] ?? 'none',
            'lead_status'  => $c['lead_status'] ?? 'cold',
        ];

        $user = "Write a short marketing email (150-250 words) for this contact.\n"
              . "Goal: $goal.\n"
              . "Language: " . ($ctx['language'] ?? 'en') . ".\n"
              . "Personalisation context (JSON, use ONLY these facts about the person):\n"
              . json_encode($ctx, JSON_PRETTY_PRINT) . "\n\n"
              . "Return STRICT JSON with keys: subject, preheader, html.\n"
              . "html must be valid HTML fragment. Include one primary CTA link to {{consultation_url}} "
              . "and a secondary link to {{whatsapp_url}}. Include {{first_name}} in the greeting. "
              . "Do not add tracking pixels. Do not include DOCTYPE/head/body.";

        $res = self::complete(self::contentSystemPrompt(), $user, ['max_tokens' => 1200, 'temperature' => 0.6]);
        if (!$res['ok']) return $res;

        $json = self::extractJson($res['text']);
        if (!$json || empty($json['subject']) || empty($json['html'])) {
            return ['ok' => false, 'error' => 'AI did not return valid JSON', 'raw' => $res['text']];
        }
        return ['ok' => true] + $json;
    }

    /** Generate a full blog article (returns fields ready for bpr_content). */
    public static function generateArticle(string $topic, string $language = 'en', array $opts = []): array {
        $user = "Write a helpful, non-generic article for the Beyond Panama blog.\n"
              . "Topic: $topic\n"
              . "Language: $language\n\n"
              . "Return STRICT JSON with: slug, title, meta_title, meta_desc, summary, hero_prompt, "
              . "body_md (markdown, 800-1200 words, includes H2/H3 sections and a short FAQ), "
              . "interest_tags (array), category, location_tag (or null).";

        $res = self::complete(self::contentSystemPrompt(), $user, ['max_tokens' => 3500, 'temperature' => 0.55]);
        if (!$res['ok']) return $res;
        $json = self::extractJson($res['text']);
        if (!$json || empty($json['title']) || empty($json['body_md'])) {
            return ['ok' => false, 'error' => 'AI did not return valid JSON', 'raw' => $res['text']];
        }
        return ['ok' => true] + $json;
    }

    /** Extract a JSON object even when the model wraps it in prose. */
    public static function extractJson(string $text): ?array {
        $text = trim($text);
        if ($text === '') return null;
        if ($text[0] === '{') {
            $j = json_decode($text, true);
            if (is_array($j)) return $j;
        }
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $j = json_decode($m[0], true);
            if (is_array($j)) return $j;
        }
        return null;
    }
}
