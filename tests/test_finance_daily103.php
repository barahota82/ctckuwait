<?php
declare(strict_types=1);
// Execute production PHP against isolated SQLite; not a SQL Server integration test.
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
function db(){return $GLOBALS['pdo'];}
function query($sql,$args=[]){$GLOBALS['query_count119']=($GLOBALS['query_count119']??0)+1;$sql=preg_replace('/ WITH \((?:UPDLOCK, ?HOLDLOCK|HOLDLOCK)\)/','',$sql);$sql=preg_replace('/SELECT TOP \(1\) /','SELECT ',$sql);$s=db()->prepare($sql);$s->execute($args);return $s;}
function decode($s){return json_decode($s,true,512,JSON_THROW_ON_ERROR);}
function jencode($v){return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function fail($m,$status=422):never {throw new RuntimeException($m,$status);}
function valid_date($v){if(!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v))return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null;}
function utc_now(){return '2026-10-18 08:00:00';}function local_time(){return new DateTimeImmutable($GLOBALS['TEST_NOW120']??'2026-10-18');}
function personal_mode34($u){return $GLOBALS['personal']??false;}
function permission_has($u,$key,$op='view'){return !in_array($op,$GLOBALS['denied']??[],true);}
function permission_require($u,$key,$op='view'){if(!permission_has($u,$key,$op))fail('denied',403);}
function permission_scope29($u,$key,$op='view'){return [1,2,3,4,5];}
function company_allowed30($u,$key,$code,$op='view'){return in_array($code,['A','CMP-0001'],true)&&permission_has($u,$key,$op);}
function company_choices30($u){return [['code'=>'A','name'=>'Company A'],['code'=>'CMP-0001','name'=>'كليك تكنولوجي']];}function company_by_code($code){return in_array($code,['A','CMP-0001'],true)?['code'=>$code,'name'=>$code==='CMP-0001'?'كليك تكنولوجي':'Company A']:null;}
function employee_key($u){return 'EMP-'.str_pad((string)$u['id'],4,'0',STR_PAD_LEFT);}
function service_state($u,$date){return ['employed'=>$u['hired_on']<=$date&&(!$u['ended_on']||$date<=$u['ended_on']),'staff_type'=>$u['staff_type'],'job_title'=>$u['job_title'],'company_code'=>'A'];}
function historical_for($uid,$type,$from,$to){return [];}
function current_user(){return ['id'=>99,'role'=>'admin'];}
function v26_payroll_version($p,$u,$event,$note=''){$GLOBALS['versions'][]=$p;}
function audit($a,$s,$v=[]){$GLOBALS['audits'][]=[$a,$s,$v];}
function v26_insert($module,$uid,$from,$to,$body,$status='approved',$amount=0){query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,body_json,status,amount,created_at,updated_at)VALUES(?,?,?,?,?,?,?,?,?,?)',[$module,$uid,$module,$from,$to,jencode($body),$status,$amount,utc_now(),utc_now()]);return (int)db()->lastInsertId();}
query('CREATE TABLE ep_records(id INTEGER PRIMARY KEY AUTOINCREMENT,module TEXT,user_id INTEGER,title TEXT,date_from TEXT,date_to TEXT,body_json TEXT,status TEXT,amount REAL,created_at TEXT,updated_at TEXT)');
query('CREATE TABLE ep_users(id INTEGER PRIMARY KEY,name TEXT,role TEXT,staff_type TEXT,job_title TEXT,hired_on TEXT,ended_on TEXT)');query('CREATE TABLE ep_settings(setting_key TEXT,value_json TEXT)');query("INSERT INTO ep_settings VALUES('commission_rules_lock','true')");
query('CREATE TABLE ep_payroll(id INTEGER PRIMARY KEY,user_id INTEGER,month_key TEXT,status TEXT,calculation_json TEXT)');query('CREATE TABLE ep_bank_payroll(id INTEGER PRIMARY KEY,user_id INTEGER,month_key TEXT,status TEXT)');
foreach([[1,'A','office','مسؤول مبيعات','2017-01-01',null],[2,'B','rep','مندوب','2017-01-01',null],[3,'C','office','موظف','2017-01-01',null],[4,'D','office','مسؤول مبيعات','2017-01-01','2026-09-30'],[5,'E','office','مسؤول مبيعات','2026-10-15',null],[6,'Outside','office','مسؤول مبيعات','2017-01-01',null]]as $r)query("INSERT INTO ep_users VALUES(?,?,'employee',?,?,?,?)",$r);
query("ALTER TABLE ep_users ADD COLUMN profile_json TEXT DEFAULT '{}'");query('UPDATE ep_users SET profile_json=? WHERE id=3',[jencode(['contract_commission118'=>true])]);
foreach([1,4,5,6] as $id198)query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode(['sales_commission130'=>true,'sales_rate130'=>10,'sales_target130'=>100,'sales_bonus130'=>1,'sales_retro130'=>false]),$id198]);
require __DIR__.'/../hr/app/allowances.php';require __DIR__.'/../hr/app/commissions.php';require __DIR__.'/../hr/app/payroll-settlement.php';require __DIR__.'/../hr/app/finance-daily.php';
$u=['id'=>99,'role'=>'admin'];$checks=0;
function ok($x){if(!$x)throw new RuntimeException('assertion failed #'.($GLOBALS['checks']+1));$GLOBALS['checks']++;}
function rejected($fn){try{$fn();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$GLOBALS['checks']++;return $e->getMessage();}throw new RuntimeException('expected rejection');}
function day($key,$date){return finance_day103($GLOBALS['u'],$key,$date,[1,2,3,4,5]);}
function save_day($key,$date,$rows,$extra=[]){static $i=0;$d=day($key,$extra['source_date']??$date);return finance_action103('finance103_save',$GLOBALS['u'],array_merge(['key'=>$key,'date'=>$date,'rows'=>$rows,'revision'=>$d['revision'],'request_id'=>sprintf('00000000-0000-4000-8000-%012d',++$i)],$extra));}
$key='contract_sales99';$date='2026-09-15';$d=day($key,$date);ok(array_column($d['people'],'user_id')===[1,4]);ok(array_column(day($key,'2026-10-18')['people'],'user_id')===[1,5]);ok(array_column(day('receipt_agents99',$date)['people'],'user_id')===[2]);
v26_insert('commission_rule',1,'2017-01-01',null,['type'=>'sales_agent','mode'=>'progressive','rate'=>10,'threshold'=>100,'upper_rate'=>11]);
$rows=[['user_id'=>1,'delivered'=>120,'returns'=>['2026-08'=>3,'2026-07'=>2]],['user_id'=>4,'delivered'=>'  ','returns'=>[]]];$revision=$d['revision'];
$out=save_day($key,$date,$rows,['request_id'=>'11111111-1111-4111-8111-111111111111']);ok($out['changed']===1);$id=query("SELECT id FROM ep_records WHERE module='finance_daily103'")->fetchColumn();ok(finance_bases103(1,'2026-09')['bases']['sales_agent']===115);ok(pay35_commissions(1,'2026-09')['total']===1165.0);
$same=finance_action103('finance103_save',$u,['key'=>$key,'date'=>$date,'rows'=>$rows,'revision'=>$revision,'request_id'=>'11111111-1111-4111-8111-111111111111']);ok($same['duplicate']===true);
rejected(fn()=>finance_action103('finance103_save',$u,['key'=>$key,'date'=>$date,'rows'=>$rows,'revision'=>$revision,'request_id'=>'22222222-2222-4222-8222-222222222222']));
$rows[0]['delivered']=10;$rows[0]['returns']=[];save_day($key,$date,$rows);ok(query("SELECT id FROM ep_records WHERE module='finance_daily103'")->fetchColumn()===$id);ok(finance_bases103(1,'2026-09')['bases']['sales_agent']===10);
$bad=$rows;$bad[1]['user_id']=6;$bad[1]['delivered']=1;rejected(fn()=>save_day($key,$date,$bad));ok(finance_bases103(1,'2026-09')['bases']['sales_agent']===10);
$rows[0]['delivered']=0;save_day($key,$date,$rows);ok(query('SELECT status FROM ep_records WHERE id=?',[$id])->fetchColumn()==='cancelled');ok(finance_bases103(1,'2026-09')['bases']===[]);
$rows[0]['delivered']=5;save_day($key,$date,$rows);ok(query('SELECT id FROM ep_records WHERE id=?',[$id])->fetchColumn()===$id);save_day($key,'2026-09-16',$rows,['source_date'=>$date]);ok(query('SELECT date_from FROM ep_records WHERE id=?',[$id])->fetchColumn()==='2026-09-16');
save_day('receipt_company99','2026-09-16',[['user_id'=>0,'company_code'=>'CMP-0001','amount'=>'100.125']]);ok(finance_bases103(0,'2026-09')['bases']===[]);ok(!isset(finance_bases103(1,'2026-09')['bases']['collection']));
save_day('receipt_agents99','2026-09-16',[['user_id'=>2,'amount'=>'10.123']]);ok(finance_bases103(2,'2026-09')['bases']['collection']===10.123);
save_day('contract_staff99','2026-09-16',[['user_id'=>3,'delivered'=>5]]);ok(count(finance_bases103(3,'2026-09')['flags'])===1);
query("INSERT INTO ep_payroll VALUES(1,1,'2026-10','approved','{}')");$rows[0]['delivered']=8;rejected(fn()=>save_day($key,'2026-09-16',$rows));ok(finance_bases103(1,'2026-09')['bases']['sales_agent']===5);query('DELETE FROM ep_payroll');
v26_insert('payroll_input35',2,'2026-08-01',null,['collection'=>200,'collection_entered'=>true]);$legacy=finance_legacy103([2],'receipt_agents99','2026-08-01','2026-08-31');ok(count($legacy)===1&&$legacy[0]['date']==='2026-08-31');ok(finance_legacy103([2],'receipt_agents99','2026-08-01','2026-08-30')===[]);rejected(fn()=>save_day('receipt_agents99','2026-08-15',[['user_id'=>2,'amount'=>10]]));
$GLOBALS['denied']=['edit'];rejected(fn()=>save_day($key,'2026-09-16',$rows));$GLOBALS['denied']=[];$GLOBALS['personal']=true;rejected(fn()=>finance_scope103($u,$key));$GLOBALS['personal']=false;
// Moving an existing record between months keeps its identity and recomputes both periods.
$rows[0]['delivered']=8;save_day($key,'2026-10-01',$rows,['source_date'=>'2026-09-16']);ok(query('SELECT date_from FROM ep_records WHERE id=?',[$id])->fetchColumn()==='2026-10-01');ok(finance_bases103(1,'2026-09')['bases']===[]);ok(finance_bases103(1,'2026-10')['bases']['sales_agent']===8);
save_day($key,'2026-10-02',[['user_id'=>1,'delivered'=>1]]);rejected(fn()=>save_day($key,'2026-10-02',$rows,['source_date'=>'2026-10-01']));
// Source clash stops payroll approval; the daily amount is not added twice.
v26_insert('payroll_input35',2,'2026-09-01',null,['collection'=>99,'collection_entered'=>true]);v26_insert('commission_rule',2,'2017-01-01',null,['type'=>'collection','mode'=>'percent','rate'=>1]);query('UPDATE ep_users SET profile_json=? WHERE id=2',[jencode(['collection_commission130'=>true,'collection_rate130'=>1])]);$commission=pay35_commissions(2,'2026-09');ok(count($commission['flags'])===1);ok($commission['lines'][0]['base']===10.123);
rejected(fn()=>pay37_contract_locked($u,employee_required(1),['type'=>'sales_agent'],'2026-10'));
rejected(fn()=>pay35_action('pay35_input_save',$u,['user_id'=>2,'month'=>'2026-09','collection'=>100,'collection_entered'=>true,'revision'=>0]));
foreach([-1,1.2,'hello',INF]as $v)rejected(fn()=>finance_number103($v,true));ok(finance_number103('   ',true)===0.0);rejected(fn()=>finance_number103('1.2345'));ok(count($GLOBALS['audits'])>=7);
require __DIR__.'/../hr/app/backups.php';$schema=backup_schema();ok(count($schema)===26);ok(isset($schema['ep_records'])&&in_array('body_json',$schema['ep_records']['columns'],true));
echo 'PASS '.$checks.' production PHP storage/math checks in isolated SQLite: roles by date, save/edit/same id/zero, atomic rollback, retries, stale revision, month move, company exclusion, collection, pending allocation, locks, legacy month end, permissions and existing backup schema'.PHP_EOL;
