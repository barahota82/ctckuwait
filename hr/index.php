<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
if(!app_installed()){header('Location: install.php');exit;}
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; frame-src https://www.google.com; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>شؤون الموظفين</title><link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link
      href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
      rel="stylesheet"
    /><link rel="stylesheet" href="assets/style.css"></head>
<body><div id="app"><div class="loading">جارٍ فتح مساحة العمل…</div></div><div id="toast" role="status"></div><footer class="portal-privacy"><a href="privacy.php">بيان بيانات الموظفين</a></footer>
<dialog id="modal"><div id="modalContent"></div></dialog><script src="assets/administration.js"></script><script src="assets/cms.js"></script><script src="assets/control.js"></script><script src="assets/customers.js"></script><script src="assets/languages.js"></script><script src="assets/work-locations.js"></script><script src="assets/app.js"></script></body></html>
