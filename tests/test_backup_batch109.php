<?php
declare(strict_types=1);
define('EP_ROOT',sys_get_temp_dir().'/backup109-'.bin2hex(random_bytes(4)));mkdir(EP_ROOT.'/private',0700,true);
function jencode($v):string{return json_encode($v,JSON_THROW_ON_ERROR);}function decode($v):array{return json_decode($v,true,512,JSON_THROW_ON_ERROR);}function fail($m,$s=422):never{throw new RuntimeException($m);}
require __DIR__.'/../hr/app/backups.php';
$n=0;function ok109($v){global $n;if(!$v)throw new Exception('assert '.$n);$n++;}
foreach(backup_schema()as $table=>$spec){$limit=backup_batch_limit109($spec);$sql=backup_insert_batch_sql109($table,$spec,$limit);ok109($limit<=100);ok109(substr_count($sql,'?')===$limit*count($spec['columns']));ok109(substr_count($sql,'?')<=2000);ok109(str_contains($sql,'VALUES ('));if($spec['identity']){ok109(str_contains($sql,'SET IDENTITY_INSERT [dbo].['.$table.'] ON;'));ok109(str_contains($sql,'SET IDENTITY_INSERT [dbo].['.$table.'] OFF;'));}}
$captured=[];$GLOBALS['fake109']=new class{function prepare($s){$GLOBALS['sql109']=$s;return new class{function execute($a){$GLOBALS['captured']=$a;}function closeCursor(){}};}};function db(){return $GLOBALS['fake109'];}
$spec=['columns'=>['id','value'],'identity'=>true];$rows=[['id'=>'1','value'=>"عربي ' ?"],['id'=>'2','value'=>null]];$statements=[];backup_insert_batch109('ep_test',$spec,$rows,$statements);ok109($GLOBALS['captured']===['1',"عربي ' ?",'2',null]);ok109(!str_contains($GLOBALS['sql109'],'عربي'));ok109(substr_count($GLOBALS['sql109'],'?')===4);
foreach([0,101]as $size){try{backup_insert_batch_sql109('ep_test',$spec,$size);throw new Exception('accepted size');}catch(LogicException $e){ok109(true);}}
file_put_contents(EP_ROOT.'/private/backup-config.json',jencode(['max_mb'=>16]));$budget=backup_runtime109();ok109($budget['max_bytes']===16777216);backup_budget(microtime(true)-60);ok109(true);file_put_contents(EP_ROOT.'/private/backup-config.json',jencode(['max_mb'=>32]));ok109(backup_runtime109()['max_bytes']===16777216);
try{backup_budget(0,16777217);throw new Exception('accepted bytes');}catch(RuntimeException $e){ok109(true);}$GLOBALS['EP_BACKUP_BUDGET109']['deadline']=microtime(true)-1;try{backup_budget(microtime(true));throw new Exception('accepted deadline');}catch(RuntimeException $e){ok109(true);}
unlink(EP_ROOT.'/private/backup-config.json');rmdir(EP_ROOT.'/private');rmdir(EP_ROOT);echo 'PASS '.$n.' production batch SQL bounds, parameter binding, identity wrapper, cached byte limit and operation-wide time budget';
