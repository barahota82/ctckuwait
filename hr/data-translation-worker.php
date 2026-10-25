<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app/core.php';
if(!app_installed())exit(1);operation_lock();
for($i=0;$i<4;$i++){$result=data_translation_process160();echo jencode($result).PHP_EOL;if(empty($result['processed']))break;}
exit(in_array($result['state']??'',['failed','unconfigured'],true)?1:0);
