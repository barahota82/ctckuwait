<?php
declare(strict_types=1);
// Optional inline form values join user_save's existing transaction. No separate commits.
function employee_editor_extras134(array $actor,int $id,mixed $values,bool $new136=false):void {
 admin_only($actor);if(!db()->inTransaction())throw new LogicException('Employee editor requires the user transaction.');
 if(!is_array($values)||array_diff(array_keys($values),['allowance','incentives','service','employment','settings130','incentive_stops132','benefits198']))fail('بيانات الموظف غير صالحة.');
 if(isset($values['service'])){
  $v=$values['service'];if(!is_array($v)||!in_array($v['kind']??'', ['close','rehire','terms'],true))fail('إجراء الخدمة غير صالح.');
  $data=$v['data']??[];if(!is_array($data))fail('بيانات الخدمة غير صالحة.');$data['user_id']=$id;
  if($v['kind']==='close'){if(empty($data['id'])||empty($data['to']))fail('حدد الفترة وتاريخ انتهاء الخدمة.');service_save($actor,$data);}
  elseif($v['kind']==='rehire'){unset($data['id']);service_save($actor,$data);}
  else {if(array_diff(array_keys($data['values']??[]),['company_code'])||array_key_exists('residence_file_id',$data))fail('تغيير شركة العمل فقط؛ الإقامة من أذونات العمل.');workforce_action('workforce_terms',$actor,$data);}
 }
 if(isset($values['settings130'])){require_once __DIR__.'/employee-fields.php';employee_settings_save130($actor,$id,$values['settings130']);}
 if(isset($values['benefits198'])){require_once __DIR__.'/benefits.php';employee_benefits_save198($actor,$id,$values['benefits198'],$new136);}
 if(isset($values['allowance'])){
  $a=$values['allowance'];if(!is_array($a))fail('بيانات البدلات غير صالحة.');
  allowance_action('allowance_save',$actor,['user_id'=>$id,'date'=>$a['date']??null,'amounts'=>$a['amounts']??[],'note'=>$a['note']??'','replace_current393'=>true,'expected393'=>$a['expected393']??[]]);
 }
 $stops132=$values['incentive_stops132']??[];if(!is_array($stops132)||count($stops132)>20)fail('بيانات إيقاف الحافز غير صالحة.');foreach($stops132 as $stop132){if(!is_array($stop132))fail('بيانات إيقاف الحافز غير صالحة.');incentive_action114('incentive114_stop',$actor,['user_id'=>$id,'id'=>$stop132['id']??0,'company_code'=>$stop132['company_code']??'','to'=>$stop132['to']??null]);}
 $terms=$values['incentives']??[];if(!is_array($terms)||count($terms)>20)fail('بيانات الحافز غير صالحة.');
 foreach($terms as $term){if(!is_array($term))fail('بيانات الحافز غير صالحة.');incentive_action114('incentive114_terms',$actor,['user_id'=>$id,'id'=>$term['id']??0,'resume_id135'=>$term['resume_id135']??0,'company_code'=>$term['company_code']??'','amount'=>$term['amount']??null,'from'=>$term['from']??null,'to'=>$term['to']??null]);}
 if(isset($values['employment'])){
  $e=$values['employment'];if(!is_array($e))fail('بيانات الإجراء غير صالحة.');$initial136=!empty($e['initial136']);if($initial136){if(!$new136)fail('إجراءات التعيين الأول تخص تسجيل الموظف الجديد فقط.');$staff136=employee_required($id);$hired136=valid_date($staff136['hired_on']??null);if(!$hired136)fail('تاريخ التعيين مطلوب لإجراءات التعيين الأول.');$e['title']='إجراءات التعيين';$e['date_from']=$hired136;$e['date_to']=null;$e['purpose130']='appointment';}$title=trim((string)($e['title']??''));if(!$title||mb_strlen($title)>180)fail('عنوان السجل غير صالح.');
  $from=valid_date($e['date_from']??null);$to=valid_date($e['date_to']??null);if($from&&$to&&$to<$from)fail('نهاية الفترة قبل البداية.');$body=[];foreach(['stage','checklist','details','purpose130']as $k)$body[$k]=mb_substr((string)($e[$k]??''),0,5000);
  if($initial136)$body['_initial136']=true;
  $fid=null;if(!empty($_FILES['service_file143']['tmp_name'])){$GLOBALS['EDITOR_FILES143']=[];$fid=upload_file($_FILES['service_file143'],(int)$actor['id']);}
  query('INSERT INTO ep_records(module,user_id,title,date_from,date_to,amount,status,body_json,file_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',['employment',$id,$title,$from,$to,0,'active',jencode($body),$fid,utc_now(),utc_now()]);audit('record_create','employment:'.db()->lastInsertId());
 }

}
