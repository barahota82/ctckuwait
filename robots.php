<?php
declare(strict_types=1);
require __DIR__.'/hr/app/core.php';require __DIR__.'/hr/app/public-seo.php';header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: /hr/\nDisallow: /*receipt=\nDisallow: /*preview=\n";
if(app_installed())echo 'Sitemap: '.public_base_url()."sitemap.php\n";
