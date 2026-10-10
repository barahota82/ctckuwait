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
 if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
}
require_once __DIR__.'/database.php';
function db(): PDO {
 static $pdo=null;
 if($pdo)return $pdo;
 $config=require EP_ROOT.'/../config.php';
 return $pdo=database_connect($config);
}
function query(string $sql,array $args=[]): PDOStatement {return database_statement(db(),$sql,$args);}
function audit(string $action,string $subject,array $detail=[]): void {
 query('INSERT INTO ep_audit(user_id,action,subject,detail_json,created_at) VALUES(?,?,?,?,?)',
 [$_SESSION['uid']??null,$action,$subject,jencode($detail),utc_now()]);
}
function settings(): array {
 $s=default_settings();
 foreach(query('SELECT * FROM ep_settings')->fetchAll() as $r) $s[$r['setting_key']]=json_decode($r['value_json'],true);
 foreach(decode(file_get_contents(__DIR__.'/receipt-texts.json'))as $language=>$base)$s['public_ui'][$language]=array_replace($base,$s['public_ui'][$language]??[]);
 $s['employee_languages']=['ar','en'];$s['public_languages']=['ar','en','fil','hi','ur'];
 $s['offers_languages']=array_values(array_intersect($s['offers_languages'], $s['public_languages']))?:['ar'];
 foreach(['admin_default_language','employee_default_language']as $key)if(!in_array($s[$key],['ar','en'],true))$s[$key]='ar';
 if(!in_array($s['offers_default_language'],$s['offers_languages'],true))$s['offers_default_language']=$s['offers_languages'][0];
 foreach(default_settings()['public_ui']as $language=>$base)$s['public_ui'][$language]=array_replace($base,$s['public_ui'][$language]??[]);
 return $s;
}
function default_settings(): array { return [
 'customer_routes'=>[],'public_original_texts'=>[],'offers_languages'=>['ar','en','fil','hi','ur'],'offers_default_language'=>'ar','public_language_approvals'=>[],'ratings_enabled'=>true,'ratings_visibility'=>'admin','public_languages'=>['ar','en','fil','hi','ur'],'employee_languages'=>['ar','en'],'employee_default_language'=>'ar','admin_default_language'=>'ar','translation_enabled'=>false,'translation_context'=>'Professional corporate website and employee administration. Preserve company names, dates, numbers and placeholders.','translation_budget'=>100000,'public_portal_label'=>[],'public_footer'=>[],'public_ui'=>['ar'=>['error_generic'=>'تعذر الإرسال. راجع البيانات وحاول مرة أخرى، أو حاول لاحقًا.','page_unavailable'=>'الصفحة غير متاحة'],'en'=>['error_generic'=>'We could not submit your request. Please check your details and try again, or try later.','page_unavailable'=>'Page unavailable']],'public_phone'=>'','public_email'=>'','public_hours'=>[],'public_address'=>[],'public_slogan'=>[],'public_site_title'=>[],'public_default_language'=>'ar','public_contact_enabled'=>true,
 'company_name'=>'شؤون الموظفين','company_name_en'=>'People Administration','site_url'=>'','logo_id'=>null,
 'timezone'=>'Asia/Kuwait','currency'=>'KWD','session_hours'=>8,
 'locations'=>[], 'tracking_enabled'=>false,'tracking_interval'=>300,'tracking_stale'=>1200,
 'retention_days'=>365, 'sick_year'=>'calendar','sick_full_days'=>null,'sick_half_days'=>null,
 'permissions'=>[], 'history_approved'=>false,'history_approved_at'=>null,'history_approved_by'=>null,'operation_start'=>null,'attendance_enabled'=>false,'reports_enabled'=>false,
 'payroll_enabled'=>false,'salary_divisor'=>30,'overtime_enabled'=>false,
 'overtime_multiplier'=>1.0,'work_hours'=>8,'annual_leave_days'=>null,'leave_year'=>'calendar','leave_accrual_mode'=>'annual','leave_accrual_divisor'=>365,'leave_exclude_approved_days'=>false
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
function current_user(): array {
 $uid=$_SESSION['uid']??0;
 if(!$uid)fail('سجل الدخول أولًا.',401);
 $u=query('SELECT * FROM ep_users WHERE id=? AND active=1',[$uid])->fetch();
 if($u&&!hash_equals(hash('sha256',$u['password_hash']),(string)($_SESSION['auth_fingerprint']??''))){unset($_SESSION['uid']);fail('تغيرت بيانات الدخول؛ سجل الدخول مجددًا.',401);}
 if(!$u) {unset($_SESSION['uid']);fail('الحساب غير متاح.',401);}
 $hours=max(1,min(168,(int)settings()['session_hours']));
 if(time()-($_SESSION['last']??0)>$hours*3600){unset($_SESSION['uid']);fail('انتهت جلسة الدخول.',401);}
 $_SESSION['last']=time();
 return $u;
}
function admin_only(array $u): void { if($u['role']!=='admin')fail('هذه الشاشة للإدارة فقط.',403); }
function can_manage(array $u,int $target): bool {
 if($u['role']==='admin')return true;
 if($u['role']!=='manager'||$target===(int)$u['id']||!permission_has($u,$GLOBALS['EP_SCREEN']??'dashboard','view'))return false;
 return (bool)query('SELECT id FROM ep_users WHERE id=? AND manager_id=?',[$target,$u['id']])->fetch();
}
function can_view(array $u,int $target): bool {return $target===(int)$u['id']||can_manage($u,$target);}
function public_user(array $u): array {unset($u['password_hash']);$u['profile']=decode($u['profile_json']);unset($u['profile_json']);return $u;}
function scope_ids(array $u): array {
 if($u['role']==='admin')return array_map('intval',query('SELECT id FROM ep_users')->fetchAll(PDO::FETCH_COLUMN));
 $ids=[(int)$u['id']];
 if($u['role']==='manager'&&permission_has($u,$GLOBALS['EP_SCREEN']??'dashboard','view'))foreach(query('SELECT id FROM ep_users WHERE manager_id=?',[$u['id']])->fetchAll(PDO::FETCH_COLUMN)as $id)$ids[]=(int)$id;
 return $ids;
}
function policy_for(int $uid,string $date): array {
 $r=query('SELECT TOP (1) * FROM ep_policies WHERE (user_id=? OR user_id IS NULL) AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC,effective_from DESC,id DESC',[$uid,$date,$date])->fetch();
 return ['id'=>$r['id']??null,'values'=>array_replace(default_policy(),$r?decode($r['value_json']):[])];
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
function upload_file(array $file,int $uid,bool $image_only=false): int {
 if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)fail('تعذر رفع المرفق.');
 if(($file['size']??0)>8*1024*1024)fail('المرفق أكبر من 8 ميجابايت.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
 $allowed=$image_only?['image/jpeg','image/png','image/webp']:['image/jpeg','image/png','image/webp','application/pdf'];
 if(!in_array($mime,$allowed,true))fail('المرفق يجب أن يكون صورة أو PDF.');
 if(str_starts_with($mime,'image/') && !getimagesize($file['tmp_name']))fail('الصورة غير صالحة.');
 $name=bin2hex(random_bytes(24)).'.bin';
 if(!move_uploaded_file($file['tmp_name'],EP_ROOT.'/private/files/'.$name))fail('تعذر حفظ المرفق؛ راجع صلاحية الكتابة.');
 query('INSERT INTO ep_files(user_id,disk_name,original_name,mime,size_bytes,sha256,created_at) VALUES(?,?,?,?,?,?,?)',
 [$uid,$name,mb_substr(basename($file['name']),0,180),$mime,$file['size'],hash_file('sha256',EP_ROOT.'/private/files/'.$name),utc_now()]);
 return (int)db()->lastInsertId();
}
function notice(int $uid,string $title,string $body,bool $ack=false): void {
 query('INSERT INTO ep_notifications(user_id,title,body,require_ack,created_at) VALUES(?,?,?,?,?)',[$uid,$title,$body,$ack?1:0,utc_now()]);
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

function public_copyright(string $language,string $company,?int $year=null):string{
 $rights=['ar'=>'جميع الحقوق محفوظة.','en'=>'All rights reserved.','fil'=>'Lahat ng karapatan ay nakalaan.','hi'=>'सर्वाधिकार सुरक्षित.','ur'=>'تمام حقوق محفوظ ہیں۔'];
 return '© 2017–'.($year??(int)date('Y')).' '.$company.'. '.($rights[$language]??$rights['en']);
}
