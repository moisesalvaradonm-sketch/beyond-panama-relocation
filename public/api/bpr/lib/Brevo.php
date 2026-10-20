<?php
namespace BPR;

/**
 * Thin Brevo (v3 API) client — transactional email + contact sync.
 * Docs: https://developers.brevo.com/reference
 */
class Brevo {
    public static function sendEmail(array $to, string $subject, string $html, ?string $text = null, array $opts = []): array {
        $cfg = bpr_config()['brevo'];
        $payload = [
            'sender' => [
                'name'  => $opts['sender_name']  ?? $cfg['sender']['name'],
                'email' => $opts['sender_email'] ?? $cfg['sender']['email'],
            ],
            'to'      => [ $to ],
            'subject' => $subject,
            'htmlContent' => $html,
        ];
        if ($text) $payload['textContent'] = $text;
        if (!empty($cfg['reply_to'])) $payload['replyTo'] = ['email' => $cfg['reply_to']];
        if (!empty($opts['headers']))  $payload['headers']  = $opts['headers'];
        if (!empty($opts['tags']))     $payload['tags']     = array_values($opts['tags']);
        if (!empty($opts['params']))   $payload['params']   = $opts['params'];

        return self::call('POST', '/smtp/email', $payload);
    }

    public static function upsertContact(string $email, array $attributes = [], array $listIds = []): array {
        $cfg = bpr_config()['brevo'];
        $ids = $listIds ?: [(int) $cfg['list_id']];
        $payload = [
            'email'           => $email,
            'attributes'      => $attributes,
            'listIds'         => array_values(array_filter($ids)),
            'updateEnabled'   => true,
        ];
        return self::call('POST', '/contacts', $payload);
    }

    public static function removeFromList(string $email, ?int $listId = null): array {
        $cfg = bpr_config()['brevo'];
        $id = $listId ?: (int) $cfg['list_id'];
        return self::call('POST', "/contacts/lists/$id/contacts/remove", ['emails' => [$email]]);
    }

    private static function call(string $method, string $path, ?array $payload = null): array {
        $cfg = bpr_config()['brevo'];
        $ch = curl_init('https://api.brevo.com/v3' . $path);
        $headers = [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $cfg['api_key'],
        ];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false) {
            error_log('[Brevo] cURL ' . $err);
            return ['ok' => false, 'code' => 0, 'error' => $err];
        }
        $body = json_decode($res, true);
        $ok = $code >= 200 && $code < 300;
        if (!$ok) error_log('[Brevo] HTTP ' . $code . ' ' . $res);
        return ['ok' => $ok, 'code' => $code, 'body' => $body];
    }
}
