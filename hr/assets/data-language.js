'use strict';
const hrTextFields162=["department", "job_title", "additional_job", "address", "bank_name", "occupation", "note", "notes", "reason", "description", "stage", "checklist", "details", "name", "document_type", "on_behalf_reason", "leave_type", "asset_type", "handover", "criteria", "strengths", "improvement", "customer_area", "request_type", "decision_type", "employee_response", "_employee_reply", "_revision_details", "late_reason", "review_note", "calculation", "contract", "change_reason", "revision_note", "cancel_reason", "_cancel_reason", "update_reason", "objection", "commission_deduction_note36", "request_reason", "text", "evidence_note"];
const dataLabels31=new Map();
function collectDataLabels31(value){
 if(!value||typeof value!=='object')return;
 if(typeof value.bank_name==='string'&&value.bank_name.includes('COMMERCAIL'))dataLabels31.set(value.bank_name,value.bank_name.replaceAll('COMMERCAIL','COMMERCIAL'));
 collectEmployeeNames143(value);
 for(const child of Object.values(value))if(child&&typeof child==='object')collectDataLabels31(child);
}
async function dataTranslationWorker31(){return;} // Compatibility hook: translation happens on save only.
function dataPublicMap160(contexts){
 const terms=new Map();for(const context of ['department','job_title','company_name','company_legal','group_name','group_description','location','policy_label164'])for(const [source,target]of Object.entries(contexts?.[context]||{})){const choices=terms.get(source)||new Set();choices.add(target);terms.set(source,choices);}
 return new Map([...terms].filter(([,targets])=>targets.size===1).map(([source,targets])=>[source,[...targets][0]]));
}
function mergeHRLocale160(payload,target=boot){
 if(!target?.localization||!payload)return;const l=target.localization;l.data_context137??={};l.data_states160??={};
 const oldPublic=dataPublicMap160(l.data_context137),next={...l.data_context137};for(const [context,terms]of Object.entries(payload.dictionary||{}))next[context]={...(next[context]||{}),...terms};l.data_context137=next;const publicNow=dataPublicMap160(next),dictionary={...(l.dictionary||{})};for(const [source,prior]of oldPublic)if(!publicNow.has(source)&&dictionary[source]===prior)delete dictionary[source];for(const [source,text]of publicNow)if(!Object.prototype.hasOwnProperty.call(dictionary,source)||dictionary[source]===oldPublic.get(source))dictionary[source]=text;l.dictionary=dictionary;
 for(const [context,terms]of Object.entries(payload.states||{}))Object.assign(l.data_states160[context]??={},terms);
}
function dataDisplayAny160(source){
 source=String(source??'');if(boot?.localization?.language!=='en')return source;const targets=new Set();
 for(const terms of Object.values(boot.localization.data_context137||{})){const target=terms[source]||terms[source.trim()];if(target)targets.add(target);}
 return targets.size===1?[...targets][0]:source;
}
async function dataTranslationsView31(){
 let info;try{info=await api('translation31_list');}catch(error){toast(error.message||uiText('تعذر فتح شاشة ترجمة البيانات'));return;}const ui=s=>esc(uiText(s));
 const contexts={department:'القسم',job_title:'الوظيفة',company_name:'اسم الشركة',company_legal:'الاسم القانوني',group_name:'اسم المجموعة',group_description:'وصف المجموعة',location:'موقع الدوام',policy_label164:'اسم سياسة الدوام',customer_internal_note162:'ملاحظة متابعة داخلية',customer_department164:'قسم خدمة العملاء',additional_job:'وظيفة إضافية',address:'العنوان',bank_name:'اسم البنك',occupation:'المهنة',salary_note160:'سبب تغيير الراتب',allowance_note160:'ملاحظات ملحقات الأجر',permit_note159:'ملاحظات إذن العمل'};
 $('#page').innerHTML=head('ترجمة بيانات الإدارة والموظفين','الترجمة تلقائية؛ هذه الشاشة للمراجعة والتصحيح الاختياري.')+'<section class="card"><button id="backLanguages31">'+ui('الرجوع إلى اللغات')+'</button><p id="dataProgress31">'+ui('ترجمة Google تعمل تلقائيًا عند الحفظ دون مفتاح API.')+'</p></section><section class="card translation-review31"><div class="toolbar"><input id="translationSearch31" type="search" placeholder="'+ui('بحث')+'"><label class="check"><input id="allTranslations31" type="checkbox">'+ui('تحديد النتائج الظاهرة')+'</label></div><form id="dataReview31"><div id="translationRows31"></div><label class="check"><input name="reviewed" type="checkbox" required>'+ui('راجعت النصوص المحددة وأوافق على اعتمادها')+'</label><button class="primary">'+ui('حفظ واعتماد المحدد')+'</button></form></section>';
 const draft=new Map(info.items.map(r=>[r.id,{...r,selected:false}]));
 const render=()=>{const q=$('#translationSearch31').value.trim().toLowerCase(),rows=[...draft.values()].filter(r=>(r.source+' '+r.target).toLowerCase().includes(q));$('#translationRows31').innerHTML=rows.map(r=>'<article class="card" data-row31="'+r.id+'"><label class="check"><input data-select31="'+r.id+'" type="checkbox" '+(r.selected?'checked':'')+'>'+ui(contexts[r.context]??(r.context.startsWith('record_title:')?'عنوان السجل':r.context.startsWith('record_text:')?'نص السجل':r.context.startsWith('notice_text:')?'نص الإشعار':r.context))+' · '+ui({running:'جارٍ الترجمة',translated:'مترجمة آليًا',superseded:'تغير الأصل',pending:'بانتظار الترجمة',review:'تحتاج مراجعة',approved:'معتمدة',failed:'تعذر التوليد'}[r.status]??r.status)+'</label><p data-no-translate dir="rtl">'+esc(r.source)+'</p><textarea data-target31="'+r.id+'" data-no-translate lang="en" dir="ltr">'+esc(r.target)+'</textarea>'+(r.error?'<p>'+esc(hrTranslationError160(r.error))+'</p>':'')+'</article>').join('')||empty();document.querySelectorAll('[data-target31]').forEach(n=>n.oninput=()=>{draft.get(n.dataset.target31).target=n.value;});document.querySelectorAll('[data-select31]').forEach(n=>n.onchange=()=>{draft.get(n.dataset.select31).selected=n.checked;sync();});function sync(){const n=rows.filter(r=>r.selected).length;$('#allTranslations31').checked=rows.length>0&&n===rows.length;$('#allTranslations31').indeterminate=n>0&&n<rows.length;}$('#allTranslations31').onchange=e=>{rows.forEach(r=>r.selected=e.target.checked);render();};sync();};render();
 $('#translationSearch31').oninput=render;$('#backLanguages31').onclick=languagesView;
 $('#dataReview31').onsubmit=async e=>{e.preventDefault();const items=[...draft.values()].filter(r=>r.selected).map(({id,source,target})=>({id,source,target}));try{await api('translation31_approve',{items,reviewed:e.target.reviewed.checked,revision:info.revision});await loadBoot();await dataTranslationsView31();toast('تم اعتماد الترجمات');}catch(err){toast(err.message);}};
 installInterfaceLanguage();
}

function displayName31(person){return boot?.localization?.language==='en'?(person?.profile?.name_en||person?.name_en||person?.name||''):(person?.name||'');}

// V137: contextual data display never overwrites the original form value on an unchanged save.
function dataText137(context,source){
 source=String(source??'');if(boot?.localization?.language!=='en')return source;
 const terms=boot.localization.data_context137?.[context]||{},exact=terms[source]||terms[source.trim()];if(exact)return exact;
 if(context==='permit_note159'){
  const match=source.match(/^(\s*)(رئيسي|رئيسى|فرع)(\s*[-—–]\s*)([0-9٠-٩]+)(\s*)$/u);
  if(match)return match[1]+(match[2]==='فرع'?'Branch':'Head office')+match[3]+match[4]+match[5];
 }
 return boot?.localization?.dictionary?.[source]||source;
}
// Display translations retain the source; only explicit edits change saved data.
function dataFormBind159(form,mapping){
 for(const [name,context]of Object.entries(mapping)){
  const c=form.elements.namedItem(name)||[...form.querySelectorAll('[name]')].find(n=>n.name===name);if(!c||c.dataset.context137)continue;
  c.dataset.context137=context;c.dataset.source137=c.value;
  const show=()=>{if(c.dataset.pairEdited170==='1')return;let text=dataText137(context,c.dataset.source137);if(context==='permit_note159')text=text.replace(/\r\n?|\n/g,' ');c.dataset.display137=text;c.value=text;dataFieldState160(c);};
  c.addEventListener('input',()=>{c.dataset.source137=c.value;delete c.dataset.display137;});c.addEventListener('blur',show);show();if(typeof textPairBind170==='function')textPairBind170(form,c,context);
 }
}

let nationalitySource138=null,nationalityIndex138=new Map();
function nationalityFind137(value){const catalog=boot?.localization?.nationalities137||[];if(nationalitySource138!==catalog){nationalitySource138=catalog;nationalityIndex138=new Map();for(const r of catalog)for(const key of [r.code,r.ar,r.en,...(r.aliases||[])])nationalityIndex138.set(normalize32(key),r);}return nationalityIndex138.get(normalize32(value));}
function nationalityLabel137(value){const r=nationalityFind137(value);return r?(boot?.localization?.language==='en'?r.en:r.ar):String(value??'');}
function nationalityInput137(value){
 const catalog=boot?.localization?.nationalities137||[];if(!catalog.length)return input('nationality','الجنسية',value);
 const row=nationalityFind137(value),selected=row?row.code:String(value||''),option=(code,label)=>'<option data-no-translate value="'+esc(code)+'" '+(code===selected?'selected':'')+'>'+esc(label)+'</option>';
 return '<label>'+esc(uiText('الجنسية'))+'<select name="nationality" data-nationality137 aria-label="'+esc(uiText('الجنسية'))+'">'+option('',uiText('اختر الجنسية'))+[...catalog].sort((a,b)=>nationalityLabel137(a.code).localeCompare(nationalityLabel137(b.code),boot.localization.language==='en'?'en':'ar')).map(r=>option(r.code,nationalityLabel137(r.code))).join('')+(!row&&value?option(value,value):'')+'</select></label>';
}
function employeeNationality137(form){
 const old=form.elements.namedItem('nationality'),catalog=boot?.localization?.nationalities137||[];if(!old||!catalog.length||old.tagName==='SELECT')return;
 const selected=nationalityFind137(old.value),select=document.createElement('select');select.name=old.name;select.required=old.required;select.dataset.nationality137='';select.setAttribute('aria-label',uiText('الجنسية'));
 const append=(value,text)=>{const o=document.createElement('option');o.value=value;o.textContent=text;o.dataset.noTranslate='';select.append(o);};append('',uiText('اختر الجنسية'));
 for(const r of [...catalog].sort((a,b)=>nationalityLabel137(a.code).localeCompare(nationalityLabel137(b.code),boot.localization.language==='en'?'en':'ar')))append(r.code,nationalityLabel137(r.code));
 if(old.value&&!selected)append(old.value,old.value);select.value=selected?selected.code:old.value;old.replaceWith(select);
}
function dataFieldState160(control){if(!control?.dataset.context137)return;const source=employeeDataOriginal137(control),context=control.dataset.context137,pending=boot?.localization?.language==='en'&&/[\u0600-\u06ff]/.test(source)&&dataText137(context,source)===source&&!boot.localization.data_context137?.[context]?.[source]&&!boot.localization.data_context137?.[context]?.[source.trim()];control.dataset.translationState160=pending?(boot.localization.data_states160?.[context]?.[source]||'pending'):'translated';if(pending){control.title=uiText('الترجمة قيد الانتظار؛ يظهر النص الأصلي');control.setAttribute('aria-description',control.title);}else if(control.getAttribute('aria-description')===control.title){control.removeAttribute('title');control.removeAttribute('aria-description');}}
function employeeDataOriginal137(control){if(!control)return '';return control.dataset.source137!==undefined&&control.value===control.dataset.display137?control.dataset.source137:control.value;}
function employeeDataForm137(form){
 employeeNationality137(form);
 const address=form.elements.namedItem('address');if(address){const label=address.closest('label');for(const node of [...(label?.childNodes||[])])if(node.nodeType===3&&node.textContent.trim()){const span=document.createElement('span');span.dataset.noTranslate='';span.dataset.textContext138='field_label:address';span.dataset.textSource138='العنوان';span.textContent=dataText137('field_label:address','العنوان');node.replaceWith(span);}}

 for(const [name,context]of Object.entries({department:'department',job_title:'job_title',additional_job:'additional_job',address:'address',bank_name:'bank_name',salary_note:'salary_note160',editor_allowance_note:'allowance_note160'})){
  const control=form.elements.namedItem(name);if(!control||control.dataset.context137)continue;control.dataset.context137=context;control.dataset.source137=control.value;
  const show=()=>{if(control.dataset.pairEdited170==='1')return;const source=control.dataset.source137;control.dataset.display137=dataText137(context,source);control.value=control.dataset.display137;dataFieldState160(control);};
  control.addEventListener('input',()=>{control.dataset.source137=control.value;delete control.dataset.display137;});control.addEventListener('blur',show);show();if(typeof textPairBind170==='function')textPairBind170(form,control,context);
 }
 form.addEventListener('formdata',event=>{for(const c of form.querySelectorAll('[data-context137]'))if(!c.disabled)event.formData.set(c.name,employeeDataOriginal137(c));});
}
function employeeDataRaw137(form,raw){for(const c of form.querySelectorAll('[data-context137]'))if(c.name in raw)raw[c.name]=employeeDataOriginal137(c);return raw;}

const dataLocaleState138=new WeakMap();
function refreshDataLanguage138(root=document){
 const en=boot?.localization?.language==='en',catalog=boot?.localization?.nationalities137||[],byCode=new Map(catalog.map(r=>[r.code,r]));
 for(const select of root.querySelectorAll('select[data-nationality137]')){const prior=dataLocaleState138.get(select);if(prior?.language===boot.localization.language&&prior.catalog===catalog)continue;dataLocaleState138.set(select,{language:boot.localization.language,catalog});for(const option of select.options){const item=byCode.get(option.value),text=item?(en?item.en:item.ar):option.value?null:uiText('اختر الجنسية');if(text!==null&&option.textContent!==text)option.textContent=text;}}
 for(const control of root.querySelectorAll('input[data-context137],textarea[data-context137]')){if(control.dataset.pairEdited170==='1'||document.activeElement===control&&!control.disabled)continue;const prior=dataLocaleState138.get(control);if(prior?.language===boot.localization.language&&prior.context===boot.localization.data_context137)continue;dataLocaleState138.set(control,{language:boot.localization.language,context:boot.localization.data_context137});const source=employeeDataOriginal137(control);control.dataset.source137=source;control.dataset.display137=dataText137(control.dataset.context137,source);if(control.dataset.context137==='permit_note159')control.dataset.display137=control.dataset.display137.replace(/\r\n?|\n/g,' ');if(control.value!==control.dataset.display137)control.value=control.dataset.display137;dataFieldState160(control);}
 for(const el of root.querySelectorAll('[data-text-context138]')){const text=dataText137(el.dataset.textContext138,el.dataset.textSource138||'');if(el.textContent!==text)el.textContent=text;}
 for(const el of root.querySelectorAll('[data-text-auto160]')){const text=dataDisplayAny160(el.dataset.textAuto160);if(el.textContent!==text)el.textContent=text;}
 for(const el of root.querySelectorAll('[data-receipt164]')){try{const r=JSON.parse(el.dataset.receipt164),text=receiptText164(r,r);if(el.textContent!==text)el.textContent=text;}catch(e){}}
 for(const el of root.querySelectorAll('[data-text-key138]')){const text=uiText(el.dataset.textKey138);if(el.textContent!==text)el.textContent=text;}
}

// Labels belong to an employee identity and stay attached to their DOM node across language changes.
const employeeNames143=new Map();
function collectEmployeeNames143(value){
 if(!value||typeof value!=='object')return;
 const p=value.employee_locale143||(value.id&&value.name&&(value.profile||value.role)?{id:value.id,ar:value.name,en:value.profile?.name_en||value.name_en||''}:null);
 if(p&&Number(p.id)>0&&typeof p.ar==='string')employeeNames143.set(Number(p.id),{id:Number(p.id),ar:p.ar,en:String(p.en||'').trim()});
}
function employeeLabel143(id,name='',person=null){
 const cached=employeeNames143.get(Number(id)),own=person?.employee_locale143||(person?.name?{id,ar:person.name,en:person.profile?.name_en||person.name_en||''}:null),p=own?.en?own:(cached&&(!own||cached.ar===own.ar)?cached:own);
 const fallback=String(name??'');if(!p||fallback&&fallback!==p.ar&&fallback!==p.en)return {id:Number(id)||0,ar:fallback,en:fallback};
 return {id:Number(id)||0,ar:p.ar,en:p.en||p.ar};
}
function employeeNameText143(id,name='',person=null){const p=employeeLabel143(id,name,person);return boot?.localization?.language==='en'?p.en:p.ar;}
function employeeNameMarkup143(id,name='',person=null){const p=employeeLabel143(id,name,person);return '<span data-employee-name143="'+p.id+'" data-name-ar143="'+esc(p.ar)+'" data-name-en143="'+esc(p.en)+'" data-no-translate>'+esc(boot?.localization?.language==='en'?p.en:p.ar)+'</span>';}
function employeeOption143(id,name,code='',selected=false,person=null){const p=employeeLabel143(id,name,person),prefix=code?code+' — ':'';return '<option value="'+Number(id)+'" '+(selected?'selected ':'')+'data-employee-name143="'+p.id+'" data-name-ar143="'+esc(prefix+p.ar)+'" data-name-en143="'+esc(prefix+p.en)+'" data-no-translate>'+esc(prefix+(boot?.localization?.language==='en'?p.en:p.ar))+'</option>';}
function refreshEmployeeNames143(root=document){for(const el of root.querySelectorAll('[data-employee-name143]')){const text=boot?.localization?.language==='en'?el.dataset.nameEn143:el.dataset.nameAr143;if(el.textContent!==text)el.textContent=text;}}

function dataRecordContext160(module,key){
 if(key==='commission_deduction_note36')return 'record_text:payroll_input35:commission_deduction_note36';
 if(['department','job_title','additional_job','address','bank_name','occupation'].includes(key))return key;
 if(module==='work_permit'&&key==='reason')return 'permit_note159';
 if(module==='salary_history'&&key==='note')return 'salary_note160';
 if(module==='allowance_terms'&&key==='note')return 'allowance_note160';
 return module?'record_text:'+module+':'+key:'';
}
function dataDetailMarkup160(key,value,context,display){
 if(typeof value!=='string'||!/[\u0600-\u06ff]/.test(value)||!hrTextFields162.includes(key))return esc(display);
 const c=dataRecordContext160(context?.module||'',key),text=c?dataText137(c,value):dataDisplayAny160(value);
 return '<span data-no-translate '+(c?'data-text-context138="'+esc(c)+'" data-text-source138="'+esc(value)+'"':'data-text-auto160="'+esc(value)+'"')+'>'+esc(text)+'</span>';
}

function hrTranslationError160(code){return uiText(({hr_translation_unconfigured:'أدخل مفتاح ترجمة بيانات الموظفين أولًا.',hr_translation_key_rejected:'مفتاح ترجمة الموظفين لم يُقبل',hr_translation_local_budget:'تم بلوغ حد ترجمة الموظفين لهذا الشهر',hr_translation_provider_quota:'انتهت حصة محرك ترجمة الموظفين',hr_translation_unreachable:'تعذر الاتصال بمحرك ترجمة الموظفين',text_too_long:'النص أطول من الحد المتاح للترجمة الآلية'})[code]||'تعذرت ترجمة النص؛ يمكن تصحيحه يدويًا أو إعادة المحاولة لاحقًا');}

function dataTextMarkup162(context,source){source=String(source??'');return '<span data-no-translate data-text-context138="'+esc(context)+'" data-text-source138="'+esc(source)+'">'+esc(typeof dataText137==='function'?dataText137(context,source):source)+'</span>';}

// Shared HR display hook: only a unique translation from this authorized page can replace a raw text node.
function interfaceDataText164(source){
 const fixed=uiText(source);if(fixed!==source)return fixed;
 if(typeof view!=='undefined'&&['publication','cms','offers','public_settings','languages'].includes(view))return source;
 return typeof dataDisplayAny160==='function'?dataDisplayAny160(source):source;
}

function receiptText164(row,value){
 const source=String(value.text||'');if(boot?.localization?.language!=='en')return source;
 if(value.mode!=='automatic')return typeof dataText137==='function'?dataText137('record_text:video_receipt:text',source):source;
 const m=source.match(/^أنا (.+) أقر باستلام مبلغ ([0-9.,]+) دينار كويتي عن (مستحقات شهر|صرف رصيد إجازات بتاريخ) (\d{4}-\d{2}(?:-\d{2})?)\. هذا الإقرار يخص العملية المحددة فقط\.(?: الراتب الأساسي ([0-9.,]+) د\.ك، والمستحقات الإضافية ([0-9.,]+) د\.ك، والخصومات ([0-9.,]+) د\.ك\.)?$/u);
 if(!m)return source;
 const fields={employee:typeof employeeNameText143==='function'?employeeNameText143(row.user_id,m[1],row):m[1],amount:m[2],period:m[4],salary:m[5],addition:m[6],deduction:m[7]};
 const render=template=>uiText(template,false).replace(/\{(employee|amount|period|salary|addition|deduction)\}/g,(_,key)=>fields[key]??'');
 const first=m[3]==='مستحقات شهر'?'أنا {employee} أقر باستلام مبلغ {amount} دينار كويتي عن مستحقات شهر {period}. هذا الإقرار يخص العملية المحددة فقط.':'أنا {employee} أقر باستلام مبلغ {amount} دينار كويتي عن صرف رصيد إجازات بتاريخ {period}. هذا الإقرار يخص العملية المحددة فقط.';
 return render(first)+(m[5]===undefined?'':render(' الراتب الأساسي {salary} د.ك، والمستحقات الإضافية {addition} د.ك، والخصومات {deduction} د.ك.'));
}
function receiptMarkup164(row,value){const payload={user_id:row.user_id,text:value.text,mode:value.mode};return '<span data-no-translate data-receipt164="'+esc(JSON.stringify(payload))+'">'+esc(receiptText164(row,value))+'</span>';}

function noticeSegment164(text){
 if(typeof dataDisplayAny160!=='function')return uiText(text,false);
 if(boot?.localization?.language!=='en')return text;
 const whole=dataDisplayAny160(text.trim());if(whole!==text.trim())return text.replace(text.trim(),whole);
 return text.split(/([—·:])/u).map(part=>{const trim=part.trim();if(!trim)return part;const fixed=uiText(trim,false),value=fixed!==trim?fixed:dataDisplayAny160(trim);return part.replace(trim,value);}).join('');
}

// Scoped hidden counterparts: no layout or styling changes.
function textPairMeta170(form){return {language:form.dataset.textLanguage170||(boot.localization?.language==='en'?'en':'ar'),edited:[...form.querySelectorAll('[data-pair-edited170="1"]')].map(n=>n.name),languages:Object.fromEntries([...form.querySelectorAll('[data-pair-edited170="1"]')].map(n=>[n.name,n.dataset.editLanguage170||form.dataset.textLanguage170]))};}
function textPairBind170(form,control,context){
 if(!['employeeEditorForm134','employeeDelegate134','permitForm148','permitPrevious151'].includes(form.id)&&form.dataset.recordPairs173!=='1')return;
 if(control.dataset.pairBound170)return;control.dataset.pairBound170='1';
 form.dataset.textLanguage170=form.dataset.textLanguage170||(boot.localization?.language==='en'?'en':'ar');
 const make=(name,value)=>{const n=document.createElement('input');n.type='hidden';n.name=name;n.value=value;n.setAttribute('data-no-translate','');form.append(n);return n;};
 let meta=form.elements.namedItem('_edit170');if(!meta)meta=make('_edit170',JSON.stringify(textPairMeta170(form)));
 const source=employeeDataOriginal137(control),ar=make('_pair170_'+control.name+'_ar',source),en=make('_pair170_'+control.name+'_en',typeof dataText137==='function'?(boot.localization?.data_context137?.[context]?.[source]||''):'');
 control.addEventListener('input',()=>{control.dataset.pairEdited170='1';control.dataset.editLanguage170=boot.localization?.language==='en'?'en':'ar';form.dataset.textLanguage170=control.dataset.editLanguage170;if(control.dataset.editLanguage170==='en'){en.value=control.value;ar.value='';}else{ar.value=control.value;en.value='';}meta.value=JSON.stringify(textPairMeta170(form));});
}
