<?php
declare(strict_types=1);
const EP_SITE_ROOT=__DIR__.'/../..';
function environment_values():array {
 $path=EP_SITE_ROOT.'/.env.php';
 if(!is_file($path))return [];
 $values=(static function(string $file):mixed{return require $file;})($path);
 if(!is_array($values))throw new RuntimeException('ملف إعدادات الخادم غير صالح.');
 return $values;
}
function environment_bool(mixed $value):bool {
 if(in_array($value,[true,1,'1','true'],true))return true;
 if(in_array($value,[false,0,'0','false','',null],true))return false;
 throw new RuntimeException('قيمة تفعيل غير صالحة في إعدادات الخادم.');
}
function app_installed():bool{return environment_bool(environment_values()['APP_INSTALLED']??false);}
function environment_defaults():array {
 return ['APP_INSTALLED'=>false,'DB_DRIVER'=>'sqlsrv','DB_HOST'=>'.\\MSSQLSERVER2022','DB_PORT'=>'','DB_NAME'=>'','DB_USER'=>'','DB_PASS'=>'','DB_TRUST_SERVER_CERTIFICATE'=>false,
  'HEALTH_CHECK_KEY'=>'','MAIL_ENABLED'=>false,'SMTP_HOST'=>'','SMTP_PORT'=>587,'SMTP_SECURE'=>'tls','SMTP_USER'=>'','SMTP_PASS'=>'','MAIL_FROM'=>'','MAIL_FROM_NAME'=>''];
}
function environment_database(array $env):array {
 return ['driver'=>(string)($env['DB_DRIVER']??'sqlsrv'),'host'=>(string)($env['DB_HOST']??''),'port'=>(string)($env['DB_PORT']??''),'database'=>(string)($env['DB_NAME']??''),'username'=>(string)($env['DB_USER']??''),'password'=>(string)($env['DB_PASS']??''),'trust_server_certificate'=>environment_bool($env['DB_TRUST_SERVER_CERTIFICATE']??false)];
}
function app_configuration():array {
 $env=environment_values();
 if(!$env||!app_installed())throw new RuntimeException('النظام يحتاج إكمال التثبيت.');
 return environment_database($env);
}
/** Server-only SMTP settings. No email is sent by loading this configuration. */
function mail_configuration():array {
 $env=environment_values();
 return ['enabled'=>environment_bool($env['MAIL_ENABLED']??false),'host'=>(string)($env['SMTP_HOST']??''),'port'=>(int)($env['SMTP_PORT']??587),'secure'=>(string)($env['SMTP_SECURE']??'tls'),'username'=>(string)($env['SMTP_USER']??''),'password'=>(string)($env['SMTP_PASS']??''),'from'=>(string)($env['MAIL_FROM']??''),'from_name'=>(string)($env['MAIL_FROM_NAME']??'')];
}
function environment_prepare(array $values):string {
 $path=EP_SITE_ROOT.'/.env.tmp.php';
 $data="<?php\ndeclare(strict_types=1);\n// Server-only secrets. Never commit this file.\nreturn ".var_export($values,true).";\n";
 if(file_put_contents($path,$data,LOCK_EX)===false)throw new RuntimeException('تعذر كتابة ملف الإعدادات المؤقت في جذر الموقع.');
 @chmod($path,0600);return $path;
}
function environment_publish(string $temporary):void {
 if($temporary!==EP_SITE_ROOT.'/.env.tmp.php'||!rename($temporary,EP_SITE_ROOT.'/.env.php'))throw new RuntimeException('الجداول جاهزة لكن تعذر تفعيل .env.php؛ لا تعِد التثبيت. راجع صلاحية إعادة تسمية .env.tmp.php إلى .env.php.');
 @chmod(EP_SITE_ROOT.'/.env.php',0600);
}
