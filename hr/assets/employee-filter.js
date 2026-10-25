'use strict';
// Selection belongs to a hub and an identity. It never changes mutation payloads.
const employeeScreens73=new Set(['users','workforce','employment','report','request','external','tracking','proof','policies','document','work_permit','custody','discipline','evaluation','payroll','commissions','collection37','payroll_review35','bank','vouchers37','payments26','payout_status','loan','v28_debt','allowances','addition','entitlement','eos','video_auto','video_custom','video_receipts','analytics','v26_history']);
const employeeSelections73=new Map();let employeeContext73='';
function employeeEnabled73(key=view){return employeeScreens73.has(key)&&!personalNavigation50();}
function financeFilter118(key){return typeof financeShellKeys101!=='undefined'&&financeShellKeys101.has(key);}
function financeNoEmployee118(key){return ['receipt_company99','receipt_click99','receipt_afaq99'].includes(key);}
function employeeGroup73(key=view){return typeof payrollEntryFilter127==='function'&&payrollEntryFilter127(key)?key+'|'+(payrollEntrySections127.get(key)||'salary'):financeFilter118(key)?key:hubFor32(key)?.[0]||key;}
function employeeValue73(key=view){return employeeEnabled73(key)&&!financeNoEmployee118(key)?Number(employeeSelections73.get(employeeGroup73(key))||0):0;}
function employeeRows73(rows,key='user_id'){const id=employeeValue73();return id?rows.filter(r=>Number(r[key])===id):rows;}
function employeeReadParams73(action,data,params){
 if(params.page195||data!==null||!employeeEnabled73()||document.querySelector('dialog[open]'))return params;
 const id=employeeValue73();if(!id)return params;
 const allowed=['list','v26_info','v28_info','workforce_list','locations','v26_historical','v26_payments','bank_list','allowance_info','payroll_list','v26_video_list','commission_info'];
 if(!allowed.includes(action))return params;
 // Auxiliary company-file reads for employee editors must remain complete.
 if(action==='v28_info'&&(view!=='v28_debt'||params.kind!=='debt_advance'))return params;
 return {...params,employee73:id,...(['list','v26_info'].includes(action)?{employee69:id}:{})};
}
function prepareEmployeeFilter73(key){
 if(typeof recordModules173!=='undefined'&&recordModules173.includes(key))return;
 if(key==='users'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134())return;
 if(!employeeEnabled73(key))return;
 const context=screenIdentity38();if(context!==employeeContext73){employeeSelections73.clear();employeeContext73=context;}
 const host=$('#portalTabs32');if(!host)return;

 const box=operationalBox69(host),label=document.createElement('label');label.className='leave-employee-filter69 employee-filter73';label.dataset.employeeFilter73=key;
 label.innerHTML='<span>'+esc(uiText('الموظف'))+'</span><select id="employeeFilter73" disabled><option value="">'+esc(uiText('كل الموظفين في نطاقك'))+'</option></select>';box.append(label);
 // Existing report selectors are adopted once their original render has completed.
 if(financeNoEmployee118(key)){employeeSelections73.delete(employeeGroup73(key));label.querySelector('select').innerHTML='<option value="">'+esc(uiText('لا ينطبق — تحصيل مباشر'))+'</option>';return;}
 if(['report','analytics'].includes(key)||(key==='users'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134()))return;
 const select=label.querySelector('select'),epoch=navigationEpoch32;
 select.onchange=()=>changeEmployeeFilter73(select);
 if(financeFilter118(key)||(typeof payrollEntryFilter127==='function'&&payrollEntryFilter127(key)))return; // finance103_info supplies the same scoped choices in its single screen response.
 const choicesDate118=financeFilter118(key)?financePeriods101.get(hubFor32(key)?.[0]||key)?.to||dateNow():null;
 api('user_choices29',null,{screen:key==='policies'?'policy_staff':key,...(financeFilter118(key)?{date118:choicesDate118}: {})}).then(rows=>{
  if(epoch!==navigationEpoch32||!select.isConnected)return;
  if(financeFilter118(key)){if((financePeriods101.get(hubFor32(key)?.[0]||key)?.to||dateNow())===choicesDate118)setFinanceEmployeeOptions118(rows,key);return;}
  select.innerHTML='<option value="">'+esc(uiText('كل الموظفين في نطاقك'))+'</option>'+orderedStaff32(rows).map(u=>employeeOption143(u.id,u.name,u.employee_code||'',false,u)).join('');
  const id=employeeValue73(key);if(id&&!rows.some(u=>Number(u.id)===id))select.insertAdjacentHTML('beforeend','<option disabled value="'+id+'">'+esc(uiText('الموظف خارج نطاق هذا التبويب'))+'</option>');
  select.value=id?String(id):'';select.disabled=false;applyInterfaceLanguage();
 }).catch(e=>{if(epoch===navigationEpoch32&&!e.stale32)toast(e.message);});
}
async function changeEmployeeFilter73(select){
 if(view==='users'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134())return employeeSelect134(Number(select.value)||0,select);
 if(typeof payrollEntryFilter127==='function'&&payrollEntryFilter127(view)) {employeeSelections73.set(employeeGroup73(),Number(select.value)||0);$('#entrySearch127')?.dispatchEvent(new Event('input',{bubbles:true}));return;}
 const before=employeeValue73();
 if(view==='users'&&$('.profile-tabs29')&&!employeeTransitionAllowed40($('#page'))){select.value=before?String(before):'';return;}
 if(typeof screenSave38!=='undefined')screenSave38=null;
 const selected=Number(select.value)||0;employeeSelections73.set(employeeGroup73(),selected);
 if(['payroll','commissions','collection37'].includes(view)&&$('#gridSearch37')){$('#gridSearch37').dispatchEvent(new Event('input',{bubbles:true}));return;}
 if(view==='vouchers37'&&$('#voucherSearch37')){$('#voucherSearch37').dispatchEvent(new Event('input',{bubbles:true}));return;}
 if(view==='policies'){document.getElementById('staffPanel32')?.selectEmployee173?.(selected);applyEmployeeCards73();return;}
 // Preserve the current search while narrowing the server read. No data is saved.
 const category=document.querySelector('[data-category49][aria-current="page"]')?.dataset.category49;
 const controls=['recordStatus49','recordExpiry49'].map(id=>({id,value:document.getElementById(id)?.value}));
 const searches=[...document.querySelectorAll('#page input[type="search"],#portalTabs32 input[type="search"]')].filter(n=>n.id||n.hasAttribute('data-record-search32')).map(n=>({id:n.id,value:n.value,record:n.hasAttribute('data-record-search32')}));
 select.disabled=true;const target=view;
 try{const loading=navigate(target),epoch=navigationEpoch32;await loading;if(view!==target||epoch!==navigationEpoch32)return;if(category)document.querySelector('[data-category49="'+CSS.escape(category)+'"]')?.click();for(const c of controls){const n=document.getElementById(c.id);if(n&&c.value){n.value=c.value;n.dispatchEvent(new Event('change',{bubbles:true}));}}for(const s of searches){const node=s.id?document.getElementById(s.id):document.querySelector('[data-record-search32]');if(node&&s.value){node.value=s.value;node.dispatchEvent(new Event('input',{bubbles:true}));}}}finally{if(select.isConnected)select.disabled=false;}
}
function applyEmployeeCards73(){
 if(view!=='policies')return;
 const id=employeeValue73(),label=document.querySelector('[data-employee-filter73]');
 if(label&&label.hidden!==(policyTab32==='general'||(policyTab32==='staff'&&!!document.getElementById('staffPanel32')?.dataset.workspace173))){label.hidden=policyTab32==='general'||(policyTab32==='staff'&&!!document.getElementById('staffPanel32')?.dataset.workspace173);scheduleStickyTabs48();}
 document.querySelectorAll('#staffPanel32 [data-employee-id],#temporaryPanel38 [data-employee-id]').forEach(n=>{
  const hide=!!id&&Number(n.dataset.employeeId)!==id;
  if(n.hasAttribute('data-employee-filtered73')!==hide)n.toggleAttribute('data-employee-filtered73',hide);
 });
}
function mountEmployeeFilter73(){
 if(!employeeEnabled73())return;
 const label=document.querySelector('[data-employee-filter73]');if(!label)return;
 if(['custody','discipline','evaluation','request'].includes(view)&&!hubFor32(view)){
  const card=$('#recordTabs49');if(card&&!card.contains(label))card.append(label);
 }
 const native=view==='report'?$('#reportUser'):view==='analytics'?$('#v26ReportForm select[name="user_id"]'):null;
 if(native&&!label.contains(native)){
  const sourceLabel=native.closest('label');if(!native.options.length){const option=document.createElement('option');option.value='';option.textContent=uiText('كل الموظفين في نطاقك');option.disabled=true;option.selected=true;native.append(option);}
  const placeholder=label.querySelector('select'),form=native.closest('form');if(form?.id)native.setAttribute('form',form.id);
  (placeholder?.closest('.search-select32')||placeholder)?.remove();label.append(native.closest('.search-select32')||native);if(sourceLabel&&sourceLabel!==label&&!sourceLabel.querySelector('input,select,textarea,button,a'))sourceLabel.remove();
  const previous=native.onchange;native.onchange=e=>{employeeSelections73.set(employeeGroup73(),Number(native.value)||0);previous?.call(native,e);if(view==='report'){const run=$('#runReport'),exp=$('#exportReport');if(run)run.disabled=false;if(exp)exp.disabled=false;run?.click();}else $('#v26ReportForm')?.requestSubmit();};
 }
 if(native&&!native.disabled&&!native.dataset.employeeInitial73){
  native.dataset.employeeInitial73='1';const selected=employeeValue73();
  if(selected){
   if([...native.options].some(o=>Number(o.value)===selected)){const changed=Number(native.value)!==selected;native.value=String(selected);if(changed)native.dispatchEvent(new Event('change',{bubbles:true}));}
   else {native.insertAdjacentHTML('beforeend','<option disabled value="'+selected+'">'+esc(uiText('الموظف خارج نطاق هذا التبويب'))+'</option>');native.value=String(selected);if(view==='report'){const area=$('#reportData');if(area)area.replaceChildren();$('#runReport').disabled=$('#exportReport').disabled=true;}}
  }
 }
 applyEmployeeCards73();
}

function setFinanceEmployeeOptions118(rows,key=view){
 if(!financeFilter118(key)||key!==view)return;
 const label=document.querySelector('[data-employee-filter73]'),select=label?.querySelector('select');if(!select)return;
 if(financeNoEmployee118(key)){employeeSelections73.delete(employeeGroup73(key));select.innerHTML='<option value="">'+esc(uiText('لا ينطبق — تحصيل مباشر'))+'</option>';select.disabled=true;applyInterfaceLanguage();return;}
 const people=rows.map(p=>({id:Number(p.id??p.user_id),employee_code:p.employee_code??p.code,name:p.name,profile:p.profile||{name_en:p.name_en||''}}));
 const id=employeeValue73(key);if(id&&!people.some(p=>p.id===id))employeeSelections73.delete(employeeGroup73(key));
 select.innerHTML='<option value="">'+esc(uiText('كل الموظفين في نطاقك'))+'</option>'+orderedStaff32(people).map(p=>employeeOption143(p.id,p.name,p.employee_code,false,p)).join('');select.value=employeeValue73(key)?String(employeeValue73(key)):'';select.disabled=false;applyInterfaceLanguage();
}
