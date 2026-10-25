<?php
declare(strict_types=1);
require __DIR__.'/../hr/app/workspace-page.php';require __DIR__.'/../hr/app/evaluation.php';
$records=[];$revision=1;$writes=0;$calcs=0;$batches=0;$sequence=0;$people=[];foreach(range(1,57)as $i)$people[]=['id'=>$i,'name'=>'Employee '.$i,'username'=>'EMP-'.str_pad((string)$i,4,'0',STR_PAD_LEFT),'role'=>'employee','profile_json'=>'{}'];
function fail($s){throw new Exception($s);}function local_time(){return new DateTimeImmutable('2026-10-24');}function utc_now(){return '2026-10-24 00:00:00';}function decode($s){return json_decode($s,true)??[];}function jencode($x){return json_encode($x);}function settings(){return ['timezone'=>'Asia/Kuwait'];}function employee_key($s){return $s['username'];}function scope_ids($a){return range(1,57);}function employee_scope69($ids,$id){return $id?array_values(array_intersect($ids,[$id])):$ids;}function service_prefetch($p){}function modules(){return ['evaluation'=>['approval'=>true]];}function record_search_normalize32($s){return strtolower(trim($s));}function attendance_prefetch113($p,$f,$t){$GLOBALS['batches']++;}function attendance_report($id,$f,$t){$GLOBALS['calcs']++;return [['configured'=>true,'scheduled'=>true,'events'=>[['event_type'=>'attendance'],['event_type'=>'checkout']],'absence'=>false,'late_minutes'=>7,'early_minutes'=>0,'missing'=>0]];}function audit($a,$s,$d=[]){}
function db(){static $db;return $db??=new class{public $trans=false;function beginTransaction(){$this->trans=true;}function commit(){$this->trans=false;}function inTransaction(){return $this->trans;}};}
function query($sql,$args=[]){global $records,$people,$revision,$writes,$sequence;$rows=[];$scalar=false;
 if(str_contains($sql,'SELECT * FROM ep_users WHERE id IN'))$rows=array_values(array_filter($people,fn($r)=>in_array($r['id'],$args,true)));
 elseif(str_starts_with($sql,'SELECT COALESCE(MAX(id),0) FROM ep_audit'))$scalar=$revision;
 elseif(str_contains($sql,'COUNT_BIG(*)'))$rows=[['n'=>1,'last_id'=>1,'changed'=>'fixed']];
 elseif(str_starts_with($sql,'SELECT * FROM ep_records WHERE module=\'evaluation\' AND user_id IN'))$rows=array_values($records);
 elseif(str_starts_with($sql,'SELECT TOP (1) * FROM ep_records'))$rows=isset($records[$args[0]])?[$records[$args[0]]]:[];
 elseif(str_starts_with($sql,'SELECT * FROM ep_records WHERE user_id IN'))$rows=[];
 elseif(str_starts_with($sql,'SELECT id FROM ep_users WITH')){}
 elseif(str_starts_with($sql,'MERGE INTO ep_settings')){}
 elseif(str_starts_with($sql,'SELECT value_json FROM ep_settings'))$scalar=$sequence;
 elseif(str_starts_with($sql,'UPDATE ep_settings'))$sequence=(int)$args[0];
 elseif(str_starts_with($sql,'UPDATE ep_records SET body_json')){foreach($records as &$r)if($r['id']===$args[2])$r['body_json']=$args[0];unset($r);$writes++;}
 elseif(str_starts_with($sql,'UPDATE ep_records SET title')){foreach($records as &$r)if($r['id']===$args[1])$r['title']=$args[0];unset($r);}
 elseif(str_starts_with($sql,'SELECT * FROM ep_records WHERE id='))$rows=array_values(array_filter($records,fn($r)=>$r['id']===$args[0]));
 else throw new Exception('Unhandled SQL: '.$sql);
 return new class($rows,$scalar){function __construct(private $rows,private $scalar){}function fetchAll(){return $this->rows;}function fetch(){return $this->rows[0]??false;}function fetchColumn(){return $this->scalar;}};
}
function v26_insert($module,$uid,$from,$to,$body,$status){$GLOBALS['writes']++;$id=1000+$uid;$GLOBALS['records'][$uid]=['id'=>$id,'user_id'=>$uid,'module'=>$module,'title'=>'','date_from'=>$from,'date_to'=>$to,'status'=>$status,'body_json'=>jencode($body)];return $id;}
function ck($v,$s){if(!$v)throw new Exception($s);}
$_SERVER['REQUEST_METHOD']='GET';$_GET=['kind182'=>'monthly','year182'=>2026,'part182'=>10,'page195'=>jencode(['size'=>'50','page'=>0,'filters'=>['route'=>'evaluation']])];
$a=evaluation_system194(['id'=>99]);ck(count($a)===50&&$calcs===50&&$batches===1,'first page batches only fifty calculations');ck($GLOBALS['PAGE195_READY']['total']===57,'total roster');$reference=decode($a[0]['body_json'])['reference185'];$before=$writes;
$b=evaluation_system194(['id'=>99]);ck($calcs===50&&$writes===$before&&$batches===1,'second read must not calculate/write snapshots');ck(decode($b[0]['body_json'])['reference185']===$reference,'stable reference');
$revision++;$c=evaluation_system194(['id'=>99]);ck($calcs===100&&$writes===$before+50,'source revision invalidates counts');ck(decode($c[0]['body_json'])['reference185']===$reference,'refresh preserves reference');ck(decode($c[0]['body_json'])['metrics182']['score']===null,'unapproved weights remain absent');
$_GET['page195']=jencode(['size'=>'50','page'=>1,'filters'=>['route'=>'evaluation']]);$d=evaluation_system194(['id'=>99]);ck(count($d)===7&&$calcs===107,'next page calculates remaining seven only');
echo "PASS evaluation snapshots: 57 staff, 50/7 paging, one batch/page, fresh read zero calculations/writes, revision invalidation, stable reference and no score\n";
