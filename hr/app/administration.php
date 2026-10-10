<?php
declare(strict_types=1);
require_once __DIR__.'/xlsx.php';
function import_contract():array{return decode(file_get_contents(__DIR__.'/import-contract.json'));}
function company_by_code(string $code):?array{return query('SELECT * FROM ep_companies WHERE code=?',[$code])->fetch()?:null;}
function historical_for(int $uid,string $kind,string $from,string $to):array{$code=query('SELECT username FROM ep_users WHERE id=?',[$uid])->fetchColumn();return query('SELECT * FROM ep_history WHERE kind=? AND employee_code=? AND period_key>=? AND period_key<=? ORDER BY period_key',[$kind,$code,$from,$to])->fetchAll();}
function import_date(mixed $v):string{if(is_numeric($v))$v=(new DateTimeImmutable('1899-12-30'))->modify('+'.(int)$v.' days')->format('Y-m-d');return valid_date($v)??'';}
function import_document(string $raw):array{
 $sheets=xlsx_read($raw);$records=[];$errors=[];$seen=[];$companyCodes=array_column(query('SELECT code FROM ep_companies')->fetchAll(),'code');$employeeCodes=array_column(query('SELECT username FROM ep_users')->fetchAll(),'username');
 foreach(import_contract()as $tab){$rows=$sheets[$tab['name']]??[];if(!$rows)continue;$expected=array_column($tab['fields'],'label');$headers=array_values($rows[0]);if($headers!==$expected){$errors[]=$tab['name'].': الأعمدة لا تطابق القالب.';continue;}
  foreach(array_slice($rows,1)as $i=>$row){if(!array_filter($row,fn($v)=>trim((string)$v)!==''))continue;$body=[];foreach($tab['fields']as $col=>$field){$value=trim((string)($row[$col]??''));$body[$field['key']]=$value;if($field['required']&&$value==='')$errors[]=$tab['name'].' سطر '.($i+2).': '.$field['label'].' مطلوب.';}
   $kind=$tab['kind'];$code=$body['code'];
   foreach(['code'=>80,'name'=>150,'name_en'=>180,'bank_account'=>100,'iban'=>100,'civil_id'=>40,'legal_name'=>250,'bank_name'=>150,'labor_file'=>100,'department'=>100,'job_title'=>100]as $field=>$max)if(isset($body[$field])&&mb_strlen((string)$body[$field])>$max)$errors[]=$tab['name'].' سطر '.($i+2).': حقل طويل أكثر من المسموح '.$field;
   if(mb_strlen(jencode($body))>20000)$errors[]='السطر يحتوي نصًا طويلًا جدًا.';if(!preg_match('/^[A-Za-z0-9_.@-]{3,80}$/',$code))$errors[]=$tab['name'].' سطر '.($i+2).': الكود 3–80 حرفًا إنجليزيًا أو رقمًا.';
   foreach($body as $k=>$v){try{if(in_array($k,['date','as_of','from','to','return_date','last_return','hired_on','ended_on'],true)&&$v!=='')$body[$k]=import_date($v);if(in_array($k,['salary','bank_salary','addition','deduction','net','amount','days','cash_days','annual_entitlement','late_minutes','early_minutes','permission_minutes','missing','penalty_minutes','penalty_days','full','half','unpaid'],true)&&$v!==''){if(!is_numeric($v)||!is_finite((float)$v))throw new RuntimeException('قيمة رقمية مطلوبة');$body[$k]=(float)$v;if($k!=='net'&&$body[$k]<0)throw new RuntimeException('القيمة لا تكون سالبة');}}catch(Throwable $e){$errors[]=$tab['name'].' سطر '.($i+2).': '.$k.' غير صالح.';}}
   $period=match($kind){'companies','employees'=>'profile','attendance','leave_settlement'=>$body['date'],'payroll','bank'=>$body['month'],'leave_balance'=>$body['as_of'],'leave_history'=>$body['from'].'_'.$body['to'],'sick_summary'=>$body['year'],default=>''};
   $key=$kind.'|'.$code.'|'.$period;if(isset($seen[$key]))$errors[]='سجل مكرر في الملف: '.$key;$seen[$key]=true;
   if(in_array($kind,['payroll','bank'],true)&&!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$period))$errors[]='شهر غير صالح: '.$key;
   if($kind==='sick_summary'&&(!preg_match('/^\d{4}$/',$period)||substr($body['as_of'],0,4)!==$period))$errors[]='سنة المرضيات وتاريخها غير متوافقين: '.$key;
   if($kind==='attendance'&&!in_array($body['status'],['دوام','غياب','إجازة','مرضي','راحة'],true))$errors[]='حالة اليوم غير صالحة: '.$key;
   if($kind==='sick_summary'&&((float)$body['full']!=(int)$body['full']||(float)$body['half']!=(int)$body['half']||(float)$body['unpaid']!=(int)$body['unpaid']))$errors[]='عداد المرضيات أيام صحيحة: '.$key;
   if($kind==='attendance')foreach(['attendance','checkout']as $t)if($body[$t]!==''&&!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$body[$t]))$errors[]='اكتب الوقت HH:MM: '.$key;
   if($kind==='payroll'&&abs((float)$body['salary']+(float)$body['addition']-(float)$body['deduction']-(float)$body['net'])>.001)$errors[]='الأساسي + الإضافات − الخصومات لا يطابق الصافي: '.$key;
   if($kind==='leave_history'&&$body['from']>$body['to'])$errors[]='نهاية الإجازة قبل بدايتها: '.$key;
   if($kind==='employees'&&$body['staff_type']!==''&&!in_array($body['staff_type'],['office','rep'],true))$errors[]='طبيعة العمل office أو rep: '.$key;
   if($kind==='companies'){$companyCodes[]=$code;if(company_by_code($code))$errors[]='الشركة موجودة بالفعل: '.$code;}
   elseif($kind==='employees'){$employeeCodes[]=$code;if(query('SELECT id FROM ep_users WHERE username=?',[$code])->fetch())$errors[]='الموظف موجود بالفعل: '.$code;}
   else{if(!in_array($code,$employeeCodes,true))$errors[]='كود موظف غير موجود: '.$code;if(query('SELECT id FROM ep_history WHERE kind=? AND employee_code=? AND period_key=?',[$kind,$code,$period])->fetch())$errors[]='سجل مستورد سابقًا: '.$key;}
   foreach(['company_code','residence_company_code']as $field)if(!empty($body[$field])&&!in_array($body[$field],$companyCodes,true))$errors[]='شركة غير موجودة: '.$body[$field];
   $records[]=['kind'=>$kind,'code'=>$code,'period'=>$period,'body'=>$body];
  }
 }
 if(!$records)$errors[]='لا توجد بيانات قابلة للاستيراد.';
 return ['records'=>$records,'errors'=>array_values(array_unique($errors)),'count'=>count($records)];
}
function import_commit(array $doc,int $uid):void{
 if($doc['errors'])fail('أصلح أخطاء الملف قبل الاعتماد.');
 foreach($doc['records']as $r){$b=$r['body'];$kind=$r['kind'];
  if($kind==='companies')query('INSERT INTO ep_companies(code,name,legal_name,bank_name,bank_account,labor_file,created_at) VALUES(?,?,?,?,?,?,?)',[$r['code'],$b['name'],$b['legal_name'],$b['bank_name'],$b['bank_account'],$b['labor_file'],utc_now()]);
  elseif($kind==='employees'){
   $profile=array_intersect_key($b,array_flip(['name_en','company_code','residence_company_code','bank_name','bank_account','iban','civil_id','bank_salary','ended_on']));
   query('INSERT INTO ep_users(username,name,password_hash,role,department,job_title,staff_type,active,hired_on,salary,profile_json,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',[$r['code'],$b['name'],password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'employee',$b['department'],$b['job_title'],$b['staff_type']?:'office',0,$b['hired_on']?:null,(float)($b['salary']?:0),jencode($profile),utc_now()]);
  }else{
   if(!query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE username=?',[$r['code']])->fetch())fail('تغير ملف الموظف؛ راجع الاستيراد.');
   if($kind==='attendance'&&query('SELECT e.id FROM ep_events e JOIN ep_users u ON u.id=e.user_id WHERE u.username=? AND e.work_date=?',[$r['code'],$r['period']])->fetch())fail('اليوم له تسجيلات فعلية؛ لا يستبدل بالاستيراد.');
   if($kind==='payroll'&&query('SELECT p.id FROM ep_payroll p JOIN ep_users u ON u.id=p.user_id WHERE u.username=? AND p.month_key=?',[$r['code'],$r['period']])->fetch())fail('الشهر له كشف موجود؛ لا يستبدل بالاستيراد.');
   if($kind==='bank')$b['_company']=company_by_code($b['residence_company_code']);
   query('INSERT INTO ep_history(kind,employee_code,period_key,body_json,imported_at,imported_by) VALUES(?,?,?,?,?,?)',[$kind,$r['code'],$r['period'],jencode($b),utc_now(),$uid]);
  }
 }
}
function administration_action(string $action,array $u,array $b):array{
 admin_only($u);if(in_array($action,['bank_build','bank_save','bank_approve'],true))operation_require('payroll');
 if($action==='companies')return query('SELECT * FROM ep_companies ORDER BY name')->fetchAll();
 if($action==='company_save'){
  $id=(int)($b['id']??0);$code=trim((string)($b['code']??''));$name=trim((string)($b['name']??''));if(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$code)||!$name)fail('كود الشركة واسمها مطلوبان.');
  if($id&&query('SELECT code FROM ep_companies WHERE id=?',[$id])->fetchColumn()!==$code)fail('كود الشركة ثابت للحفاظ على ارتباط البيانات.');
  $args=[$code,mb_substr($name,0,180),mb_substr($b['legal_name']??'',0,250),mb_substr($b['bank_name']??'',0,150),mb_substr($b['bank_account']??'',0,100),mb_substr($b['labor_file']??'',0,100),empty($b['active'])?0:1];
  if($id)query('UPDATE ep_companies SET code=?,name=?,legal_name=?,bank_name=?,bank_account=?,labor_file=?,active=? WHERE id=?',[...$args,$id]);else{query('INSERT INTO ep_companies(code,name,legal_name,bank_name,bank_account,labor_file,active,created_at) VALUES(?,?,?,?,?,?,?,?)',[...$args,utc_now()]);$id=(int)db()->lastInsertId();}audit('company_save','company:'.$id);return ['id'=>$id];
 }
 if($action==='history_list')return query('SELECT TOP (1000) id,kind,employee_code,period_key,body_json,imported_at FROM ep_history ORDER BY id DESC')->fetchAll();
 if($action==='import_preview'){
  $file=$_FILES['file']??[];if(($file['error']??1)!==UPLOAD_ERR_OK||($file['size']??0)>8*1024*1024)fail('ارفع XLSX بحجم أقصى 8 ميجابايت.');$doc=import_document(file_get_contents($file['tmp_name']));$token=bin2hex(random_bytes(24));query('INSERT INTO ep_import_jobs(token_hash,user_id,document_json,created_at) VALUES(?,?,?,?)',[hash('sha256',$token),$u['id'],jencode($doc),utc_now()]);return ['token'=>$token,'count'=>$doc['count'],'errors'=>$doc['errors'],'sample'=>array_slice($doc['records'],0,15)];
 }
 if($action==='import_commit'){
  db()->beginTransaction();$hash=hash('sha256',(string)($b['token']??''));$job=query('SELECT * FROM ep_import_jobs WITH (UPDLOCK, HOLDLOCK) WHERE token_hash=? AND user_id=?',[$hash,$u['id']])->fetch();if(!$job||$job['used_at']||strtotime($job['created_at'].' UTC')<time()-1800)fail('معاينة الاستيراد انتهت أو اعتمدت سابقًا.');$doc=decode($job['document_json']);import_commit($doc,(int)$u['id']);query('UPDATE ep_import_jobs SET used_at=? WHERE token_hash=?',[utc_now(),$hash]);audit('history_import','batch:'.substr($hash,0,12),['count'=>$doc['count']]);db()->commit();return ['count'=>$doc['count']];
 }
 if($action==='bank_list')return query('SELECT TOP (1000) * FROM ep_bank_payroll ORDER BY month_key DESC,id DESC')->fetchAll();
 if($action==='bank_build'){
  $month=(string)($b['month']??'');if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('الشهر غير صالح.');db()->beginTransaction();
  foreach(query("SELECT * FROM ep_users WHERE active=1 AND role<>'admin' ORDER BY id")->fetchAll()as $staff){query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$staff['id']]);$p=decode($staff['profile_json']);if(!empty($b['company'])&&($p['residence_company_code']??'')!==$b['company'])continue;$company=company_by_code((string)($p['residence_company_code']??''));if(!$company||!$company['active'])fail('حدد شركة إقامة مفعلة لكل موظف.');if(!isset($p['bank_salary'])||!is_numeric($p['bank_salary'])||$p['bank_salary']<0)fail('حدد مبلغ التحويل المعتاد لكل موظف؛ مستقل عن صافي الراتب الإداري.');
   if(historical_for((int)$staff['id'],'bank',$month,$month))continue;
   $locked=query("SELECT TOP (1) snapshot_json FROM ep_bank_payroll WHERE month_key=? AND company_code=? AND status='approved' ORDER BY id",[$month,$company['code']])->fetch();if($locked)$company=decode($locked['snapshot_json'])['company'];
   $snapshot=['employee_name'=>$staff['name'],'profile'=>$p,'company'=>$company];query("MERGE INTO ep_bank_payroll WITH (HOLDLOCK) AS target USING (VALUES (?, ?, ?, ?, ?, ?)) AS source (user_id, month_key, company_code, amount, snapshot_json, created_at) ON target.user_id=source.user_id AND target.month_key=source.month_key WHEN MATCHED THEN UPDATE SET amount=CASE WHEN target.status='draft' THEN source.amount ELSE target.amount END,snapshot_json=CASE WHEN target.status='draft' THEN source.snapshot_json ELSE target.snapshot_json END WHEN NOT MATCHED THEN INSERT (user_id, month_key, company_code, amount, snapshot_json, created_at) VALUES (source.user_id, source.month_key, source.company_code, source.amount, source.snapshot_json, source.created_at);",[$staff['id'],$month,$company['code'],(float)$p['bank_salary'],jencode($snapshot),utc_now()]);
  }audit('bank_build','month:'.$month);db()->commit();return ['ok'=>true];
 }
 if($action==='bank_save'||$action==='bank_approve'){
  db()->beginTransaction();$row=query('SELECT * FROM ep_bank_payroll WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[(int)($b['id']??0)])->fetch();if(!$row||$row['status']!=='draft')fail('لا تعدل كشفًا معتمدًا.');
  if($action==='bank_save'){if(!is_numeric($b['amount']??null)||(float)$b['amount']<0)fail('المبلغ غير صالح.');query('UPDATE ep_bank_payroll SET amount=? WHERE id=?',[(float)$b['amount'],$row['id']]);}
  else{$snapshot=decode($row['snapshot_json']);foreach(['name_en','bank_name','bank_account','iban','civil_id']as $field)if(empty($snapshot['profile'][$field]))fail('بيانات البنك ناقصة: '.$field);query("UPDATE ep_bank_payroll SET status='approved',approved_at=? WHERE id=?",[utc_now(),$row['id']]);}
  audit($action,'bank:'.$row['id']);db()->commit();return ['ok'=>true];
 }
 fail('العملية غير موجودة.',404);
}
