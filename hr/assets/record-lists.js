'use strict';
/* Approved archive batch, isolated from the frozen reference components. */
const batch185=RecordFields194;
recordCategories49.request=batch185.requests.map(x=>x.slice(0,2));
recordCategories49.evaluation=batch185.evaluations.map(x=>x.slice(0,2));
for(const hub of [...portalHubs32,...personalHubs50])hub[2]=hub[2].filter(key=>key!=='loan');
const adminRequests185=()=>view==='request'&&requestMode50==='admin';
const family185=groupFamily179;groupFamily179=function(key=view){return key==='request'&&requestMode50==='admin'?null:family185(key);};
const employeeEnabled185=employeeEnabled73;employeeEnabled73=function(key=view){return key==='request'&&requestMode50==='admin'?false:employeeEnabled185(key);};
const tabs185=syncPortalTabs32;syncPortalTabs32=function(key=view){tabs185(key);if(key==='request'&&requestMode50==='admin'){const n=document.getElementById('portalTabs32');n.hidden=true;n.replaceChildren();}};
const categories185=categorizedRecords61;categorizedRecords61=async function(module){return module==='request'&&requestMode50==='admin'?requestWorkspace185():categories185(module);};
const records185=records;records=async function(module,...args){if(module==='loan'){requestMode50='admin';return navigate('request');}return records185(module,...args);};

const boot185=loadBoot;loadBoot=async function(...args){const result=await boot185(...args);const dict=boot.localization.dictionary;
 for(const [,ar,en]of [...batch185.requests,...batch185.evaluations])dict[ar]=en;
 for(const f of Object.values({...batch185.requestFields,...Object.fromEntries(Object.entries(batch185.evaluationFields).map(([k,v])=>['eval_'+k,v]))}).flat())dict[f.ar]=f.en;
 Object.assign(dict,{'تاريخ المستند':'Document date','الفترة / التاريخ':'Period / date','رقم التقييم / الخطة':'Evaluation / plan reference','اسم العهدة':'Asset name','كل الموظفين في نطاقك':'All employees in your scope','عرض كل الموظفين':'Show all employees','رقم التقييم':'Evaluation reference','رقم الخطة':'Plan reference','فترة التقييم من':'Assessment period from','فترة التقييم إلى':'Assessment period to','الدرجة من 100':'Score out of 100','اختر التقييم المرتبط':'Choose the related evaluation','لا توجد تقييمات لهذا الموظف':'No evaluations for this employee','إنشاء خطة تحسين':'Create improvement plan','نتيجة المتابعة':'Follow-up result','حفظ نتيجة المتابعة':'Save follow-up result','تم حفظ نتيجة المتابعة':'Follow-up result saved','سجّل نتيجة المتابعة.':'Enter the follow-up result.','شكوى':'Complaint','مقترح':'Suggestion','تاريخ الطلب':'Request date','مرفق المستند':'Document attachment','الطلبات الإدارية':'Administrative requests','قائمة الطلبات':'Request list','أكمل الحقول المطلوبة.':'Complete the required fields.','اختر تقييمًا لنفس الموظف.':'Choose an evaluation for the same employee.','أدخل فترة صحيحة.':'Enter a valid period.','بانتظار اعتماد النقاط':'Scoring rules pending'});
 Object.assign(dict,{'جدول السجلات':'Records table','تاريخ الجزاء':'Penalty date','بداية السريان':'Effective from','نهاية السريان':'Effective to','التاريخ':'Date','فترة التقييم':'Assessment period','مدة الخطة':'Plan period','ملخص الاستفسار':'Inquiry summary','ملخص التحديث':'Update summary','ملخص الطلب':'Request summary'});
 return result;
};

const controls185=evaluationControls182;evaluationControls182=function(host){
 controls185(host);host.classList.add('evaluation-workspace185');
 const row=host.querySelector('.record-filters173'),period=host.querySelector('.evaluation-period182'),source=period.querySelector('#evaluationSource182').closest('label');
 row.prepend(source);row.after(period);if(!period.children.length)period.remove();
 const status=row.querySelector('#recordStatus173');status.insertAdjacentHTML('beforeend','<option value="awaiting_rules182">'+esc(uiText('بانتظار اعتماد النقاط'))+'</option>');
};

const requestState185=new Map();
async function requestWorkspace185(){
 resetRecordFilters75();employeeSelections73.delete(employeeGroup73('request'));document.querySelector('[data-employee-filter73]')?.remove();
 const page=$('#page'),epoch=navigationEpoch32,key=screenIdentity38(),saved=requestState185.get(key)||{employee:0,category:'all',query:'',status:''};
 const canCreate=teamPermission('request','create')||personalPermission34('request','create');
 page.innerHTML='<section class="card employee-editor134 record-workspace173 request-workspace185 record-table187" aria-busy="true"><header class="employee-editor-identity134"><div><strong>'+esc(uiText('الطلبات الإدارية'))+'</strong><span data-request-person185></span></div><div class="employee-editor-top-actions136"><button id="addRecord" class="primary create-action32" data-create-action="1" disabled><span class="create-icon32 record-add-icon180" aria-hidden="true">+</span><span>'+esc(uiText('إضافة'))+'</span></button><button id="requestFind185" disabled>'+esc(uiText('بحث عن موظف'))+'</button></div></header><nav class="employee-editor-sections134 record-categories174" aria-label="'+esc(uiText('التصنيف'))+'">'+[['all','الكل'],...recordCategories49.request].map(([k,t])=>'<button type="button" data-request-category185="'+k+'" aria-current="'+(saved.category===k?'location':'false')+'" aria-pressed="'+(saved.category===k)+'">'+esc(uiText(t))+'</button>').join('')+'</nav><div class="record-filters173"><label>'+esc(uiText('البحث في السجلات'))+'<input id="requestQuery185" type="search" value="'+esc(saved.query)+'" placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'"></label><label>'+esc(uiText('الحالة'))+'<select id="requestStatus185"><option value="">'+esc(uiText('الكل'))+'</option>'+['pending','revision','approved','rejected','closed'].map(k=>'<option value="'+k+'" '+(saved.status===k?'selected':'')+'>'+esc(uiText(labels[k]||k))+'</option>').join('')+'</select></label></div><div class="request-selection185"><button type="button" id="requestAll185" hidden>'+esc(uiText('عرض كل الموظفين'))+'</button><span data-request-count185></span></div><div class="employee-editor-history134" data-record-list173>'+loadingMarkup()+'</div></section>';
 const host=page.firstElementChild,add=host.querySelector('#addRecord');add.hidden=!canCreate;prepareCreation88(add);
 const current=()=>epoch===navigationEpoch32&&host.isConnected&&key===screenIdentity38();
 await recordWorkspaceLoad195({module:'request',host,canAdd:canCreate,personal:personalNavigation50(),prior:saved,identity:key,current});
}

function fields185(r){const b=batch185.body(r),category=r.module==='loan'?'loan':b.record_category49;
 if(r.module==='evaluation')return b._auto182?[]:[{key:'reference185',ar:category==='improvement'?'رقم الخطة':'رقم التقييم'},...(category!=='improvement'?[{key:'score',ar:'الدرجة من 100'}]:[]),...(batch185.evaluationFields[category]||batch185.evaluationFields.other).filter(f=>!['date_from','date_to'].includes(f.key)).map(f=>f.key==='related_record49'?{...f,key:'related_reference185'}:f),...(category==='improvement'?[{key:'followup_result',ar:'نتيجة المتابعة'}]:[])];
 return (batch185.requestFields[category]||batch185.requestFields.other).filter(f=>f.key!=='title');
}
function value185(r,key){const b=batch185.body(r),value=key==='amount'?r.amount:b[key];if(key==='related_record49')return esc(b.related_reference185||value||'—');if(key==='request_type'&&['complaint','suggestion'].includes(value))return esc(uiText(value==='complaint'?'شكوى':'مقترح'));if(/date$/.test(key))return esc(value?dateDisplay32(value):'—');if(key==='reference185')return '<bdi>'+esc(value||'—')+'</bdi>';return dataTextMarkup162('record_text:'+r.module+':'+key,String(value??'—'));}
function detailsMarkup185(r){return fields185(r).map(f=>'<div><strong>'+esc(uiText(f.ar))+'</strong><div class="record-value32">'+value185(r,f.key)+'</div></div>').join('');}
const display185=recordDisplayFields176;recordDisplayFields176=function(module,fields,b,r){return module==='evaluation'?fields185(r).map(f=>[f.key,f.ar]):display185(module,fields,b,r);};
const field185=recordFieldMarkup173;recordFieldMarkup173=function(module,key,value){if(module==='evaluation'&&['reference185','related_reference185'].includes(key))return '<bdi>'+esc(value||'—')+'</bdi>';if(module==='evaluation'&&key==='followup_date')return esc(value?dateDisplay32(value):'—');return field185(module,key,value);};

const originalModal185=recordModal;
recordModal=async function(module,target=null,known=null,context={}){
 if(!['request','loan','evaluation'].includes(module)||module==='request'&&requestMode50!=='admin')return originalModal185(module,target,known);
 const evaluation=module==='evaluation',permission=evaluation?'evaluation':'request',canCreate=teamPermission(permission,'create')||(!evaluation&&personalPermission34(permission,'create'));if(!canCreate)return;
 const epoch=navigationEpoch32;let staff=known;try{if(!staff)staff=await api('user_choices29',null,{screen:permission});}catch(e){toast(e.message);return;}if(epoch!==navigationEpoch32)return;users=staff;
 const team=teamPermission(permission,'create'),options=evaluation?batch185.evaluations:batch185.requests;
 let chosen=context.category|| (module==='loan'?'loan':recordCategory49);if(!options.some(x=>x[0]===chosen))chosen=options[0][0];
 const categoryMarkup='<label data-span185="1">'+esc(uiText('التصنيف'))+'<select name="record_category49">'+options.map(([k,ar])=>'<option value="'+k+'" '+(k===chosen?'selected':'')+'>'+esc(uiText(ar))+'</option>').join('')+'</select></label>';
 const employee=team?'<label data-span185="2">'+esc(uiText('الموظف'))+'<select name="user_id">'+userOptions()+'</select></label>':'<input type="hidden" name="user_id" value="'+Number(boot.user.id)+'">';
 modal(uiText('إضافة')+' — '+uiText(evaluation?'التقييمات':'الطلبات الإدارية'),'<form id="modalForm" class="archive-form185">'+employee+categoryMarkup+'<div data-fields185></div><label data-span185="3" data-attachment-label185>'+esc(uiText('المرفق'))+'<input type="file" name="file" accept="image/jpeg,image/png,image/webp,application/pdf"></label>'+saveButton()+'</form>',async form=>{
  const raw=Object.fromEntries(new FormData(form)),cat=raw.record_category49,actual=evaluation?'evaluation':cat==='loan'?'loan':'request',values={record_category49:cat};
  for(const [k,v]of Object.entries(raw))if(!k.startsWith('_')&&!['title','user_id','date_from','date_to','amount','file'].includes(k))values[k]=v;
  const fd=new FormData();fd.set('module',actual);fd.set('user_id',raw.user_id);fd.set('title',raw.title||uiText(options.find(x=>x[0]===cat)[1]));for(const k of ['date_from','date_to','amount'])if(raw[k])fd.set(k,raw[k]);fd.set('body',JSON.stringify(values));if(form.elements.file.files[0])fd.set('file',form.elements.file.files[0]);
  if(await recordSubmit173(form,fd)===false)return false;toast(uiText('تم حفظ السجل'));
 });
 const form=$('#modalForm');if(target&&staff.some(u=>Number(u.id)===Number(target)))form.elements.user_id.value=String(target);recordModalLayout174(form);form.dataset.recordPairs173='1';
 const area=form.querySelector('[data-fields185]'),category=form.elements.record_category49,drafts=new Map();let last='',parentTicket=0;
 const inputField=f=>{const extra=(f.required?' required':'')+(f.type==='number'?' min="'+(f.key==='amount'?'0.001':'0')+'" '+(f.key==='score'?'max="100"':'')+' step="any"':''),open='<label data-span185="'+f.span+'">'+esc(uiText(f.ar));let control;
  if(f.type==='textarea')control='<textarea name="'+f.key+'" rows="3"'+extra+'></textarea>';
  else if(f.type==='parent')control='<select name="'+f.key+'" required><option value="">'+esc(uiText('اختر التقييم المرتبط'))+'</option></select>';
  else if(f.type==='complaint')control='<select name="'+f.key+'"><option value="complaint">'+esc(uiText('شكوى'))+'</option><option value="suggestion">'+esc(uiText('مقترح'))+'</option></select>';
  else control='<input name="'+f.key+'" type="'+f.type+'"'+extra+'>';
  return open+control+'</label>';
 };
 async function parents(){const select=form.elements.related_record49;if(!select)return;const ticket=++parentTicket,id=Number(form.elements.user_id.value);select.disabled=true;select.innerHTML='<option value="">'+esc(uiText('جارٍ التحميل…'))+'</option>';select._refreshChoice138?.();
  try{const rows=await api('evaluation_parents185',null,{user_id:id});if(ticket!==parentTicket||!form.isConnected||!select.isConnected)return;select.innerHTML='<option value="">'+esc(uiText(rows.length?'اختر التقييم المرتبط':'لا توجد تقييمات لهذا الموظف'))+'</option>'+rows.map(r=>'<option value="'+r.id+'">'+esc(batch185.body(r).reference185)+' — '+esc(dataText137('record_title:evaluation',r.title))+' — '+esc(dateDisplay32(r.date_from))+'</option>').join('');const requested=context.parent&&Number(context.parent.user_id)===id?context.parent.id:drafts.get('improvement')?.related_record49;if(rows.some(r=>String(r.id)===String(requested)))select.value=String(requested);}
  catch(e){if(ticket===parentTicket&&select.isConnected){select.innerHTML='<option value="">'+esc(uiText('إعادة المحاولة'))+'</option>';toast(e.message);const retry=document.createElement('button');retry.type='button';retry.textContent=uiText('إعادة المحاولة');retry.onclick=()=>{retry.remove();parents();};select.closest('label').append(retry);}}
  finally{if(ticket===parentTicket&&select.isConnected){select.disabled=false;select._refreshChoice138?.();}}
 }
 function render(){if(last)drafts.set(last,Object.fromEntries(new FormData(form)));last=category.value;parentTicket++;
  const plan=evaluation&&last==='improvement',fields=evaluation?[{key:'title',ar:'العنوان',type:'text',span:plan?3:2,required:true},...(!plan?[{key:'score',ar:'الدرجة من 100',type:'number',span:1},{key:'date_from',ar:'فترة التقييم من',type:'date',span:1,required:true},{key:'date_to',ar:'فترة التقييم إلى',type:'date',span:2,required:true}]:[]),...batch185.evaluationFields[last]]:batch185.requestFields[last];
  form.querySelectorAll('input[name^="_pair170_"]').forEach(n=>n.remove());area.innerHTML=fields.map(inputField).join('');const values=drafts.get(last)||{};for(const f of fields){const n=form.elements.namedItem(f.key);if(n&&f.type!=='parent'){n.value=values[f.key]??(f.type==='date'?dateNow():f.type==='complaint'?'complaint':'');}}
  area.querySelectorAll('select').forEach(enhanceSelect32);area.querySelectorAll('input[type=date]').forEach(enhanceDate32);
  const actual=evaluation?'evaluation':last==='loan'?'loan':'request';const contexts=Object.fromEntries(fields.filter(f=>['text','textarea'].includes(f.type)).map(f=>[f.key,f.key==='title'?'record_title:'+actual:'record_text:'+actual+':'+f.key]));dataFormBind159(form,contexts);applyInterfaceLanguage();if(plan)parents();
 }
 category.addEventListener('change',render);form.elements.user_id.addEventListener('change',()=>{context.parent=null;if(drafts.has('improvement'))delete drafts.get('improvement').related_record49;parents();});
 form.addEventListener('formdata',event=>{for(const control of form.querySelectorAll('[data-context137]'))if(control.name&&!control.disabled)event.formData.set(control.name,employeeDataOriginal137(control));});render();
};

const detail185=recordDetail;recordDetail=function(r){
 r=recordForReview188(r);
 const affected=['request','loan','evaluation'].includes(r.module)&&!batch185.body(r)._punch39;if(!affected)return detail185(r);
 const b=batch185.body(r),meta=boot.modules[r.module],prior=meta.fields;
 if(r.module!=='evaluation')meta.fields=Object.fromEntries(fields185(r).map(f=>[f.key,f.ar]));const shown={...r,body_json:JSON.stringify({...b,...(r.module==='loan'?{amount:r.amount}:{}),...(b.related_reference185?{related_record49:b.related_reference185}:{}),...(b.request_type==='complaint'||b.request_type==='suggestion'?{request_type:uiText(b.request_type==='complaint'?'شكوى':'مقترح')}:{} )})};try{detail185(shown);}finally{meta.fields=prior;}
 const root=$('#modalContent'),body=root.querySelector('.modal-body');
 if(r.module!=='evaluation'){body.insertAdjacentHTML('afterbegin','<div class="detail-row"><b>'+esc(uiText('تاريخ الطلب'))+'</b><span>'+esc(dateDisplay32((r.created_at||'').slice(0,10)))+'</span></div>');root.querySelectorAll('#revisedFrom,#revisedTo').forEach(n=>n.closest('label').hidden=true);}
 if(Number(r.user_id)===Number(boot.user.id)){root.querySelectorAll('[data-review]').forEach(n=>n.remove());root.querySelector('#reviewNote')?.closest('label')?.remove();}
 if(r.module==='evaluation'){
  for(const row of body.querySelectorAll('.detail-row'))if(row.querySelector('b')?.textContent===uiText('رقم السجل'))row.remove();
  if(b._auto182)body.insertAdjacentHTML('afterbegin','<div class="detail-row"><b>'+esc(uiText('رقم التقييم'))+'</b><bdi>'+esc(b.reference185)+'</bdi></div>');
  if(teamPermission('evaluation','create')&&b.record_category49!=='improvement'){const button=document.createElement('button');button.type='button';button.textContent=uiText('إنشاء خطة تحسين');button.onclick=()=>recordModal('evaluation',r.user_id,null,{category:'improvement',parent:r});body.append(button);}
  if(teamPermission('evaluation','review')&&b.record_category49==='improvement'){
   const form=document.createElement('form');form.innerHTML='<label>'+esc(uiText('نتيجة المتابعة'))+'<textarea name="followup_result" rows="3" required>'+esc(b.followup_result||'')+'</textarea></label><button type="submit" class="primary">'+esc(uiText('حفظ نتيجة المتابعة'))+'</button>';body.append(form);recordBind173(form,'evaluation',['followup_result']);
   form.onsubmit=async e=>{e.preventDefault();const unlock=recordLock173(root);try{await api('evaluation_followup185',{id:r.id,result:employeeDataOriginal137(form.elements.followup_result),_edit170:recordMeta173(form)});toast(uiText('تم حفظ نتيجة المتابعة'));$('#modal').close();await navigate('evaluation');}catch(error){toast(error.message);}finally{unlock();}};
  }
 }
 applyInterfaceLanguage();
};
const send185=recordSend173;recordSend173=async function(action,r,data){if(!['request','loan'].includes(r.module)||batch185.body(r)._punch39)return send185(action,r,data);const root=$('#modalContent');if(root.dataset.saving173)return false;root.dataset.saving173='1';const unlock=recordLock173(root);try{return await api(action,data);}finally{unlock();delete root.dataset.saving173;}};

const summary185=recordSummary173;recordSummary173=function(r){if(r.module!=='evaluation'||batch185.body(r)._auto182)return summary185(r);const plan=batch185.body(r).record_category49==='improvement';return [[plan?'بداية الخطة':'فترة التقييم من',r.date_from],[plan?'نهاية الخطة':'فترة التقييم إلى',r.date_to]].map(([label,value])=>'<div><strong>'+esc(uiText(label))+'</strong><div class="record-value32">'+esc(value?dateDisplay32(value):'—')+'</div></div>').join('');};
