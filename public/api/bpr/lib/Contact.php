<?php
namespace BPR;

class Contact {
    public const INTERESTS = ['relocation','residency','retirement','property','business','remote_work','healthcare','lifestyle','investment','banking','other'];
    public const TIMELINES = ['researching','2plus_years','1_2_years','6_12_months','3_6_months','under_3_months','ready'];
    public const LOCATIONS = ['panama_city','boquete','coronado','bocas_del_toro','chiriqui','pedasi','playa_blanca','other'];
    public const BUYING    = ['buying','renting','undecided'];
    public const BUDGETS   = ['under_100k','100_200k','200_300k','300_500k','500k_1m','1m_plus'];
    public const PROP_TYPES = ['condo','house','land','commercial','investment'];

    /** Upsert a contact by email. Returns full contact row. */
    public static function upsert(array $in): array {
        $email = Utils::email($in['email'] ?? '');
        if (!$email) throw new \InvalidArgumentException('Invalid email');

        $existing = DB::row('SELECT * FROM bpr_contacts WHERE email = ?', [$email]);

        $data = [
            'first_name'      => Utils::s($in['first_name'] ?? ($existing['first_name'] ?? ''), 120) ?: null,
            'last_name'       => Utils::s($in['last_name']  ?? ($existing['last_name']  ?? ''), 120) ?: null,
            'phone'           => Utils::s($in['phone']      ?? ($existing['phone']      ?? ''), 40)  ?: null,
            'country'         => Utils::s($in['country']    ?? ($existing['country']    ?? ''), 80)  ?: null,
            'current_country' => Utils::s($in['current_country'] ?? ($existing['current_country'] ?? ''), 80) ?: null,
            'current_city'    => Utils::s($in['current_city']    ?? ($existing['current_city']    ?? ''), 120) ?: null,
            'language'        => self::enum($in['language'] ?? ($existing['language'] ?? 'en'), ['en','es','fr'], 'en'),
            'main_interest'   => self::enum($in['main_interest'] ?? ($existing['main_interest'] ?? null), self::INTERESTS, null),
            'timeline'        => self::enum($in['timeline'] ?? ($existing['timeline'] ?? null), self::TIMELINES, null),
            'buying_status'   => self::enum($in['buying_status'] ?? ($existing['buying_status'] ?? null), self::BUYING, null),
            'budget'          => self::enum($in['budget'] ?? ($existing['budget'] ?? null), self::BUDGETS, null),
            'preferred_area'  => Utils::s($in['preferred_area'] ?? ($existing['preferred_area'] ?? ''), 120) ?: null,
            'property_type'   => self::enum($in['property_type'] ?? ($existing['property_type'] ?? null), self::PROP_TYPES, null),
            'lead_source'     => Utils::s($in['lead_source'] ?? ($existing['lead_source'] ?? ''), 80) ?: null,
            'utm_source'      => Utils::s($in['utm_source'] ?? ($existing['utm_source'] ?? ''), 120) ?: null,
            'utm_medium'      => Utils::s($in['utm_medium'] ?? ($existing['utm_medium'] ?? ''), 120) ?: null,
            'utm_campaign'    => Utils::s($in['utm_campaign'] ?? ($existing['utm_campaign'] ?? ''), 120) ?: null,
            'utm_content'     => Utils::s($in['utm_content'] ?? ($existing['utm_content'] ?? ''), 120) ?: null,
        ];

        if ($existing) {
            $set = [];
            $params = [];
            foreach ($data as $k => $v) {
                if ($v !== null && $v !== '') { $set[] = "$k = ?"; $params[] = $v; }
            }
            if ($set) {
                $params[] = $existing['id'];
                DB::exec('UPDATE bpr_contacts SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
            }
            $id = (int)$existing['id'];
        } else {
            $data['email'] = $email;
            // Marketing consent handled by explicit opt-in only
            if (!empty($in['marketing_consent'])) {
                $data['marketing_consent'] = 1;
                $data['consent_source']    = Utils::s($in['consent_source'] ?? ($data['lead_source'] ?? 'signup'), 80);
                $data['consent_at']        = Utils::nowUtc();
            }
            $cols = array_keys($data);
            $ph   = array_fill(0, count($cols), '?');
            $id = DB::insert(
                'INSERT INTO bpr_contacts (' . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')',
                array_values($data)
            );
        }

        // Interests (multi-value)
        $interests = self::normalizeList($in['interests'] ?? [], self::INTERESTS);
        foreach ($interests as $x) {
            DB::exec('INSERT IGNORE INTO bpr_contact_interests (contact_id, interest) VALUES (?, ?)', [$id, $x]);
        }
        // Locations (multi-value)
        $locations = self::normalizeList($in['locations'] ?? [], self::LOCATIONS);
        foreach ($locations as $x) {
            DB::exec('INSERT IGNORE INTO bpr_contact_locations (contact_id, location) VALUES (?, ?)', [$id, $x]);
        }

        // Explicit consent update
        if (!empty($in['marketing_consent']) && (!$existing || !$existing['marketing_consent'])) {
            DB::exec('UPDATE bpr_contacts SET marketing_consent = 1, consent_at = ?, consent_source = ? WHERE id = ?',
                [Utils::nowUtc(), Utils::s($in['consent_source'] ?? 'form', 80), $id]);
        }

        // Recompute segments + status
        Segmenter::recompute($id);

        $row = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [$id]);
        return $row ?? [];
    }

    private static function enum($v, array $allowed, $default) {
        return in_array($v, $allowed, true) ? $v : $default;
    }

    private static function normalizeList($v, array $allowed): array {
        if (is_string($v)) $v = array_map('trim', explode(',', $v));
        if (!is_array($v)) return [];
        $out = [];
        foreach ($v as $item) {
            $item = is_string($item) ? strtolower(trim($item)) : '';
            if ($item !== '' && in_array($item, $allowed, true)) $out[] = $item;
        }
        return array_values(array_unique($out));
    }

    /** Full profile (contact + interests + locations + last quiz + recent activity). */
    public static function profile(int $id): ?array {
        $c = DB::row('SELECT * FROM bpr_contacts WHERE id = ?', [$id]);
        if (!$c) return null;
        $c['interests'] = DB::rows('SELECT interest, score FROM bpr_contact_interests WHERE contact_id = ?', [$id]);
        $c['locations'] = DB::rows('SELECT location, score FROM bpr_contact_locations WHERE contact_id = ?', [$id]);
        $c['quiz']      = DB::row('SELECT * FROM bpr_quiz_results WHERE contact_id = ? ORDER BY id DESC LIMIT 1', [$id]);
        $c['segments']  = DB::rows(
            'SELECT s.slug, s.name FROM bpr_contact_segments cs JOIN bpr_segments s ON s.id = cs.segment_id WHERE cs.contact_id = ?',
            [$id]
        );
        $c['activities'] = DB::rows(
            'SELECT event, url, title, category, score_delta, created_at
             FROM bpr_activities WHERE contact_id = ? ORDER BY id DESC LIMIT 30',
            [$id]
        );
        return $c;
    }
}
