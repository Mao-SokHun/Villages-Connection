<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/push.php';

$keys = push_generate_vapid_keys();
if ($keys == null) {
    echo "Failed to generate VAPID keys. Run: composer install\n";
    exit(1);
}

echo "Add these to your .env file:\n\n";
echo "VAPID_PUBLIC_KEY=" . $keys['publicKey'] . "\n";
echo "VAPID_PRIVATE_KEY=" . $keys['privateKey'] . "\n";
echo "VAPID_SUBJECT=mailto:" . (getenv('SITE_CONTACT_EMAIL') ?: 'admin@example.com') . "\n";
