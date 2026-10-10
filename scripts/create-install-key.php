<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../hr/app/environment.php';
if(app_installed()){fwrite(STDERR,"Installation is already locked.\n");exit(1);}
$path=__DIR__.'/../hr/private/install.key';
$handle=fopen($path,'x');
if(!$handle){fwrite(STDERR,"An installation key already exists or the directory is not writable.\n");exit(1);}
$written=fwrite($handle,bin2hex(random_bytes(32))."\n");fclose($handle);@chmod($path,0600);
if($written!==65){fwrite(STDERR,"Could not write installation key.\n");exit(1);}
echo "Installation key created. Read hr/private/install.key privately on the server.\n";
