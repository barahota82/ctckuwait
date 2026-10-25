<?php
declare(strict_types=1);
// Isolated tests: synthetic rows only. Does not load config.php or connect to a server.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$site=is_dir($root.'/SITE_FILES')?$root.'/SITE_FILES':(is_dir($root.'/site')?$root.'/site':$root);
$cfg40=['preparation_entry40'=>true];$tables40=['ep_events'=>[],'ep_payroll'=>[],'ep_bank_payroll'=>[]];$history40=[];$rules40=[];$audits40=[];$checks40=0;
class Result40 {
 private int $position=0;
 public function __construct(private array $rows){}
 public function fetch():array|false{return $this->rows[$this->position++]??false;}
 public function fetchAll():array{return $this->rows;}
 public function fetchColumn():mixed{$r=$this->fetch();return $r===false?false:array_values($r)[0];}
}
function query(string $sql,array $params=[]):Result40 {
 global $tables40,$history40;
 if(preg_match('/SELECT TOP \(1\) id FROM (ep_\w+) WITH \(HOLDLOCK\)/',$sql,$m))return new Result40($tables40[$m[1]]);
 if(str_starts_with($sql,'SELECT TOP (1) period_key FROM ep_history')){
  $rows=array_values(array_filter($history40,fn($r)=>$r['kind']===$params[0]&&$r['employee_code']===$params[1]&&$r['period_key']<=$params[2]));usort($rows,fn($a,$b)=>strcmp($b['period_key'],$a['period_key']));return new Result40(array_map(fn($r)=>['period_key'=>$r['period_key']],$rows));
 }
 if(str_contains($sql,'SELECT id,body_json FROM ep_history')){
  $kind=str_contains($sql,"kind='salary_history'")?'salary_history':'bank_terms';return new Result40(array_values(array_filter($history40,fn($r)=>$r['kind']===$kind&&$r['employee_code']===$params[0]&&$r['period_key']===$params[1])));
 }
 if(str_starts_with($sql,'UPDATE ep_history SET body_json=? WHERE id=?')){foreach($history40 as &$row)if($row['id']===$params[1])$row['body_json']=$params[0];unset($row);return new Result40([]);}
 throw new RuntimeException('Unexpected SQL in isolated test');
}
function settings():array{global $cfg40;return $cfg40;}
function decode(string $v):array{return json_decode($v,true,512,JSON_THROW_ON_ERROR);}
function jencode(mixed $v):string{return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
function local_time():DateTimeImmutable{return new DateTimeImmutable('2026-10-17');}
function valid_date(?string $v):?string{if($v===null||$v==='')return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)fail('Invalid date');return $v;}
function fail(string $message,int $code=400):never{throw new RuntimeException($message,$code);}
function financial_invalidate(int $id,string $date):void{}
function audit(string $action,string $subject,array $detail=[]):void{global $audits40;$audits40[]=$action;}
function permission_effective29(array $u):array{global $rules40;$out=[];foreach($rules40 as $screen=>$rules){$allow=[];$deny=[];foreach($rules as $r){$allow=array_merge($allow,$r['allow']);$deny=array_merge($deny,$r['deny']??[]);}$out[$screen]=array_values(array_diff($allow,$deny));}return $out;}
function permission_rules29(array $u,string $screen):array{global $rules40;return $rules40[$screen]??[];}
function modules():array{return [];}
function check40(bool $value):void{global $checks40;if(!$value)throw new RuntimeException('Assertion failed');$checks40++;}
function denied40(callable $call):void{try{$call();}catch(RuntimeException $e){check40(true);return;}check40(false);}
require $site.'/hr/app/preparation.php';require $site.'/hr/app/workforce.php';require $site.'/hr/app/salary.php';require $site.'/hr/app/permissions.php';require $site.'/hr/app/administration-records.php';
check40(preparation_entry40());$cfg40['operation_start']='2026-10-17';check40(!preparation_entry40());unset($cfg40['operation_start']);
foreach(array_keys($tables40)as $table){$tables40[$table]=[['id'=>1]];check40(!preparation_entry40());$tables40[$table]=[];}
$cfg40['preparation_entry40']=false;check40(!preparation_entry40());$cfg40['preparation_entry40']=true;
$staff40=['id'=>2,'role'=>'employee','username'=>'EMP-TEST','hired_on'=>'2017-01-01','name'=>'Synthetic','profile_json'=>jencode(['employee_code'=>'EMP-TEST'])];
$history40=[['id'=>1,'kind'=>'salary_history','employee_code'=>'EMP-TEST','period_key'=>'2017-01-01','body_json'=>jencode(['salary'=>100,'fixed_salary'=>true,'effective_from'=>'2017-01-01'])],['id'=>2,'kind'=>'salary_history','employee_code'=>'EMP-TEST','period_key'=>'2020-01-01','body_json'=>jencode(['salary'=>200,'fixed_salary'=>true,'effective_from'=>'2020-01-01'])]];
check40(preparation_effective40($staff40,'salary_history')==='2020-01-01');salary_record($staff40,250,'','',1);check40(count($history40)===2);check40(decode($history40[1]['body_json'])['salary']===250);check40(decode($history40[0]['body_json'])['salary']===100);check40(in_array('initial_salary_corrected',$audits40,true));
$tables40['ep_events']=[['id'=>1]];denied40(fn()=>salary_record($staff40,300,'2020-01-01','',1));$tables40['ep_events']=[];denied40(fn()=>salary_record($staff40,300,'2020-01-01','',1,true,'2019-01-01'));denied40(fn()=>salary_record($staff40,300,'2016-01-01','',1));
$GLOBALS['EP_SERVICE_CACHE'][2]=['periods'=>[['id'=>7,'date_from'=>'2017-01-01','date_to'=>null,'body_json'=>'{}']],'terms'=>[]];
$history40[]=['id'=>3,'kind'=>'bank_terms','employee_code'=>'EMP-TEST','period_key'=>'2017-01-01','body_json'=>jencode(['code'=>'EMP-TEST','effective_from'=>'2017-01-01','name'=>'Synthetic','bank_name'=>'Old'])];
bank_identity_record($staff40,['bank_name'=>'New'],'',1);check40(count($history40)===3);check40(decode($history40[2]['body_json'])['bank_name']==='New');check40(in_array('initial_bank_corrected',$audits40,true));
$tables40['ep_bank_payroll']=[['id'=>1]];denied40(fn()=>bank_identity_record($staff40,['bank_name'=>'Other'],'2017-01-01',1));$tables40['ep_bank_payroll']=[];
$_SESSION=['access_mode29'=>'team'];$manager40=['id'=>2,'role'=>'manager'];denied40(fn()=>work_location_access40($manager40));
$rules40['v28_locations']=[['allow'=>['view','create','edit'],'deny'=>[],'scope'=>'all','company_mode'=>'all']];work_location_access40($manager40,'edit');check40(true);
$rules40['v28_locations'][0]['company_mode']='selected';denied40(fn()=>work_location_access40($manager40,'edit'));$rules40['v28_locations'][0]['company_mode']='all';$rules40['v28_locations'][0]['deny']=['view'];denied40(fn()=>work_location_access40($manager40,'edit'));
$_SESSION['access_mode29']='personal';$rules40['v28_locations'][0]['deny']=[];denied40(fn()=>work_location_access40($manager40,'edit'));work_location_access40(['role'=>'admin'],'edit');check40(true);
check40(isset(permission_catalog()['location_capture40']));echo 'PASS '.$checks40." isolated PHP logic checks\n";
