<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
if(!app_installed()){header('Location: install.php');exit;}
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; frame-src https://www.google.com; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
header('Permissions-Policy: camera=(self), geolocation=(self), microphone=(self)');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>شؤون الموظفين</title><link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link
      href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
      rel="stylesheet"
    /><link rel="stylesheet" href="assets/style.css?v=32-align1"><link rel="manifest" href="manifest.php"><meta name="theme-color" content="#0f0f0f"><meta name="apple-mobile-web-app-capable" content="yes"><link rel="apple-touch-icon" href="app-icon.php?size=180"></head>
<body><div id="app"><div class="loading">جارٍ التحميل…</div></div><div id="toast" role="status"></div><footer class="portal-privacy"><a href="privacy.php">بيان بيانات الموظفين</a></footer>
<dialog id="modal"><div id="modalContent"></div></dialog><script defer src="assets/administration.js?v=32-nav2"></script><script defer src="assets/cms.js?v=32"></script><script defer src="assets/control.js?v=32"></script><script defer src="assets/customers.js?v=32"></script><script defer src="assets/languages.js?v=32-header1"></script><script defer src="assets/work-locations.js?v=32"></script><script defer src="assets/backups.js?v=32"></script><script defer src="assets/workforce.js?v=32-nav2"></script><script defer src="assets/compensation.js?v=32-nav2"></script><script defer src="assets/operations.js?v=32-nav2"></script><script defer src="assets/administration-records.js?v=32"></script><script defer src="assets/employee-profile.js?v=32"></script><script defer src="assets/navigation.js?v=32"></script><script defer src="assets/permission-groups.js?v=32-align1"></script><script defer src="assets/supervisors.js?v=32"></script><script defer src="assets/data-language.js?v=32"></script><script defer src="assets/install.js?v=32-header1"></script><script defer src="assets/interface-controls.js?v=32-nav1"></script><script defer src="assets/header.js?v=32-header4"></script><script defer src="assets/app.js?v=32-nav2"></script></body></html>
