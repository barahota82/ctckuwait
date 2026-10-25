<?php
declare(strict_types=1);
function permission_catalog(): array {
 require_once __DIR__."/modules.php";
 $screens=['customer_cases'=>['label'=>'حالات العملاء — الأقسام المسندة','actions'=>['view','edit','transfer']], 'dashboard'=>['label'=>'نظرة عامة للفريق','actions'=>['view']], 'users'=>['label'=>'ملفات الموظفين','actions'=>['view','edit']], 'report'=>['label'=>'سجل الدوام','actions'=>['view','export']], 'tracking'=>['label'=>'المتابعة الميدانية','actions'=>['view']], 'notifications'=>['label'=>'الإشعارات','actions'=>['view','create']], 'leave_overview'=>['label'=>'أرصدة الإجازات','actions'=>['view','export']]];
 $screens['corporate_document']=['label'=>'مستندات وتجديدات الشركات','actions'=>['view','create']];$screens['attachment_archive']=['label'=>'أرشيف المرفقات العامة / ملفات الفريق','actions'=>['view','create']];$screens['company_file']=['label'=>'الرئيسي والفروع','actions'=>['view','create']];
 foreach(['cms'=>'المحتوى والكروت','offers'=>'العروض']as $key=>$label)$screens[$key]=['label'=>$label,'actions'=>['view','create','edit']];
 $screens['bank_details']=['label'=>'بيانات الحساب البنكي','actions'=>['view']];
 $screens['commissions']=['label'=>'العمولات','actions'=>['view','create','edit','export']];$screens['work_permit']=['label'=>'أذونات العمل','actions'=>['view','create','edit']];$screens['addition']=['label'=>'الإضافات الشهرية','actions'=>['view','create']];$screens['workforce']=['label'=>'فترات الخدمة','actions'=>['view','create','edit']];$screens['companies']=['label'=>'الشركات','actions'=>['view','create','edit']];$screens['allowances']=['label'=>'الإضافات المؤرخة والجمعات','actions'=>['view','create']];$screens['payroll']=['label'=>'إعداد الرواتب','actions'=>['view','create','submit','export']];$screens['resumption']=['label'=>'مباشرة العمل','actions'=>['view','create','review']];$screens['analytics']=['label'=>'التقارير الشاملة','actions'=>['view','export']];
 foreach(modules() as $key=>$m)$screens[$key]=['label'=>$m['label'],'actions'=>['view','create','review']];
 $screens['batch_addition29']=['label'=>'إدخال الإضافات جماعيًا','actions'=>['view','create']];$screens['batch_friday29']=['label'=>'إدخال الجمعات جماعيًا','actions'=>['view','create']];
 foreach(['policy_general'=>'السياسة العامة','policy_staff'=>'جداول الموظفين']as $key=>$label)$screens[$key]=['label'=>$label,'actions'=>['view','create','edit','delete']];
 $screens['collection37']=['label'=>'عمولة التحصيل','actions'=>['view','create','export']];$screens['vouchers37']=['label'=>'سندات الصرف','actions'=>['view','export']];
 $screens['payroll_review35']=['label'=>'مراجعة كشوف الرواتب','actions'=>['view']];$screens['fridays35']=['label'=>'الجمعات الفعلية','actions'=>['view']];$screens['payroll_settings35']=['label'=>'إعدادات الرواتب والعمولات','actions'=>['view','create']];
 $screens['v28_locations']=['label'=>'مواقع العمل المشتركة','actions'=>['view','create','edit']];
 $screens['location_capture40']=['label'=>'استخدام الموقع الحالي لإعداد الدوام','actions'=>['view']];
 foreach(['contract_sales99'=>'تسجيل عقود مسؤولي المبيعات','contract_staff99'=>'تسجيل عقود موظفي الشركة','contract_agents99'=>'تسجيل عقود المناديب','receipt_company99'=>'تسجيل تحصيل الشركة','receipt_click99'=>'تسجيل تحصيل أونلاين كليك','receipt_afaq99'=>'تسجيل تحصيل أونلاين آفاق','receipt_agents99'=>'تسجيل تحصيل المناديب']as $key=>$label)$screens[$key]=['label'=>$label,'actions'=>['view','create','edit']];
 foreach(['payroll_entry_click126'=>'تسجيل لإعداد الرواتب — شركة كليك','payroll_entry_afaq126'=>'تسجيل لإعداد الرواتب — شركة آفاق']as $key=>$label)$screens[$key]=['label'=>$label,'actions'=>['view']];
 return $screens;
}
function permission_has(array $u,string $screen,string $action='view'): bool {
 if($u['role']==='admin')return true;
 $grants=permission_effective29($u)[$screen]??[];
 return is_array($grants)&&in_array($action,$grants,true)&&($action==='view'||in_array('view',$grants,true));
}
function permission_require(array $u,string $screen,string $action='view'): void {
 $GLOBALS['EP_SCREEN']=$screen;$GLOBALS['EP_OPERATION29']=$action;
 if(!permission_has($u,$screen,$action))fail('هذه العملية غير مسموحة بصلاحيات حسابك.',403);
}
function permission_context(string $action,array $u,array $b): void {
 $screen=match($action){'users','user_save'=>'users','report'=>'report','dashboard'=>'dashboard','locations'=>'tracking','notice_send'=>'notifications','leave_overview','leave_balance'=>'leave_overview','sick_summary','sick_overview'=>'sick',default=>''};
 if(in_array($action,['list','record_save'],true))$screen=(string)($b['module']??$_GET['module']??'');
 if($action==='record_review')$screen=(string)query('SELECT module FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetchColumn();
 $GLOBALS['EP_SCREEN']=$screen?:'dashboard';
 if($u['role']==='manager'&&in_array($action,['record_save','record_review','notice_send','locations','leave_overview','user_save'],true)) {
  if($action==='record_save'&&(int)($b['user_id']??$u['id'])===(int)$u['id']&&!empty(modules()[$screen]['employee']))return;
  permission_require($u,$screen,match($action){'record_save','notice_send'=>'create','record_review'=>'review','user_save'=>'edit',default=>'view'});
 }
}
function permissions_action(string $action,array $u,array $b): array {
 admin_only($u);
 if($action==='permissions_list')return ['catalog'=>permission_catalog(),'managers'=>array_map('public_user',query("SELECT * FROM ep_users WHERE role='manager' ORDER BY name")->fetchAll()),'grants'=>settings()['permissions']];
 if($action==='permissions_save'){
  if(site_v29_ready())fail('استخدم مجموعات الصلاحيات بدل الأدوار القديمة.',409);
  $id=(int)($b['user_id']??0);if(!query("SELECT id FROM ep_users WHERE id=? AND role='manager'",[$id])->fetch())fail('اختر مدير قسم موجودًا.');
  $input=$b['grants']??[];if(!is_array($input))fail('الصلاحيات غير صالحة.');$clean=[];$catalog=permission_catalog();
  foreach($input as $screen=>$actions){if(!isset($catalog[$screen])||!is_array($actions))fail('شاشة أو صلاحية غير صالحة.');foreach($actions as $op)if(!in_array($op,$catalog[$screen]['actions'],true))fail('إجراء غير صالح.');$actions=array_values(array_unique($actions));if($actions&&!in_array('view',$actions,true))fail('يلزم منح العرض قبل الإجراءات الأخرى.');$clean[$screen]=$actions;}
  db()->beginTransaction();query("MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES ('permissions', '{}')) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);");$all=decode(query("SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key='permissions'")->fetchColumn());$all[(string)$id]=$clean;query("UPDATE ep_settings SET value_json=? WHERE setting_key='permissions'",[jencode($all)]);audit('permissions_save','manager:'.$id,['grants'=>$clean]);db()->commit();return ['ok'=>true];
 }

 fail('العملية غير موجودة.',404);
}
