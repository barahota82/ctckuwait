<?php
declare(strict_types=1);
// Execute production PHP against isolated SQLite; not a SQL Server integration test.
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
function db(){return $GLOBALS['pdo'];}
function query($sql,$args=[]){$sql=preg_replace('/ WITH \((?:UPDLOCK, ?HOLDLOCK|HOLDLOCK)\)/','',$sql);$sql=preg_replace('/SELECT TOP \(1\) /','SELECT ',$sql);$s=db()->prepare($sql);$s->execute($args);return $s;}
function decode($s){return json_decode($s,true,512,JSON_THROW_ON_ERROR);}
function jencode($v){return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function fail($m,$status=422):never {throw new RuntimeException($m,$status);}
function valid_date($v){if(!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v))return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null;}
function utc_now(){return '2026-10-18 08:00:00';}function local_time(){return new DateTimeImmutable('2026-10-18');}
function personal_mode34($u){return $GLOBALS['personal']??false;}
function permission_has($u,$key,$op='view'){return !in_array($op,$GLOBALS['denied']??[],true);}
function permission_require($u,$key,$op='view'){if(!permission_has($u,$key,$op))fail('denied',403);}
function permission_scope29($u,$key,$op='view'){return [1,2,3,4,5];}
function company_allowed30($u,$key,$code,$op='view'){return in_array($code,['A','B'],true)&&permission_has($u,$key,$op);}
function company_choices30($u){return [['code'=>'A','name'=>'Company A']];}function company_by_code($code){return in_array($code,['A','B'],true)?['code'=>$code,'name'=>'Company '.$code]:null;}
function employee_key($u){return 'EMP-'.str_pad((string)$u['id'],4,'0',STR_PAD_LEFT);}
function service_state($u,$date){return ['employed'=>$u['hired_on']<=$date&&(!$u['ended_on']||$date<=$u['ended_on']),'staff_type'=>$u['staff_type'],'job_title'=>$u['job_title'],'company_code'=>'A','from'=>$u['hired_on']];}
function historical_for($uid,$type,$from,$to){return [];}
function current_user(){return ['id'=>99,'role'=>'admin'];}
function v26_payroll_version($p,$u,$event,$note=''){$GLOBALS['versions'][]=$p;}
function audit($a,$s,$v=[]){$GLOBALS['audits'][]=[$a,$s,$v];}
function v26_insert($module,$uid,$from,$to,$body,$status='approved',$amount=0){query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,body_json,status,amount,created_at,updated_at)VALUES(?,?,?,?,?,?,?,?,?,?)',[$module,$uid,$module,$from,$to,jencode($body),$status,$amount,utc_now(),utc_now()]);return (int)db()->lastInsertId();}
query('CREATE TABLE ep_records(id INTEGER PRIMARY KEY AUTOINCREMENT,module TEXT,user_id INTEGER,title TEXT,date_from TEXT,date_to TEXT,body_json TEXT,status TEXT,amount REAL,created_at TEXT,updated_at TEXT)');
query('CREATE TABLE ep_users(id INTEGER PRIMARY KEY,name TEXT,role TEXT,staff_type TEXT,job_title TEXT,hired_on TEXT,ended_on TEXT)');query('CREATE TABLE ep_settings(setting_key TEXT,value_json TEXT)');query("INSERT INTO ep_settings VALUES('commission_rules_lock','true')");
query('CREATE TABLE ep_payroll(id INTEGER PRIMARY KEY,user_id INTEGER,month_key TEXT,status TEXT,calculation_json TEXT)');query('CREATE TABLE ep_bank_payroll(id INTEGER PRIMARY KEY,user_id INTEGER,month_key TEXT,status TEXT)');
foreach([[1,'A','office','مسؤول مبيعات','2017-01-01',null],[2,'B','rep','مندوب','2017-01-01',null],[3,'C','office','موظف','2017-01-01',null],[4,'D','office','مسؤول مبيعات','2017-01-01','2026-09-30'],[5,'E','office','مسؤول مبيعات','2026-10-15',null],[6,'Outside','office','مسؤول مبيعات','2017-01-01',null]]as $r)query("INSERT INTO ep_users VALUES(?,?,'employee',?,?,?,?)",$r);
require __DIR__.'/../hr/app/allowances.php';require __DIR__.'/../hr/app/commissions.php';require __DIR__.'/../hr/app/payroll-settlement.php';require __DIR__.'/../hr/app/finance-daily.php';

function admin_only($u){if($u['role']!=='admin')fail('admin only');}
function days_between($a,$b){$out=[];for($d=new DateTimeImmutable($a);$d<=new DateTimeImmutable($b);$d=$d->modify('+1 day'))$out[]=$d->format('Y-m-d');return $out;}
function salary_at($u,$date){return ['fixed_salary'=>true];}
query("ALTER TABLE ep_users ADD COLUMN profile_json TEXT DEFAULT '{}'");
require_once __DIR__.'/../hr/app/payroll-grid.php';
$admin=['id'=>99,'role'=>'admin'];$b=['user_id'=>1,'company_code'=>'B','month'=>'2026-09','from'=>'2026-08-01','amount'=>500];
function test114($yes){if(!$yes)throw new Exception('incentive assertion');}
function deny114($fn){try{$fn();}catch(Throwable $e){return;}throw new Exception('expected denial');}
incentive_action114('incentive114_terms',$admin,$b);$rows=incentive_rows114($admin,'2026-09');test114(count($rows)===1&&$rows[0]['amount']===500.0);$grid=incentive_grid_row114($rows[0]);test114($grid['net']===500.0&&$grid['calculation']['salary']===0&&$grid['can_debt']===false);
incentive_action114('incentive114_approve',$admin,$b);incentive_action114('incentive114_approve',$admin,$b);test114((int)query("SELECT COUNT(*) FROM ep_records WHERE module='company_incentive_month114'")->fetchColumn()===1);
incentive_action114('incentive114_pay',$admin,$b+['payment_date'=>'2026-10-18','reference'=>'Receipt 1']);incentive_action114('incentive114_pay',$admin,$b+['payment_date'=>'2026-10-18','reference'=>'Receipt 1']);$r=incentive_rows114($admin,'2026-09')[0];test114($r['paid']&&$r['reference']==='Receipt 1');
deny114(fn()=>incentive_action114('incentive114_terms',$admin,$b));deny114(fn()=>incentive_action114('incentive114_terms',['id'=>1,'role'=>'employee'],$b));deny114(fn()=>incentive_action114('incentive114_terms',$admin,array_replace($b,['company_code'=>'A'])));
test114((int)query('SELECT COUNT(*) FROM ep_payroll')->fetchColumn()===0);test114((int)query("SELECT COUNT(*) FROM ep_records WHERE module IN ('salary_history','leave_cash','eos')")->fetchColumn()===0);
$sheet=pay37_sheet($admin,'payroll','2026-09','B');test114(count($sheet['rows'])===1&&$sheet['rows'][0]['company_incentive114']&&$sheet['rows'][0]['net']===500.0);
echo "PASS independent company entitlement, monthly approval and payment idempotency, overlap/primary-company/authority guards, no base salary/leave/EOS rows";

$_GET=['user_id'=>1];$terms115=incentive_action114('incentive114_info',$admin,[]);test114(count($terms115['terms'])===1&&$terms115['terms'][0]['from']==='2026-08-01');test114(count(incentive_rows114($admin,'2026-08'))===1&&count(incentive_rows114($admin,'2026-10'))===1);deny114(fn()=>incentive_action114('incentive114_info',['id'=>1,'role'=>'employee'],[]));unset($_GET['user_id']);echo ' PASS employee terms listing, recurring months without repeated entry, authority guard';
