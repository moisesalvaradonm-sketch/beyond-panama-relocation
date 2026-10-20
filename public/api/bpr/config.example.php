<?php
/**
 * Beyond Panama Relocation — Lead Engine config
 *
 * Copy this file to `config.php` on the server and fill in real credentials.
 * `config.php` MUST NOT be committed to git and is protected from web access
 * by the .htaccess in this directory.
 */

return [
    // ---------- Database (MySQL / MariaDB, from Hostinger hPanel) ----------
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'YOUR_DB_NAME',
        'user' => 'YOUR_DB_USER',
        'pass' => 'YOUR_DB_PASSWORD',
        'charset' => 'utf8mb4',
    ],

    // ---------- Site ----------
    'site' => [
        'base_url'   => 'https://beyondpanamarelocation.com',
        'admin_url'  => 'https://beyondpanamarelocation.com/admin/',
        'allowed_origins' => [
            'https://beyondpanamarelocation.com',
            'https://www.beyondpanamarelocation.com',
        ],
    ],

    // ---------- Brevo ----------
    'brevo' => [
        'api_key' => 'xkeysib-REPLACE_ME',
        'list_id' => 3,                              // Brevo contact list ID
        'sender'  => [
            'name'  => 'Beyond Panama Relocation',
            'email' => 'contact@beyondpanamarelocation.com',
        ],
        'reply_to' => 'contact@beyondpanamarelocation.com',
    ],

    // ---------- Anthropic (AI Content Engine) ----------
    'anthropic' => [
        'api_key' => 'sk-ant-REPLACE_ME',
        'model'   => 'claude-sonnet-5',   // AI writing model
    ],

    // ---------- Security ----------
    'security' => [
        // Random 64+ char string; rotate periodically.
        'app_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_AT_LEAST_64_CHARS',
        // Token required to hit /api/bpr/cron/*.php (put in a Hostinger cron URL).
        'cron_token' => 'CHANGE_ME_CRON_TOKEN',
        // HMAC secret used to sign tracking/unsubscribe URLs.
        'link_secret' => 'CHANGE_ME_LINK_SECRET',
        // If you receive Brevo webhooks, put the secret here.
        'brevo_webhook_secret' => '',
    ],

    // ---------- Feature flags ----------
    'features' => [
        'ai_content_engine' => true,
        'auto_publish_ai'   => false,   // require human approval unless true
        'fact_check_ai'     => true,
    ],
];
