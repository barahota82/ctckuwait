'use strict';
globalThis.clickScheduleCompanies38 = globalThis.clickScheduleCompanies38 || [];
function scheduleMeta38(row){return policyValues32(row)._schedule38??{kind:'legacy'};}
function scheduleForm38(row,lockedUser,management){
 const meta=scheduleMeta38(row),temporary=meta.kind==='temporary',edit=management?.mode==='edit';
 return '<label>'+esc(uiText('نوع الجدول'))+'<select name="schedule_kind" '+'disabled'+'><option value="basic">'+esc(uiText('دوام أساسي'))+'</option><option value="temporary" '+(temporary?'selected':'')+'>'+esc(uiText('دوام مؤقت'))+'</option></select></label><div id="scheduleEnd38">'+input('effective_to','تاريخ نهاية السريان',edit?row.effective_to??'':'','date')+'</div>'+(!Number(lockedUser)?'<div id="scheduleScope38"><label>'+esc(uiText('نطاق الجدول المؤقت'))+'<select name="schedule_scope" '+(edit?'disabled':'')+'><option value="all">'+esc(uiText('جميع الموظفين'))+'</option><option value="company" '+(meta.company_code?'selected':'')+'>'+esc(uiText('شركة محددة'))+'</option><option value="people">'+esc(uiText('موظفون محددون'))+'</option></select></label><div id="scheduleCompany38"><label>'+esc(uiText('الشركة'))+'<select name="schedule_company" '+(edit?'disabled':'')+'><option value="">'+esc(uiText('اختر الشركة'))+'</option>'+globalThis.clickScheduleCompanies38.map(c=>'<option value="'+esc(c.code)+'" '+(meta.company_code===c.code?'selected':'')+'>'+esc(c.code+' — '+c.name)+'</option>').join('')+'</select></label></div><div id="schedulePeople38"><label>'+esc(uiText('بحث بالاسم أو الكود'))+'<input id="schedulePeopleSearch38" type="search"></label><div class="schedule-people38">'+orderedStaff32(users).map(u=>'<label data-schedule-person38="'+u.id+'" class="check"><input type="checkbox" value="'+u.id+'" name="schedule_person38">'+esc((u.employee_code||u.username)+' — '+displayName31(u))+'</label>').join('')+'</div></div></div>':'');
}
function bindSchedule38(){
 const f=$('#modalForm'),kind=f.elements.namedItem('schedule_kind'),scope=f.elements.namedItem('schedule_scope'),end=f.elements.namedItem('effective_to');
 const sync=()=>{const temp=kind.value==='temporary';if($('#policyExtras38'))$('#policyExtras38').hidden=temp;for(const name of ['max_accuracy','location_max_age','photo_max_age']){const el=f.elements.namedItem(name);if(el)el.closest('label').hidden=temp;}$('#scheduleEnd38').hidden=!temp;end.disabled=!temp;end.required=temp;if(!temp)end.value='';const wrap=end.closest('.date-field32');if(wrap)wrap.querySelectorAll('input,button').forEach(e=>{e.disabled=!temp;if(e!==end&&e.tagName==='INPUT'){e.setCustomValidity('');e.value=dateDisplay32(end.value||'');}});
 if(scope){$('#scheduleScope38').hidden=!temp;$('#scheduleCompany38').hidden=scope.value!=='company';$('#schedulePeople38').hidden=scope.value!=='people';f.elements.namedItem('schedule_company').required=temp&&scope.value==='company';}
 };kind.addEventListener('change',sync);scope?.addEventListener('change',sync);sync();
 if($('#schedulePeopleSearch38'))$('#schedulePeopleSearch38').oninput=e=>{const q=normalize32(e.target.value);f.querySelectorAll('[data-schedule-person38]').forEach(n=>n.hidden=!normalize32(n.textContent).includes(q));};
}
function schedulePayload38(form){
 const kind=form.elements.namedItem('schedule_kind').value,scope=form.elements.namedItem('schedule_scope')?.value;
 return {schedule_kind:kind,effective_to:kind==='temporary'?form.elements.namedItem('effective_to').value:null,schedule_company:kind==='temporary'&&scope==='company'?form.elements.namedItem('schedule_company').value:'',schedule_users:kind==='temporary'&&scope==='people'?[...form.querySelectorAll('[name=schedule_person38]:checked')].map(e=>Number(e.value)):[],schedule_scope:scope??'employee'};
}

'use strict';
let policyDefaults32=null;
let policyRows32=[];
let policyTab32='staff';
function orderedStaff32(list){return [...list].filter(u=>u.role!=='admin').sort((a,b)=>String(a.employee_code||a.username||'').localeCompare(String(b.employee_code||b.username||''),'en',{numeric:true}));}
function staffHeading32(u){return '<summary><bdi class="leave-code32">'+esc(u.employee_code||u.username)+'</bdi><span class="leave-name32" data-name-ar="'+esc(u.name)+'" data-name-en="'+esc(u.profile?.name_en||u.name)+'" data-no-translate>'+esc(displayName31(u))+'</span></summary>';}
function field32(label,value){return '<div><strong>'+esc(uiText(label))+'</strong><div class="record-value32">'+esc(value??'—')+'</div></div>';}
function activePolicy32(rows,date){return [...rows].filter(r=>r.effective_from<=date&&(!r.effective_to||r.effective_to>=date)).sort((a,b)=>b.effective_from.localeCompare(a.effective_from)||Number(b.id)-Number(a.id))[0]||null;}
function policyValues32(row){try{return JSON.parse(row?.value_json||'{}');}catch(e){return {};}}
function scheduleFields32(p,locations){const loc=locations.find(l=>String(l.id)===String(p.location_id));let html=field32('نمط المواعيد',uiText(p.weekly_mode==='variable'?'مختلف حسب اليوم':'ثابت خلال الأسبوع'))+field32('الموقع المعتمد',loc?.name||uiText('غير محدد'));
 for(const day of week32){const entry=p.weekly?.[day]||{rest:!(p.weekdays||[]).includes(day),start:p.start,end:p.end};html+=field32(dayNames32[day],entry.rest?uiText('راحة أسبوعية'):(entry.start||'—')+' — '+(entry.end||'—'));}return html+field32('سماح التأخير الشهري بالدقائق',p.grace_minutes??'—')+field32('الاستراحة بالدقائق',p.break_minutes??'—')+field32('أقصى خطأ موقع بالمتر',p.max_accuracy??'—')+(p.punch_rules_from393?field32('بداية سريان قواعد الجزاء',dateDisplay32(p.punch_rules_from393)):'')+(p.punch_rules39?Object.entries(p.punch_rules39).filter(([t])=>t!=='presence').map(([t,r])=>field32(uiText(labels[t]),uiText(r.enabled?'مفعّل':'غير مفعّل')+(r.enabled?' · '+r.minutes+' '+uiText('دقيقة')+' · '+uiText(r.escalation?'تصعيد الجزاء عند التكرار':'دون تصعيد'):''))).join(''):field32('تطبيق جزاء نسيان البصمة',uiText(p.forget_enabled?'مفعّل':'غير مفعّل'))+(p.forget_enabled?field32('جزاء النسيان الأساسي بالدقائق',p.missing_minutes??'—'):''));}
async function policiesUnified32(expectedEpoch371=null){if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 if(expectedEpoch371!==null&&expectedEpoch371!==navigationEpoch32)return false;
 document.querySelector('#portalTabs32 [data-operational-controls68]')?.remove();
 const generalAllowed=boot.user.role==='admin'||teamPermission('policy_general'),staffAllowed=boot.user.role==='admin'||teamPermission('policy_staff');
 $('#page').innerHTML=head('الدوام والسياسات','كل تعديل يُحفظ كنسخة بتاريخ سريان؛ لا تُستبدل النسخة السابقة.')+'<nav class="portal-tabs32" role="tablist"><button role="tab" id="generalTab32">'+esc(uiText('القواعد والدوام الافتراضي'))+'</button><button role="tab" id="staffTab32">'+esc(uiText('دوام الموظفين'))+'</button><button role="tab" id="temporaryTab38">'+esc(uiText('الجداول المؤقتة'))+'</button></nav><section id="generalPanel32" role="tabpanel"></section><section id="staffPanel32" role="tabpanel"></section><section id="temporaryPanel38" role="tabpanel"></section>';
 const earlyTabs68=[['general','generalTab32','generalPanel32',generalAllowed],['staff','staffTab32','staffPanel32',staffAllowed],['temporary','temporaryTab38','temporaryPanel38',generalAllowed||staffAllowed]];
 const earlySelect68=t=>{if(!earlyTabs68.some(x=>x[0]===t&&x[3]))t=staffAllowed?'staff':'general';policyTab32=t;for(const [key,id,panel,allowed]of earlyTabs68){$('#'+id).hidden=!allowed;$('#'+id).setAttribute('aria-selected',String(t===key));$('#'+panel).hidden=t!==key||!allowed;}};
 for(const [key,id,panel]of earlyTabs68){$('#'+id).onclick=()=>{earlySelect68(key);if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();};$('#'+panel).innerHTML=loadingMarkup();}earlySelect68(policyTab32);
 $('#generalPanel32').innerHTML='<label class="staff-search32">'+esc(uiText('بحث في السجلات'))+'<input type="search" id="generalSearch69"></label>'+loadingMarkup();
 $('#staffPanel32').innerHTML='<label class="staff-search32">'+esc(uiText('بحث بالاسم أو الكود'))+'<input type="search" id="policySearch32" disabled></label>'+loadingMarkup();
 policySkeleton173();
 $('#temporaryPanel38').innerHTML='<label class="staff-search32">'+esc(uiText('بحث في السجلات'))+'<input type="search" id="temporarySearch38" disabled></label>'+loadingMarkup();
 if(policyCan32(0,'create'))$('#temporaryPanel38').insertAdjacentHTML('afterbegin','<button id="newTemporary38" class="primary" disabled>'+esc(uiText('إضافة جدول مؤقت'))+'</button>');
 if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();applyInterfaceLanguage();
 const epoch68=navigationEpoch32;
 const [rows,staff,context]=await Promise.all([api('policies'),staffAllowed?api('policy_staff_list'):Promise.resolve([]),api('policy_editor_context')]);if(view!=='policies'||navigationEpoch32!==epoch68||(expectedEpoch371!==null&&navigationEpoch32!==expectedEpoch371))return false;boot.settings.locations=context.locations;globalThis.clickScheduleCompanies38=context.companies||[];policyDefaults32=context.values;policyRows32=rows;if(staffAllowed)users=staff;
const today=dateNow(),general=rows.filter(r=>!Number(r.user_id)),currentGeneral=activePolicy32(general.filter(r=>scheduleMeta38(r).kind!=='temporary'),today),locations=boot.settings?.locations||[];

 const baseGeneral=general.filter(r=>scheduleMeta38(r).kind!=='temporary');
 const futureGeneral=baseGeneral.filter(r=>r.effective_from>today).sort((a,b)=>a.effective_from.localeCompare(b.effective_from)||Number(a.id)-Number(b.id));
 const pastGeneral=baseGeneral.filter(r=>r.effective_from<=today&&r.id!==currentGeneral?.id).sort((a,b)=>b.effective_from.localeCompare(a.effective_from)||Number(b.id)-Number(a.id));
 const generalDetails=r=>field32('اسم السياسة',r.label)+field32('بداية السريان',dateDisplay32(r.effective_from))+(r.effective_to?field32('نهاية السريان',dateDisplay32(r.effective_to)):'')+scheduleFields32(policyValues32(r),locations)+(typeof presenceSetup200==='function'?presenceGeneralSummary200(policyValues32(r)):'');
 const generalEdit=r=>(policyCan32(0,'edit')||policyCan32(0,'create')?'<div class="record-actions32"><button data-policy-manage32="'+r.id+'">'+esc(uiText('تعديل'))+'</button></div>':'');
 const generalRecord=r=>'<details class="record-group32" data-policy-period371="'+r.id+'"><summary>'+esc(r.label)+' — '+esc(dateDisplay32(r.effective_from))+'</summary><section class="record-details32">'+generalDetails(r)+generalEdit(r)+(policyCan32(0,'delete')?'<div class="record-actions32"><button class="danger" data-policy-delete32="'+r.id+'">'+esc(uiText('حذف'))+'</button></div>':'')+'</section></details>';
 $('#generalPanel32').innerHTML='<section class="card" id="currentGeneral384"><h2>'+esc(uiText('الإعداد العام الساري'))+'</h2><section class="record-details32">'+(currentGeneral?generalDetails(currentGeneral)+generalEdit(currentGeneral):scheduleFields32(context.values,locations)+(policyCan32(0,'create')?'<div class="record-actions32"><button id="newGeneral32">'+esc(uiText('إعداد القواعد العامة'))+'</button></div>':''))+'</section></section>'+(futureGeneral.length?'<section id="scheduledGeneral384"><h2>'+esc(uiText('تعديلات مجدولة'))+'</h2><div class="record-groups32">'+futureGeneral.map(generalRecord).join('')+'</div></section>':'')+(pastGeneral.length?'<details class="record-group32" data-general-history384="true"><summary>'+esc(uiText('سجل التغييرات'))+'</summary><div class="record-groups32">'+pastGeneral.map(generalRecord).join('')+'</div></details>':'');
 const people=orderedStaff32(staff);$('#staffPanel32').innerHTML='<label class="staff-search32">'+esc(uiText('بحث بالاسم أو الكود'))+'<input type="search" id="policySearch32"></label><div class="record-groups32">'+people.map(u=>{const own=rows.filter(r=>Number(r.user_id)===Number(u.id)),current=(Object.prototype.hasOwnProperty.call(u,'effective_policy_id38')?rows.find(r=>Number(r.id)===Number(u.effective_policy_id38)):activePolicy32(own,today)),p=u.effective_policy_values38||policyValues32(current||currentGeneral),exempt=!u.service?.attendance||!u.service?.employed,status=!u.service?.employed?'خارج فترة الخدمة':exempt?'لا يخضع للحضور والانصراف':current&&scheduleMeta38(current).kind==='temporary'?'دوام مؤقت':current&&Number(current.user_id)?'جدول خاص بالموظف':currentGeneral?'يتبع السياسة العامة':'لم يتم إعداد جدول دوام';
 return '<details class="record-group32" data-employee-id="'+u.id+'">'+staffHeading32(u)+'<section class="record-details32">'+field32('الحالة',uiText(status))+(u.service?.to?field32('تاريخ نهاية السريان',dateDisplay32(u.service.to)):'')+(!exempt&&current&&scheduleMeta38(current).kind==='temporary'?field32('اسم الجدول المؤقت',current.label)+field32('نهاية الجدول المؤقت',dateDisplay32(current.effective_to)):'')+(exempt?'':field32('بداية السريان',(current||currentGeneral)?.effective_from?dateDisplay32((current||currentGeneral).effective_from):'—')+scheduleFields32(p,locations))+(typeof presenceFields32==='function'?presenceFields32(u):'')+'<div class="record-actions32">'+(exempt?'':'<button data-weekly-edit32="'+u.id+'">'+esc(uiText('تعديل الدوام الأساسي وقواعد الموظف'))+'</button><button data-temporary38="'+u.id+'">'+esc(uiText('إضافة جدول مؤقت'))+'</button>')+'<button type="button" data-presence-log="'+u.id+'">'+esc(uiText('سجل الإرسال'))+'</button></div></section><div class="weekly-editor32"></div>'+(own.length?'<details class="policy-history32" data-policy-history371="'+u.id+'"><summary>'+esc(uiText('سجل تغييرات الدوام والسياسات'))+'</summary>'+own.map(r=>'<section class="record-details32">'+field32('بداية السريان',dateDisplay32(r.effective_from))+(r.effective_to?field32('نهاية السريان',dateDisplay32(r.effective_to)):'')+field32('نوع الجدول',uiText(scheduleMeta38(r).kind==='temporary'?'دوام مؤقت':'دوام أساسي'))+scheduleFields32(policyValues32(r),locations)+policyActions32(r)+'</section>').join('')+'</details>':'')+'</details>';}).join('')+'</div>';
 const temporaryAllowed=generalAllowed||staffAllowed;
 const tempRows=rows.filter(r=>scheduleMeta38(r).kind==='temporary'&&(Number(r.user_id)?staffAllowed:generalAllowed));
 $('#temporaryPanel38').innerHTML=(policyCan32(0,'create')?'<button id="newTemporary38" class="primary">'+esc(uiText('إضافة جدول مؤقت'))+'</button>':'')+'<label class="staff-search32">'+esc(uiText('بحث في السجلات'))+'<input type="search" id="temporarySearch38"></label><div class="record-groups32">'+tempRows.map(r=>{const u=people.find(u=>Number(u.id)===Number(r.user_id)),meta=scheduleMeta38(r),company=globalThis.clickScheduleCompanies38.find(c=>c.code===meta.company_code),scope=Number(r.user_id)?(u?((u.employee_code||u.username)+' — '+displayName31(u)):uiText('موظف')):meta.company_code?(company?.name||meta.company_code):uiText('جميع الموظفين');return '<details class="record-group32" '+(Number(r.user_id)?'data-employee-id="'+Number(r.user_id)+'" ':'')+'data-policy-period371="'+r.id+'"><summary>'+esc(r.label)+' — '+esc(scope)+' — '+esc(dateDisplay32(r.effective_from))+' / '+esc(dateDisplay32(r.effective_to))+'</summary><section class="record-details32">'+field32('نطاق الجدول المؤقت',scope)+field32('بداية السريان',dateDisplay32(r.effective_from))+field32('نهاية الجدول المؤقت',dateDisplay32(r.effective_to))+scheduleFields32(policyValues32(r),locations)+policyActions32(r)+'</section></details>';}).join('')+'</div>'+(tempRows.length?'':'<p>'+esc(uiText('لا توجد نتائج'))+'</p>');
 if(!$('#generalSearch69'))$('#generalPanel32').insertAdjacentHTML('afterbegin','<label class="staff-search32">'+esc(uiText('بحث في السجلات'))+'<input type="search" id="generalSearch69"></label>');
 $('#generalSearch69').oninput=e=>{const q=normalize32(e.target.value);$('#generalPanel32').querySelectorAll('.record-groups32>details').forEach(d=>d.hidden=!normalize32(d.textContent).includes(q));};
 if(hubFor32('policies'))document.querySelectorAll('#page #newTemporary38').forEach(n=>n.remove());
 if($('#newTemporary38'))$('#newTemporary38').disabled=false;
 if(hubFor32('policies'))document.querySelectorAll('#page #policySearch32,#page #temporarySearch38').forEach(n=>n.closest('label').remove());
 for(const id of ['policySearch32','temporarySearch38'])$('#'+id).disabled=false;
 const tabs=[['general','generalTab32','generalPanel32',generalAllowed],['staff','staffTab32','staffPanel32',staffAllowed],['temporary','temporaryTab38','temporaryPanel38',temporaryAllowed]];
 const tab=t=>{if(!tabs.some(x=>x[0]===t&&x[3]))t=staffAllowed?'staff':'general';policyTab32=t;for(const [key,btn,panel,allowed]of tabs){$('#'+panel).hidden=t!==key||!allowed;$('#'+btn).hidden=!allowed;$('#'+btn).setAttribute('aria-selected',String(t===key));$('#'+btn).classList.toggle('primary',t===key);}};
 for(const [key,btn]of tabs)$('#'+btn).onclick=()=>{tab(key);if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();if(typeof applyEmployeeCards73==='function')applyEmployeeCards73();};tab(policyTab32);
 if($('#newTemporary38'))$('#newTemporary38').onclick=()=>policyModal({label:uiText('جدول مؤقت'),value_json:JSON.stringify({...policyValues32(currentGeneral),...(!currentGeneral?context.values:{}),_schedule38:{kind:'temporary'}})},0);
 $('#temporarySearch38').oninput=e=>{const q=normalize32(e.target.value);$('#temporaryPanel38').querySelectorAll('.record-groups32>details').forEach(d=>d.hidden=!normalize32(d.textContent).includes(q));};
 document.querySelectorAll('[data-weekly-edit32],[data-staff-policy32]').forEach(b=>b.hidden=!policyCan32(1,'create')&&!policyCan32(1,'edit'));
 if(!generalAllowed)$('#generalPanel32').replaceChildren();
 bindPolicyActions32();
 if($('#newGeneral32'))$('#newGeneral32').onclick=()=>policyModal({label:uiText('القواعد والدوام الافتراضي'),value_json:JSON.stringify(currentGeneral?policyValues32(currentGeneral):context.values)},0);
 $('#policySearch32').oninput=e=>{const q=normalize32(e.target.value);$('#staffPanel32').querySelectorAll('.record-groups32>details').forEach(d=>d.hidden=!normalize32(d.querySelector('summary').textContent).includes(q));};
 document.querySelectorAll('[data-temporary38]').forEach(b=>{b.hidden=!policyCan32(1,'create');b.onclick=()=>{const id=Number(b.dataset.temporary38),base=activePolicy32(rows.filter(r=>Number(r.user_id)===id&&scheduleMeta38(r).kind!=='temporary'),today)||currentGeneral;policyModal({user_id:id,label:'جدول مؤقت',value_json:JSON.stringify({...policyValues32(base),_schedule38:{kind:'temporary'}})},id);};});
 document.querySelectorAll('[data-weekly-edit32],[data-staff-policy32]').forEach(b=>b.onclick=async()=>{const id=Number(b.dataset.weeklyEdit32||b.dataset.staffPolicy32),r=activePolicy32(rows.filter(r=>Number(r.user_id)===id&&scheduleMeta38(r).kind!=='temporary'),today);if(r){await managePolicy32(r.id,false,false);return;}policyModal({user_id:id,label:'سياسة موظف',value_json:currentGeneral?.value_json||JSON.stringify(context.values)},id);});
 if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();
 policyWorkspace173();
}
function flatDetails32(data){const box=document.createElement('div');box.innerHTML=detail26(data);return [...box.querySelectorAll('dt')].map(dt=>field32(dt.textContent,dt.nextElementSibling?.textContent)).join('');}
function eosPayForm200(target,id){target.innerHTML='<form class="eos-pay200 record-details32"><p>'+esc(uiText('تسوية معتمدة'))+' · '+esc(String(id))+'</p><input type="hidden" name="id" value="'+Number(id)+'"><label>'+esc(uiText('تاريخ الصرف'))+'<input name="date" type="date" value="'+dateNow()+'" required></label><label>'+esc(uiText('المبلغ د.ك'))+'<input name="amount" type="number" min="0.001" step="0.001" required></label><label>'+esc(uiText('مرجع الصرف'))+'<input name="reference" required></label><label>'+esc(uiText('وسيلة الصرف'))+'<select name="method"><option value="cash">'+esc(uiText('نقدي'))+'</option><option value="bank">'+esc(uiText('تحويل بنك'))+'</option><option value="bank_salary">'+esc(uiText('مع الراتب البنكي'))+'</option><option value="bank_separate">'+esc(uiText('تحويل بنكي مستقل'))+'</option></select></label><div class="record-actions32"><button type="submit" class="primary">'+esc(uiText('تأكيد الصرف'))+'</button></div></form>';const form=target.querySelector('form');form.onsubmit=async e=>{e.preventDefault();const button=form.querySelector('[type=submit]');button.disabled=true;const data=Object.fromEntries(new FormData(form));try{const saved=await api('v26_payment',{kind:'eos',id:data.id,date:data.date,amount:data.amount,reference:data.reference,method:data.method});toast(uiText('تم تسجيل الصرف'));form.replaceWith(document.createTextNode(uiText('تم الصرف')));}catch(error){toast(error.message);button.disabled=false;}};}
async function eosUnified32(){if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 const admin=boot.user.role==='admin',can=op=>admin||(typeof teamPermission==='function'&&teamPermission('eos',op));
 if(!can('view'))throw Error(uiText('هذه العملية غير مسموحة بصلاحيات حسابك.'));
 const epoch=navigationEpoch32;
 const staff=orderedStaff32(await api('user_choices29',null,{screen:'eos'}));
 if(epoch!==navigationEpoch32)return;
 let settlements=[];
 try{const info=await api('v26_info',null,{kind:'eos_settlement'});if(epoch!==navigationEpoch32)return;settlements=(info.records||[]).filter(r=>r.status==='approved');}
 catch(error){if(epoch!==navigationEpoch32||error?.stale32)return;toast(error.message);}
 const preset=typeof employeeValue73==='function'?Number(employeeValue73('eos'))||0:0;
 const options=staff.map(u=>employeeOption143(u.id,u.name,u.employee_code||'',preset===Number(u.id),u)).join('');
 $('#page').innerHTML=head('نهاية الخدمة')+'<section class="card group-calculator179"><h2>'+esc(uiText('حساب نهاية الخدمة'))+'</h2><label>'+esc(uiText('الموظف'))+'<select id="eosEmployee200"><option value="">'+esc(uiText('اختر موظفًا'))+'</option>'+options+'</select></label><form id="eosCalc200" hidden><input type="hidden" name="user_id"><label>'+esc(uiText('تاريخ الحساب'))+'<input name="date" type="date" value="'+dateNow()+'" required></label><label>'+esc(uiText('سبب الحساب'))+'<select name="reason"><option value="estimate">'+esc(uiText('تقدير كامل'))+'</option><option value="resignation">'+esc(uiText('استقالة عقد غير محدد'))+'</option></select></label><div class="record-actions32"><button type="submit">'+esc(uiText('حساب'))+'</button></div></form><div id="eosResult200"></div></section><section class="card"><h2>'+esc(uiText('التسويات المعتمدة'))+'</h2><div id="eosSettlements200"></div></section>';
 const select=$('#eosEmployee200'),form=$('#eosCalc200'),result=$('#eosResult200');
 if(typeof enhanceSelect32==='function')document.querySelectorAll('#page select').forEach(enhanceSelect32);
 if(typeof enhanceDate32==='function')document.querySelectorAll('#page input[type=date]').forEach(enhanceDate32);
 let seq=0;
 const draw=()=>{
  const id=Number(select.value)||0;form.hidden=!id;if(id)form.elements.user_id.value=String(id);else result.replaceChildren();
  const rows=settlements.filter(r=>!id||Number(r.user_id)===id),host=$('#eosSettlements200');
  if(!rows.length){host.innerHTML='<p>'+esc(uiText('لا توجد تسوية معتمدة في هذا النطاق.'))+'</p>';return;}
  host.innerHTML=rows.map(r=>'<section class="record-details32" data-record-id="'+Number(r.id)+'">'+field32('الموظف',r.name||r.user_id)+field32('التاريخ',r.date_from||'')+field32('المبلغ د.ك',typeof money26==='function'?money26(r.amount):r.amount)+field32('الحالة',uiText((typeof labels!=='undefined'&&labels[r.status])||r.status))+'<div data-eos-pay="'+Number(r.id)+'"></div></section>').join('');
  if(can('pay'))rows.forEach(r=>{const box=host.querySelector('[data-eos-pay="'+Number(r.id)+'"]');if(box)eosPayForm200(box,r.id);});
  if(typeof applyInterfaceLanguage==='function')applyInterfaceLanguage();
 };
 select.onchange=()=>{seq++;result.replaceChildren();if(typeof employeeSelections73!=='undefined'&&typeof employeeGroup73==='function')employeeSelections73.set(employeeGroup73('eos'),Number(select.value)||0);draw();};
 form.addEventListener('input',()=>{seq++;result.replaceChildren();});
 form.onsubmit=async e=>{e.preventDefault();const ticket=++seq,button=form.querySelector('[type=submit]'),params=Object.fromEntries(new FormData(form));button.disabled=true;
  try{const calculated=await api('v26_eos',null,params);if(ticket!==seq||!form.isConnected||epoch!==navigationEpoch32)return;
   result.innerHTML=(calculated.review_required?'<p class="error">'+esc(uiText('حدد مقسوم أجر اليوم واعتمد قواعد نهاية الخدمة من إعدادات الإدارة قبل التسوية النهائية.'))+'</p>':'')+'<section class="record-details32">'+flatDetails32(calculated)+(calculated.review_required||!can('review')?'':'<div class="record-actions32"><button type="button" class="primary" data-eos-settle32>'+esc(uiText('اعتماد تسوية نهاية الخدمة'))+'</button></div>')+'</section>';
   const settle=result.querySelector('[data-eos-settle32]');if(settle)settle.onclick=async()=>{if(settle.dataset.busy200)return;const note=prompt(uiText('سبب التسوية وملاحظات مراجعتها'));if(!note)return;const employeeId=Number(params.user_id),employeeName=[...select.options].find(option=>Number(option.value)===employeeId)?.textContent||'',listHost=document.getElementById('eosSettlements200'),screenEpoch=epoch;settle.dataset.busy200='1';settle.disabled=true;select.disabled=true;let savedOk=false;try{const saved=await api('v26_eos_settle',{...params,confirm:true,note});savedOk=true;if(screenEpoch!==navigationEpoch32||!listHost?.isConnected)return;if(saved?.id&&!settlements.some(row=>Number(row.id)===Number(saved.id)))settlements.unshift({id:saved.id,user_id:employeeId,status:'approved',module:'eos_settlement',date_from:params.date,amount:saved.amount??calculated.net_estimate,name:employeeName});try{draw();toast(uiText('تم اعتماد التسوية'));}catch(error){toast(uiText('تم الحفظ، وتعذر تحديث الشاشة.'));}}catch(error){if(screenEpoch!==navigationEpoch32||!listHost?.isConnected||error?.stale32)return;toast(error.message);}finally{if(select.isConnected&&screenEpoch===navigationEpoch32)select.disabled=false;if(!savedOk&&settle.isConnected){settle.disabled=false;delete settle.dataset.busy200;}}};
   if(typeof applyInterfaceLanguage==='function')applyInterfaceLanguage();
  }catch(error){if(ticket===seq&&!error?.stale32)toast(error.message);}finally{if(button.isConnected)button.disabled=false;}};
 if(preset)select.value=String(preset);
 draw();
}

function debtRecord32(r,companies){
 const v=JSON.parse(r.body_json||'{}'),company=companies.find(c=>c.code===v.company_code);
 return '<section class="record-details32" data-record-id="'+Number(r.id)+'">'+field32('اسم السجل',v.name??r.title)+field32('الشركة',company?.name||v.company_code||'—')+field32('البداية',v.incomplete_dates?uiText('غير مستكمل'):dateDisplay32(r.date_from))+field32('النهاية',r.date_to?dateDisplay32(r.date_to):'—')+field32('المبلغ د.ك',money26(r.amount))+field32('الحالة',uiText(r.status==='archived'?'مؤرشف':labels[r.status]||r.status))+(v.reference?field32('مرجع الصرف',v.reference):'')+(v.reason?field32('ملاحظات',v.reason):'')+(v.update_reason?field32('سبب التحديث',v.update_reason):'')+(r.review_note?field32('ملاحظات المراجعة',r.review_note):'')+(r.file_id?'<div class="record-actions32"><a class="button" target="_blank" rel="noopener" href="file.php?id='+Number(r.file_id)+'">'+esc(uiText('فتح / تنزيل'))+'</a></div>':'')+'</section>';
}

function nextPolicyDate32(){const d=new Date(dateNow()+'T12:00:00Z');d.setUTCDate(d.getUTCDate()+1);return d.toISOString().slice(0,10);}
function policyCan32(uid,op){return boot.user.role==='admin'||teamPermission(uid?'policy_staff':'policy_general',op);}
function policyActions32(r){const uid=Number(r.user_id||0);return '<div class="record-actions32">'+(policyCan32(uid,'edit')||policyCan32(uid,'create')?'<button data-policy-manage32="'+r.id+'">'+esc(uiText('تعديل'))+'</button>':'')+(policyCan32(uid,'create')?'<button data-policy-copy32="'+r.id+'">'+esc(uiText('نسخ لتعديل جديد'))+'</button>':'')+(policyCan32(uid,'delete')?'<button class="danger" data-policy-delete32="'+r.id+'">'+esc(uiText('حذف'))+'</button>':'')+'</div>';}
function bindPolicyActions32(){document.querySelectorAll('[data-policy-manage32],[data-policy-delete32],[data-policy-copy32]').forEach(b=>b.onclick=async()=>{b.disabled=true;try{await managePolicy32(b.dataset.policyManage32||b.dataset.policyDelete32||b.dataset.policyCopy32,!!b.dataset.policyDelete32,!!b.dataset.policyCopy32);}catch(e){toast(e.message);}finally{b.disabled=false;}});}
async function managePolicy32(id,del=false,copy=false){
 const screenState371=policyScreenState371();
 const x=await api('policy_manage_preview',null,{id}),i=x.impact,uid=Number(x.policy.user_id||0),mode=copy||i.used?'version':'edit';
 if(scheduleMeta38(x.policy).kind==='temporary'&&i.used&&!copy&&!del){toast('الجدول المؤقت مستخدم؛ أضف فترة جديدة غير متداخلة.');return;}
 if(scheduleMeta38(x.policy).kind==='temporary'&&copy){policyModal({...x.policy,id:null,effective_from:null,effective_to:null},uid);return;}
 if(del&&i.used){toast('السياسة مستخدمة؛ لا يمكن حذفها. استخدم تعديل بتاريخ سريان جديد.');return;}
 if(!del&&!policyCan32(uid,mode==='version'?'create':'edit')){toast('هذه العملية غير مسموحة بصلاحيات حسابك.');return;}
 if(copy){policyModal(x.policy,uid,{mode:'version',fingerprint:i.fingerprint,presence_form:x.presence_form});return;}
 modal(del?'مراجعة حذف السياسة':'مراجعة تعديل السياسة','<section class="card"><h3>'+esc(x.policy.label)+'</h3>'+field32('بداية السريان',dateDisplay32(i.from))+(i.to?field32('نهاية السريان',dateDisplay32(i.to)):'')+field32('بصمات مرتبطة',i.events)+field32('كشوف رواتب بالفترة',i.payroll)+'<details><summary>'+esc(uiText('الموظفون المحتمل تأثرهم'))+'</summary>'+i.staff.map(u=>'<p>'+esc(u.employee_code+' — '+u.name)+'</p>').join('')+'</details>'+(del?'<h3>'+esc(uiText('السياسات البديلة حسب تاريخ السريان'))+'</h3>'+x.alternatives.map(a=>'<p>'+esc(a.label+' · '+dateDisplay32(a.effective_from)+' — '+(a.effective_to?dateDisplay32(a.effective_to):uiText('مستمرة')))+'</p>').join('')+'<p>'+esc(uiText('بعد الحذف تُطبق السياسة الأخرى السارية أو إعدادات النظام؛ راجع المواعيد قبل التأكيد.'))+'</p><form id="policyDeleteForm32">'+input('reason','سبب الحذف','','text','required')+check('confirm_fallback','راجعت السياسة البديلة وتأثير الحذف',false)+saveButton()+'</form>':'<p>'+esc(uiText(i.used?'التعديل يحفظ نسخة جديدة من يوم قادم؛ السجلات السابقة لا تتغير.':'يمكن تصحيح السياسة غير المستخدمة مع حفظ سجل التعديل.'))+'</p><button id="policyContinue32">'+esc(uiText('متابعة التعديل'))+'</button>')+'</section>');
 if(del)$('#policyDeleteForm32').onsubmit=async e=>{e.preventDefault();if(e.target.dataset.saving173)return;try{await policyDelete173(e.target,{id:x.policy.id,fingerprint:i.fingerprint,reason:typeof employeeDataOriginal137==='function'?employeeDataOriginal137(e.target.elements.reason):e.target.reason.value,...(e.target.dataset.recordPairs173?{_edit170:recordMeta173(e.target)}:{}),confirm_fallback:e.target.confirm_fallback.checked});$('#modal').close();await refreshPolicy371(screenState371);}catch(err){policyError371(err.message,e.target);}};
 if(del&&uid)recordBind173($('#policyDeleteForm32'),'policy',['reason']);
 else if(!del)$('#policyContinue32').onclick=()=>policyModal(x.policy,uid,{mode,fingerprint:i.fingerprint,presence_form:x.presence_form});applyInterfaceLanguage();
}

function nextDayPolicy32(date){const d=new Date(date+'T12:00:00Z');d.setUTCDate(d.getUTCDate()+1);return d.toISOString().slice(0,10);}
function policyWeeklyFields32(p){return '<label>'+esc(uiText('نمط المواعيد'))+'<select name="weekly_mode"><option value="fixed">'+esc(uiText('ثابت خلال الأسبوع'))+'</option><option value="variable" '+(p.weekly_mode==='variable'?'selected':'')+'>'+esc(uiText('مختلف حسب اليوم'))+'</option></select></label><label>'+esc(uiText('الموقع المعتمد'))+'<select name="location_id"><option value="">'+esc(uiText('غير محدد'))+'</option>'+(boot.settings.locations||[]).filter(l=>(l.place||'work')!=='court'||String(l.id)===String(p.location_id)).map(l=>'<option value="'+esc(l.id)+'" '+(String(l.id)===String(p.location_id)?'selected':'')+'>'+esc(l.name)+(l.place==='court'?' — '+esc(uiText('محكمة')):'')+(l.active===false?' — '+esc(uiText('غير نشط')):'')+'</option>').join('')+'</select></label><div id="policyDays32">'+[6,0,1,2,3,4,5].map(i=>'<fieldset><legend>'+esc(uiText(dayNames32[i]))+'</legend>'+input('day_start'+i,'الحضور',p.weekly?.[i]?.start||p.start,'time')+input('day_end'+i,'الانصراف',p.weekly?.[i]?.end||p.end,'time')+'</fieldset>').join('')+'</div>';}
function bindPolicyWeekly32(){const f=$('#modalForm'),mode=f.elements.namedItem('weekly_mode');const sync=()=>{$('#policyDays32').hidden=mode.value!=='variable';for(let i=0;i<7;i++){const work=f.elements.namedItem('weekday'+i).checked;for(const prefix of ['day_start','day_end']){const n=f.elements.namedItem(prefix+i);n.required=mode.value==='variable'&&work;n.disabled=!work;}}};mode.onchange=sync;for(let i=0;i<7;i++)f.elements.namedItem('weekday'+i).addEventListener('change',sync);sync();}

function bindForgetPolicy36(){
 const f=document.querySelector('#modalForm');if(!f)return;
 const base=f.querySelector('[name="forget_enabled"]'),escalation=f.querySelector('[name="forget_escalation_enabled"]');if(!base||!escalation)return;
 const sync=()=>{const basic=f.querySelector('#forgetBase36'),tiers=f.querySelector('#forgetEscalation36');basic.hidden=!base.checked;basic.querySelectorAll('input').forEach(el=>el.disabled=!base.checked);escalation.disabled=!base.checked;tiers.hidden=!base.checked||!escalation.checked;tiers.querySelectorAll('input,select,textarea').forEach(el=>el.disabled=tiers.hidden);};
 base.addEventListener('change',sync);escalation.addEventListener('change',sync);sync();
}

function bindCapturePolicy361(){
 const f=$('#modalForm');if(!f)return;
 const target=f.querySelector('[name="user_id"]');
 const sync=()=>{
  const id=Number(target?.value??0),staff=users.find(u=>Number(u.id)===id);
  const state=staff?.service??{},type=state.staff_type??staff?.staff_type;
  const exempt=state.attendance===false||state.employed===false;
  for(const key of ['office_photo','rep_photo','rep_evidence','rep_morning_external']){
   const field=f.querySelector('[name="'+key+'"]');if(!field)continue;
   const show=!id||(!exempt&&(key==='office_photo'?type!=='rep':type==='rep'));
   field.closest('label').hidden=!show;field.disabled=!show;
  }
 };
 target?.addEventListener('change',sync);sync();
}

// Keep the employee, expanded records, search and viewport after a policy save.
function policyScreenState371(){
 const page=$('#page');if(!page?.querySelector('#policySearch32'))return null;
 const keys=['data-employee-id','data-policy-period371','data-policy-history371','data-general-history384'];
 const open=[];for(const d of page.querySelectorAll('details[open]'))for(const k of keys)if(d.hasAttribute(k))open.push([k,d.getAttribute(k)]);
 const anchor=[...page.querySelectorAll('#staffPanel32 details[data-employee-id]')].find(d=>!d.hidden&&d.getBoundingClientRect().bottom>0);
 return {tab:policyTab32,generalSearch:$('#generalSearch69')?.value||'',search:$('#policySearch32').value,temporarySearch:$('#temporarySearch38')?.value||'',open,x:window.scrollX,y:window.scrollY,anchor:anchor?.dataset.employeeId,top:anchor?.getBoundingClientRect().top};
}
async function refreshPolicy371(state,notify=true){
 if(view!=='policies'){if(notify)toast('تم الحفظ');return;}
 if(state)policyTab32=state.tab;
 const epoch=navigationEpoch32;if(await policiesUnified32(epoch)===false)return;applyInterfaceLanguage();
 if(state){for(const [key,id]of state.open){const node=$('#page').querySelector('['+key+'="'+id+'"]');if(node)node.open=true;}
 const generalSearch=$('#generalSearch69');if(generalSearch){generalSearch.value=state.generalSearch||'';generalSearch.oninput({target:generalSearch});}
 const search=$('#policySearch32');search.value=state.search;search.oninput({target:search});const tempSearch=$('#temporarySearch38');if(tempSearch){tempSearch.value=state.temporarySearch||'';tempSearch.oninput({target:tempSearch});}
 await new Promise(resolve=>requestAnimationFrame(resolve));
 const anchor=state.anchor?$('#page').querySelector('[data-employee-id="'+state.anchor+'"]'):null;
 window.scrollTo({left:state.x,top:anchor?window.scrollY+anchor.getBoundingClientRect().top-state.top:state.y,behavior:'instant'});
 }
 if(notify)toast('تم الحفظ');
}
function policyClearError371(form){form.querySelector('[data-policy-error371]')?.remove();form.querySelectorAll('[data-policy-invalid371]').forEach(e=>{e.removeAttribute('aria-invalid');e.removeAttribute('data-policy-invalid371');});}
function policyError371(message,form){
 policyClearError371(form);const box=document.createElement('div');box.className='error policy-error371';box.dataset.policyError371='';box.setAttribute('role','alert');box.setAttribute('tabindex','-1');box.textContent=uiText(message);
 const end=form.querySelector('#scheduleEnd38,.policy-end371');if(end&&/فترة|فترات|سريان|تاريخ|تتعارض|سياسة لاحقة/.test(message)){end.after(box);for(const name of ['effective_from','effective_to']){const e=form.elements.namedItem(name);if(e&&!e.disabled){e.setAttribute('aria-invalid','true');e.dataset.policyInvalid371='';}}}else form.prepend(box);
 box.focus({preventScroll:true});box.scrollIntoView({block:'nearest',behavior:'instant'});
}
