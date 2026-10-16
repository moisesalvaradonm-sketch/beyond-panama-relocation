<?php
namespace BPR;

/**
 * "Where should I live in Panama?" — deterministic scorer.
 * Answers shape (any of the fields may be missing):
 *   {
 *     climate:    "hot"|"mild"|"cool",
 *     lifestyle:  "urban"|"suburban"|"beach"|"mountain"|"island",
 *     healthcare: "high"|"medium"|"low",
 *     spanish:    "high"|"medium"|"low",
 *     remote_work:"yes"|"no",
 *     business:   "yes"|"no",
 *     retirement: "yes"|"no",
 *     budget:     "under_100k"|...,
 *     timeline:   "researching"|... (feeds contact.timeline)
 *   }
 */
class Quiz {
    /** Compute result and store on the contact. Returns [result_key, label, pct, alternates]. */
    public static function scoreAndStore(int $contactId, array $answers): array {
        $scores = [
            'panama_city'    => 0,
            'boquete'        => 0,
            'coronado'       => 0,
            'bocas_del_toro' => 0,
            'pedasi'         => 0,
        ];

        // Climate
        $climate = $answers['climate'] ?? '';
        if ($climate === 'hot')  { $scores['panama_city']+=8; $scores['coronado']+=8; $scores['bocas_del_toro']+=9; $scores['pedasi']+=7; }
        if ($climate === 'mild') { $scores['boquete']+=4; $scores['pedasi']+=6; $scores['coronado']+=5; }
        if ($climate === 'cool') { $scores['boquete']+=10; }

        // Lifestyle
        $ls = $answers['lifestyle'] ?? '';
        $lsMap = [
            'urban'    => ['panama_city' => 12],
            'suburban' => ['coronado' => 10, 'panama_city' => 4],
            'beach'    => ['coronado' => 8, 'pedasi' => 9, 'bocas_del_toro' => 9],
            'mountain' => ['boquete' => 12],
            'island'   => ['bocas_del_toro' => 12],
        ];
        foreach ($lsMap[$ls] ?? [] as $k => $v) $scores[$k] += $v;

        // Healthcare importance
        $hc = $answers['healthcare'] ?? '';
        if ($hc === 'high')   { $scores['panama_city']+=8; $scores['coronado']+=4; }
        if ($hc === 'medium') { $scores['boquete']+=3; $scores['coronado']+=3; }

        // Spanish practice
        $sp = $answers['spanish'] ?? '';
        if ($sp === 'high')   { $scores['pedasi']+=4; $scores['panama_city']+=3; }
        if ($sp === 'low')    { $scores['boquete']+=4; $scores['coronado']+=3; $scores['bocas_del_toro']+=3; }

        // Remote work
        if (($answers['remote_work'] ?? '') === 'yes') { $scores['panama_city']+=5; $scores['boquete']+=4; $scores['bocas_del_toro']+=3; }
        // Business
        if (($answers['business'] ?? '') === 'yes')    { $scores['panama_city']+=8; }
        // Retirement
        if (($answers['retirement'] ?? '') === 'yes')  { $scores['boquete']+=8; $scores['coronado']+=6; $scores['pedasi']+=5; }

        arsort($scores);
        $keys = array_keys($scores);
        $topKey = $keys[0];
        $topScore = $scores[$topKey];
        $maxPossible = 55;
        $pct = min(100, (int) round(($topScore / max(1,$maxPossible)) * 100));

        $labels = [
            'panama_city'    => 'Panama City',
            'boquete'        => 'Boquete',
            'coronado'       => 'Coronado',
            'bocas_del_toro' => 'Bocas del Toro',
            'pedasi'         => 'Pedasí',
        ];
        $alternates = [];
        foreach (array_slice($keys, 1, 2) as $k) {
            $altPct = min(100, (int) round(($scores[$k] / max(1,$maxPossible)) * 100));
            $alternates[] = ['key' => $k, 'label' => $labels[$k], 'pct' => $altPct];
        }

        // Persist result
        DB::insert(
            'INSERT INTO bpr_quiz_results (contact_id, result_key, result_label, match_percent, alternates, answers_json)
             VALUES (?,?,?,?,?,?)',
            [$contactId, $topKey, $labels[$topKey], $pct, json_encode($alternates), json_encode($answers)]
        );

        // Feed answers into the contact record
        $update = [];
        if (($answers['business'] ?? '') === 'yes')     $update['main_interest'] = 'business';
        if (($answers['retirement'] ?? '') === 'yes')   $update['main_interest'] = 'retirement';
        if (!empty($answers['timeline']))               $update['timeline'] = $answers['timeline'];
        if (!empty($answers['budget']))                 $update['budget'] = $answers['budget'];
        if (!empty($update)) {
            $set = [];$vals = [];
            foreach ($update as $k => $v) { $set[] = "$k = ?"; $vals[] = $v; }
            $vals[] = $contactId;
            DB::exec('UPDATE bpr_contacts SET ' . implode(',', $set) . ' WHERE id = ?', $vals);
        }

        // Tag interests + preferred location
        $tags = [];
        if (($answers['retirement'] ?? '') === 'yes')  $tags[] = 'retirement';
        if (($answers['business'] ?? '') === 'yes')    $tags[] = 'business';
        if (($answers['remote_work'] ?? '') === 'yes') $tags[] = 'remote_work';
        foreach ($tags as $t) DB::exec('INSERT IGNORE INTO bpr_contact_interests (contact_id, interest) VALUES (?, ?)', [$contactId, $t]);
        DB::exec('INSERT IGNORE INTO bpr_contact_locations (contact_id, location) VALUES (?, ?)', [$contactId, $topKey]);

        Segmenter::recompute($contactId);

        return ['key' => $topKey, 'label' => $labels[$topKey], 'match' => $pct, 'alternates' => $alternates];
    }
}
