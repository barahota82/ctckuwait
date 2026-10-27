<?php
declare(strict_types=1);
// Personal permissions never grant team authority. All targets are the authenticated employee.
function personal_mode34(array $u):bool{return $u['role']!=='admin'&&($_SESSION['access_mode29']??'personal')==='personal';}
function personal_catalog34():array{
 $labels=['dashboard'=>'ملخصي','notifications'=>'إشعاراتي','users'=>'بياناتي الأساسية','profile_service'=>'فترات خدمتي','profile_salary'=>'تاريخ راتبي','profile_bank'=>'بيانات بنكي','profile_permit'=>'إذن عملي','leave_finance'=>'رصيد إجازاتي المالي وتسوياته','profile_leave'=>'إجازاتي السابقة','profile_documents'=>'مستنداتي بالملف','attendance'=>'بصمة الدوام','report'=>'سجل دوامي','leave'=>'إجازاتي','sick'=>'مرضياتي','external'=>'طلب انصراف من الخارج','request'=>'طلباتي','loan'=>'طلبات السلف ومديونيتي','resumption'=>'مباشرة عملي','payroll'=>'كشوف راتبي','commissions'=>'عمولاتي','custody'=>'عهدي','document'=>'مستنداتي وعقودي','discipline'=>'القرارات الموجهة لي','evaluation'=>'تقييماتي','employment'=>'إجراءات خدمتي','proof'=>'طلبات إثبات تواجدي','video_receipts'=>'إقراراتي بالفيديو','personal_history'=>'سجلي السابق'];
 $out=[];foreach($labels as $k=>$label){$ops=['view'];if(in_array($k,['attendance','leave','sick','external','request','loan','resumption','video_receipts'],true))$ops[]='create';if(in_array($k,['notifications','discipline','proof','leave','sick','external','request','loan','custody','document','evaluation','employment'],true))$ops[]='respond';if(in_array($k,['report','payroll'],true))$ops[]='export';$out[$k]=['label'=>$label,'actions'=>$ops];}return $out;
}
function personal_defaults34():array{$out=[];foreach(personal_catalog34()as $k=>$m)$out[$k]=['allow'=>$m['actions'],'deny'=>[]];return $out;}
function personal_effective34(array $u):array{
 $cfg=groups_config29();$sets=[];foreach($cfg['groups']??[]as $id=>$g){if(!($g['active']??true)||!isset($g['personal_grants']))continue;$ids=array_map('intval',$g['personal_users']??[]);if(in_array((int)$u['id'],$ids,true))$sets[]=$g['personal_grants'];}
 if(!$sets)$sets=[$cfg['personal_default34']??personal_defaults34()];
 $out=[];foreach(personal_catalog34()as $k=>$m){$allow=[];$deny=[];foreach($sets as $rules){$allow=array_merge($allow,$rules[$k]['allow']??[]);$deny=array_merge($deny,$rules[$k]['deny']??[]);}$ops=array_values(array_diff(array_intersect(array_unique($allow),$m['actions']),$deny));$out[$k]=in_array('view',$ops,true)?$ops:[];}return $out;
}
function personal_has34(array $u,string $screen,string $op='view'):bool{return in_array($op,personal_effective34($u)[$screen]??[],true);}
function personal_require34(array $u,string $screen,string $op='view'):void{if(!personal_has34($u,$screen,$op))fail('هذه العملية غير متاحة في ملفك الشخصي.',403);}
function personal_clean34(mixed $input):array{
 if(!is_array($input))fail('صلاحيات الملف الشخصي غير صالحة.');$catalog=personal_catalog34();$out=[];
 foreach($input as $key=>$rule){if(!isset($catalog[$key])||!is_array($rule))fail('شاشة شخصية غير صالحة.');$a=$rule['allow']??[];$d=$rule['deny']??[];if(!is_array($a)||!is_array($d)||array_diff([...$a,...$d],$catalog[$key]['actions'])||array_intersect($a,$d)||($a&&!in_array('view',$a,true)))fail('عمليات الملف الشخصي غير صالحة.');$out[$key]=['allow'=>array_values(array_unique($a)),'deny'=>array_values(array_unique($d))];}return $out;
}
function personal_gate34(string $action,array $u,array $b):void{
 if(!personal_mode34($u))return;
 if($action==='payroll_list'&&($_GET['tab35']??'')==='review')fail('هذه العملية تخص مساحة العمل.',403);
 if(in_array($action,['logout','change_password','language_select','mode29','identity32_mode','user_choices29'],true))return;
 if(in_array($action,['presence_send','presence_ticket'],true)){require_once __DIR__.'/presence.php';presence_personal_create($u);return;}
 $map=['punch127_info'=>['attendance','view'],'incentive114_info'=>['payroll','view'],'punch39_choices'=>['request','create'],'pay35_info'=>['commissions','view'],'dashboard'=>['dashboard','view'],'users'=>['users','view'],'profile29'=>['users','view'],'personal_history'=>['personal_history','view'],'notifications'=>['notifications','view'],'notice_read'=>['notifications','respond'],'capture_ticket'=>['attendance','create'],'event'=>['attendance','create'],'tracking'=>['attendance','create'],'report'=>['report','view'],'presence_log'=>['report','view'],'leave_balance'=>['leave','view'],'sick_summary'=>['sick','view'],'payroll_list'=>['payroll','view'],'commission_info'=>['commissions','view'],'v28_my_debt'=>['loan','view'],'v26_video_list'=>['video_receipts','view'],'v26_video_detail'=>['video_receipts','view'],'v26_video_ticket'=>['video_receipts','create'],'v26_video_upload'=>['video_receipts','create'],'v26_video_objection'=>['video_receipts','create'],'v26_resumption_resubmit'=>['resumption','create']];
 $map+=['permission_cancel_request199'=>['request','create'],'permissions182'=>['request','view'],'permission_get182'=>['request','view'],'permission_request183'=>['request','create'],'permission_punch182'=>['attendance','create'],'evaluation_parents185'=>['evaluation','view']];
 if(in_array($action,['list','record_save'],true))$map[$action]=[(string)($b['module']??$_GET['module']??''),$action==='list'?'view':'create'];
 if(in_array($action,['v26_info','v26_save'],true)&&($b['kind']??$_GET['kind']??'')==='resumption')$map[$action]=['resumption',$action==='v26_info'?'view':'create'];
 if(in_array($action,['record_reply','record_resubmit','record_cancel199'],true)){$row=query('SELECT user_id,module FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetch();if(!$row||(int)$row['user_id']!==(int)$u['id'])fail('السجل غير متاح.',403);$map[$action]=[$row['module'],$action==='record_reply'?'respond':'create'];}
 if(!isset($map[$action]))fail('هذه العملية تخص مساحة العمل.',403);
 foreach(['user_id','employee_id']as $field)if(isset($b[$field])||isset($_GET[$field])){if((int)($b[$field]??$_GET[$field])!==(int)$u['id'])fail('الملف الشخصي يتيح بياناتك فقط.',403);}
 if(in_array($action,['v26_video_detail','v26_video_ticket','v26_video_upload','v26_video_objection','v26_resumption_resubmit'],true)){$record=query('SELECT user_id,module FROM ep_records WHERE id=?',[(int)($b['id']??$_GET['id']??0)])->fetch();$expected=$action==='v26_resumption_resubmit'?'resumption':'video_receipt';if(!$record||(int)$record['user_id']!==(int)$u['id']||$record['module']!==$expected)fail('السجل غير متاح.',403);}
 if($action==='notifications'&&in_array($_GET['box']??'',['sent','options'],true))fail('الإرسال الإداري غير متاح في الملف الشخصي.',403);
 if($action==='tracking'&&!empty($b['proof_id']))$map[$action]=['proof','respond'];
 if($action==='list'&&($_GET['module']??'')==='leave'&&($_GET['purpose34']??'')==='resumption')$map[$action]=['resumption','view'];
 personal_require34($u,...$map[$action]);
}
function personal_public_user34(array $u,array $actor):array{
 // Bootstrap and user choices must not bypass profile-tab controls.
 if(!personal_mode34($actor))return $u;
 if(!personal_has34($actor,'profile_salary'))unset($u['salary']);
 $profile=$u['profile']??[];
 if(!personal_has34($actor,'profile_bank'))foreach(['bank_name','bank_account','iban','bank_salary','bank_effective_from']as $key)unset($profile[$key]);
 if(!personal_has34($actor,'profile_salary'))foreach(['salary','basic_salary','fixed_salary']as $key)unset($profile[$key]);
 if(!personal_has34($actor,'profile_service')&&isset($u['service']))$u['service']=array_intersect_key($u['service'],array_flip(['employed','attendance','from','department','job_title','staff_type','company_code']));
 $u['profile']=array_intersect_key($profile,array_flip(['name_en','phone','email','nationality','address','company_code','residence_company_code','must_change_password','bank_name','bank_account','iban','bank_salary','bank_effective_from','fixed_salary']));return $u;
}
function personal_result34(string $action,array $u,mixed $result):mixed{
 if(!personal_mode34($u))return $result;
 if($action==='list'&&($_GET['module']??'')==='leave'&&($_GET['purpose34']??'')==='resumption')return array_map(fn($row)=>array_intersect_key($row,array_flip(['id','user_id','title','date_from','date_to','status'])),array_values(array_filter($result,fn($row)=>$row['status']==='approved')));
 if($action==='payroll_list')$result=array_values(array_filter($result,fn($r)=>(int)($r['user_id']??0)===(int)$u['id']&&$r['status']==='approved'));
 if($action==='user_choices29')return array_values(array_filter($result,fn($row)=>(int)$row['id']===(int)$u['id']));
 if($action==='users')return array_map(fn($row)=>personal_public_user34($row,$u),$result);
 if($action==='dashboard'){if(!personal_has34($u,'report'))$result['staff']=[];if(!personal_has34($u,'document'))$result['expiring']=[];$result['stale']=[];}
 if($action==='leave_balance'){if(!personal_has34($u,'leave_finance'))foreach(array_keys($result)as $k)if(str_contains($k,'cash')||str_contains($k,'valuation')||str_contains($k,'salary'))unset($result[$k]);if(!personal_has34($u,'profile_salary'))unset($result['valuation_salary'],$result['salary_effective_from']);}
 if($action==='personal_history')$result=array_values(array_filter($result,fn($r)=>personal_has34($u,$r['kind']==='salary_history'?'profile_salary':'profile_leave')&&($r['kind']!=='leave_settlement'||personal_has34($u,'leave_finance'))));
 if($action==='personal_history'&&!personal_has34($u,'profile_salary'))foreach($result as &$row){$body=decode($row['body_json']);unset($body['amount'],$body['_valuation']);$row['body_json']=jencode($body);}unset($row);
 return $result;
}
function personal_notice34(array $row,array $u):bool{
 $m=$row['meta']??notice_details32($row);$screen=(string)($m['screen']??'');$uid=(int)$u['id'];
 if(str_starts_with((string)$row['title'],'طلب جديد:')||in_array($screen,['customer_cases','corporate_document','company_file','v28_documents','v28_files','tracking'],true))return false;
 if(!empty($m['employee_id']))return (int)$m['employee_id']===$uid;
 if(!empty($m['record_id'])){$record=query('SELECT user_id,module FROM ep_records WHERE id=?',[(int)$m['record_id']])->fetch();return $record&&(int)$record['user_id']===$uid&&!in_array($record['module'],['company_file','corporate_document','attachment_archive'],true);}
 if(($m['type']??'')==='balance'){
  $name=explode(' — رصيد الأيام ',(string)$row['body'])[0];$ids=query("SELECT id FROM ep_users WHERE name=? AND role<>'admin'",[$name])->fetchAll(PDO::FETCH_COLUMN);return count($ids)===1&&(int)$ids[0]===$uid;
 }
 return $screen===''||in_array($m['type']??'',['payroll','leave_cash','resumption'],true);
}
function personal_notice_row34(int $id,array $u):array{
 $row=query("SELECT n.*,m.body_json AS notice_meta FROM ep_notifications n LEFT JOIN ep_records m ON m.module='notification_meta' AND m.title=CONCAT('notice:',n.id) WHERE n.id=? AND n.user_id=?",[$id,$u['id']])->fetch();
 if(!$row||!personal_notice34($row,$u))fail('الإشعار غير متاح في الملف الشخصي.',403);return $row;
}
function personal_file34(array $u,int $id):void{
 require_once __DIR__.'/notifications.php';
 $rows=query('SELECT * FROM ep_records WHERE file_id=?',[$id])->fetchAll();
 foreach($rows as $row){if((int)$row['user_id']!==(int)$u['id'])continue;$module=$row['module'];
  if($module==='notification_meta'){if(!personal_has34($u,'notifications'))continue;$nid=(int)substr($row['title'],7);personal_notice_row34($nid,$u);return;}
  if(in_array($module,['discipline','adjustment'],true)&&$row['status']!=='approved')continue;
  if($module==='video_receipt'&&$row['status']==='draft')continue;
  $key=$module==='video_receipt'?'video_receipts':$module;
  if(personal_has34($u,$key))return;
  $tabs=['document'=>'profile_documents','work_permit'=>'profile_permit','leave'=>'profile_leave'];if(isset($tabs[$module])&&personal_has34($u,'users')&&personal_has34($u,$tabs[$module]))return;
 }
 if(personal_has34($u,'request')&&query("SELECT id FROM ep_records WHERE module='exit_permission194' AND user_id=? AND (JSON_VALUE(body_json,'$.exit_file_id')=? OR JSON_VALUE(body_json,'$.return_file_id')=?)",[$u['id'],(string)$id,(string)$id])->fetch())return;
 if(personal_has34($u,'report')&&query('SELECT id FROM ep_events WHERE user_id=? AND (photo_id=? OR evidence_id=?)',[$u['id'],$id,$id])->fetch())return;
 require_once __DIR__.'/presence.php';if(personal_has34($u,'report')&&presence_file_owner($id)===(int)$u['id'])return;
 fail('المرفق غير متاح في الملف الشخصي.',403);
}
