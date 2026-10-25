<?php
declare(strict_types=1);
/* Encrypted, authenticated, streaming application snapshots. No server-level BACKUP privilege required. */
function backup_config():array {
 $base=['enabled'=>false,'frequency'=>'daily','week_day'=>0,'last_scheduled'=>null,'at'=>'03:00','keep'=>7,'folder'=>'backups','max_mb'=>256,'last_success'=>null,'last_worker'=>null];$p=EP_ROOT.'/private/backup-config.json';return is_file($p)?array_replace($base,decode(file_get_contents($p))):$base;
}
function backup_private_write(string $path,array $data):void {$tmp=$path.'.'.bin2hex(random_bytes(8)).'.tmp';if(file_put_contents($tmp,jencode($data),LOCK_EX)===false||!rename($tmp,$path)){@unlink($tmp);fail('تعذر حفظ إعدادات النسخ.');}@chmod($path,0600);}
function backup_dir():string {$c=backup_config();if(!preg_match('/^[a-zA-Z0-9_-]{1,40}$/',$c['folder']))fail('مجلد النسخ غير صالح.');$dir=EP_ROOT.'/private/'.$c['folder'];if(!is_dir($dir)&&!mkdir($dir,0700,true))fail('تعذر إنشاء مجلد النسخ.');return $dir;}
function backup_key_data():array {$p=EP_ROOT.'/private/backup-key.json';if(!is_file($p))fail('حدد كلمة حماية النسخ أولًا.');$data=decode(file_get_contents($p));if(strlen(base64_decode($data['key']??'',true)?:'')!==32||strlen(base64_decode($data['salt']??'',true)?:'')!==16)fail('مفتاح النسخ غير مكتمل.');return $data;}
function backup_key():string {return base64_decode(backup_key_data()['key'],true);}
function backup_password_key(string $path,string $password):string {$h=fopen($path,'rb');if(!$h||fread($h,7)!=="EPBK23\n")fail('نوع ملف النسخة غير صالح.');$salt=fread($h,16);fclose($h);if(strlen($salt)!==16)fail('النسخة غير مكتملة.');return hash_pbkdf2('sha256',$password,$salt,600000,32,true);}
function backup_schema():array {
 preg_match_all('/CREATE TABLE \[dbo\]\.\[(ep_\w+)\] \((.*?)\n\);/s',file_get_contents(__DIR__.'/schema.sql'),$matches,PREG_SET_ORDER);$out=[];foreach($matches as $m){$columns=[];$identity=false;foreach(explode("\n",$m[2])as $line)if(preg_match('/^\s*(\w+)\s+(?:BIGINT|INT|TINYINT|NVARCHAR|NCHAR|DECIMAL|DATETIME2|DATE|CHAR|VARCHAR)\b/',$line,$col)){$columns[]=$col[1];if(str_contains($line,'IDENTITY'))$identity=true;}$out[$m[1]]=['columns'=>$columns,'identity'=>$identity];}if(count($out)!==26)fail('تعذر التحقق من مخطط النسخ.');return $out;
}
function backup_pack($handle,array $record,string $key):void {
 $raw=jencode($record);if(strlen($raw)>16777216)fail('سجل أكبر من حد النسخ.');$plain=gzdeflate($raw,6);$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'EPBK23',16);if($cipher===false)fail('تعذر تشفير النسخة.');$block=$iv.$tag.$cipher;$bytes=pack('N',strlen($block)).$block;if(fwrite($handle,$bytes)!==strlen($bytes))fail('تعذر كتابة النسخة.');
}
function backup_unpack($handle,string $key):?array {
 $size=fread($handle,4);if($size===''&&feof($handle))return null;if(strlen($size)!==4)fail('النسخة غير مكتملة.');$n=unpack('N',$size)[1];if($n<29||$n>16777216)fail('حجم مقطع النسخة غير صالح.');$bytes='';while(strlen($bytes)<$n&&!feof($handle))$bytes.=fread($handle,$n-strlen($bytes));if(strlen($bytes)!==$n)fail('النسخة غير مكتملة.');$plain=openssl_decrypt(substr($bytes,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16),'EPBK23');if($plain===false)fail('كلمة حماية النسخة غير صحيحة أو الملف تالف.');$raw=@gzinflate($plain,16777217);if($raw===false||strlen($raw)>16777216)fail('مقطع النسخة تالف.');return decode($raw);
}
function backup_budget(float $start,int $bytes=0):void {$budget109=backup_runtime109();if(microtime(true)>$budget109['deadline']||$bytes>$budget109['max_bytes'])fail('تجاوزت العملية حد الوقت أو الحجم؛ لم تعتمد نسخة جزئية.');}
function backup_exclusive():void {
 if(!empty($GLOBALS['EP_OPERATION_EXCLUSIVE']))return;operation_lock();$h=$GLOBALS['EP_OPERATION_LOCK'];if($h){flock($h,LOCK_UN);if(!flock($h,LOCK_EX|LOCK_NB)){if(!flock($h,LOCK_SH|LOCK_NB))fail('تجري عملية استرجاع؛ حاول بعد قليل.',503);fail('الموقع مشغول؛ حاول النسخ أو الاسترجاع بعد قليل.',409);}$GLOBALS['EP_OPERATION_EXCLUSIVE']=true;}
}
function backup_list():array {$out=[];foreach(glob(backup_dir().'/*.meta.json')?:[]as $p){try{$m=decode(file_get_contents($p));if(preg_match('/^[a-f0-9]{32}$/',$m['id']??'')&&is_file(backup_dir().'/'.$m['id'].'.epbackup'))$out[]=$m;}catch(Throwable $e){}}usort($out,fn($a,$b)=>strcmp($b['created_at'],$a['created_at']));return $out;}
function backup_run(?array $actor=null,string $reason='manual'):array {
 backup_runtime109();
 if(!extension_loaded('openssl'))fail('امتداد OpenSSL مطلوب للنسخ.');backup_exclusive();$key=backup_key();$schema=backup_schema();if(!site_v24_ready())fail('طبّق تحديث القاعدة قبل النسخ.');$start=microtime(true);$id=bin2hex(random_bytes(16));$dir=backup_dir();$temp=$dir.'/'.$id.'.tmp';$target=$dir.'/'.$id.'.epbackup';$h=fopen($temp,'xb');if(!$h)fail('تعذر إنشاء النسخة.');$counts=[];$files=0;$bytes=0;
 try {$salt=base64_decode(backup_key_data()['salt'],true);if(strlen($salt)!==16)fail('مفتاح النسخ غير مكتمل.');fwrite($h,"EPBK23\n".$salt);backup_pack($h,['type'=>'header','version'=>23,'salt'=>base64_encode($salt),'schema'=>$schema,'created_at'=>utc_now(),'reason'=>$reason],$key);query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');db()->beginTransaction();
 foreach($schema as $table=>$spec){$counts[$table]=0;$s=db()->prepare('SELECT * FROM [dbo].['.$table.'] WITH (HOLDLOCK)');$s->execute();while($row=$s->fetch()){if(array_keys($row)!==$spec['columns'])fail('مخطط القاعدة لا يطابق نسخة الموقع.');backup_pack($h,['type'=>'row','table'=>$table,'row'=>$row],$key);$counts[$table]++;$bytes+=strlen(jencode($row));backup_budget($start,max(ftell($h),$bytes));}$s->closeCursor();}
 $s=query('SELECT disk_name FROM ep_files ORDER BY id');while($row=$s->fetch()){$name=$row['disk_name'];if(!preg_match('/^[A-Za-z0-9_.-]+$/',$name)||str_contains($name,'..'))fail('اسم مرفق غير صالح.');$path=EP_ROOT.'/private/files/'.$name;$input=@fopen($path,'rb');if(!$input)fail('مرفق مسجل مفقود؛ تعذر إنشاء نسخة كاملة.');$hash=hash_init('sha256');backup_pack($h,['type'=>'file_start','name'=>$name],$key);while(!feof($input)){$chunk=fread($input,262144);if($chunk===false)fail('تعذر قراءة المرفق.');if($chunk==='')break;hash_update($hash,$chunk);$bytes+=strlen($chunk);backup_pack($h,['type'=>'file_chunk','bytes'=>base64_encode($chunk)],$key);backup_budget($start,max(ftell($h),$bytes));}fclose($input);backup_pack($h,['type'=>'file_end','hash'=>hash_final($hash)],$key);$files++;}$s->closeCursor();
 backup_pack($h,['type'=>'complete','counts'=>$counts,'files'=>$files],$key);db()->commit();fclose($h);$h=null;if(!rename($temp,$target))fail('تعذر اعتماد ملف النسخة.');@chmod($target,0600);$meta=['id'=>$id,'created_at'=>utc_now(),'reason'=>$reason,'by'=>$actor['id']??null,'tables'=>count($counts),'rows'=>array_sum($counts),'files'=>$files,'bytes'=>filesize($target),'sha256'=>hash_file('sha256',$target)];backup_private_write($dir.'/'.$id.'.meta.json',$meta);$c=backup_config();$c['last_success']=$meta['created_at'];if($reason==='scheduled')$c['last_scheduled']=$meta['created_at'];backup_private_write(EP_ROOT.'/private/backup-config.json',$c);
 // Retain the configured number of normal backups; safety copies before restore are retained separately.
 $normal=array_values(array_filter(backup_list(),fn($r)=>!in_array($r['reason'],['before_restore','before_data_correction_v25'],true)));foreach(array_slice($normal,(int)$c['keep'])as $old){@unlink($dir.'/'.$old['id'].'.epbackup');@unlink($dir.'/'.$old['id'].'.meta.json');}backup_log($reason,'success',['id'=>$id]);return $meta;
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();if(is_resource($h))fclose($h);@unlink($temp);backup_log($reason,'failed');throw $e;}finally{query('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');}
}
function backup_stage(string $path,string $key,?callable $observer107=null):array {
 backup_runtime109();
 $start=microtime(true);$size=filesize($path);backup_budget($start,$size);$h=fopen($path,'rb');if(!$h||fread($h,7)!=="EPBK23\n")fail('نوع ملف النسخة غير صالح.');$headerSalt=fread($h,16);if(strlen($headerSalt)!==16)fail('النسخة غير مكتملة.');$dir=EP_ROOT.'/private/restore-'.bin2hex(random_bytes(12));if(!mkdir($dir,0700))fail('تعذر تجهيز الاسترجاع.');$rows=fopen($dir.'/rows.ndjson','xb');$file=null;$fileName='';$hash=null;$fileMap=[];$counts=[];$complete=false;$expanded=0;$schema=backup_schema();$first=true;$end=null;
 try {while(($r=backup_unpack($h,$key))!==null){backup_progress107($observer107,'validate',ftell($h),$size);backup_budget($start,$size);if($complete)fail('بيانات زائدة بعد نهاية النسخة.');$type=$r['type']??'';if($first){if($type!=='header'||($r['version']??0)!==23||($r['schema']??[])!==$schema||!hash_equals(base64_encode($headerSalt),(string)($r['salt']??'')))fail('النسخة لا تطابق إصدار الموقع الحالي.');$first=false;continue;}
 if($type==='row'){$table=$r['table']??'';if(!isset($schema[$table])||array_keys($r['row']??[])!==$schema[$table]['columns']||$file)fail('سجل استرجاع غير صالح.');fwrite($rows,jencode($r)."\n");$counts[$table]=($counts[$table]??0)+1;$expanded+=strlen(jencode($r['row']));backup_budget($start,$expanded);}
 elseif($type==='file_start'){if($file||!preg_match('/^[A-Za-z0-9_.-]+$/',$r['name']??'')||str_contains($r['name'],'..')||isset($fileMap[$r['name']]))fail('اسم مرفق النسخة غير صالح.');$fileName=$r['name'];$fileMap[$fileName]='restore_'.bin2hex(random_bytes(16));$file=fopen($dir.'/'.$fileMap[$fileName],'xb');$hash=hash_init('sha256');}
 elseif($type==='file_chunk'){if(!$file)fail('مقطع مرفق غير صالح.');$bytes=base64_decode($r['bytes']??'',true);if($bytes===false||strlen($bytes)>262144)fail('مقطع مرفق غير صالح.');$expanded+=strlen($bytes);backup_budget($start,$expanded);hash_update($hash,$bytes);if(fwrite($file,$bytes)!==strlen($bytes))fail('تعذر تجهيز المرفق.');}
 elseif($type==='file_end'){if(!$file||!hash_equals(hash_final($hash),(string)($r['hash']??'')))fail('فشل التحقق من المرفق.');fclose($file);$file=null;}
 elseif($type==='complete'){if($file)fail('مرفق غير مكتمل.');foreach($schema as $t=>$spec)if(($r['counts'][$t]??-1)!==($counts[$t]??0))fail('عدد سجلات النسخة غير متطابق.');if(($r['files']??-1)!==count($fileMap))fail('عدد مرفقات النسخة غير متطابق.');$complete=true;$end=$r;}
 else fail('محتوى نسخة غير صالح.');}
 if(!$complete||$first)fail('النسخة غير مكتملة.');fclose($rows);fclose($h);return ['dir'=>$dir,'files'=>$fileMap,'counts'=>$counts,'summary'=>['tables'=>26,'rows'=>array_sum($counts),'files'=>count($fileMap)]];
 }catch(Throwable $e){if(is_resource($file))fclose($file);if(is_resource($rows))fclose($rows);if(is_resource($h))fclose($h);backup_stage_clean($dir);throw $e;}
}
function backup_stage_clean(string $dir):void {if(!str_starts_with($dir,EP_ROOT.'/private/restore-'))return;foreach(glob($dir.'/*')?:[]as $p)if(is_file($p))@unlink($p);@rmdir($dir);}
function backup_insert_sql40(string $table,array $spec):string {
 if(!preg_match('/^ep_[a-z0-9_]+$/D',$table))throw new LogicException('Invalid restore table');
 foreach($spec['columns']as $column)if(!preg_match('/^[a-z0-9_]+$/D',$column))throw new LogicException('Invalid restore column');
 $columns=array_map(fn($column)=>'['.$column.']',$spec['columns']);
 $insert='INSERT INTO [dbo].['.$table.'] ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).');';
 // SET and INSERT must execute in the same batch, including prepared execution.
 return 'SET NOCOUNT ON; '.($spec['identity']?'SET IDENTITY_INSERT [dbo].['.$table.'] ON; ':'').$insert.($spec['identity']?' SET IDENTITY_INSERT [dbo].['.$table.'] OFF;':'');
}
function backup_restore_preflight40():void {
 // Test this driver's explicit-identity insert on a temporary table before any DELETE.
 $sql='SET NOCOUNT ON; CREATE TABLE #ep_restore_probe40 (id INT IDENTITY(1,1) NOT NULL PRIMARY KEY, marker NVARCHAR(16) NOT NULL); SET IDENTITY_INSERT #ep_restore_probe40 ON; INSERT INTO #ep_restore_probe40(id,marker) VALUES (?,?),(?,?); SET IDENTITY_INSERT #ep_restore_probe40 OFF; DECLARE @restored_id40 INT, @restored_marker40 NVARCHAR(16); DECLARE @restored_count109 INT; SELECT @restored_count109=COUNT(*) FROM #ep_restore_probe40; SELECT @restored_id40=id,@restored_marker40=marker FROM #ep_restore_probe40 WHERE id=7; DROP TABLE #ep_restore_probe40; SELECT @restored_id40 AS id,@restored_marker40 AS marker,@restored_count109 AS rows_count;';
 $s=db()->prepare($sql);$s->execute([7,'restore40',8,'restore109']);
 while($s->columnCount()===0&&$s->nextRowset()){}
 $row=$s->fetch();$s->closeCursor();
 if(!$row||(int)$row['id']!==7||$row['marker']!=='restore40'||(int)($row['rows_count']??0)!==2)fail('فشل اختبار إدخال معرّفات النسخة؛ لم يبدأ استبدال البيانات.',409);
}
function backup_restore(array $actor,string $path,string $key,?callable $observer107=null):array {
 backup_runtime109();
 backup_progress107($observer107,'validate');$stage=backup_stage($path,$key,$observer107);$moved=[];$committed=false;$schema=backup_schema();$handle=null;$statements=[];
 $phase40='validate';$table40='';$row40=0;$safety=null;
 try {
  $handle=fopen($stage['dir'].'/rows.ndjson','rb');$admins=0;$refs=[];
  while(($line=fgets($handle))!==false){$r=decode($line);if($r['table']==='ep_users'&&$r['row']['role']==='admin'&&(int)$r['row']['active'])$admins++;if($r['table']==='ep_files')$refs[]=$r['row']['disk_name'];}
  fclose($handle);$handle=null;
  if(!$admins||count($refs)!==count($stage['files'])||array_diff($refs,array_keys($stage['files'])))fail('النسخة لا تحتوي حساب مشرف نشط أو مرفقاتها غير متطابقة.');
  $phase40='preflight';backup_progress107($observer107,'preflight');backup_exclusive();backup_restore_preflight40();
  $phase40='safety_backup';backup_progress107($observer107,'safety_backup');$safety=backup_run($actor,'before_restore');$start=microtime(true);
  db()->beginTransaction();$phase40='delete';
  $cleared107=0;foreach(array_reverse(array_keys($schema))as $table40){query('DELETE FROM [dbo].['.$table40.']');backup_progress107($observer107,'delete',++$cleared107,count($schema));}
  $handle=fopen($stage['dir'].'/rows.ndjson','rb');$phase40='insert';$counts40=[];$inserted107=0;backup_progress107($observer107,'insert',0,(int)$stage['summary']['rows']);
  $batch109=[];$batchTable109='';$batchBytes109=0;
  $flush109=function()use(&$batch109,&$batchTable109,&$batchBytes109,&$statements,&$counts40,&$inserted107,&$table40,&$row40,$schema,$observer107,$stage):void {
   if(!$batch109)return;$table40=$batchTable109;$row40=($counts40[$table40]??0)+1;
   backup_budget(microtime(true));backup_insert_batch109($table40,$schema[$table40],$batch109,$statements);backup_budget(microtime(true));$written109=count($batch109);$counts40[$table40]=($counts40[$table40]??0)+$written109;$row40=$counts40[$table40];$inserted107+=$written109;
   $batch109=[];$batchBytes109=0;backup_progress107($observer107,'insert',$inserted107,(int)$stage['summary']['rows']);
  };
  while(($line=fgets($handle))!==false){
   backup_budget($start);$r=decode($line);$nextTable109=$r['table'];
   if($batch109&&($batchTable109!==$nextTable109||count($batch109)>=backup_batch_limit109($schema[$batchTable109])||$batchBytes109+strlen($line)>1048576))$flush109();
   $batchTable109=$nextTable109;$row=$r['row'];if($nextTable109==='ep_files')$row['disk_name']=$stage['files'][$row['disk_name']];
   $batch109[]=$row;$batchBytes109+=strlen($line);
  }
  $flush109();unset($flush109);
  fclose($handle);$handle=null;$statements=[];
  foreach($schema as $table40=>$spec)if(($counts40[$table40]??0)!==($stage['counts'][$table40]??0))fail('عدد السجلات المكتوبة لا يطابق النسخة؛ تراجع الاسترجاع.',409);
  $phase40='files';$table40='';$row40=0;backup_progress107($observer107,'files',0,count($stage['files']));
  foreach($stage['files']as $new){$target=EP_ROOT.'/private/files/'.$new;if(file_exists($target)||!rename($stage['dir'].'/'.$new,$target))fail('تعذر اعتماد مرفقات الاسترجاع.');$moved[]=$target;backup_progress107($observer107,'files',count($moved),count($stage['files']));}
  $phase40='session_epoch';backup_progress107($observer107,'session_epoch');$epoch=EP_ROOT.'/private/session-epoch.txt';$epochTmp=$epoch.'.tmp';
  if(file_put_contents($epochTmp,bin2hex(random_bytes(16)),LOCK_EX)===false||!rename($epochTmp,$epoch))fail('تعذر تأمين جلسات الاسترجاع.');
  $phase40='commit';backup_progress107($observer107,'commit');db()->commit();$committed=true;backup_progress107($observer107,'complete',1,1);
  backup_log('restore','success',['safety_backup'=>$safety['id'],'by'=>$actor['id'],'restore_version'=>'109','summary'=>$stage['summary']]);
  $_SESSION=[];session_destroy();backup_progress107($observer107,'complete',1,1);return ['ok'=>true,'safety_backup'=>$safety['id'],'summary'=>$stage['summary']];
 }catch(Throwable $e){
  if($committed){backup_progress107($observer107,'complete',1,1);$_SESSION=[];if(session_status()===PHP_SESSION_ACTIVE)session_destroy();return ['ok'=>true,'safety_backup'=>$safety['id'],'summary'=>$stage['summary']];}
  foreach($statements as $statement)try{$statement->closeCursor();}catch(Throwable $ignored){}$statements=[];
  $rolledBack40=false;
  try{if(db()->inTransaction()){db()->rollBack();$rolledBack40=true;}}catch(Throwable $rollbackError40){}
  if(is_resource($handle))fclose($handle);
  if(!$committed&&$phase40!=='commit')foreach($moved as $p)@unlink($p);
  $detail40=['phase'=>$phase40,'table'=>$table40,'row'=>$row40,'restore_version'=>'109','committed'=>$committed,'rolled_back'=>$rolledBack40];
  if($safety)$detail40['safety_backup']=$safety['id'];
  if($e instanceof PDOException){$detail40['sqlstate']=preg_replace('/[^A-Z0-9]/','',(string)($e->errorInfo[0]??$e->getCode()));$detail40['driver']=(int)($e->errorInfo[1]??0);}
  backup_log('restore','failed',$detail40);
  if($e instanceof PDOException){
   // No SQL text, row values, passwords or provider messages enter the diagnostic.
   error_log('EP restore40 '.jencode($detail40));
   $message40=$rolledBack40?'تعذر الاسترجاع؛ تراجعت معاملة البيانات.':'تعذر تأكيد الاسترجاع؛ راجع السجل قبل إعادة المحاولة.';
   fail($message40.' [phase='.$phase40.'; table='.$table40.'; row='.$row40.'; SQLSTATE='.$detail40['sqlstate'].'; driver='.$detail40['driver'].']',409);
  }
  throw $e;
 }finally{backup_stage_clean($stage['dir']);}
}
function backup_ini_bytes46(string $value):int {
 $value=trim($value);if(!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMG]?)$/iD',$value,$m))return PHP_INT_MAX;
 $n=(float)$m[1];if($n<=0)return PHP_INT_MAX;$power=[''=>0,'K'=>1,'M'=>2,'G'=>3][strtoupper($m[2])];return (int)min(PHP_INT_MAX,$n*(1024**$power));
}
function backup_upload_limits46():array {
 $upload=(string)ini_get('upload_max_filesize');$post=(string)ini_get('post_max_size');
 return ['max_file_bytes'=>max(0,min((int)backup_config()['max_mb']*1048576,backup_ini_bytes46($upload),backup_ini_bytes46($post)-1048576)),'upload_max_filesize'=>$upload,'post_max_size'=>$post];
}
function backup_upload_check46(array $upload):void {
 $post=backup_ini_bytes46((string)ini_get('post_max_size'));
 if((int)($_SERVER['CONTENT_LENGTH']??0)>$post){error_log('EP backup preview46 rejected=post_limit');fail('حجم طلب رفع النسخة تجاوز post_max_size في PHP؛ لم يبدأ فحص النسخة ولم تتغير البيانات.',413);}
 $error=(int)($upload['error']??UPLOAD_ERR_NO_FILE);
 if(in_array($error,[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true)){error_log('EP backup preview46 rejected=file_limit');fail('ملف النسخة أكبر من حد الرفع الفعلي في PHP (upload_max_filesize='.(string)ini_get('upload_max_filesize').'). لم تتغير البيانات.',413);}
 if($error!==UPLOAD_ERR_OK){error_log('EP backup preview46 upload_error='.$error);fail(match($error){UPLOAD_ERR_PARTIAL=>'لم يكتمل رفع النسخة؛ أعد رفع الملف بعد استقرار الاتصال.',UPLOAD_ERR_NO_TMP_DIR,UPLOAD_ERR_CANT_WRITE=>'تعذر حفظ الملف المؤقت؛ راجع مساحة الاستضافة وصلاحية مجلد PHP المؤقت.',UPLOAD_ERR_EXTENSION=>'أوقف امتداد PHP رفع الملف؛ راجع إعدادات الاستضافة.',default=>'اختر ملف النسخة لرفعه.'},422);}
 if((int)($upload['size']??0)>backup_upload_limits46()['max_file_bytes'])fail('ملف النسخة يتجاوز حد الرفع الفعلي المسموح؛ لم تتغير البيانات.',413);
}
function backup_action(string $action,array $u,array $b):array {
 admin_only($u);
 if(str_starts_with($action,'backup_upload_'))return backup_upload_action104($action,$u,$b);
 if($action==='backup_info'){$c=backup_config();return ['settings'=>$c,'upload_limits46'=>backup_upload_limits46(),'chunk_limits104'=>backup_chunk_limits104(),'configured'=>is_file(EP_ROOT.'/private/backup-key.json'),'available'=>extension_loaded('openssl'),'location'=>str_replace('\\','/',backup_dir()),'backups'=>backup_list(),'scheduler'=>'cli','events'=>backup_events(),'upgrade_needed'=>!site_v25_ready()];}
 if($action==='backup_settings'){backup_exclusive();$c=backup_config();$folder=(string)($b['folder']??$c['folder']);$keep=(int)($b['keep']??7);$mb=(int)($b['max_mb']??256);$at=(string)($b['at']??'03:00');$frequency=(string)($b['frequency']??'daily');$day=(int)($b['week_day']??$c['week_day']);if(!preg_match('/^[A-Za-z0-9_-]{1,40}$/',$folder)||$keep<1||$keep>100||$mb<16||$mb>2048||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$at)||$day<0||$day>6||!in_array($frequency,['daily','weekly'],true))fail('إعدادات النسخ غير صالحة.');$password=(string)($b['recovery_password']??'');if($password!==''){if(strlen($password)<16)fail('كلمة حماية النسخ 16 حرفًا على الأقل.');$salt=random_bytes(16);$key=hash_pbkdf2('sha256',$password,$salt,600000,32,true);backup_private_write(EP_ROOT.'/private/backup-key.json',['key'=>base64_encode($key),'salt'=>base64_encode($salt)]);}if(!empty($b['enabled'])&&!is_file(EP_ROOT.'/private/backup-key.json'))fail('حدد كلمة حماية النسخ أولًا.');$c=array_replace($c,['enabled'=>!empty($b['enabled']),'folder'=>$folder,'keep'=>$keep,'max_mb'=>$mb,'at'=>$at,'frequency'=>$frequency,'week_day'=>$day]);backup_private_write(EP_ROOT.'/private/backup-config.json',$c);audit($action,'backups');return ['ok'=>true];}
 if($action==='backup_create'){$r=backup_run($u);audit($action,'backup:'.$r['id']);return $r;}
 if($action==='backup_restore_preview'){backup_clean_temporary();$upload=$_FILES['file']??[];backup_upload_check46($upload);if(!is_uploaded_file($upload['tmp_name']))fail('ملف الرفع غير صالح.');if(isset($_SESSION['restore_preview']['path']))@unlink($_SESSION['restore_preview']['path']);unset($_SESSION['restore_preview']);$token=bin2hex(random_bytes(16));$target=backup_dir().'/upload-'.$token.'.epbackup';if(!move_uploaded_file($upload['tmp_name'],$target))fail('تعذر رفع النسخة.');try{return backup_preview_ready104($target,(string)($b['recovery_password']??''),$token);}catch(Throwable $e){@unlink($target);backup_log('preview','failed',['version'=>'46','exception'=>get_class($e)]);error_log('EP backup preview46 failed type='.get_class($e));throw $e;}}
 if($action==='backup_restore_commit'){$p=$_SESSION['restore_preview']??[];if(empty($p)||($p['expires']??0)<time()||!hash_equals($p['token'],(string)($b['token']??''))||!is_file($p['path'])||!hash_equals($p['hash'],hash_file('sha256',$p['path'])))fail('معاينة الاسترجاع انتهت؛ أعد رفع النسخة.');if(($b['confirm']??'')!=='RESTORE'||!password_verify((string)($b['password']??''),$u['password_hash']))fail('أكد الاسترجاع وكلمة مرور المشرف الحالية.');try{$observer107=!empty($b['progress_token107'])?backup_observer107((string)$b['progress_token107']):null;try{return backup_restore($u,$p['path'],base64_decode($p['key'],true),$observer107);}catch(Throwable $error107){backup_progress107($observer107,'failed');throw $error107;}}finally{@unlink($p['path']);unset($_SESSION['restore_preview']);}}
 fail('العملية غير موجودة.',404);
}

function backup_events():array {$p=EP_ROOT.'/private/backup-events.json';return is_file($p)?decode(file_get_contents($p)):[];}
function backup_log(string $operation,string $status,array $detail=[]):void {$log=backup_events();array_unshift($log,['at'=>utc_now(),'operation'=>$operation,'status'=>$status,'detail'=>$detail]);try{backup_private_write(EP_ROOT.'/private/backup-events.json',array_slice($log,0,200));}catch(Throwable $e){security_log($e,'EP backup log');}}

function backup_due(array $c,DateTimeImmutable $now,?DateTimeImmutable $last):bool {
 if(!$c['enabled']||$now->format('H:i')<$c['at'])return false;
 if($c['frequency']==='weekly'&&(int)$now->format('w')!==(int)$c['week_day'])return false;
 return !$last||($c['frequency']==='daily'?$last->format('Y-m-d')!==$now->format('Y-m-d'):$last->format('o-W')!==$now->format('o-W'));
}
function backup_clean_temporary():void {
 foreach(glob(backup_dir().'/upload-*.epbackup')?:[]as $p)if(filemtime($p)<time()-86400)@unlink($p);
 foreach(glob(EP_ROOT.'/private/restore-*',GLOB_ONLYDIR)?:[]as $dir)if(filemtime($dir)<time()-86400)backup_stage_clean($dir);
}

// Upload transport only: these endpoints never write application tables.
function backup_chunk_limits104():array {
 $limit=backup_upload_limits46();return ['chunk_bytes'=>max(0,min(524288,(int)$limit['max_file_bytes'])),'max_backup_bytes'=>(int)backup_config()['max_mb']*1048576];
}
function backup_chunk_append104(string $path,int $offset,string $bytes,int $total):int {
 $length=strlen($bytes);if(!$length||$length>524288||$offset<0||$offset+$length>$total)fail('مقطع رفع النسخة غير صالح.');
 $h=fopen($path,'r+b');if(!$h||!flock($h,LOCK_EX))fail('تعذر فتح ملف الرفع المؤقت.');
 try{$size=(int)fstat($h)['size'];if($size>$total||$offset>$size)fail('ترتيب مقاطع الرفع غير متطابق؛ أعد اختيار النسخة.',409);
  if($size>$offset){$present=min($length,$size-$offset);fseek($h,$offset);$existing=fread($h,$present);if(!hash_equals(substr($bytes,0,$present),$existing))fail('محتوى المقطع المعاد لا يطابق الملف.',409);if($size>=$offset+$length)return $size;if(!ftruncate($h,$offset))fail('تعذر إعادة المقطع غير المكتمل.');}
  if(fseek($h,$offset)!==0)fail('تعذر تحديد موضع الرفع.');$written=0;while($written<$length){$n=fwrite($h,substr($bytes,$written));if(!$n)fail('تعذر حفظ المقطع؛ راجع المساحة المتاحة.');$written+=$n;}
  if(!fflush($h))fail('تعذر اعتماد مقطع الرفع.');if(function_exists('fsync')&&!fsync($h))fail('تعذر تثبيت مقطع الرفع.');return (int)fstat($h)['size'];
 }finally{flock($h,LOCK_UN);fclose($h);}
}
function backup_preview_trace104(string $phase,string $trace,?Throwable $error=null):void {
 $detail=['version'=>'104','phase'=>$phase,'trace'=>$trace];if($error)$detail['exception']=get_class($error);
 try{backup_log('preview',$error?'failed':'started',$detail);}catch(Throwable $ignored){}
 error_log('EP backup104 phase='.$phase.' trace='.$trace.($error?' type='.get_class($error):''));
}
function backup_preview_ready104(string $path,string $password,string $token):array {
 $trace=substr($token,0,12);backup_preview_trace104('decrypt_validate',$trace);
 // Give even a hard PHP termination a safe, operation-specific JSON response.
 $emergency=str_repeat('x',65536);$done=false;
 register_shutdown_function(static function()use(&$emergency,&$done,$trace){if($done)return;$last=error_get_last();if(!$last||!in_array($last['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))return;$emergency='';error_log('EP backup104 phase=fatal trace='.$trace.' type='.$last['type']);if(!headers_sent())http_response_code(500);echo jencode(['error'=>'توقف فحص النسخة داخل PHP. رمز المتابعة: '.$trace]);});
 try{$key=$password===''?backup_key():backup_password_key($path,$password);$stage=backup_stage($path,$key);$summary=$stage['summary'];backup_stage_clean($stage['dir']);$hash=hash_file('sha256',$path);if($hash===false)fail('تعذر حساب بصمة النسخة.');
  $_SESSION['restore_preview']=['token'=>$token,'path'=>$path,'key'=>base64_encode($key),'hash'=>$hash,'expires'=>time()+900,'summary104'=>$summary];
  backup_log('preview','success',['version'=>'104','trace'=>$trace,'summary'=>$summary]);return ['token'=>$token,'summary'=>$summary,'trace104'=>$trace];
 }catch(Throwable $e){backup_preview_trace104('decrypt_validate',$trace,$e);throw $e;}finally{$done=true;}
}
function backup_upload_action104(string $action,array $u,array $b):array {
 admin_only($u);backup_clean_temporary();$limits=backup_chunk_limits104();
 if($action==='backup_upload_begin104'){
  $total=$b['size']??null;$request=(string)($b['request_id']??'');$name=(string)($b['name']??'');
  if(!is_numeric($total)||(float)$total!==(float)(int)$total||(int)$total<24||(int)$total>$limits['max_backup_bytes']||!preg_match('/^[a-f0-9-]{36}$/i',$request)||!preg_match('/\.epbackup$/i',$name)||!$limits['chunk_bytes'])fail('حجم أو نوع ملف النسخة غير صالح.');
  $old=$_SESSION['backup_upload104']??[];if(($old['request_id']??'')===$request&&(int)($old['total']??0)===(int)$total&&($old['expires']??0)>time()&&is_file($old['path']))return ['token'=>$old['token'],'received'=>(int)filesize($old['path']),'chunk_bytes'=>$limits['chunk_bytes']];
  if(isset($old['path'])&&is_file($old['path']))@unlink($old['path']);if(isset($_SESSION['restore_preview']['path']))@unlink($_SESSION['restore_preview']['path']);unset($_SESSION['restore_preview'],$_SESSION['backup_upload104']);
  $token=bin2hex(random_bytes(16));$path=backup_dir().'/upload-'.$token.'.epbackup';$free=disk_free_space(backup_dir());if($free!==false&&$free<(int)$total*2+10485760)fail('المساحة المتاحة لا تكفي لرفع النسخة وفحصها.');$h=fopen($path,'xb');if(!$h)fail('تعذر إنشاء ملف رفع النسخة.');fclose($h);@chmod($path,0600);
  $_SESSION['backup_upload104']=['token'=>$token,'path'=>$path,'total'=>(int)$total,'request_id'=>$request,'expires'=>time()+3600];backup_preview_trace104('upload_begin',substr($token,0,12));return ['token'=>$token,'received'=>0,'chunk_bytes'=>$limits['chunk_bytes']];
 }
 $upload=$_SESSION['backup_upload104']??[];$token=(string)($b['token']??'');if(!$upload||!hash_equals($upload['token'],$token)||($upload['expires']??0)<time()||!is_file($upload['path']))fail('انتهت جلسة رفع النسخة؛ أعد اختيار الملف.',409);
 if($action==='backup_upload_chunk104'){
  if(isset($_SESSION['restore_preview']['token'])&&hash_equals($_SESSION['restore_preview']['token'],$token))fail('اكتمل فحص هذا الملف بالفعل.',409);
  $file=$_FILES['chunk']??[];backup_upload_check46($file);if(!is_uploaded_file($file['tmp_name']))fail('مقطع الرفع غير صالح.');$offset=(string)($b['offset']??'');if(!preg_match('/^\d{1,12}$/',$offset)||(int)$file['size']>$limits['chunk_bytes'])fail('موضع أو حجم المقطع غير صالح.');$bytes=file_get_contents($file['tmp_name']);if($bytes===false)fail('تعذر قراءة مقطع الرفع.');$received=backup_chunk_append104($upload['path'],(int)$offset,$bytes,(int)$upload['total']);$_SESSION['backup_upload104']['expires']=time()+3600;return ['received'=>$received];
 }
 if($action==='backup_upload_finish104'){
  clearstatcache(true,$upload['path']);if(filesize($upload['path'])!==$upload['total'])fail('رفع النسخة غير مكتمل.');$preview=$_SESSION['restore_preview']??[];
  if(($preview['token']??'')===$token&&($preview['expires']??0)>time()&&isset($preview['summary104']))return ['token'=>$token,'summary'=>$preview['summary104'],'trace104'=>substr($token,0,12)];
  return backup_preview_ready104($upload['path'],(string)($b['recovery_password']??''),$token);
 }
 fail('عملية رفع غير صالحة.',404);
}

// Observation never changes the result or boundaries of the restore transaction.
function backup_progress107(?callable $observer,string $phase,int $done=0,int $total=0):void {
 if($observer===null)return;try{$observer(['phase'=>$phase,'done'=>max(0,$done),'total'=>max(0,$total)]);}catch(Throwable $ignored){}
}
function backup_observer107(string $token):callable {
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))fail('رمز متابعة الاسترجاع غير صالح.');
 $dir=EP_ROOT.'/private';foreach(glob($dir.'/restore-progress107-*.json')?:[]as $old)if(filemtime($old)<time()-1200)@unlink($old);
 $path=$dir.'/restore-progress107-'.$token.'.json';$h=@fopen($path,'xb');if(!$h)fail('تعذر تجهيز متابعة الاسترجاع.');fclose($h);@chmod($path,0600);
 // This random capability exposes only phase/counts, and cannot start or change a restore.
 $last=0.0;$phase='';$complete=false;
 $observer=static function(array $event)use($path,&$last,&$phase,&$complete):void {
  $now=microtime(true);if($event['phase']===$phase&&$now-$last<0.15&&($event['total']<=0||$event['done']<$event['total']))return;
  $last=$now;$phase=$event['phase'];$complete=in_array($phase,['complete','failed'],true);$temp=$path.'.tmp';
  $encoded=jencode($event+['expires'=>time()+1200]);if(@file_put_contents($temp,$encoded,LOCK_EX)!==false){@chmod($temp,0600);@rename($temp,$path);}else @unlink($temp);
 };
 $observer(['phase'=>'start','done'=>0,'total'=>0]);
 register_shutdown_function(static function()use($observer,&$complete){if(!$complete){$last=error_get_last();if($last&&in_array($last['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))backup_progress107($observer,'failed');}});
 return $observer;
}

function backup_runtime109():array {
 if(!isset($GLOBALS['EP_BACKUP_BUDGET109'])){
  if(function_exists('set_time_limit'))@set_time_limit(900);
  $GLOBALS['EP_BACKUP_BUDGET109']=['deadline'=>microtime(true)+900,'max_bytes'=>(int)backup_config()['max_mb']*1048576];
 }
 return $GLOBALS['EP_BACKUP_BUDGET109'];
}
function backup_batch_limit109(array $spec):int {
 $columns=count($spec['columns']);if(!$columns||$columns>2000)throw new LogicException('Invalid restore columns');return min(100,intdiv(2000,$columns));
}
function backup_insert_batch_sql109(string $table,array $spec,int $rows):string {
 if($rows<1||$rows>backup_batch_limit109($spec))throw new LogicException('Invalid restore batch');
 // Preserve the proven explicit-identity wrapper, with bound parameters only.
 $sql=backup_insert_sql40($table,$spec);$one='VALUES ('.implode(',',array_fill(0,count($spec['columns']),'?')).')';
 return str_replace($one,'VALUES '.implode(',',array_fill(0,$rows,substr($one,7))),$sql);
}
function backup_insert_batch109(string $table,array $spec,array $rows,array &$statements):void {
 if(!$rows)return;$n=count($rows);$key=$table.':'.$n;$args=[];
 foreach($rows as $row){if(array_keys($row)!==$spec['columns'])fail('أعمدة دفعة الاسترجاع لا تطابق النسخة.');foreach($row as $value)$args[]=$value;}
 if(!isset($statements[$key]))$statements[$key]=db()->prepare(backup_insert_batch_sql109($table,$spec,$n));
 try{$statements[$key]->execute($args);}catch(Throwable $e){try{$statements[$key]->closeCursor();}catch(Throwable $ignored){}throw $e;}$statements[$key]->closeCursor();
 if(count($statements)>64){$old=array_key_first($statements);unset($statements[$old]);}
}
