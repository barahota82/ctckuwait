'use strict';
const recordModules173=['document','custody','discipline','evaluation'];
const recordFree173={document:['document_type'],custody:['asset_type','handover','return_notes'],discipline:['decision_type','reason','employee_response'],evaluation:['criteria','strengths','improvement']};
const recordState173=new Map();
function recordFieldMarkup173(module,key,value){
 if(key==='return_notes')return dataTextMarkup162('record_text:custody:return_notes',value||'—');
 if(key==='record_category49')return esc(uiText(recordCategoryLabel49(module,value)||'أخرى'));
 if(key==='custody_state49')return esc(uiText(({active:'سارية',returned:'مستردة',settled:'تمت تسويتها'})[value]||value||'—'));
 if(['expiry','return_date'].includes(key))return esc(value?dateDisplay32(value):'—');
 return dataDetailMarkup160(key,value??'—',{module},value??'—');
}
function recordSummary173(r){
 const cell=(label,value)=>'<div><strong>'+esc(uiText(label))+'</strong><div class="record-value32">'+esc(value)+'</div></div>';
 const b=JSON.parse(r.body_json||'{}');
 if(b._auto182)return evaluationSummary182(r);
 if(r.module==='document'&&b.record_category49==='certificates')return cell('تاريخ الشهادة',r.date_from?dateDisplay32(r.date_from):'—');
 if(r.module==='custody')return cell('تاريخ الاستلام',r.date_from?dateDisplay32(r.date_from):'—');
 if(r.module==='discipline')return cell(b.record_category49==='decision'?'بداية سريان القرار':b.record_category49==='appeal'?'تاريخ التظلم':'تاريخ الإنذار / القرار',r.date_from?dateDisplay32(r.date_from):'—')+(b.record_category49==='decision'?cell('نهاية سريان القرار — اختياري',r.date_to?dateDisplay32(r.date_to):uiText('مستمر')):'')+(b.record_category49==='penalty'?cell('مبلغ الخصم',Number(r.amount||0).toFixed(3)+' '+uiText(boot.company?.currency||'د.ك')):'');
 return (r.module==='document'?'<div><strong>'+esc(uiText('اسم المستند'))+'</strong><div class="record-value32">'+dataTextMarkup162('record_title:document',r.title)+'</div></div>':'')+cell('من تاريخ',r.date_from?dateDisplay32(r.date_from):'—')+cell('إلى تاريخ',r.date_to?dateDisplay32(r.date_to):'—')+(r.module==='discipline'?cell('المبلغ',Number(r.amount||0).toFixed(3)+' '+uiText(boot.company?.currency||'د.ك')):'');
}
function recordTextContext173(module,key){return key==='title'?'record_title:'+module:'record_text:'+module+':'+key;}
function recordBind173(form,module,keys){
 if(!recordModules173.includes(module)&&module!=='policy')return;
 form.dataset.recordPairs173='1';const contexts=Object.fromEntries(keys.map(k=>[k,module==='policy'?(k==='label'?'policy_label164':'record_text:policy:'+k):recordTextContext173(module,k)]));dataFormBind159(form,contexts);
}
function recordMeta173(form){return typeof textPairMeta170==='function'?textPairMeta170(form):null;}
function recordLock173(root){
 const before=root.inert,busy=root.getAttribute('aria-busy'),dialog=root.closest('dialog')||document.getElementById('modal'),close=document.getElementById('closeModal'),closeDisabled=close?.disabled;
 const buttons=new Map([...root.querySelectorAll('button')].map(button=>[button,button.disabled]));
 let status=root.querySelector('[data-record-status178]');if(!status){status=document.createElement('span');status.dataset.recordStatus178='';status.setAttribute('role','status');(root.querySelector('.modal-foot')||root).append(status);}status.hidden=false;status.textContent=uiText('جارٍ الحفظ…');
 const cancel=e=>e.preventDefault();dialog?.addEventListener('cancel',cancel);root.inert=true;root.setAttribute('aria-busy','true');buttons.forEach((_,button)=>button.disabled=true);if(close)close.disabled=true;
 return ()=>{root.inert=before;if(busy===null)root.removeAttribute('aria-busy');else root.setAttribute('aria-busy',busy);buttons.forEach((value,button)=>{if(button.isConnected)button.disabled=value;});status.textContent='';status.hidden=true;if(close?.isConnected)close.disabled=closeDisabled;dialog?.removeEventListener('cancel',cancel);};
}

async function policyDelete173(form,payload){
 if(form.dataset.recordPairs173!=='1')return api('policy_delete',payload);
 const unlock=recordLock173(form);form.dataset.saving173='1';try{return await api('policy_delete',payload);}finally{unlock();delete form.dataset.saving173;}
}
async function policySubmit173(payload){
 const form=document.getElementById('modalForm');if(!payload._edit170||form?.dataset.recordPairs173!=='1')return api('policy_save',payload);
 const unlock=recordLock173(form);try{return await api('policy_save',payload);}finally{unlock();}
}
async function recordSubmit173(form,data){
 if(!form?.dataset.recordPairs173)return api('record_save',data);
 if(form.dataset.saving173)return false;form.dataset.saving173='1';const unlock=recordLock173(form);data.set('_edit170',JSON.stringify(recordMeta173(form)));
 try{return await api('record_save',data);}finally{unlock();delete form.dataset.saving173;}
}
function recordDetailBind173(r){
 const detailRoot=document.getElementById('modalContent');
 detailRoot?.querySelector('.modal-body')?.classList.toggle('record-white189',recordModules173.includes(r.module)||['request','loan'].includes(r.module)&&!JSON.parse(r.body_json||'{}')._punch39);
 const review=recordReviewPolicy188(r);
 if(review.managed&&!review.canDecide){
  document.querySelectorAll('#modalContent [data-review]').forEach(button=>{const row=button.closest('.row-actions');button.remove();if(row&&!row.children.length)row.remove();});
  document.querySelector('#modalContent #reviewNote')?.closest('label')?.remove();
 }
 if(recordStatusHidden186(r))document.querySelectorAll('#modalContent .detail-row').forEach(row=>{if(row.children.length===1&&row.firstElementChild?.tagName==='B'&&row.firstElementChild.textContent.trim()===uiText('الحالة'))row.remove();});
 if(!recordModules173.includes(r.module))return;
 for(const [id,key]of [['reviewNote','review_note'],['employeeReply','_employee_reply']]){const control=document.getElementById(id);if(!control)continue;const form=document.createElement('form');form.id='recordAction173-'+id;form.onsubmit=e=>e.preventDefault();control.closest('label').before(form);form.append(control.closest('label'));control.name=key;recordBind173(form,r.module,[key]);}
 const root=document.getElementById('modalContent');if(root?.querySelector('[data-record-pairs173]'))recordDialogGuard175(root);
}
async function recordSend173(action,r,data){
 if(!recordModules173.includes(r.module))return api(action,data);
 const id=action==='record_review'?'reviewNote':'employeeReply',control=document.getElementById(id),form=control?.form,dialog=document.getElementById('modalContent');if(!form)return;
 if(dialog.dataset.saving173)return false;dialog.dataset.saving173='1';const unlock=recordLock173(dialog);
 data.revision173=r.revision173||'';data._edit170=recordMeta173(form);data[action==='record_review'?'note':'reply']=employeeDataOriginal137(control);
 try{return await api(action,data);}finally{unlock();delete dialog.dataset.saving173;}
}
function recordWorkspaceKey173(module){return screenIdentity38()+'|'+module;}
async function recordWorkspace173(module){
 resetRecordFilters75();recordCategory49='all';employeeSelections73.delete(employeeGroup73(module));document.querySelector('[data-employee-filter73]')?.remove();document.querySelector('#portalTabs32 [data-record-search32]')?.closest('label')?.remove();
 const epoch=navigationEpoch32,page=document.getElementById('page'),identity=recordWorkspaceKey173(module),prior=recordState173.get(identity);
 const personal=personalNavigation50()||!teamPermission(module),canAdd=teamPermission(module,'create')&&(module!=='evaluation'||evaluationState182.source==='manager');
 const titles={document:'مستندات الموظفين',custody:'العهد',discipline:'الإنذارات والقرارات',evaluation:'التقييمات'};
 const statuses=module==='custody'?['active','returned']:['pending','revision','approved','rejected','closed'];
 page.innerHTML='<section class="card employee-editor134 record-workspace173 record-table187" aria-busy="true"><header class="employee-editor-identity134"><div class="record-heading187"><strong>'+esc(uiText(titles[module]))+'</strong><span data-person173></span></div><div class="employee-editor-top-actions136"><button id="addRecord" class="primary create-action32" data-create-action="1" disabled><span class="create-icon32 record-add-icon180" aria-hidden="true">+</span><span>'+esc(uiText('إضافة'))+'</span></button><button id="recordFind173" disabled>'+esc(uiText('بحث عن موظف'))+'</button></div></header><div class="record-filters173"><label>'+esc(uiText('البحث في السجلات'))+'<input type="search" id="recordQuery173" placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'"></label><label>'+esc(uiText('الحالة'))+'<select id="recordStatus173"><option value="">'+esc(uiText('الكل'))+'</option>'+statuses.map(k=>'<option value="'+k+'">'+esc(uiText(module==='custody'?(k==='active'?'سارية':'مستردة'):(labels[k]||k)))+'</option>').join('')+'</select></label></div><div class="record-selection187"><button id="recordAll187" type="button" hidden>'+esc(uiText('عرض كل الموظفين'))+'</button><span data-record-total187></span></div><div class="employee-editor-history134" data-record-list173>'+loadingMarkup()+'</div><footer class="employee-editor-foot134" data-record-footer187 hidden><div class="employee-editor-save134"></div><div class="employee-editor-movement134"><span dir="ltr" data-record-count173></span><nav class="employee-nav48" data-record-nav173 aria-label="'+esc(uiText('التنقل بين الموظفين'))+'"></nav></div></footer></section>';
 const host=page.firstElementChild,filters=host.querySelector('.record-filters173');
 if(module==='evaluation')evaluationControls182(host);
 const category=document.createElement('select');category.id='recordCategory173';category.hidden=true;category.dataset.choiceOnly32='1';category.innerHTML='<option value="all"></option>'+recordCategories49[module].map(([k])=>'<option value="'+k+'"></option>').join('');host.append(category);
 const categories=document.createElement('nav');categories.className='employee-editor-sections134 record-categories174';categories.setAttribute('aria-label',uiText('التصنيف'));categories.innerHTML=[['all','الكل'],...recordCategories49[module]].map(([k,label])=>'<button type="button" data-record-category174="'+k+'">'+esc(uiText(label))+'</button>').join('');host.querySelector('header').after(categories);
 if(module==='document'){const label=document.createElement('label');label.innerHTML=esc(uiText('التصفية'))+'<select id="recordExpiry173"><option value="">'+esc(uiText('الكل'))+'</option>'+[['valid','ساري لأكثر من 30 يومًا'],['soon','قارب الانتهاء — خلال 30 يومًا'],['expired','منتهي'],['unknown','بدون تاريخ انتهاء']].map(([k,title])=>'<option value="'+k+'">'+esc(uiText(title))+'</option>').join('')+'</select>';filters.append(label);}
 const add=host.querySelector('#addRecord'),find=host.querySelector('#recordFind173'),allButton=host.querySelector('#recordAll187');
 add.hidden=!canAdd;find.hidden=personal;prepareCreation88(add);
 const controls=host.querySelectorAll('.record-filters173 input,.record-filters173 select,.record-categories174 button');
 controls.forEach(n=>n.disabled=true);
 const current=()=>epoch===navigationEpoch32&&identity===recordWorkspaceKey173(module)&&host.isConnected;
 await recordWorkspaceLoad195({module,host,canAdd,personal,prior,identity,current});
}

function recordExpiry174(module,r,filter){
 if(!filter)return true;const body=JSON.parse(r.body_json||'{}');
 if(module==='document'){const end=body.record_category49==='certificates'?'':body.expiry;if(!end)return filter==='unknown';const days=(Date.parse(end)-Date.parse(dateNow()))/86400000;return filter==='expired'?days<0:filter==='soon'?days>=0&&days<=30:filter==='valid'?days>30:false;}
 if(module==='custody')return custodyState181(body)===filter;return true;
}
function recordFind173(people,id,onChoose){
 let choice=idValue195(id);
 modal('بحث عن موظف','<form id="employeeSearchForm174"><label>'+esc(uiText('الاسم أو كود الموظف'))+'<input id="employeeSearchText174" type="search" autocomplete="off" placeholder="'+esc(uiText('ابحث باسم الموظف أو الكود'))+'"></label><div id="employeeSearchRows174" class="employee-search-rows136" role="radiogroup" aria-label="'+esc(uiText('الموظفون'))+'"></div><div class="modal-foot"><button type="submit" id="employeeSearchConfirm174" class="primary">'+esc(uiText('موافق'))+'</button><button type="button" id="employeeSearchCancel174">'+esc(uiText('إلغاء'))+'</button></div></form>');
 const dialog=document.getElementById('modal'),form=document.getElementById('employeeSearchForm174'),search=document.getElementById('employeeSearchText174'),rows=document.getElementById('employeeSearchRows174'),confirm=document.getElementById('employeeSearchConfirm174');
 const render=()=>{const q=normalize32(search.value),matches=people.filter(u=>!q||employeeSearchText195(u).includes(q));rows.innerHTML=matches.length?matches.map(u=>'<label class="employee-search-choice136"><input type="radio" name="employee_search174" value="'+esc(idKey195(u.id))+'" '+(sameId195(u.id,choice)?'checked':'')+'><span><bdi>'+esc(u.employee_code||'')+'</bdi> — '+employeeNameMarkup143(u.id,u.name,u)+'</span></label>').join(''):empty();confirm.disabled=!matches.some(u=>sameId195(u.id,choice));rows.querySelectorAll('input').forEach(r=>r.onchange=()=>{choice=idValue195(r.value);confirm.disabled=false;});};
 search.oninput=()=>{choice=0;render();};render();if(typeof mobileInput141!=='function'||!mobileInput141())search.focus();document.getElementById('employeeSearchCancel174').onclick=()=>dialog.close();
 form.onsubmit=e=>{e.preventDefault();if(confirm.disabled||!people.some(u=>sameId195(u.id,choice)))return;if(!employeeTransitionAllowed40(document.getElementById('page')))return;dialog.close();onChoose(choice);};
}
function policyWorkspace173(){
 const panel=document.getElementById('staffPanel32');if(!panel||panel.dataset.workspace173)return;panel.dataset.workspace173='1';panel.classList.add('card','employee-editor134');
 const cards=[...panel.querySelectorAll(':scope > .record-groups32 > details[data-employee-id]')];if(!cards.length){const list=panel.querySelector('.record-groups32');if(list)list.innerHTML=empty();policySkeleton173();return;}
 const people=employeeOrder40(users.filter(u=>cards.some(c=>Number(c.dataset.employeeId)===Number(u.id))));let index=0;
 for(const card of cards){
  card.querySelector(':scope > .record-details32')?.classList.add('employee-editor-fields134');
  const history=card.querySelector(':scope > .policy-history32');if(!history)continue;history.classList.add('employee-editor-history134');
  for(const section of [...history.querySelectorAll(':scope > .record-details32')]){const entry=document.createElement('details');entry.className='record-group32';const action=section.querySelector('[data-policy-manage32],[data-policy-copy32],[data-policy-delete32]');if(action){const id=action.dataset.policyManage32||action.dataset.policyCopy32||action.dataset.policyDelete32;if(id)entry.dataset.policyPeriod371=id;}const summary=document.createElement('summary');summary.textContent=section.querySelector('.record-value32')?.textContent||uiText('التفاصيل');section.before(entry);entry.append(summary,section);}
 }

 const search=document.getElementById('policySearch32');if(search)search.oninput=()=>{const q=normalize32(search.value);panel.querySelectorAll('details[data-employee-id]>.record-details32,.policy-history32>.record-group32').forEach(n=>n.hidden=!!q&&!normalize32(n.textContent).includes(q));};
 const selected=employeeValue73();if(selected){const found=people.findIndex(u=>Number(u.id)===selected);if(found>=0)index=found;}
 const head=document.createElement('header');head.className='employee-editor-identity134';head.innerHTML='<div data-policy-person173></div><div class="employee-editor-top-actions136"><button type="button" data-policy-find173>'+esc(uiText('بحث عن موظف'))+'</button></div>';panel.prepend(head);
 const foot=document.createElement('footer');foot.className='employee-editor-foot134';foot.innerHTML='<div class="employee-editor-save134"></div><div class="employee-editor-movement134"><span dir="ltr" data-policy-count173></span><nav class="employee-nav48" data-policy-nav173></nav></div>';panel.append(foot);panel.classList.add('card','employee-editor134','record-workspace173');
 const render=()=>{const u=people[index];employeeSelections73.set(employeeGroup73(),Number(u.id));head.querySelector('[data-policy-person173]').innerHTML='<strong>'+employeeNameMarkup143(u.id,u.name,u)+'</strong><span dir="ltr">'+esc(u.employee_code||u.username||'')+'</span>';for(const c of cards){c.hidden=Number(c.dataset.employeeId)!==Number(u.id);c.toggleAttribute('data-employee-filtered73',c.hidden);c.open=!c.hidden;c.querySelector(':scope > summary').hidden=true;c.querySelectorAll('[data-employee-nav40],[data-employee-history-nav74]').forEach(n=>n.remove());}foot.querySelector('span').textContent=(index+1)+' / '+people.length;const nav=foot.querySelector('nav');nav.innerHTML=employeeNavSteps48.map(([step,title])=>{const n=employeeNavIndex48(people.length,index,step);return '<button type="button" data-policy-step173="'+step+'" aria-label="'+esc(uiText(title))+'" '+(n<0||n>=people.length||n===index?'disabled':'')+'>'+employeeNavIcon48(step)+'</button>';}).join('');nav.querySelectorAll('button').forEach(b=>b.onclick=()=>{index=employeeNavIndex48(people.length,index,b.dataset.policyStep173);render();});};
 panel.selectEmployee173=id=>{const n=people.findIndex(u=>Number(u.id)===Number(id));if(n>=0){index=n;render();return true;}return false;};
 head.querySelector('button').onclick=()=>recordFind173(people,Number(people[index].id),id=>{index=people.findIndex(u=>Number(u.id)===id);render();});render();if(typeof applyEmployeeCards73==='function')applyEmployeeCards73();
}

function policySkeleton173(){
 const panel=document.getElementById('staffPanel32');if(!panel)return;panel.classList.add('card','employee-editor134','record-workspace173');
 panel.insertAdjacentHTML('afterbegin','<header class="employee-editor-identity134"><div data-policy-person173><span class="record-person-pending176" aria-hidden="true"></span></div><div class="employee-editor-top-actions136"><button type="button" disabled>'+esc(uiText('بحث عن موظف'))+'</button></div></header>');
 panel.insertAdjacentHTML('beforeend','<footer class="employee-editor-foot134"><div class="employee-editor-save134"></div><div class="employee-editor-movement134"><span dir="ltr"></span><nav class="employee-nav48">'+employeeNavSteps48.map(([step,title])=>'<button type="button" disabled aria-label="'+esc(uiText(title))+'">'+employeeNavIcon48(step)+'</button>').join('')+'</nav></div></footer>');
}

function recordDialogGuard175(root){
 const dialog=root.closest('dialog')||document.getElementById('modal'),anchor=root.firstElementChild;
 const canClose=()=>!root.dataset.saving173&&!root.querySelector('[data-saving173]')&&employeeTransitionAllowed40(root);
 const close=()=>{if(canClose())dialog?.close();};const cross=document.getElementById('closeModal');if(cross)cross.onclick=close;
 const escape=event=>{if(!canClose())event.preventDefault();};dialog?.addEventListener('cancel',escape);
 const observer=new MutationObserver(()=>{if(!root.isConnected||root.firstElementChild!==anchor)dispose();});observer.observe(root.closest('#modalContent')||root,{childList:true,subtree:true});
 const dispose=()=>{observer.disconnect();dialog?.removeEventListener('cancel',escape);dialog?.removeEventListener('close',dispose);};dialog?.addEventListener('close',dispose,{once:true});
 return {close,dispose};
}
function recordModalLayout174(form){
 form.classList.add('record-modal174');
 const grid=document.createElement('div');grid.className='employee-editor-fields134';
 const foot=form.querySelector('.modal-foot');for(const child of [...form.children]){if(child===foot||child.matches('input[type=hidden]'))continue;if(child.classList.contains('form-grid')){grid.append(...child.children);child.remove();}else grid.append(child);}form.prepend(grid);
 const dialog=form.closest('dialog'),content=form.parentElement,guard=recordDialogGuard175(content);
 const cancel=document.createElement('button');cancel.type='button';cancel.textContent=uiText('إلغاء');cancel.onclick=guard.close;foot?.append(cancel);
 form.querySelectorAll('select').forEach(enhanceSelect32);form.querySelectorAll('input[type=date]').forEach(enhanceDate32);
 const file=form.querySelector('input[type=file]');let url='';
 const release=()=>{if(url){URL.revokeObjectURL(url);url='';}};
 let observer;
 const cleanup=()=>{release();observer?.disconnect();guard.dispose();dialog?.removeEventListener('close',cleanup);window.removeEventListener('pagehide',cleanup);};
 dialog?.addEventListener('close',cleanup,{once:true});window.addEventListener('pagehide',cleanup,{once:true});
 if(content){observer=new MutationObserver(()=>{if(!form.isConnected)cleanup();});observer.observe(form.closest('#modalContent')||content,{childList:true,subtree:true});}
 if(!file)return;const preview=document.createElement('div');preview.className='record-attachment174';preview.hidden=true;file.closest('label').after(preview);
 file.accept='image/jpeg,image/png,image/webp,application/pdf';
 file.addEventListener('change',()=>{release();file.setCustomValidity('');preview.replaceChildren();preview.hidden=true;const selected=file.files[0];if(!selected)return;preview.hidden=false;
 if(selected.size>8*1024*1024||!['image/jpeg','image/png','image/webp','application/pdf'].includes(selected.type)){const message=uiText('نوع المرفق أو حجمه غير مسموح.');file.setCustomValidity(message);preview.textContent=message;return;}
 url=URL.createObjectURL(selected);const link=document.createElement('a');link.href=url;link.target='_blank';link.rel='noopener';link.textContent=uiText('عرض المرفق المختار')+' — '+selected.name;preview.append(link);
 if(selected.type.startsWith('image/')){const image=document.createElement('img');image.src=url;image.alt=uiText('معاينة المرفق المختار');preview.append(image);}else{const hint=document.createElement('p');hint.textContent=uiText('ملف PDF مختار — افتحه من رابط العرض.');preview.append(hint);}
 });
}

// Keep the original persisted field names and expiry consumers; only one name
// and one end-date input are presented to the user.
function documentModalLayout176(form){
 form.classList.add('document-modal176');const grid=form.querySelector('.employee-editor-fields134');
 const rows=[['user_id','record_category49'],['title','date_from','date_to'],['document_number','file']];
 for(const names of rows){const row=document.createElement('div');row.className='document-modal-row176';for(const name of names){const control=form.elements.namedItem(name);const label=control?.closest('label');if(label){label.dataset.documentField176=name;row.append(label);}}if(row.childElementCount)grid.append(row);}const preview=grid.querySelector('.record-attachment174');if(preview)grid.append(preview);
 documentCertificate182(form);
}

function recordDisplayFields176(module,fields,body={},record={}){
 if(body._auto182)return [];
 if(module==='document'&&body.record_category49==='certificates')return Object.entries(fields).filter(([k])=>!['expiry','document_type'].includes(k));
 if(module==='custody')return Object.entries(fields).filter(([key])=>key!=='custody_state49'&&(key!=='asset_type'||body.asset_type&&body.asset_type!==record.title)).map(([key,label])=>[key,key==='asset_type'?'نوع العهدة':label]);
 if(module==='discipline')return Object.entries(fields).filter(([key])=>key==='record_category49'||key==='reason'||key==='payroll_month'&&body.record_category49==='penalty'||key==='related_record49'&&body.record_category49==='appeal'||['decision_type','employee_response'].includes(key)&&body[key]).map(([key,label])=>[key,key==='payroll_month'?'شهر تطبيق الخصم':key==='related_record49'?'القرار محل التظلم':key==='reason'?(body.record_category49==='appeal'?'تفاصيل التظلم':'السبب / التفاصيل'):label]);
 return Object.entries(fields).filter(([key])=>module!=='document'||((key!=='expiry'||!!body.expiry&&body.expiry!==record.date_to)&&(key!=='document_type'||!!body.document_type))).map(([key,label])=>[key,module==='document'&&key==='document_number'?'رقم المستند':label]);}

// V5 demo batch: existing form, translation, attachment and save lifecycle.
function custodyState181(body){return body.return_date?'returned':'active';}
function recordReviewPolicy188(record){return RecordFields194.reviewPolicy(record,typeof boot==='undefined'?null:boot?.modules?.[record.module]);}
function recordStatus188(record){return recordReviewPolicy188(record).state;}
function recordForReview188(record){const status=recordStatus188(record);return status===record.status?record:{...record,status};}
function recordStatusHidden186(record){const body=JSON.parse(record.body_json||'{}'),status=recordStatus188(record);return (!status||status==='active')&&!record.state148&&!body._auto182&&!body._punch39&&['document','request','loan','discipline','evaluation'].includes(record.module);}
function recordBadge181(record,className=''){
 record=recordForReview188(record);
 if(recordStatusHidden186(record))return '';
 if(record.module==='document')return documentBadges182(record,className);
 if(JSON.parse(record.body_json||'{}')._auto182)return '<span class="profile-status33">'+esc(t182('بانتظار اعتماد النقاط','Scoring rules pending'))+'</span>';
 const b=JSON.parse(record.body_json||'{}'),state=record.module==='custody'?custodyState181(b):record.state148||record.status;
 const title=record.module==='custody'?(state==='returned'?'مستردة':'سارية'):record.state148?(typeof permitLabel148==='function'?permitLabel148(state):state):labels[state]||state;
 return '<span class="badge '+esc(className)+' '+esc(state||'')+'">'+esc(uiText(title||'—'))+'</span>';
}
function recordForm181(module,date){
 if(!['custody','discipline'].includes(module))return '';
 const employee=teamPermission(module,'create')?'<label>الموظف<select name="user_id">'+userOptions()+'</select></label>':'',area=(name,label)=>'<label>'+esc(uiText(label))+'<textarea name="'+name+'"></textarea></label>',file='<label>'+esc(uiText('المرفق'))+'<input name="file" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"></label>';
 const common=employee+recordCategoryField49(module);
 return '<form id="modalForm" class="record-form181 '+module+'-form181">'+common+(module==='custody'?
 input('title','اسم العهدة','','text','required maxlength="180"')+input('serial','الرقم / اللوحة')+input('date_from','تاريخ الاستلام',date,'date','required')+area('handover','ملاحظات التسليم')+input('return_date','تاريخ الاسترجاع','','date')+file+area('return_notes','ملاحظات الاسترجاع'):
 input('title','موضوع الإنذار / القرار','','text','required maxlength="180"')+input('date_from','تاريخ الإنذار / القرار',date,'date','required')+input('date_to','نهاية سريان القرار — اختياري','','date')+input('amount','مبلغ الخصم',0,'number','min="0" step="0.001"')+input('payroll_month','شهر تطبيق الخصم','','month')+'<label>'+esc(uiText('القرار محل التظلم'))+'<select name="related_record49"><option value="">'+esc(uiText('اختر القرار الأصلي'))+'</option></select></label>'+area('reason','السبب / التفاصيل')+file)+saveButton()+'</form>';
}
function recordNormalize181(module,raw,body){
 if(module==='document'&&body.record_category49==='certificates'){raw.date_to='';body.expiry='';}
 if(module==='evaluation')body.source182='manager';
 if(module==='custody'){
  delete raw.date_to;delete raw.amount;body.asset_type=raw.title;body.custody_state49=custodyState181(body);
  if(body.return_date&&raw.date_from&&body.return_date<raw.date_from)throw Error(uiText('تاريخ الاسترجاع لا يسبق تاريخ الاستلام.'));
 }
 if(module==='discipline'){
  const category=body.record_category49;
  if(category!=='decision')delete raw.date_to;
  if(category!=='penalty'){raw.amount='0';body.payroll_month='';}
  else if(!/^\d{4}-(0[1-9]|1[0-2])$/.test(body.payroll_month))throw Error(uiText('أدخل شهر تطبيق الخصم.'));
  if(category!=='appeal')body.related_record49='';
  else if(!Number(body.related_record49))throw Error(uiText('اختر قرارًا أصليًا لنفس الموظف.'));
  if(raw.date_to&&raw.date_from&&raw.date_to<raw.date_from)throw Error(uiText('تاريخ النهاية لا يسبق تاريخ البداية.'));
 }
}
function recordModal181(form,module){
 const grid=form.querySelector('.employee-editor-fields134'),control=name=>form.elements.namedItem(name),label=name=>control(name)?.closest('label');
 for(const n of form.querySelectorAll('[name]'))n.closest('label')?.setAttribute('data-record-field181',n.name);
 const width=(name,span)=>{if(label(name))label(name).style.gridColumn=span===3?'1 / -1':'span '+span;};
 width('user_id',2);width('record_category49',1);
 const setLabel=(name,text)=>{const l=label(name);if(!l)return;let title=l.querySelector(':scope > [data-record-label181]');if(!title){title=document.createElement('span');title.dataset.recordLabel181='';for(const n of [...l.childNodes])if(n.nodeType===3)n.remove();l.prepend(title);}title.dataset.textKey138=text;title.textContent=uiText(text);const enhanced=l.querySelector('.search-select32>input,.date-field32>input:not(.date-native32)');enhanced?.setAttribute('aria-label',uiText(text));};
 const show=(name,on)=>{const c=control(name),l=label(name);if(!c||!l)return;l.hidden=!on;c.disabled=!on;for(const n of l.querySelectorAll('.date-field32>input,.date-field32>button'))n.disabled=!on;c._refreshChoice138?.();};
 const references=[label('user_id')?.querySelector('.search-select32>input'),control('user_id'),control('title')].filter(Boolean);
 const measure=()=>{const h=references.map(n=>n.getBoundingClientRect().height).find(h=>h>1);if(h)form.style.setProperty('--record-input181',h+'px');};
 requestAnimationFrame(measure);const observer=typeof ResizeObserver==='function'?new ResizeObserver(measure):null;references.forEach(n=>observer?.observe(n));form.closest('dialog')?.addEventListener('close',()=>observer?.disconnect(),{once:true});
 if(module==='custody'){width('handover',3);width('return_notes',3);width('file',2);return;}
 let loadTicket=0;const cache=new Map(),parent=control('related_record49');
 const loadParents=async()=>{
  const id=Number(control('user_id')?.value||boot.user.id),ticket=++loadTicket;
  parent.innerHTML='<option value="">'+esc(uiText('جارٍ التحميل…'))+'</option>';parent.disabled=true;enhanceSelect32(parent);
  try{
   if(!cache.has(id))cache.set(id,api('list',null,{module:'discipline',employee69:id,workspace173:1}));
   const rows=await cache.get(id);if(ticket!==loadTicket||!form.isConnected)return;
   const matches=rows.filter(r=>Number(r.user_id)===id&&r.module==='discipline'&&recordCategoryOf49('discipline',r)!=='appeal');
   parent.innerHTML='<option value="">'+esc(uiText(matches.length?'اختر القرار الأصلي':'لا توجد قرارات لهذا الموظف'))+'</option>'+matches.map(r=>'<option value="'+Number(r.id)+'">'+esc(dataText137('record_title:discipline',r.title))+' — '+esc(dateDisplay32(r.date_from)||'—')+' — #'+Number(r.id)+'</option>').join('');
  }catch(error){cache.delete(id);if(ticket===loadTicket&&form.isConnected)parent.innerHTML='<option value="">'+esc(uiText('تعذر تحميل القرارات. أعد اختيار الموظف للمحاولة.'))+'</option>';}
  finally{if(ticket===loadTicket&&form.isConnected){parent.disabled=control('record_category49').value!=='appeal';enhanceSelect32(parent);parent._refreshChoice138?.();}}
 };
 const render=()=>{
  const category=control('record_category49').value,decision=category==='decision',penalty=category==='penalty',appeal=category==='appeal';
  show('date_to',decision);show('amount',penalty);show('payroll_month',penalty);show('related_record49',appeal);
  control('payroll_month').required=penalty;parent.required=appeal;
  setLabel('title',appeal?'موضوع التظلم':decision?'موضوع القرار':'موضوع الإنذار / القرار');
  setLabel('date_from',appeal?'تاريخ التظلم':decision?'بداية سريان القرار':'تاريخ الإنذار / القرار');
  setLabel('reason',appeal?'تفاصيل التظلم':'السبب / التفاصيل');
  width('title',decision?3:2);width('reason',3);width('related_record49',2);width('file',decision||penalty||appeal?1:3);
  const order=['user_id','record_category49','title','date_from','date_to','amount','payroll_month','related_record49',...(decision||penalty||appeal?['file','reason']:['reason','file'])];
  for(const name of order)if(label(name))grid.append(label(name));const preview=grid.querySelector('.record-attachment174');if(preview)grid.append(preview);
  if(appeal)void loadParents();else{loadTicket++;parent.value='';parent._refreshChoice138?.();}
  measure();
 };
 control('record_category49').addEventListener('change',render);control('user_id')?.addEventListener('change',()=>{parent.value='';if(control('record_category49').value==='appeal')void loadParents();});render();
}
