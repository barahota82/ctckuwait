<?php
declare(strict_types=1);
$apiStarted32=hrtime(true);
require __DIR__.'/app/core.php';
require __DIR__.'/app/modules.php';
require __DIR__.'/app/sick-summary.php';
require __DIR__.'/app/cms.php';
require __DIR__.'/app/administration.php';
require __DIR__.'/app/languages.php';
require __DIR__.'/app/customers.php';
require __DIR__.'/app/backups.php';
require __DIR__.'/app/setup-data.php';
require __DIR__.'/app/corrections.php';
require __DIR__.'/app/payroll.php';
require __DIR__.'/app/operations.php';
require __DIR__.'/app/finance.php';
require __DIR__.'/app/reports.php';
require __DIR__.'/app/video.php';
require_once __DIR__.'/app/administration-records.php';
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
 if($_SERVER['REQUEST_METHOD']==='POST')v26_money_validate($b);if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$b['csrf']??'')))fail('انتهت صلاحية الصفحة؛ أعد تحميلها.',403);
 $read=['server_clock','v28_schedule_get','control31_supervisors','translation31_list','user_choices29','company_files_choices29','company_choices29','groups29_list','profile29','v28_my_debt','v28_info','v28_preview','v26_payments','v26_historical','v26_info','v26_preview','v26_report_detail','v26_report','v26_eos','v26_audit','v26_video_list','v26_video_detail','v26_payroll_history','bootstrap','list','users','policies','report','sick_summary','sick_overview','locations','notifications','audit','payroll_list','leave_balance','dashboard','cms_list','cms_messages','companies','history_list','bank_list','leave_overview','permissions_list','customer_departments','customer_cases','customer_case_detail','customer_ratings','languages_info','backup_info','workforce_list','commission_info','setup_preview','personal_history','correction_preview','allowance_info'];
 if(!in_array($action,$read,true)&&$_SERVER['REQUEST_METHOD']!=='POST')fail('طريقة الطلب غير مسموحة.',405);
 if($action==='bootstrap'){
  $cfg=settings();$public=array_intersect_key($cfg,array_flip(['company_name','company_name_en','site_url','logo_id','timezone','currency','public_site_title']));
  $u=null;if(isset($_SESSION['uid']))try{$u=public_user(current_user());}catch(Throwable $e){}
  $result=['trial_mode'=>is_file(EP_SITE_ROOT.'/TRIAL_INSTANCE'),'localization'=>localization_boot($u),'csrf'=>$_SESSION['csrf'],'company'=>$public,'user'=>$u,'modules'=>modules(),'server_time'=>utc_now()];
  if($u){$result['company_choices30']=company_choices30($u,null,false);if(!empty($_SESSION['company_filter30'])&&!in_array($_SESSION['company_filter30'],array_column($result['company_choices30'],'code'),true))$_SESSION['company_filter30']='';$result['company_filter30']=$_SESSION['company_filter30']??'';$result['permissions']=permission_effective29($u);$result['access_mode']=$_SESSION['access_mode29']??'personal';$result['has_team_access']=permission_has_team29($u);$result['denied_screens']=array_values(array_filter(array_keys(permission_catalog()),fn($s)=>permission_denied29($u,$s)));$result['operation']=array_intersect_key($cfg,array_flip(['history_approved','operation_start','attendance_enabled','reports_enabled','payroll_enabled','leave_enabled','attendance_start','leave_start','reports_start','payroll_start','payroll_start_month']));$result['settings']=$u['role']==='admin'?$cfg:array_intersect_key($cfg,array_flip(['tracking_enabled','tracking_interval','tracking_stale','timezone','currency','annual_leave_days']));$date=local_time()->format('Y-m-d');$result['upgrade_needed']=$u['role']==='admin'?!site_v32_ready():false;$result['policy']=policy_for((int)$u['id'],$date);$result['state']=event_state(query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$u['id'],$date])->fetchAll());}
 }elseif($action==='server_clock'){
  $lastActivity=$_SESSION['last']??0;current_user();$_SESSION['last']=$lastActivity;session_write_close();$result=['server_epoch_ms'=>(int)round(microtime(true)*1000)];
 }elseif($action==='login_language'){
  $lang=(string)($b['language']??'');if(!in_array($lang,['ar','en'],true)||($lang==='en'&&!interface_english_ready()))fail('اللغة غير متاحة.');$_SESSION['login_language']=$lang;$result=['ok'=>true];
 }elseif($action==='login'){
  $name=trim((string)($b['username']??''));$key=hash('sha256',strtolower($name).'|'.($_SERVER['REMOTE_ADDR']??''));
  $limit=query('SELECT * FROM ep_login_limits WHERE limit_key=?',[$key])->fetch();
  if($limit&&(int)$limit['blocked_until']>time())fail('محاولات كثيرة؛ انتظر 15 دقيقة.',429);
  $u=preg_match('/^EMP-\d{4,}$/i',$name)?employee_by_code(strtoupper($name)):query("SELECT * FROM ep_users WHERE username=? AND role='admin' AND active=1",[$name])->fetch();if($u&&empty($u['active']))$u=null;
  if(!$u||!password_verify((string)($b['password']??''),$u['password_hash'])){
   query('MERGE INTO ep_login_limits WITH (HOLDLOCK) AS target USING (VALUES (?, 1, 0)) AS source (limit_key, attempts, blocked_until) ON target.limit_key=source.limit_key WHEN MATCHED THEN UPDATE SET attempts=target.attempts+1 WHEN NOT MATCHED THEN INSERT (limit_key, attempts, blocked_until) VALUES (source.limit_key, source.attempts, source.blocked_until);',[$key]);
   query('UPDATE ep_login_limits SET blocked_until=? WHERE limit_key=? AND attempts>=5',[time()+900,$key]);
   fail('بيانات الدخول غير صحيحة.',401);
  }
  query('DELETE FROM ep_login_limits WHERE limit_key=?',[$key]);session_regenerate_id(true);
  $loginLanguage=(string)($b['language']??'');if(in_array($loginLanguage,['ar','en'],true)&&($loginLanguage==='ar'||interface_english_ready()))$_SESSION['login_language']=$loginLanguage;
  $_SESSION['company_filter30']='';$_SESSION['access_mode29']='personal';$_SESSION['uid']=(int)$u['id'];$_SESSION['auth_fingerprint']=hash('sha256',$u['password_hash']);$_SESSION['last']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));
  audit('login','user:'.$u['id']);$result=['ok'=>true];
 }else{
  $u=current_user();$uid=(int)$u['id'];$cfg=settings();
  if(!empty(decode($u['profile_json'])['must_change_password'])&&!in_array($action,['change_password','logout'],true))fail('غيّر كلمة المرور المؤقتة أولًا.',428);
  permission_context($action,$u,$b);
  permission_gate29($action,$u,$b);
  access_request30($action,$u,$b);
  // These reads do not write session state; release the lock after authorization.
  if($_SERVER['REQUEST_METHOD']==='GET'&&in_array($action,['users','user_choices29','dashboard','companies','notifications','list','report','policies','workforce_list','commission_info','allowance_info','v28_schedule_get','v26_report','v26_report_detail','leave_overview','v26_historical','v26_payments','locations','audit','payroll_list','leave_balance','sick_summary','sick_overview','v26_info','v28_info','v26_audit','company_choices29','company_files_choices29','bank_list'],true))session_write_close();
  $actor29=$u;$delegate29=delegation29($action,$u,$b);$u=$delegate29;
  if(in_array($action,['correction_apply','correction_commit','setup_import','setup_commit','v26_import'],true)&&query("SELECT setting_key FROM ep_settings WHERE setting_key='prepared_v28_complete'")->fetch())fail('تم تطبيق بيانات أحدث؛ لا يمكن إعادة استيراد دفعة قديمة.');
  $v26Before=v26_audit_before($action,$b);
  if(str_starts_with($action,'control31_'))$result=control_action31($action,$u,$b);elseif(str_starts_with($action,'translation31_'))$result=data_language_action31($action,$u,$b);elseif(str_starts_with($action,'groups30_'))$result=groups_action30($action,$u,$b);elseif(str_starts_with($action,'groups29_')||in_array($action,['profile29','mode29','batch29','company_choices29','company_files_choices29','user_choices29'],true))$result=groups_action29($action,$u,$b);elseif(str_starts_with($action,'v28_'))$result=v28_action($action,$u,$b);elseif(str_starts_with($action,'v26_video_'))$result=v26_video_action($action,$u,$b);elseif(in_array($action,['v26_payroll_submit','v26_payroll_return','v26_payroll_history','v26_payment','v26_payments','v26_leave_cash_cancel','v26_eos_settle','v26_eos'],true))$result=v26_finance_action($action,$u,$b);elseif(in_array($action,['v26_report_detail','v26_report','v26_audit'],true))$result=v26_reports_action($action,$u,$b);elseif(str_starts_with($action,'v26_'))$result=v26_action($action,$u,$b);elseif(str_starts_with($action,'correction_'))$result=correction_action($action,$u,$b);elseif(str_starts_with($action,'allowance_')||str_starts_with($action,'friday_'))$result=allowance_action($action,$u,$b);elseif(str_starts_with($action,'workforce_')){$result=workforce_action($action,$u,$b);if(!empty($u['_delegate29'])&&$action==='workforce_list')$result=array_values(array_filter($result,fn($row)=>in_array((int)$row['user_id'],permission_scope29($actor29,'workforce'),true)));}elseif(str_starts_with($action,'commission_'))$result=commission_action($action,$u,$b);elseif(str_starts_with($action,'setup_'))$result=setup_action($action,$u,$b);elseif($action==='site_upgrade')$result=site_upgrade($u);elseif(str_starts_with($action,'backup_'))$result=backup_action($action,$u,$b);elseif(str_starts_with($action,'customer_'))$result=customer_action($action,$u,$b);elseif(in_array($action,['languages_info','languages_save','language_select','translation_prepare','translation_process','translation_key_save','public_language_review'],true))$result=language_action($action,$u,$b);elseif(in_array($action,['permissions_list','permissions_save','launch_save'],true))$result=permissions_action($action,$u,$b);elseif(in_array($action,['companies','company_save','history_list','import_preview','import_commit','bank_list','bank_build','bank_save','bank_approve'],true))$result=administration_action($action,$u,$b);elseif(str_starts_with($action,'cms_'))$result=cms_action($action,$u,$b);else switch($action){
   case 'personal_history':
    if($u['role']==='admin')fail('المشرف العام لا يملك سجلًا وظيفيًا.',403);
    $result=query("SELECT kind,period_key,body_json FROM ep_history WHERE employee_code=? AND kind IN ('leave_history','leave_settlement','salary_history') ORDER BY period_key DESC,id DESC",[employee_key($u)])->fetchAll();break;
   case 'logout':session_destroy();$result=['ok'=>true];break;
   case 'change_password':
    if(!password_verify((string)($b['old_password']??''),$u['password_hash']))fail('كلمة المرور الحالية غير صحيحة.');
    v28_password((string)($b['new_password']??''),$u['role']);if(password_verify((string)$b['new_password'],$u['password_hash']))fail('اختر كلمة جديدة مختلفة عن المؤقتة.');$profile28=decode($u['profile_json']);$profile28['must_change_password']=false;query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile28),$uid]);
    query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['new_password'],PASSWORD_DEFAULT),$uid]);$_SESSION['auth_fingerprint']=hash('sha256',query('SELECT password_hash FROM ep_users WHERE id=?',[$uid])->fetchColumn());audit('password_change','user:'.$uid);$result=['ok'=>true];break;
   case 'users':
    if($u['role']==='employee'){$result=[public_user($u)];break;}
    $rows=$u['role']==='admin'?query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll():array_values(array_filter(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll(),fn($staff)=>in_array((int)$staff['id'],scope_ids($u),true)));
    service_prefetch($rows);usort($rows,fn($a,$b)=>strnatcasecmp(employee_key($a),employee_key($b)));$result=array_map(function($row)use($u,$uid){$v=public_user($row);if($u['role']==='manager'&&(int)$row['id']!==$uid){unset($v['salary']);$v['profile']=permission_has($u,'users')?array_intersect_key($v['profile'],array_flip(['name_en','phone','email','nationality','address'])):array_intersect_key($v['profile'],['name_en'=>true]);}return $v;},$rows);break;
   case 'user_save':
    if($u['role']!=='admin'){
     permission_require($u,'users','edit');$target=(int)($b['id']??0);if(!$target||!can_manage($u,$target))fail('تعديل الموظف خارج نطاقك.',403);$name=trim((string)($b['name']??''));if(!$name)fail('الاسم مطلوب.');query('UPDATE ep_users SET name=?,department=?,job_title=? WHERE id=?',[$name,mb_substr((string)($b['department']??''),0,100),mb_substr((string)($b['job_title']??''),0,100),$target]);audit('user_basic_edit','user:'.$target);$result=['ok'=>true,'id'=>$target];break;
    }
    $id=(int)($b['id']??0);$name=trim((string)($b['name']??''));$username=trim((string)($b['username']??''));
    if(!$name||!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/',$username))fail('اسم الموظف واسم دخول صحيح مطلوبان.');
    $role=$id?(string)query('SELECT role FROM ep_users WHERE id=?',[$id])->fetchColumn():'employee';if(!in_array($role,['manager','employee'],true))fail('حساب المشرف العام مستقل عن الموظفين.');if($id&&query("SELECT id FROM ep_users WHERE id=? AND role='admin'",[$id])->fetch())fail('حساب المشرف العام مستقل عن الموظفين.');
    $type=(string)($b['staff_type']??'office');if(!in_array($type,['office','rep','remote','partner'],true))fail('نوع الموظف غير صالح.');
    $active=empty($b['active'])?0:1;
    if($id===$uid && (!$active||$role!=='admin'))fail('لا يمكنك تعطيل حسابك أو إزالة دورك الإداري.');
    $manager=(int)($b['manager_id']??0);if($manager&&($manager===$id||!query("SELECT id FROM ep_users WHERE id=? AND role<>'admin'",[$manager])->fetch()))fail('المدير غير صالح.');
    $hired=valid_date($b['hired_on']??null);
    foreach(['company_code','residence_company_code']as $companyField)if(!empty($b['profile'][$companyField])&&!company_by_code((string)$b['profile'][$companyField]))fail('شركة غير موجودة.');
    if(isset($b['profile']['bank_salary'])&&$b['profile']['bank_salary']!==''&&(!is_numeric($b['profile']['bank_salary'])||(float)$b['profile']['bank_salary']<0))fail('مبلغ التحويل غير صالح.');
    if($id&&in_array($id,supervisor_ids31(),true)&&(!empty($b['password'])||!$active))owner_only31($u);
    $previousProfile=$id?decode(query('SELECT profile_json FROM ep_users WHERE id=?',[$id])->fetchColumn()?:'{}'):[];$b['profile']=array_replace($b['profile']??[],array_intersect_key($previousProfile,array_flip(['employee_code','setup_serial'])));
    if($id&&query("SELECT id FROM ep_records WHERE user_id=? AND module='service_period'",[$id])->fetch()){foreach(['company_code','residence_company_code','attendance_required','leave_eligible','payroll_visible','bank_visible']as $key)if(array_key_exists($key,$previousProfile))$b['profile'][$key]=$previousProfile[$key];$oldHire=query('SELECT hired_on FROM ep_users WHERE id=?',[$id])->fetchColumn();$hired=$oldHire?:null;}
    if(!($b['profile']['fixed_salary']??true)&&(float)($b['salary']??0)!==0.0)fail('بدون راتب ثابت يجب أن يكون الراتب صفرًا');
    if(!empty($b['password']))$b['profile']['must_change_password']=true;else if(isset($previousProfile['must_change_password']))$b['profile']['must_change_password']=$previousProfile['must_change_password'];
    $args=[$username,$name,$role,$manager?:null,mb_substr((string)($b['department']??''),0,100),mb_substr((string)($b['job_title']??''),0,100),$type,$active,empty($b['show_sick'])?0:1,$hired,max(0,(float)($b['salary']??0)),jencode($b['profile']??[])];
    if(!empty($b['password']))v28_password((string)$b['password'],$role);
    db()->beginTransaction();if(!$id){$b['profile']['employee_code']=next_code('employee');$args[11]=jencode($b['profile']);}
    if($id){$previous=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$previous)fail('الموظف غير موجود.');if($previous['username']!==$username&&query('SELECT id FROM ep_history WHERE employee_code=?',[employee_key($previous)])->fetch()&&!isset($previousProfile['employee_code']))fail('أضف الكود الثابت عبر ترقية البيانات قبل تغيير اسم الدخول.');if(($previousProfile['fixed_salary']??true)!==($b['profile']['fixed_salary']??true)||abs((float)$previous['salary']-(float)($b['salary']??0))>.0001||!empty($b['salary_effective_from']))salary_record(array_replace($previous,['username'=>$username]),(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid,(bool)($b['profile']['fixed_salary']??true));query('UPDATE ep_users SET username=?,name=?,role=?,manager_id=?,department=?,job_title=?,staff_type=?,active=?,show_sick=?,hired_on=?,salary=?,profile_json=? WHERE id=?',[...$args,$id]);}
    else{v28_password((string)($b['password']??''),$role);query('INSERT INTO ep_users(username,name,role,manager_id,department,job_title,staff_type,active,show_sick,hired_on,salary,profile_json,password_hash,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[...$args,password_hash($b['password'],PASSWORD_DEFAULT),utc_now()]);$id=(int)db()->lastInsertId();salary_record(['id'=>$id,'role'=>$role,'username'=>$username,'hired_on'=>$hired,'profile_json'=>$args[11]],(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid,(bool)($b['profile']['fixed_salary']??true));}
    if(empty($b['id'])&&$role!=='admin'&&$hired&&!empty($b['profile']['company_code'])){$p=$b['profile'];$period=['company_code'=>$p['company_code'],'residence_company_code'=>$p['residence_company_code']??'','department'=>$args[4],'job_title'=>$args[5],'staff_type'=>$type,'attendance'=>(bool)($p['attendance_required']??true),'leave'=>(bool)($p['leave_eligible']??true),'payroll'=>(bool)($p['payroll_visible']??true),'bank'=>(bool)($p['bank_visible']??true)];query("INSERT INTO ep_records(module,user_id,title,date_from,status,body_json,created_at,updated_at) VALUES('service_period',?,?,?,'approved',?,?,?)",[$id,'فترة خدمة',$hired,jencode($period),utc_now(),utc_now()]);unset($GLOBALS['EP_SERVICE_CACHE'][$id]);}
    $bankStaff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();$bankProfile=decode($bankStaff['profile_json']);$oldBankProfile=isset($previous)?decode($previous['profile_json']):[];$hasBank=false;$bankChanged=false;foreach(['bank_name','bank_account','iban']as $k)if(trim((string)($bankProfile[$k]??''))!==''||trim((string)($oldBankProfile[$k]??''))!=='')$hasBank=true;foreach(['bank_name','bank_account','iban','civil_id','name_en']as $k)if((string)($bankProfile[$k]??'')!==(string)($oldBankProfile[$k]??''))$bankChanged=true;if(($hasBank&&$bankChanged)||!empty($b['bank_effective_from']))bank_identity_record($bankStaff,$bankProfile,(string)($b['bank_effective_from']??''),$uid);
    if(!empty($b['password'])&&isset($b['id'])){if(!empty($b['password']))v28_password((string)$b['password'],$role);query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['password'],PASSWORD_DEFAULT),$id]);}
    if(!empty($b['profile']['residence_file_id'])&&(string)($previousProfile['residence_file_id']??'')!==(string)$b['profile']['residence_file_id']){$fileDate=valid_date($b['residence_file_from']??null);if(!$fileDate)fail('تاريخ سريان تغيير ملف الإقامة مطلوب');v28_residence_record($id,(int)$b['profile']['residence_file_id'],$fileDate,$uid);}
    audit('user_save','user:'.$id);db()->commit();$result=['ok'=>true,'id'=>$id];break;
   case 'settings_save':
    admin_only($u);$values=$b['values']??[];if(!is_array($values))fail('الإعدادات غير صالحة.');foreach(array_keys($values) as $key)if(!preg_match('/^[a-z][a-z0-9_]{0,79}$/',(string)$key))fail('الإعدادات غير صالحة.');if(isset($values['app_name31'])&&(!is_string($values['app_name31'])||trim($values['app_name31'])===''||mb_strlen($values['app_name31'])>60))fail('اسم التطبيق مطلوب ولا يتجاوز 60 حرفًا.');
    foreach(['upgrade_v32','upgrade_v31','owner31','supervisors31','supervisor_revision31','data_translation31','data_translation_revision31','data_translation_enabled31','upgrade_v30','access_groups29','upgrade_v29','customer_routes','offers_languages','offers_default_language','public_language_approvals','public_languages','employee_languages','employee_default_language','admin_default_language','translation_enabled','translation_context','translation_budget','ratings_enabled','ratings_visibility','permissions','history_approved','history_approved_at','history_approved_by','operation_start','attendance_enabled','reports_enabled','payroll_enabled','leave_enabled','attendance_start','leave_start','payroll_start','reports_start','payroll_start_month']as $protected)if(array_key_exists($protected,$values))fail('استخدم شاشة الصلاحيات أو بدء التشغيل لتغيير هذه القيم.');
    if(!empty($values['payroll_enabled'])&&!$cfg['history_approved'])fail('اعتمد البيانات القديمة من شاشة بدء التشغيل أولًا.');
    unset($values['public_footer'],$values['public_ui'],$values['public_original_texts']);
    if(isset($values['timezone'])&&!in_array($values['timezone'],DateTimeZone::listIdentifiers(),true))fail('المنطقة الزمنية غير صالحة.');
    if(isset($values['site_url'])&&$values['site_url']!==''&&!preg_match('~^https://[^\s]+$~',$values['site_url']))fail('رابط الموقع يجب أن يبدأ بـHTTPS.');
    foreach(['sick_full_days','sick_half_days','annual_leave_days','leave_accrual_divisor','retention_days','tracking_interval','tracking_stale','salary_divisor','session_hours','work_hours','overtime_multiplier','leave_alert_first','leave_alert_high','leave_first_months','eos_day_divisor','eos_year_divisor']as $k)
     if(isset($values[$k])&&(!is_numeric($values[$k])||(float)$values[$k]<0))fail('قيمة الإعداد غير صالحة: '.$k);
    if(isset($values['leave_accrual_divisor'])&&(float)$values['leave_accrual_divisor']!==365.0)fail('مقسوم الاستحقاق المعتمد365');if(isset($values['eos_day_divisor'])&&(float)$values['eos_day_divisor']<=0)fail('مقسوم نهاية الخدمة موجب');if(isset($values['salary_proration'])&&!in_array($values['salary_proration'],['calendar','configured'],true))fail('طريقة تجزئة الراتب غير صالحة.');
    if(isset($values['leave_accrual_mode'])&&!in_array($values['leave_accrual_mode'],['annual','daily'],true))fail('طريقة الاستحقاق غير صالحة.');
    if(isset($values['leave_accrual_divisor'])&&(float)$values['leave_accrual_divisor']<=0)fail('مقسوم الاستحقاق يجب أن يكون موجبًا.');
    if(isset($values['locations'])){foreach($values['locations']as &$location32)if(empty($location32['id']))$location32['id']=bin2hex(random_bytes(8));unset($location32);}
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
    $target=employee_view_target($u,isset($_GET['user_id'])?(int)$_GET['user_id']:null);
    $result=leave_balance($target,$cfg);if($u['role']!=='admin'&&$target!==$uid)unset($result['valuation_salary'],$result['cash_value'],$result['salary_effective_from']);break;
   case 'dashboard':
    $date=local_time()->format('Y-m-d');$result=['staff'=>[],'expiring'=>[],'stale'=>[]];
    $visible=array_fill_keys(scope_ids($u),true);$people=array_values(array_filter(query('SELECT * FROM ep_users')->fetchAll(),fn($p)=>isset($visible[(int)$p['id']])));service_prefetch($people);
    $eventMap=[];$documentMap=[];$locationMap=[];$canDocuments=permission_has($u,'document');$canTracking=permission_has($u,'tracking');
    foreach(array_chunk(array_keys($visible),500) as $ids){
     $marks=implode(',',array_fill(0,count($ids),'?'));
     foreach(query("SELECT user_id,event_type FROM ep_events WHERE user_id IN ($marks) AND work_date=? ORDER BY received_at,id",[...$ids,$date])->fetchAll()as $row)$eventMap[(int)$row['user_id']][]=$row;
     foreach(query("SELECT user_id,id,title,body_json FROM ep_records WHERE user_id IN ($marks) AND module='document' AND status='active'",$ids)->fetchAll()as $row)if($canDocuments||(int)$row['user_id']===$uid)$documentMap[(int)$row['user_id']][]=$row;
     if($canTracking)foreach(query("SELECT l.user_id,l.received_at FROM ep_locations l JOIN (SELECT user_id,MAX(id) AS last_id FROM ep_locations WHERE user_id IN ($marks) GROUP BY user_id) latest ON l.id=latest.last_id",$ids)->fetchAll()as $row)$locationMap[(int)$row['user_id']]=$row;
    }
    $peopleById=array_column($people,null,'id');foreach(array_keys($visible)as $target){
     $staff=$peopleById[$target]??null;if(!$staff||!service_state($staff,$date)['employed'])continue;
     $events=$eventMap[$target]??[];
     $d=$cfg['attendance_enabled']&&$cfg['history_approved']&&$cfg['operation_start']<=$date?attendance_report($target,$date,$date)[0]:['late_minutes'=>0,'missing'=>false,'unclosed_permission'=>false,'absence'=>false];
     $result['staff'][]=['id'=>$target,'name'=>$staff['name'],'state'=>event_state($events),'late'=>$d['late_minutes'],'missing'=>$d['missing'],'unclosed'=>$d['unclosed_permission'],'absence'=>$d['absence']];
     foreach(($documentMap[$target]??[])as $r){
      $expiry=decode($r['body_json'])['expiry']??'';if($expiry&&$expiry<=(new DateTimeImmutable($date))->modify('+30 days')->format('Y-m-d'))$result['expiring'][]=['name'=>$staff['name'],'title'=>$r['title'],'expiry'=>$expiry];
     }
     if(permission_has($u,'tracking')&&$staff['staff_type']==='rep'&&in_array(event_state($events),['working','away'],true)){
      $loc=$locationMap[$target]??null;
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
    operation_require('attendance');$service=service_state($u,local_time()->format('Y-m-d'));if(!$service['employed']||!$service['attendance'])fail('هذا الموظف لا يخضع للحضور في هذا التاريخ.');
    $ticket=bin2hex(random_bytes(24));$_SESSION['capture_ticket']=['token'=>$ticket,'at'=>time()];$result=['ticket'=>$ticket,'server_time'=>utc_now()];break;
   case 'event':
    operation_require('attendance');
    $date=local_time()->format('Y-m-d');$service=service_state($u,$date);if(!$service['employed']||!$service['attendance'])fail('هذا الموظف لا يخضع للحضور في هذا التاريخ.');$u['staff_type']=$service['staff_type']??$u['staff_type'];$policy=policy_for($uid,$date);$p=$policy['values'];
    if(!$p['configured'])fail('الإدارة لم تعتمد جدول دوامك بعد.');
    $rid=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$rid))fail('رقم الطلب غير صالح.');
    db()->beginTransaction();$lockedUser=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid])->fetch();unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);$lockedService=service_state($lockedUser,$date);if(!$lockedService['employed']||!$lockedService['attendance'])fail('هذا الموظف لا يخضع للحضور في هذا التاريخ.');
    $old=query('SELECT * FROM ep_events WHERE request_id=?',[$rid])->fetch();
    if($old){if((int)$old['user_id']!==$uid)fail('رقم طلب مستخدم.',409);$result=['ok'=>true,'id'=>$old['id'],'received_at'=>$old['received_at'],'duplicate'=>true];db()->commit();break;}
    $events=query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$uid,$date])->fetchAll();
    $type=(string)($b['event_type']??'');if(!valid_event_transition(event_state($events),$type))fail('الحركة غير متاحة حسب آخر تسجيل.');
    if(approved_day($uid,$date))fail('اليوم مسجل كإجازة معتمدة؛ راجع الإدارة.');
    [$lat,$lon,$acc]=validate_location($b,$p);$inside=false;$p['location_snapshot']=$cfg['locations'];
    foreach($cfg['locations']as $loc)if(($loc['active']??true)&&(!isset($p['location_id'])||$p['location_id']===''||($loc['id']??'')===$p['location_id'])&&distance_m($lat,$lon,(float)$loc['latitude'],(float)$loc['longitude'])+$acc<=(float)$loc['radius'])$inside=true;
    $permission=null;
    if(!$inside){
     if(!array_filter($cfg['locations'],fn($loc)=>$loc['active']??true))fail('مواقع الشركة لم تضبط بعد.');
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
    $id=(int)db()->lastInsertId();unset($_SESSION['capture_ticket']);audit('event','event:'.$id,['type'=>$type,'policy_snapshot'=>$p,'location_snapshot'=>$cfg['locations']]);db()->commit();
    $result=['ok'=>true,'id'=>$id,'received_at'=>$received,'duplicate'=>false];break;
   case 'list':
    $module=(string)($_GET['module']??'');if(!isset(modules()[$module]))fail('القسم غير موجود.');
    $ids=scope_ids($u);$marks=$ids?implode(',',array_fill(0,count($ids),'?')):'NULL';
    $result=query("SELECT TOP (500) r.*,u.name AS employee_name FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE r.module=? AND r.user_id IN ($marks) ORDER BY r.id DESC",[$module,...$ids])->fetchAll();
    if($u['role']!=='admin'){
     $result=array_values(array_filter($result,fn($r)=>(int)$r['user_id']!==$uid||!in_array($module,['discipline','adjustment'],true)||$r['status']==='approved'));
     foreach($result as &$row){$rb=decode($row['body_json']);unset($rb['_sick_days']);$row['body_json']=jencode($rb);}unset($row);
    }
    break;
   case 'record_save':
    $module=(string)($b['module']??'');$meta=modules()[$module]??null;if(!$meta)fail('القسم غير موجود.');
    $target=(int)($b['user_id']??$uid);employee_required($target);
    if($u['role']==='employee'&&(!$meta['employee']||$target!==$uid))fail('ليس لديك صلاحية إنشاء هذا السجل.',403);
    if($u['role']!=='employee'&&!can_view($u,$target))fail('الموظف خارج صلاحياتك.',403);
    if(in_array($module,['discipline','adjustment','document','employment'],true)&&$u['role']==='manager'&&$target===$uid)fail('لا يمكن إنشاء قرار إداري لنفسك.');
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
     $target=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']])->fetch();foreach(days_between($r['date_from'],$r['date_to'])as $day)if(!service_state($target,$day)['employed'])fail('الفترة خارج خدمة الموظف.');
     if(query("SELECT TOP (1) id FROM ep_records WHERE id<>? AND user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? AND date_to>=?",[$id,$r['user_id'],$r['date_to'],$r['date_from']])->fetch())fail('الفترة تتداخل مع إجازة معتمدة.');
     if($r['module']==='sick'){foreach(historical_for((int)$target['id'],'sick_summary',substr($r['date_from'],0,4),substr($r['date_to'],0,4))as $h)if($r['date_from']<=decode($h['body_json'])['as_of'])fail('الفترة ضمن المرضيات التاريخية المستوردة؛ لا تعتمدها مرة ثانية.');$body['_sick_days']=sick_snapshot($r,$target,$cfg);}
     if($r['module']==='leave'){$hire=service_state($target,$r['date_from'])['from']??$target['hired_on'];if(v26_entitlements($target,$r['date_from'])['leave_days']&&$r['date_from']<(new DateTimeImmutable($hire))->modify('+'.(int)($cfg['leave_first_months']??9).' months')->format('Y-m-d'))fail('لم تكتمل مدة استحقاق أول إجازة');
      $requested=count(days_between($r['date_from'],$r['date_to']));$balance=leave_balance((int)$r['user_id'],$cfg,(new DateTimeImmutable($r['date_from']))->modify('-1 day')->format('Y-m-d'));$body['_approved_days']=$requested;$body['_balance_at_approval']=$balance['remaining'];$body['_excess_approved']=max(0,$requested-max(0,(float)$balance['remaining']));$body['expected_return']=(new DateTimeImmutable($r['date_to']))->modify('+1 day')->format('Y-m-d');
     }
    }
    if($status==='approved'&&$r['module']==='leave_cash'){query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']]);$days=(float)($body['days']??0);$balance=leave_balance((int)$r['user_id'],$cfg,$r['date_from']??local_time()->format('Y-m-d'));if($days<=0||$balance['cash_days']===null||$days>$balance['cash_days']+1e-9)fail('أيام التسوية تتجاوز الرصيد النقدي أو الرصيد غير محدد.');if(!in_array($body['payment_method']??'', ['cash','bank_salary','bank_separate'],true))fail('اختر طريقة صرف الإجازات.');if($body['payment_method']==='bank_salary'&&!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$body['payroll_month']??''))fail('حدد شهر إضافة مبلغ التسوية.');if($balance['valuation_salary']===null)fail('أدخل تاريخ الراتب قبل اعتماد تسوية الإجازة.');$amount=round($days*$balance['valuation_salary']/30,3);if($amount<=0)fail('قيمة صرف الإجازة يجب أن تكون موجبة؛ راجع الراتب الأساسي');$body['_approved_on']=local_time()->format('Y-m-d');$body['_valuation']=['date'=>$balance['valuation_date'],'salary'=>$balance['valuation_salary'],'effective_from'=>$balance['salary_effective_from'],'days'=>$days,'divisor'=>30,'amount'=>$amount];query('UPDATE ep_records SET amount=? WHERE id=?',[$amount,$id]);}

    if($status==='approved'&&$r['module']==='adjustment')v28_addition_check((int)$r['user_id'],(string)($body['payroll_month']??''),(float)$r['amount'],(string)($body['reason']??''),'addition');
    if($status==='approved'&&$r['module']==='external'&&$r['date_from']<local_time()->format('Y-m-d'))fail('لا اعتماد بأثر رجعي للانصراف الخارجي.');
    if($status==='approved'&&in_array($r['module'],['leave','sick','discipline','adjustment','leave_cash'],true))financial_invalidate((int)$r['user_id'],in_array($r['module'],['leave','sick'],true)?$r['date_from']:(($r['module']==='leave_cash'&&($body['payment_method']??'')!=='bank_salary')?$r['date_from']:($body['payroll_month'].'-01')));
    query('UPDATE ep_records SET status=?,body_json=?,reviewed_by=?,review_note=?,updated_at=? WHERE id=?',[$status,jencode($body),$uid,mb_substr((string)($b['note']??''),0,5000),utc_now(),$id]);
    if($status==='approved'&&$r['module']==='leave_cash'){v26_cash_integrity((int)$r['user_id']);}notice((int)$r['user_id'],'قرار: '.modules()[$r['module']]['label'],$r['title'].' — '.$status.' — '.($b['note']??''),in_array($r['module'],['discipline','external'],true));
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
    $body['_revisions'][]=['date_from'=>$r['date_from'],'date_to'=>$r['date_to'],'file_id'=>$r['file_id'],'at'=>utc_now(),'actor'=>$uid,'details'=>$body['_revision_details']??''];$fileId=$r['file_id'];if(!empty($_FILES['file']['tmp_name']))$fileId=upload_file($_FILES['file'],$uid,$r['module']==='sick');
    $body['_revision_details']=mb_substr((string)($b['details']??''),0,5000);
    query("UPDATE ep_records SET date_from=?,date_to=?,body_json=?,file_id=?,status='pending',updated_at=? WHERE id=?",[$from,$to,jencode($body),$fileId,utc_now(),$r['id']]);
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
    $ids=scope_ids($u);$marks=$ids?implode(',',array_fill(0,count($ids),'?')):'NULL';
    if(isset($_GET['user_id'])){
     $target=employee_view_target($u,(int)$_GET['user_id']);
     $result=query('SELECT TOP (300) * FROM ep_locations WHERE user_id=? AND received_at>=? ORDER BY id DESC',[$target,local_time()->setTime(0,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')])->fetchAll();
    }else{$result=query("SELECT u.id,u.name,l.received_at,l.latitude,l.longitude,l.accuracy FROM ep_users u LEFT JOIN ep_locations l ON l.id=(SELECT MAX(id) FROM ep_locations WHERE user_id=u.id) WHERE u.staff_type='rep' AND u.id IN ($marks)",$ids)->fetchAll();}
    break;
   case 'report':
    $target=employee_view_target($u,isset($_GET['user_id'])?(int)$_GET['user_id']:null);
    operation_require('reports');$from=valid_date($_GET['from']??local_time()->format('Y-m-01'));$to=valid_date($_GET['to']??local_time()->format('Y-m-d'));$result=attendance_report($target,$from,$to);if($u['role']==='employee')foreach($result as &$day){unset($day['sick_paid']);}unset($day);break;
   case 'sick_summary':
    $target=employee_view_target($u,isset($_GET['user_id'])?(int)$_GET['user_id']:null);
    $staff=query('SELECT * FROM ep_users WHERE id=?',[$target])->fetch();
    if($target===$uid&&$u['role']!=='admin'&&!$staff['show_sick'])fail('الإدارة لم تفتح عرض عدد المرضيات لحسابك.',403);
    $records=query("SELECT body_json FROM ep_records WHERE user_id=? AND module='sick' AND status='approved'",[$target])->fetchAll();
    $history=query("SELECT period_key,body_json FROM ep_history WHERE kind='sick_summary' AND employee_code=? ORDER BY period_key",[employee_key($staff)])->fetchAll();
    $summary=sick_summary_values($staff,$cfg,$records,$history,local_time()->format('Y-m-d'));
    $result=($target===$uid&&$u['role']!=='admin')?['used'=>$summary['used']]:$summary;break;
   case 'sick_overview':
    $result=sick_overview_rows($u,$cfg);break;
   case 'audit':admin_only($u);$result=query('SELECT TOP (500) a.*,u.name FROM ep_audit a LEFT JOIN ep_users u ON u.id=a.user_id ORDER BY a.id DESC')->fetchAll();break;
   case 'payroll_list':
    if($u['role']==='admin'||permission_has($u,'payroll','view')){$GLOBALS['EP_SCREEN']='payroll';$visible=scope_ids($u);$result=array_values(array_filter(query('SELECT TOP (500) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id ORDER BY month_key DESC,u.name')->fetchAll(),fn($r)=>in_array((int)$r['user_id'],$visible,true)));}else{$result=query("SELECT TOP (100) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id WHERE p.user_id=? AND p.status='approved' ORDER BY month_key DESC",[$uid])->fetchAll();foreach($result as &$row){$calc=decode($row['calculation_json']);unset($calc['settings'],$calc['days_detail']);$row['calculation_json']=jencode($calc);}unset($row);}foreach(scope_ids($u)as $target)foreach(historical_for($target,'payroll','0000-01','9999-12')as $h){if($u['role']!=='admin'&&$target!==$uid)continue;$v=decode($h['body_json']);$name=query('SELECT name FROM ep_users WHERE id=?',[$target])->fetchColumn();$result[]=['id'=>'history-'.$h['id'],'name'=>$name,'month_key'=>$h['period_key'],'net_amount'=>$v['net'],'status'=>'approved','calculation_json'=>jencode(['salary'=>$v['salary'],'addition'=>$v['addition'],'deduction'=>$v['deduction'],'flags'=>[],'note'=>'كشف تاريخي مستورد؛ لا يعاد حسابه'])];}break;
   case 'payroll_build':
    operation_require('payroll');
    v26_permission($u,'payroll','create');if(!$cfg['payroll_enabled'])fail('حساب الرواتب غير مفعل.');
    $month=(string)($b['month']??'');if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('الشهر غير صالح.');
    $from=$month.'-01';$to=(new DateTimeImmutable($from))->format('Y-m-t');
    if($to>=local_time()->format('Y-m-d'))fail('أنشئ الكشف بعد انتهاء الشهر.');
    db()->beginTransaction();
    if(empty($cfg['payroll_start_month'])||$month<$cfg['payroll_start_month'])fail('الشهر قبل بداية الرواتب الجديدة');v26_payroll_snapshot_month($month,$u);payroll_build_month($month,$cfg);
    audit('payroll_build','month:'.$month);db()->commit();$result=['ok'=>true];break;
   case 'payroll_approve':
    operation_require('payroll');
    admin_only($u);db()->beginTransaction();$p=query('SELECT * FROM ep_payroll WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[(int)$b['id']])->fetch();if(!$p||$p['status']!=='submitted')fail('الكشف غير متاح للاعتماد.');
    query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$p['user_id']]);if(query("SELECT TOP (1) id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>?",[$p['user_id'],$p['month_key']])->fetch())fail('يوجد كشف لاحق معتمد؛ راجع ترتيب الشهور.');
    $snapshot=decode($p['calculation_json']);if((float)($snapshot['debt_deduction']??0)>v28_debt_balance((int)$p['user_id'],$p['month_key'].'-31')+.0001)fail('رصيد المديونية تغير؛ أعد مراجعة الخصم.');foreach($snapshot['loan_items']??[]as $item){$loan=query('SELECT amount FROM ep_records WHERE id=?',[$item['id']])->fetch();$paid=0.0;foreach(query("SELECT calculation_json FROM ep_payroll WHERE user_id=? AND status='approved'",[$p['user_id']])->fetchAll()as $past)foreach(decode($past['calculation_json'])['loan_items']??[]as $prior)if((int)$prior['id']===(int)$item['id'])$paid+=(float)$prior['amount'];if(!$loan||$paid+(float)$item['amount']>(float)$loan['amount']+.0001)fail('مسودة السلف قديمة؛ أعد بناء الكشف قبل الاعتماد.');}
    if(decode($p['calculation_json'])['flags'])fail('عالج ملاحظات المسودة قبل الاعتماد.');
    v26_payroll_version($p,$u,'approved',(string)($b['note']??''));query("UPDATE ep_payroll SET status='approved',approved_by=?,approved_at=? WHERE id=?",[$uid,utc_now(),$p['id']]);
    notice((int)$p['user_id'],'اعتماد كشف الراتب','الشهر '.$p['month_key'].' — الصافي '.$p['net_amount'].' '.$cfg['currency'],true);audit('payroll_approve','payroll:'.$p['id']);db()->commit();$result=['ok'=>true];break;
   default:fail('العملية غير موجودة.',404);
  }
 }
 if(isset($u)&&$u&&$_SERVER['REQUEST_METHOD']==='POST'&&!in_array($action,['login','logout'],true))audit('api_change',$action,['before'=>$v26Before??null,'request'=>v26_audit_clean($b),'result'=>v26_audit_clean(is_array($result)?$result:[])]);
 $result=display_order32($action,$result);
 if(isset($u)&&($u['role']??'')==='admin'&&!headers_sent())header('Server-Timing: app;dur='.round((hrtime(true)-$apiStarted32)/1e6,2).', db;dur='.round($GLOBALS['EP_SQL_MS32']??0,2).', queries;desc="'.(int)($GLOBALS['EP_SQL_COUNT32']??0).'"');
 echo jencode(['data'=>$result]);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
 try{if(app_installed()&&db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}
 if(http_response_code()<400)http_response_code(500);
 $safe=$e instanceof RuntimeException && !($e instanceof PDOException);
 if(!$safe)security_log($e);
 echo jencode(['error'=>$safe?$e->getMessage():'تعذر تنفيذ العملية؛ راجع سجل الخادم.']);
}
