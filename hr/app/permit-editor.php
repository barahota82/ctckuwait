<?php
declare(strict_types=1);
require_once __DIR__.'/permit-fields.php';
require_once __DIR__.'/text-pairs.php';
require_once __DIR__.'/permit-renewal.php';
// Dedicated permit workflow. Shared employee/bank readers retain their existing schema.
function permit_revision148(array $r):string {return hash('sha256',jencode([$r['id'],$r['date_from'],$r['date_to'],$r['body_json'],$r['file_id']??null,$r['updated_at']??'']));}
function permit_scope148(array $actor,string $op='view'):array {if(personal_mode34($actor))fail('هذه الشاشة ضمن مساحة العمل.',403);permission_require($actor,'work_permit',$op);return permission_scope29($actor,'work_permit',$op);}
function permit_transfer148(array $staff,string $today):array {
 $context=permit_context89($staff,$today);$periods=$GLOBALS['EP_SERVICE_CACHE'][(int)$staff['id']]['periods']??[];$start=$staff['hired_on']??'';
 foreach($periods as $p)if($p['date_from']<=$today&&$p['date_from']>$start)$start=$p['date_from'];
 $t=$context['transfer'];if(($t['date']??'')<$start)$t=['status'=>'pending','date'=>null];return ['ended'=>$context['ended'],'start'=>$start,'transfer'=>$t,'transferred'=>($t['status']??'pending')==='transferred'&&!empty($t['date'])&&$t['date']<=$today];
}
function permit_status148(array $r,string $today):string {$b=decode($r['body_json']);if(array_key_exists('confirmed_active',$b)&&!$b['confirmed_active'])return 'unconfirmed';if(empty($b['effective_from'])&&!empty($b['incomplete_dates']))return 'incomplete';if($r['date_from']>$today)return 'future';if(!$r['date_to'])return 'incomplete';return $r['date_to']<$today?'expired':'active';}
function permit_record148(array $r):array {$r['revision148']=permit_revision148($r);$r['state148']=permit_status148($r,local_time()->format('Y-m-d'));return $r;}
function permit_transfer_revision148(array $staff):string{return hash('sha256',jencode(decode($staff['profile_json'])['sponsorship_transfer89']??null));}
function permit_save_transfer148(array $actor,array $staff,array $transfer,string $revision,string $today):void{
 if(!in_array((int)$staff['id'],permit_scope148($actor,'edit'),true))fail('لا توجد صلاحية تعديل النقل.',403);if(!hash_equals(permit_transfer_revision148($staff),$revision))fail('تغيرت حالة نقل الكفالة؛ أعد تحميل بيانات الموظف.',409);$context=permit_transfer148($staff,$today);if(!$context['ended'])fail('حالة النقل مخصصة للمنتهية خدماتهم.');$status=(string)($transfer['status']??'');if(!in_array($status,['pending','transferred'],true))fail('حدد حالة نقل الكفالة.');$date=$status==='transferred'?valid_date($transfer['date']??null):null;if($status==='transferred'&&(!$date||$date>$today||$date<$context['start']))fail('تاريخ النقل يجب أن يخص فترة الخدمة الحالية ولا يكون مستقبليًا.');$profile=decode($staff['profile_json']);$before=$profile['sponsorship_transfer89']??null;$profile['sponsorship_transfer89']=['status'=>$status,'date'=>$date];query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile),$staff['id']]);audit('work_permit_transfer','user:'.$staff['id'],['before'=>$before,'after'=>$profile['sponsorship_transfer89']]);
}
function permit_action148(string $action,array $actor,array $request):array {
 $ids=permit_scope148($actor,$action==='permit148_save'?(in_array((string)($request['mode']??''),['correct','transfer'],true)?'edit':'create'):'view');$today=local_time()->format('Y-m-d');
 if($action==='permit148_info'){
  $people=[];$records=[];foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$people=array_merge($people,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());$records=array_merge($records,query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module='work_permit' AND status='approved' ORDER BY date_from DESC,id DESC",$chunk)->fetchAll());}
  foreach($people as $person){$GLOBALS['EMP_RESIDENCE134'][(int)$person['id']]=[];$GLOBALS['EMP_RESIDENCE_FILES134'][(int)$person['id']]=[];}foreach($records as $record)$GLOBALS['EMP_RESIDENCE134'][(int)$record['user_id']][]=$record;foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module='residence_file' AND status='approved' ORDER BY date_from DESC,id DESC",$chunk)->fetchAll()as $legacy)$GLOBALS['EMP_RESIDENCE_FILES134'][(int)$legacy['user_id']][]=$legacy;}
  $records=array_values(array_filter($records,fn($r)=>record_company_allowed30($actor,'work_permit',$r)));service_prefetch($people);$grouped=[];foreach($records as $r)$grouped[(int)$r['user_id']][]=$r;$rows=[];
  foreach($people as $p){if(!empty(decode($p['profile_json'])['permit_exempt150']))continue;$uid=(int)$p['id'];$history=$grouped[$uid]??[];$context=permit_transfer148($p,$today);$last=null;$first='';$stamp='';
   foreach($history as $r){$v=decode($r['body_json']);if(!$last)$last=$r;$at=(string)($v['first_permit_recorded_at131']??'');if(array_key_exists('first_permit_on130',$v)&&($at>$stamp||($stamp===''&&$first===''))){$first=$v['first_permit_on130'];$stamp=$at;}}
   $selected=$last;$state=$selected?permit_status148($selected,$today):'none';$tab=$context['ended']?($context['transferred']?'ended_transferred':'ended_company'):($state==='expired'?'expired':'active');
   $rows[]=['id'=>$uid,'code'=>employee_key($p),'name'=>$p['name'],'name_en'=>decode($p['profile_json'])['name_en']??'','hired_on'=>$p['hired_on'],'first148'=>$first,'tab148'=>$tab,'context148'=>$context,'transfer_revision148'=>permit_transfer_revision148($p),'current'=>$selected?permit_record148($selected):null,'has_permit150'=>!empty($GLOBALS['EMP_RESIDENCE134'][$uid]),'renewal148'=>$last?permit_record148($last):null,'count148'=>count($history),'record_ids148'=>array_map('intval',array_column($history,'id'))];
  }
  usort($rows,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));$companies=company_choices30($actor,'work_permit',false);$files=query("SELECT id,body_json FROM ep_records WHERE module='company_file' AND status<>'archived' ORDER BY id")->fetchAll();$files=array_values(array_filter($files,fn($r)=>company_allowed30($actor,'work_permit',(string)(decode($r['body_json'])['company_code']??''))));foreach($files as &$f)$f['body_json']=jencode(array_intersect_key(decode($f['body_json']),array_flip(['company_code','type','file_number'])));unset($f);
  return ['today150'=>$today,'people'=>$rows,'companies'=>$companies,'files'=>$files,'can_create'=>$actor['role']==='admin'||permission_has($actor,'work_permit','create'),'can_edit'=>$actor['role']==='admin'||permission_has($actor,'work_permit','edit')];
 }
 $uid=(int)($request['user_id']??$_GET['user_id']??0);if(!in_array($uid,$ids,true))fail('الموظف خارج نطاق الصلاحية.',403);
 if($action==='permit148_history'){ $profile150=query('SELECT profile_json FROM ep_users WHERE id=?',[$uid])->fetchColumn();if(!empty(decode($profile150?:'{}')['permit_exempt150']))fail('الموظف مستثنى من إذن العمل.',403);}
 if($action==='permit148_history')return ['records'=>array_map('permit_record148',array_values(array_filter(query("SELECT * FROM ep_records WHERE module='work_permit' AND user_id=? AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetchAll(),fn($r)=>record_company_allowed30($actor,'work_permit',$r))))];
 if($action!=='permit148_save')fail('عملية غير صالحة.');
 $mode=(string)($request['mode']??'');if(!in_array($mode,['new','renew','correct','transfer'],true))fail('حدد إجراء الإذن.');
 $token=(string)($request['request_id']??'');if(!preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$token))fail('معرف الحفظ غير صالح.');
 $fingerprint=hash('sha256',jencode($request).(!empty($_FILES['document']['tmp_name'])?hash_file('sha256',$_FILES['document']['tmp_name']):''));
 // Replays are checked against the original human request, before any provider call.
 foreach(query("SELECT detail_json FROM ep_audit WHERE action='permit_request148' AND subject=?",['permit:'.$uid.':'.$token])->fetchAll()as $op170){$prior170=decode($op170['detail_json']);if(($prior170['hash']??'')!==$fingerprint)fail('معرف حفظ مستخدم لبيانات مختلفة.',409);return ['id'=>$prior170['permit_id'],'replayed'=>true];}
 $textPairs170=text_permit_prepare170($request,$actor,$ids);
 $GLOBALS['EDITOR_FILES143']=[];db()->beginTransaction();$staff=query('SELECT * FROM ep_users WITH (UPDLOCK, HOLDLOCK) WHERE id=?',[$uid])->fetch();if(!$staff||$staff['role']==='admin')fail('اختر موظفًا.');if(!empty(decode($staff['profile_json'])['permit_exempt150']))fail('الموظف مستثنى من إذن العمل.',403);
 foreach(query("SELECT detail_json FROM ep_audit WHERE action='permit_request148' AND subject=?",['permit:'.$uid.':'.$token])->fetchAll()as $op){$v=decode($op['detail_json']);{if(($v['hash']??'')!==$fingerprint)fail('معرف حفظ مستخدم لبيانات مختلفة.',409);db()->commit();unset($GLOBALS['EDITOR_FILES143']);return ['id'=>$v['permit_id'],'replayed'=>true];}}
 if($mode==='transfer'){permit_save_transfer148($actor,$staff,$request['transfer']??[],(string)($request['transfer_revision']??''),$today);audit('permit_request148','permit:'.$uid.':'.$token,['hash'=>$fingerprint,'permit_id'=>(int)($request['id']??0),'mode'=>$mode]);db()->commit();unset($GLOBALS['EDITOR_FILES143']);return ['id'=>(int)($request['id']??0)];}
 if($mode==='new'&&query("SELECT id FROM ep_records WHERE user_id=? AND module='work_permit' AND status='approved'",[$uid])->fetch())fail('للموظف إذن عمل مسجل؛ استخدم التجديد.',409);
 $old=null;$id=(int)($request['id']??0);if($mode!=='new'){$old=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='work_permit' AND status='approved'",[$id,$uid])->fetch();if($old&&!record_company_allowed30($actor,'work_permit',$old,$mode==='correct'?'edit':'view'))fail('الإذن خارج نطاق الصلاحية.',403);if(!$old||!hash_equals(permit_revision148($old),(string)($request['revision']??'')))fail('تغيرت بيانات الإذن؛ أعد تحميل بيانات الموظف قبل الحفظ.',409);}
 $v=$request['values']??[];if(!is_array($v))fail('بيانات الإذن غير صالحة.');$from=valid_date($request['from']??null);$to=valid_date($request['to']??null);[$to,$extra155]=permit_extra155($mode,$old,$request,$from);if(!$from||!$to||$to<$from)fail('بداية ونهاية سريان صحيحتان مطلوبتان.');
 if(!service_state($staff,$from)['employed'])fail('التاريخ خارج فترة الخدمة.');$company=(string)($v['company_code']??'');if(!company_allowed30($actor,'work_permit',$company,$mode==='correct'?'edit':'create')||!company_by_code($company))fail('الشركة خارج نطاق الصلاحية.',403);
 $fileId=(int)($v['file_id']??0);$file=query("SELECT body_json FROM ep_records WHERE id=? AND module='company_file' AND status<>'archived'",[$fileId])->fetch();if(!$file||decode($file['body_json'])['company_code']!==$company)fail('اختر ملفًا تابعًا لشركة الإقامة.');
 if(!is_numeric($v['salary']??null)||!is_finite((float)$v['salary'])||(float)$v['salary']<0||(float)$v['salary']>1000000000)fail('راتب إذن العمل غير صالح.');
 $prior=$old?decode($old['body_json']):[];$body=array_replace($prior,['company_code'=>$company,'file_id'=>$fileId,'salary'=>round((float)$v['salary'],3),'occupation'=>trim((string)($v['occupation']??'')),'reason'=>trim((string)($v['reason']??'')),'confirmed_active'=>($v['confirmed_active']??false)===true,'effective_from'=>$from,'incomplete_dates'=>false],$extra155);
 $body=text_pairs_attach170($body,$textPairs170,'work_permit');
 $first=valid_date($v['first_permit_on130']??null);if($first&&$first>$from)fail('تاريخ أول إذن لا يتجاوز بداية هذا الإذن.');$body['first_permit_on130']=$first??'';if(!$old||($prior['first_permit_on130']??'')!==($first??''))$body['first_permit_recorded_at131']=utc_now();
 if($mode==='renew'){
  $p=decode($old['body_json']);if((!empty($p['incomplete_dates'])&&empty($p['effective_from']))||empty($old['date_from'])||empty($old['date_to']))fail('استكمل بداية ونهاية الإذن السابق قبل التجديد.');
  $latest156=query("SELECT TOP (1) id FROM ep_records WHERE user_id=? AND module='work_permit' AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetchColumn();if((int)$latest156!==$id)fail('آخر إذن مسجل تغيّر؛ أعد تحميل بيانات الموظف قبل التجديد.',409);
  if($from<=$old['date_from']||$from<(new DateTimeImmutable($old['date_to']))->modify('-90 days')->format('Y-m-d'))fail('يسمح ببدء التجديد قبل انتهاء الإذن السابق بـ90 يومًا كحد أقصى.');
  $body['renewed_from156']=$id;
 }
 // A manual correction of the latest legacy permit may establish its missing renewal link.
 // Use the stored chronological predecessor, never infer an arbitrary overlapping document.
 if($mode==='correct'&&empty($prior['renewed_from156'])&&$from!==$old['date_from']){
  $timeline157=query("SELECT * FROM ep_records WHERE module='work_permit' AND user_id=? AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetchAll();
  if(count($timeline157)>1&&(int)$timeline157[0]['id']===$id){
   $parent157=$timeline157[1];$parentBody157=decode($parent157['body_json']);
   if(!empty($parent157['date_to'])&&!empty($parent157['date_from'])&&empty($parentBody157['incomplete_dates'])&&record_company_allowed30($actor,'work_permit',$parent157,'view')&&$from<=$parent157['date_to']&&permit_renewal_pair156(['id'=>$id,'date_from'=>$from,'date_to'=>$to,'body_json'=>jencode(['renewed_from156'=>(int)$parent157['id']])],$parent157))$body['renewed_from156']=(int)$parent157['id'];
  }
 }
 if($mode!=='correct'||$from!==$old['date_from']||$to!==$old['date_to']||(!(bool)($prior['confirmed_active']??true)&&$body['confirmed_active']))foreach(query("SELECT * FROM ep_records WHERE module='work_permit' AND user_id=? AND status='approved'",[$uid])->fetchAll()as $other){if($mode==='correct'&&(int)$other['id']===$id)continue;if($other['date_from']<=$to&&($other['date_to']??'9999-12-31')>=$from&&!permit_renewal_pair156(['id'=>$mode==='correct'?$id:0,'date_from'=>$from,'date_to'=>$to,'body_json'=>jencode($body)],$other))fail('فترة الإذن تتداخل مع إذن غير مرتبط بهذا التجديد.');}
 $financial=$mode!=='correct'||$from!==$old['date_from']||$to!==$old['date_to'];if(round((float)($prior['salary']??0),3)!==$body['salary']||(string)($prior['company_code']??'')!==$company||(int)($prior['file_id']??0)!==$fileId||(bool)($prior['confirmed_active']??true)!==$body['confirmed_active'])$financial=true;
 if($financial)financial_invalidate($uid,$mode==='correct'?min($from,$old['date_from']):$from);
 $transfer=$request['transfer']??null;if($transfer!==null)permit_save_transfer148($actor,$staff,$transfer,(string)($request['transfer_revision']??''),$today);
 if($mode==='correct'){query('UPDATE ep_records SET date_from=?,date_to=?,body_json=?,updated_at=? WHERE id=?',[$from,$to,jencode($body),utc_now(),$id]);}else{$id=v26_insert('work_permit',$uid,$from,$to,$body);}
 if(!empty($_FILES['document']['tmp_name'])){$fid=upload_file($_FILES['document'],$uid);query('UPDATE ep_records SET file_id=? WHERE id=?',[$fid,$id]);}
 text_pairs_index170($textPairs170);
 audit('permit_request148','permit:'.$uid.':'.$token,['hash'=>$fingerprint,'permit_id'=>$id,'mode'=>$mode]);audit('work_permit_'.$mode,'record:'.$id,['before'=>$old,'after'=>$body,'record_after150'=>query('SELECT * FROM ep_records WHERE id=?',[$id])->fetch(),'performed_by150'=>(int)($_SESSION['uid']??$actor['id']),'effective_actor150'=>(int)$actor['id'],'changed_at150'=>utc_now()]);db()->commit();unset($GLOBALS['EDITOR_FILES143'],$GLOBALS['EMP_RESIDENCE134'][$uid],$GLOBALS['EP_SERVICE_CACHE'][$uid]);return ['id'=>$id];
}
