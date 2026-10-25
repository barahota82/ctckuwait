<?php
declare(strict_types=1);
// Isolated SQLite fixture adapter. No production configuration or connection is loaded.
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$pdo->exec("CREATE TABLE ep_settings(setting_key TEXT PRIMARY KEY,value_json TEXT);CREATE TABLE ep_users(id INTEGER PRIMARY KEY,name TEXT,role TEXT,active INTEGER,manager_id INTEGER,profile_json TEXT);CREATE TABLE ep_records(id INTEGER PRIMARY KEY AUTOINCREMENT,module TEXT,user_id INTEGER,title TEXT,date_from TEXT,date_to TEXT,amount REAL DEFAULT 0,status TEXT,body_json TEXT,file_id INTEGER,created_at TEXT,updated_at TEXT,reviewed_by INTEGER,review_note TEXT);CREATE TABLE ep_events(id INTEGER PRIMARY KEY AUTOINCREMENT,request_id TEXT,user_id INTEGER,event_type TEXT,received_at TEXT,work_date TEXT,latitude REAL,longitude REAL,accuracy REAL,photo_id INTEGER,policy_id INTEGER);");
$grants=[2=>['request'=>['view','create','review'],'evaluation'=>['view','create'],'corporate_document'=>['view','create']],3=>[],4=>['request'=>['view','create','review']]];
foreach([[1,'owner','admin',0],[2,'manager','manager',1],[3,'employee','employee',2],[4,'other manager','manager',1],[5,'another employee','employee',4]]as [$id,$name,$role,$mgr])query('INSERT INTO ep_users VALUES(?,?,?,?,?,?)',[$id,$name,$role,1,$mgr,'{}']);
function db():PDO{return $GLOBALS['pdo'];}
function query(string $sql,array $params=[]):PDOStatement{
 if(str_starts_with($sql,'MERGE INTO ep_settings'))$sql='INSERT OR IGNORE INTO ep_settings(setting_key,value_json) VALUES(?,?)';
 $sql=preg_replace('/ WITH \(UPDLOCK,\s*HOLDLOCK\)/','',$sql);$sql=str_replace('JSON_VALUE(','json_extract(',$sql);$top=str_contains($sql,'TOP (1) ');$sql=str_replace('TOP (1) ','',$sql);if($top)$sql.=' LIMIT 1';$q=db()->prepare($sql);$q->execute($params);return $q;
}
function fail(string $message,int $code=400):never{throw new RuntimeException($message,$code);}
function settings():array{return ['timezone'=>'Asia/Kuwait','locations'=>[['id'=>'office','active'=>true,'latitude'=>0,'longitude'=>0,'radius'=>100]]];}
function local_time():DateTimeImmutable{return new DateTimeImmutable('now',new DateTimeZone('Asia/Kuwait'));}
function utc_now():string{return gmdate('Y-m-d H:i:s');}
function jencode(mixed $v):string{return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
function decode(mixed $v):array{return json_decode($v?:'{}',true,512,JSON_THROW_ON_ERROR);}
function employee_required(int $id):array{$r=query("SELECT * FROM ep_users WHERE id=? AND role<>'admin'",[$id])->fetch();if(!$r)fail('employee');return $r;}
function employee_key(array $u):string{return 'EMP-'.str_pad((string)$u['id'],4,'0',STR_PAD_LEFT);}
function personal_mode34(array $u):bool{return $u['role']!=='admin'&&($_SESSION['access_mode29']??'personal')==='personal';}
function personal_require34(array $u,string $screen,string $op='view'):void{if(!personal_mode34($u))fail('personal',403);}
function permission_has(array $u,string $screen,string $op='view'):bool{return $u['role']==='admin'||in_array($op,$GLOBALS['grants'][$u['id']][$screen]??[],true);}
function permission_require(array $u,string $screen,string $op='view'):void{if(!permission_has($u,$screen,$op))fail('grants',403);}
function permission_scope29(array $u,string $screen,string $op='view'):array{if(personal_mode34($u))return [(int)$u['id']];if(!permission_has($u,$screen,$op))return [];return $u['role']==='admin'?[2,3,4,5]:($u['id']===2?[3]:[5]);}
function admin_only(array $u):void{if($u['role']!=='admin')fail('admin',403);}
function audit(...$args):void{$GLOBALS['audits'][]=$args;}
function notice(...$args):void{$GLOBALS['notices'][]=$args;}
function setting31(string $key,mixed $fallback=[]):mixed{$v=query('SELECT value_json FROM ep_settings WHERE setting_key=?',[$key])->fetchColumn();return $v===false?$fallback:json_decode($v,true);}
function put_setting31(string $key,mixed $value):void{query('INSERT OR REPLACE INTO ep_settings VALUES(?,?)',[$key,jencode($value)]);}
function v26_insert(string $module,int $uid,string $from,?string $to,array $body,string $status='approved',float $amount=0):int{query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,amount,status,body_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)',[$module,$uid,$module,$from,$to,$amount,$status,jencode($body),utc_now(),utc_now()]);return (int)db()->lastInsertId();}
function company_by_code(string $code):bool{return in_array($code,['A','B'],true);}
function company_allowed30(array $u,string $screen,string $code,string $op='view'):bool{return $u['role']==='admin'||$code==='A';}
function scope_ids(array $u):array{return permission_scope29($u,'evaluation');}
function employee_scope69(array $ids,int $selected):array{if($selected&&!in_array($selected,$ids,true))fail('scope',403);return $selected?[$selected]:$ids;}
function attendance_report(int $uid,string $from,string $to):array{return $GLOBALS['facts']??[];}
// Date validation is loaded from the unchanged production implementation.
$core=file_get_contents(__DIR__.'/../hr/app/core.php');$a=strpos($core,'function valid_date(');$z=strpos($core,'function current_user(',$a);eval(substr($core,$a,$z-$a));
require __DIR__.'/../hr/app/workflows.php';

function record_search_normalize32($s){return mb_strtolower(trim($s));}
$checks=0;function ok(bool $yes,string $why):void{global $checks;if(!$yes)throw new Exception('FAIL '.$why);$checks++;}
function reject(callable $fn,string $why):void{global $checks;try{$fn();}catch(RuntimeException $e){if(db()->inTransaction())db()->rollBack();$checks++;return;}throw new Exception('DID NOT REJECT '.$why);}
$owner=query('SELECT * FROM ep_users WHERE id=1')->fetch();$manager=query('SELECT * FROM ep_users WHERE id=2')->fetch();$staff=query('SELECT * FROM ep_users WHERE id=3')->fetch();$stranger=query('SELECT * FROM ep_users WHERE id=4')->fetch();
$_SESSION=['access_mode29'=>'personal'];$start=local_time()->modify('+5 minutes')->format('Y-m-d\TH:i:s');$request=['starts_at'=>$start,'duration'=>30,'reason'=>'fixture'];
$p=permission_action194('permission_request183',$staff,$request);ok($p['state']==='pending'&&$p['revision']===1,'request pending');ok($GLOBALS['notices'][0][0]===2,'direct manager notified');
reject(fn()=>permission_action194('permission_request183',$staff,$request),'overlap');
reject(fn()=>permission_action194('permission_request183',$staff,$request+['user_id'=>5]),'cross employee request');
reject(fn()=>permission_ticket194($staff,['permission_id'=>$p['id'],'kind'=>'exit']),'pending capture');
$_SESSION['access_mode29']='team';reject(fn()=>permission_action194('permission_review183',$stranger,['id'=>$p['id'],'revision'=>1,'decision'=>'approve']+$request),'stranger review');
$review=['id'=>$p['id'],'revision'=>1,'decision'=>'save','starts_at'=>$start,'duration'=>45];$p=permission_action194('permission_review183',$manager,$review);ok($p['state']==='pending'&&$p['allowed_minutes']===45,'manager edit remains pending');
reject(fn()=>permission_action194('permission_review183',$manager,$review),'stale review');$review['revision']=2;$review['decision']='approve';$p=permission_action194('permission_review183',$manager,$review);ok($p['state']==='approved'&&$p['revision']===3,'manager approve');ok(count($GLOBALS['notices'])===2,'save did not notify decision; approval did');
reject(fn()=>permission_action194('permission_review183',$manager,$review),'repeat decision');
$_SESSION['access_mode29']='personal';reject(fn()=>permission_ticket194($staff,['permission_id'=>$p['id'],'kind'=>'exit']),'exit before time');$_GET=['id'=>$p['id']];reject(fn()=>permission_action194('permission_get182',employee_required(5),[]),'cross employee detail');
$open=$p;$open['starts_at']=local_time()->modify('-10 minutes')->format('Y-m-d\TH:i:s');$open['ends_at']=local_time()->modify('+20 minutes')->format('Y-m-d\TH:i:s');$open['exited_at']=local_time()->format('Y-m-d\TH:i:s');ok(permission_state194($open)==='open','open');$open['ends_at']=local_time()->modify('-2 minutes')->format('Y-m-d\TH:i:s');ok(permission_state194($open)==='overdue','overdue');$open['returned_at']=local_time()->format('Y-m-d\TH:i:s');ok(permission_state194($open)==='completed','completed');
foreach([['monthly',2024,2,'2024-02-01','2024-02-29'],['quarterly',2026,1,'2026-01-01','2026-03-31'],['half',2026,2,'2026-07-01','2026-12-31'],['annual',2026,1,'2026-01-01','2026-12-31']]as [$k,$y,$n,$a,$z]){$period=evaluation_period194($k,$y,$n);ok($period['from']===$a&&$period['to']===$z,'calendar '.$k);}
reject(fn()=>evaluation_period194('quarterly',2026,5),'period range');reject(fn()=>permission_clock194('2026-02-30T12:00'),'invalid date');reject(fn()=>permission_timing194(['starts_at'=>$start,'duration'=>1.5]),'fractional duration');
$_SESSION['access_mode29']='team';$types=company_types_action194('review193_types',$owner,[]);ok(count($types)===15&&$types[14]['name']==='آر جي','15 types');$new=company_types_action194('review193_type_save',$owner,['name'=>'fixture doc']);ok(count(company_types194())===16,'add type persists');reject(fn()=>company_types_action194('review193_type_save',$owner,['name'=>' fixture doc ']),'duplicate type');reject(fn()=>company_types_action194('review193_type_save',$owner,['name'=>'أخرى']),'Other separate');
$fa=v26_insert('company_file',1,'2026-01-01',null,['company_code'=>'A','name'=>'main','type'=>'main']);$fb=v26_insert('company_file',1,'2026-01-01',null,['company_code'=>'B','name'=>'branch','type'=>'branch']);
$base=['name'=>'social','company_code'=>'A','document_type_id'=>9,'issued_to'=>'file','issued_file_id'=>$fa,'coverage'=>'selected','coverage_file_ids'=>[$fa]];$copy=$base;company_document_validate194('corporate_document',$copy,[],$owner);ok($copy['document_type']==='شهادة التأمينات الاجتماعية','social stored with type');
$bad=$base;$bad['issued_file_id']=$fb;reject(function()use(&$bad,$owner){company_document_validate194('corporate_document',$bad,[],$owner);},'issuer company mismatch');$bad=$base;$bad['coverage_file_ids']=[$fb];reject(function()use(&$bad,$owner){company_document_validate194('corporate_document',$bad,[],$owner);},'coverage company mismatch');$bad=$base;$bad['issued_to']='company';reject(function()use(&$bad,$owner){company_document_validate194('corporate_document',$bad,[],$owner);},'social issuer required');
$quarter=$base;$quarter['document_type_id']=14;$quarter['report_year']=2026;$quarter['report_quarter']=2;company_document_validate194('corporate_document',$quarter,[],$owner);ok($quarter['report_quarter']===2,'quarter stored');reject(function()use(&$quarter,$owner){company_document_validate194('corporate_document',$quarter,['previous_id'=>3],$owner);},'quarter independent');
$other=$base;$other['document_type_id']='other';$other['other_name']='one off';company_document_validate194('corporate_document',$other,[],$owner);ok(count(company_types194())===16,'Other not added');
$b=['module'=>'custody','title'=>'asset','date_from'=>'2026-10-23','body'=>['return_date'=>'2026-10-22']];reject(function()use(&$b){record_validate194($b);},'return before handover');$b['body']['return_date']='2026-10-24';record_validate194($b);ok($b['body']['custody_state49']==='returned'&&$b['date_to']===null,'custody state');
$b=['module'=>'document','date_from'=>'2026-10-23','date_to'=>'2027-10-23','body'=>['record_category49'=>'certificates','expiry'=>'2027-10-23']];record_validate194($b);ok($b['date_to']===null&&$b['body']['expiry']==='','certificate one date');
$b=['module'=>'request','date_from'=>'2026-10-23','body'=>['record_category49'=>'salary','salary_month'=>'2026-10','request_type'=>'deduction','details'=>'fixture']];record_validate194($b);ok($b['date_from']===null&&$b['amount']===0,'request no irrelevant period');
$b=['module'=>'loan','amount'=>20,'date_from'=>'2026-10-23','body'=>['reason'=>'fixture']];record_validate194($b);ok($b['date_from']===null,'loan no period');
$ev=v26_insert('evaluation',3,'2026-10-01','2026-10-31',['record_category49'=>'performance','reference185'=>'EV-2026-000001']);$b=['module'=>'evaluation','user_id'=>3,'date_from'=>'2026-10-01','date_to'=>'2026-10-31','body'=>['record_category49'=>'improvement','related_record49'=>$ev,'followup_date'=>'2026-10-20']];record_validate194($b);ok($b['body']['related_reference185']==='EV-2026-000001','linked ref generated');$b['user_id']=5;reject(function()use(&$b){record_validate194($b);},'different employee parent');
$_SESSION['access_mode29']='team';$draft=evaluation_action194('evaluation_draft182',$owner,['points'=>['absence'=>2]]);ok($draft['approved']===false&&$draft['points']['absence']===2.0,'draft not activated');reject(fn()=>evaluation_action194('evaluation_draft182',$manager,[]),'manager scoring');
db()->beginTransaction();$a=evaluation_reference194('EV',2026);$b=evaluation_reference194('EV',2026);db()->commit();ok($a!==$b&&$a==='EV-2026-000001','persistent sequence');db()->beginTransaction();evaluation_reference194('EV',2026);db()->rollBack();db()->beginTransaction();$c=evaluation_reference194('EV',2026);db()->commit();ok($c==='EV-2026-000003','rollback sequence');
$_SESSION['access_mode29']='personal';$GLOBALS['facts']=[['configured'=>true,'scheduled'=>true,'leave'=>null,'events'=>[['event_type'=>'checkout']],'absence'=>false,'missing'=>0,'missing_raw194'=>1,'absence_raw194'=>false,'late_minutes'=>0,'early_minutes'=>0]];$period=evaluation_period194('monthly',(int)local_time()->format('Y'),(int)local_time()->format('n'));$m=evaluation_counts194(3,$period);ok($m['values']['missingAttendance']===1,'approved exception does not erase raw missing punch');ok($m['score']===null&&!isset($m['values']['sales']),'no scoring or sales');
echo 'PASS '.$checks." production backend checks\n";

require __DIR__.'/../hr/app/workspace-page.php';
$_SERVER['REQUEST_METHOD']='GET';$_SESSION['access_mode29']='team';
for($i=0;$i<205;$i++)v26_insert('exit_permission194',3,substr($start,0,10),null,['decision'=>'pending','starts_at'=>$start,'ends_at'=>local_time()->modify('+35 minutes')->format('Y-m-d\TH:i:s'),'reason'=>'pagination fixture '.$i,'revision'=>1]);
$_GET=['page195'=>json_encode(['size'=>'50','page'=>1,'filters'=>[]])];$list=permission_action194('permissions182',$manager,[]);ok(count($list['rows195'])===50&&$list['page195']['total']===206,'permission page counts complete authorized set');ok(array_column($list['people195'],'id')===[3],'direct manager roster only');ok(count(workspace_page195('permissions182',$list)['rows195'])===50,'no second paging');
$list=permission_action194('permissions182',$stranger,[]);ok($list['rows195']===[],'other manager cannot see page');
$_SESSION['access_mode29']='personal';$_GET['page195']=json_encode(['size'=>'50','page'=>0,'filters'=>['employee'=>5]]);$list=permission_action194('permissions182',$staff,[]);ok($list['rows195']===[],'personal filter cannot escape owner');
$_GET['page195']=json_encode(['size'=>'50','page'=>0,'filters'=>['query'=>'pagination fixture 204']]);$list=permission_action194('permissions182',$staff,[]);ok(count($list['rows195'])===1,'permission search precedes paging');
echo 'PASS '.$checks." backend workflow and permission paging checks\n";
