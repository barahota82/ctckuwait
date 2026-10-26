<?php
declare(strict_types=1);
// Incident-specific attendance review. Raw ep_events are never rewritten.
function punch_types39():array{return ['attendance'=>'حضور','checkout'=>'انصراف','permission_exit'=>'إذن خروج','permission_return'=>'عودة','presence'=>'إثبات التواجد'];}
function punch_rules39(array $p):array{
 if(isset($p['punch_rules39']))return $p['punch_rules39'];
 $out=[];foreach(punch_types39()as $type=>$label){
  if($type==='presence'){$out[$type]=['enabled'=>false,'minutes'=>0,'escalation'=>false,'period'=>'month','mode'=>'replace','tier_unit'=>'minutes','tiers'=>[]];continue;}
  $out[$type]=['enabled'=>in_array($type,['attendance','checkout'],true)&&(bool)($p['forget_enabled']??false),'minutes'=>(int)($p['missing_minutes']??120),'escalation'=>(bool)($p['forget_escalation_enabled']??!empty($p['forget_tiers'])),'period'=>$p['forget_period']??'month','mode'=>$p['penalty_mode']??'replace','tiers'=>$p['forget_tiers']??[]];
 }return $out;
}
function punch_validate39(array $p):void{
 if(!isset($p['punch_rules39']))return;
 if(!is_array($p['punch_rules39']))fail('قواعد البصمات الخمس غير مكتملة.');$punchKeys39=array_keys($p['punch_rules39']);sort($punchKeys39);$punchFour39=['attendance','checkout','permission_exit','permission_return'];$punchFive39=['attendance','checkout','permission_exit','permission_return','presence'];if($punchKeys39!==$punchFour39&&$punchKeys39!==$punchFive39)fail('قواعد البصمات الخمس غير مكتملة.');
 foreach($p['punch_rules39']as $r){
  if(!is_array($r)||!is_bool($r['enabled']??null)||!is_bool($r['escalation']??null)||!is_numeric($r['minutes']??null)||!is_finite((float)$r['minutes'])||$r['minutes']<0||$r['minutes']>1440||(float)$r['minutes']!=floor((float)$r['minutes'])||!in_array($r['period']??'', ['month','year'],true)||!in_array($r['mode']??'', ['add','replace'],true)||!is_array($r['tiers']??null))fail('إعداد جزاء البصمة غير صالح.');
  $unit=$r['tier_unit']??'days';if(!in_array($unit,['minutes','days'],true)||($unit==='minutes'&&$r['mode']!=='replace'))fail('إعداد جزاء البصمة غير صالح.');$valueKey=$unit==='minutes'?'minutes':'days';
  $seen=[];foreach($r['tiers']as $t){if(!is_array($t)||!is_numeric($t['from']??null)||!is_numeric($t[$valueKey]??null)||!is_finite((float)$t[$valueKey])||$t['from']<1||$t['from']>10000||(float)$t['from']!=floor((float)$t['from'])||$t[$valueKey]<0||$t[$valueKey]>($unit==='minutes'?44640:31)||($unit==='minutes'&&(float)$t[$valueKey]!=floor((float)$t[$valueKey]))||isset($seen[(int)$t['from']]))fail('شريحة جزاء البصمة غير صالحة.');$seen[(int)$t['from']]=true;}
  if($r['enabled']&&$r['escalation']&&!$r['tiers'])fail('أضف شريحة تصعيد أو أوقف التصعيد لهذه البصمة.');
 }
}
function punch_decisions39(int $uid,string $date):array{
 $out=[];foreach(query("SELECT * FROM ep_records WHERE user_id=? AND module='request' AND status='approved' AND date_from=? ORDER BY id",[$uid,$date])->fetchAll()as $r){$v=decode($r['body_json'])['_punch39']??null;if($v){$v['record_id']=(int)$r['id'];$out[]=$v;}}return $out;
}
function punch_minutes39(int $a,int $b,int $start,int $end):float{return max(0,min($end,$b)-max($start,$a))/60;}
function punch_day39(array $day,array $p,array $decisions,int $start,int $end,int $now):array{
 $date=$day['date'];$waivers=[];$resolutions=[];$manual=[];
 foreach($decisions as $d){$key=$d['key']??'';if(($d['kind']??'')==='waive_penalty')$waivers['penalty:'.$key]=$d['record_id'];if(($d['kind']??'')==='waive_duration')$waivers['duration:'.$key]=$d['record_id'];if(($d['kind']??'')==='resolve_return')$resolutions[$key]=$d;if(($d['kind']??'')==='missing_exit')$manual[]=$d;}
 $periods=[];$open=null;$chargedStart=$start;$chargedEnd=$end;
 foreach($day['events']as $event){$at=(new DateTimeImmutable($event['received_at'],new DateTimeZone('UTC')))->getTimestamp();if($event['event_type']==='attendance')$chargedStart=max($chargedStart,$at);if($event['event_type']==='checkout')$chargedEnd=min($chargedEnd,$at);}
 foreach($day['events']as $e){$ts=(new DateTimeImmutable($e['received_at'],new DateTimeZone('UTC')))->getTimestamp();
  if($e['event_type']==='permission_exit')$open=['key'=>$date.':permission:'.$e['id'],'exit_id'=>(int)$e['id'],'from'=>$ts,'to'=>null,'raw_return'=>false];
  if($e['event_type']==='permission_return'&&$open){$open['to']=$ts;$open['raw_return']=true;$periods[]=$open;$open=null;}
 }
 if($open)$periods[]=$open;
 foreach($manual as $m)if(!empty($m['exit_at'])&&!empty($m['return_at']))$periods[]=['key'=>$m['key'].':duration','exit_id'=>null,'from'=>strtotime($m['exit_at'].' UTC'),'to'=>strtotime($m['return_at'].' UTC'),'raw_return'=>true,'manual_record_id'=>$m['record_id']];
 $day['permission_periods39']=[];$day['permission_deduct_minutes39']=0.0;$day['permission_exempt_minutes39']=0.0;$day['permission_unresolved39']=false;$day['punch_incidents39']=[];
 foreach($periods as $period){$key=$period['key'];$resolved=$resolutions[$key]??null;if($period['to']===null&&$resolved)$period['to']=(new DateTimeImmutable($resolved['return_at'],new DateTimeZone('UTC')))->getTimestamp();
  $period['unresolved']=$period['to']===null;$period['minutes']=$period['to']===null?null:punch_minutes39($period['from'],$period['to'],$chargedStart,$chargedEnd);
  $period['exemption_id']=$waivers['duration:'.$key]??null;$period['resolution_id']=$resolved['record_id']??null;
  $day['permission_unresolved39']=$day['permission_unresolved39']||$period['unresolved'];
  if($period['minutes']!==null){if($period['exemption_id'])$day['permission_exempt_minutes39']+=$period['minutes'];else $day['permission_deduct_minutes39']+=$period['minutes'];}
  $day['permission_periods39'][]=$period;
  if(!$period['raw_return']&&$now>$end)$day['punch_incidents39'][]=['key'=>$date.':permission_return:'.$period['exit_id'],'type'=>'permission_return'];
 }
 $day['unclosed_permission']=$day['permission_unresolved39'];
 if($day['scheduled']&&$now>$end&&!$day['absence']){foreach(['attendance','checkout']as $type)if(!in_array($type,array_column($day['events'],'event_type'),true))$day['punch_incidents39'][]=['key'=>$date.':'.$type,'type'=>$type];}
 foreach($manual as $m)$day['punch_incidents39'][]=['key'=>$m['key'],'type'=>'permission_exit','evidence_id'=>$m['record_id']];
 foreach($day['punch_incidents39']as &$incident){$incident['exemption_id']=$waivers['penalty:'.$incident['key']]??null;$incident['penalty_minutes']=0.0;$incident['penalty_days']=0.0;}unset($incident);
 // Unsheduled days and all-day absence do not acquire additional missed-punch penalties.
 if(!$day['scheduled']||$day['absence'])$day['punch_incidents39']=[];
 if(!$day['scheduled'])$day['permission_deduct_minutes39']=0.0;
 return $day;
}
function punch_charge39(array $rule,int $sequence):array{
 $minutes=0.0;$days=0.0;if($rule['enabled']){$minutes=(float)$rule['minutes'];$tiers=$rule['escalation']?$rule['tiers']:[];usort($tiers,fn($a,$b)=>$a['from']<=>$b['from']);foreach($tiers as $t)if($sequence>=$t['from']){if(($rule['tier_unit']??'days')==='minutes'){$minutes=(float)$t['minutes'];$days=0.0;}else{$days=(float)$t['days'];if($rule['mode']==='replace')$minutes=0.0;}}}
 return [$minutes,$days];
}
function punch_apply39(int $uid,array $day,array $p,int $start,int $end,array &$counts,string $reportFrom,array $previousDays,array $decisions):array{
 $day=punch_day39($day,$p,$decisions,$start,$end,time());
 // Legacy policies keep their combined counter; only a dated saved V39 policy enables separate counters.
 if(!isset($p['punch_rules39'])){
  foreach($day['punch_incidents39']as &$legacy)if(in_array($legacy['type'],['attendance','checkout'],true)){$legacy['original_minutes']=$day['penalty_minutes'];$legacy['original_days']=$day['penalty_days'];if($legacy['exemption_id']){$day['penalty_minutes']=0;$day['penalty_days']=0;}else{$legacy['penalty_minutes']=$day['penalty_minutes'];$legacy['penalty_days']=$day['penalty_days'];}}unset($legacy);return $day;
 }
 $day['penalty_minutes']=0.0;$day['penalty_days']=0.0;if(!$day['absence'])$day['missing']=count($day['punch_incidents39']);
 foreach($day['punch_incidents39']as &$i){$rule=$p['punch_rules39'][$i['type']];$period=period_start($day['date'],$rule['period']);$k=$i['type'].':'.$period;
  if(!isset($counts[$k])){$counts[$k]=0;foreach($previousDays as $previous)if($previous['date']>=$period)foreach($previous['punch_incidents39']??[]as $prev)if($prev['type']===$i['type'])$counts[$k]++;if($period<$reportFrom)foreach(attendance_report($uid,$period,(new DateTimeImmutable($reportFrom))->modify('-1 day')->format('Y-m-d'))as $previous)foreach($previous['punch_incidents39']??[]as $prev)if($prev['type']===$i['type'])$counts[$k]++;}
  $i['sequence']=++$counts[$k];[$minutes,$days]=punch_charge39($rule,$i['sequence']);$i['original_minutes']=$minutes;$i['original_days']=$days;
  if(!$i['exemption_id']){$i['penalty_minutes']=$minutes;$i['penalty_days']=$days;$day['penalty_minutes']+=$minutes;$day['penalty_days']+=$days;}
 }unset($i);return $day;
}
function punch_access39(array $u,int $target,string $op='create'):void{
 $GLOBALS['EP_SCREEN']='request';
 if(personal_mode34($u)){if($target!==(int)$u['id']||$op!=='create')fail('الطلب خارج صلاحيات الملف الشخصي.',403);personal_require34($u,'request','create');}
 elseif($u['role']!=='admin'){permission_require($u,'request',$op);if(!in_array($target,permission_scope29($u,'request',$op),true)||($op==='review'&&$target===(int)$u['id']))fail('الطلب خارج نطاق صلاحياتك.',403);}
 employee_required($target);
}
function punch_options39(int $uid,string $date):array{
 if($date>local_time()->format('Y-m-d'))fail('لا توجد واقعة حضور مستقبلية.');$days=attendance_report($uid,$date,$date);$d=$days[0];$choices=[];
 if(!empty($d['historical']))return [];
 foreach($d['punch_incidents39']??[]as $i)if(!$i['exemption_id'])$choices[]=['kind'=>'waive_penalty','key'=>$i['key'],'type'=>$i['type'],'minutes'=>$i['penalty_minutes'],'days'=>$i['penalty_days']];
 foreach($d['permission_periods39']??[]as $p){if(!$p['unresolved']&&!$p['exemption_id']&&$p['minutes']>0)$choices[]=['kind'=>'waive_duration','key'=>$p['key'],'from'=>gmdate('Y-m-d H:i:s',$p['from']),'to'=>gmdate('Y-m-d H:i:s',$p['to']),'minutes'=>$p['minutes']];if($p['unresolved'])$choices[]=['kind'=>'resolve_return','key'=>$p['key'],'from'=>gmdate('Y-m-d H:i:s',$p['from'])];}
 return $choices;
}
function punch_prepare_request39(array $u,int $uid,?string $date,?string $to,array $body,bool $reviewing=false):?array{
 if(!isset($body['_punch39']))return null;
 punch_access39($u,$uid,$reviewing?'review':'create');if(!$date||$date!==$to)fail('طلب البصمة يخص واقعة في يوم واحد.');
 $v=$body['_punch39'];if(!is_array($v))fail('بيانات واقعة البصمة غير صالحة.');$kind=(string)($v['kind']??'');$key=(string)($v['key']??'');
 if(!trim((string)($body['details']??'')))fail('وضح سبب طلب مراجعة الواقعة.');
 if(in_array($kind,['resolve_return','missing_exit'],true))punch_access39($u,$uid,'review');
 if($kind==='missing_exit'){
  $p=policy_for($uid,$date)['values'];$day=attendance_report($uid,$date,$date)[0];
  if($date>=local_time()->format('Y-m-d')||!isset($p['punch_rules39'])||empty($day['scheduled'])||!empty($day['absence'])||!empty($day['leave']))fail('تثبيت خروج دون بصمة يتطلب يوم دوام منتهيًا وقاعدة البصمات الجديدة.');
  $at=(string)($v['exit_time']??'');if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$at))fail('حدد وقت واقعة الخروج المثبتة.');$return=(string)($v['return_time']??'');if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$return)||$return<=$at)fail('حدد وقت العودة المثبت بعد الخروج.');
  $tz=new DateTimeZone(settings()['timezone']);$a=new DateTimeImmutable($date.' '.$at,$tz);$z=new DateTimeImmutable($date.' '.$return,$tz);
  foreach($day['permission_periods39']??[]as $existing)if($a->getTimestamp()<($existing['to']??PHP_INT_MAX)&&$z->getTimestamp()>$existing['from'])fail('الفترة تتداخل مع خروج مسجل بالفعل.');
  $key=$date.':permission_exit:'.$at;return ['kind'=>$kind,'key'=>$key,'exit_time'=>$at,'return_time'=>$return,'exit_at'=>$a->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'return_at'=>$z->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
 }
 $option=null;foreach(punch_options39($uid,$date)as $o)if($o['key']===$key&&$o['kind']===$kind){$option=$o;break;}if(!$option)fail('الواقعة غير متاحة أو تمت تسويتها؛ حدّث البيانات.');
 $out=['kind'=>$kind,'key'=>$key];if($kind==='resolve_return'){
  if($date>=local_time()->format('Y-m-d'))fail('إثبات العودة إداريًا متاح بعد انتهاء يوم الواقعة.');
  $time=(string)($v['return_time']??'');if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$time))fail('حدد وقت العودة الفعلي المثبت.');
  $actual=new DateTimeImmutable($date.' '.$time,new DateTimeZone(settings()['timezone']));$exit=new DateTimeImmutable($option['from'],new DateTimeZone('UTC'));
  if($actual<=$exit||$actual->getTimestamp()>time())fail('وقت العودة يجب أن يلي الخروج وألا يكون في المستقبل.');
  $out['return_at']=$actual->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$out['return_time']=$time;
 }
 return $out;
}
function punch_affected_end39(string $date,array $v):string{
 // Preserve the existing conservative dependency rule for later payroll/debt snapshots.
 return '9999-12';
}
function punch_review39(array $u,array $r,array &$body,string $status,string $note):void{
 if($r['module']!=='request'||!isset($body['_punch39']))return;
 punch_access39($u,(int)$r['user_id'],'review');if(!trim($note))fail('سجل سبب قرار مراجعة واقعة البصمة.');
 if($status==='approved'){
  query('SELECT id FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$r['user_id']]);
  $v=$body['_punch39'];foreach(punch_decisions39((int)$r['user_id'],$r['date_from'])as $old)if($old['kind']===$v['kind']&&$old['key']===$v['key'])fail('سبق اعتماد قرار لهذه الواقعة.');
  // Validate again under employee lock; a stale request cannot waive a different interval.
  $validate=$body;$body['_punch39']=punch_prepare_request39($u,(int)$r['user_id'],$r['date_from'],$r['date_to'],$validate,true);
  $month=substr($r['date_from'],0,7);$endMonth=punch_affected_end39($r['date_from'],$v);
  if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>=? AND month_key<=? AND status IN ('submitted','approved')",[$r['user_id'],$month,$endMonth])->fetch()||query("SELECT id FROM ep_bank_payroll WHERE user_id=? AND month_key>=? AND month_key<=? AND status='approved'",[$r['user_id'],$month,$endMonth])->fetch())fail('الكشف مرسل أو معتمد؛ أعده للمراجعة قبل تغيير الأثر المالي لهذه الواقعة.',409);
 }
 $body['_punch39']['decision']=['status'=>$status,'actor'=>(int)$u['id'],'at'=>utc_now(),'note'=>$note];
}
function punch_refresh_payroll39(array $r,array $body,string $status,array $actor):void{
 if($r['module']!=='request'||!isset($body['_punch39'])||$status!=='approved')return;
 $month=substr($r['date_from'],0,7);$endMonth=punch_affected_end39($r['date_from'],$body['_punch39']);$uid=(int)$r['user_id'];
 $rows=query("SELECT * FROM ep_payroll WHERE user_id=? AND month_key>=? AND month_key<=? AND status IN ('draft','returned') ORDER BY month_key",[$uid,$month,$endMonth])->fetchAll();
 foreach($rows as $old){v26_payroll_version($old,$actor,'attendance_review','قرار مراجعة واقعة حضور');$staff=query('SELECT * FROM ep_users WHERE id=?',[$uid])->fetch();$calc=payroll_calculate37($staff,$old['month_key'],settings(),$old);query('UPDATE ep_payroll SET calculation_json=?,net_amount=? WHERE id=?',[jencode($calc['calculation']),$calc['net'],$old['id']]);}
 query("DELETE FROM ep_bank_payroll WHERE user_id=? AND month_key>=? AND month_key<=? AND status='draft'",[$uid,$month,$endMonth]);
 audit('attendance_incident_decision','request:'.$r['id'],['incident'=>$body['_punch39'],'month'=>$month]);
}

// Verify the exact persisted payload before committing a policy change.
function policy_saved392(int $id,array $expected):array{
 $row=query('SELECT value_json FROM ep_policies WHERE id=?',[$id])->fetch();
 if(!$row||!hash_equals(jencode($expected),(string)$row['value_json']))fail('تعذر التحقق من حفظ إعدادات الدوام. لم يتم اعتماد التعديل.',409);
 return ['ok'=>true,'id'=>$id,'punch_rules39'=>$expected['punch_rules39']??null];
}

function owner_rules393():array{
 $out=[];foreach(['attendance'=>[[3,180],[4,240],[5,300]],'checkout'=>[[3,150],[4,240],[5,300]],'permission_exit'=>[[1,180],[2,240],[3,300]],'permission_return'=>[[1,180],[2,240],[3,300]]]as $type=>$tiers)$out[$type]=['enabled'=>true,'minutes'=>120,'escalation'=>true,'period'=>'month','mode'=>'replace','tier_unit'=>'minutes','tiers'=>array_map(fn($t)=>['from'=>$t[0],'minutes'=>$t[1]],$tiers)];return $out;
}
function punch_values_at393(array $p,string $date):array{
 if(isset($p['punch_rules_from393'])&&$date<$p['punch_rules_from393']){if(isset($p['punch_rules_before393']))$p['punch_rules39']=$p['punch_rules_before393'];else unset($p['punch_rules39']);}return $p;
}
// Owner-authorized one-time correction. Only penalty fields change, not schedule periods.
function owner_policy393(array $actor):array{
 admin_only($actor);$from='2026-01-01';db()->beginTransaction();policy_lock_scope32(0);
 if(query("SELECT setting_key FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='owner_policy393'")->fetch()){db()->commit();return ['ok'=>true,'already_applied'=>true];}
 $staff=query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll();$ids=[];foreach($staff as $s){$state=service_state($s,local_time()->format('Y-m-d'));if($state['employed']&&$state['attendance'])$ids[]=(int)$s['id'];}
 foreach($ids as $id)financial_invalidate($id,$from);
 $rules=owner_rules393();$changes=[];
 foreach(query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) ORDER BY id')->fetchAll()as $row){
  $id=(int)($row['user_id']??0);if(($id&&!in_array($id,$ids,true))||($row['effective_to']&&$row['effective_to']<$from)||schedule_temp38($row))continue;
  $p=decode($row['value_json']);if(!isset($p['punch_rules_from393'])){$p['punch_rules_before393']=$p['punch_rules39']??null;}$p['punch_rules39']=$rules;$p['punch_rules_from393']=$from;
  query('UPDATE ep_policies SET value_json=? WHERE id=?',[jencode($p),$row['id']]);policy_saved392((int)$row['id'],$p);audit('owner_penalty_date_correction','policy:'.$row['id'],['before'=>$row,'after'=>$p,'rules_from'=>$from]);$changes[]=(int)$row['id'];
 }
 if(!$changes)fail('لا توجد سياسات لتطبيق تصحيح الجزاءات عليها.');
 query('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)',['owner_policy393',jencode(['from'=>$from,'at'=>utc_now(),'by'=>$actor['id'],'employee_ids'=>$ids,'policy_ids'=>$changes])]);db()->commit();unset($GLOBALS['REPORT_POLICIES32'],$GLOBALS['EP_REQUEST_SETTINGS32']);return ['ok'=>true,'from'=>$from,'employees'=>count($ids),'policies'=>count($changes)];
}

// Consolidate setup-only schedule splits. Keep an exact recovery snapshot and refuse used periods.
function owner_schedule396(array $actor):array{
 admin_only($actor);$from='2026-01-01';db()->beginTransaction();policy_lock_scope32(0);
 if(query("SELECT setting_key FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='owner_schedule396'")->fetch()){db()->commit();return ['ok'=>true,'already_applied'=>true];}
 $rows=query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) ORDER BY effective_from,id')->fetchAll();$groups=[];
 foreach($rows as $r)if(!schedule_temp38($r)&&(!$r['effective_to']||$r['effective_to']>=$from))$groups[(int)($r['user_id']??0)][]=$r;
 $changes=[];
 foreach($groups as $uid=>$list){
  if(!array_filter($list,fn($r)=>$r['effective_from']>=$from&&$r['effective_from']<='2026-10-17'))continue;
  foreach($list as $r)if($r['effective_from']>'2026-10-17')fail('توجد سياسة مستقبلية أخرى تحتاج مراجعة قبل توحيد إعدادات بداية العام.');
  $candidate=$list[count($list)-1];
  // General settings most recently edited by the owner are the currently active baseline.
  if(!$uid)foreach($list as $r)if($r['effective_from']<=local_time()->format('Y-m-d')&&(!$r['effective_to']||$r['effective_to']>=local_time()->format('Y-m-d')))$candidate=$r;
  foreach($list as $r)if(policy_impact32($r)['events']>0)fail('توجد بصمات مرتبطة بالفترات؛ لا يمكن دمجها تلقائيًا.');
  $p=decode($candidate['value_json']);$p['_schedule38']=['kind'=>'basic'];
  if($uid){$employee=employee_required($uid);$state=service_state($employee,local_time()->format('Y-m-d'));$p['_schedule38']['service_from']=$state['from']??$employee['hired_on'];financial_invalidate($uid,$from);}
  else foreach(query("SELECT id FROM ep_users WHERE role<>'admin'")->fetchAll()as $u)financial_invalidate((int)$u['id'],$from);
  foreach($list as $r){if($r['effective_from']<$from)query('UPDATE ep_policies SET effective_to=? WHERE id=?',['2025-12-31',$r['id']]);else query('DELETE FROM ep_policies WHERE id=?',[$r['id']]);}
  query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[$uid?:null,$from,null,$candidate['label'],jencode($p),utc_now()]);$id=(int)db()->lastInsertId();policy_saved392($id,$p);
  $change=['user_id'=>$uid,'before'=>$list,'source_id'=>$candidate['id'],'new_id'=>$id,'from'=>$from];audit('owner_schedule_baseline396','policy:'.$id,$change);$changes[]=$change;
 }
 query('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)',['owner_schedule396',jencode(['at'=>utc_now(),'by'=>$actor['id'],'changes'=>$changes])]);db()->commit();unset($GLOBALS['REPORT_POLICIES32'],$GLOBALS['EP_REQUEST_SETTINGS32']);return ['ok'=>true,'from'=>$from,'scopes'=>count($changes)];
}
