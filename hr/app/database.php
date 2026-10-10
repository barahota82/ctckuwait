<?php
declare(strict_types=1);

/** Classify driver errors without returning server text, credentials or SQL. */
function database_install_error(PDOException $error): string {
 $info=$error->errorInfo??[];
 $state=(string)($info[0]??$error->getCode());
 $native=(int)($info[1]??0);
 $message=strtolower((string)($info[2]??$error->getMessage()));
 $suffix=' [SQLSTATE '.preg_replace('/[^A-Z0-9]/','',$state).'; driver '.$native.']';
 if(str_contains($message,'certificate')||str_contains($message,'ssl provider')||str_contains($message,'tls'))
  return 'فشل التحقق من شهادة الاتصال المشفّر. اطلب من الاستضافة شهادة موثوقة أو تأكيد استخدام شهادة ذاتية قبل تفعيل DB_TRUST_SERVER_CERTIFICATE.' .$suffix;
 if($native===18456||$state==='28000')return 'رفض SQL Server تسجيل الدخول. راجع DB_USER وDB_PASS وربط المستخدم بقاعدة الموقع والسماح بمصادقة SQL Server.'.$suffix;
 if($native===4060)return 'تعذر فتح قاعدة البيانات المحددة. راجع DB_NAME وربط المستخدم بهذه القاعدة.'.$suffix;
 if($native===229||$native===262)return 'مستخدم القاعدة لا يملك صلاحيات العملية المطلوبة. امنحه صلاحيات إنشاء وإدارة جداول قاعدة الموقع فقط.'.$suffix;
 if($state==='08001'||$state==='HYT00'||$state==='HYT01')return 'تعذر الوصول إلى مثيل SQL Server أو انتهت مهلة الاتصال. اطلب من الاستضافة عنوان TCP والمنفذ المتاحين لاتصال PHP؛ اسم المثيل وحده قد يحتاج SQL Server Browser وبروتوكول TCP/IP. لا تغيّر كلمة المرور لهذا السبب وحده.'.$suffix;
 return 'فشل SQL Server أثناء التثبيت. أرسل كود التشخيص التالي لتحديد الخطوة المطلوبة؛ لا ترسل كلمات المرور.'.$suffix;
}

/** SQL Server connection configuration. Credentials never form part of the DSN. */
function database_dsn(array $config): string {
 $host=trim((string)($config['host']??''));
 if(!preg_match('/^[A-Za-z0-9_.:\\\\-]{1,200}$/D',$host))throw new RuntimeException('عنوان خادم SQL Server غير صالح. استخدم اسم الخادم أو اسم الخادم\\المثيل.');
 $database=trim((string)($config['database']??''));
 if(!preg_match('/^[A-Za-z0-9_-]{1,128}$/D',$database))throw new RuntimeException('اسم قاعدة البيانات غير صالح.');
 $port=trim((string)($config['port']??''));
 if($port!==''&&(!preg_match('/^[0-9]+$/D',$port)||(int)$port<1||(int)$port>65535))throw new RuntimeException('منفذ SQL Server غير صالح.');
 if(str_contains($host,'\\')&&$port!=='')throw new RuntimeException('للمثيل المسمى اترك المنفذ فارغًا، أو استخدم عنوان الخادم ومنفذ TCP الذي تحدده الاستضافة.');
 return 'sqlsrv:Server='.$host.($port!==''?','.$port:'').';Database='.$database.';Encrypt=1;TrustServerCertificate='.(!empty($config['trust_server_certificate'])?'1':'0').';MultipleActiveResultSets=true;LoginTimeout=15';
}
function database_connect(array $config): PDO {
 if(($config['driver']??'')!=='sqlsrv')throw new RuntimeException('V19 مخصصة لـSQL Server. لا تستخدم ملف اتصال MySQL من V18.');
 $dsn=database_dsn($config);
 if(!extension_loaded('pdo_sqlsrv'))throw new RuntimeException('امتداد pdo_sqlsrv غير مفعّل في إصدار PHP الذي يشغل هذا الموقع.');
 $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,
  PDO::SQLSRV_ATTR_ENCODING=>PDO::SQLSRV_ENCODING_UTF8];
 $pdo=new PDO($dsn,(string)$config['username'],(string)$config['password'],$options);
 $pdo->exec('SET XACT_ABORT ON; SET ANSI_NULLS ON; SET QUOTED_IDENTIFIER ON; SET ANSI_WARNINGS ON; SET DATEFORMAT ymd;');
 return $pdo;
}
/** Buffer SELECT results, so lock-only reads leave no pending MARS transaction. */
function database_statement(PDO $pdo,string $sql,array $args=[]): PDOStatement {
 $options=[];
 if(preg_match('/^\s*SELECT\b/i',$sql))$options=[PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL,PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED];
 $statement=$pdo->prepare($sql,$options);$statement->execute($args);return $statement;
}

/** Runs on the actual hosting engine before installing any permanent table. */
function database_self_test(PDO $pdo): void {
 $text='اختبار Click — हिंदी — اردو — Filipino';
 $json=json_encode(['text'=>$text,'long'=>str_repeat($text,300)],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $date='2026-10-10 00:00:01';
 try{
  $pdo->beginTransaction();
  $pdo->exec('CREATE TABLE #ep_preflight (id BIGINT IDENTITY(1,1) PRIMARY KEY,code NVARCHAR(40) NOT NULL UNIQUE,body NVARCHAR(MAX) NOT NULL,amount DECIMAL(12,3) NOT NULL,received_at DATETIME2(0) NOT NULL)');
  $s=database_statement($pdo,'INSERT INTO #ep_preflight(code,body,amount,received_at) VALUES(?,?,?,?)',['seed',$text,'1.234',$date]);$s->closeCursor();
  if((int)$pdo->lastInsertId()!==1)throw new RuntimeException('لم ينجح اختبار ترقيم السجلات في SQL Server.');
  $merge='MERGE INTO #ep_preflight WITH (HOLDLOCK) AS target USING (VALUES (?, ?, ?, ?)) AS source (code, body, amount, received_at) ON target.code=source.code WHEN MATCHED THEN UPDATE SET body=source.body WHEN NOT MATCHED THEN INSERT (code,body,amount,received_at) VALUES(source.code,source.body,source.amount,source.received_at);';
  foreach([['seed',$json,'1.234',$date],['second',$text,'2.345',$date]]as $args){$s=database_statement($pdo,$merge,$args);$s->closeCursor();}
  $s=database_statement($pdo,'SELECT TOP (1) * FROM #ep_preflight WITH (UPDLOCK, HOLDLOCK) WHERE code=? ORDER BY CASE WHEN code=\'seed\' THEN 1 ELSE 0 END DESC,id',['seed']);$row=$s->fetch();$s->closeCursor();
  if(!$row||$row['body']!==$json||abs((float)$row['amount']-1.234)>0.00001||(new DateTimeImmutable((string)$row['received_at']))->format('Y-m-d H:i:s')!==$date)throw new RuntimeException('لم ينجح اختبار Unicode أو النصوص الطويلة أو دقة الأرقام والتواريخ في SQL Server.');
  $s=$pdo->query('SELECT COUNT(*) FROM #ep_preflight');$count=(int)$s->fetchColumn();$s->closeCursor();
  if($count!==2)throw new RuntimeException('لم ينجح اختبار إدراج وتحديث السجلات في SQL Server.');
  $pdo->rollBack();
  $s=$pdo->query("SELECT OBJECT_ID('tempdb..#ep_preflight')");$exists=$s->fetchColumn();$s->closeCursor();
  if($exists!==null&&$exists!==false)throw new RuntimeException('لم ينجح اختبار التراجع عن المعاملة في SQL Server.');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
