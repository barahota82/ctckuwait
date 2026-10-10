<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
require __DIR__.'/app/cms.php';
require __DIR__.'/app/customers.php';
session_init();
if(app_installed()){header('Location: index.php');exit;}
$error='';$success=false;$pdo=null;$lock=null;$seedFiles=[];$existingEnv=environment_values();
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!secure_request())fail('افتح المثبت باستخدام HTTPS.');
  if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??'')))fail('أعد تحميل الصفحة.');
  if(!is_file(__DIR__.'/private/install.key'))fail('مفتاح التثبيت غير موجود؛ ارفع الحزمة الأصلية كاملة.');
  if(!is_writable(EP_SITE_ROOT)||!is_writable(__DIR__.'/private')||!is_dir(__DIR__.'/private/files')||!is_writable(__DIR__.'/private/files'))fail('أعط صلاحية كتابة لمستخدم PHP على جذر الموقع وprivate ومجلد files.');
  $expected=trim(file_get_contents(__DIR__.'/private/install.key'));if(strlen($expected)<32)fail('مفتاح الحزمة غير صالح.');
  if(!hash_equals($expected,trim((string)($_POST['install_key']??''))))fail('مفتاح تثبيت الحزمة غير صحيح.');
  if(version_compare(PHP_VERSION,'8.2','<'))fail('يلزم PHP 8.2 أو أحدث.');
  foreach(['pdo_sqlsrv','mbstring','fileinfo','gd','dom']as $extension)if(!extension_loaded($extension))fail('فعّل امتداد PHP: '.$extension);
  if($existingEnv){$config=environment_database($existingEnv);}else{
   $database=trim((string)($_POST['database']??''));
   $host=trim((string)($_POST['host']??''));$port=trim((string)($_POST['port']??''));
   $username=trim((string)($_POST['db_user']??''));$password=(string)($_POST['db_password']??'');
   $config=['driver'=>'sqlsrv','host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password,'trust_server_certificate'=>!empty($_POST['trust_server_certificate'])];
  }
  if($config['username']===''||$config['password']==='')fail('أكمل DB_USER وDB_PASS في .env.php أو أدخل بيانات الاتصال في المثبّت.');
  database_dsn($config);
  $admin=trim((string)($_POST['admin_user']??''));$pass=(string)($_POST['admin_password']??'');
  if(!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/',$admin)||strlen($pass)<12)fail('اسم دخول المسؤول 3 أحرف على الأقل، وكلمة المرور 12 حرفًا على الأقل.');
  $lock=fopen(__DIR__.'/private/install.lock','c+');
  if(!$lock||!flock($lock,LOCK_EX))fail('تعذر قفل عملية التثبيت.');
  if(app_installed())fail('النظام مثبت بالفعل.');
  $pdo=database_connect($config);
  $major=(int)$pdo->query("SELECT CONVERT(int,SERVERPROPERTY('ProductMajorVersion'))")->fetchColumn();
  if($major<16)fail('هذه الحزمة تستهدف SQL Server 2022 أو أحدث.');
  $tables=$pdo->query('SELECT name FROM sys.tables WHERE is_ms_shipped=0')->fetchAll(PDO::FETCH_COLUMN);
  if(count($tables)>0)fail('استخدم قاعدة مستقلة فارغة. لن يكتب المثبت في قاعدة بها جداول.');
  if(!(int)$pdo->query("SELECT HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CREATE TABLE')")->fetchColumn())fail('مستخدم القاعدة يحتاج صلاحية إنشاء الجداول داخل هذه القاعدة.');
  database_self_test($pdo);
  $seedFiles=glob(__DIR__.'/private/files/*.bin')?:[];
  $pdo->beginTransaction();
  foreach(explode(';',file_get_contents(__DIR__.'/app/schema.sql'))as $sql)if(trim($sql)!=='')$pdo->exec($sql);
  $stmt=$pdo->prepare('INSERT INTO ep_users(username,password_hash,name,role,profile_json,created_at) VALUES(?,?,?,?,?,?)');
  $stmt->execute([$admin,password_hash($pass,PASSWORD_DEFAULT),'مسؤول النظام','admin','{}',utc_now()]);
  $stmt=$pdo->prepare('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)');
  foreach(default_settings()as $k=>$v)$stmt->execute([$k,jencode($v)]);
  customer_seed($pdo);cms_seed($pdo);
  $newEnv=array_replace(environment_defaults(),$existingEnv,[
   'APP_INSTALLED'=>true,'DB_DRIVER'=>'sqlsrv','DB_HOST'=>$config['host'],'DB_PORT'=>$config['port'],'DB_NAME'=>$config['database'],'DB_USER'=>$config['username'],'DB_PASS'=>$config['password'],'DB_TRUST_SERVER_CERTIFICATE'=>$config['trust_server_certificate']
  ]);
  if($newEnv['HEALTH_CHECK_KEY']==='')$newEnv['HEALTH_CHECK_KEY']=bin2hex(random_bytes(32));
  $tmp=environment_prepare($newEnv);
  $pdo->commit();
  environment_publish($tmp);@unlink(__DIR__.'/private/install.key');
  $success=true;
 }catch(Throwable $e){
  if($pdo instanceof PDO&&$pdo->inTransaction()){
   $pdo->rollBack();
   foreach(array_diff(glob(__DIR__.'/private/files/*.bin')?:[],$seedFiles)as $path)@unlink($path);
   @unlink(EP_SITE_ROOT.'/.env.tmp.php');
  }
  $error=$e instanceof PDOException?database_install_error($e):($e instanceof RuntimeException?$e->getMessage():'تعذر إكمال التثبيت.');
  if($e instanceof PDOException && isset($config)){
   $error.=' [الإعداد الفعلي TrustServerCertificate=' . (!empty($config['trust_server_certificate'])?'1':'0') . '; Encrypt=1]';
   $native=(int)($e->errorInfo[1]??0);
   if($native===-2146893019)$error.=' [الشهادة: جهة الإصدار غير موثوقة]';
   if($native===-2146893022)$error.=' [الشهادة: اسم الخادم لا يطابق الاسم في الشهادة]';
  }
  security_log($e,'EP install');
 }finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}

}
function h(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تثبيت شؤون الموظفين</title><link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link
      href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap"
      rel="stylesheet"
    /><link rel="stylesheet" href="assets/style.css"></head><body class="auth-page"><main class="install-card">
<div class="brand-mark">◆</div><p class="eyebrow">PEOPLE ADMINISTRATION</p><h1>تجهيز مساحة العمل</h1><p>إعداد لمرة واحدة على SQL Server 2022. أنشئ قاعدة مستقلة فارغة من لوحة الاستضافة.</p>
<?php if($success):?><div class="success">اكتمل التثبيت. المثبت مقفل الآن تلقائيًا.</div><a class="button primary" href="index.php">دخول النظام</a>
<?php else:?>
<?php if($error):?><div class="error"><?=h($error)?></div><?php endif;?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<label>مفتاح تثبيت الحزمة — من دليل INSTALL_AR.txt المرفق خارج ملفات الموقع<input name="install_key" type="password" required></label>
<?php if($existingEnv):?><p>سيستخدم المثبّت بيانات SQL Server المسجلة في .env.php على الخادم، دون عرض كلمة المرور هنا.</p><?php else:?>
<div class="form-grid"><label>خادم قاعدة البيانات<input name="host" placeholder=".\MSSQLSERVER2022" required></label><label>منفذ TCP — اختياري<input name="port" type="number" min="1" max="65535" placeholder="اتركه فارغًا للمثيل المسمى"></label></div>
<label>اسم القاعدة المستقلة<input name="database" required></label><label>مستخدم القاعدة<input name="db_user" required></label><label>كلمة مرور القاعدة<input name="db_password" type="password" required></label>
<label class="check"><input type="checkbox" name="trust_server_certificate" value="1">الثقة في شهادة خادم قاعدة البيانات الذاتية — فعّل فقط إذا أكدت الاستضافة استخدامها</label>
<?php endif;?>
<hr><label>اسم دخول مسؤول النظام<input name="admin_user" required minlength="3"></label><label>كلمة مرور المسؤول<input name="admin_password" type="password" required minlength="12"></label>
<p class="muted">أنشئ قاعدة SQL Server ومستخدمها من لوحة الاستضافة، ثم أدخل البيانات هنا. لا تستورد schema.sql؛ المثبّت ينشئ الجداول والبيانات التأسيسية تلقائيًا. اتصال قاعدة البيانات مشفّر في الحالتين.</p><button class="primary">تثبيت النظام</button></form><?php endif;?>
</main></body></html>
