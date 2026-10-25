<?php
declare(strict_types=1);
$pairModule170=file_get_contents(__DIR__.'/../hr/app/text-pairs.php');$pairModule170=preg_replace('/^require_once .*;$/m','',$pairModule170);eval(substr($pairModule170,5));

require __DIR__.'/test_employee_unified143.php';
function settings(){return[];}function can_manage($u,$id){return $id!==(int)$u['id']&&in_array($id,permission_scope29($u,'users','edit'),true);}
require __DIR__.'/../hr/app/permissions.php';require __DIR__.'/../hr/app/permission-groups.php';
query('ALTER TABLE ep_users ADD COLUMN department TEXT');query('ALTER TABLE ep_users ADD COLUMN job_title TEXT');query('ALTER TABLE ep_users ADD COLUMN staff_type TEXT');query('ALTER TABLE ep_users ADD COLUMN manager_id INTEGER');query("UPDATE ep_users SET department='D',job_title='J',staff_type='office',manager_id=99");query("INSERT INTO ep_users(id,role,hired_on,profile_json,name,department,job_title,staff_type) VALUES(99,'manager','2017-01-01','{}','Delegate','D','Manager','office')");
$u=employee_required(99);$_SESSION=['access_mode29'=>'team'];$GLOBALS['READY29']=true;$GLOBALS['READY30']=true;$GLOBALS['GROUPS29']=['groups'=>[],'members'=>[],'exceptions'=>['99'=>['users'=>['allow'=>['view','edit'],'deny'=>[],'scope'=>'selected','users'=>[1],'company_mode'=>'all','companies'=>[]]]]];
$n134=0;function assertion134($v){$GLOBALS['n134']++;if(!$v)throw new RuntimeException('permission134 '.$GLOBALS['n134']);}
permission_context('user_save',$u,['id'=>1]);permission_gate29('user_save',$u,['id'=>1]);assertion134(permission_has($u,'users','edit'));assertion134(!permission_has($u,'payroll','create'));
try{permission_gate29('user_save',$u,['id'=>2,'user_id'=>1]);throw new LogicException('must reject');}catch(RuntimeException $e){assertion134($e->getMessage()!=='must reject');}
$api=file_get_contents(__DIR__.'/../hr/api.php');$start=strpos($api,"case 'user_save':");$end=strpos($api,"\n    \$id=(int)",$start);$branch=substr($api,$start,$end-$start);
$b=['id'=>1,'name'=>'Allowed Name','department'=>'NewD','job_title'=>'NewJ','salary'=>9999,'profile'=>['iban'=>'Injected','collection_rate130'=>99],'editor134'=>['service'=>['kind'=>'close']],'active'=>0,'password'=>'InjectedPassword'];$original=employee_required(1);$nRecords=(int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn();eval("switch('user_save'){".$branch.'}');$new=employee_required(1);assertion134($result['id']===1);assertion134($new['name']==='Allowed Name'&&$new['department']==='NewD');assertion134($new['profile_json']===$original['profile_json']);assertion134($new['role']===$original['role']);assertion134(!db()->inTransaction());assertion134((int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn()===$nRecords+1);assertion134(service_state($new,'2026-10-20')['department']==='NewD');
// View grant alone cannot use the write route; user_id cannot redirect the target scope.
$GLOBALS['GROUPS29']['exceptions']['99']['users']['allow']=['view'];try{permission_context('user_save',$u,['id'=>1]);throw new LogicException('must reject');}catch(RuntimeException $e){assertion134($e->getMessage()!=='must reject');}
echo 'PASS '.$n134.' V134 actual permission gates and delegated basic writer: submitted id scope, view-only denial, salary/bank/password/service injection ignored, dated job save atomic. SQLite, not live sessions.'.PHP_EOL;
