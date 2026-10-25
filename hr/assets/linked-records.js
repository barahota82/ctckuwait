'use strict';
function recordIdentity32(employeeId,name,recordId=0){return '<span data-employee-id="'+Number(employeeId||0)+'"'+(recordId?' data-record-id="'+Number(recordId)+'"':'')+'>'+(typeof employeeNameMarkup143==='function'?employeeNameMarkup143(employeeId,name):esc(name))+'</span>';}
function focusLinkedElement32(el){
 if(!el)return false;let parent=el;while(parent){if(parent.tagName==='DETAILS')parent.open=true;parent=parent.parentElement;}
 const group=el.matches('details')?el:el.closest('details');if(group)group.open=true;
 const focus=el.matches('.record-details32')?el:(group||el.closest('tr')||el);focus.classList.add('linked-focus32');focus.setAttribute('tabindex','-1');focus.focus({preventScroll:true});focus.scrollIntoView({behavior:'instant',block:'center'});setTimeout(()=>focus.classList.remove('linked-focus32'),3500);return true;
}
async function openLinked32(screen,meta={}){
 const source=view,scroll=window.scrollY,aliases={v28_schedule:'policies',corporate_document:'v28_documents',company_file:'v28_files',attachment_archive:'v28_archive'};screen=aliases[screen]||screen;
 if(screen==='video_receipt'&&meta.record_id){await v26Receipt(Number(meta.record_id));return;}
 if(!screenAllowed(screen))throw Error(uiText('السجل غير متاح ضمن صلاحياتك.'));
 const employeeId=Number(meta.employee_id||0),recordId=Number(meta.record_id||0);
 if(screen==='policies'&&employeeId&&typeof policyTab32!=='undefined'){policyTab32='staff';if(view==='policies')document.querySelector('#staffTab32')?.click();}
 // Legacy balance notices without an unambiguous employee link must not pick a namesake.
 if(screen==='leave_overview'&&!employeeId)throw Error(uiText('هذا التنبيه القديم لا يحدد الموظف بشكل مؤكد. افتح الأرصدة واختر الموظف.'));
 const selectWorkspace175=async()=>{if(!employeeId)return null;if(screen==='policies'){const panel=document.getElementById('staffPanel32');return typeof panel?.selectEmployee173==='function'?panel.selectEmployee173(employeeId):null;}if(typeof recordModules173==='undefined'||!recordModules173.includes(screen))return null;const host=document.querySelector('#page [data-record-workspace-module175]');if(!host||host.dataset.recordWorkspaceModule175!==screen)return null;return await host.selectEmployee173?.(employeeId);};
 if(view===screen){
  const selected175=await selectWorkspace175();if(selected175===false)return;if(selected175===true&&!recordId){focusLinkedElement32(document.querySelector('#page [data-person173],#page [data-policy-person173]'));return;}
  compactTables32(document.querySelector('#app'));
  const existing=recordId?(screen==='policies'&&employeeId?document.querySelector('#staffPanel32 [data-policy-period371="'+recordId+'"]'):document.querySelector('#page [data-record-id="'+recordId+'"]')):employeeId?document.querySelector('#page details[data-employee-id="'+employeeId+'"]'):null;
  if(existing&&!['payroll','customer_cases'].includes(screen)){focusLinkedElement32(existing);return;}
 }
 await navigate(screen,{record_id:recordId});if(view!==screen)return;
 const selected175=await selectWorkspace175();if(selected175===false)return;if(selected175===true&&!recordId){focusLinkedElement32(document.querySelector('#page [data-person173],#page [data-policy-person173]'));return;}
 compactTables32(document.querySelector('#app'));applyInterfaceLanguage();
 let target=null;
 if(recordId){target=screen==='policies'&&employeeId?document.querySelector('#staffPanel32 [data-policy-period371="'+recordId+'"]'):null;target=target||document.querySelector('#page .record-details32[data-record-id="'+recordId+'"]')||document.querySelector('#page [data-record-id="'+recordId+'"]');
  if(!target&&screen==='customer_cases')target=document.querySelector('#page [data-case="'+recordId+'"]');
  if(!target&&boot.modules?.[screen]){const list=await api('list',null,{module:screen,record_id:recordId});const record=list.find(r=>Number(r.id)===recordId);if(record){recordDetail(record);return;}}
 }
 if(!target&&employeeId)target=document.querySelector('#page details[data-employee-id="'+employeeId+'"]')||document.querySelector('#page [data-employee-id="'+employeeId+'"]');
 if(screen==='payroll'&&!recordId&&employeeId&&meta.month){target=[...document.querySelectorAll('#page [data-employee-id="'+employeeId+'"]')].find(el=>el.closest('tr')?.textContent.includes(meta.month))||null;}
 if(target&&screen==='customer_cases'&&target.matches('[data-case]'))target.click();
 if(target&&screen==='payroll'){const button=target.closest('tr')?.querySelector('[data-v26pay]');if(button)button.click();}
 if((employeeId||recordId)&&!focusLinkedElement32(target))toast('السجل غير متاح ضمن صلاحياتك.');
 if(source==='notifications'){
  const back=document.createElement('button');back.className='linked-back32';back.textContent=uiText('الرجوع إلى الإشعارات');back.onclick=async()=>{await navigate('notifications');window.scrollTo({top:scroll,behavior:'auto'});};document.querySelector('#page .page-head')?.append(back);
 }
}
