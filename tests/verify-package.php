<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(PHP_VERSION_ID<80100){fwrite(STDERR,"PHP 8.1+ is required for these verification tools.\n");exit(1);}
$root=dirname(__DIR__);$site=is_dir($root.'/SITE_FILES')?$root.'/SITE_FILES':(is_dir($root.'/site')?$root.'/site':$root);$bad=[];$count=0;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site,FilesystemIterator::SKIP_DOTS))as $file){if($file->getExtension()!=='php')continue;$output=[];$code=0;exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1',$output,$code);$count++;if($code)$bad[]=$file->getPathname();}
if($bad){fwrite(STDERR,'PHP syntax failures: '.implode(', ',$bad)."\n");exit(1);}echo 'PASS PHP syntax: '.$count." files\n";
require __DIR__.'/test_core40.php';
echo "SQL Server/IIS restore and browser verification are still separate required checks.\n";
