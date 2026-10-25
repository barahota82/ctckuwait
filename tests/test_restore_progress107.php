<?php
declare(strict_types=1);
define('EP_ROOT',sys_get_temp_dir().'/click-restore107-'.bin2hex(random_bytes(5)));mkdir(EP_ROOT.'/private/files',0700,true);
function jencode($v):string{return json_encode($v,JSON_THROW_ON_ERROR);}
function decode($v):array{return json_decode($v,true,512,JSON_THROW_ON_ERROR);}
function fail($m,$status=422):never{throw new RuntimeException($m);}
require_once __DIR__.'/php_function_fixture.php';
load_production_functions(__DIR__.'/../hr/app/backups.php', ['backup_progress107','backup_observer107','backup_runtime109','backup_batch_limit109','backup_insert_batch_sql109','backup_insert_batch109']);
function backup_config(){return ['max_mb'=>256];}
$checks=0;function check107($v){global $checks;if(!$v)throw new Exception('Assertion failed '.$checks);$checks++;}
class Statement107{function execute($r){$d=db();if($d->failure&&++$d->writes===2){$e=new PDOException('private SQL detail');$e->errorInfo=['23000',2601];throw $e;}$d->rows[]=$r;}function closeCursor(){}}
class DB107{public bool $tx=false,$committed=false,$rolled=false,$failure=false;public int $writes=0;public array $rows=[];function beginTransaction(){$this->tx=true;}function commit(){$this->tx=false;$this->committed=true;}function inTransaction(){return $this->tx;}function rollBack(){$this->tx=false;$this->rolled=true;$this->rows=[];}function prepare($q){return new Statement107();}}
function db(){return $GLOBALS['db107'];}function query($q){check107(str_starts_with($q,'DELETE'));}function backup_schema(){return ['ep_users'=>['columns'=>['id','role','active']],'ep_settings'=>['columns'=>['key']]];}
function backup_stage($p,$k,$observer=null){$dir=EP_ROOT.'/private/restore-'.bin2hex(random_bytes(4));mkdir($dir);$rows=[['table'=>'ep_users','row'=>['id'=>1,'role'=>'admin','active'=>1]],['table'=>'ep_settings','row'=>['key'=>'x']]];file_put_contents($dir.'/rows.ndjson',implode("\n",array_map('jencode',$rows))."\n");backup_progress107($observer,'validate',100,100);return ['dir'=>$dir,'files'=>[],'counts'=>['ep_users'=>1,'ep_settings'=>1],'summary'=>['tables'=>2,'rows'=>2,'files'=>0]];}
function backup_stage_clean($d){@unlink($d.'/rows.ndjson');@rmdir($d);}function backup_exclusive(){}function backup_restore_preflight40(){}function backup_run($a,$r){check107($r==='before_restore');return ['id'=>'safety'];}function backup_insert_sql40($t,$s){return $t;}function backup_budget($s){}function backup_log($a,$b,$c){}
$source=file_get_contents(__DIR__.'/../hr/app/backups.php');$start=strpos($source,'function backup_restore(array');$end=strpos($source,'\nfunction backup_ini_bytes46',$start);if($end===false)$end=strpos($source,"\nfunction backup_ini_bytes46",$start);eval(substr($source,$start,$end-$start));
foreach([false,true]as $failure){if(session_status()!==PHP_SESSION_ACTIVE)session_start();$GLOBALS['db107']=new DB107();db()->failure=$failure;$events=[];
 $observer=function($e)use(&$events){$events[]=$e;if($e['phase']==='complete')check107(db()->committed);};
 try{$r=backup_restore(['id'=>1],'fixture','key',$observer);check107(!$failure&&$r['ok']);}catch(RuntimeException $e){check107($failure);}
 if($failure){check107(db()->rolled&&!db()->committed&&!db()->rows);check107(!in_array('complete',array_column($events,'phase'),true));}else{check107(db()->committed&&!db()->rolled&&count(db()->rows)===2);$insert=array_values(array_filter($events,fn($e)=>$e['phase']==='insert'));check107(array_column($insert,'done')===[0,1,2]);check107(end($events)['phase']==='complete');}
}
if(session_status()!==PHP_SESSION_ACTIVE)session_start();$GLOBALS['db107']=new DB107();backup_restore(['id'=>1],'fixture','key',function(){throw new Exception('observer disconnected');});check107(db()->committed);
$token=bin2hex(random_bytes(32));$observer=backup_observer107($token);backup_progress107($observer,'insert',93,100);$p=EP_ROOT.'/private/restore-progress107-'.$token.'.json';$v=decode(file_get_contents($p));check107($v['phase']==='insert'&&$v['done']===93&&$v['total']===100);check107(array_keys($v)===['phase','done','total','expires']);backup_progress107($observer,'complete',1,1);check107(decode(file_get_contents($p))['phase']==='complete');
try{backup_observer107('../invalid');throw new LogicException('accepted');}catch(RuntimeException $e){check107(true);}
try{backup_observer107($token);throw new LogicException('accepted reuse');}catch(RuntimeException $e){check107(true);}
@unlink($p);@unlink(EP_ROOT.'/private/session-epoch.txt');@rmdir(EP_ROOT.'/private/files');@rmdir(EP_ROOT.'/private');@rmdir(EP_ROOT);echo 'PASS '.$checks.' production restore progress, unchanged rollback, commit-before-complete, observer isolation and private receipt constraints';
