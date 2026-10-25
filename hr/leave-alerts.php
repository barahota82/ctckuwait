<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app/core.php';
require __DIR__.'/app/modules.php';require __DIR__.'/app/administration.php';require __DIR__.'/app/salary.php';
require __DIR__.'/app/operations.php';
if(!app_installed())exit(1);
echo jencode(v26_alerts_run()).PHP_EOL;
