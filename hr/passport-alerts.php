<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app/core.php';
require_once __DIR__.'/app/passport-alerts.php';
if(!app_installed())exit(1);
operation_lock();$result=passport_alerts155();echo jencode($result).PHP_EOL;exit($result['failed']?1:0);
