<?php
declare(strict_types=1);
function permission_catalog(): array {
 $screens=['customer_cases'=>['label'=>'حالات العملاء — الأقسام المسندة','actions'=>['view','edit','transfer']], 'dashboard'=>['label'=>'نظرة عامة للفريق','actions'=>['view']], 'users'=>['label'=>'ملفات الموظفين','actions'=>['view','edit']], 'report'=>['label'=>'سجل الدوام','actions'=>['view','export']], 'tracking'=>['label'=>'المتابعة الميدانية','actions'=>['view']], 'notifications'=>['label'=>'الإشعارات','actions'=>['view','create']], 'leave_overview'=>['label'=>'أرصدة الإجازات','actions'=>['view','export']]];
 foreach(modules() as $key=>$m)$screens[$key]=['label'=>$m['label'],'actions'=>['view','create','review']];
 return $screens;
}
function permission_has(array $u,string $screen,string $action='view'): bool {
 if($u['role']==='admin')return true;
 if($u['role']!=='manager')return false;
 $grants=settings()['permissions'][(string)$u['id']][$screen]??[];
 return is_array($grants)&&in_array($action,$grants,true)&&($action==='view'||in_array('view',$grants,true));
}
function permission_require(array $u,string $screen,string $action='view'): void {
 $GLOBALS['EP_SCREEN']=$screen;
 if(!permission_has($u,$screen,$action))fail('هذه العملية غير مسموحة بصلاحيات حسابك.',403);
}
function permission_context(string $action,array $u,array $b): void {
 $screen=match($action){'users','user_save'=>'users','report'=>'report','dashboard'=>'dashboard','locations'=>'tracking','notice_send'=>'notifications','leave_overview','leave_balance'=>'leave_overview','sick_summary'=>'sick',default=>''};
 if(in_array($action,['list','record_save'],true))$screen=(string)($b['module']??$_GET['module']??'');
 if($action==='record_review')$screen=(string)query('SELECT module FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetchColumn();
 $GLOBALS['EP_SCREEN']=$screen?:'dashboard';
 if($u['role']==='manager'&&in_array($action,['record_save','record_review','notice_send','locations','leave_overview','user_save'],true)) {
  if($action==='record_save'&&(int)($b['user_id']??$u['id'])===(int)$u['id']&&!empty(modules()[$screen]['employee']))return;
  permission_require($u,$screen,match($action){'record_save','notice_send'=>'create','record_review'=>'review','user_save'=>'edit',default=>'view'});
 }
}
function operation_require(string $module): void {
 $cfg=settings();$flag=match($module){'attendance'=>'attendance_enabled','payroll'=>'payroll_enabled',default=>'reports_enabled'};
 if(empty($cfg['history_approved'])||empty($cfg[$flag])||empty($cfg['operation_start'])||local_time()->format('Y-m-d')<$cfg['operation_start'])fail('التشغيل مؤجل لحين اعتماد البيانات القديمة وتاريخ البداية من الإدارة.',409);
}
function permissions_action(string $action,array $u,array $b): array {
 admin_only($u);
 if($action==='permissions_list')return ['catalog'=>permission_catalog(),'managers'=>array_map('public_user',query("SELECT * FROM ep_users WHERE role='manager' ORDER BY name")->fetchAll()),'grants'=>settings()['permissions']];
 if($action==='permissions_save'){
  $id=(int)($b['user_id']??0);if(!query("SELECT id FROM ep_users WHERE id=? AND role='manager'",[$id])->fetch())fail('اختر مدير قسم موجودًا.');
  $input=$b['grants']??[];if(!is_array($input))fail('الصلاحيات غير صالحة.');$clean=[];$catalog=permission_catalog();
  foreach($input as $screen=>$actions){if(!isset($catalog[$screen])||!is_array($actions))fail('شاشة أو صلاحية غير صالحة.');foreach($actions as $op)if(!in_array($op,$catalog[$screen]['actions'],true))fail('إجراء غير صالح.');$actions=array_values(array_unique($actions));if($actions&&!in_array('view',$actions,true))fail('يلزم منح العرض قبل الإجراءات الأخرى.');$clean[$screen]=$actions;}
  db()->beginTransaction();query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('permissions', '{}')) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);");$all=decode(query("SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key='permissions'")->fetchColumn());$all[(string)$id]=$clean;query("UPDATE ep_settings SET value_json=? WHERE setting_key='permissions'",[jencode($all)]);audit('permissions_save','manager:'.$id,['grants'=>$clean]);db()->commit();return ['ok'=>true];
 }
 if($action==='launch_save'){
  $date=valid_date($b['operation_start']??null);if(!$date)fail('حدد تاريخ بداية التشغيل.');
  if(empty($b['confirm_history'])||empty($b['confirm_balances']))fail('راجع البيانات والأرصدة ثم أكد اعتمادها.');
  if(!(int)query('SELECT COUNT(*) FROM ep_history')->fetchColumn())fail('استورد البيانات القديمة أولًا قبل اعتماد بداية التشغيل.');
  if(!empty($b['attendance_enabled'])&&(!(int)query('SELECT COUNT(*) FROM ep_policies')->fetchColumn()||!settings()['locations']))fail('اضبط الدوام وموقع الشركة قبل تشغيل الحضور.');
  $values=['history_approved'=>true,'history_approved_at'=>utc_now(),'history_approved_by'=>(int)$u['id'],'operation_start'=>$date,'attendance_enabled'=>!empty($b['attendance_enabled']),'reports_enabled'=>!empty($b['reports_enabled']),'payroll_enabled'=>!empty($b['payroll_enabled'])];
  $old=settings();if($old['history_approved']&&$old['operation_start']!==$date&&((int)query('SELECT COUNT(*) FROM ep_events')->fetchColumn()||(int)query('SELECT COUNT(*) FROM ep_payroll')->fetchColumn()))fail('لا تغير بداية التشغيل بعد وجود سجلات تشغيل.');
  db()->beginTransaction();foreach($values as $k=>$v)query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',[$k,jencode($v)]);audit('launch_save','operations',$values);db()->commit();return ['ok'=>true];
 }
 fail('العملية غير موجودة.',404);
}
