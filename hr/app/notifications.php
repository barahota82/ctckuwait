<?php
declare(strict_types=1);
// Uses existing records storage: no schema migration or historical notification rewrite.
require_once __DIR__.'/hr-translation-provider.php';
function notice_pair169(string $title,string $body,string $language):array {
 if(!in_array($language,['ar','en'],true))fail('لغة نص الإشعار غير صالحة.');
 $source=['title'=>$title,'body'=>$body];
 try{$target=hr_translation_batch160($source,'Internal notification title and message',$language==='ar'?'AR':'EN',$language==='ar'?'EN-GB':'AR');}
 catch(Throwable $e){error_log('HR notice translation failed: '.($e instanceof RuntimeException&&preg_match('/^hr_translation_[a-z_]+$/',$e->getMessage())?$e->getMessage():'provider_error'));fail('تعذرت ترجمة الإشعار؛ لم يتم إرساله. النص محفوظ في النافذة، أعد المحاولة.');}
 return ['source_language'=>$language,'source_revision'=>hash('sha256',jencode([$language,$title,$body])),'ar'=>$language==='ar'?$source:$target,'en'=>$language==='en'?$source:$target];
}
function notice_details32(array $row):array {
 $meta=decode($row['notice_meta']??'{}');$title=(string)$row['title'];$body=(string)$row['body'];
 $category='administration';$type='message';$balance=null;$due=null;$screen=null;$record=null;
 if($title==='متابعة رصيد الإجازات'){$category='leave';$type='balance';$screen='leave_overview';if(preg_match('/رصيد الأيام\s+([\d,.]+)/u',$body,$m))$balance=(float)str_replace(',','',$m[1]);}
 elseif($title==='تجديد مستند شركة'){$category='documents';$type='renewal';$screen='corporate_document';if(preg_match('/\d{4}-\d{2}-\d{2}/',$body,$m))$due=$m[0];}
 elseif($title==='حالة عميل جديدة'){$category='customers';$type='customer';$screen='customer_cases';if(preg_match('/M-(\d+)/',$body,$m))$record=(int)$m[1];}
 elseif($title==='اعتماد كشف الراتب'){$category='finance';$type='payroll';$screen='payroll';}
 elseif($title==='إلغاء طلب صرف الإجازات'){$category='finance';$type='leave_cash';$screen='leave_cash';}
 elseif($title==='طلب إثبات تواجد'){$category='attendance';$type='proof';$screen='proof';}
 elseif($title==='قرار مباشرة العمل'){$category='leave';$type='resumption';$screen='resumption';}
 elseif(preg_match('/^(طلب جديد|قرار): (.+)$/u',$title,$m)){
  foreach(modules() as $key=>$module)if($module['label']===$m[2]){$screen=$key;$type=$key;$category=in_array($key,['leave','sick'],true)?'leave':(in_array($key,['external','proof'],true)?'attendance':(in_array($key,['loan','leave_cash','adjustment'],true)?'finance':'administration'));break;}
 }
 if(preg_match('/رقم الإقرار (\d+)/u',$body,$m)){$record=(int)$m[1];$screen='video_receipt';$type='video';}
 $defaults=['category'=>$category,'type'=>$type,'balance'=>$balance,'due'=>$due,'screen'=>$screen,'record_id'=>$record,'priority'=>'normal'];
 return array_replace($defaults,$meta);
}
function notice_manage32(array $u):void {
 permission_require($u,'notifications','create');
 if($u['role']!=='admin'&&($_SESSION['access_mode29']??'personal')!=='team')fail('انتقل إلى عرض الفريق لإرسال الإشعارات.',403);
}
function notice_people32(array $u):array {
 notice_manage32($u);$allowed=permission_scope29($u,'notifications','create');
 $rows=query('SELECT * FROM ep_users WHERE active=1')->fetchAll();service_prefetch($rows);$out=[];
 foreach($rows as $r){if($u['role']!=='admin'&&!in_array((int)$r['id'],$allowed,true))continue;
  $out[]=['id'=>(int)$r['id'],'name'=>$r['name'],'name_en'=>decode($r['profile_json'])['name_en']??'','code'=>$r['role']==='admin'?$r['username']:employee_key($r),'company'=>staff_company30($r),'department'=>$r['department']];}
 usort($out,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return $out;
}
function notice_options32(array $u):array {
 $people=notice_people32($u);$ids=array_column($people,'id');$config=groups_config29();$groups=[];
 foreach($config['groups']??[] as $key=>$group){if(!($group['active']??true))continue;$members=[];if(!empty($group['manual_team'])){foreach((array)($group['team_users']??[])as $id)if(in_array((int)$id,$ids,true))$members[]=(int)$id;}else foreach($config['members']??[] as $id=>$assigned)if(in_array($key,$assigned,true)&&in_array((int)$id,$ids,true))$members[]=(int)$id;$members=array_values(array_unique($members));if($members)$groups[]=['id'=>$key,'name'=>$group['name'],'members'=>$members];}
 return ['people'=>$people,'groups'=>$groups,'companies'=>company_choices30($u,'notifications')];
}
function notice_list32(array $u):array {
 $sent=($_GET['box']??'')==='sent';$args=[];
 if($sent){notice_manage32($u);$where="m.module='notification_meta' AND m.reviewed_by IS NOT NULL";if($u['role']!=='admin'){$where.=' AND m.reviewed_by=?';$args[]=(int)$u['id'];}}
 else{$where='n.user_id=?';$args[]=(int)$u['id'];}
 $summary140=!empty($_GET['summary140'])&&!$sent;if($summary140)$where.=' AND n.read_at IS NULL';
 $rows=query("SELECT n.*,m.body_json AS notice_meta,m.file_id AS attachment_id,m.reviewed_by AS sender_id,p.name AS recipient_name,p.profile_json AS recipient_profile FROM ep_notifications n LEFT JOIN ep_records m ON m.module='notification_meta' AND m.title=CONCAT('notice:',n.id) JOIN ep_users p ON p.id=n.user_id WHERE $where ORDER BY n.id DESC",$args)->fetchAll();
 $allowed=$sent?array_column(notice_people32($u),'id'):[];$out=[];
 $legacyPeople=[];foreach(query("SELECT id,name FROM ep_users WHERE role<>'admin'")->fetchAll()as $person)$legacyPeople[$person['name']][]=(int)$person['id'];
 foreach($rows as $row){if($sent&&$u['role']!=='admin'&&!in_array((int)$row['user_id'],$allowed,true))continue;$row['meta']=notice_details32($row);if($row['meta']['type']==='balance'&&empty($row['meta']['employee_id'])){$name=explode(' — رصيد الأيام ',(string)$row['body'])[0];$matches=$legacyPeople[$name]??[];if(count($matches)===1)$row['meta']['employee_id']=$matches[0];}if(empty($row['meta']['employee_id'])&&in_array($row['meta']['type'],['payroll','leave_cash','resumption'],true))$row['meta']['employee_id']=(int)$row['user_id'];
 if($row['meta']['type']==='payroll'&&empty($row['meta']['month'])&&preg_match('/الشهر (\d{4}-\d{2})/',(string)$row['body'],$monthMatch))$row['meta']['month']=$monthMatch[1];
 if($row['meta']['type']==='renewal'&&empty($row['meta']['record_id'])){if(!isset($legacyDocuments)){$legacyDocuments=[];foreach(query("SELECT id,module,title,body_json,date_to FROM ep_records WHERE module IN ('corporate_document','company_file')")->fetchAll()as $doc){$v=decode($doc['body_json']);$legacyDocuments[($v['name']??$doc['title']).' — '.$doc['date_to']][]=$doc;}}$matches=$legacyDocuments[$row['body']]??[];if(count($matches)===1){$row['meta']['record_id']=(int)$matches[0]['id'];$row['meta']['screen']=$matches[0]['module']==='company_file'?'v28_files':'v28_documents';}}
 if(personal_mode34($u)&&!personal_notice34($row,$u))continue;
 if(!personal_mode34($u)&&!empty($GLOBALS['EP_IDENTITY32']['has_employee'])&&personal_notice34($row,$u)&&!$sent)continue;
 if(personal_mode34($u)){if(($row['meta']['screen']??'')==='leave_overview')$row['meta']['screen']='leave';$dest=($row['meta']['screen']??'')==='video_receipt'?'video_receipts':($row['meta']['screen']??'');if($dest&&!personal_has34($u,$dest)){$row['meta']['screen']=null;$row['meta']['record_id']=null;}}
 $row['recipient_name_en']=decode($row['recipient_profile'])['name_en']??'';unset($row['notice_meta'],$row['recipient_profile']);$out[]=$row;}
 if($summary140)return ['unread'=>count($out)];
 require_once __DIR__.'/notice-names.php';return notice_names142($out);
}
function notice_send32(array $u,array $b):array {
 notice_manage32($u);$options=notice_options32($u);$people=$options['people'];$mode=(string)($b['audience']??'selected');$wanted=$b['recipients']??[];if(is_string($wanted))$wanted=decode($wanted);if(!is_array($wanted))fail('اختر المستلمين.');$wanted=array_map('intval',$wanted);
 if(!in_array($mode,['all','selected','company','department','group'],true))fail('اختر المستلمين.');
 $value=trim((string)($b['audience_value']??''));if(in_array($mode,['company','department','group'],true)&&$value==='')fail('اختر المستلمين.');
 $group=[];if($mode==='group'){foreach($options['groups'] as $g)if((string)$g['id']===$value)$group=$g['members'];}
 if($mode==='selected'&&array_diff($wanted,array_column($people,'id')))fail('الموظف خارج الصلاحيات.',403);
 $targets=array_values(array_filter($people,fn($p)=>match($mode){'all'=>true,'selected'=>in_array($p['id'],$wanted,true),'company'=>$p['company']===$value,'department'=>$p['department']===$value,'group'=>in_array($p['id'],$group,true)}));
 if(!$targets)fail('اختر مستلمًا واحدًا على الأقل.');
 $title=trim((string)($b['title']??''));$body=trim((string)($b['body']??''));if(!$title||!$body||mb_strlen($title)>180||mb_strlen($body)>5000)fail('عنوان ونص الإشعار مطلوبان.');
 $type=(string)($b['type']??'message');if(!in_array($type,['announcement','circular','message','decision'],true))fail('نوع الإشعار غير صالح.');
 $pair=notice_pair169($title,$body,(string)($b['source_language169']??'ar'));
 $meta=['text_pair169'=>$pair,'category'=>'administration','type'=>$type,'priority'=>($b['priority']??'')==='important'?'important':'normal','batch'=>bin2hex(random_bytes(12)),'screen'=>null,'record_id'=>null,'sender'=>(int)$u['id']];
 db()->beginTransaction();$file=null;if(!empty($_FILES['file']['tmp_name']))$file=upload_file($_FILES['file'],(int)$u['id']);$meta['file_id']=$file;
 foreach($targets as $person)notice($person['id'],$title,$body,!empty($b['require_ack']),$meta);
 audit('notice_send','notifications',['targets'=>array_column($targets,'id'),'batch'=>$meta['batch'],'type'=>$type]);db()->commit();return ['ok'=>true,'count'=>count($targets),'text_pair169'=>$pair];
}
function notice_read32(int $uid,array $b):array {
 $actor=current_user();if(!personal_mode34($actor)&&!empty($GLOBALS['EP_IDENTITY32']['has_employee'])){$visible=query("SELECT n.*,m.body_json AS notice_meta FROM ep_notifications n LEFT JOIN ep_records m ON m.module='notification_meta' AND m.title=CONCAT('notice:',n.id) WHERE n.id=? AND n.user_id=?",[(int)($b['id']??0),$uid])->fetch();if($visible&&personal_notice34($visible,$actor))fail('هذا الإشعار متاح في ملفك الشخصي.',403);}
 if(personal_mode34($actor)){personal_require34($actor,'notifications','respond');personal_notice_row34((int)($b['id']??0),$actor);}
 $id=(int)($b['id']??0);$row=query('SELECT id FROM ep_notifications WHERE id=? AND user_id=?',[$id,$uid])->fetch();if(!$row)fail('الإشعار غير موجود.',404);
 if(!empty($b['unread'])){query('UPDATE ep_notifications SET read_at=NULL WHERE id=? AND user_id=?',[$id,$uid]);audit('notice_unread','notification:'.$id);}
 else{query('UPDATE ep_notifications SET read_at=COALESCE(read_at,?),acknowledged_at=CASE WHEN ?=1 AND require_ack=1 THEN COALESCE(acknowledged_at,?) ELSE acknowledged_at END WHERE id=? AND user_id=?',[utc_now(),empty($b['ack'])?0:1,utc_now(),$id,$uid]);audit(!empty($b['ack'])?'notice_ack':'notice_read','notification:'.$id);}
 return ['ok'=>true];
}
