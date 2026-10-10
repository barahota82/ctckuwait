<?php
declare(strict_types=1);
function customer_seed(PDO $pdo): void {
 $s=$pdo->prepare('MERGE INTO ep_customer_departments WITH (HOLDLOCK) AS target USING (VALUES (?, ?, ?)) AS source (code, content_json, sort_order) ON target.code=source.code WHEN NOT MATCHED THEN INSERT (code, content_json, sort_order) VALUES (source.code, source.content_json, source.sort_order);');
 foreach(['general'=>['رسائل عامة','General enquiries'],'service'=>['خدمة العملاء','Customer service'],'collection'=>['التحصيل','Collection enquiries'],'delivery'=>['التوصيل','Delivery'],'complaints'=>['الشكاوى','Complaints']] as $code=>$v)$s->execute([$code,jencode(['ar'=>['title'=>$v[0]],'en'=>['title'=>$v[1]]]),array_search($code,['general','service','collection','delivery','complaints'])]);
}
function customer_department_ids(array $u): array {
 if($u['role']==='admin')return array_map('intval',query('SELECT id FROM ep_customer_departments')->fetchAll(PDO::FETCH_COLUMN));
 permission_require($u,'customer_cases');return array_map('intval',query('SELECT department_id FROM ep_customer_members WHERE user_id=?',[$u['id']])->fetchAll(PDO::FETCH_COLUMN));
}
function customer_case(array $u,int $id,bool $lock=false): array {
 $r=query('SELECT * FROM ep_messages'.($lock?' WITH (UPDLOCK, HOLDLOCK)':'').' WHERE id=?',[$id])->fetch();if(!$r)fail('الحالة غير موجودة.',404);
 if($u['role']!=='admin'&&!in_array((int)$r['department_id'],customer_department_ids($u),true))fail('هذه الحالة خارج أقسامك.',403);return $r;
}
function customer_event(int $id,?int $actor,string $type,?int $from,?int $to,string $note):void {
 query('INSERT INTO ep_message_events(message_id,user_id,event_type,from_department,to_department,note,created_at) VALUES(?,?,?,?,?,?,?)',[$id,$actor,$type,$from,$to,$note,utc_now()]);
}
function customer_notify(int $department,int $id):void {
 foreach(query('SELECT u.* FROM ep_users u JOIN ep_customer_members m ON m.user_id=u.id WHERE m.department_id=? AND u.active=1',[$department])->fetchAll() as $u)if(permission_has($u,'customer_cases'))query('INSERT INTO ep_notifications(user_id,title,body,require_ack,created_at) VALUES(?,?,?,?,?)',[$u['id'],'حالة عميل جديدة','حالة M-'.$id.' أُسندت إلى قسمك. افتح حالات العملاء للمتابعة.',0,utc_now()]);
}
function customer_submit(array $b): int {
 $kind=(string)($b['kind']??'general');$routes=['general'=>'general','payment'=>'collection','purchase_enquiry'=>'service','delivery'=>'delivery','complaint'=>'complaints','support'=>'service'];if(!isset($routes[$kind]))fail('نوع الرسالة غير صالح.');
 $configured=(int)(settings()['customer_routes'][$kind]??0);$dep=$configured&&(bool)query('SELECT id FROM ep_customer_departments WHERE id=? AND active=1',[$configured])->fetchColumn()?$configured:(int)query('SELECT id FROM ep_customer_departments WHERE code=? AND active=1',[$routes[$kind]])->fetchColumn();if(!$dep)$dep=(int)query("SELECT id FROM ep_customer_departments WHERE code='general' AND active=1")->fetchColumn();if(!$dep)fail('استقبال الرسائل غير متاح حاليًا.');
 $name=trim((string)($b['name']??''));$email=trim((string)($b['email']??''));$phone=trim((string)($b['phone']??''));$body=trim((string)($b['message']??''));
 if(!$name||mb_strlen($name)>150||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>180||strlen($phone)>50||mb_strlen($body)<10||mb_strlen($body)>5000)fail('راجع الاسم والبريد ونص الرسالة (10–5000 حرف).');
 $ip=hash('sha256','public-message|'.($_SERVER['REMOTE_ADDR']??''));if((int)query('SELECT COUNT(*) FROM ep_messages WHERE ip_hash=? AND created_at>=?',[$ip,gmdate('Y-m-d H:i:s',time()-3600)])->fetchColumn()>=5)fail('انتظر قبل إرسال رسالة أخرى.',429);
 db()->beginTransaction();query('INSERT INTO ep_messages(name,email,phone,body,created_at,status,ip_hash,department_id,kind) VALUES(?,?,?,?,?,?,?,?,?)',[$name,$email,$phone,$body,utc_now(),'new',$ip,$dep,$kind]);$id=(int)db()->lastInsertId();customer_event($id,null,'received',null,$dep,'استلام من الموقع');customer_notify($dep,$id);db()->commit();return $id;
}
function rating_submit(array $b): int {
 if(!settings()['ratings_enabled'])fail('استقبال التقييمات غير متاح.');$name=trim((string)($b['name']??''));$email=trim((string)($b['email']??''));$phone=trim((string)($b['phone']??''));$score=(int)($b['score']??0);$body=trim((string)($b['message']??''));
 if(!$name||mb_strlen($name)>150||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>180||mb_strlen($phone)>50||$score<1||$score>5||mb_strlen($body)>3000)fail('راجع الاسم والبريد والتقييم.');
 $ip=hash('sha256','public-rating|'.($_SERVER['REMOTE_ADDR']??''));if((int)query('SELECT COUNT(*) FROM ep_ratings WHERE ip_hash=? AND created_at>=?',[$ip,gmdate('Y-m-d H:i:s',time()-3600)])->fetchColumn()>=3)fail('انتظر قبل تقديم تقييم آخر.',429);
 if(!ratings_phone_ready()&&$phone!=='')fail('تعذر حفظ بيانات التقييم؛ حاول لاحقًا.');
 $columns=ratings_phone_ready()?'name,email,phone,score,body,consent_public,created_at,ip_hash':'name,email,score,body,consent_public,created_at,ip_hash';$values=[$name,$email];if(ratings_phone_ready())$values[]=$phone;$values=array_merge($values,[$score,$body,empty($b['consent_public'])?0:1,utc_now(),$ip]);
 query('INSERT INTO ep_ratings('.$columns.') VALUES('.implode(',',array_fill(0,count($values),'?')).')',$values);return (int)db()->lastInsertId();
}
function customer_action(string $action,array $u,array $b): array {
 if($action==='customer_routes_save'){admin_only($u);$routes=$b['routes']??[];if(!is_array($routes))fail('توجيه الرسائل غير صالح.');$clean=[];foreach(['general','payment','purchase_enquiry','delivery','complaint','support']as $kind){$id=(int)($routes[$kind]??0);if(!query('SELECT id FROM ep_customer_departments WHERE id=? AND active=1',[$id])->fetchColumn())fail('اختر قسمًا متاحًا لكل نوع رسالة.');$clean[$kind]=$id;}query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',['customer_routes',jencode($clean)]);audit('customer_routes_save','customers');return ['ok'=>true];}

 if($action==='customer_departments'){
  if($u['role']!=='admin')permission_require($u,'customer_cases');$out=['departments'=>query('SELECT * FROM ep_customer_departments ORDER BY sort_order,id')->fetchAll()];
  if($u['role']==='admin')$out['members']=query('SELECT * FROM ep_customer_members')->fetchAll();return $out;
 }
 if($action==='customer_department_save'){
  admin_only($u);$id=(int)($b['id']??0);$code=trim((string)($b['code']??''));$name=trim((string)($b['name']??''));$members=array_values(array_unique(array_map('intval',$b['members']??[])));
  if(!preg_match('/^[a-z0-9_-]{1,40}$/',$code)||!$name||mb_strlen($name)>150)fail('اسم القسم ورمزه مطلوبان.');
  foreach($members as $uid)if(!query("SELECT id FROM ep_users WHERE id=? AND active=1 AND role IN ('admin','manager')",[$uid])->fetch())fail('مسؤول القسم يجب أن يكون مديرًا أو مشرفًا نشطًا.');
  if($id&&!query('SELECT id FROM ep_customer_departments WHERE id=?',[$id])->fetch())fail('القسم غير موجود.',404);
  db()->beginTransaction();$content=['ar'=>['title'=>$name],'en'=>['title'=>trim((string)($b['name_en']??''))]];
  if($id)query('UPDATE ep_customer_departments SET code=?,content_json=?,active=?,sort_order=? WHERE id=?',[$code,jencode($content),empty($b['active'])?0:1,(int)($b['sort_order']??0),$id]);else{query('INSERT INTO ep_customer_departments(code,content_json,active,sort_order) VALUES(?,?,?,?)',[$code,jencode($content),empty($b['active'])?0:1,(int)($b['sort_order']??0)]);$id=(int)db()->lastInsertId();}
  query('DELETE FROM ep_customer_members WHERE department_id=?',[$id]);foreach($members as $uid)query('INSERT INTO ep_customer_members(department_id,user_id) VALUES(?,?)',[$id,$uid]);
  translation_enqueue('department',$id,$content['ar']);audit($action,'department:'.$id,['members'=>$members]);db()->commit();return ['ok'=>true,'id'=>$id];
 }
 if($action==='customer_cases'){
  $ids=customer_department_ids($u);$where=$u['role']==='admin'?'1=1':($ids?'department_id IN ('.implode(',',array_fill(0,count($ids),'?')).')':'1=0');return query('SELECT TOP (500) id,name,email,phone,body,created_at,status,department_id,kind FROM ep_messages WHERE '.$where.' ORDER BY id DESC',$u['role']==='admin'?[]:$ids)->fetchAll();
 }
 if($action==='customer_case_detail'){
  $r=customer_case($u,(int)($_GET['id']??0));$r['events']=query('SELECT e.*,u.name AS actor_name FROM ep_message_events e LEFT JOIN ep_users u ON u.id=e.user_id WHERE e.message_id=? ORDER BY e.id',[$r['id']])->fetchAll();return $r;
 }
 if(in_array($action,['customer_case_update','customer_case_transfer'],true)){
  if($u['role']!=='admin')permission_require($u,'customer_cases',$action==='customer_case_transfer'?'transfer':'edit');
  db()->beginTransaction();$r=customer_case($u,(int)($b['id']??0),true);$note=trim((string)($b['note']??''));if(mb_strlen($note)>5000)fail('الملاحظة طويلة.');$from=$r['department_id']?(int)$r['department_id']:null;
  if($action==='customer_case_transfer'){
   $to=(int)($b['department_id']??0);if($to===$from||!query('SELECT id FROM ep_customer_departments WHERE id=? AND active=1',[$to])->fetch()||mb_strlen($note)<3)fail('اختر قسمًا مختلفًا وأضف سبب التحويل.');
   query("UPDATE ep_messages SET department_id=?,status='new' WHERE id=?",[$to,$r['id']]);customer_event((int)$r['id'],(int)$u['id'],'transfer',$from,$to,$note);customer_notify($to,(int)$r['id']);
  }else{
   $status=(string)($b['status']??$r['status']);if(!in_array($status,['new','read','in_progress','waiting','closed'],true))fail('الحالة غير صالحة.');if($status===$r['status']&&!$note)fail('أضف ملاحظة أو غير الحالة.');query('UPDATE ep_messages SET status=? WHERE id=?',[$status,$r['id']]);customer_event((int)$r['id'],(int)$u['id'],'status:'.$status,$from,$from,$note);
  }
  audit($action,'message:'.$r['id']);db()->commit();return ['ok'=>true];
 }
 if($action==='customer_ratings'){admin_only($u);return query('SELECT TOP (500) id,name,email,'.(ratings_phone_ready()?'phone':"N'' AS phone").',score,body,consent_public,status,created_at,reviewed_by FROM ep_ratings ORDER BY id DESC')->fetchAll();}
 if($action==='customer_rating_review'){
  admin_only($u);$id=(int)($b['id']??0);$status=(string)($b['status']??'');if(!in_array($status,['pending','private','published'],true))fail('الحالة غير صالحة.');$r=query('SELECT * FROM ep_ratings WHERE id=?',[$id])->fetch();if(!$r)fail('التقييم غير موجود.',404);if($status==='published'&&!$r['consent_public'])fail('العميل لم يوافق على نشر تقييمه.');query('UPDATE ep_ratings SET status=?,reviewed_by=? WHERE id=?',[$status,$u['id'],$id]);audit($action,'rating:'.$id,['status'=>$status]);return ['ok'=>true];
 }
 if($action==='customer_rating_settings'){
  admin_only($u);$v=(string)($b['visibility']??'admin');if(!in_array($v,['admin','public'],true))fail('اختيار غير صالح.');foreach(['ratings_visibility'=>$v,'ratings_enabled'=>!empty($b['enabled'])]as $k=>$value)query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',[$k,jencode($value)]);audit($action,'ratings',['visibility'=>$v]);return ['ok'=>true];
 }
 fail('العملية غير موجودة.',404);
}
