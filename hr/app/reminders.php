<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/core.php';require __DIR__.'/modules.php';require __DIR__.'/operations.php';require __DIR__.'/administration-records.php';require __DIR__.'/finance.php';require_once __DIR__.'/salary.php';
if(!app_installed()){fwrite(STDERR,"Install first.\n");exit(1);}
$actor=query("SELECT TOP (1) * FROM ep_users WHERE role='admin' AND active=1 ORDER BY id")->fetch();if(!$actor)exit(1);
require_once __DIR__.'/passport-alerts.php';
operation_lock();$passport=passport_alerts155();$documents=v28_action('v28_document_alerts',$actor,[]);$leave=v26_alerts_run();echo jencode(['documents'=>$documents,'leave'=>$leave,'passport'=>$passport])."\n";
