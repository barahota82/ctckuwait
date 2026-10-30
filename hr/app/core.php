<?php
declare(strict_types=1);
date_default_timezone_set('UTC');
ini_set('display_errors','0');ini_set('log_errors','1');
require_once __DIR__.'/environment.php';
function security_log(Throwable $error,string $context='EP'):void {error_log($context.': '.get_class($error).' code='.$error->getCode().' file='.basename($error->getFile()).' line='.$error->getLine());}
set_exception_handler(static function(Throwable $error):void {
 security_log($error);
 if(PHP_SAPI==='cli'){fwrite(STDERR,"Operation failed; see the private server log.\n");exit(1);}
 if(!headers_sent()){http_response_code(500);header('Content-Type: text/plain; charset=utf-8');header('Cache-Control: no-store');}
 echo 'تعذر فتح الصفحة؛ راجع إعدادات الخادم.';
});
const EP_ROOT = __DIR__ . '/..';
function utc_now(): string { return gmdate('Y-m-d H:i:s'); }
function jencode(mixed $v): string { return json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR); }
function decode(string $s): array { $v=json_decode($s,true,512,JSON_THROW_ON_ERROR); return is_array($v)?$v:[]; }
function fail(string $message, int $status=422): never { http_response_code($status); throw new RuntimeException($message); }
function secure_request(): bool { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || (int)($_SERVER['SERVER_PORT']??0)===443; }
function session_init(): void {
 ini_set('session.use_strict_mode','1');
 session_name('EP_SESSION');
 session_set_cookie_params(['lifetime'=>0,'path'=>rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')), '/').'/','secure'=>secure_request(),'httponly'=>true,'samesite'=>'Strict']);
 session_start();
 $epoch=is_file(EP_ROOT.'/private/session-epoch.txt')?trim(file_get_contents(EP_ROOT.'/private/session-epoch.txt')):'';
 if(isset($_SESSION['epoch'])&&!hash_equals($_SESSION['epoch'],$epoch)){session_regenerate_id(true);$_SESSION=[];}
 $_SESSION['epoch']=$epoch;
 if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
}
require_once __DIR__.'/database.php';
function operation_lock():void {
 if(isset($GLOBALS['EP_OPERATION_LOCK']))return;
 $h=fopen(EP_ROOT.'/private/operation.lock','c');
 if(!$h||!flock($h,LOCK_SH|LOCK_NB))fail('تجري عملية نسخ أو استرجاع؛ حاول بعد قليل.',503);
 $GLOBALS['EP_OPERATION_LOCK']=$h;
}
function ratings_phone_ready():bool {return query("SELECT COL_LENGTH('dbo.ep_ratings','phone')")->fetchColumn()!==null;}
function site_v24_ready():bool{return ratings_phone_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v24'")->fetch();}
function site_v25_ready():bool{return site_v24_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v25'")->fetch();}
function site_v26_ready():bool{return site_v25_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v26'")->fetch();}
function site_upgrade(array $u):array {
 admin_only($u);require_once __DIR__.'/backups.php';backup_exclusive();if(!site_v32_ready()&&site_v31_ready()&&is_file(EP_ROOT.'/private/backup-key.json'))backup_run($u,'before_v32_update');if(!site_v29_ready()&&(int)query("SELECT COUNT(*) FROM ep_users WHERE role<>'admin'")->fetchColumn()>0)backup_run($u,'before_v29_permissions');
 if(!ratings_phone_ready())query("ALTER TABLE [dbo].[ep_ratings] ADD phone NVARCHAR(50) NOT NULL CONSTRAINT ep_rating_phone_v23 DEFAULT N''");
 db()->beginTransaction();$seed=decode(file_get_contents(__DIR__.'/cms-seed.json'));$page=current(array_filter($seed['pages'],fn($p)=>$p['slug']==='leadership'));$cards=[];foreach($page['sections']as $section)foreach($section['cards']as $card)$cards[$card['template_key']]=$card;
 foreach(query("SELECT c.* FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id JOIN ep_cms_pages p ON p.id=s.page_id WHERE p.slug='leadership'")->fetchAll()as $card){$original=$cards[$card['template_key']]??null;if(!$original)continue;$content=decode($card['content_json']);$changed=false;foreach($original['content']as $lang=>$values)if(empty($content[$lang]['subtitle'])){$content[$lang]['subtitle']=$values['subtitle'];$changed=true;}if($changed)query('UPDATE ep_cms_cards SET content_json=? WHERE id=?',[jencode($content),$card['id']]);}
 foreach(query("SELECT id,username,profile_json FROM ep_users WHERE role<>'admin'")->fetchAll()as $staff){$p=decode($staff['profile_json']);if(!isset($p['employee_code'])){$p['employee_code']=$staff['username'];query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($p),$staff['id']]);}}
 query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('upgrade_v24', 'true')) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);");query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('upgrade_v25', 'true')) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);");if(!site_v26_ready()){foreach(['annual_leave_days'=>30,'leave_accrual_divisor'=>365,'leave_accrual_mode'=>'daily','leave_exclude_approved_days'=>true,'leave_first_months'=>9,'leave_alert_first'=>30,'leave_alert_high'=>60,'eos_day_divisor'=>null,'eos_year_divisor'=>365,'eos_rule_confirmed'=>false]as $k=>$v)query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=target.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',[$k,jencode($v)]);query("INSERT INTO ep_settings(setting_key,value_json) VALUES('upgrade_v26','true')");}query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);",['upgrade_v28','true']);groups_migrate29();access_migrate30();migrate31();migrate32();$forget361=forget_migrate361();audit('site_upgrade','V36.1',['forget'=>$forget361]);db()->commit();return ['ok'=>true,'forget_policy'=>$forget361];
}
function db(): PDO {
 static $pdo=null;
 if($pdo)return $pdo;
 operation_lock();
 $config=require EP_ROOT.'/../config.php';
 return $pdo=database_connect($config);
}
function query(string $sql,array $args=[]): PDOStatement {
 if(stripos($sql,'ep_users')!==false&&preg_match('/\b(INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|TRUNCATE)\b/i',$sql))unset($GLOBALS['EP_EMPLOYEE_REQUEST119']);
 if(stripos($sql,'ep_settings')!==false&&preg_match('/\b(INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|TRUNCATE)\b/i',$sql))unset($GLOBALS['EP_REQUEST_SETTINGS32']);
 $started32=hrtime(true);$GLOBALS['EP_SQL_COUNT32']=($GLOBALS['EP_SQL_COUNT32']??0)+1;
 try{return database_statement(db(),$sql,$args);}finally{$GLOBALS['EP_SQL_MS32']=($GLOBALS['EP_SQL_MS32']??0)+(hrtime(true)-$started32)/1e6;}
}
function audit(string $action,string $subject,array $detail=[]): void {
 if(isset($GLOBALS['EP_EFFECTIVE_EMPLOYEE32']))$detail['_effective_employee_id']=(int)$GLOBALS['EP_EFFECTIVE_EMPLOYEE32'];
 query('INSERT INTO ep_audit(user_id,action,subject,detail_json,created_at) VALUES(?,?,?,?,?)',
 [$_SESSION['uid']??null,$action,$subject,jencode($detail),utc_now()]);
}
function settings(): array {
 $cacheable=($_SERVER['REQUEST_METHOD']??'')==='GET'&&!db()->inTransaction();
 if($cacheable&&isset($GLOBALS['EP_REQUEST_SETTINGS32']))return $GLOBALS['EP_REQUEST_SETTINGS32'];
 $s=default_settings();
 foreach(query('SELECT * FROM ep_settings')->fetchAll() as $r) $s[$r['setting_key']]=json_decode($r['value_json'],true);
 foreach(decode(file_get_contents(__DIR__.'/receipt-texts.json'))as $language=>$base)$s['public_ui'][$language]=array_replace($base,$s['public_ui'][$language]??[]);
 $s['employee_languages']=['ar','en'];$s['public_languages']=['ar','en','fil','hi','ur'];
 $s['offers_languages']=array_values(array_intersect($s['offers_languages'], $s['public_languages']))?:['ar'];
 foreach(['admin_default_language','employee_default_language']as $key)if(!in_array($s[$key],['ar','en'],true))$s[$key]='ar';
 if(!in_array($s['offers_default_language'],$s['offers_languages'],true))$s['offers_default_language']=$s['offers_languages'][0];
 foreach(default_settings()['public_ui']as $language=>$base)$s['public_ui'][$language]=array_replace($base,$s['public_ui'][$language]??[]);
 if($s['annual_leave_days']===null)$s['annual_leave_days']=30;
 if($cacheable)$GLOBALS['EP_REQUEST_SETTINGS32']=$s;
 return $s;
}
function default_settings(): array { return [
 'customer_routes'=>[],'public_original_texts'=>[],'offers_languages'=>['ar','en','fil','hi','ur'],'offers_default_language'=>'ar','public_language_approvals'=>[],'ratings_enabled'=>true,'ratings_visibility'=>'admin','public_languages'=>['ar','en','fil','hi','ur'],'employee_languages'=>['ar','en'],'employee_default_language'=>'ar','admin_default_language'=>'ar','translation_enabled'=>false,'translation_context'=>'Professional corporate website and employee administration. Preserve company names, dates, numbers and placeholders.','translation_budget'=>100000,'public_portal_label'=>[],'public_footer'=>[],'public_ui'=>['ar'=>['error_generic'=>'تعذر الإرسال. راجع البيانات وحاول مرة أخرى، أو حاول لاحقًا.','page_unavailable'=>'الصفحة غير متاحة'],'en'=>['error_generic'=>'We could not submit your request. Please check your details and try again, or try later.','page_unavailable'=>'Page unavailable']],'public_phone'=>'','public_email'=>'','public_hours'=>[],'public_address'=>[],'public_slogan'=>[],'public_site_title'=>[],'public_default_language'=>'ar','public_contact_enabled'=>true,
 'company_name'=>'شؤون الموظفين','company_name_en'=>'People Administration','site_url'=>'','logo_id'=>null,
 'timezone'=>'Asia/Kuwait','currency'=>'KWD','session_hours'=>8,
 'locations'=>[], 'tracking_enabled'=>false,'tracking_interval'=>300,'tracking_stale'=>1200,
 'retention_days'=>365, 'sick_year'=>'calendar','sick_full_days'=>null,'sick_half_days'=>null,
 'permissions'=>[], 'history_approved'=>false,'history_approved_at'=>null,'history_approved_by'=>null,'operation_start'=>null,'attendance_enabled'=>false,'reports_enabled'=>false,
 'leave_enabled'=>false,'attendance_start'=>null,'leave_start'=>null,'payroll_start'=>null,'reports_start'=>null,'payroll_start_month'=>null,'leave_alert_first'=>30,'leave_alert_high'=>60,'leave_first_months'=>9,'eos_day_divisor'=>null,'eos_year_divisor'=>365,'eos_rule_confirmed'=>false,'payroll_enabled'=>false,'salary_proration'=>'calendar','salary_divisor'=>30,'overtime_enabled'=>false,
 'overtime_multiplier'=>1.0,'work_hours'=>8,'annual_leave_days'=>30,'leave_year'=>'calendar','leave_accrual_mode'=>'annual','leave_accrual_divisor'=>365,'leave_exclude_approved_days'=>false
 ]; }
function default_policy(): array { return [
 'configured'=>false,'weekdays'=>[0,1,2,3,4],'start'=>'08:00','end'=>'16:00','break_minutes'=>0,
 'grace_minutes'=>0,'max_accuracy'=>100,'location_max_age'=>60,'photo_max_age'=>120,
 'office_photo'=>true,'rep_photo'=>true,'rep_evidence'=>false,'rep_morning_external'=>true,
 'forget_enabled'=>false,'forget_period'=>'month','forget_unit'=>'punch',
 'missing_minutes'=>120,'forget_tiers'=>[], 'penalty_mode'=>'replace','holiday_dates'=>[]
 ]; }
function local_time(?string $utc=null): DateTimeImmutable {
 return (new DateTimeImmutable($utc??'now',new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(settings()['timezone']));
}
function valid_date(mixed $v): ?string {
 if($v===null||$v==='')return null;
 if(!is_string($v)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v))fail('التاريخ غير صالح.');
 $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
 if(!$d||$d->format('Y-m-d')!==$v)fail('التاريخ غير صالح.');
 return $v;
}
function current_user(bool $authenticationOnly=false): array {
 $uid=$_SESSION['uid']??0;
 if(!$uid)fail('سجل الدخول أولًا.',401);
 $u=query('SELECT * FROM ep_users WHERE id=? AND active=1',[$uid])->fetch();
 if($u&&!hash_equals(hash('sha256',$u['password_hash']),(string)($_SESSION['auth_fingerprint']??''))){unset($_SESSION['uid']);fail('تغيرت بيانات الدخول؛ سجل الدخول مجددًا.',401);}
 if(!$u) {unset($_SESSION['uid']);fail('الحساب غير متاح.',401);}
 $hours=max(1,min(168,(int)settings()['session_hours']));
 if(time()-($_SESSION['last']??0)>$hours*3600){unset($_SESSION['uid']);fail('انتهت جلسة الدخول.',401);}
 $_SESSION['last']=time();
 if(function_exists('permission_has_team29')&&$u['role']!=='admin')$u['role']=permission_has_team29($u)?'manager':'employee';
 $actor=supervisor_project31($u);return $authenticationOnly?$actor:identity_project32($actor);
}
function admin_only(array $u): void { if($u['role']!=='admin')fail('هذه الشاشة للإدارة فقط.',403); }
function can_manage(array $u,int $target): bool {
 if($u['role']==='admin')return true;
 if($u['role']!=='manager'||$target===(int)$u['id']||!permission_has($u,$GLOBALS['EP_SCREEN']??'dashboard','view'))return false;
 return in_array($target,permission_scope29($u,$GLOBALS['EP_SCREEN']??'dashboard',$GLOBALS['EP_OPERATION29']??'view'),true);
}
function can_view(array $u,int $target): bool {if(query("SELECT id FROM ep_users WHERE id=? AND role='admin'",[$target])->fetch())return false;return $target===(int)$u['id']||can_manage($u,$target);}
function employee_view_target(array $u,?int $requested=null):int {
 if($requested===null&&$u['role']==='admin')fail('اختر موظفًا لعرض بياناته.',422);
 $target=$requested??(int)$u['id'];
 employee_required($target);
 if(!can_view($u,$target))fail('خارج الصلاحيات.',403);
 return $target;
}
function public_user(array $u): array {foreach(['active','show_sick']as $flag)if(isset($u[$flag]))$u[$flag]=(bool)(int)$u[$flag];$u['employee_code']=employee_key($u);$u['service']=service_state($u,local_time()->format('Y-m-d'));unset($u['password_hash']);$u['profile']=decode($u['profile_json']);foreach(['department','job_title','staff_type']as $job134)if(array_key_exists($job134,$u['service']))$u[$job134]=$u['service'][$job134];$residence134=employee_residence134((int)$u['id'],local_time()->format('Y-m-d'));if($residence134){$u['profile']['residence_company_code']=$residence134['company_code'];$u['profile']['residence_file_id']=$residence134['file_id'];}unset($u['profile_json']);return $u;}
function scope_ids(array $u): array {
 if($u['role']==='admin')return array_map('intval',query("SELECT id FROM ep_users WHERE role<>'admin'")->fetchAll(PDO::FETCH_COLUMN));
 $ids=($_SESSION['access_mode29']??'personal')==='personal'?[(int)$u['id']]:[];
 if($u['role']==='manager'&&permission_has($u,$GLOBALS['EP_SCREEN']??'dashboard','view'))foreach(permission_scope29($u,$GLOBALS['EP_SCREEN']??'dashboard',$GLOBALS['EP_OPERATION29']??'view') as $id)$ids[]=(int)$id;
 return array_values(array_unique($ids));
}
function policy_for(int $uid,string $date): array {
 $rows=$GLOBALS['REPORT_POLICIES32'][$uid]??query('SELECT * FROM ep_policies WHERE (user_id=? OR user_id IS NULL) AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC,effective_from DESC,id DESC',[$uid,$date,$date])->fetchAll();
 if(function_exists('schedule_policy38'))$r=schedule_policy38($uid,$date,$rows);else{$r=false;foreach($rows as $candidate)if($candidate['effective_from']<=$date&&(!$candidate['effective_to']||$candidate['effective_to']>=$date)){$r=$candidate;break;}}
 $values=array_replace(default_policy(),$r?decode($r['value_json']):[]);if(function_exists('punch_values_at393'))$values=punch_values_at393($values,$date);$day=(int)(new DateTimeImmutable($date))->format('w');if(isset($values['weekly'][$day])){$w=$values['weekly'][$day];$values['start']=$w['start'];$values['end']=$w['end'];$values['weekdays']=!empty($w['rest'])?[]:[$day];}return ['id'=>$r['id']??null,'values'=>$values];
}
function distance_m(float $a,float $b,float $c,float $d): float {
 $x=sin(deg2rad($c-$a)/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin(deg2rad($d-$b)/2)**2;
 return 6371000*2*atan2(sqrt($x),sqrt(max(0,1-$x)));
}
function validate_location(array $b,array $policy): array {
 foreach(['latitude','longitude','accuracy','location_at'] as $k)if(!isset($b[$k])||!is_numeric($b[$k]))fail('انتظر الحصول على موقع حديث.');
 $a=(float)$b['latitude'];$o=(float)$b['longitude'];$acc=(float)$b['accuracy'];
 if(!is_finite($a)||abs($a)>90||!is_finite($o)||abs($o)>180||$acc<=0||$acc>(float)$policy['max_accuracy'])fail('دقة الموقع غير كافية أو الإحداثيات غير صالحة.');
 $age=time()-(int)$b['location_at'];
 if($age < -30 || $age > (int)$policy['location_max_age'])fail('الموقع قديم؛ انتظر تحديث الموقع.');
 return [$a,$o,$acc];
}
function upload_file(array $file,int $uid,bool $image_only=false,bool $archive=false): int {
 if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)fail('تعذر رفع المرفق.');
 if(($file['size']??0)>8*1024*1024)fail('المرفق أكبر من 8 ميجابايت.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
 $allowed=$image_only?['image/jpeg','image/png','image/webp']:['image/jpeg','image/png','image/webp','application/pdf'];
 if($archive)$allowed=[...$allowed,'text/plain','application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];if(!in_array($mime,$allowed,true))fail('نوع المرفق غير مسموح.');
 if(str_starts_with($mime,'image/') && !getimagesize($file['tmp_name']))fail('الصورة غير صالحة.');
 $name=bin2hex(random_bytes(24)).'.bin';
 $path143=EP_ROOT.'/private/files/'.$name;if(!move_uploaded_file($file['tmp_name'],$path143))fail('تعذر حفظ المرفق؛ راجع صلاحية الكتابة.');
 $GLOBALS['EDITOR_FILES143'][]=$path143;
 try{query('INSERT INTO ep_files(user_id,disk_name,original_name,mime,size_bytes,sha256,created_at) VALUES(?,?,?,?,?,?,?)',
 [$uid,$name,mb_substr(basename($file['name']),0,180),$mime,$file['size'],hash_file('sha256',$path143),utc_now()]);}catch(Throwable $e){if(is_file($path143))@unlink($path143);throw $e;}
 return (int)db()->lastInsertId();
}
function editor_files_release143(): void {
 foreach($GLOBALS['EDITOR_FILES143']??[] as $path143){if(!is_file($path143))continue;$keep143=true;try{$keep143=(bool)query('SELECT id FROM ep_files WHERE disk_name=?',[basename((string)$path143)])->fetch();}catch(Throwable $ignored143){}if(!$keep143&&is_file($path143))@unlink($path143);}
 unset($GLOBALS['EDITOR_FILES143']);
}
function notice(int $uid,string $title,string $body,bool $ack=false,array $meta=[]): void {
 query('INSERT INTO ep_notifications(user_id,title,body,require_ack,created_at) VALUES(?,?,?,?,?)',[$uid,$title,$body,$ack?1:0,utc_now()]);
 if($meta){$id=(int)db()->lastInsertId();$now=utc_now();query("INSERT INTO ep_records(module,user_id,title,status,body_json,file_id,reviewed_by,created_at,updated_at) VALUES('notification_meta',?,?,'sent',?,?,?,?,?)",[$uid,'notice:'.$id,jencode($meta),$meta['file_id']??null,$meta['sender']??null,$now,$now]);}
}
function event_state(array $events): string {
 $s='none';
 foreach($events as $e){ $s=match($e['event_type']){'attendance'=>'working','permission_exit'=>'away','permission_return'=>'working','checkout'=>'closed',default=>$s};}
 return $s;
}
function valid_event_transition(string $state,string $type): bool {
 return in_array($type,match($state){'none'=>['attendance','checkout'],'working'=>['permission_exit','checkout'],'away'=>['permission_return'],default=>[]},true);
}
function period_start(string $date,string $mode): string {return substr($date,0,$mode==='year'?4:7).($mode==='year'?'-01-01':'-01');}

require_once __DIR__.'/permissions.php';
require_once __DIR__.'/permission-groups.php';

function public_copyright(string $language,string $company,?int $year=null):string{
 $rights=['ar'=>'جميع الحقوق محفوظة.','en'=>'All rights reserved.','fil'=>'Lahat ng karapatan ay nakalaan.','hi'=>'सर्वाधिकार सुरक्षित.','ur'=>'تمام حقوق محفوظ ہیں۔'];
 return '© 2017–'.($year??(int)date('Y')).' '.$company.'. '.($rights[$language]??$rights['en']);
}

require_once __DIR__.'/workforce.php';
require_once __DIR__.'/allowances.php';
require_once __DIR__.'/commissions.php';

require_once __DIR__.'/leave-engine.php';

require_once __DIR__.'/system-control.php';
require_once __DIR__.'/data-language.php';

require_once __DIR__.'/record-review.php';

require_once __DIR__.'/identity.php';
require_once __DIR__.'/personal.php';

require_once __DIR__."/payroll-settlement.php";
require_once __DIR__."/company-sales.php";

require_once __DIR__.'/forget-policy.php';

require_once __DIR__.'/payroll-grid.php';

require_once __DIR__.'/schedule.php';

require_once __DIR__.'/punch.php';

require_once __DIR__.'/preparation.php';

require_once __DIR__.'/finance-daily.php';

require_once __DIR__.'/contract-terms.php';
