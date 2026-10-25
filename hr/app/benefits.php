<?php
declare(strict_types=1);
// One employee editor owns the dated entitlement source. No data migration on reads.
function employee_benefits198(array $staff):array {
 $date=local_time()->format('Y-m-d');$flags=v26_entitlements($staff,$date);
 $rows=query("SELECT * FROM ep_records WHERE user_id=? AND module='entitlement' AND status='approved' ORDER BY date_from,id",[$staff['id']])->fetchAll();
 return ['date'=>$date,'flags'=>array_intersect_key($flags,array_flip(['leave_days','leave_cash','eos'])),'records'=>$rows,'revision'=>hash('sha256',jencode($rows))];
}
function employee_benefits_save198(array $actor,int $uid,array $data,bool $new=false):void {
 admin_only($actor);if(!db()->inTransaction())throw new LogicException('Employee transaction required');
 $staff=employee_required($uid);$current=employee_benefits198($staff);
 if(!($new&&!$current['records'])&&!hash_equals($current['revision'],(string)($data['revision']??'')))fail('تغيرت الاستحقاقات؛ أعد فتح ملف الموظف.',409);
 $changes=$data['changes']??[];if(!is_array($changes)||count($changes)>3)fail('بيانات الاستحقاقات غير صالحة.');
 foreach($changes as $kind=>$value){
  if(!in_array($kind,['leave_days','leave_cash','eos'],true)||!is_array($value)||!is_bool($value['enabled']??null))fail('نوع الاستحقاق غير صالح.');
  $from=valid_date($value['from']??null);$to=valid_date($value['to']??null);if(!$from||($to&&$to<$from)||!service_state($staff,$from)['employed'])fail('حدد فترة سريان الاستحقاق داخل الخدمة.');
  financial_invalidate($uid,$from);
  foreach($current['records'] as $row){$body=decode($row['body_json']);if(($body['kind']??'')!==$kind)continue;
   if($row['date_from']>($to??'9999-12-31')||($row['date_to']??'9999-12-31')<$from)continue;
   if($row['date_from']>=$from)fail('يوجد تغيير مسجل داخل الفترة؛ اختر فترة لا تتداخل مع التاريخ المحفوظ.');
   $end=(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d');query('UPDATE ep_records SET date_to=?,updated_at=? WHERE id=?',[$end,utc_now(),$row['id']]);audit('entitlement_period_closed198','record:'.$row['id'],['before'=>$row,'to'=>$end]);
  }
  $id=v26_insert('entitlement',$uid,$from,$to,['kind'=>$kind,'enabled'=>$value['enabled'],'reason'=>mb_substr(trim((string)($value['reason']??'')),0,1000),'source'=>'employee198'],'approved');
  audit('employee_entitlement198','record:'.$id,['kind'=>$kind,'enabled'=>$value['enabled'],'from'=>$from,'to'=>$to]);
 }
 unset($GLOBALS['V26_ENTITLEMENTS'][$uid]);
}
function friday_access198(int $uid,string $date):array {
 $r=query("SELECT TOP (1) * FROM ep_records WHERE module='friday_access35' AND user_id=? AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$uid,$date])->fetch();
 return ['enabled'=>$r?(bool)(decode($r['body_json'])['enabled']??false):false,'from'=>$r['date_from']??null];
}
function addition_month198(string $month):string {
 if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month))fail('حدد شهر الإضافة.');return $month.'-01';
}
function addition_rows198(int $uid,string $month):array {
 $rows=(($_SERVER['REQUEST_METHOD']??'')==='GET'&&isset($GLOBALS['ADDITIONS198'][$uid]))?$GLOBALS['ADDITIONS198'][$uid]:query("SELECT * FROM ep_records WHERE user_id=? AND module IN ('addition','adjustment') AND status NOT IN ('cancelled','rejected','archived') ORDER BY id",[$uid])->fetchAll();
 return array_map(function($r){$r['revision198']=hash('sha256',jencode([$r['body_json'],(float)$r['amount'],$r['status'],$r['updated_at']]));return $r;},array_values(array_filter($rows,fn($r)=>(decode($r['body_json'])['payroll_month']??decode($r['body_json'])['month']??'')===$month)));
}
function payroll_addition198(array $actor,array $b):array {
 if(personal_mode34($actor))fail('هذه العملية للإدارة.',403);
 $key=(string)($b['key']??'');if(!in_array($key,['payroll_entry_click126','payroll_entry_afaq126'],true))fail('شاشة غير صالحة.');
 permission_require($actor,$key);$uid=(int)($b['user_id']??0);$staff=employee_required($uid);$month=(string)($b['month']??'');$from=addition_month198($month);$end=pay35_month($month);
 if(!in_array($uid,permission_scope29($actor,$key),true))fail('الموظف خارج نطاقك.',403);
 $company=$key==='payroll_entry_click126'?'CMP-0001':'CMP-0003';if(!company_allowed30($actor,$key,$company)||!pay41_build_matches($staff,$month,$company))fail('الموظف خارج شركة الشهر المحدد.',403);
 $direct=$actor['role']==='admin'||(permission_has($actor,'addition','create')&&in_array($uid,permission_scope29($actor,'addition','create'),true));
 if(!$direct&&(!permission_has($actor,'adjustment','create')||!in_array($uid,permission_scope29($actor,'adjustment','create'),true)))fail('لا تملك صلاحية تسجيل الإضافة.',403);
 if($actor['role']!=='admin'&&$uid===(int)$actor['id'])fail('لا يجوز تسجيل إضافة لنفسك.',403);
 $amount=$b['amount']??null;$reason=trim((string)($b['reason']??''));$token=(string)($b['request_id']??'');
 if(!is_numeric($amount)||!is_finite((float)$amount)||(float)$amount<=0||!$reason||strlen($reason)>4000||!preg_match('/^[a-zA-Z0-9-]{16,80}$/',$token))fail('أدخل مبلغًا موجبًا وسبب الإضافة.');$amount=round((float)$amount,3);
 db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);
 $existing=null;foreach(addition_rows198($uid,$month) as $r){$v=decode($r['body_json']);if(($v['request198']??'')===$token){if(abs((float)$r['amount']-$amount)>.0005||($v['reason']??'')!==$reason)fail('تغير محتوى الطلب؛ أعد تحميله.',409);db()->commit();return ['id'=>(int)$r['id'],'duplicate'=>true];}
  if((int)($b['id']??0)===(int)$r['id']&&($v['source198']??'')==='payroll_entry'){if(!hash_equals($r['revision198'],(string)($b['revision']??'')))fail('تغيرت الإضافة؛ أعد تحميل الشهر.',409);if($r['status']==='approved'&&!$direct)fail('تعديل الإضافة المعتمدة يتطلب صلاحية تسجيل الإضافات المعتمدة.',403);$existing=$r;continue;}
  // The unified monthly field cannot silently stack on another source for this month.
  fail('توجد إضافة لهذا الموظف في الشهر؛ راجع السجل القائم قبل تسجيل مبلغ آخر.',409);
 }
 if(!empty($b['id'])&&!$existing)fail('الإضافة غير متاحة للتعديل.',409);if($direct||($existing['status']??'')==='approved')financial_invalidate($uid,$from);$status=$direct?'approved':'pending';
 $body=['payroll_month'=>$month,'reason'=>$reason,'source198'=>'payroll_entry','request198'=>$token];
 if($existing){$id=(int)$existing['id'];query('UPDATE ep_records SET body_json=?,amount=?,status=?,updated_at=? WHERE id=?',[jencode($body),$amount,$status,utc_now(),$id]);audit('payroll_addition_update198','record:'.$id,['before'=>$existing,'after'=>$body,'amount'=>$amount]);}else $id=v26_insert('adjustment',$uid,$from,$end,$body,$status,$amount);audit('payroll_addition198','record:'.$id,['amount'=>$amount,'month'=>$month,'status'=>$status]);
 if(!$direct)foreach(request_notice_recipients32($uid,'adjustment') as $approver)notice((int)$approver,'إضافة شهرية بانتظار المراجعة',$reason,false,['screen'=>'adjustment','record_id'=>$id]);
 db()->commit();return ['id'=>$id,'status'=>$status];
}

// Aggregate actual receipts without multiplying the original settlement rows.
function leave_paid_join198():string {
 return " LEFT JOIN (SELECT entries.user_id,entries.source_id,SUM(entries.amount) AS amount FROM (
 SELECT p.user_id,a.source_id,a.amount FROM ep_records p CROSS APPLY OPENJSON(p.body_json,'$.leave_allocations') WITH (source_id bigint '$.id',amount decimal(18,3) '$.amount') a WHERE p.module='payment' AND p.status='approved'
 UNION ALL SELECT p.user_id,TRY_CONVERT(bigint,JSON_VALUE(p.body_json,'$.source_id')),p.amount FROM ep_records p WHERE p.module='payment' AND p.status='approved' AND JSON_VALUE(p.body_json,'$.kind')='leave_cash' AND COALESCE(JSON_QUERY(p.body_json,'$.leave_allocations'),'[]')='[]'
 ) entries GROUP BY entries.user_id,entries.source_id) cashPaid198 ON cashPaid198.user_id=r.user_id AND cashPaid198.source_id=r.id ";
}
function leave_paid_rows198(array $rows):array {
 $paid=[];$uids=array_unique(array_map('intval',array_column($rows,'user_id')));
 foreach(array_chunk($uids,500) as $ids){$marks=implode(',',array_fill(0,count($ids),'?'));foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module='payment' AND status='approved'",$ids)->fetchAll() as $r){$v=decode($r['body_json']);$alloc=$v['leave_allocations']??[];if(!$alloc&&($v['kind']??'')==='leave_cash')$alloc=[['id'=>(int)$v['source_id'],'amount'=>(float)$r['amount']]];foreach($alloc as $a)$paid[(int)$r['user_id']][(int)$a['id']]=($paid[(int)$r['user_id']][(int)$a['id']]??0)+(float)$a['amount'];}}
 foreach($rows as &$r)$r['paid_amount198']=round($paid[(int)$r['user_id']][(int)$r['id']]??0,3);unset($r);return $rows;
}
