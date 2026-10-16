<?php
require __DIR__ . '/../bootstrap.php';

use BPR\{DB, Utils, Activity, Brevo};

$t = $_GET['t'] ?? '';
$data = Utils::unsign($t);
$msg = 'Invalid or expired unsubscribe link.';
$ok = false;

if ($data && ($data['t'] ?? '') === 'unsub' && !empty($data['c'])) {
    $id = (int) $data['c'];
    $c = DB::row('SELECT id, email FROM bpr_contacts WHERE id = ?', [$id]);
    if ($c) {
        DB::exec('UPDATE bpr_contacts SET marketing_consent = 0, unsubscribed_at = ? WHERE id = ?',
            [Utils::nowUtc(), $id]);
        Activity::log($id, 'unsubscribe', ['meta' => ['source' => 'link']]);
        Brevo::removeFromList($c['email']);
        $ok = true;
        $msg = 'You have been unsubscribed from Beyond Panama Relocation emails.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><title>Unsubscribe · Beyond Panama Relocation</title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; background: #f5f6f7; margin: 0; }
  .card { max-width: 520px; margin: 12vh auto; background: #fff; padding: 40px; border-radius: 14px; box-shadow: 0 2px 20px rgba(0,0,0,.05); text-align:center; }
  h1 { color: #0a3d62; margin-bottom: 12px; }
  a { color: #0a3d62; }
</style>
</head><body>
  <div class="card">
    <h1><?= $ok ? '✓ Unsubscribed' : 'Unsubscribe' ?></h1>
    <p><?= htmlspecialchars($msg, ENT_QUOTES) ?></p>
    <p><a href="/">Return to beyondpanamarelocation.com</a></p>
  </div>
</body></html>
