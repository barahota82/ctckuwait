<?php
declare(strict_types=1);
// Conservative dependency check: protects stored events and all payroll snapshots.
function policy_impact32(array $row):array{
 $uid=(int)($row['user_id']??0);$from=$row['effective_from'];$to=$row['effective_to']??'9999-12-31';
 $staff=query("SELECT id,name,username,profile_json FROM ep_users WHERE role<>'admin'".($uid?' AND id=?':''),$uid?[$uid]:[])->fetchAll();
 $staff=array_map(fn($u)=>['id'=>(int)$u['id'],'name'=>$u['name'],'employee_code'=>employee_key($u)],$staff);usort($staff,fn($a,$b)=>strnatcasecmp($a['employee_code'],$b['employee_code']));
 $eventParams=[(int)$row['id'],$from,$to];if($uid)$eventParams[]=$uid;
 $events=(int)query('SELECT COUNT(*) FROM ep_events WHERE policy_id=? OR (work_date>=? AND work_date<=?'.($uid?' AND user_id=?':'').')',$eventParams)->fetchColumn();
 $params=[substr($from,0,7),substr($to,0,7)];if($uid)$params[]=$uid;
 $payroll=(int)query('SELECT COUNT(*) FROM ep_payroll WHERE month_key>=? AND month_key<=?'.($uid?' AND user_id=?':''),$params)->fetchColumn();
 $cfg=settings();$today=local_time()->format('Y-m-d');$start=$cfg['attendance_start']??$cfg['operation_start']??(query('SELECT MIN(work_date) FROM ep_events')->fetchColumn()?:null);
 $operational=$start&&max($start,$from)<=min($today,$to);
 $result=['used'=>$events>0||$payroll>0||$operational,'events'=>$events,'payroll'=>$payroll,'operational'=>(bool)$operational,'from'=>$from,'to'=>$row['effective_to'],'staff'=>$staff];
 $result['fingerprint']=hash('sha256',jencode([$row,$events,$payroll,(bool)$operational,array_column($staff,'id')]));return $result;
}
function policy_locked32(int $id):array{
 $peek=query('SELECT * FROM ep_policies WHERE id=?',[$id])->fetch();if(!$peek)fail('السياسة غير موجودة');
 policy_lock_scope32((int)($peek['user_id']??0));
 $row=query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) WHERE id=?',[$id])->fetch();if(!$row)fail('السياسة غير موجودة');return $row;
}
function policy_lock_scope32(int $uid):void{
 query("SELECT id FROM ep_users WITH (UPDLOCK,HOLDLOCK) WHERE role<>'admin'".($uid?' AND id=?':'').' ORDER BY id',$uid?[$uid]:[])->fetchAll();
}
function policy_conflict32(int $uid,string $from,?string $to,int $exclude=0):void{
 $rows=query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) WHERE id<>? AND (user_id=? OR (user_id IS NULL AND ?=0)) AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?)',[$exclude,$uid,$uid,$to??'9999-12-31',$from])->fetchAll();
 if(function_exists('schedule_temp38'))$rows=array_values(array_filter($rows,fn($r)=>!schedule_temp38($r)));if($rows)fail(policy_conflict_message371($rows[0]),409);
}
function policy_access32(array $u,int $uid,string $op='view'):void{
 if($u['role']==='admin')return;
 if(($_SESSION['access_mode29']??'personal')!=='team')fail('انتقل إلى عرض الفريق',403);
 $key=$uid?'policy_staff':'policy_general';permission_require($u,$key,$op);
 if($uid&&!in_array($uid,permission_scope29($u,$key,$op),true))fail('الموظف خارج نطاق الصلاحية',403);
 if(!$uid&&$op!=='view'){
  $all=false;foreach(permission_rules29($u,$key)as $r)if(($r['scope']??'')==='all'&&($r['company_mode']??'all')==='all'&&in_array($op,$r['allow']??[],true))$all=true;
  if(!$all)fail('تعديل السياسة العامة يتطلب نطاق جميع الموظفين وجميع الشركات.',403);
 }
}
function policy_staff_list32(array $u):array{
 policy_context_access32($u);if($u['role']!=='admin')permission_require($u,'policy_staff');
 $ids=$u['role']==='admin'?null:permission_scope29($u,'policy_staff');$out=[];
 $staff38=query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll();if($ids!==null)$staff38=array_values(array_filter($staff38,fn($p)=>in_array((int)$p['id'],$ids,true)));service_prefetch($staff38);$general38=[];$own38=[];foreach(query('SELECT * FROM ep_policies ORDER BY effective_from DESC,id DESC')->fetchAll()as $policy38){if(empty($policy38['user_id']))$general38[]=$policy38;else $own38[(int)$policy38['user_id']][]=$policy38;}
 $today38=local_time()->format('Y-m-d');foreach($staff38 as $p){$GLOBALS['REPORT_POLICIES32'][(int)$p['id']]=array_merge($general38,$own38[(int)$p['id']]??[]);$v=array_intersect_key(public_user($p),array_flip(['id','name','employee_code','username','role','profile','staff_type']));$v['profile']=array_intersect_key($v['profile']??[],['name_en'=>1]);$v['service']=array_intersect_key(schedule_service_display38($p,$today38),array_flip(['attendance','employed','staff_type','from','to','company_code']));$selected=policy_for((int)$p['id'],$today38);$v['effective_policy_id38']=$selected['id'];$v['effective_policy_values38']=$selected['values'];$at=function_exists('presence_at')?presence_at((int)$p['id'],$today38):['subject'=>!empty($v['service']['attendance']),'source'=>'default','rule'=>['mode'=>'off','capture'=>'location','window_minutes'=>15,'times'=>[]],'penalty'=>[]];$v['presence']=['subject'=>$at['subject'],'source'=>$at['source'],'rule'=>$at['rule'],'penalty'=>$at['penalty']??[]];$v['presence_rule']=$at['subject']?($at['rule']+['source'=>$at['source'],'penalty'=>$at['penalty']??[]]):null;$legacy=function_exists('presence_legacy_row')?presence_legacy_row((int)$p['id']):null;$body=$legacy?decode($legacy['body_json']??'{}'):[];$v['presence_legacy']=$legacy?(['date_from'=>$legacy['date_from'],'penalty'=>is_array($body['penalty']??null)?$body['penalty']:null]+presence_setup_from(['presence_setup'=>$body])):null;$out[]=$v;}return $out;
}
function policy_assert32(array $row,array $b,bool $unused=true):array{
 $impact=policy_impact32($row);if(!hash_equals($impact['fingerprint'],(string)($b['fingerprint']??'')))fail('تغيرت السياسة أو السجلات المرتبطة؛ أعد المعاينة.',409);
 if($unused&&$impact['used'])fail('السياسة مستخدمة؛ أنشئ تغييرًا بتاريخ سريان جديد بدل تعديل الماضي أو حذفه.',409);return $impact;
}
function policy_delete32(array $u,array $b):array{
 $deletePair173=null;$peek173=query('SELECT * FROM ep_policies WHERE id=?',[(int)($b['id']??0)])->fetch();if($peek173&&!empty($peek173['user_id'])){policy_access32($u,(int)$peek173['user_id'],'delete');$deletePair173=record_pair_values173(['reason'=>$b['reason']??''],['reason'=>'record_text:policy:reason'],[],$b['_edit170']??null);$b['reason']=$deletePair173['values']['reason'];}
 if(trim((string)($b['reason']??''))==='')fail('سبب تعديل أو حذف السياسة مطلوب.');
 db()->beginTransaction();$row=policy_locked32((int)($b['id']??0));policy_access32($u,(int)($row['user_id']??0),'delete');$impact=policy_assert32($row,$b);
 if(empty($b['confirm_fallback']))fail('أكد مراجعة السياسة البديلة قبل الحذف.');
 if($deletePair173)text_pairs_index170($deletePair173['pairs']);
 query('DELETE FROM ep_policies WHERE id=?',[$row['id']]);audit('policy_delete','policy:'.$row['id'],['before'=>$row,'reason'=>$b['reason'],'impact'=>$impact,'text_pairs173'=>$deletePair173['pairs']??[]]);db()->commit();return ['ok'=>true];
}
function policy_preview32(array $u,int $id):array{
 $row=query('SELECT * FROM ep_policies WHERE id=?',[$id])->fetch();if(!$row)fail('السياسة غير موجودة');policy_access32($u,(int)($row['user_id']??0));$impact=policy_impact32($row);
 $fallback=query('SELECT id,label,user_id,effective_from,effective_to FROM ep_policies WHERE id<>? AND (user_id=? OR user_id IS NULL) ORDER BY effective_from DESC,id DESC',[$id,(int)($row['user_id']??0)])->fetchAll();
 if($u['role']!=='admin'){$ids=permission_scope29($u,$row['user_id']?'policy_staff':'policy_general');$impact['staff']=array_values(array_filter($impact['staff'],fn($p)=>in_array($p['id'],$ids,true)));}
 $values=decode($row['value_json']??'{}');$uid=(int)($row['user_id']??0);$from=(string)($row['effective_from']??'');
 $openFrom=function_exists('presence_form_from')?presence_form_from($row,!empty($impact['used'])):$from;
 $form=function_exists('presence_form_state')?($uid?presence_form_state(presence_legacy_row($uid),$values,$openFrom,presence_subject($uid,$openFrom)):['source'=>'own','setup'=>presence_setup_from($values),'penalty'=>presence_penalty_from($values),'exempt'=>false,'from'=>$openFrom]):null;
 return ['policy'=>$row,'impact'=>$impact,'alternatives'=>$fallback,'presence_form'=>$form];
}

function policy_validate_weekly32(array $p):void{
 if(isset($p['weekly'])){if(count($p['weekly'])!==7)fail('حدد أيام الأسبوع السبعة');foreach($p['weekly']as $day){if(!empty($day['rest']))continue;foreach(['start','end']as $k)if(!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/',$day[$k]??''))fail('وقت غير صالح');if($day['end']<=$day['start'])fail('الانصراف يجب أن يكون بعد الحضور');}}
 if(!empty($p['location_id'])){$sites200=array_values(array_filter(settings()['locations']??[],fn($l)=>($l['id']??'')===$p['location_id']));if(!$sites200||($sites200[0]['active']??true)===false)fail('اختر موقعًا مفعلًا');if(($sites200[0]['place']??'work')==='court')fail('موقع المحكمة لإثبات التواجد فقط، وليس موقع الدوام.');}
}

function policy_context_access32(array $u):void{
 if($u['role']==='admin')return;
 if(($_SESSION['access_mode29']??'personal')!=='team'||(!permission_has($u,'policy_general')&&!permission_has($u,'policy_staff')))fail('غير مسموح',403);
}

function policy_resume33(array $old,string $from,string $to):void {
 // Only resume an original period which actually covered the temporary override.
 if($old['effective_to']&&$old['effective_to']<=$to)return;
 $resume=(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');$uid=(int)($old['user_id']??0);
 $next=query('SELECT effective_from FROM ep_policies WHERE (user_id=? OR (user_id IS NULL AND ?=0)) AND effective_from>=? ORDER BY effective_from,id',[$uid,$uid,$resume])->fetchColumn();
 if($next===$resume)return;$end=$old['effective_to'];
 if($next&&(!$end||$next<=$end))$end=(new DateTimeImmutable($next))->modify('-1 day')->format('Y-m-d');
 policy_conflict32($uid,$resume,$end);
 query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[$uid?:null,$resume,$end,$old['label'],$old['value_json'],utc_now()]);
 audit('policy_resume','policy:'.db()->lastInsertId(),['base_id'=>$old['id'],'from'=>$resume,'to'=>$end]);
}

function policy_conflict_message371(array $row):string {
 $from=(new DateTimeImmutable($row['effective_from']))->format('d/m/Y');$to=empty($row['effective_to'])?'مستمرة':(new DateTimeImmutable($row['effective_to']))->format('d/m/Y');
 return 'فترة السياسة تتعارض مع سياسة أخرى؛ راجع الفترات قبل الحفظ. ['.$from.' — '.$to.']';
}
