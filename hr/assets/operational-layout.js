'use strict';
const operationalScreens68=['report','request','external','tracking','proof','policies','v28_locations','leave','sick','leave_overview','leave_cash','resumption'];
function operationalView68(){return operationalScreens68.includes(view)&&(view!=='request'||requestMode50==='attendance');}
function mountOperationalChrome68(){
 if(!operationalView68())return;
 const portal=document.querySelector('#portalTabs32'),page=document.querySelector('#page');if(!portal||portal.hidden||!page)return;
 let box=portal.querySelector('[data-operational-controls68]');
 const ensure=()=>{if(!box){box=document.createElement('div');box.className='operational-controls68';box.dataset.operationalControls68='';portal.append(box);}return box;};
 const move=node=>{if(node&&!node.closest('[data-operational-controls68]')&&!node.closest('.record-filter-row75,.header-secondary77'))ensure().append(node);};
 if(view==='policies'){
  move(page.querySelector('[role="tablist"]')?.closest('.tab-scroll53')||page.querySelector('[role="tablist"]'));
  for(const [id,tab]of [['generalSearch69','general'],['policySearch32','staff'],['temporarySearch38','temporary'],['newTemporary38','temporary']]){const node=document.getElementById(id);if(!node)continue;const control=id==='newTemporary38'?node:node.closest('label');move(control);control.hidden=policyTab32!==tab;}
 }else{
  if(view==='report')move(page.querySelector('#reportUser')?.closest('.toolbar'));
  move(page.querySelector('[data-record-search-host32]>.record-search-label32'));
  if(page.querySelector('#v26Search32'))portal.querySelector('[data-resumption-loading68]')?.remove();
  move(page.querySelector('#v26Search32')?.closest('label'));
  move(page.querySelector('#leaveBalanceUser')?.closest('label'));
 }
 if(typeof mountRecordFilters75==='function')mountRecordFilters75();
 if(typeof mobileTabsRefresh33==='function')mobileTabsRefresh33();
 if(typeof scheduleStickyTabs48==='function')scheduleStickyTabs48();
}
async function operationalRecords68(module){if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 recordCategory49='all';
 const epoch=navigationEpoch32,meta=boot.modules[module],incident=module==='request'&&requestMode50==='attendance';let rows=[],revision=0;
 const canAdd=teamPermission(module,'create')||(meta.employee&&personalPermission34(module,'create'));
 const personalLeave=module==='leave'&&boot.user.role!=='admin'&&boot.access_mode==='personal';
 $('#page').innerHTML=head(meta.label,'',canAdd?'<button id="addRecord" class="primary">'+esc(uiText(incident?'+ مراجعة واقعة حضور':'+ إضافة'))+'</button>':'')+
 (module==='leave'&&boot.user.role==='admin'&&leaveEmployeeValue69()?'<section class="card"><div id="leaveBalanceData"></div></section>':'')+
 '<div id="operationalSummary68"></div><section class="card" id="moduleRecords32" data-record-search-host32><div data-record-results32>'+loadingMarkup()+'</div></section>';
 const host=$('#moduleRecords32'),add=$('#addRecord'),summary=$('#operationalSummary68');
 if(add){add.disabled=boot.user.role!=='admin';add.onclick=()=>incident?punchRequestModal39():recordModal(module);}
 const render=()=>{host.querySelector('[data-record-results32]').innerHTML=recordTable49(module,rows);host.querySelectorAll('[data-record]').forEach(b=>b.onclick=()=>recordDetail(rows.find(r=>r.id==b.dataset.record)));};
 const params=q=>({module,...leaveEmployeeParams69(module),...(module==='request'?{request_scope50:requestMode50}:{}),...(q===undefined?{}:{q})});
 remoteRecordSearch32(host,async(q,current)=>{const ticket=++revision,result=await api('list',null,params(q));if(!current()||ticket!==revision||epoch!==navigationEpoch32)return;rows=result;render();});

 installScreenActions50();mountOperationalChrome68();applyInterfaceLanguage();
 const initial=revision;
 const [loaded,choices,overview,balance]=await Promise.all([api('list',null,params()),boot.user.role!=='admin'?(leaveEmployeeScreens69.includes(module)?leaveEmployeeChoices69:api('user_choices29',null,{screen:module})):Promise.resolve(null),module==='sick'&&teamPermission('sick')?api('sick_overview'):Promise.resolve(null),personalLeave?api('leave_balance'):module==='leave'&&boot.user.role==='admin'&&leaveEmployeeValue69()?api('leave_balance',null,{user_id:leaveEmployeeValue69()}):Promise.resolve(null)]);
 if(epoch!==navigationEpoch32||!host.isConnected)return;
 if(choices)users=choices;if(add)add.disabled=boot.user.role!=='admin'&&leaveEmployeeScreens69.includes(module)&&!leaveEmployeeReady69;
 if(initial===revision){rows=loaded;render();}
 if(overview){const summaries=overview.filter(x=>!leaveEmployeeValue69()||Number(x.id)===leaveEmployeeValue69()).map(x=>[recordIdentity32(x.id,x.name),x.error?esc(x.error):x.used,x.full??'—',x.half??'—',x.unpaid??'—',x.year_from?esc(x.year_from)+' — '+esc(x.year_to):'—',x.show_sick?'مسموح':'مخفي']);summary.innerHTML='<section class="card space-bottom"><h2>'+esc(uiText('متابعة المرضيات السنوية حتى اليوم'))+'</h2>'+table(['الموظف','المستخدم','كامل','نصف','دون أجر','السنة','عرض العدد للموظف'],summaries)+'</section>';}
 if(balance&&personalLeave)summary.innerHTML='<div class="cards">'+stat('الرصيد المرجعي',balance.entitlement??'غير محدد',balance.year_from+' — '+balance.year_to,'◇')+stat('المستخدم',balance.used,'طلبات معتمدة','◷')+stat('المتبقي',balance.remaining??'غير محدد','حسب الرصيد المضبوط','◈')+stat('قيد المراجعة',balance.pending,'أيام بطلبات معلقة','▤')+'</div>';
 if(balance&&!personalLeave&&$('#leaveBalanceData'))$('#leaveBalanceData').innerHTML='<div class="cards">'+stat('المستخدم',balance.used,'طلبات معتمدة','◷')+stat('المتبقي',balance.remaining??'غير محدد','حسب الرصيد المضبوط','◈')+stat('قيد المراجعة',balance.pending,'أيام بطلبات معلقة','▤')+'</div>';
 applyInterfaceLanguage();
}

const leaveEmployeeScreens69=['leave','sick','leave_overview','leave_cash','resumption'];
let leaveEmployeeSelection69='',leaveEmployeeContext69='',leaveEmployeeChoices69=Promise.resolve([]),leaveEmployeeReady69=false;
function leaveEmployeeValue69(){return Number(leaveEmployeeSelection69)||0;}
function leaveEmployeeParams69(key){return leaveEmployeeScreens69.includes(key)?{employee69:leaveEmployeeValue69()}:{};}
function operationalBox69(portal){let box=portal.querySelector('[data-operational-controls68]');if(!box){box=document.createElement('div');box.className='operational-controls68';box.dataset.operationalControls68='';portal.append(box);}return box;}
function prepareOperationalHeader68(key){
 const portal=document.querySelector('#portalTabs32');if(!portal||portal.hidden)return;
 if(leaveEmployeeScreens69.includes(key)){
  const context=typeof screenIdentity38==='function'?screenIdentity38():String(boot.user.id);
  if(context!==leaveEmployeeContext69){leaveEmployeeContext69=context;leaveEmployeeSelection69='';}
  leaveEmployeeReady69=false;const epoch=navigationEpoch32,box=operationalBox69(portal),label=document.createElement('label');label.className='leave-employee-filter69';label.innerHTML='<span>'+esc(uiText('الموظف'))+'</span><select id="leaveEmployee69" disabled><option value="">'+esc(uiText('كل الموظفين في نطاقك'))+'</option></select>';box.append(label);
  const select=label.querySelector('select');select.onchange=()=>{leaveEmployeeSelection69=select.value;navigate(view);};
  leaveEmployeeChoices69=api('user_choices29',null,{screen:key}).then(rows=>{
   if(epoch!==navigationEpoch32||!select.isConnected)return rows;
   select.innerHTML='<option value="">'+esc(uiText('كل الموظفين في نطاقك'))+'</option>'+rows.map(u=>employeeOption143(u.id,u.name,u.employee_code||'',false,u)).join('');
   if(leaveEmployeeSelection69&&!rows.some(u=>Number(u.id)===leaveEmployeeValue69()))select.insertAdjacentHTML('beforeend','<option disabled value="'+leaveEmployeeValue69()+'">'+esc(uiText('الموظف خارج نطاق هذا التبويب'))+'</option>');
   select.value=leaveEmployeeSelection69;select.disabled=false;leaveEmployeeReady69=true;applyInterfaceLanguage();return rows;
  }).catch(e=>{if(epoch===navigationEpoch32&&!e.stale32)toast(e.message);return [];});
 }
 if(['leave_overview','tracking','v28_locations','report'].includes(key)){
  const label=document.createElement('label');label.className='record-search-label32';label.innerHTML='<span class="record-search-title32">'+esc(uiText('بحث في السجلات'))+'</span><input type="search" data-record-search32="1" placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'">';operationalBox69(portal).append(label);
 }
 if(key==='resumption'){
  const label=document.createElement('label');label.className='record-search-label32';label.dataset.resumptionLoading68='';label.innerHTML='<span class="record-search-title32">'+esc(uiText('بحث في السجلات'))+'</span><input type="search" disabled placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'">';operationalBox69(portal).append(label);
 }
}
