<?php
declare(strict_types=1);
function company_types_raw199():array {
 $names=['عقد تأسيس الشركة','عقد / ملحق تعديل عقد التأسيس','الترخيص التجاري','رخصة الإعلان','رخصة الإطفاء','وثيقة تأمين إصابات العمل','مستخرج السجل التجاري','تأشيرة السجل التجاري','شهادة التأمينات الاجتماعية','شهادة المعلومات المدنية','اعتماد توقيع الشؤون','اعتماد توقيع المرور','لائحة الدوام','تقرير إصابات العمل ربع السنوي','آر جي'];$defaults=[];foreach($names as $i=>$name)$defaults[]=['id'=>$i+1,'name'=>$name,'active'=>true];return setting31('company_document_types194',$defaults);
}
function company_types194():array {return array_values(array_filter(company_types_raw199(),fn($t)=>empty($t['_deleted199'])));}
function company_types_lock199():array {
 $defaults=company_types_raw199();query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source(setting_key,value_json) ON target.setting_key=source.setting_key WHEN NOT MATCHED THEN INSERT(setting_key,value_json) VALUES(source.setting_key,source.value_json);',['company_document_types194',jencode($defaults)]);
 return decode(query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='company_document_types194'")->fetchColumn());
}
function company_type_references199(array $type):int {
 $uses=function($v)use(&$uses,$type):bool{if(!is_array($v))return false;foreach($v as $key=>$value){if($key==='document_type_id'&&(string)$value===(string)$type['id'])return true;if(in_array($key,['document_type','document_name'],true)&&is_string($value)&&in_array(trim($value),array_map('trim',[$type['name'],...($type['_names199']??[])]),true))return true;if(is_array($value)&&$uses($value))return true;}return false;};$count=0;
 foreach(query("SELECT body_json FROM ep_records WHERE module IN ('company_file','corporate_document','attachment_archive')")->fetchAll()as $row)if($uses(decode($row['body_json'])))$count++;
 foreach(query('SELECT body_json FROM ep_history')->fetchAll()as $row)if($uses(decode($row['body_json'])))$count++;
 return $count;
}
function company_types_action194(string $action,array $actor,array $b):array {
 if(personal_mode34($actor))fail('هذه الشاشة لمساحة العمل.',403);permission_require($actor,'corporate_document',$action==='review193_types'?'view':'create');
 if($action==='review193_types')return company_types194();admin_only($actor);
 db()->beginTransaction();$types=company_types_lock199();$id=(int)($b['id']??0);$index=null;foreach($types as $i=>$t)if((int)$t['id']===$id&&empty($t['_deleted199']))$index=$i;
 if($id&&$index===null)fail('اسم المستند غير موجود.');
 if($action==='review193_type_delete'){
  if(!$id)fail('حدد اسم المستند.');$count=company_type_references199($types[$index]);if($count)fail('لا يمكن حذف المسمى لارتباطه بـ '.$count.' مستند أو سجل تاريخي؛ يمكنك تعطيله للاختيار الجديد.');
  $types[$index]['_deleted199']=utc_now();$types[$index]['active']=false;
 }else{
  $name=trim((string)($b['name']??''));if(!$name||mb_strlen($name)>180||$name==='أخرى')fail('اسم مستند صالح مطلوب؛ خيار أخرى مستقل.');
  $normalize=fn($s)=>mb_strtolower(preg_replace('/\s+/u',' ',trim($s)));foreach($types as $t)if(empty($t['_deleted199'])&&(int)$t['id']!==$id&&$normalize($t['name'])===$normalize($name))fail('اسم المستند موجود بالفعل.');
  if(!$id){$id=max([15,...array_column($types,'id')])+1;$types[]=['id'=>$id,'name'=>$name,'active'=>true];}else{$types[$index]['_names199']=array_values(array_unique([...($types[$index]['_names199']??[]),$types[$index]['name']]));$types[$index]['name']=$name;$types[$index]['active']=filter_var($b['active']??true,FILTER_VALIDATE_BOOLEAN);}
 }
 query("UPDATE ep_settings SET value_json=? WHERE setting_key='company_document_types194'",[jencode($types)]);unset($GLOBALS['EP_SETTINGS']);audit($action,'type:'.$id,['name'=>$types[$index]['name']??$name??'']);db()->commit();return ['ok'=>true,'id'=>$id];
}
function company_document_validate194(string $kind,array &$body,array $b,array $actor):void {
 if(!in_array($kind,['company_file','corporate_document','attachment_archive'],true))return;
 $company=(string)($body['company_code']??'');if(!$company||!company_by_code($company))fail('اختر شركة موجودة.');if(!company_allowed30($actor,$kind,$company,'create'))fail('الشركة خارج نطاقك.',403);
 if(empty($body['name'])||mb_strlen((string)$body['name'])>180)fail('اسم المستند مطلوب وفي حدود 180 حرف.');
 $target=(int)($body['linked_user_id']??0);if($target){employee_required($target);if(!in_array($target,permission_scope29($actor,$kind,'create'),true))fail('الموظف المرتبط خارج نطاقك.',403);}
 if($kind==='company_file'){if(!in_array($body['type']??'',['main','branch'],true))fail('حدد الرئيسي أو الفرع.');return;}
 $type=$body['document_type_id']??null;if($type==='other'){if(!trim((string)($body['other_name']??''))||mb_strlen((string)$body['other_name'])>180)fail('حدد اسم المستند الآخر.');$body['document_type']=$body['other_name'];}
 else{$selected=null;foreach(company_types194()as $t)if((int)$t['id']===(int)$type&&$t['active'])$selected=$t;if(!$selected)fail('اختر مستندًا متاحًا من القائمة.');$body['document_type_id']=(int)$selected['id'];$body['document_type']=$selected['name'];$type=(int)$selected['id'];}
 if(!in_array($body['issued_to']??'',['company','file'],true)||!in_array($body['coverage']??'',['company','all','selected'],true))fail('حدد جهة الإصدار ونطاق المستند.');
 if($type===9&&$body['issued_to']!=='file')fail('شهادة التأمينات الاجتماعية ترتبط بالرئيسي أو الفرع.');
 $files=[];foreach(query("SELECT * FROM ep_records WHERE module='company_file' AND status='approved'")->fetchAll()as $r){$v=decode($r['body_json']);if(($v['company_code']??'')===$company)$files[(int)$r['id']]=$v;}
 $check=function(int $id)use($files){if(!$id||!isset($files[$id]))fail('الملف المحدد لا يتبع الشركة.');};
 if($body['issued_to']==='file')$check((int)($body['issued_file_id']??0));else $body['issued_file_id']=null;
 if($body['coverage']==='selected'){if(!is_array($body['coverage_file_ids']??null)||!count($body['coverage_file_ids']))fail('اختر الملفات المشمولة.');$body['coverage_file_ids']=array_values(array_unique(array_map('intval',$body['coverage_file_ids'])));foreach($body['coverage_file_ids']as $id)$check($id);}else $body['coverage_file_ids']=[];
 if($type===14){$year=filter_var($body['report_year']??null,FILTER_VALIDATE_INT);$quarter=filter_var($body['report_quarter']??null,FILTER_VALIDATE_INT);if($year===false||$year<1900||$year>2200||$quarter===false||$quarter<1||$quarter>4)fail('حدد سنة وربع التقرير.');$body['report_year']=$year;$body['report_quarter']=$quarter;}
 else{unset($body['report_year'],$body['report_quarter']);}
 $prev=(int)($b['previous_id']??0);if($prev){if(in_array($type,[1,2,14],true))fail('هذا المستند يسجل كسجل مستقل.');$r=query('SELECT * FROM ep_records WHERE id=? AND module=?',[$prev,$kind])->fetch();if(!$r)fail('النسخة السابقة غير موجودة.');$v=decode($r['body_json']);if(($v['company_code']??'')!==$company||(isset($v['document_type_id'])&&(string)$v['document_type_id']!==(string)$type))fail('النسخة الجديدة يجب أن تخص نفس الشركة ونوع المستند.');}
}
function company_result194(array $result,array $actor,string $kind):array {
 if(!in_array($kind,['company_file','corporate_document','attachment_archive'],true))return $result;
 $result['types']=company_types194();$allowed=array_column($result['companies']??[],'code');$files=query("SELECT * FROM ep_records WHERE module='company_file' AND status='approved' ORDER BY id DESC")->fetchAll();$result['files']=[];
 foreach($files as $r){$v=decode($r['body_json']);if(!in_array($v['company_code']??'',$allowed,true))continue;$result['files'][]=['id'=>(int)$r['id'],'previous_id'=>(int)($v['previous_id']??0),'body_json'=>jencode(array_intersect_key($v,array_flip(['name','company_code','type','active','file_number'])) )];}
 foreach($result['records']as &$r){$r['id']=(int)$r['id'];$r['previous_id']=(int)(decode($r['body_json'])['previous_id']??0);}unset($r);
 $result['users']=[];foreach(array_chunk(permission_scope29($actor,$kind),500)as $ids){$marks=implode(',',array_fill(0,count($ids),'?'));foreach(query("SELECT id,name,username,profile_json FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$ids)->fetchAll()as $s)$result['users'][]=['id'=>$s['id'],'name'=>$s['name'],'employee_code'=>employee_key($s),'profile'=>['name_en'=>decode($s['profile_json'])['name_en']??'']];}return $result;
}
function video_sources194(array $actor):array {
 admin_only($actor);$out=[];$paid=[];
 foreach(query("SELECT * FROM ep_records WHERE module='payment' AND status='approved'")->fetchAll()as $r){$p=decode($r['body_json']);$kind=$p['kind']??'';$source=(int)($p['source_id']??0);if($kind==='bank'){$bank=query('SELECT snapshot_json FROM ep_bank_payroll WHERE id=?',[$source])->fetch();if(!$bank)continue;$source=(int)(decode($bank['snapshot_json'])['payroll_id']??0);$kind='payroll';}if($kind==='payroll'){$key=(int)$r['user_id'].':'.$source;$paid[$key]=($paid[$key]??0)+(float)$r['amount'];}}
 foreach(query("SELECT * FROM ep_payroll WHERE status='approved' ORDER BY month_key DESC,id DESC")->fetchAll()as $r){$amount=min((float)$r['net_amount'],$paid[$r['user_id'].':'.$r['id']]??0);if($amount>0)$out[]=['id'=>(int)$r['id'],'user_id'=>(int)$r['user_id'],'kind'=>'payroll','label'=>'مستحقات شهر '.$r['month_key'],'paid_amount'=>$amount];}
 $leavePaid=[];foreach(query("SELECT * FROM ep_records WHERE module='leave_cash' AND status='approved' ORDER BY id DESC")->fetchAll()as $r){$uid=(int)$r['user_id'];$leavePaid[$uid]??=v26_leave_paid_amounts($uid);$amount=$leavePaid[$uid][(int)$r['id']]??0;if($amount>0)$out[]=['id'=>(int)$r['id'],'user_id'=>$uid,'kind'=>'leave_cash','label'=>'صرف إجازة '.$r['date_from'],'paid_amount'=>$amount];}return $out;
}
