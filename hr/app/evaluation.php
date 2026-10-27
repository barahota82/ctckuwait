<?php
declare(strict_types=1);
function evaluation_period194(string $kind,int $year,int $part):array {
 $length=['monthly'=>1,'quarterly'=>3,'half'=>6,'annual'=>12][$kind]??0;if(!$length||$year<2000||$year>2100||$part<1||$part>12/$length)fail('فترة التقييم غير صالحة.');$start=new DateTimeImmutable(sprintf('%04d-%02d-01',$year,($part-1)*$length+1));return ['from'=>$start->format('Y-m-d'),'to'=>$start->modify('+'.$length.' months')->modify('-1 day')->format('Y-m-d'),'kind'=>$kind,'year'=>$year,'part'=>$part];
}
function evaluation_metric_keys194():array {return ['presence','absence','late','early','permissions','exit','return','returnLate','missingAttendance','missingCheckout','missingExit','missingReturn','missingPresence','leaveLateCount','leaveLateDays','exceptions','warnings','penalties'];}
function evaluation_reference194(string $prefix,int $year):string {
 // Called inside the record transaction. The row is serialized across all writers.
 $key='evaluation_sequence194_'.$prefix.'_'.$year;query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key,value_json) ON target.setting_key=source.setting_key WHEN NOT MATCHED THEN INSERT(setting_key,value_json) VALUES(source.setting_key,source.value_json);',[$key,'0']);$n=(int)query('SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key=?',[$key])->fetchColumn()+1;query('UPDATE ep_settings SET value_json=? WHERE setting_key=?',[(string)$n,$key]); return $prefix.'-'.$year.'-'.str_pad((string)$n,6,'0',STR_PAD_LEFT);
}
function evaluation_create_missing194(array $people,string $key,array $period): void {
 if(!$people)return;
 usort($people,fn($a,$b)=>strnatcasecmp(employee_key($a),employee_key($b))?:((int)$a['id']<=>(int)$b['id']));
 db()->beginTransaction();
 query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('evaluation_batch194','{}')) AS source (setting_key,value_json) ON target.setting_key=source.setting_key WHEN NOT MATCHED THEN INSERT (setting_key,value_json) VALUES (source.setting_key,source.value_json);");
 query("SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key=?",['evaluation_batch194'])->fetch();
 foreach($people as $staff){
  $uid=(int)$staff['id'];
  query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);
  $old=query("SELECT TOP (1) * FROM ep_records WHERE module='evaluation' AND user_id=? AND JSON_VALUE(body_json,'$.period_key194')=? ORDER BY id",[$uid,$key])->fetch();
  if($old)continue;
  $body=['_auto182'=>true,'source182'=>'system','record_category49'=>'attendance','period_key194'=>$key,'reference185'=>evaluation_reference194('EV',(int)local_time()->format('Y'))];
  $id=v26_insert('evaluation',$uid,$period['from'],$period['to'],$body,'awaiting_rules182');
  query('UPDATE ep_records SET title=? WHERE id=?',['تقييم الالتزام بالدوام',$id]);
  audit('evaluation_period_created','evaluation:'.$id,['period'=>$period]);
 }
 db()->commit();
}
function evaluation_counts194(int $uid,array $period):array {
 $values=array_fill_keys(evaluation_metric_keys194(),0);$today=local_time()->format('Y-m-d');$to=min($today,$period['to']);$days=$period['from']<=$to?attendance_report($uid,$period['from'],$to):[];$source=0;
 $presenceRefresh=null;$zone=new DateTimeZone((string)(settings()['timezone']??'Asia/Kuwait'));$nowStamp=local_time()->getTimestamp();
 foreach($days as $day){if(empty($day['configured'])||empty($day['scheduled'])||!empty($day['leave'])||isset($day['note'])&&str_contains($day['note'],'قبل بدء'))continue;$source++;$events=$day['events'];$types=array_column($events,'event_type');if(in_array('attendance',$types,true))$values['presence']++;if($day['absence'])$values['absence']++;$values['late']+=(float)$day['late_minutes'];$values['early']+=(float)$day['early_minutes'];
  if(($day['missing_raw194']??$day['missing'])>0&&!($day['absence_raw194']??$day['absence'])){if(!in_array('attendance',$types,true))$values['missingAttendance']++;if(!in_array('checkout',$types,true))$values['missingCheckout']++;}$values['missingPresence']+=(int)($day['presence_missed']??0);
  foreach($day['presence_slots']??[] as $slot){if(in_array($slot['status']??'',['sent','missed'],true)||empty($slot['closes_at']))continue;$stamp=(new DateTimeImmutable((string)$slot['closes_at'],$zone))->getTimestamp()+1;if($stamp>$nowStamp)$presenceRefresh=$presenceRefresh===null?$stamp:min($presenceRefresh,$stamp);}
 }
 foreach(($GLOBALS['EVAL_INPUT195'][$uid]['permissions']??query("SELECT * FROM ep_records WHERE user_id=? AND module='exit_permission194' AND status='approved' AND date_from>=? AND date_from<=?",[$uid,$period['from'],$to])->fetchAll())as $r){$p=decode($r['body_json']);if(!empty($p['cancelled_at']))continue;$values['permissions']++;if(!empty($p['exited_at']))$values['exit']++;if(!empty($p['returned_at'])){$values['return']++;$values['returnLate']+=max(0,(permission_clock194($p['returned_at'])->getTimestamp()-permission_clock194($p['ends_at'])->getTimestamp())/60);}if(permission_clock194($p['ends_at'])->getTimestamp()<time()){if(empty($p['exited_at']))$values['missingExit']++;elseif(empty($p['returned_at']))$values['missingReturn']++;}}
 foreach(($GLOBALS['EVAL_INPUT195'][$uid]['decisions']??query("SELECT * FROM ep_records WHERE user_id=? AND module IN ('request','discipline') AND status='approved' AND date_from>=? AND date_from<=?",[$uid,$period['from'],$to])->fetchAll())as $r){$b=decode($r['body_json']);if($r['module']==='request'&&!empty($b['_punch39']))$values['exceptions']++;if($r['module']==='discipline'){if(($b['record_category49']??'')==='warning')$values['warnings']++;if(($b['record_category49']??'')==='penalty')$values['penalties']++;}}
 foreach(($GLOBALS['EVAL_INPUT195'][$uid]['leaves']??query("SELECT * FROM ep_records WHERE user_id=? AND module='leave' AND status='approved' AND date_to<?",[$uid,$to])->fetchAll())as $r){if(function_exists('leave_effect_pending200')&&leave_effect_pending200($r))continue;$b=decode($r['body_json']);$expected=$b['expected_return']??(new DateTimeImmutable($r['date_to']))->modify('+1 day')->format('Y-m-d');$actual=$b['returned_on']??null;if(!$actual&&!empty($b['actual_end']))$actual=(new DateTimeImmutable($b['actual_end']))->modify('+1 day')->format('Y-m-d');$end=$actual?(new DateTimeImmutable($actual))->modify('-1 day')->format('Y-m-d'):$to;if($end<$expected)continue;if($expected>=$period['from']&&$expected<=$to)$values['leaveLateCount']++;$a=max($period['from'],$expected);$z=min($to,$end);if($a<=$z)$values['leaveLateDays']+=(int)(new DateTimeImmutable($a))->diff(new DateTimeImmutable($z))->format('%a')+1;}
 return ['values'=>$values,'source_days'=>$source,'as_of'=>$to,'score'=>null,'presence_refresh'=>$presenceRefresh];
}
function evaluation_system194(array $actor):array {
 $period=evaluation_period194((string)($_GET['kind182']??'monthly'),(int)($_GET['year182']??local_time()->format('Y')),(int)($_GET['part182']??local_time()->format('n')));
 $ids=employee_scope69(scope_ids($actor),max(0,(int)($_GET['employee69']??0)));$people=scoped_people195($ids);$roster=$people;$key=$period['kind'].'|'.$period['year'].'|'.$period['part'];$existing=[];
 foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));foreach(query("SELECT * FROM ep_records WHERE module='evaluation' AND user_id IN ($marks) AND JSON_VALUE(body_json,'$.period_key194')=? ORDER BY id",[...$chunk,$key])->fetchAll()as $r)$existing[(int)$r['user_id']]??=$r;}
 if($p=page_request195()){
  $all=count($people);$people=array_values(array_filter($people,function($s)use($existing,$period,$p){$r=$existing[(int)$s['id']]??['user_id'=>$s['id'],'module'=>'evaluation','title'=>'تقييم الالتزام بالدوام','status'=>'awaiting_rules182','date_from'=>$period['from'],'date_to'=>$period['to'],'body_json'=>'{"_auto182":true}'];$r['employee_name']=$s['name'];$r['employee_code']=employee_key($s);$r['name_en']=decode($s['profile_json'])['name_en']??'';return page_matches195($r,$p['filters']);}));
  $window=page_rows195($people,$p,$all);$people=$window['rows195'];$GLOBALS['PAGE195_READY']=$window['page195'];
 }
 evaluation_create_missing194($roster,$key,$period);
 if(!$people)return [];
 // Existing API writes are audited. Include append-only sources and non-evaluation
 // record changes as well. Reuse snapshots only within this source revision/day.
 $revision=query("SELECT COALESCE(MAX(id),0) FROM ep_audit WHERE action NOT IN ('evaluation_period_created','evaluation_snapshot195')")->fetchColumn();
 $recordVersion=query("SELECT COUNT_BIG(*) AS n,MAX(id) AS last_id,MAX(updated_at) AS changed FROM ep_records WHERE module<>'evaluation'")->fetch();
 $eventVersion=query('SELECT COUNT_BIG(*) AS n,MAX(id) AS last_id,MAX(received_at) AS changed FROM ep_events')->fetch();
 $historyVersion=query('SELECT COUNT_BIG(*) AS n,MAX(id) AS last_id,MAX(imported_at) AS changed FROM ep_history')->fetch();
 $config=settings();foreach(array_keys($config)as $name)if(str_starts_with($name,'evaluation_sequence194_'))unset($config[$name]);
 $signature=hash('sha256',jencode(['195.0','presence-window-refresh-1',$period,local_time()->format('Y-m-d'),$revision,$recordVersion,$eventVersion,$historyVersion,$config]));
 $nowStamp=local_time()->getTimestamp();
 $stale=[];foreach($people as $s){$body=isset($existing[(int)$s['id']])?decode($existing[(int)$s['id']]['body_json']):[];if(($body['_inputs195']??'')!==$signature||!isset($body['metrics182'])||!empty($body['_next_refresh195'])&&(int)$body['_next_refresh195']<=$nowStamp)$stale[]=$s;}
 $to=min(local_time()->format('Y-m-d'),$period['to']);
 if($stale&&$period['from']<=$to)attendance_prefetch113($stale,$period['from'],$to);
 foreach(array_chunk($stale,500)as $chunk){
  $chunkIds=array_map('intval',array_column($chunk,'id'));$marks=implode(',',array_fill(0,count($chunkIds),'?'));
  foreach($chunkIds as $uid)$GLOBALS['EVAL_INPUT195'][$uid]=['permissions'=>[],'decisions'=>[],'leaves'=>[]];
  foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND status='approved' AND ((module='leave' AND date_to<?) OR (module IN ('exit_permission194','request','discipline') AND date_from>=? AND date_from<=?)) ORDER BY id",[...$chunkIds,$to,$period['from'],$to])->fetchAll()as $r){$bucket=$r['module']==='leave'?'leaves':($r['module']==='exit_permission194'?'permissions':'decisions');$GLOBALS['EVAL_INPUT195'][(int)$r['user_id']][$bucket][]=$r;}
 }
 $out=[];
 foreach($people as $staff){
  $uid=(int)$staff['id'];$r=$existing[$uid]??null;
  if(isset($GLOBALS['EVAL_INPUT195'][$uid])){
   $metrics=evaluation_counts194($uid,$period);$presenceNext=$metrics['presence_refresh']??null;unset($metrics['presence_refresh']);$next=$presenceNext?(int)$presenceNext:null;
   foreach($GLOBALS['EVAL_INPUT195'][$uid]['permissions']as $permission){$v=decode($permission['body_json']);if(!empty($v['cancelled_at']))continue;$end=permission_clock194($v['ends_at'])->getTimestamp();if($end>=$nowStamp)$next=$next===null?$end+1:min($next,$end+1);}
   db()->beginTransaction();query('SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$uid]);
   $old=query("SELECT TOP (1) * FROM ep_records WHERE module='evaluation' AND user_id=? AND JSON_VALUE(body_json,'$.period_key194')=? ORDER BY id",[$uid,$key])->fetch();
   $body=$old?decode($old['body_json']):['_auto182'=>true,'source182'=>'system','record_category49'=>'attendance','period_key194'=>$key,'reference185'=>evaluation_reference194('EV',(int)local_time()->format('Y'))];
   $body['metrics182']=$metrics;$body['period182']=$period;$body['_inputs195']=$signature;$body['_next_refresh195']=$next;
   if($old){if(jencode($body)!==$old['body_json'])query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$old['id']]);$r=$old;$r['body_json']=jencode($body);}
   else{$id=v26_insert('evaluation',$uid,$period['from'],$period['to'],$body,'awaiting_rules182');query('UPDATE ep_records SET title=? WHERE id=?',['تقييم الالتزام بالدوام',$id]);audit('evaluation_period_created','evaluation:'.$id,['period'=>$period]);$r=query('SELECT * FROM ep_records WHERE id=?',[$id])->fetch();}
   db()->commit();unset($GLOBALS['EVAL_INPUT195'][$uid]);
  }
  $r['employee_name']=$staff['name'];$r['employee_code']=employee_key($staff);$out[]=$r;
 }
 return $out;
}
function evaluation_action194(string $action,array $actor,array $b):array {
 if(in_array($action,['evaluation_settings182','evaluation_draft182'],true)){admin_only($actor);if($action==='evaluation_settings182')return setting31('evaluation_draft194',['points'=>[],'saved_at'=>null])+['approved'=>false];$points=[];foreach(evaluation_metric_keys194()as $key){$v=$b['points'][$key]??null;if($v===''||$v===null)$points[$key]=null;elseif(is_numeric($v)&&is_finite((float)$v)&&(float)$v>=0)$points[$key]=(float)$v;else fail('قيمة النقاط غير صالحة.');}$draft=['points'=>$points,'saved_at'=>utc_now(),'approved'=>false];put_setting31('evaluation_draft194',$draft);return $draft;}
 if($action==='evaluation_parents185'){$target=(int)($_GET['user_id']??0);employee_required($target);if(!in_array($target,permission_scope29($actor,'evaluation'),true))fail('الموظف خارج النطاق.',403);$rows=query("SELECT * FROM ep_records WHERE module='evaluation' AND user_id=? AND COALESCE(JSON_VALUE(body_json,'$.record_category49'),'')<>'improvement' ORDER BY id DESC",[$target])->fetchAll();foreach($rows as &$r){$v=decode($r['body_json']);if(empty($v['reference185']))$v['reference185']='EV-'.substr($r['created_at'],0,4).'-L'.str_pad((string)$r['id'],6,'0',STR_PAD_LEFT);$r['body_json']=jencode($v);}unset($r);return $rows;}
 if($action==='evaluation_followup185'){$r=query("SELECT * FROM ep_records WHERE module='evaluation' AND id=?",[(int)($b['id']??0)])->fetch();if(!$r||personal_mode34($actor)||!in_array((int)$r['user_id'],permission_scope29($actor,'evaluation','create'),true))fail('الخطة خارج نطاقك.',403);$text=trim((string)($b['result']??''));if(!$text||mb_strlen($text)>5000)fail('سجل نتيجة المتابعة في حدود 5000 حرف.');db()->beginTransaction();$r=query('SELECT * FROM ep_records WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$r['id']])->fetch();$v=decode($r['body_json']);if(($v['record_category49']??'')!=='improvement')fail('المتابعة تخص خطة تحسين فقط.');$v['followup_history194'][]=['result'=>$text,'at'=>utc_now(),'by'=>(int)$actor['id']];$v['followup_result']=$text;$v['followup_at']=utc_now();query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($v),utc_now(),$r['id']]);audit('evaluation_followup','record:'.$r['id']);db()->commit();return ['ok'=>true];}
 fail('العملية غير موجودة.',404);
}
function record_validate194(array &$b):void {
 $module=(string)($b['module']??'');$v=$b['body']??[];if(is_string($v))$v=decode($v);$category=(string)($v['record_category49']??'');
 if($module==='custody'){if(!valid_date($b['date_from']??null))fail('تاريخ الاستلام مطلوب.');$return=valid_date($v['return_date']??null);if($return&&$return<$b['date_from'])fail('تاريخ الاسترجاع لا يسبق تاريخ الاستلام.');$v['custody_state49']=$return?'returned':'active';$v['asset_type']=$b['title']??'';$b['amount']=0;$b['date_to']=null;}
 if($module==='document'&&$category==='certificates'){if(!valid_date($b['date_from']??null))fail('تاريخ الشهادة مطلوب.');$b['date_to']=null;$v['expiry']='';}
 if($module==='discipline'){if($category!=='decision')$b['date_to']=null;if($category!=='penalty'){$b['amount']=0;$v['payroll_month']='';}elseif(!is_numeric($b['amount']??null)||(float)$b['amount']<=0||!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',(string)($v['payroll_month']??'')))fail('مبلغ الجزاء وشهر التطبيق مطلوبان.');}
 if($module==='request'&&empty($v['_punch39'])&&$category!=='attendance'){
  $required=['salary'=>['salary_month','request_type','details'],'letters'=>['request_type','purpose'],'data'=>['request_type','details'],'assets'=>['requested_item','reason'],'complaints'=>['request_type','details'],'other'=>['details']][$category]??null;if($required===null)fail('اختر تصنيف الطلب.');foreach($required as $key)if(!trim((string)($v[$key]??'')))fail('أكمل الحقول المطلوبة.');if($category==='salary'&&!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',(string)$v['salary_month']))fail('شهر الراتب غير صالح.');$b['date_from']=null;$b['date_to']=null;$b['amount']=0;
 }
 if($module==='loan'){if(!trim((string)($v['reason']??'')))fail('سبب طلب السلفة مطلوب.');$b['date_from']=null;$b['date_to']=null;}
 if($module==='evaluation'){
  $from=valid_date($b['date_from']??null);$to=valid_date($b['date_to']??null);if(!$from||!$to||$to<$from)fail('حدد فترة صحيحة للتقييم.');$v['source182']='manager';unset($v['_auto182'],$v['reference185'],$v['metrics182'],$v['period_key194']);
  if($category==='improvement'){$target=(int)($b['user_id']??0);$parent=query("SELECT * FROM ep_records WHERE id=? AND module='evaluation' AND user_id=?",[(int)($v['related_record49']??0),$target])->fetch();if(!$parent||(decode($parent['body_json'])['record_category49']??'')==='improvement')fail('اختر تقييمًا أصليًا لنفس الموظف.');$follow=valid_date($v['followup_date']??null);if(!$follow||$follow<$from)fail('موعد المتابعة لا يسبق بداية الخطة.');$pv=decode($parent['body_json']);$v['related_reference185']=$pv['reference185']??'EV-'.substr($parent['created_at'],0,4).'-L'.str_pad((string)$parent['id'],6,'0',STR_PAD_LEFT);$v['score']='';}
 }
 $b['body']=$v;
}
