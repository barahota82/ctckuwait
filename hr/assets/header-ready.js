'use strict';
// Reserve real header controls before asynchronous reads, without duplicate IDs or handlers.
const headerFields79={
 v28_debt:[['#debtFrom95','من تاريخ','date'],['#debtTo95','إلى تاريخ','date']],
 debt_statement95:[['#debtFrom95','من تاريخ','date'],['#debtTo95','إلى تاريخ','date']],
 report:[['#reportFrom','من تاريخ','date'],['#reportTo','إلى تاريخ','date']],
 bank:[['#bankMonth','الشهر والسنة','month'],['#bankCompany','الشركة','select'],['#bankName28','البنك','select']],
 payroll:[['#gridCompany41','شركة العمل','select'],['#payMonth35','الشهر والسنة','month'],['#gridDate37','تاريخ الحركة','date']],
 commissions:[['#gridCompany41','شركة العمل','select'],['#payMonth35','الشهر والسنة','month'],['#gridDate37','تاريخ الحركة','date']],
 collection37:[['#gridCompany41','شركة العمل','select'],['#payMonth35','الشهر والسنة','month']],
 payroll_review35:[['#reviewCompany41','شركة العمل','select'],['#v26Month','الشهر والسنة','month']],
 vouchers37:[['#payMonth35','الشهر والسنة','month']],
 analytics:[['#v26ReportForm [name=from]','من تاريخ','date'],['#v26ReportForm [name=to]','إلى تاريخ','date'],['#v26ReportForm [name=company]','الشركة','select'],['#v26ReportForm [name=department]','القسم','select']],
 audit:[['#audit26 [name=from]','من تاريخ','date'],['#audit26 [name=to]','إلى تاريخ','date'],['#audit26 [name=actor]','منفذ العملية','select'],['#audit26 [name=action_type]','نوع العملية','text']]
};
const headerActionIds80=new Set(['runReport','exportReport','generalTab32','staffTab32','temporaryTab38','newTemporary38','buildBank','exportBank']);
function markHeaderActions80(root){
 for(const node of root.querySelectorAll('button,a.button')){
  const id=node.id||node.dataset.pendingAction79||node.dataset.policyAction80;
  if(['runReport','exportReport','exportLeave','leaveAlerts26'].includes(id)){
   node.classList.add('primary','create-action32');node.classList.remove('standard-action71');node.dataset.addLike93='';delete node.dataset.headerTone80;delete node.dataset.headerAction80;
   if(typeof prepareCreation88==='function')prepareCreation88(node);continue;
  }
  if(id==='newTemporary38'){node.dataset.headerAction80=id;delete node.dataset.headerTone80;node.classList.add('primary','create-action32');continue;}
  if(!headerActionIds80.has(id))continue;
  node.dataset.headerAction80=id;
  if(['generalTab32','staffTab32','temporaryTab38'].includes(id))node.dataset.headerTone80='secondary';
  else if(['runReport','buildBank'].includes(id))node.dataset.headerTone80='action';
 }
}
function resetHeaderActions80(){
 for(const button of document.querySelectorAll('#portalTabs32 [data-live-action80],#portalTabs32 [data-top-actions49]>button[id],#portalTabs32 [data-top-actions49]>a.button[id],#portalTabs32 .toolbar:not(form) button[id],#portalTabs32 .toolbar:not(form) a.button[id]')){
  const id=button.id;if(!id)continue;const pending=button.cloneNode(true);pending.removeAttribute('id');pending.removeAttribute('onclick');delete pending.dataset.liveAction80;pending.dataset.pendingAction79=id;pending.disabled=true;pending.setAttribute('aria-busy','true');const tools=button.closest('.toolbar:not(form)');if(tools)tools.dataset.chrome79='';button.replaceWith(pending);
 }
}
function prepareHeader79(key){
 if(!headerStackScreens77.has(key))return;
 const root=recordFilterHost75();if(!root)return;
 if(typeof prepareCreateActions82==='function')prepareCreateActions82(key,root);
 let row=root.querySelector('[data-record-filter-row75]');if(!row){row=document.createElement('div');row.className='record-filter-row75';row.dataset.recordFilterRow75='';root.append(row);}
 if(key!=='policies'&&!root.querySelector('input[type=search]')){
  const label=document.createElement('label');label.className='record-search-label32';label.dataset.chrome79='';label.innerHTML='<span class="record-search-title32">'+esc(uiText('البحث في السجلات'))+'</span><input type="search" data-record-search32 data-unified-search75 data-pending-search79 disabled>';row.append(label);
 }
 const fields=[...(headerFields79[key]||[])].filter(([selector])=>!(selector==='#reviewCompany41'&&boot.access_mode==='personal'));
 if(key==='policies'){
  const general=boot.user.role==='admin'||teamPermission('policy_general'),staff=boot.user.role==='admin'||teamPermission('policy_staff');
  if((policyTab32==='general'&&!general)||(policyTab32==='staff'&&!staff))policyTab32=staff?'staff':'general';
  const person=root.querySelector('[data-employee-filter73]');if(person)person.hidden=policyTab32==='general';
  if(!root.querySelector('.record-search-label32')&&!document.querySelector('#generalSearch69,#policySearch32,#temporarySearch38')){
   const label=document.createElement('label');label.className='record-search-label32';label.dataset.chrome79='';label.dataset.pendingPolicySearch81='';label.innerHTML='<span class="record-search-title32">'+esc(uiText('البحث في السجلات'))+'</span><input type="search" disabled>';row.append(label);
  }
 }

 if(key==='policies'&&!root.querySelector('[data-pending-policy-tabs79],#generalTab32')){
  const box=operationalBox69(root),nav=document.createElement('nav');nav.className='portal-tabs32';nav.dataset.chrome79='';nav.dataset.pendingPolicyTabs79='';
  const general=boot.user.role==='admin'||teamPermission('policy_general'),staff=boot.user.role==='admin'||teamPermission('policy_staff');
  for(const [tab,title,allowed]of [['general','القواعد والدوام الافتراضي',general],['staff','دوام الموظفين',staff],['temporary','الجداول المؤقتة',general||staff]])if(allowed){const button=document.createElement('button');button.type='button';button.disabled=true;button.dataset.policyAction80=({general:'generalTab32',staff:'staffTab32',temporary:'temporaryTab38'})[tab];button.textContent=uiText(title);button.setAttribute('aria-selected',String(tab===policyTab32));nav.append(button);}box.append(nav);
 }

 if(typeof recordCategories49!=='undefined'&&recordCategories49[key]){
  fields.push(['#recordStatus49','الحالة','select']);if(['document','custody'].includes(key))fields.push(['#recordExpiry49','التصفية','select']);
 }
 for(const [selector,title,type]of fields){
  if(document.querySelector(selector)||[...root.querySelectorAll('[data-pending-field79]')].some(n=>n.dataset.pendingField79===selector))continue;
  const label=document.createElement('label');label.dataset.chrome79='';label.dataset.pendingField79=selector;
  const text=document.createElement('span');text.textContent=uiText(title);const input=document.createElement(type==='select'?'select':'input');if(type!=='select')input.type=type;else{const option=document.createElement('option');option.value='';option.textContent=uiText('الكل');input.append(option);}input.disabled=true;input.setAttribute('aria-busy','true');label.append(text,input);row.append(label);
 }
 let actions=key==='report'?[['runReport','عرض التقرير'],['exportReport','تصدير Excel']]:key==='bank'?[['buildBank','إنشاء مسودات الشهر'],['exportBank','تصدير Excel']]:[];
 if(key==='leave_overview')actions=[...(boot.user.role==='admin'?[['leaveAlerts26','فحص تنبيهات الأرصدة الآن']]:[]),...(teamPermission('leave_overview','export')?[['exportLeave','تصدير Excel']]:[])];
 if(key==='allowances'&&canWrite29('allowances'))actions=[['addAllowance','بدلات بتاريخ سريان'],...(boot.user.role==='admin'?[['fridayRate','قيمة الجمعة بتاريخ سريان']]:[])];
 if(actions.length){
  const mainRow92=['report','leave_overview','allowances'].includes(key);
  let tools=mainRow92?root.querySelector('[data-top-actions49]'):root.querySelector('[data-pending-toolbar80]');
  if(!tools){tools=document.createElement('div');tools.className=mainRow92?'top-actions49':'toolbar';if(mainRow92)tools.dataset.topActions49='';else tools.dataset.pendingToolbar80='';tools.dataset.chrome79='';root.append(tools);}
  for(const [id,title]of actions){if(document.getElementById(id)||root.querySelector('[data-pending-action79="'+id+'"]'))continue;const button=document.createElement('button');button.type='button';button.disabled=true;button.dataset.pendingAction79=id;button.textContent=uiText(title);if(id==='addAllowance')button.className='primary';tools.append(button);}
 }
 if(['allowances','addition'].includes(key)&&typeof installScreenActions50==='function')installScreenActions50();
 if(typeof mountRecordFilters75==='function')mountRecordFilters75();
}
function reconcileHeader79(){
 if(document.querySelector('#generalSearch69,#policySearch32,#temporarySearch38'))document.querySelectorAll('[data-pending-policy-search81]').forEach(n=>n.remove());
 for(const nav of document.querySelectorAll('[data-pending-policy-tabs79]'))if(document.getElementById('generalTab32'))nav.closest('.tab-scroll53')?.remove()||nav.remove();
 for(const label of document.querySelectorAll('[data-pending-field79]')){
  const actual=document.querySelector(label.dataset.pendingField79);if(!actual)continue;
  const source=actual.closest('label'),control=actual.closest('.search-select32,.date-field32')||actual,form=actual.form;
  if(form?.id)actual.setAttribute('form',form.id);
  for(const child of [...label.children])if(child.tagName!=='SPAN')child.remove();label.append(control);delete label.dataset.pendingField79;delete label.dataset.chrome79;label.dataset.movedFilter75='1';
  if(source&&source!==label&&!source.querySelector('input,select,textarea,button,a'))source.remove();
 }
 for(const early of document.querySelectorAll('[data-early-action61]')){const actual=document.getElementById(early.dataset.earlyAction61);if(actual){actual.dataset.liveAction80='';actual.removeAttribute('aria-busy');if(actual.closest('[data-top-actions49]'))early.remove();else early.replaceWith(actual);}}
 for(const button of document.querySelectorAll('[data-pending-action79]')){const actual=document.getElementById(button.dataset.pendingAction79);if(actual){actual.dataset.liveAction80='';actual.removeAttribute('aria-busy');button.replaceWith(actual);}}
 // Remove empty shells left by adopting the original buttons; never remove a form owner.
 for(const shell of document.querySelectorAll('#portalTabs32 .header-actions76'))if(!shell.children.length)shell.remove();
 for(const tools of document.querySelectorAll('#portalTabs32 .toolbar:not(form)'))if(!tools.children.length)tools.remove();
 const page=document.querySelector('#page');if(page&&!page.querySelector('.loading'))for(const input of document.querySelectorAll('[data-pending-search79]')){input.disabled=false;delete input.dataset.pendingSearch79;}
}
