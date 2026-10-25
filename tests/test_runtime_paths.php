<?php
declare(strict_types=1);
$root=dirname(__DIR__).'/hr';$checks=0;
function assert_path(bool $ok,string $message):void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
foreach(['app','assets'] as $folder)foreach(new DirectoryIterator($root.'/'.$folder) as $p){
 if(!$p->isFile())continue;
 assert_path(!preg_match('/-v\d+(?=\.)|batch\d+(?=\.)/',$p->getFilename()),'Release-numbered runtime file: '.$p->getFilename());
}
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $p){
 if($p->getExtension()!=='php')continue;
 $source=file_get_contents($p->getPathname());
 preg_match_all('/(?:require|include)(?:_once)?\s+__DIR__\s*\.\s*([\'\"])([^\'\"]+)\1/',$source,$matches,PREG_SET_ORDER);
 foreach($matches as $m)assert_path(is_file(dirname($p->getPathname()).'/'.ltrim($m[2],'/')),'Missing PHP dependency: '.$m[2]);
}
preg_match_all('/(?:src|href)="(assets\/[^"?]+)/',file_get_contents($root.'/index.php'),$matches);
foreach($matches[1] as $asset)assert_path(is_file($root.'/'.$asset),'Missing page asset: '.$asset);
$backup=file_get_contents($root.'/app/backups.php');
foreach(['backup_chunk_append104','backup_observer107','backup_insert_batch109'] as $name)assert_path(str_contains($backup,'function '.$name.'('),'Missing consolidated backup function: '.$name);
assert_path(!preg_match('/require(?:_once)?[^;]*backup-(?:upload|progress|batch)/',$backup),'Backup implementation split into release files');
echo 'PASS '.$checks.' stable runtime paths, PHP dependencies, page assets and consolidated backup helpers';
