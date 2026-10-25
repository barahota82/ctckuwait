<?php
declare(strict_types=1);
// Presence rules, sends, court geofence and salary minutes. SQL Server locks are not emulated.
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
function db(){return $GLOBALS['pdo'];}
function query($sql,$args=[]){$sql=preg_replace('/ WITH \((?:UPDLOCK, ?HOLDLOCK|HOLDLOCK)\)/','',$sql);$top=str_contains($sql,'TOP (1) ');$sql=str_replace('TOP (1) ','',$sql);if($top)$sql.=' LIMIT 1';$q=db()->prepare($sql);$q->execute($args);return $q;}
function fail($m,$c=422):never{throw new RuntimeException($m,$c);}function decode($s){return json_decode($s?:'{}',true)??[];}function jencode($v){return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
function settings(){return ['timezone'=>'Asia/Kuwait','locations'=>$GLOBALS['locations']??[]];}
function utc_now(){return '2026-10-26 07:05:00';}
function local_time(){return new DateTimeImmutable($GLOBALS['PRESENCE_NOW']??'2026-10-26 10:05:00',new DateTimeZone('Asia/Kuwait'));}
function employee_required($id){$s=query('SELECT * FROM ep_users WHERE id=? AND role<>?',[$id,'admin'])->fetch();if(!$s)fail('staff');return $s;}
function personal_mode34($u){return !empty($GLOBALS['personal'])&&($u['role']??'')!=='admin';}
function permission_has($u,$screen,$op='view'){return ($u['role']??'')==='admin'||(int)$u['id']===2;}
function permission_require($u,$screen,$op='view'){if(!permission_has($u,$screen,$op))fail('denied',403);}
function permission_scope29($u,$screen,$op='view'){return [3];}
function service_state($s,$date){return ['employed'=>empty($GLOBALS['unemployed']),'attendance'=>empty($GLOBALS['no_attendance']),'staff_type'=>$s['staff_type']??'rep'];}
function policy_for($uid,$date){$day=(int)(new DateTimeImmutable('2026-10-26'))->format('w');return ['id'=>1,'values'=>['configured'=>true,'weekdays'=>$GLOBALS['weekdays']??[$day],'holiday_dates'=>$GLOBALS['holidays']??[],'max_accuracy'=>100,'location_max_age'=>180,'photo_max_age'=>120]];}
function approved_day($uid,$date){return in_array($date,$GLOBALS['leave_days']??[],true)?['module'=>'leave']:null;}
function valid_date($s){return is_string($s)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)?$s:null;}
function period_start(string $date,string $mode): string {return substr($date,0,$mode==='year'?4:7).($mode==='year'?'-01-01':'-01');}
function notice(...$args){$GLOBALS['notices'][]=$args;}function audit(...$args){$GLOBALS['audits'][]=$args;}
function upload_file($file,$uid,$image=false){$GLOBALS['uploads'][]=$file;return 9;}
function v26_insert($module,$uid,$from,$to,$body,$status='approved',$amount=0){query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,body_json,status,amount,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)',[$module,$uid,$module,$from,$to,jencode($body),$status,$amount,utc_now(),utc_now()]);return (int)db()->lastInsertId();}
function attendance_report($id,$from,$to){return $GLOBALS['report_days']??[];}
$core=file_get_contents(__DIR__.'/../hr/app/core.php');$start=strpos($core,'function distance_m(');$end=strpos($core,'function upload_file(');eval(substr($core,$start,$end-$start));
require __DIR__.'/../hr/app/presence.php';
query('CREATE TABLE ep_users(id INTEGER PRIMARY KEY,name TEXT,role TEXT,staff_type TEXT,manager_id INTEGER,active INTEGER,hired_on TEXT)');
query('CREATE TABLE ep_records(id INTEGER PRIMARY KEY AUTOINCREMENT,module TEXT,user_id INTEGER,title TEXT,date_from TEXT,date_to TEXT,body_json TEXT,status TEXT,amount REAL DEFAULT 0,created_at TEXT,updated_at TEXT)');
query('CREATE TABLE ep_policies(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,value_json TEXT)');
foreach([[1,'owner','admin','office',0],[2,'manager','manager','office',1],[3,'rep','employee','rep',2],[4,'other','manager','office',1]] as $row)query('INSERT INTO ep_users(id,name,role,staff_type,manager_id,active,hired_on) VALUES(?,?,?,?,?,1,?)',[...$row,'2020-01-01']);
$admin=query('SELECT * FROM ep_users WHERE id=1')->fetch();$manager=query('SELECT * FROM ep_users WHERE id=2')->fetch();$rep=query('SELECT * FROM ep_users WHERE id=3')->fetch();$other=query('SELECT * FROM ep_users WHERE id=4')->fetch();
$n=0;function check($ok,$message){if(!$ok)throw new RuntimeException('FAIL: '.$message);$GLOBALS['n']++;}
function reject($fn,$part){try{$fn();}catch(RuntimeException $e){if(db()->inTransaction())db()->rollBack();if(!str_contains($e->getMessage(),$part))throw new RuntimeException('WRONG REJECT: '.$e->getMessage());$GLOBALS['n']++;return;}throw new RuntimeException('DID NOT REJECT: '.$part);}
$rule=['mode'=>'scheduled','capture'=>'location','window_minutes'=>15,'times'=>['14:00','10:00'],'penalty'=>['enabled'=>true,'minutes'=>30,'escalation'=>true,'period'=>'month','tier_unit'=>'minutes','tiers'=>[['from'=>2,'minutes'=>90]]]];
$saved=presence_action('presence_rule_save',$admin,['user_id'=>3,'rule'=>$rule]);
check((int)query('SELECT COUNT(*) FROM ep_policies')->fetchColumn()===0,'rule save does not version the shift');
check((int)query("SELECT COUNT(*) FROM ep_records WHERE module='presence_rule' AND status='active'")->fetchColumn()===1,'one active rule');
$again=presence_action('presence_rule_save',$admin,['user_id'=>3,'rule'=>$rule]);
check($again['id']===$saved['id']&&(int)query("SELECT COUNT(*) FROM ep_records WHERE module='presence_rule'")->fetchColumn()===1,'second save updates the same rule');
check($again['rule']['times']===['10:00','14:00'],'times are ordered');
reject(fn()=>presence_action('presence_rule_save',$other,['user_id'=>3,'rule'=>$rule]),'denied');
$GLOBALS['personal']=true;reject(fn()=>presence_action('presence_rule_save',$rep,['user_id'=>3,'rule'=>$rule]),'مساحة العمل');
$GLOBALS['weekdays']=[0,1,2,3,4,5,6];
$GLOBALS['PRESENCE_NOW']='2026-10-26 10:05:00';$_SESSION=[];$GLOBALS['notices']=[];
presence_notify_open($rep);presence_notify_open($rep);check(count($GLOBALS['notices'])===1,'the open-slot notice is sent once');
$point=['latitude'=>29.37,'longitude'=>47.97,'accuracy'=>10,'location_at'=>time()];
$open=presence_action('presence_send',$rep,['request_id'=>'11111111-1111-4111-8111-111111111111','slot'=>'2026-10-26 10:00']+$point);
check($open['duplicate']===false&&$open['id']>0,'open slot accepts the point');
$retry=presence_action('presence_send',$rep,['request_id'=>'22222222-2222-4222-8222-222222222222','slot'=>'2026-10-26 10:00']+$point);
check($retry['duplicate']===true&&$retry['id']===$open['id'],'a filled slot is not stored twice');
check((int)query("SELECT COUNT(*) FROM ep_records WHERE module='presence_send'")->fetchColumn()===1,'one send row');
$same=presence_action('presence_send',$rep,['request_id'=>'11111111-1111-4111-8111-111111111111','slot'=>'2026-10-26 10:00']+$point);
check($same['duplicate']===true,'same request is idempotent');
reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'33333333-3333-4333-8333-333333333333','slot'=>'2026-10-26 14:00']+$point),'لم يحن');
$GLOBALS['PRESENCE_NOW']='2026-10-26 14:20:00';reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'44444444-4444-4444-8444-444444444444','slot'=>'2026-10-26 14:00']+$point),'انتهت');
$GLOBALS['leave_days']=['2026-10-27'];$GLOBALS['PRESENCE_NOW']='2026-10-27 10:05:00';reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'55555555-5555-4555-8555-555555555555','slot'=>'2026-10-27 10:00']+$point),'لا يوجد موعد');
$GLOBALS['leave_days']=[];$GLOBALS['holidays']=['2026-10-26'];$GLOBALS['PRESENCE_NOW']='2026-10-26 10:05:00';reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'66666666-6666-4666-8666-666666666666','slot'=>'2026-10-26 10:00']+$point),'لا يوجد موعد');
$GLOBALS['holidays']=[];$GLOBALS['no_attendance']=true;reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'77777777-7777-4777-8777-777777777777','slot'=>'2026-10-26 14:00']+$point),'لا يوجد موعد');
$GLOBALS['no_attendance']=false;$GLOBALS['personal']=false;reject(fn()=>presence_action('presence_send',$manager,['request_id'=>'88888888-8888-4888-8888-888888888888','slot'=>'2026-10-26 10:00']+$point),'مساحة الموظف');
$GLOBALS['PRESENCE_NOW']='2026-10-26 15:00:00';$days=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>true,'configured'=>true]],'2026-10-01');
check($days[0]['presence_missed']===1&&$days[0]['presence_penalty_minutes']===30.0,'only the unsent slot is charged at the first tier');
query("DELETE FROM ep_records WHERE module='presence_send'");presence_forget_cache(3);
$both=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>true,'configured'=>true]],'2026-10-01');
check($both[0]['presence_missed']===2&&$both[0]['presence_penalty_minutes']===120.0,'second miss in the month uses the 90 minute tier');
$rest=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>false,'configured'=>true]],'2026-10-01');
check($rest[0]['presence_missed']===0&&$rest[0]['presence_penalty_minutes']===0.0,'a rest day creates no slot and no deduction');
$leave=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>true,'configured'=>true,'leave'=>'leave']],'2026-10-01');
check($leave[0]['presence_missed']===0,'approved leave creates no miss');
$absent=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>true,'configured'=>true,'absence'=>true]],'2026-10-01');
check($absent[0]['presence_missed']===0&&$absent[0]['presence_penalty_minutes']===0.0,'a full absence is not charged again for presence');
$court=['mode'=>'court','capture'=>'location','window_minutes'=>15,'times'=>[],'penalty'=>['enabled'=>true,'minutes'=>180,'escalation'=>false,'period'=>'month','tier_unit'=>'minutes','tiers'=>[]]];
presence_action('presence_rule_save',$admin,['user_id'=>3,'rule'=>$court]);
$courtDay=presence_apply_report(3,[['date'=>'2026-10-26','scheduled'=>true,'configured'=>true]],'2026-10-01');
check($courtDay[0]['presence_missed']===0&&$courtDay[0]['presence_penalty_minutes']===0.0,'court mode has no automatic deduction');
$GLOBALS['locations']=[['id'=>'court-1','name'=>'محكمة العاصمة','latitude'=>29.37,'longitude'=>47.97,'radius'=>100,'active'=>true,'place'=>'court'],['id'=>'work-1','name'=>'المكتب','latitude'=>29.37,'longitude'=>47.97,'radius'=>100,'active'=>true,'place'=>'work']];
$GLOBALS['personal']=true;$GLOBALS['no_attendance']=true;
$visit=presence_action('presence_send',$rep,['request_id'=>'99999999-9999-4999-8999-999999999999']+$point);
check($visit['court_name']==='محكمة العاصمة','a point inside a saved court is accepted');
$second=presence_action('presence_send',$rep,['request_id'=>'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']+$point);
check($second['duplicate']===false&&(int)query("SELECT COUNT(*) FROM ep_records WHERE module='presence_send'")->fetchColumn()===2,'court visits can repeat the same day');
reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb','latitude'=>29.50,'longitude'=>47.50,'accuracy'=>10,'location_at'=>time()]),'داخل نطاق محكمة');
$photo=['mode'=>'scheduled','capture'=>'photo','window_minutes'=>15,'times'=>['10:00'],'penalty'=>['enabled'=>false,'minutes'=>0,'escalation'=>false,'period'=>'month','tier_unit'=>'minutes','tiers'=>[]]];
presence_action('presence_rule_save',$admin,['user_id'=>3,'rule'=>$photo]);
$GLOBALS['no_attendance']=false;$GLOBALS['PRESENCE_NOW']='2026-10-26 10:05:00';
reject(fn()=>presence_action('presence_send',$rep,['request_id'=>'cccccccc-cccc-4ccc-8ccc-cccccccccccc','slot'=>'2026-10-26 10:00']+$point),'الصورة مطلوبة');
$_SESSION['presence_capture']=['token'=>'abc','at'=>time(),'user_id'=>3];$_FILES['photo']=['tmp_name'=>'x','size'=>10,'error'=>0];
$shot=presence_action('presence_send',$rep,['request_id'=>'dddddddd-dddd-4ddd-8ddd-dddddddddddd','slot'=>'2026-10-26 10:00','capture_ticket'=>'abc','photo_at'=>time()]+$point);
check($shot['ok']===true&&(int)decode(query('SELECT body_json FROM ep_records WHERE id=?',[$shot['id']])->fetchColumn())['photo_id']===9,'photo mode stores the captured image');
require __DIR__.'/../hr/app/evaluation.php';
$GLOBALS['report_days']=[['date'=>'2026-10-26','configured'=>true,'scheduled'=>true,'events'=>[],'late_minutes'=>0,'early_minutes'=>0,'absence'=>false,'missing'=>0,'presence_missed'=>2]];
$GLOBALS['EVAL_INPUT195'][3]=['permissions'=>[],'decisions'=>[],'leaves'=>[]];
$counts=evaluation_counts194(3,['from'=>'2026-10-01','to'=>'2026-10-31']);
check($counts['values']['missingPresence']===2&&$counts['score']===null,'missed proofs are a separate evaluation count and the score stays unset');
$GLOBALS['personal']=false;$log=presence_action('presence_log',$manager,['user_id'=>3,'from'=>'2026-10-01','to'=>'2026-10-31']);
check(count($log['sends'])>=1,'the direct manager can read the points');
reject(fn()=>presence_action('presence_log',$other,['user_id'=>3,'from'=>'2026-10-01','to'=>'2026-10-31']),'denied');
echo "PASS presence {$GLOBALS['n']}\n";
