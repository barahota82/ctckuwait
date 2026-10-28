<?php
declare(strict_types=1);
$apiStarted32=hrtime(true);
require __DIR__.'/app/core.php';
require __DIR__.'/app/modules.php';
require_once __DIR__.'/app/employee-filter.php';
require __DIR__.'/app/sick-summary.php';
require __DIR__.'/app/cms.php';
require __DIR__.'/app/administration.php';
require __DIR__.'/app/languages.php';
require __DIR__.'/app/customers.php';
require __DIR__.'/app/backups.php';
require __DIR__.'/app/setup-data.php';
require __DIR__.'/app/corrections.php';
require __DIR__.'/app/payroll.php';
require_once __DIR__.'/app/company-incentive.php';
require __DIR__.'/app/operations.php';
require_once __DIR__.'/app/permit-editor.php';
require __DIR__.'/app/finance.php';
require_once __DIR__.'/app/benefits.php';
require __DIR__.'/app/reports.php';
require __DIR__.'/app/video.php';
require __DIR__.'/app/notifications.php';
require_once __DIR__.'/app/text-pairs.php';
require_once __DIR__.'/app/record-pairs.php';
require_once __DIR__.'/app/administration-records.php';
require_once __DIR__.'/app/workflows.php';
require_once __DIR__.'/app/decisions.php';
require_once __DIR__.'/app/departure.php';
require_once __DIR__.'/app/workspace-page.php';
require_once __DIR__.'/app/policy-management.php';
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
 if($action==='permit148_save'&&isset($b['payload148']))$b=decode((string)$b['payload148']);
 if($action==='user_save'&&isset($b['payload143'])){$b=decode((string)$b['payload143']);if(!is_array($b))fail('بيانات الموظف غير صالحة.');}
 if($_SERVER['REQUEST_METHOD']==='POST')v26_money_validate($b);if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$b['csrf']??'')))fail('انتهت صلاحية الصفحة؛ أعد تحميلها.',403);
 $read=['permit148_info','permit148_history','login_token127','punch127_info','payroll_entry127','incentive114_info','finance103_info','finance103_day','punch39_choices','pay37_sheet','pay37_vouchers','sales36_report','pay35_info','policy_editor_context','policy_staff_list','policy_manage_preview','server_clock','v28_schedule_get','control31_supervisors','translation31_list','user_choices29','company_files_choices29','company_choices29','groups29_list','profile29','v28_my_debt','v28_info','v28_preview','v26_payments','v26_historical','v26_info','v26_preview','v26_report_detail','v26_report','v26_eos','v26_audit','v26_video_list','v26_video_detail','v26_payroll_history','bootstrap','list','users','policies','report','attendance_today200','sick_summary','sick_overview','locations','notifications','audit','payroll_list','leave_balance','dashboard','cms_list','cms_messages','companies','history_list','bank_list','leave_overview','permissions_list','customer_departments','customer_cases','customer_case_detail','customer_ratings','languages_info','backup_info','workforce_list','commission_info','setup_preview','personal_history','correction_preview','allowance_info'];
 $read=array_merge($read,workflow_reads194());
 if(!in_array($action,$read,true)&&$_SERVER['REQUEST_METHOD']!=='POST')fail('طريقة الطلب غير مسموحة.',405);
 if($action==='login_token127'){$result=['csrf'=>$_SESSION['csrf']];}
 elseif($action==='bootstrap'){
  $cfg=settings();$public=array_intersect_key($cfg,array_flip(['company_name','company_name_en','site_url','logo_id','timezone','currency','public_site_title']));
  $u=null;if(isset($_SESSION['uid']))try{$u=public_user(current_user());}catch(Throwable $e){}
  $result=['version194'=>'195.0','super_admin193'=>false,'trial_mode'=>is_file(EP_SITE_ROOT.'/TRIAL_INSTANCE'),'localization'=>localization_boot($u),'csrf'=>$_SESSION['csrf'],'company'=>$public,'user'=>$u,'modules'=>modules(),'server_time'=>utc_now()];
  if($u){$result['super_admin193']=$u['role']==='admin'&&!empty($u['full_supervisor31']);$result['personal_permissions34']=personal_effective34($u);$result['personal_permissions34']['exit_permissions183']=$result['personal_permissions34']['request']??[];$result['user']=personal_public_user34($result['user'],$u);$result['identity32']=$GLOBALS['EP_IDENTITY32']??null;$result['company_choices30']=company_choices30($u,null,false);if(!empty($_SESSION['company_filter30'])&&!in_array($_SESSION['company_filter30'],array_column($result['company_choices30'],'code'),true))$_SESSION['company_filter30']='';$result['company_filter30']=$_SESSION['company_filter30']??'';$result['permissions']=permission_effective29($u);$result['access_mode']=$_SESSION['access_mode29']??'personal';$result['has_team_access']=!empty($result['identity32']['has_workspace']);$result['denied_screens']=array_values(array_filter(array_keys(permission_catalog()),fn($s)=>permission_denied29($u,$s)));$result['operation']=array_intersect_key($cfg,array_flip(['history_approved','operation_start','attendance_enabled','reports_enabled','payroll_enabled','leave_enabled','attendance_start','leave_start','reports_start','payroll_start','payroll_start_month']));$result['settings']=$u['role']==='admin'?array_diff_key($cfg,['data_translation31'=>true]):array_intersect_key($cfg,array_flip(['tracking_enabled','tracking_interval','tracking_stale','timezone','currency','annual_leave_days']));$date=local_time()->format('Y-m-d');$result['upgrade_needed']=$u['role']==='admin'?(!site_v32_ready()||!forget_ready361()):false;$result['attendance_available35']=pay35_attendance($u,$date);$result['policy']=policy_for((int)$u['id'],$date);$result['state']=event_state(query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$u['id'],$date])->fetchAll());}
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
  unset($_SESSION['identity_mode32']);$_SESSION['identity_epoch32']=0;$_SESSION['company_filter30']='';$_SESSION['access_mode29']='personal';$_SESSION['uid']=(int)$u['id'];$_SESSION['auth_fingerprint']=hash('sha256',$u['password_hash']);$_SESSION['last']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));
  audit('login','user:'.$u['id']);$result=['ok'=>true,'csrf'=>$_SESSION['csrf']];
 }else{
  $u=current_user();identity_guard32();if($action==='change_password'||$action==='language_select'||$action==='mode29'||str_starts_with($action,'identity32_'))$u=current_user(true);$uid=(int)$u['id'];$cfg=settings();
  if(!empty(decode($u['profile_json'])['must_change_password'])&&!in_array($action,['change_password','logout'],true))fail('غيّر كلمة المرور المؤقتة أولًا.',428);
  if($action!=='translation161_tick')personal_gate34($action,$u,$b);
  permission_context($action,$u,$b);
  permission_gate29($action,$u,$b);
  access_request30($action,$u,$b);
  // These reads do not write session state; release the lock after authorization.
  if($_SERVER['REQUEST_METHOD']==='GET'&&in_array($action,['permit148_info','permit148_history','punch127_info','payroll_entry127','incentive114_info','finance103_info','finance103_day','server_clock','pay37_sheet','pay37_vouchers','sales36_report','pay35_info','policy_editor_context','policy_staff_list','policy_manage_preview','users','user_choices29','dashboard','companies','notifications','list','report','attendance_today200','policies','workforce_list','commission_info','allowance_info','v28_schedule_get','v26_report','v26_report_detail','leave_overview','v26_historical','v26_payments','locations','audit','payroll_list','leave_balance','sick_summary','sick_overview','v26_info','v28_info','v26_audit','company_choices29','company_files_choices29','bank_list','profile29','v28_my_debt','v26_video_list','v26_video_detail','customer_departments','customer_cases','customer_case_detail','customer_ratings','languages_info','cms_list','cms_messages','history_list','personal_history','backup_info','translation31_list',...workflow_reads194()],true))session_write_close();
  $actor29=$u;$delegate29=delegation29($action,$u,$b);$u=$delegate29;
  if(in_array($action,['correction_apply','correction_commit','setup_import','setup_commit','v26_import'],true)&&query("SELECT setting_key FROM ep_settings WHERE setting_key='prepared_v28_complete'")->fetch())fail('تم تطبيق بيانات أحدث؛ لا يمكن إعادة استيراد دفعة قديمة.');
  if(in_array($action,['owner_schedule396','owner_policy393','v28_preview','v28_import','v26_preview','v26_import','setup_preview','setup_import','setup_commit','correction_preview','correction_apply','correction_commit'],true))fail('انتهى تجهيز البيانات؛ استخدم الشاشات العادية أو استرجاع نسخة كاملة.',410);
  if(($action==='v26_save'&&($b['kind']??'')==='work_permit')||$action==='v26_permit_transfer')fail('تغيرت شاشة أذونات العمل؛ أعد فتحها ثم احفظ من النموذج الجديد.',409);
  if($action==='record_save')record_validate194($b);
  $recordPair173=record_prepare173($action,$b,$actor29,$u);
  $v26Before=v26_audit_before($action,$b);
  if(workflow_route194($action,$b)!==null){$result=workflow_action194($action,$actor29,$b);}
  elseif($action==='capture_ticket'&&!empty($b['permission_id'])){$result=permission_ticket194($actor29,$b);}
  elseif($action==='punch127_info'){if(($actor29['role']??'')==='admin'&&!personal_mode34($actor29)){$result=presence_admin_attendance200($actor29);}elseif(true){if(!personal_mode34($actor29))permission_require($actor29,'attendance');$date127=local_time()->format('Y-m-d');$state127=service_state($actor29,$date127);$result=['user_id'=>(int)$actor29['id'],'staff_type'=>$state127['staff_type']??$actor29['staff_type'],'service_attendance'=>(bool)$state127['attendance'],'personal_permissions34'=>personal_effective34($actor29),'server_time'=>utc_now(),'attendance_available35'=>pay35_attendance($actor29,$date127),'policy'=>policy_for((int)$actor29['id'],$date127),'state'=>event_state(query('SELECT event_type FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$actor29['id'],$date127])->fetchAll())];if(function_exists('presence_employee_status')){$result['presence']=presence_employee_status($actor29);presence_notify_open($actor29);}}}
  elseif(str_starts_with($action,'permit148_'))$result=permit_action148($action,$actor29,$b);
  elseif($action==='payroll_entry127'){require_once __DIR__.'/app/payroll-entry.php';$result=payroll_entry127($actor29);}
  elseif($action==='payroll_addition198_save')$result=payroll_addition198($actor29,$b);elseif(str_starts_with($action,'incentive114_'))$result=incentive_action114($action,$actor29,$b);elseif(str_starts_with($action,'finance103_'))$result=finance_action103($action,$actor29,$b);elseif($action==='owner_schedule396')$result=owner_schedule396($actor29);elseif($action==='owner_policy393')$result=owner_policy393($actor29);elseif(str_starts_with($action,'pay37_'))$result=pay37_action($action,$actor29,$b);elseif(str_starts_with($action,'sales36_'))$result=company_sales_action36($action,$actor29,$b);elseif(str_starts_with($action,'pay35_'))$result=pay35_action($action,$u,$b);elseif($action==='mode29')$result=identity_action32('identity32_mode',$u,$b);elseif(str_starts_with($action,'identity32_'))$result=identity_action32($action,$u,$b);elseif(str_starts_with($action,'control31_'))$result=control_action31($action,$u,$b);elseif($action==='translation161_tick'){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();$result=data_translation_tick161();}elseif(str_starts_with($action,'translation31_'))$result=data_language_action31($action,$u,$b);elseif(str_starts_with($action,'groups30_'))$result=groups_action30($action,$u,$b);elseif(str_starts_with($action,'groups29_')||in_array($action,['profile29','mode29','batch29','company_choices29','company_files_choices29','user_choices29'],true))$result=groups_action29($action,$u,$b);elseif(str_starts_with($action,'v28_'))$result=v28_action($action,$u,$b);elseif(str_starts_with($action,'v26_video_'))$result=v26_video_action($action,$u,$b);elseif(in_array($action,['v26_payroll_submit','v26_payroll_return','v26_payroll_history','v26_payment','v26_payments','v26_leave_cash_cancel','v26_eos_settle','v26_eos'],true))$result=v26_finance_action($action,$u,$b);elseif(in_array($action,['v26_report_detail','v26_report','v26_audit'],true))$result=v26_reports_action($action,$u,$b);elseif(str_starts_with($action,'v26_'))$result=v26_action($action,$u,$b);elseif(str_starts_with($action,'correction_'))$result=correction_action($action,$u,$b);elseif(str_starts_with($action,'allowance_')||str_starts_with($action,'friday_'))$result=allowance_action($action,$u,$b);elseif(str_starts_with($action,'workforce_')){$result=workforce_action($action,$u,$b);if(!empty($u['_delegate29'])&&$action==='workforce_list')$result=array_values(array_filter($result,fn($row)=>in_array((int)$row['user_id'],permission_scope29($actor29,'workforce'),true)));}elseif(str_starts_with($action,'commission_'))$result=commission_action($action,$u,$b);elseif(str_starts_with($action,'setup_'))$result=setup_action($action,$u,$b);elseif($action==='site_upgrade')$result=site_upgrade($u);elseif(str_starts_with($action,'backup_'))$result=backup_action($action,$u,$b);elseif(str_starts_with($action,'customer_'))$result=customer_action($action,$u,$b);elseif(in_array($action,['languages_info','languages_save','language_select','translation_prepare','translation_process','translation_key_save','public_language_review'],true))$result=language_action($action,$u,$b);elseif(in_array($action,['permissions_list','permissions_save'],true))$result=permissions_action($action,$u,$b);elseif(in_array($action,['companies','company_save','history_list','import_preview','import_commit','bank_list','bank_build','bank_save','bank_approve'],true))$result=administration_action($action,$u,$b);elseif(str_starts_with($action,'cms_'))$result=cms_action($action,$u,$b);else switch($action){
   case 'punch39_choices':
    $target=(int)($_GET['user_id']??$uid);punch_access39($actor29,$target);$date=valid_date($_GET['date']??null);if(!$date)fail('حدد تاريخ الواقعة.');$result=punch_options39($target,$date);break;
   case 'personal_history':
    if($u['role']==='admin'){$result=[];break;}
    $result=query("SELECT kind,period_key,body_json FROM ep_history WHERE employee_code=? AND kind IN ('leave_history','leave_settlement','salary_history') ORDER BY period_key DESC,id DESC",[employee_key($u)])->fetchAll();break;
   case 'logout':session_destroy();$result=['ok'=>true];break;
   case 'change_password':
    if(!password_verify((string)($b['old_password']??''),$u['password_hash']))fail('كلمة المرور الحالية غير صحيحة.');
    v28_password((string)($b['new_password']??''),$u['role']);if(password_verify((string)$b['new_password'],$u['password_hash']))fail('اختر كلمة جديدة مختلفة عن المؤقتة.');$profile28=decode($u['profile_json']);$profile28['must_change_password']=false;query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile28),$uid]);
    query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['new_password'],PASSWORD_DEFAULT),$uid]);$_SESSION['auth_fingerprint']=hash('sha256',query('SELECT password_hash FROM ep_users WHERE id=?',[$uid])->fetchColumn());audit('password_change','user:'.$uid);$result=['ok'=>true];break;
   case 'users':
    if($u['role']==='employee'){$result=[public_user($u)];break;}
    $scope58=$u['role']==='admin'?[]:scope_ids($u);$canUsers58=$u['role']==='manager'&&permission_has($u,'users');
    $rows=$u['role']==='admin'?query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll():array_values(array_filter(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll(),fn($staff)=>in_array((int)$staff['id'],$scope58,true)));
    service_prefetch($rows);usort($rows,fn($a,$b)=>strnatcasecmp(employee_key($a),employee_key($b)));$result=array_map(function($row)use($u,$uid,$canUsers58){$v=public_user($row);if($u['role']==='manager'&&(int)$row['id']!==$uid){unset($v['salary']);$v['profile']=$canUsers58?array_intersect_key($v['profile'],array_flip(['name_en','phone','email','nationality','address'])):array_intersect_key($v['profile'],['name_en'=>true]);}return $v;},$rows);break;
   case 'user_save':
    if($u['role']!=='admin'){
     permission_require($u,'users','edit');$target=(int)($b['id']??0);if(!$target||!can_manage($u,$target)||!in_array($target,permission_scope29($u,'users','edit'),true))fail('تعديل الموظف خارج نطاقك.',403);$name=trim((string)($b['name']??''));if(!$name)fail('الاسم مطلوب.');$textState170=text_user_prepare170($b,$u);db()->beginTransaction();$before134=query('SELECT * FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$target])->fetch();if(!$before134||$before134['role']==='admin')fail('الموظف غير موجود.',403);text_user_check170($textState170,$before134);$after134=$before134;$after134['name']=$name;foreach(['department','job_title']as $job134)$after134[$job134]=mb_substr((string)($b[$job134]??''),0,100);employee_job134($u,$before134,$after134);query('UPDATE ep_users SET name=?,department=?,job_title=? WHERE id=?',[$name,$after134['department'],$after134['job_title'],$target]);text_user_persist170($target,$textState170);audit('user_basic_edit','user:'.$target);db()->commit();$result=['ok'=>true,'id'=>$target];break;
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
    require_once __DIR__.'/app/employee-fields.php';
    $previousProfile=$id?decode(query('SELECT profile_json FROM ep_users WHERE id=?',[$id])->fetchColumn()?:'{}'):[];$b['profile']=array_replace($b['profile']??[],array_intersect_key($previousProfile,array_flip(['employee_code','setup_serial'])));unset($b['profile']['_text170']);if(isset($previousProfile['_text170']))$b['profile']['_text170']=$previousProfile['_text170'];
    $b['profile']['permit_exempt150']=array_key_exists('permit_exempt150',$b['profile'])?filter_var($b['profile']['permit_exempt150'],FILTER_VALIDATE_BOOLEAN):(bool)($previousProfile['permit_exempt150']??false);
    require_once __DIR__.'/app/permit-fields.php';employee_birth155($b['profile'],$previousProfile);
    nationality_profile137($b['profile'],$previousProfile);
    foreach(contract_fields119()as [$flag119,$rate119]){$b['profile'][$flag119]=array_key_exists($flag119,$b['profile'])?filter_var($b['profile'][$flag119],FILTER_VALIDATE_BOOLEAN):($previousProfile[$flag119]??false);$b['profile'][$rate119]=finance_number103($b['profile'][$rate119]??$previousProfile[$rate119]??0);}
    foreach(['collection_rate130','sales_rate130','sales_target130','sales_bonus130']as $key130)if(array_key_exists($key130,$b['profile']))$b['profile'][$key130]=finance_number103($b['profile'][$key130],$key130==='sales_target130');
    foreach(['collection_commission130','sales_commission130','sales_retro130']as $key130)if(array_key_exists($key130,$b['profile']))$b['profile'][$key130]=filter_var($b['profile'][$key130],FILTER_VALIDATE_BOOLEAN);
    employee_commission_validate130($b['profile']);
    if($id&&query("SELECT id FROM ep_records WHERE user_id=? AND module='service_period'",[$id])->fetch()){foreach(['company_code','residence_company_code','attendance_required','leave_eligible','payroll_visible','bank_visible']as $key)if(array_key_exists($key,$previousProfile))$b['profile'][$key]=$previousProfile[$key];$oldHire=query('SELECT hired_on FROM ep_users WHERE id=?',[$id])->fetchColumn();$hired=$oldHire?:null;}
    {foreach(['residence_file_id','residence_company_code']as $residenceKey134){if($id)$b['profile'][$residenceKey134]=$previousProfile[$residenceKey134]??'';else unset($b['profile'][$residenceKey134]);}}
    if(!($b['profile']['fixed_salary']??true)&&(float)($b['salary']??0)!==0.0)fail('بدون راتب ثابت يجب أن يكون الراتب صفرًا');
    if(!empty($b['password']))$b['profile']['must_change_password']=true;else if(isset($previousProfile['must_change_password']))$b['profile']['must_change_password']=$previousProfile['must_change_password'];
    if($id&&!empty($b['keep_salary130'])){$salary130=employee_editor_dates130(query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch())['salary'];if(!$salary130||abs((float)$b['salary']-$salary130['amount'])>.0001||(bool)($b['profile']['fixed_salary']??true)!==$salary130['fixed'])fail('تغيرت بيانات الراتب؛ أعد تحميل الموظف.');$b['salary']=(float)query('SELECT salary FROM ep_users WHERE id=?',[$id])->fetchColumn();$b['profile']['fixed_salary']=$previousProfile['fixed_salary']??true;}
    $textState170=text_user_prepare170($b,$u);
    $args=[$username,$name,$role,$manager?:null,mb_substr((string)($b['department']??''),0,100),mb_substr((string)($b['job_title']??''),0,100),$type,$active,empty($b['show_sick'])?0:1,$hired,max(0,(float)($b['salary']??0)),jencode($b['profile']??[])];
    if(!empty($b['password']))v28_password((string)$b['password'],$role);
    db()->beginTransaction();if(contract_profile119($previousProfile)!==contract_profile119($b['profile']))commission_rules_lock();if(!$id){$b['profile']['employee_code']=next_code('employee');$args[11]=jencode($b['profile']);}
    if($id){$previous=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$previous)fail('الموظف غير موجود.');text_user_check170($textState170,$previous);if($previous['username']!==$username&&query('SELECT id FROM ep_history WHERE employee_code=?',[employee_key($previous)])->fetch()&&!isset($previousProfile['employee_code']))fail('أضف الكود الثابت عبر ترقية البيانات قبل تغيير اسم الدخول.');if(empty($b['keep_salary130'])&&(($previousProfile['fixed_salary']??true)!==($b['profile']['fixed_salary']??true)||abs((float)$previous['salary']-(float)($b['salary']??0))>.0001||!empty($b['salary_effective_from'])))salary_record(array_replace($previous,['username'=>$username]),(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid,(bool)($b['profile']['fixed_salary']??true),$b['salary_effective_to']??null);query('UPDATE ep_users SET username=?,name=?,role=?,manager_id=?,department=?,job_title=?,staff_type=?,active=?,show_sick=?,hired_on=?,salary=?,profile_json=? WHERE id=?',[...$args,$id]);}
    else{v28_password((string)($b['password']??''),$role);query('INSERT INTO ep_users(username,name,role,manager_id,department,job_title,staff_type,active,show_sick,hired_on,salary,profile_json,password_hash,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[...$args,password_hash($b['password'],PASSWORD_DEFAULT),utc_now()]);$id=(int)db()->lastInsertId();salary_record(['id'=>$id,'role'=>$role,'username'=>$username,'hired_on'=>$hired,'profile_json'=>$args[11]],(float)($b['salary']??0),(string)($b['salary_effective_from']??''),(string)($b['salary_note']??''),$uid,(bool)($b['profile']['fixed_salary']??true),$b['salary_effective_to']??null);}
    if(empty($b['id'])&&$role!=='admin'&&$hired&&!empty($b['profile']['company_code'])){$p=$b['profile'];$period=['company_code'=>$p['company_code'],'department'=>$args[4],'job_title'=>$args[5],'staff_type'=>$type,'attendance'=>(bool)($p['attendance_required']??true),'leave'=>(bool)($p['leave_eligible']??true),'payroll'=>(bool)($p['payroll_visible']??true),'bank'=>(bool)($p['bank_visible']??true)];query("INSERT INTO ep_records(module,user_id,title,date_from,status,body_json,created_at,updated_at) VALUES('service_period',?,?,?,'approved',?,?,?)",[$id,'فترة خدمة',$hired,jencode($period),utc_now(),utc_now()]);unset($GLOBALS['EP_SERVICE_CACHE'][$id]);}
    $bankStaff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();if(isset($previous)&&array_key_exists('editor134',$b)&&employee_bank_unchanged134($previous,decode($bankStaff['profile_json']),$b)){unset($b['bank_effective_from'],$b['bank_effective_to']);$skipBank134=true;}else $skipBank134=false;$bankProfile=decode($bankStaff['profile_json']);$oldBankProfile=isset($previous)?decode($previous['profile_json']):[];$hasBank=false;$bankChanged=false;foreach(['bank_name','bank_account','iban']as $k)if(trim((string)($bankProfile[$k]??''))!==''||trim((string)($oldBankProfile[$k]??''))!=='')$hasBank=true;foreach(['bank_name','bank_account','iban','civil_id','name_en']as $k)if((string)($bankProfile[$k]??'')!==(string)($oldBankProfile[$k]??''))$bankChanged=true;if(!$skipBank134&&(($hasBank&&$bankChanged)||!empty($b['bank_effective_from'])))bank_identity_record($bankStaff,$bankProfile,(string)($b['bank_effective_from']??''),$uid,$b['bank_effective_to']??null);
    if(!empty($b['password'])&&isset($b['id'])){if(!empty($b['password']))v28_password((string)$b['password'],$role);query('UPDATE ep_users SET password_hash=? WHERE id=?',[password_hash($b['password'],PASSWORD_DEFAULT),$id]);}

    if(isset($previous))employee_job134($u,$previous,$bankStaff);
    employee_commission_save130($id,$previousProfile,$b['profile']);
    contract_save119($id,isset($previous)?decode($previous['profile_json']):[],$b['profile']);
    if(array_key_exists('editor134',$b)){require_once __DIR__.'/app/employee-editor.php';employee_editor_extras134($u,$id,$b['editor134'],empty($b['id']));}
    $beforeExempt150=isset($previous)?(bool)(decode($previous['profile_json'])['permit_exempt150']??false):false;if($beforeExempt150!==$b['profile']['permit_exempt150'])audit('work_permit_exemption','user:'.$id,['before'=>$beforeExempt150,'after'=>$b['profile']['permit_exempt150']]);
    $birthBefore155=isset($previous)?(decode($previous['profile_json'])['birth_date155']??''):'';
    if($birthBefore155!==($b['profile']['birth_date155']??''))audit('employee_birth_date','user:'.$id,['before'=>$birthBefore155,'after'=>$b['profile']['birth_date155']]);
    text_user_persist170($id,$textState170);audit('user_save','user:'.$id);db()->commit();unset($GLOBALS['EDITOR_FILES143']);$result=['ok'=>true,'id'=>$id];break;
   case 'settings_save':
    admin_only($u);$values=$b['values']??[];if(!is_array($values))fail('الإعدادات غير صالحة.');foreach(array_keys($values) as $key)if(!preg_match('/^[a-z][a-z0-9_]{0,79}$/',(string)$key))fail('الإعدادات غير صالحة.');if(isset($values['app_name31'])&&(!is_string($values['app_name31'])||trim($values['app_name31'])===''||mb_strlen($values['app_name31'])>60))fail('اسم التطبيق مطلوب ولا يتجاوز 60 حرفًا.');
    foreach(['locations','preparation_entry40','company_document_types194','owner_schedule396','owner_policy393','upgrade_v32','upgrade_v31','owner31','supervisors31','supervisor_revision31','data_translation31','data_translation_revision31','data_translation_enabled31','upgrade_v30','access_groups29','upgrade_v29','customer_routes','offers_languages','offers_default_language','public_language_approvals','public_languages','employee_languages','employee_default_language','admin_default_language','translation_enabled','translation_context','translation_budget','ratings_enabled','ratings_visibility','permissions','history_approved','history_approved_at','history_approved_by','operation_start','attendance_enabled','reports_enabled','payroll_enabled','leave_enabled','attendance_start','leave_start','payroll_start','reports_start','payroll_start_month']as $protected)if(array_key_exists($protected,$values))fail('هذه إعدادات نظام محمية؛ استخدم الشاشة المختصة.');
    unset($values['public_footer'],$values['public_ui'],$values['public_original_texts']);
    unset($values['overtime_multiplier']);$values['overtime_enabled']=false;
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
   case 'policy_editor_context':policy_context_access32($u);$result=['values'=>policy_editor_defaults361(),'locations'=>settings()['locations'],'companies'=>company_choices30($u,null,false)];break;
   case 'policy_staff_list':$result=policy_staff_list32($u);break;
   case 'policies':
    policy_context_access32($u);
    $result=query('SELECT p.*,u.name AS employee_name FROM ep_policies p LEFT JOIN ep_users u ON u.id=p.user_id ORDER BY effective_from DESC,id DESC')->fetchAll();
    if($u['role']!=='admin'){$ids=permission_scope29($u,'policy_staff');$result=array_values(array_filter($result,fn($r)=>!$r['user_id']||in_array((int)$r['user_id'],$ids,true)));}break;
   case 'leave_overview':
    if($u['role']==='employee')fail('للإدارة فقط.',403);$result=[];$people195=scoped_people195(employee_scope69(scope_ids($u),max(0,(int)($_GET['employee69']??0))));
    if($page195=page_request195()){$all195=count($people195);$f195=$page195['filters'];$people195=array_values(array_filter($people195,function($s)use($f195){return page_matches195(['id'=>$s['id'],'name'=>$s['name'],'name_en'=>decode($s['profile_json'])['name_en']??'','employee_code'=>employee_key($s)],$f195);}));$window195=page_rows195($people195,$page195,$all195);$people195=$window195['rows195'];$GLOBALS['PAGE195_READY']=$window195['page195'];}
    leave_prefetch195($people195,local_time()->format('Y-m-d'));foreach($people195 as $staff){$target=(int)$staff['id'];$balance=leave_balance($target,$cfg);if($u['role']!=='admin')unset($balance['valuation_salary'],$balance['cash_value'],$balance['salary_effective_from']);$result[]=['id'=>$target,'name'=>$staff['name'],'name_en'=>decode($staff['profile_json'])['name_en']??'','employee_code'=>employee_key($staff),'balance'=>$balance];}break;
   case 'leave_balance':
    $target=employee_view_target($u,isset($_GET['user_id'])?(int)$_GET['user_id']:null);
    $result=leave_balance($target,$cfg,$u['role']==='admin'&&!empty($_GET['date'])?valid_date($_GET['date']):null);if($u['role']!=='admin')unset($result['valuation_salary'],$result['cash_value'],$result['salary_effective_from']);break;
   case 'dashboard':
    $date=local_time()->format('Y-m-d');$result=['staff'=>[],'expiring'=>[],'stale'=>[]];
    $visible=array_fill_keys(scope_ids($u),true);$people=array_values(array_filter(query('SELECT * FROM ep_users')->fetchAll(),fn($p)=>isset($visible[(int)$p['id']])));service_prefetch($people);
    $eventMap=[];$documentMap=[];$locationMap=[];$compact140=!empty($_GET['compact140']);$canDocuments=permission_has($u,'document');$canTracking=!$compact140&&permission_has($u,'tracking');
    foreach(array_chunk(array_keys($visible),500) as $ids){
     $marks=implode(',',array_fill(0,count($ids),'?'));
     foreach(query("SELECT user_id,event_type FROM ep_events WHERE user_id IN ($marks) AND work_date=? ORDER BY received_at,id",[...$ids,$date])->fetchAll()as $row)$eventMap[(int)$row['user_id']][]=$row;
     if(!$compact140)foreach(query("SELECT user_id,id,title,body_json FROM ep_records WHERE user_id IN ($marks) AND module='document' AND status='active'",$ids)->fetchAll()as $row)if($canDocuments||(int)$row['user_id']===$uid)$documentMap[(int)$row['user_id']][]=$row;
     if($canTracking)foreach(query("SELECT l.user_id,l.received_at FROM ep_locations l JOIN (SELECT user_id,MAX(id) AS last_id FROM ep_locations WHERE user_id IN ($marks) GROUP BY user_id) latest ON l.id=latest.last_id",$ids)->fetchAll()as $row)$locationMap[(int)$row['user_id']]=$row;
    }
    attendance_prefetch113($people,$date,$date);$peopleById=array_column($people,null,'id');foreach(array_keys($visible)as $target){
     $staff=$peopleById[$target]??null;if(!$staff||!service_state($staff,$date)['employed'])continue;
     $events=$eventMap[$target]??[];
     $d=attendance_report($target,$date,$date)[0];
     $result['staff'][]=['id'=>$target,'name'=>$staff['name'],'state'=>event_state($events),'late'=>$d['late_minutes'],'missing'=>$d['missing'],'unclosed'=>$d['unclosed_permission'],'absence'=>$d['absence']];
     foreach(($documentMap[$target]??[])as $r){
      $expiry=decode($r['body_json'])['expiry']??'';if($expiry&&$expiry<=(new DateTimeImmutable($date))->modify('+30 days')->format('Y-m-d'))$result['expiring'][]=['name'=>$staff['name'],'title'=>$r['title'],'expiry'=>$expiry];
     }
     if($canTracking&&$staff['staff_type']==='rep'&&in_array(event_state($events),['working','away'],true)){
      $loc=$locationMap[$target]??null;
      if(!$loc||time()-strtotime($loc['received_at'].' UTC')>(int)$cfg['tracking_stale'])$result['stale'][]=$staff['name'];
     }
    }
    break;
   case 'policy_manage_preview':$result=policy_preview32($u,(int)($_GET['id']??0));break;
   case 'policy_delete':$result=policy_delete32($u,$b);break;
   case 'policy_save':
    if(($b['schedule_kind']??'')!=='temporary')policy_access32($u,(int)($b['user_id']??0),empty($b['manage_mode'])||($b['manage_mode']??'')==='version'?'create':'edit');$from=valid_date($b['effective_from']??'');$to=valid_date($b['effective_to']??null);if(!$from||($to&&$to<$from))fail('فترة السياسة غير صالحة.');
    $policyPair173=policy_prepare173($b,$actor29);
    $p=array_replace(default_policy(),$b['values']??[]);unset($p['_schedule38'],$p['_text170']);if($policyPair173)$p['_text170']=$policyPair173['pairs'];
    foreach($p['holiday_dates']??[]as $date)valid_date($date);
    foreach(['start','end']as $k)if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$p[$k]))fail('موعد الدوام غير صالح.');
    if($p['end']<=$p['start'])fail('هذه النسخة تتطلب انتهاء الدوام في نفس اليوم بعد بدايته.');
    foreach(['max_accuracy','location_max_age','photo_max_age']as $k)if((float)$p[$k]<=0)fail('حدود الدقة والحداثة يجب أن تكون موجبة.');
    foreach(['grace_minutes','missing_minutes','break_minutes']as $k)if((float)$p[$k]<0)fail('القيمة لا يمكن أن تكون سالبة.');
    if(!isset($p['punch_rules39'])){
    $p['forget_escalation_enabled']=$p['forget_escalation_enabled']??!empty($p['forget_tiers']);if(!is_bool($p['forget_escalation_enabled']))fail('حالة تصعيد الجزاء غير صالحة.');if(!is_array($p['forget_tiers']))fail('شرائح التصعيد غير صالحة.');if($p['forget_enabled']&&$p['forget_escalation_enabled']&&!$p['forget_tiers'])fail('أضف شريحة تصعيد أو أوقف تصعيد الجزاء.');
    foreach($p['forget_tiers']as $tier)if(!isset($tier['from'],$tier['days'])||(int)$tier['from']<1||(float)$tier['days']<0)fail('شريحة النسيان غير صالحة.');
    if(!in_array($p['forget_period'],['month','year'],true)||!in_array($p['forget_unit'],['day','punch'],true)||!in_array($p['penalty_mode'],['replace','add'],true))fail('سياسة النسيان غير صالحة.');
    }
    policy_validate_weekly32($p);foreach($p['weekly']??[['rest'=>false,'start'=>$p['start'],'end'=>$p['end']]]as $shift39)if(empty($shift39['rest'])&&(float)$p['break_minutes']>=(strtotime($shift39['end'])-strtotime($shift39['start']))/60)fail('مدة الاستراحة يجب أن تكون أقل من مدة الدوام.');if(($b['schedule_kind']??'')!=='temporary'&&function_exists('presence_prepare_policy'))presence_prepare_policy($p,(int)($b['user_id']??0),$from);punch_validate39($p);$p['configured']=true;
    if(isset($b['schedule_kind'])){if(!in_array($b['schedule_kind'],['basic','temporary'],true))fail('نوع الجدول غير صالح.');if($b['schedule_kind']==='temporary'){$result=schedule_temporary_save38($actor29,$b,$p,$from,$to);break;}$to=null;$p['_schedule38']=['kind'=>'basic'];if(!empty($b['user_id'])){$state38=service_state(employee_required((int)$b['user_id']),$from);if(!$state38['employed'])fail('بداية الجدول يجب أن تكون داخل فترة خدمة الموظف.');$p['_schedule38']['service_from']=$state38['from'];}}
    db()->beginTransaction();policy_lock_scope32((int)($b['user_id']??0));if($policyPair173)text_pairs_index170($policyPair173['pairs']);if(empty($b['manage_mode'])&&policy_impact32(['id'=>0,'user_id'=>(int)($b['user_id']??0),'effective_from'=>$from,'effective_to'=>$to])['used'])fail('الفترة مرتبطة بتشغيل أو كشوف رواتب؛ اختر تاريخ سريان قادمًا لا يغير الحسابات السابقة.',409);$mode=(string)($b['manage_mode']??'');$old=null;
    if($mode!==''){if(!in_array($mode,['edit','version'],true))fail('طريقة تعديل غير صالحة');$old=policy_locked32((int)($b['id']??0));if(function_exists('schedule_temp38')&&schedule_temp38($old))fail('استخدم نموذج الجدول المؤقت لتعديله.');policy_assert32($old,$b,$mode==='edit');if((int)($old['user_id']??0)!==(int)($b['user_id']??0))fail('لا يمكن نقل السياسة لموظف آخر.');
     if($mode==='edit'){if(($b['schedule_kind']??'')==='basic'){$to=$old['effective_to']?:null;if($to&&$from>$to)fail('بداية السياسة لا يمكن أن تتجاوز نهاية فترتها المسجلة.');}policy_conflict32((int)($old['user_id']??0),$from,$to,(int)$old['id']);$candidate=$old;$candidate['effective_from']=$from;$candidate['effective_to']=$to;if(policy_impact32($candidate)['used'])fail('الفترة الجديدة مرتبطة بسجلات؛ لا يسمح بتغيير الحسابات السابقة.',409);query('UPDATE ep_policies SET effective_from=?,effective_to=?,label=?,value_json=? WHERE id=?',[$from,$to,mb_substr((string)$b['label'],0,150),jencode($p),$old['id']]);$saved392=policy_saved392((int)$old['id'],$p);audit('policy_edit','policy:'.$old['id'],['before'=>$old,'after'=>['from'=>$from,'to'=>$to,'values'=>$p],'reason'=>$b['reason']??'']);db()->commit();$result=$saved392;break;}
     if($from<=local_time()->format('Y-m-d')||$from<=$old['effective_from'])fail('التغيير يبدأ من يوم قادم بعد بداية السياسة الأصلية؛ لا نعيد حساب الماضي.');
     $candidate=$old;$candidate['effective_from']=$from;$candidate['effective_to']=$to;if(policy_impact32($candidate)['payroll']>0)fail('توجد كشوف رواتب للفترة الجديدة؛ راجعها قبل تغيير السياسة.',409);
     $conflicts38=query('SELECT * FROM ep_policies WHERE id<>? AND (user_id=? OR (user_id IS NULL AND ?=0)) AND effective_from>=? AND effective_from<=?',[$old['id'],(int)($old['user_id']??0),(int)($old['user_id']??0),$from,$to??'9999-12-31'])->fetchAll();$conflict=null;foreach($conflicts38 as $row38)if(!function_exists('schedule_temp38')||!schedule_temp38($row38)){$conflict=$row38;break;}if($conflict)fail('توجد سياسة لاحقة في الفترة؛ راجعها قبل إنشاء النسخة الجديدة. '.policy_conflict_message371($conflict),409);
     if(!$old['effective_to']||$from<=$old['effective_to'])query('UPDATE ep_policies SET effective_to=? WHERE id=?',[(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d'),$old['id']]);
    }
    policy_conflict32((int)($b['user_id']??0),$from,$to);query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[(int)($b['user_id']??0)?:null,$from,$to,mb_substr((string)($b['label']??'سياسة دوام'),0,150),jencode($p),utc_now()]);
    $newPolicyId33=db()->lastInsertId();if($mode==='version'&&$old&&$to)policy_resume33($old,$from,$to);
    $saved392=policy_saved392((int)$newPolicyId33,$p);audit($mode==='version'?'policy_version':'policy_create','policy:'.$newPolicyId33,['before'=>$old,'reason'=>$b['reason']??'','from'=>$from,'to'=>$to,'values'=>$p]);db()->commit();$result=$saved392;break;
   case 'capture_ticket':
    $context199=departure_context199($u);departure_check_context199($b,$context199);$service=service_state($u,$context199['work_date']);if(!pay35_attendance($u,$context199['work_date']))fail('هذا الموظف لا يخضع للحضور في هذا التاريخ.');
    $ticket=bin2hex(random_bytes(24));$_SESSION['capture_ticket']=['token'=>$ticket,'at'=>time(),'work_date199'=>$context199['work_date']];$result=['ticket'=>$ticket,'server_time'=>utc_now()];break;
   case 'event':
    if(in_array($b['event_type']??'',['permission_exit','permission_return'],true))fail('سجل البصمة من إذن الخروج المعتمد.',403);
    
    $rid=(string)($b['request_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$rid))fail('رقم الطلب غير صالح.');
    db()->beginTransaction();$lockedUser=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid])->fetch();
    $old=query('SELECT * FROM ep_events WHERE request_id=?',[$rid])->fetch();
    if($old){if((int)$old['user_id']!==$uid)fail('رقم طلب مستخدم.',409);$result=['ok'=>true,'id'=>$old['id'],'received_at'=>$old['received_at'],'duplicate'=>true];db()->commit();break;}
    unset($GLOBALS['EP_SERVICE_CACHE'][$uid]);$context199=departure_context199($lockedUser);departure_check_context199($b,$context199);$date=$context199['work_date'];$service=service_state($lockedUser,$date);if(!pay35_attendance($lockedUser,$date))fail('هذا الموظف لا يخضع للحضور في هذا التاريخ.');$u['staff_type']=$service['staff_type']??$lockedUser['staff_type'];$policy=$context199['policy'];$p=$policy['values'];if(!$p['configured'])fail('الإدارة لم تعتمد جدول دوامك بعد.');
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
     $ticket=$_SESSION['capture_ticket']??[];if(isset($ticket['work_date199'])&&$ticket['work_date199']!==$date)fail('التقط صورة جديدة ليوم الدوام الحالي.');if(!isset($ticket['token'],$ticket['at'])||!hash_equals($ticket['token'],(string)($b['capture_ticket']??''))||time()-(int)$ticket['at']>(int)$p['photo_max_age'])fail('التقط صورة جديدة من شاشة التسجيل.');
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
    if($module==='evaluation'&&($_GET['source182']??'')==='system'){$result=evaluation_system194($actor29);break;}
    $ids=scope_ids($u);$ids=employee_scope69($ids,max(0,(int)($_GET['employee69']??0)));$marks=$ids?implode(',',array_fill(0,count($ids),'?')):'NULL';
    $recordFilter=(int)($_GET['record_id']??0);$recordWhere=$recordFilter?' AND r.id=?':'';if($module==='request'&&!$recordFilter&&in_array($_GET['request_scope50']??'',['admin','attendance'],true))$recordWhere.=(($_GET['request_scope50']==='attendance')?" AND JSON_QUERY(r.body_json,'$._punch39') IS NOT NULL":" AND JSON_QUERY(r.body_json,'$._punch39') IS NULL");if(!empty($_GET['summary140'])&&in_array($module,['leave','sick','external','request'],true)){require_once __DIR__.'/app/dashboard.php';$result=dashboard_requests140($u,$module,$ids,$recordWhere,$recordFilter?[$recordFilter]:[]);break;}$workspace173=!empty($_GET['workspace173'])&&(int)($_GET['employee69']??0)>0&&in_array($module,record_modules173(),true);$searchLimit32='';$result=record_sql_page195($module,$u,$ids,$recordWhere,$recordFilter?[$recordFilter]:[])??query("SELECT {$searchLimit32}r.*,u.name AS employee_name,COALESCE(JSON_VALUE(u.profile_json,'$.employee_code'),u.username) AS employee_code FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE r.module=? AND r.user_id IN ($marks)$recordWhere ORDER BY r.id DESC",[$module,...$ids,...($recordFilter?[$recordFilter]:[])])->fetchAll();
    foreach($result as &$row199)$row199['revision199']=record_revision173($row199);unset($row199);
    if($module==='leave_cash'&&!isset($GLOBALS['PAGE195_READY']))$result=leave_paid_rows198($result);
    if($u['role']!=='admin'){
     $result=array_values(array_filter($result,fn($r)=>(int)$r['user_id']!==$uid||!in_array($module,['discipline','adjustment'],true)||$r['status']==='approved'));
     foreach($result as &$row){$rb=decode($row['body_json']);unset($rb['_sick_days']);$row['body_json']=jencode($rb);}unset($row);
    }
    $search32=(string)($_GET['q']??'');$result=record_search_rows32($result,$search32);if(in_array($module,record_modules173(),true))foreach($result as &$record173)$record173['revision173']=record_revision173($record173);unset($record173);if($module==='evaluation'&&($_GET['source182']??'')==='manager')$result=array_values(array_filter($result,fn($r)=>empty(decode($r['body_json'])['_auto182'])));
    if($module==='request'&&!empty($_GET['requests195'])&&($_GET['request_scope50']??'')==='admin')$result=array_merge($result,request_loans195($actor29));
    break;
   case 'record_save':
    $module=(string)($b['module']??'');if($module==='adjustment')fail('الإضافة الشهرية تسجل من شاشة تسجيل لإعداد الرواتب.');$meta=modules()[$module]??null;if(!$meta)fail('القسم غير موجود.');
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
    if($module==='leave_cash'){if(!is_numeric($clean['days']??'')||!is_finite((float)$clean['days'])||(float)$clean['days']<=0)fail('أدخل عدد أيام تسوية صحيحًا.');if(($clean['payment_method']??'')==='bank_salary'&&!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$clean['payroll_month']??''))fail('حدد شهر الإضافة للراتب.');if(($clean['payment_method']??'')!=='bank_salary')$clean['payroll_month']='';if(!$from)fail('حدد تاريخ احتساب الرصيد.');if(!empty($clean['leave_id'])&&!query("SELECT id FROM ep_records WHERE id=? AND user_id=? AND module='leave' AND status='approved'",[(int)$clean['leave_id'],$target])->fetch())fail('الإجازة المرتبطة يجب أن تكون معتمدة لنفس الموظف.');if(!in_array($clean['payment_method']??'',['cash','bank_salary','bank_separate'],true))fail('حدد طريقة الصرف.');}
    if($module==='proof'&&!empty($clean['deadline'])){$deadline195=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$clean['deadline']);if(!$deadline195||$deadline195->format('Y-m-d\TH:i')!==$clean['deadline'])fail('موعد الاستجابة غير صالح.');}
    if(in_array($module,record_modules173(),true)){if($from&&$to&&$to<$from)fail('فترة السجل غير صالحة.');foreach(['expiry','return_date']as $date173)if(isset($clean[$date173])&&$clean[$date173]!=='')$clean[$date173]=valid_date($clean[$date173]);if($module==='evaluation'&&$clean['score']!==''&&(!is_numeric($clean['score'])||(float)$clean['score']<0||(float)$clean['score']>100))fail('التقييم من صفر إلى 100.');}
    if(in_array($module,['request','document','custody','discipline','evaluation'],true)){
     $categories49=['request'=>['attendance','salary','letters','data','assets','complaints','other'],'document'=>['contract','personal','certificates','letters','other'],'custody'=>['financial','electronics','sim','vehicles','keys','tools','other'],'discipline'=>['notice','warning','penalty','decision','appeal','other'],'evaluation'=>['trial','periodic','performance','attendance','behavior','improvement','other']];
     $category49=(string)($clean['record_category49']??'');
     if($category49!==''&&!in_array($category49,$categories49[$module],true))fail('التصنيف غير صالح.');
     if(($module==='discipline'&&$category49==='appeal')||($module==='evaluation'&&$category49==='improvement')){
      $parent49=(int)($clean['related_record49']??0);$original49=query('SELECT id FROM ep_records WHERE id=? AND user_id=? AND module=?',[$parent49,$target,$module])->fetch();
      if(!$original49)fail('حدد رقم السجل الأصلي لنفس الموظف في هذه الشاشة.');
     }
     if($module==='custody'&&!in_array($clean['custody_state49']??'',['','active','returned','settled'],true))fail('حالة العهدة غير صالحة.');
    }
    if($module==='request'&&isset($body['_punch39'])){db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$target]);$incident39=punch_prepare_request39($actor29,$target,$from,$to,$body);$clean['_punch39']=$incident39;foreach(query("SELECT body_json FROM ep_records WHERE module='request' AND user_id=? AND date_from=? AND status IN ('pending','revision')",[$target,$from])->fetchAll()as $pending39){$previous39=decode($pending39['body_json'])['_punch39']??[];if(($previous39['kind']??'')===$incident39['kind']&&($previous39['key']??'')===$incident39['key'])fail('يوجد طلب قيد المراجعة لنفس الواقعة.');}}
    if($module==='external'){
     $p=policy_for($target,$from)['values'];[$lat,$lon,$acc]=validate_location($b,$p);
     $clean['_location']=['latitude'=>$lat,'longitude'=>$lon,'accuracy'=>$acc,'received_at'=>utc_now()];
    }
    if(!db()->inTransaction()){db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$target]);}
    if($meta['approval'])$clean['_workflow199']=workflow_new199($actor29,$target,$module,$clean);
    if($recordPair173){$clean=record_attach173($clean,$recordPair173);}
    $fid=isset($_FILES['file'])?upload_file($_FILES['file'],$uid,$module==='sick'):null;
    if($module==='sick'&&!$fid)fail('صورة شهادة المرضية مطلوبة.');
    $title=trim((string)($b['title']??$meta['label']));if(!$title||mb_strlen($title)>180)fail('عنوان السجل غير صالح.');
    $status=$meta['approval']?'pending':'active';
    query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,amount,status,body_json,file_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
     [$module,$target,$title,$from,$to,max(0,(float)($b['amount']??0)),$status,jencode($clean),$fid,utc_now(),utc_now()]);
    $id=(int)db()->lastInsertId();if($module==='evaluation'){$clean['reference185']=evaluation_reference194(($clean['record_category49']??'')==='improvement'?'IP':'EV',(int)local_time()->format('Y'));query('UPDATE ep_records SET body_json=? WHERE id=?',[jencode($clean),$id]);}audit('record_create',$module.':'.$id);
    if($module==='proof')notice($target,'طلب إثبات تواجد',$title.' — افتح المتابعة وأرسل موقعك الحالي.',true,['screen'=>$module,'record_id'=>$id]);
    if($meta['approval'])workflow_notify199(['id'=>$id,'user_id'=>$target,'module'=>$module,'title'=>$title,'body_json'=>jencode($clean)],'طلب جديد: '.$meta['label']);
    db()->commit();$result=['ok'=>true,'id'=>$id];break;
   case 'record_review':
    $id=(int)($b['id']??0);$status=(string)($b['status']??'');
    if(!in_array($status,['approved','rejected','revision','closed'],true))fail('القرار غير صالح.');
    db()->beginTransaction();$reviewTarget39=query('SELECT user_id FROM ep_records WHERE id=?',[$id])->fetchColumn();if($reviewTarget39)query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[(int)$reviewTarget39]);$r=query('SELECT * FROM ep_records WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$r)fail('السجل غير موجود.');
    record_check173($recordPair173,$r);
    if(!can_manage($u,(int)$r['user_id']))fail('ليس لديك صلاحية اعتماد هذا الموظف أو نفسك.',403);
    if(!in_array($r['status'],['pending','revision','active'],true))fail('القرار سبق اعتماده؛ لا تعدل التاريخ بصمت.');
    if(!isset(modules()[$r['module']]))fail('راجع السجل من شاشته المخصصة.',403);
    if(modules()[$r['module']]['approval']&&$status==='closed')fail('حدد موافقة أو رفض أو تعديل.');
    $body=record_attach173(decode($r['body_json']),$recordPair173);$intermediate199=workflow_review199($actor29,$r,$body,$status,(string)($b['note']??''),isset($b['revision199'])?(string)$b['revision199']:null);punch_review39($actor29,$r,$body,$status,(string)($b['note']??''));
    if($status==='approved'&&in_array($r['module'],['leave','sick'],true)){
     $target=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']])->fetch();foreach(days_between($r['date_from'],$r['date_to'])as $day)if(!service_state($target,$day)['employed'])fail('الفترة خارج خدمة الموظف.');
     if(query("SELECT TOP (1) id FROM ep_records WHERE id<>? AND user_id=? AND module IN ('leave','sick') AND status='approved' AND date_from<=? AND date_to>=?",[$id,$r['user_id'],$r['date_to'],$r['date_from']])->fetch())fail('الفترة تتداخل مع إجازة معتمدة.');
     if($r['module']==='sick'){foreach(historical_for((int)$target['id'],'sick_summary',substr($r['date_from'],0,4),substr($r['date_to'],0,4))as $h)if($r['date_from']<=decode($h['body_json'])['as_of'])fail('الفترة ضمن المرضيات التاريخية المستوردة؛ لا تعتمدها مرة ثانية.');$body['_sick_days']=sick_snapshot($r,$target,$cfg);}
     if($r['module']==='leave'){$hire=service_state($target,$r['date_from'])['from']??$target['hired_on'];if(v26_entitlements($target,$r['date_from'])['leave_days']&&$r['date_from']<(new DateTimeImmutable($hire))->modify('+'.(int)($cfg['leave_first_months']??9).' months')->format('Y-m-d'))fail('لم تكتمل مدة استحقاق أول إجازة');
      $requested=count(days_between($r['date_from'],$r['date_to']));$balance=leave_balance((int)$r['user_id'],$cfg,(new DateTimeImmutable($r['date_from']))->modify('-1 day')->format('Y-m-d'));$body['_approved_days']=$requested;$body['_balance_at_approval']=$balance['remaining'];$body['_excess_approved']=max(0,$requested-max(0,(float)$balance['remaining']));$body['expected_return']=(new DateTimeImmutable($r['date_to']))->modify('+1 day')->format('Y-m-d');$body['_start200']=['phase'=>'awaiting','approved_from'=>$r['date_from'],'approved_to'=>$r['date_to'],'expected_return'=>$body['expected_return']];
     }
    }
    if($status==='approved'&&$r['module']==='leave_cash'){query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']]);$days=(float)($body['days']??0);$balance=leave_balance((int)$r['user_id'],$cfg,$r['date_from']??local_time()->format('Y-m-d'));if($days<=0||$balance['cash_days']===null||$days>$balance['cash_days']+1e-9)fail('أيام التسوية تتجاوز الرصيد النقدي أو الرصيد غير محدد.');if(!in_array($body['payment_method']??'', ['cash','bank_salary','bank_separate'],true))fail('اختر طريقة صرف الإجازات.');if($body['payment_method']==='bank_salary'&&!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$body['payroll_month']??''))fail('حدد شهر إضافة مبلغ التسوية.');if($balance['valuation_salary']===null)fail('أدخل تاريخ الراتب قبل اعتماد تسوية الإجازة.');$amount=round($days*$balance['valuation_salary']/30,3);if($amount<=0)fail('قيمة صرف الإجازة يجب أن تكون موجبة؛ راجع الراتب الأساسي');$body['_approved_on']=local_time()->format('Y-m-d');$body['_valuation']=['date'=>$balance['valuation_date'],'salary'=>$balance['valuation_salary'],'effective_from'=>$balance['salary_effective_from'],'days'=>$days,'divisor'=>30,'amount'=>$amount];query('UPDATE ep_records SET amount=? WHERE id=?',[$amount,$id]);}

    if($status==='approved'&&$r['module']==='loan')$body['_disburse200']=['phase'=>'awaiting','approved_amount'=>round((float)$r['amount'],3),'installment'=>null,'deduction_month'=>null];
    if($status==='approved'&&$r['module']==='adjustment')v28_addition_check((int)$r['user_id'],(string)($body['payroll_month']??''),(float)$r['amount'],(string)($body['reason']??''),'addition');
    if($status==='approved'&&$r['module']==='external'&&$r['date_from']<local_time()->format('Y-m-d'))fail('لا اعتماد بأثر رجعي للانصراف الخارجي.');
    if($status==='approved'&&in_array($r['module'],['leave','sick','discipline','adjustment','leave_cash'],true))financial_invalidate((int)$r['user_id'],in_array($r['module'],['leave','sick'],true)?$r['date_from']:(($r['module']==='leave_cash'&&($body['payment_method']??'')!=='bank_salary')?$r['date_from']:($body['payroll_month'].'-01')));
    query('UPDATE ep_records SET status=?,body_json=?,reviewed_by=?,review_note=?,updated_at=? WHERE id=?',[$status,jencode($body),$uid,mb_substr((string)($b['note']??''),0,5000),utc_now(),$id]);
    punch_refresh_payroll39($r,$body,$status,$actor29);
    if($status==='approved'&&$r['module']==='leave_cash'){v26_cash_integrity((int)$r['user_id']);}notice((int)$r['user_id'],'قرار: '.modules()[$r['module']]['label'],$r['title'].' — '.$status.' — '.($b['note']??''),in_array($r['module'],['discipline','external'],true),['screen'=>$r['module'],'record_id'=>(int)$r['id']]);
    if($intermediate199)workflow_notify199(array_replace($r,['body_json'=>jencode($body)]),'طلب بانتظار مراجعة المشرف العام');
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
    if(isset($body['_punch39'])&&($from!==$r['date_from']||$to!==$r['date_to']))fail('لا يمكن نقل طلب البصمة إلى يوم آخر؛ أنشئ طلبًا جديدًا.');
    $body['_revisions'][]=['date_from'=>$r['date_from'],'date_to'=>$r['date_to'],'file_id'=>$r['file_id'],'at'=>utc_now(),'actor'=>$uid,'details'=>$body['_revision_details']??''];$fileId=$r['file_id'];if(!empty($_FILES['file']['tmp_name']))$fileId=upload_file($_FILES['file'],$uid,$r['module']==='sick');
    $body['_revision_details']=mb_substr((string)($b['details']??''),0,5000);
    foreach($body['_decisions199']??[]as $i=>$decision199)if(empty($decision199['withdrawn_at'])&&empty($decision199['invalidated_at']))$body['_decisions199'][$i]['invalidated_at']=utc_now();
    $oldEver199=!empty($body['_workflow199']['ever_approved']);$body['_workflow199']=workflow_new199($actor29,(int)$r['user_id'],$r['module'],$body);$body['_workflow199']['ever_approved']=$oldEver199;
    query("UPDATE ep_records SET date_from=?,date_to=?,body_json=?,file_id=?,status='pending',updated_at=? WHERE id=?",[$from,$to,jencode($body),$fileId,utc_now(),$r['id']]);
    workflow_notify199(array_replace($r,['body_json'=>jencode($body)]),'أعيد تقديم الطلب للمراجعة');
    audit('record_resubmit','record:'.$r['id']);db()->commit();$result=['ok'=>true];break;
   case 'record_reply':
    db()->beginTransaction();if($recordPair173)query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);$r=query('SELECT * FROM ep_records WITH (UPDLOCK, HOLDLOCK) WHERE id=? AND user_id=?',[(int)($b['id']??0),$uid])->fetch();if(!$r)fail('السجل غير موجود.');
    record_check173($recordPair173,$r);$body=record_attach173(decode($r['body_json']),$recordPair173);$body['_employee_reply']=mb_substr((string)($b['reply']??''),0,5000);
    query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$r['id']]);audit('record_reply','record:'.$r['id']);db()->commit();$result=['ok'=>true];break;
   case 'notifications':if(($_GET['box']??'')!=='options')leave_due_scan200();$result=($_GET['box']??'')==='options'?notice_options32($actor29):notice_list32($actor29);break;
   case 'notice_send':$result=notice_send32($actor29,$b);break;
   case 'notice_read':$result=notice_read32($uid,$b);break;
   case 'tracking':
    
    if(!$cfg['tracking_enabled'])fail('المتابعة الميدانية غير مفعلة.');
    if($u['staff_type']!=='rep')fail('المتابعة للمندوب فقط.');
    $trackingContext199=departure_context199($u);$date=$trackingContext199['work_date'];$p=$trackingContext199['policy']['values'];
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
   case 'attendance_today200':
    $result=attendance_today200($actor29);break;
   case 'report':
    $target=employee_view_target($u,isset($_GET['user_id'])?(int)$_GET['user_id']:null);
    $from=valid_date($_GET['from']??local_time()->format('Y-m-01'));$to=valid_date($_GET['to']??local_time()->format('Y-m-d'));$result=attendance_report($target,$from,$to);if($u['role']==='employee')foreach($result as &$day){unset($day['sick_paid']);}unset($day);break;
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
    $listMonth41=isset($_GET['month'])?(string)$_GET['month']:null;if($listMonth41!==null)pay35_month($listMonth41);$listScreen35=($_GET['tab35']??'')==='review'?'payroll_review35':'payroll';if($u['role']==='admin'||permission_has($u,$listScreen35,'view')){$GLOBALS['EP_SCREEN']=$listScreen35;$visible=scope_ids($u);$result=array_values(array_filter(($listMonth41!==null?query('SELECT p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id WHERE p.month_key=? ORDER BY u.name',[$listMonth41]):query('SELECT TOP (500) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id ORDER BY month_key DESC,u.name'))->fetchAll(),fn($r)=>in_array((int)$r['user_id'],$visible,true)));}else{$result=query("SELECT TOP (100) p.*,u.name FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id WHERE p.user_id=? AND p.status='approved' ORDER BY month_key DESC",[$uid])->fetchAll();if($listMonth41!==null)$result=array_values(array_filter($result,fn($r)=>$r['month_key']===$listMonth41));foreach($result as &$row){$calc=decode($row['calculation_json']);unset($calc['settings'],$calc['days_detail']);$row['calculation_json']=jencode($calc);}unset($row);}foreach(scope_ids($u)as $target)foreach(historical_for($target,'payroll',$listMonth41??'0000-01',$listMonth41??'9999-12')as $h){if($u['role']!=='admin'&&$target!==$uid)continue;$v=decode($h['body_json']);$name=query('SELECT name FROM ep_users WHERE id=?',[$target])->fetchColumn();$result[]=['id'=>'history-'.$h['id'],'user_id'=>$target,'name'=>$name,'month_key'=>$h['period_key'],'net_amount'=>$v['net'],'status'=>'approved','calculation_json'=>jencode(['salary'=>$v['salary'],'addition'=>$v['addition'],'deduction'=>$v['deduction'],'flags'=>[],'note'=>'كشف تاريخي مستورد؛ لا يعاد حسابه'])];}foreach($result as &$row41){$calc41=decode($row41['calculation_json']);$code41=pay41_company_code(employee_required((int)$row41['user_id']),$row41['month_key'],isset($calc41['work_company_code'])?$calc41:null);$row41['work_company41']=pay41_company_key($code41);$row41['company_name41']=pay41_company_name($code41);}unset($row41);break;
   case 'payroll_build':
    
    v26_permission($u,'payroll','create');if($cfg['sick_full_days']===null||$cfg['sick_half_days']===null)fail('حدد حدود المرضيات في إعدادات الإدارة قبل حساب الرواتب.');
    $month=(string)($b['month']??'');if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('الشهر غير صالح.');
    $from=$month.'-01';$to=(new DateTimeImmutable($from))->format('Y-m-t');
    if($to>=local_time()->format('Y-m-d'))fail('أنشئ الكشف بعد انتهاء الشهر.');
    db()->beginTransaction();
    commission_rules_lock();$company41=isset($b['company'])?(string)$b['company']:null;v26_payroll_snapshot_month($month,$u,$company41);payroll_build_month($month,$cfg,$company41);
    audit('payroll_build','month:'.$month,['company'=>$company41]);db()->commit();$result=['ok'=>true];break;
   case 'payroll_approve':
    
    admin_only($u);$gate200=query('SELECT user_id,month_key FROM ep_payroll WHERE id=?',[(int)($b['id']??0)])->fetch();if($gate200)leave_payroll_gate200((int)$gate200['user_id'],(string)$gate200['month_key'],false);db()->beginTransaction();$p=query('SELECT * FROM ep_payroll WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[(int)$b['id']])->fetch();if(!$p||$p['status']!=='submitted')fail('الكشف غير متاح للاعتماد.');
    query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$p['user_id']]);leave_payroll_gate200((int)$p['user_id'],(string)$p['month_key'],true);if(query("SELECT TOP (1) id FROM ep_payroll WHERE user_id=? AND status='approved' AND month_key>?",[$p['user_id'],$p['month_key']])->fetch())fail('يوجد كشف لاحق معتمد؛ راجع ترتيب الشهور.');
    $snapshot=decode($p['calculation_json']);if(empty($snapshot['attendance_version39']))fail('أعد الكشف للمحاسب لإعادة الحساب وفق مدد الخروج ومراجعة البصمات قبل الاعتماد.');if(empty($snapshot['monthly_grace_version397']))fail('أعد الكشف للمحاسب لإعادة الحساب وفق سماح التأخير الشهري قبل الاعتماد.');if((float)($snapshot['debt_deduction']??0)>v28_debt_balance((int)$p['user_id'],$p['month_key'].'-31')+.0001)fail('رصيد المديونية تغير؛ أعد مراجعة الخصم.');foreach($snapshot['loan_items']??[]as $item){$loan=query('SELECT amount FROM ep_records WHERE id=?',[$item['id']])->fetch();$paid=0.0;foreach(query("SELECT calculation_json FROM ep_payroll WHERE user_id=? AND status='approved'",[$p['user_id']])->fetchAll()as $past)foreach(decode($past['calculation_json'])['loan_items']??[]as $prior)if((int)$prior['id']===(int)$item['id'])$paid+=(float)$prior['amount'];if(!$loan||$paid+(float)$item['amount']>(float)$loan['amount']+.0001)fail('مسودة السلف قديمة؛ أعد بناء الكشف قبل الاعتماد.');}
    loan_deduction_guard200((int)$p['user_id'],(string)$p['month_key'],(float)($snapshot['debt_deduction']??0));if(decode($p['calculation_json'])['flags'])fail('عالج ملاحظات المسودة قبل الاعتماد.');
    financial_approval_snapshot199('payroll',$p,$u);v26_payroll_version($p,$u,'approved',(string)($b['note']??''));query("UPDATE ep_payroll SET status='approved',approved_by=?,approved_at=? WHERE id=?",[$uid,utc_now(),$p['id']]);
    notice((int)$p['user_id'],'اعتماد كشف الراتب','الشهر '.$p['month_key'].' — الصافي '.$p['net_amount'].' '.$cfg['currency'],true,['screen'=>'payroll','record_id'=>(int)$p['id'],'employee_id'=>(int)$p['user_id'],'month'=>$p['month_key']]);audit('payroll_approve','payroll:'.$p['id']);db()->commit();$result=['ok'=>true];break;
   default:fail('العملية غير موجودة.',404);
  }
 }
 // Bundle only the work-permit lookup fields, using the real actor's company scope.
 if($action==='v26_info'&&($_GET['kind']??'')==='work_permit'){
  $result['companies59']=company_choices30($actor29);
  $files59=query("SELECT id,body_json FROM ep_records WHERE module='company_file' ORDER BY id")->fetchAll();
  $files59=array_values(array_filter($files59,fn($r)=>company_allowed30($actor29,'work_permit',(string)(decode($r['body_json'])['company_code']??''))));
  foreach($files59 as &$file59)$file59['body_json']=jencode(array_intersect_key(decode($file59['body_json']),array_flip(['company_code','type','file_number'])));unset($file59);
  $result['files59']=sorted_records32($files59,'company_file');
 }
 if(isset($u)&&$u&&$_SERVER['REQUEST_METHOD']==='POST'&&!in_array($action,['login','logout','translation161_tick'],true))audit('api_change',$action,['before'=>$v26Before??null,'request'=>v26_audit_clean($b),'result'=>v26_audit_clean(is_array($result)?$result:[])]);
 if($action==='bootstrap'&&isset($u)&&$u&&personal_mode34($u)){if(!personal_has34($u,'attendance')&&!personal_has34($u,'report'))unset($result['state']);if(!personal_has34($u,'attendance')&&!personal_has34($u,'external','create')&&!personal_has34($u,'proof','respond'))unset($result['policy']);}
 if(isset($u)&&$u&&$action!=='bootstrap')$result=personal_result34($action,$u,$result);
 if($_SERVER['REQUEST_METHOD']==='GET'&&is_array($result))$result=employee_filter_result73($action,$result,max(0,(int)($_GET['employee73']??0)));
 if($action==='v28_info'&&isset($actor29))$result=company_result194($result,$actor29,(string)($_GET['kind']??''));
 if(in_array($action,['payroll_list','bank_list'],true)&&isset($actor29)&&is_array($result)&&$actor29['role']==='admin'&&!personal_mode34($actor29)){financial_approvals_prefetch199($action==='payroll_list'?'payroll':'bank',$result);$result=array_map(fn($r)=>financial_approval_describe199($action==='payroll_list'?'payroll':'bank',$r,$actor29),$result);}
 if($action==='list'&&isset($actor29)&&is_array($result))$result=array_map(fn($r)=>decision_describe199($r,$actor29),$result);
 if(in_array($action,['bootstrap','punch127_info'],true)&&isset($u)&&$u&&$u['role']!=='admin'){$context199=departure_context199($u);$result['departure199']=departure_public199($context199);$result['policy']=$context199['policy'];$result['state']=event_state(query('SELECT * FROM ep_events WHERE user_id=? AND work_date=? ORDER BY received_at,id',[$u['id'],$context199['work_date']])->fetchAll());$result['attendance_available35']=pay35_attendance($u,$context199['work_date']);if($action==='punch127_info'){$service199=service_state($u,$context199['work_date']);$result['service_attendance']=(bool)$service199['attendance'];$result['staff_type']=$service199['staff_type']??$u['staff_type'];}}
 $result=display_order32($action,$result);
 require_once __DIR__.'/app/display-names.php';$result=display_names143($action,$result);
 if(isset($u)&&$u&&is_array($result)&&isset($_GET['page195']))$result=workspace_page195($action,$result);
 if(isset($u)&&($u['role']??'')==='admin'&&!headers_sent())header('Server-Timing: app;dur='.round((hrtime(true)-$apiStarted32)/1e6,2).', db;dur='.round($GLOBALS['EP_SQL_MS32']??0,2).', queries;desc="'.(int)($GLOBALS['EP_SQL_COUNT32']??0).'"');
 // Project translations only after the ordinary permission and employee filters. Never expose another employee's private text catalog.
 $hrLocale160=isset($u)&&$u&&$_SERVER['REQUEST_METHOD']==='GET'&&!in_array($action,['translation31_list','languages_info','backup_info'],true)?data_translation_scope160($result,$action):null;
 echo jencode(['data'=>$result]+($hrLocale160!==null?['hr_localization160'=>$hrLocale160]:[]));
 if(isset($u)&&$u&&!in_array($action,['translation161_tick','login','logout','backup_restore_commit'],true)&&!str_starts_with($action,'backup_')&&!str_starts_with($action,'translation31_'))data_translation_after_response161();
}catch(Throwable $e){
 foreach($GLOBALS['EDITOR_FILES143']??[]as $path143)if(is_file($path143))@unlink($path143);unset($GLOBALS['EDITOR_FILES143']);
 if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
 try{if(app_installed()&&db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}
 if(http_response_code()<400)http_response_code(500);
 $safe=$e instanceof RuntimeException && !($e instanceof PDOException);
 if(!$safe)security_log($e);
 echo jencode(['error'=>$safe?$e->getMessage():'تعذر تنفيذ العملية؛ راجع سجل الخادم.']);
}
