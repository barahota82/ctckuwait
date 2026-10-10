<?php
declare(strict_types=1);
// Copy on the hosting server ONLY to .env.php, beside config.php.
// Leave APP_INSTALLED false: /hr/install.php seeds the empty SQL Server database.
// Or let the installer create .env.php from the connection fields you enter.
return [
    'APP_INSTALLED' => false,
    'DB_DRIVER' => 'sqlsrv',
    'DB_HOST' => '.\\MSSQLSERVER2022',
    'DB_PORT' => '', // Named instance: empty. TCP host: hosting-provided port.
    'DB_NAME' => '',
    'DB_USER' => '',
    'DB_PASS' => '',
    'DB_TRUST_SERVER_CERTIFICATE' => false,
    'HEALTH_CHECK_KEY' => '', // Optional random secret, at least 32 characters.
    // SMTP credentials are server-only. Mail delivery is not wired in this release.
    'MAIL_ENABLED' => false,
    'SMTP_HOST' => '',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls',
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
    'MAIL_FROM' => '',
    'MAIL_FROM_NAME' => '',
];
