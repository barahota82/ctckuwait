<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
require __DIR__.'/app/modules.php';
require __DIR__.'/app/cms.php';
require __DIR__.'/app/administration.php';
require __DIR__.'/app/languages.php';
require __DIR__.'/app/customers.php';
require __DIR__.'/app/backups.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
 if(!app_installed())fail('النظام يحتاج تثبيتًا.',503);
 if(!secure_request())fail('استخدم HTTPS لحماية بيانات الموظفين.',403);
 require_once __DIR__.'/app/salary.php';
session_init();
 $action=(string)($_GET['action']??'bootstrap');
 $b=$_POST;
 if(str_contains($_SERVER['CONTENT_TYPE']??'','application/json'))$b=decode(file_get_contents('php://input'));
 if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$b['csrf']??'')))fail('انتهت صلاحية الصفحة؛ أعد تحميلها.',403);
 $read=['bootstrap','list','users','policies','report','sick_summary','locations','notifications','audit','payroll_list','leave_balance','dashboard','cms_list','cms_messages','companies','history_list','bank_list','leave_overview','permissions_list','customer_departments','customer_cases','customer_case_detail','customer_ratings','languages_info','backup_info'];
 if(!in_array($action,$read,true)&&$_SERVER['REQUEST_METHOD']!=='POST')fail('طريقة الطلب غير مسموحة.',405);
 if($action==='bootstrap'){
  $cfg=settings();$public=array_intersect_key($cfg,array_flip(['company_name','company_name_en','site_url','logo_id','timezone','currency','public_site_title']));
  $u=null;if(isset($_SESSION['uid']))try{$u=public_user(current_user());}catch(Throwable $e){}
  $result=['localization'=>localization_boot($u),'csrf'=>$_SESSION['csrf'],'company'=>$public,'user'=>$u,'modules'=>modules(),'server_time'=>utc_now()];
  if($u){$result['permissions']=$cfg['permissions'][(string)$u['id']]??[];$result['operation']=array_intersect_key($cfg,array_flip(['history_approved','operation_start','attendance_enabled','reports_enabled','payroll_enabled']));$result['settings']=$u['role']==='admin'?$cfg:array_intersect_key($cfg,array_flip(['tracking_enabled','tracking_interval','tracking_stale','timezone','currency','annual_leave_days']));$date=local_time()->format('Y-m-d');$result['upgrade_needed']=$u['role']==='admin'?!ratings_phone_ready():false;$result['policy']=policy_for((int)$u['id'],$date);$result['state']=event_state(query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$u['id'],$date])->fetchAll());}
 }elseif($action==='login'){
  $name=trim((string)($b['username']??''));$key=hash('sha256',strtolower($name).'|'.($_SERVER['REMOTE_ADDR']??''));
  $limit=query('SELECT * FROM ep_login_limits WHERE limit_key=?',[$key])->fetch();
  if($limit&&(int)$limit['blocked_until']>time())fail('محاولات كثيرة؛ انتظر 15 دقيقة.',429);
  $u=query('SELECT * FROM ep_users WHERE username=? AND active=1',[$name])->fetch();
  if(!$u||!password_verify((string)($b['password']??''),$u['password_hash'])){
   query('MERGE INTO ep_login_limits WITH (HOLDLOCK) AS target USING (VALUES (?, 1, 0)) AS source (limit_key, attempts, blocked_until) ON target.limit_key=source.limit_key WHEN MATCHED THEN UPDATE SET attempts=target.attempts+1 WHEN NOT MATCHED THEN INSERT (limit_key, attempts, blocked_until) VALUES (source.limit_key, source.attempts, source.blocked_until);',[$key]);
   query('UPDATE ep_login_limits SET blocked_until=? WHERE limit_key=? AND attempts>=5',[time()+900,$key]);
   fail('بيانات الدخول غير صحيحة.',401);
  }
  query('DELETE FROM ep_login_limits WHERE limit_key=?',[$key]);session_regenerate_id(true);
  $_SESSION['uid']=(int)$u['id'];$_SESSION['auth_fingerprint']=hash('sha256',$u['password_hash']);$_SESSION['last']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));
  audit('login','user:'.$u['id']);$result=['ok'=>true];
 }else{
  $u=current_user();$uid=(int)$u['id'];$cfg=settings();
  permission_context($action,$u,$b);
  if($action==='site_upgrade')$result=site_upgrade($u);elseif(str_starts_with($action,'backup_'))$result=backup_action($action,$u,$b);elseif(str_starts_with($action,'customer_'))$result=customer_action($action,$u,$b);elseif(in_array($action,['languages_info','languages_save','language_select','translation_prepare','translation_process','translation_key_save','public_language_review'],true))$result=language_action($action,$u,$b);elseif(in_array($action,['permissions_list','permissions_save','launch_save'],true))$result=permissions_action($action,$u,$b);elseif(in_array($action,['companies','company_save','history_list','import_preview','import_commit','bank_list','bank_build','bank_save','bank_approve'],true))$result=administration_action($action,$u,$b);elseif(str_starts_with($action,'cms_'))$result=cms_action($action,$u,$b);else switch($action){
   case 'logout':session_destroy();$result=['ok'=>true];break;
   case 'change_password':
    if(!password_verify((string)($b['old_password']??''),$u['password_hash']))fail('كلمة المرور الحالية غير صحيحة.');
    if(strlen((string)($b['new_password']??''))<12)fail('كلمة المرور الجديدة 12 حرفًا على الأقل.');
    query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['new_password'],PASSWORD_DEFAULT),$uid]);$_SESSION['auth_fingerprint']=hash('sha256',query('SELECT password_hash FROM ep_users WHERE id=?',[$uid])->fetchColumn());audit('password_change','user:'.$uid);$result=['ok'=>true];break;
   case 'users':
    if($u['role']==='employee'){$result=[public_user($u)];break;}
    $rows=$u['role']==='admin'?query('SELECT * FROM ep_users ORDER BY name')->fetchAll():(count(array_filter($cfg['permissions'][(string)$uid]??[],fn($g)=>is_array($g)&&in_array('view',$g,true)))?query('SELECT * FROM ep_users WHERE manager_id=? OR id=? ORDER BY name',[$uid,$uid])->fetchAll():[$u]);
    $result=array_map(function($row)use($u,$uid){$v=public_user($row);if($u['role']==='manager'&&(int)$row['id']!==$uid){unset($v['salary']);$v['profile']=permission_has($u,'users')?array_intersect_key($v['profile'],array_flip(['phone','email','nationality','address'])):[];}return $v;},$rows);break;
   case 'user_save':
    if($u['role']!=='admin'){
     permission_require($u,'users','edit');$target=(int)($b['id']??0);if(!$target||!can_manage($u,$target))fail('تعديل الموظف خارج نطاقك.',403);$name=trim((string)($b['name']??''));if(!$name)fail('الاسم مطلوب.');query('UPDATE ep_users SET name=?,department=?,job_title=? WHERE id=?',[$name,mb_substr((string)($b['department']??''),0,100),mb_substr((string)($b['job_title']??''),0,100),$target]);audit('user_basic_edit','user:'.$target);$result=['ok'=>true,'id'=>$target];break;
    }
    $id=(int)($b['id']??0);$name=trim((string)($b['name']??''));$username=trim((string)($b['username']??''));
    if(!$name||!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/',$username))fail('اسم الموظف واسم دخول صحيح مطلوبان.');
    $role=(string)($b['role']??'employee');if(!in_array($role,['admin','manager','employee'],true))fail('الدور غير صالح.');
    $type=(string)($b['staff_type']??'office');if(!in_array($type,['office','rep'],true))fail('نوع الموظف غير صالح.');
    $active=empty($b['active'])?0:1;
    if($id===$uid && (!$active||$role!=='admin'))fail('لا يمكنك تعطيل حسابك أو إزالة دورك الإداري.');
    $manager=(int)($b['manager_id']??0);if($manager&&($manager===$id||!query("SELECT id FROM ep_users WHERE id=? AND role IN ('manager','admin')",[$manager])->fetch()))fail('المدير غير صالح.');
    $hired=valid_date($b['hired_on']??null);
    foreach(['company_code','residence_company_code']as $companyField)if(!empty($b['profile'][$companyField])&&!company_by_code((string)$b['profile'][$companyField]))fail('شركة غير موجودة.');
    if(isset($b['profile']['bank_salary'])&&$b['profile']['bank_salary']!==''&&(!is_numeric($b['profile']['bank_salary'])||(float)$b['profile']['bank_salary']<0))fail('مبلغ التحويل غير صالح.');
    $args=[$username,$name,$role,$manager?:null,mb_substr((string)($b['department']??''),0,100),mb_substr((string)($b['job_title']??''),0,100),$type,$active,empty($b['show_sick'])?0:1,$hired,max(0,(float)($b['salary']??0)),jencode($b['profile']??[])];
    if(!empty($b['password'])&&strlen($b['password'])<12)fail('كلمة المرور 12 حرفًا على الأقل.');
    db()->beginTransaction();
    if($id){$previous=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$previous)fail('الموظف غير موجود.');if($previous['username']!==$username&&query('SELECT id FROM ep_history WHERE employee_code=?',[$previous['username']])->fetch())fail('لا تغيّر كود موظف له بيانات تاريخية.');if(abs((float)$previous['salary']-(float)($b['salary']??0))>.0001||!query("SELECT id FROM ep_history WHERE kind='salary_history' AND employee_code=?",[$username])->fetch())salary_record(array_replace($previous,['username'=>$username]),(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid);query('UPDATE ep_users SET username=?,name=?,role=?,manager_id=?,department=?,job_title=?,staff_type=?,active=?,show_sick=?,hired_on=?,salary=?,profile_json=? WHERE id=?',[...$args,$id]);}
    else{if(strlen((string)($b['password']??''))<12)fail('كلمة مرور الموظف 12 حرفًا على الأقل.');query('INSERT INTO ep_users(username,name,role,manager_id,department,job_title,staff_type,active,show_sick,hired_on,salary,profile_json,password_hash,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[...$args,password_hash($b['password'],PASSWORD_DEFAULT),utc_now()]);$id=(int)db()->lastInsertId();salary_record(['username'=>$username,'hired_on'=>$hired],(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid);}
    if(!empty($b['password'])&&isset($b['id'])){if(strlen($b['password'])<12)fail('كلمة المرور 12 حرفًا على الأقل.');query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['password'],PASSWORD_DEFAULT),$id]);}
    audit('user_save','user:'.$id);db()->commit();$result=['ok'=>true,'id'=>$id];break;
   case 'settings_save':
    admin_only($u);$values=$b['values']??[];if(!is_array($values))fail('الإعدادات غير صالحة.');
    foreach(['customer_routes','offers_languages','offers_default_language','public_language_approvals','public_languages','employee_languages','employee_default_language','admin_default_language','translation_enabled','translation_context','translation_budget','ratings_enabled','ratings_visibility','permissions','history_approved','history_approved_at','history_approved_by','operation_start','attendance_enabled','reports_enabled']as $protected)if(array_key_exists($protected,$values))fail('استخدم شاشة الصلاحيات أو بدء التشغيل لتغيير هذه القيم.');
    if(!empty($values['payroll_enabled'])&&!$cfg['history_approved'])fail('اعتمد البيانات القديمة من شاشة بدء التشغيل أولًا.');
    unset($values['public_footer'],$values['public_ui'],$values['public_original_texts']);
    if(isset($values['timezone'])&&!in_array($values['timezone'],DateTimeZone::listIdentifiers(),true))fail('المنطقة الزمنية غير صالحة.');
    if(isset($values['site_url'])&&$values['site_url']!==''&&!preg_match('~^https://[^\s]+$~',$values['site_url']))fail('رابط الموقع يجب أن يبدأ بـHTTPS.');
    foreach(['sick_full_days','sick_half_days','annual_leave_days','leave_accrual_divisor','retention_days','tracking_interval','tracking_stale','salary_divisor','session_hours','work_hours','overtime_multiplier']as $k)
     if(isset($values[$k])&&(!is_numeric($values[$k])||(float)$values[$k]<0))fail('قيمة الإعداد غير صالحة: '.$k);
    if(isset($values['leave_accrual_mode'])&&!in_array($values['leave_accrual_mode'],['annual','daily'],true))fail('طريقة الاستحقاق غير صالحة.');
    if(isset($values['leave_accrual_divisor'])&&(float)$values['leave_accrual_divisor']<=0)fail('مقسوم الاستحقاق يجب أن يكون موجبًا.');
    foreach(['sick_year','leave_year']as $key)if(isset($values[$key])&&!in_array($values[$key],['calendar','hire'],true))fail('نوع السنة غير صالح.');
    if(isset($values['tracking_interval'])&&(int)$values['tracking_interval']<30)fail('فاصل المتابعة 30 ثانية على الأقل.');
    if(isset($values['salary_divisor'])&&(float)$values['salary_divisor']<=0)fail('مقسوم الراتب يجب أن يكون موجبًا.');
    if(isset($values['work_hours'])&&(float)$values['work_hours']<=0)fail('ساعات العمل يجب أن تكون موجبة.');
    foreach($values['locations']??[]as $loc)if(empty($loc['name'])||!isset($loc['latitude'],$loc['longitude'],$loc['radius'])||abs((float)$loc['latitude'])>90||abs((float)$loc['longitude'])>180||(float)$loc['radius']<=0)fail('موقع الشركة غير صالح.');
    foreach(['public_email']as $k)if(!empty($values[$k])&&!filter_var($values[$k],FILTER_VALIDATE_EMAIL))fail('البريد غير صالح.');
    if(isset($values['public_default_language'])&&!in_array($values['public_default_language'],$cfg['public_languages'],true))fail('اللغة غير صالحة.');
    foreach(['public_hours','public_address','public_slogan','public_site_title','public_portal_label','public_footer','public_ui']as $k)if(isset($values[$k])&&!is_array($values[$k]))fail('حقول اللغات غير صالحة.');
    if($cfg['translation_enabled'])foreach(language_setting_keys()as $key)if(isset($values[$key]['ar'])&&($cfg[$key]['ar']??null)!==$values[$key]['ar'])foreach($cfg['public_languages']as $target)if($target!=='ar')unset($values[$key][$target]);
    db()->beginTransaction();
    foreach($values as $k=>$v)if(array_key_exists($k,default_settings()))query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',[$k,jencode($v)]);
    foreach(['public_site_title','public_slogan','public_hours','public_address']as $key)if(array_key_exists($key,$values)&&$values[$key]!==$cfg[$key]){public_languages_invalidate(['site']);break;}translation_settings_enqueue($values);audit('settings_save','settings',['keys'=>array_keys($values)]);db()->commit();$result=['ok'=>true];break;
   case 'logo_upload':
    admin_only($u);$fid=upload_file($_FILES['file']??[],$uid,true);
    query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('logo_id', ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);",[jencode($fid)]);audit('logo_upload','file:'.$fid);$result=['ok'=>true];break;
   case 'policies':admin_only($u);$result=query('SELECT p.*,u.name AS employee_name FROM ep_policies p LEFT JOIN ep_users u ON u.id=p.user_id ORDER BY effective_from DESC,id DESC')->fetchAll();break;
   case 'leave_overview':
    if($u['role']==='employee')fail('للإدارة فقط.',403);$result=[];foreach(scope_ids($u)as $target){$staff=query('SELECT name FROM ep_users WHERE id=?',[$target])->fetch();$balance=leave_balance($target,$cfg);if($u['role']!=='admin')unset($balance['valuation_salary'],$balance['cash_value'],$balance['salary_effective_from']);$result[]=['id'=>$target,'name'=>$staff['name'],'balance'=>$balance];}break;
   case 'leave_balance':
    $target=(int)($_GET['user_id']??$uid);if(!can_view($u,$target))fail('خارج الصلاحيات.',403);
    $result=leave_balance($target,$cfg);if($u['role']!=='admin'&&$target!==$uid)unset($result['valuation_salary'],$result['cash_value'],$result['salary_effective_from']);break;
   case 'dashboard':
    $date=local_time()->format('Y-m-d');$result=['staff'=>[],'expiring'=>[],'stale'=>[]];
    foreach(scope_ids($u)as $target){
     $staff=query('SELECT id,name,active,staff_type FROM ep_users WHERE id=?',[$target])->fetch();if(!$staff['active'])continue;
     $events=query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$target,$date])->fetchAll();
     $d=$cfg['attendance_enabled']&&$cfg['history_approved']&&$cfg['operation_start']<=$date?attendance_report($target,$date,$date)[0]:['late_minutes'=>0,'missing'=>false,'unclosed_permission'=>false,'absence'=>false];
     $result['staff'][]=['id'=>$target,'name'=>$staff['name'],'state'=>event_state($events),'late'=>$d['late_minutes'],'missing'=>$d['missing'],'unclosed'=>$d['unclosed_permission'],'absence'=>$d['absence']];
     foreach(($target===$uid||permission_has($u,'document')?query("SELECT id,title,body_json FROM ep_records WHERE user_id=? AND module='document' AND status='active'",[$target])->fetchAll():[])as $r){
      $expiry=decode($r['body_json'])['expiry']??'';if($expiry&&$expiry<=(new DateTimeImmutable($date))->modify('+30 days')->format('Y-m-d'))$result['expiring'][]=['name'=>$staff['name'],'title'=>$r['title'],'expiry'=>$expiry];
     }
     if(permission_has($u,'tracking')&&$staff['staff_type']==='rep'&&in_array(event_state($events),['working','away'],true)){
      $loc=query('SELECT TOP (1) received_at FROM ep_locations WHERE user_id=? ORDER BY id DESC',[$target])->fetch();
      if(!$loc||time()-strtotime($loc['received_at'].' UTC')>(int)$cfg['tracking_stale'])$result['stale'][]=$staff['name'];
     }
    }
    break;
   case 'policy_save':
    admin_only($u);$from=valid_date($b['effective_from']??'');$to=valid_date($b['effective_to']??null);if(!$from||($to&&$to<$from))fail('فترة السياسة غير صالحة.');
    $p=array_replace(default_policy(),$b['values']??[]);
    foreach($p['holiday_dates']??[]as $date)valid_date($date);
    foreach(['start','end']as $k)if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$p[$k]))fail('موعد الدوام غير صالح.');
    if($p['end']<=$p['start'])fail('هذه النسخة تتطلب انتهاء الدوام في نفس اليوم بعد بدايته.');
    foreach(['max_accuracy','location_max_age','photo_max_age']as $k)if((float)$p[$k]<=0)fail('حدود الدقة والحداثة يجب أن تكون موجبة.');
    foreach(['grace_minutes','missing_minutes','break_minutes']as $k)if((float)$p[$k]<0)fail('القيمة لا يمكن أن تكون سالبة.');
    foreach($p['forget_tiers']as $tier)if(!isset($tier['from'],$tier['days'])||(int)$tier['from']<1||(float)$tier['days']<0)fail('شريحة النسيان غير صالحة.');
    if(!in_array($p['forget_period'],['month','year'],true)||!in_array($p['forget_unit'],['day','punch'],true)||!in_array($p['penalty_mode'],['replace','add'],true))fail('سياسة النسيان غير صالحة.');
    $p['configured']=true;query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[(int)($b['user_id']??0)?:null,$from,$to,mb_substr((string)($b['label']??'سياسة دوام'),0,150),jencode($p),utc_now()]);
    audit('policy_create','policy:'.db()->lastInsertId());$result=['ok'=>true];break;
   case 'capture_ticket':
    operation_require('attendance');
    $ticket=bin2hex(random_bytes(24));$_SESSION['capture_ticket']=['token'=>$ticket,'at'=>time()];$result=['ticket'=>$ticket,'server_time'=>utc_now()];break;
   case 'event':
    operation_require('attendance');
    $date=local_time()->format('Y-m-d');$policy=policy_for($uid,$date);$p=$policy['values'];
    if(!$p['configured'])fail('الإدارة لم تعتمد جدول دوامك بعد.');
    $rid=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$rid))fail('رقم الطلب غير صالح.');
    db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid]);
    $old=query('SELECT * FROM ep_events WHERE request_id=?',[$rid])->fetch();
    if($old){if((int)$old['user_id']!==$uid)fail('رقم طلب مستخدم.',409);$result=['ok'=>true,'id'=>$old['id'],'received_at'=>$old['received_at'],'duplicate'=>true];db()->commit();break;}
    $events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll();
    $type=(string)($b['event_type']??'');if(!valid_event_transition(event_state($events),$type))fail('الحركة غير متاحة حسب آخر تسجيل.');
    if(approved_day($uid,$date))fail('اليوم مسجل كإجازة معتمدة؛ راجع الإدارة.');
    [$lat,$lon,$acc]=validate_location($b,$p);$inside=false;
    foreach($cfg['locations']as $loc)if(distance_m($lat,$lon,(float)$loc['latitude'],(float)$loc['longitude'])+$acc<=(float)$loc['radius'])$inside=true;
    $permission=null;
    if(!$inside){
     if(!$cfg['locations'])fail('مواقع الشركة لم تضبط بعد.');
     if(!($u['staff_type']==='rep'&&$type==='attendance'&&$p['rep_morning_external'])){
      if($type!=='checkout'||$u['staff_type']!=='rep')fail('يجب التسجيل من نطاق موقع الشركة.');
      $permission=query("SELECT TOP (1) * FROM ep_records WHERE module='external' AND user_id=? AND status='approved' AND date_from=? AND date_to=? ORDER BY id DESC",[$uid,$date,$date])->fetch();
      if(!$permission)fail('الانصراف من الخارج غير معتمد لهذا اليوم.');
     }
    }
    $photo=null;$evidence=null;$require=$u['staff_type']==='rep'?$p['rep_photo']:$p['office_photo'];
    if($require){
     $ticket=$_SESSION['capture_ticket']??[];if(!isset($ticket['token'],$ticket['at'])||!hash_equals($ticket['token'],(string)($b['capture_ticket']??''))||time()-(int)$ticket['at']>(int)$p['photo_max_age'])fail('التقط صورة جديدة من شاشة التسجيل.');
     if(time()-(int)($b['photo_at']??0)>(int)$p['photo_max_age']||time()-(int)($b['photo_at']??0)<-30)fail('التقط صورة جديدة.');
     $photo=upload_file($_FILES['photo']??[],$uid,true);
    }
    if($u['staff_type']==='rep'&&$p['rep_evidence']&&in_array($type,['attendance','checkout'],true))$evidence=upload_file($_FILES['evidence']??[],$uid,true);
    if(historical_for($uid,'attendance',$date,$date))fail('اليوم مؤرشف ولا يقبل بصمة جديدة.');
    $received=utc_now();query('INSERT INTO ep_events(request_id,user_id,event_type,received_at,work_date,latitude,longitude,accuracy,photo_id,evidence_id,policy_id,external_permission_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
     [$rid,$uid,$type,$received,$date,$lat,$lon,$acc,$photo,$evidence,$policy['id'],$permission['id']??null]);
    $id=(int)db()->lastInsertId();unset($_SESSION['capture_ticket']);audit('event','event:'.$id,['type'=>$type]);db()->commit();
    $result=['ok'=>true,'id'=>$id,'received_at'=>$received,'duplicate'=>false];break;
   case 'list':
    $module=(string)($_GET['module']??'');if(!isset(modules()[$module]))fail('القسم غير موجود.');
    $ids=scope_ids($u);$marks=implode(',',array_fill(0,count($ids),'?'));
    $result=query("SELECT TOP (500) r.*,u.name AS employee_name FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE r.module=? AND r.user_id IN ($marks) ORDER BY r.id DESC",[$module,...$ids])->fetchAll();
    if($u['role']!=='admin'){
     $result=array_values(array_filter($result,fn($r)=>(int)$r['user_id']!==$uid||!in_array($module,['discipline','adjustment'],true)||$r['status']==='approved'));
     foreach($result as &$row){$rb=decode($row['body_json']);unset($rb['_sick_days']);$row['body_json']=jencode($rb);}unset($row);
    }
    break;
   case 'record_save':
    $module=(string)($b['module']??'');$meta=modules()[$module]??null;if(!$meta)fail('القسم غير موجود.');
    $target=(int)($b['user_id']??$uid);
    if($u['role']==='employee'&&(!$meta['employee']||$target!==$uid))fail('ليس لديك صلاحية إنشاء هذا السجل.',403);
    if($u['role']!=='employee'&&!can_view($u,$target))fail('الموظف خارج صلاحياتك.',403);
    if(in_array($module,['discipline','adjustment','loan','document','employment'],true)&&$u['role']==='manager'&&$target===$uid)fail('لا يمكن إنشاء قرار إداري لنفسك.');
    $from=valid_date($b['date_from']??null);$to=valid_date($b['date_to']??null);
    if(in_array($module,['leave','sick','external'],true)){if(!$from||!$to)fail('حدد فترة الطلب.');days_between($from,$to);}
    if($module==='loan'&&((float)($b['amount']??0)<=0))fail('حدد مبلغ السلفة.');
    if($module==='external'&&$from!==$to)fail('تصريح الانصراف الخارجي ليوم واحد فقط.');
    $body=$b['body']??[];if(is_string($body))$body=decode($body);
    $clean=[];foreach($meta['fields']as $key=>$label)$clean[$key]=mb_substr((string)($body[$key]??''),0,5000);
    if($module==='external'){
     $p=policy_for($target,$from)['values'];[$lat,$lon,$acc]=validate_location($b,$p);
     $clean['_location']=['latitude'=>$lat,'longitude'=>$lon,'accuracy'=>$acc,'received_at'=>utc_now()];
    }
    $fid=isset($_FILES['file'])?upload_file($_FILES['file'],$uid,$module==='sick'):null;
    if($module==='sick'&&!$fid)fail('صورة شهادة المرضية مطلوبة.');
    $title=trim((string)($b['title']??$meta['label']));if(!$title||mb_strlen($title)>180)fail('عنوان السجل غير صالح.');
    $status=$meta['approval']?'pending':'active';
    query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,amount,status,body_json,file_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
     [$module,$target,$title,$from,$to,max(0,(float)($b['amount']??0)),$status,jencode($clean),$fid,utc_now(),utc_now()]);
    $id=(int)db()->lastInsertId();audit('record_create',$module.':'.$id);
    if($module==='proof')notice($target,'طلب إثبات تواجد',$title.' — افتح المتابعة وأرسل موقعك الحالي.',true);
    if($meta['approval'])foreach(query("SELECT id FROM ep_users WHERE active=1 AND (role='admin' OR id=(SELECT manager_id FROM ep_users WHERE id=?))",[$target])->fetchAll(PDO::FETCH_COLUMN)as $approver)notice((int)$approver,'طلب جديد: '.$meta['label'],$title);
    $result=['ok'=>true,'id'=>$id];break;
   case 'record_review':
    $id=(int)($b['id']??0);$status=(string)($b['status']??'');
    if(!in_array($status,['approved','rejected','revision','closed'],true))fail('القرار غير صالح.');
    db()->beginTransaction();$r=query('SELECT * FROM ep_records WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$r)fail('السجل غير موجود.');
    if(!can_manage($u,(int)$r['user_id']))fail('ليس لديك صلاحية اعتماد هذا الموظف أو نفسك.',403);
    if(!in_array($r['status'],['pending','revision','active'],true))fail('القرار سبق اعتماده؛ لا تعدل التاريخ بصمت.');
    if(modules()[$r['module']]['approval']&&$status==='closed')fail('حدد موافقة أو رفض أو تعديل.');
    $body=decode($r['body_json']);
    if($status==='approved'&&in_array($r['module'],['leave','sick'],true)){
     $target=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']])->fetch();
     if(query("SELECT TOP (1) id FROM ep_records WHERE id<>? AND user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? AND date_to>=?",[$id,$r['user_id'],$r['date_to'],$r['date_from']])->fetch())fail('الفترة تتداخل مع إجازة معتمدة.');
     if($r['module']==='sick'){foreach(historical_for((int)$target['id'],'sick_summary',substr($r['date_from'],0,4),substr($r['date_to'],0,4))as $h)if($r['date_from']<=decode($h['body_json'])['as_of'])fail('الفترة ضمن المرضيات التاريخية المستوردة؛ لا تعتمدها مرة ثانية.');$body['_sick_days']=sick_snapshot($r,$target,$cfg);}
     if($r['module']==='leave'){
      $requested=count(days_between($r['date_from'],$r['date_to']));$groups=[];
      foreach(days_between($r['date_from'],$r['date_to'])as $d){$balance=leave_balance((int)$r['user_id'],$cfg,$d);$key=$balance['year_from'];$groups[$key]=($groups[$key]??0)+1;if($balance['remaining']!==null&&$groups[$key]>$balance['remaining'])fail('رصيد الإجازات غير كافٍ في السنة '.$key);}
      $body['_approved_days']=$requested;
     }
    }
    if($status==='approved'&&$r['module']==='leave_cash'){query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']]);$days=(float)($body['days']??0);$balance=leave_balance((int)$r['user_id'],$cfg,$r['date_from']??local_time()->format('Y-m-d'));if($days<=0||$balance['cash_days']===null||$days>$balance['cash_days'])fail('أيام التسوية تتجاوز الرصيد النقدي أو الرصيد غير محدد.');if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$body['payroll_month']??''))fail('حدد شهر إضافة مبلغ التسوية.');if($balance['valuation_salary']===null)fail('أدخل تاريخ الراتب قبل اعتماد تسوية الإجازة.');$amount=round($days*$balance['valuation_salary']/max(1,(float)$cfg['salary_divisor']),3);$body['_valuation']=['date'=>$balance['valuation_date'],'salary'=>$balance['valuation_salary'],'effective_from'=>$balance['salary_effective_from'],'days'=>$days,'divisor'=>$cfg['salary_divisor'],'amount'=>$amount];query('UPDATE ep_records SET amount=? WHERE id=?',[$amount,$id]);}
    if($status==='approved'&&$r['module']==='loan'&&((float)($body['installment']??0)<=0||!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/',$body['payroll_month']??'')))fail('حدد قسطًا موجبًا وأول شهر سداد صحيحًا.');
    if($status==='approved'&&$r['module']==='external'&&$r['date_from']<local_time()->format('Y-m-d'))fail('لا اعتماد بأثر رجعي للانصراف الخارجي.');
    query('UPDATE ep_records SET status=?,body_json=?,reviewed_by=?,review_note=?,updated_at=? WHERE id=?',[$status,jencode($body),$uid,mb_substr((string)($b['note']??''),0,5000),utc_now(),$id]);
    notice((int)$r['user_id'],'قرار: '.modules()[$r['module']]['label'],$r['title'].' — '.$status.' — '.($b['note']??''),in_array($r['module'],['discipline','external'],true));
    audit('record_review',$r['module'].':'.$id,['status'=>$status,'note'=>$b['note']??'']);db()->commit();$result=['ok'=>true];break;
   case 'record_resubmit':
    db()->beginTransaction();$r=query('SELECT * FROM ep_records WITH (UPDLOCK, HOLDLOCK) WHERE id=? AND user_id=?',[(int)($b['id']??0),$uid])->fetch();
    if(!$r||$r['status']!=='revision'||!modules()[$r['module']]['employee'])fail('الطلب غير متاح للتعديل.');
    $from=valid_date($b['date_from']??null);$to=valid_date($b['date_to']??null);$body=decode($r['body_json']);
    if(in_array($r['module'],['leave','sick','external'],true)){if(!$from||!$to)fail('حدد الفترة.');days_between($from,$to);}
    if($r['module']==='external'){
     if($from!==$to)fail('تصريح ليوم واحد فقط.');
     [$lat,$lon,$acc]=validate_location($b,policy_for($uid,$from)['values']);
     $body['_location']=['latitude'=>$lat,'longitude'=>$lon,'accuracy'=>$acc,'received_at'=>utc_now()];
    }
    $body['_revision_details']=mb_substr((string)($b['details']??''),0,5000);
    query("UPDATE ep_records SET date_from=?,date_to=?,body_json=?,status='pending',updated_at=? WHERE id=?",[$from,$to,jencode($body),utc_now(),$r['id']]);
    audit('record_resubmit','record:'.$r['id']);db()->commit();$result=['ok'=>true];break;
   case 'record_reply':
    $r=query('SELECT * FROM ep_records WHERE id=? AND user_id=?',[(int)($b['id']??0),$uid])->fetch();if(!$r)fail('السجل غير موجود.');
    $body=decode($r['body_json']);$body['_employee_reply']=mb_substr((string)($b['reply']??''),0,5000);
    query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$r['id']]);audit('record_reply','record:'.$r['id']);$result=['ok'=>true];break;
   case 'notifications':$result=query('SELECT TOP (300) * FROM ep_notifications WHERE user_id=? ORDER BY id DESC',[$uid])->fetchAll();break;
   case 'notice_send':
    if($u['role']==='employee')fail('للإدارة فقط.',403);
    $targets=isset($b['user_id'])&&$b['user_id']?(array)[(int)$b['user_id']]:scope_ids($u);
    if(!empty($b['department']))$targets=array_values(array_filter($targets,fn($id)=>query('SELECT department FROM ep_users WHERE id=?',[$id])->fetchColumn()===$b['department']));
    foreach($targets as $target)if(!can_view($u,$target))fail('الموظف خارج الصلاحيات.',403);
    $title=trim((string)($b['title']??''));$body=trim((string)($b['body']??''));if(!$title||!$body||mb_strlen($title)>180||mb_strlen($body)>5000)fail('عنوان ونص الإشعار مطلوبان.');
    db()->beginTransaction();foreach($targets as $target)notice($target,$title,$body,!empty($b['require_ack']));audit('notice_send','notifications',['targets'=>$targets]);db()->commit();$result=['ok'=>true];break;
   case 'notice_read':
    query('UPDATE ep_notifications SET read_at=COALESCE(read_at,?),acknowledged_at=CASE WHEN ?=1 AND require_ack=1 THEN COALESCE(acknowledged_at,?) ELSE acknowledged_at END WHERE id=? AND user_id=?',[utc_now(),empty($b['ack'])?0:1,utc_now(),(int)$b['id'],$uid]);$result=['ok'=>true];break;
   case 'tracking':
    operation_require('attendance');
    if(!$cfg['tracking_enabled'])fail('المتابعة الميدانية غير مفعلة.');
    if($u['staff_type']!=='rep')fail('المتابعة للمندوب فقط.');
    $date=local_time()->format('Y-m-d');$p=policy_for($uid,$date)['values'];
    $state=event_state(query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll());
    if(!in_array($state,['working','away'],true))fail('المتابعة متاحة أثناء الدوام المسجل فقط.');
    [$lat,$lon,$acc]=validate_location($b,$p);$proof=(int)($b['proof_id']??0);
    if($proof&&!query("SELECT id FROM ep_records WHERE id=? AND user_id=? AND module='proof' AND status='active'",[$proof,$uid])->fetch())fail('طلب إثبات التواجد غير متاح.');
    query('INSERT INTO ep_locations(user_id,received_at,latitude,longitude,accuracy,proof_id) VALUES(?,?,?,?,?,?)',[$uid,utc_now(),$lat,$lon,$acc,$proof?:null]);
    if($proof)query("UPDATE ep_records SET status='closed',updated_at=? WHERE id=?",[utc_now(),$proof]);
    $result=['ok'=>true];break;
   case 'locations':
    if($u['role']==='employee')fail('للإدارة فقط.',403);
    $ids=scope_ids($u);$marks=implode(',',array_fill(0,count($ids),'?'));
    if(isset($_GET['user_id'])){
     $target=(int)$_GET['user_id'];if(!can_view($u,$target))fail('خارج الصلاحيات.',403);
     $result=query('SELECT TOP (300) * FROM ep_locations WHERE user_id=? AND received_at>=? ORDER BY id DESC',[$target,gmdate('Y-m-d 00:00:00')])->fetchAll();
    }else{$result=query("SELECT u.id,u.name,l.received_at,l.latitude,l.longitude,l.accuracy FROM ep_users u LEFT JOIN ep_locations l ON l.id=(SELECT MAX(id) FROM ep_locations WHERE user_id=u.id) WHERE u.staff_type='rep' AND u.id IN ($marks)",$ids)->fetchAll();}
    break;
   case 'report':
    $target=(int)($_GET['user_id']??$uid);if(!can_view($u,$target))fail('خارج الصلاحيات.',403);
    operation_require('reports');$from=valid_date($_GET['from']??local_time()->format('Y-m-01'));$to=valid_date($_GET['to']??local_time()->format('Y-m-d'));$result=attendance_report($target,$from,$to);if($u['role']==='employee')foreach($result as &$day){unset($day['sick_paid']);}unset($day);break;
   case 'sick_summary':
    $target=(int)($_GET['user_id']??$uid);if(!can_view($u,$target))fail('خارج الصلاحيات.',403);
    $staff=query('SELECT * FROM ep_users WHERE id=?',[$target])->fetch();
    if($target===$uid&&$u['role']!=='admin'&&!$staff['show_sick'])fail('الإدارة لم تفتح عرض عدد المرضيات لحسابك.',403);
    [$from,$to]=sick_year_bounds(local_time()->format('Y-m-d'),$staff,$cfg);$count=0;$full=0;$half=0;$zero=0;
    foreach(query("SELECT body_json FROM ep_records WHERE user_id=? AND module='sick' AND status='approved'",[$target])->fetchAll()as $r)foreach(decode($r['body_json'])['_sick_days']??[]as $d)if($d['date']>=$from&&$d['date']<=min($to,local_time()->format('Y-m-d'))){$count++;if($d['paid']==1)$full++;elseif($d['paid']==0.5)$half++;else$zero++;}
    $historical=historical_for($target,'sick_summary',substr($from,0,4),substr($from,0,4));if($historical){$h=decode($historical[0]['body_json']);$count+=(int)$h['full']+(int)$h['half']+(int)$h['unpaid'];$full+=(int)$h['full'];$half+=(int)$h['half'];$zero+=(int)$h['unpaid'];}
    $result=($target===$uid&&$u['role']!=='admin')?['used'=>$count]:['used'=>$count,'full'=>$full,'half'=>$half,'unpaid'=>$zero,'year_from'=>$from,'year_to'=>$to];break;
   case 'audit':admin_only($u);$result=query('SELECT TOP (500) a.*,u.name FROM ep_audit a LEFT JOIN ep_users u ON u.id=a.user_id ORDER BY a.id DESC')->fetchAll();break;
   case 'payroll_list':
    if($u['role']==='admin')$result=query('SELECT TOP (500) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id ORDER BY month_key DESC,u.name')->fetchAll();else{$result=query("SELECT TOP (100) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id WHERE p.user_id=? AND p.status='approved' ORDER BY month_key DESC",[$uid])->fetchAll();foreach($result as &$row){$calc=decode($row['calculation_json']);unset($calc['settings'],$calc['days_detail']);$row['calculation_json']=jencode($calc);}unset($row);}foreach(scope_ids($u)as $target)foreach(historical_for($target,'payroll','0000-01','9999-12')as $h){if($u['role']!=='admin'&&$target!==$uid)continue;$v=decode($h['body_json']);$name=query('SELECT name FROM ep_users WHERE id=?',[$target])->fetchColumn();$result[]=['id'=>'history-'.$h['id'],'name'=>$name,'month_key'=>$h['period_key'],'net_amount'=>$v['net'],'status'=>'approved','calculation_json'=>jencode(['salary'=>$v['salary'],'addition'=>$v['addition'],'deduction'=>$v['deduction'],'flags'=>[],'note'=>'كشف تاريخي مستورد؛ لا يعاد حسابه'])];}break;
   case 'payroll_build':
    operation_require('payroll');
    admin_only($u);if(!$cfg['payroll_enabled'])fail('حساب الرواتب غير مفعل.');
    $month=(string)($b['month']??'');if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('الشهر غير صالح.');
    $from=$month.'-01';$to=(new DateTimeImmutable($from))->format('Y-m-t');
    if($to>=local_time()->format('Y-m-d'))fail('أنشئ الكشف بعد انتهاء الشهر.');
    db()->beginTransaction();
    foreach(query("SELECT * FROM ep_users WHERE active=1 AND role<>'admin'")->fetchAll()as $staff){
     $uid2=(int)$staff['id'];query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid2]);if(query("SELECT TOP (1) id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>?",[$uid2,$month])->fetch())fail('اعتمد الكشوف بترتيب الشهور؛ يوجد كشف لاحق معتمد.');$old=query('SELECT * FROM ep_payroll WITH (UPDLOCK, HOLDLOCK) WHERE user_id=? AND month_key=?',[$uid2,$month])->fetch();if($old&&$old['status']==='approved')continue;
     if(historical_for($uid2,'payroll',$month,$month))continue;
     $report=attendance_report($uid2,$from,$to);$days=0.0;$minutes=0;$overtime=0;$flags=[];
     foreach($report as $d){
      if(!$d['configured']){$flags[]=$d['date'].': دوام غير معتمد';continue;}
      if($d['unclosed_permission'])$flags[]=$d['date'].': إذن غير مغلق';
      if($d['absence'])$days++;
      if($d['sick_paid']!==null)$days+=1-(float)$d['sick_paid'];
      $days+=$d['penalty_days'];$minutes+=$d['late_minutes']+$d['early_minutes']+$d['penalty_minutes'];
      $overtime+=$d['overtime_minutes'];
     }
     $salarySnapshot=salary_at($staff,$to);$monthlySalary=$salarySnapshot['salary'];$rate=$monthlySalary/max(1,(float)$cfg['salary_divisor']);$hour=$rate/max(1,(float)$cfg['work_hours']);
     $deduction=$days*$rate+$minutes*$hour/60;$addition=$cfg['overtime_enabled']?$overtime*$hour/60*(float)$cfg['overtime_multiplier']:0;
     $loanItems=[];
     foreach(query("SELECT * FROM ep_records WHERE user_id=? AND status='approved' AND module IN ('discipline','adjustment','loan','leave_cash')",[$uid2])->fetchAll()as $r){
      $body=decode($r['body_json']);
      if($r['module']==='loan'){
       if(($body['payroll_month']??'9999-12')>$month)continue;
       $paid=0.0;
       foreach(query("SELECT calculation_json FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key<?",[$uid2,$month])->fetchAll()as $past)foreach(decode($past['calculation_json'])['loan_items']??[]as $item)if((int)$item['id']===(int)$r['id'])$paid+=(float)$item['amount'];
       $part=min(max(0,(float)$r['amount']-$paid),max(0,(float)($body['installment']??0)));
       $deduction+=$part;$loanItems[]=['id'=>(int)$r['id'],'amount'=>$part];continue;
      }
      if(($body['payroll_month']??'')===$month){if(in_array($r['module'],['adjustment','leave_cash'],true))$addition+=(float)$r['amount'];else$deduction+=(float)$r['amount'];}
     }
     $calc=['salary'=>$monthlySalary,'salary_effective_from'=>$salarySnapshot['effective_from'],'salary_rule'=>'latest_in_month_whole_month','deduction'=>round($deduction,3),'addition'=>round($addition,3),'days'=>$days,'minutes'=>$minutes,'flags'=>$flags,'settings'=>$cfg,'days_detail'=>$report,'loan_items'=>$loanItems,'note'=>'مسودة للمراجعة؛ لا تمثل حكمًا قانونيًا أو تحويل راتب'];
     $net=round($monthlySalary+$addition-$deduction,3);
     query("MERGE INTO ep_payroll WITH (HOLDLOCK) AS target USING (VALUES (?, ?, ?, ?, ?)) AS source (user_id, month_key, calculation_json, net_amount, created_at) ON target.user_id=source.user_id AND target.month_key=source.month_key WHEN MATCHED THEN UPDATE SET calculation_json=source.calculation_json,net_amount=source.net_amount,created_at=source.created_at WHEN NOT MATCHED THEN INSERT (user_id, month_key, calculation_json, net_amount, created_at) VALUES (source.user_id, source.month_key, source.calculation_json, source.net_amount, source.created_at);",[$uid2,$month,jencode($calc),$net,utc_now()]);
    }
    audit('payroll_build','month:'.$month);db()->commit();$result=['ok'=>true];break;
   case 'payroll_approve':
    operation_require('payroll');
    admin_only($u);db()->beginTransaction();$p=query('SELECT * FROM ep_payroll WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[(int)$b['id']])->fetch();if(!$p||$p['status']!=='draft')fail('الكشف غير متاح للاعتماد.');
    query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$p['user_id']]);if(query("SELECT TOP (1) id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>?",[$p['user_id'],$p['month_key']])->fetch())fail('يوجد كشف لاحق معتمد؛ راجع ترتيب الشهور.');
    $snapshot=decode($p['calculation_json']);foreach($snapshot['loan_items']??[]as $item){$loan=query('SELECT amount FROM ep_records WHERE id=?',[$item['id']])->fetch();$paid=0.0;foreach(query("SELECT calculation_json FROM ep_payroll WHERE user_id=? AND status='approved'",[$p['user_id']])->fetchAll()as $past)foreach(decode($past['calculation_json'])['loan_items']??[]as $prior)if((int)$prior['id']===(int)$item['id'])$paid+=(float)$prior['amount'];if(!$loan||$paid+(float)$item['amount']>(float)$loan['amount']+.0001)fail('مسودة السلف قديمة؛ أعد بناء الكشف قبل الاعتماد.');}
    if(decode($p['calculation_json'])['flags'])fail('عالج ملاحظات المسودة قبل الاعتماد.');
    query("UPDATE ep_payroll SET status='approved',approved_by=?,approved_at=? WHERE id=?",[$uid,utc_now(),$p['id']]);
    notice((int)$p['user_id'],'اعتماد كشف الراتب','الشهر '.$p['month_key'].' — الصافي '.$p['net_amount'].' '.$cfg['currency'],true);audit('payroll_approve','payroll:'.$p['id']);db()->commit();$result=['ok'=>true];break;
   default:fail('العملية غير موجودة.',404);
  }
 }
 echo jencode(['data'=>$result]);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
 try{if(app_installed()&&db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}
 if(http_response_code()<400)http_response_code(500);
 $safe=$e instanceof RuntimeException && !($e instanceof PDOException);
 if(!$safe)security_log($e);
 echo jencode(['error'=>$safe?$e->getMessage():'تعذر تنفيذ العملية؛ راجع سجل الخادم.']);
}
