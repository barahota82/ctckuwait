<?php
// Included by the real SQLite/provider-boundary test, after its concurrency/rollback cases.
$httpMode='ok';$during=null;put_setting31('data_translation_budget160',1000000);
$specs=[
 ['contracts_daily35',['cancel_reason'=>'إلغاء عقد بسبب تراجع العميل 164'],'cancel_reason'],
 ['company_sale36',['cancel_reason'=>'إلغاء مبيعات بسبب خطأ التسجيل 164'],'cancel_reason'],
 ['leave_cash',['_cancel_reason'=>'إلغاء التسوية بطلب الموظف 164'],'_cancel_reason'],
 ['debt_opening',['name'=>'سلفة شراء أجهزة 164 — debt_opening','update_reason'=>'تصحيح رصيد بعلم الموظف 164'],'update_reason'],
 ['video_receipt',['mode'=>'custom','text'=>'أقر باستلام مبلغ 164 عن مستحقات إضافية','objection'=>'لم يستلم الموظف هذا المبلغ 164'],'text'],
 ['payroll_input35',['commission_deduction_note36'=>'خصم عمولة بسبب مرتجع استثنائي 164'],'commission_deduction_note36'],
 ['request',['details'=>'نص طلب مصرح بعرضه 164','snapshot'=>['reason'=>'أصل تدقيق لا يترجم 164'],'before'=>['note'=>'قيمة قديمة للقراءة فقط 164']],'details']
];
$expected=[];$fid164=100;
foreach($specs as [$module,$body,$key]){query('INSERT INTO ep_records(id,module,title,body_json) VALUES(?,?,?,?)',[++$fid164,$module,$module,jencode($body)]);$expected[]= [data_record_context162($module,$key),$body[$key]];}
$expected[]=['record_text:debt_opening:name','سلفة شراء أجهزة 164'];
$calc164=['salary'=>600.125,'net'=>580.100,'_workflow'=>['note'=>'إرجاع كشف الرواتب لاستكمال المستندات 164'],'input35'=>['commission_deduction_note36'=>'خصم عمولة بسبب مرتجع استثنائي 164'],'bank_account'=>'رقم حساب لا يترجم 164'];query('INSERT INTO ep_payroll VALUES(?)',[jencode($calc164)]);$expected[]=['record_text:payroll:note',$calc164['_workflow']['note']];
query('INSERT INTO ep_bank_payroll VALUES(?)',[jencode(['profile'=>['bank_name'=>'بنك إقليمي جديد 164','bank_account'=>'حساب سري 164'],'employee_name'=>'اسم هوية لا يترجم 164'])]);$expected[]=['bank_name','بنك إقليمي جديد 164'];
query('INSERT INTO ep_policies VALUES(?,?)',['نظام دوام فرع جديد 164',jencode(['attendance_days'=>5,'note'=>'ملاحظة سياسة حرة 164'])]);$expected[]=['policy_label164','نظام دوام فرع جديد 164'];$expected[]=['record_text:policy:note','ملاحظة سياسة حرة 164'];
query('INSERT INTO ep_companies VALUES(?,?,?)',['شركة جديدة 164','شركة جديدة قانونية 164','مصرف جديد للشركة 164']);$expected[]=['bank_name','مصرف جديد للشركة 164'];
$dept164=jencode(['ar'=>['title'=>'قسم إداري بدون إنجليزي 164'],'en'=>['title'=>'']]);query('INSERT INTO ep_customer_departments VALUES(?)',[$dept164]);$expected[]=['customer_department164','قسم إداري بدون إنجليزي 164'];
query('INSERT INTO ep_customer_departments VALUES(?)',[jencode(['ar'=>['title'=>'قسم مترجم رسميًا 164'],'en'=>['title'=>'Owner approved department']])]);
query('INSERT INTO ep_message_events VALUES(?,?,?)',[1,'متابعة داخلية لإغلاق حالة 164','status:closed']);$expected[]=['customer_internal_note162','متابعة داخلية لإغلاق حالة 164'];query('INSERT INTO ep_message_events VALUES(?,?,?)',[null,'رسالة عميل أصلية 164','received']);
query('INSERT INTO ep_records(id,module,title,body_json) VALUES(?,?,?,?)',[++$fid164,'notification_meta','notice:1',jencode(['sender'=>7,'type'=>'message'])]);query('INSERT INTO ep_notifications VALUES(?,?,?)',[1,'تعميم إداري خاص 164','نص إشعار مخصص جديد 164']);$expected[]=['notice_text:body','نص إشعار مخصص جديد 164'];
$business164=['ep_users','ep_records','ep_history','ep_companies','ep_notifications','ep_message_events','ep_payroll','ep_bank_payroll','ep_policies','ep_customer_departments'];$hashes164=[];foreach($business164 as $table)$hashes164[$table]=hash('sha256',jencode(query('SELECT * FROM '.$table)->fetchAll()));
$inventory164=translation_inventory31();$sources164=array_column($inventory164,'source');
foreach(['أصل تدقيق لا يترجم 164','قيمة قديمة للقراءة فقط 164','رقم حساب لا يترجم 164','حساب سري 164','اسم هوية لا يترجم 164','قسم مترجم رسميًا 164','رسالة عميل أصلية 164']as $excluded)ok160(!in_array($excluded,$sources164,true),'excluded identity/public/security/audit source');
for($round164=0;$round164<80;$round164++){$ready164=data_context_dictionary137();$missing164=array_filter($expected,fn($pair)=>!isset($ready164[$pair[0]][$pair[1]]));if(!$missing164)break;data_translation_process160();}
foreach($expected as [$context,$source])ok160(isset(data_context_dictionary137()[$context][$source]),'real queue publishes '.$context);
foreach($business164 as $table)ok160($hashes164[$table]===hash('sha256',jencode(query('SELECT * FROM '.$table)->fetchAll())),'business table unchanged '.$table);
$scope164=data_translation_scope160(['rows'=>[['calculation'=>$calc164,'inputs'=>$calc164['input35']]]],'pay37_sheet');ok160(isset($scope164['dictionary']['record_text:payroll:note'][$calc164['_workflow']['note']]),'normalized payroll workflow projection');ok160(isset($scope164['dictionary']['record_text:payroll_input35:commission_deduction_note36'][$calc164['input35']['commission_deduction_note36']]),'commission context stable across original and calculated rows');ok160(!isset($scope164['dictionary']['notice_text:body']),'payroll response does not include private notice');
$scope164=data_translation_scope160(['movements'=>[['kind'=>'debt_opening','name95'=>'سلفة شراء أجهزة 164 — debt_opening','reason'=>'سبب حركة مصرح 164']]],'v28_my_debt');ok160(isset($scope164['dictionary']['record_text:debt_opening:name']['سلفة شراء أجهزة 164']),'normalized debt name loses internal suffix only in display');
$scope164=data_translation_scope160([['title'=>'قرار: طلبات الموظفين','body'=>'نص طلب مصرح بعرضه 164 — approved — قيمة غير مرتبطة 164','meta'=>['type'=>'request']]],'notifications');ok160(isset($scope164['dictionary']['record_text:request:details']['نص طلب مصرح بعرضه 164']),'generated notice can project exact visible free-text fragment');ok160(!isset($scope164['dictionary']['notice_text:body']),'generated notice does not expose unrelated manual notice');

$long164='س'.str_repeat("'",14999);query('INSERT INTO ep_records(id,module,title,body_json) VALUES(?,?,?,?)',[9999,'video_receipt','video_receipt',jencode(['mode'=>'custom','text'=>$long164])]);$longId164=language_key31('record_text:video_receipt:text',$long164);for($tries164=0;$tries164<40&&!data_translation_ready160(setting31('data_translation31')[$longId164]??[]);$tries164++)data_translation_process160();ok160(data_translation_ready160(setting31('data_translation31')[$longId164]??[]),'maximum valid 15000-char receipt including XML escapes is translated, not stranded');
