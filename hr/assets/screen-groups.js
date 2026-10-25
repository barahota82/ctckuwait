'use strict';
/* Five screen families within the existing portal. Source forms, actions and API contracts are reused. */
const screenFamilies179={
 employee:new Set(['users','work_permit','document','custody','discipline','evaluation']),
 requests:new Set(['request','leave','sick','resumption','external','proof']),
 finance:new Set(['allowances','addition','entitlement','leave_cash','payout_status','payments26','recovery','eos_settlement']),
 settings:new Set(['policies','v28_locations']),
 reports:new Set(['report','leave_overview','analytics','audit','v26_history','personal_history','bank','eos'])
};
// The earlier generic table-to-employee-card pass is incompatible with families 2–5.
// Keep its behavior for the approved employee family and every unrelated screen.
const groupCompact179=compactTables32;
compactTables32=function(root){const family=groupFamily179();if(family&&family!=='employee'){const page=document.getElementById('page');page?.querySelectorAll('.table-wrap').forEach(w=>w.dataset.compact32='1');}return groupCompact179(root);};
// The standalone calculator owns one employee selector; do not also create the legacy header selector.
const groupEmployeeEnabled179=employeeEnabled73;employeeEnabled73=function(key=view){return key==='eos'?false:groupEmployeeEnabled179(key);};
let groupInline179=null,groupMount179=false,groupRefresh179=false;
const groupResponses179=new Map(),groupApi179=api;
api=async function(action,data=null,params={}){const scope=view,epoch=navigationEpoch32,result=await groupApi179(action,data,params);if(data===null&&scope===view&&epoch===navigationEpoch32)groupResponses179.set(scope+'|'+action,result);return result;};
const groupText179=(ar,en)=>boot?.localization?.language==='en'?en:ar;
function groupFamily179(key=view){return Object.keys(screenFamilies179).find(k=>screenFamilies179[k].has(key))||null;}
function groupRestoreModal179(){groupInline179=null;}
const groupModal179=modal;
modal=function(title,body,onSubmit=null,options={}){
 if(!groupInline179||onSubmit){groupRestoreModal179();return groupModal179(title,body,onSubmit,options);}
 const target=groupInline179;groupInline179=null;
 target.innerHTML='<div class="modal-head"><h2>'+esc(uiText(title))+'</h2><button type="button" data-group-close179 aria-label="'+esc(groupText179('إغلاق التفاصيل','Close details'))+'">×</button></div><div class="modal-body">'+body+'</div>';
 target.querySelector('[data-group-close179]').onclick=()=>{target.innerHTML=empty(groupText179('اختر طلبًا لعرض تفاصيله','Select a request to view its details'));};applyInterfaceLanguage();
};
function groupElement179(tag,cls,html=''){const n=document.createElement(tag);n.className=cls;n.innerHTML=html;return n;}
function groupPanel179(parent,mode,title){
 const root=groupElement179('section','group-layout179 group-'+mode+'179');root.dataset.screenFamily179=mode;
 const master=groupElement179('section','card group-list179','<div class="section-top"><h2>'+esc(title)+'</h2><span data-group-count179></span></div>');
 const detail=groupElement179('section','card group-detail179','<div class="group-detail-content179">'+empty(groupText179('اختر سجلًا لعرض التفاصيل','Select a record to view details'))+'</div>');detail.setAttribute('aria-label',groupText179('تفاصيل السجل المختار','Selected record details'));
 root.append(master,detail);parent.before(root);if(root.parentElement.matches('.card'))root.parentElement.classList.add('group-source-host179');return {root,master,detail,content:detail.firstElementChild};
}
function groupSelectRow179(table,row){table.querySelectorAll('tbody tr').forEach(r=>{const active=r===row;r.classList.toggle('group-selected179',active);r.setAttribute('aria-selected',String(active));});}
function groupTable179(wrapper,family){
 if(wrapper.closest('.group-layout179')||wrapper.dataset.groupTable179)return;const table=wrapper.querySelector('table');if(!table||!table.tBodies[0]?.rows.length)return;
 wrapper.dataset.groupTable179='1';const originalRows=[...table.tBodies[0].rows],headers=[...table.tHead?.rows[0]?.cells||[]].map(x=>x.textContent.trim());
 const panel=groupPanel179(wrapper,family,family==='requests'?groupText179('قائمة الطلبات','Requests'):family==='settings'?groupText179('الإعدادات','Settings'):groupText179('حركات الفترة','Period transactions'));
 panel.master.append(wrapper);panel.master.querySelector('[data-group-count179]').textContent=String(originalRows.length);
 const detailIndex=headers.findIndex(h=>/^(التفاصيل|تفاصيل|البيانات|Details|Data)$/i.test(h));if(detailIndex>=0)table.tHead.rows[0].cells[detailIndex].hidden=true;
 let activeCell=null;
 function show(row,user=false){
  panel.detail.hidden=!user&&['finance','reports'].includes(family);
  groupRestoreModal179();if(activeCell){while(panel.content.firstChild)activeCell.append(panel.content.firstChild);activeCell=null;}
  panel.content.replaceChildren();groupSelectRow179(table,row);
  const c=detailIndex>=0?row.cells[detailIndex]:null;
  if(c&&c.childNodes.length){activeCell=c;while(c.firstChild)panel.content.append(c.firstChild);}
  else{const native=row.querySelector('[data-record]');if(native){groupInline179=panel.content;native.click();groupInline179=null;}
   else {const dl=groupElement179('dl','record-details32 group-fields179');[...row.cells].forEach((cell,i)=>{if(cell.querySelector('button,input,select,textarea')||cell.hidden||!headers[i])return;const dt=document.createElement('dt');dt.textContent=headers[i];const dd=document.createElement('dd');dd.textContent=cell.textContent;dl.append(dt,dd);});panel.content.append(dl);}
  }
  if(!panel.content.textContent.trim())panel.content.innerHTML=empty();if(user&&matchMedia('(max-width:1050px)').matches)panel.detail.scrollIntoView({block:'start',behavior:'smooth'});
 }
 const reuseRecord=family==='requests'&&originalRows.every(r=>r.querySelector('[data-record]'));const actions=groupElement179('th','','');actions.textContent=groupText179('التفاصيل','Details');if(!reuseRecord)table.tHead.rows[0].append(actions);
 for(const row of originalRows){if(detailIndex>=0&&row.cells[detailIndex])row.cells[detailIndex].hidden=true;if(reuseRecord){row.querySelector('[data-record]').dataset.groupSelect179='';continue;}const cell=document.createElement('td'),button=groupElement179('button','',esc(groupText179('عرض التفاصيل','View details')));button.type='button';button.dataset.groupSelect179='';button.onclick=()=>show(row,true);cell.append(button);row.append(cell);}
 if(family==='requests'){
  wrapper.addEventListener('click',event=>{const b=event.target.closest('[data-record]');if(b&&!groupInline179){groupSelectRow179(table,b.closest('tr'));if(matchMedia('(max-width:1050px)').matches)panel.detail.scrollIntoView({block:'start',behavior:'smooth'});groupInline179=panel.content;queueMicrotask(()=>groupInline179=null);}},true);
 }
 if(family==='finance')groupFinanceSummary179(panel.root,table,headers);
 show(originalRows[0]);
}
function groupFinanceSummary179(root,table,headers){
 const amountIndex=headers.findIndex(h=>/المبلغ|الصافي|قيمة الرصيد|Amount|Net amount|Net pay/i.test(h)),statusIndex=headers.findIndex(h=>/^(الحالة|Status)$/i.test(h));
 let total=0,approved=0,paid=0;for(const row of table.tBodies[0].rows){if(row.hidden)continue;const value=amountIndex<0?0:Number(row.cells[amountIndex]?.textContent.replace(/[^0-9.\-]/g,'')||0);if(!Number.isFinite(value))continue;total+=value;const state=row.cells[statusIndex]?.textContent||'';if(/معتمد|Approved/i.test(state))approved+=value;if(/مصروف|Paid|تم الصرف/i.test(state))paid+=value;}
 const fields=[[groupText179('عدد الحركات','Transactions'),[...table.tBodies[0].rows].filter(r=>!r.hidden).length],...(amountIndex>=0?[[groupText179('إجمالي الحركات','Transaction total'),money26(total)],...(statusIndex>=0?[[groupText179('المعتمد','Approved'),money26(approved)],[groupText179('المصروف','Paid'),money26(paid)]]:[])]:[])];
 root.previousElementSibling?.matches('[data-group-summary179]')&&root.previousElementSibling.remove();const summary=groupElement179('dl','debt-totals95 group-summary179',fields.map(([k,v])=>'<div><dt>'+esc(k)+'</dt><dd>'+esc(v)+'</dd></div>').join(''));summary.dataset.groupSummary179='';root.before(summary);
}
function groupCards179(container,cards,family,opts={}){
 cards=cards.filter(c=>!c.closest('.group-layout179'));if(!cards.length)return;
 const anchor=opts.anchor||cards[0],panel=groupPanel179(anchor,family,opts.title||groupText179(family==='requests'?'قائمة الطلبات':family==='settings'?'الإعدادات':'السجلات',family==='requests'?'Requests':family==='settings'?'Settings':'Records'));
 const headings=opts.headers||[groupText179('السجل','Record'),groupText179('الحالة','Status'),groupText179('التفاصيل','Details')];
 panel.master.insertAdjacentHTML('beforeend',table(headings,cards.map((card,i)=>{
  const summary=card.querySelector(':scope > summary'),text=summary?.textContent.trim()||card.querySelector('h2,h3')?.textContent||groupText179('سجل','Record')+' '+(i+1);
  const status=card.querySelector('summary .badge,summary .tag')?.outerHTML||'';
  return opts.cells?opts.cells(card,i):[esc(text),status,'<button type="button" data-group-card179="'+i+'">'+esc(groupText179('عرض التفاصيل','View details'))+'</button>'];
 })));
 panel.master.querySelector('[data-group-count179]').textContent=String(cards.length);
 panel.content.replaceChildren();cards.forEach(card=>{card.dataset.groupSource179='';panel.content.append(card);card.hidden=true;});
 const selectedTitle=groupElement179('h3','group-selected-title179');panel.content.prepend(selectedTitle);const rows=panel.master.querySelectorAll('tbody tr');function select(i,user=false){panel.detail.hidden=!user&&['finance','reports'].includes(family);if(user&&matchMedia('(max-width:1050px)').matches)panel.detail.scrollIntoView({block:'start',behavior:'smooth'});selectedTitle.textContent=cards[i].querySelector(':scope > summary')?.textContent||'';selectedTitle.hidden=!selectedTitle.textContent;groupRestoreModal179();cards.forEach((c,j)=>{c.hidden=i!==j;if(c.tagName==='DETAILS')c.open=i===j;});groupSelectRow179(panel.master,rows[i]);}
 panel.master.querySelectorAll('[data-group-card179]').forEach(b=>b.onclick=()=>select(Number(b.dataset.groupCard179),true));select(0);
 if(family==='finance')groupFinanceSummary179(panel.root,panel.master.querySelector('table'),headings);
 return panel;
}
function groupExistingCards179(page,family){
 let all=[...page.querySelectorAll('.record-groups32 > details.record-group32,.special-records32 > details,.leave-list32 > details.leave-person32')].filter(d=>!d.closest('.group-layout179'));
 if(!all.length)return;
 if(family==='reports'&&view==='leave_overview'){
  const data=groupResponses179.get(view+'|leave_overview')||[];
  groupCards179(page,all,family,{title:groupText179('أرصدة الموظفين','Employee balances'),headers:[groupText179('الموظف','Employee'),groupText179('رصيد أيام','Leave days'),groupText179('المتاح للصرف أيام','Cashable days'),groupText179('قيمة الرصيد النقدي','Cash balance value'),groupText179('التفاصيل','Details')],cells:(c,i)=>{const r=data.find(r=>Number(r.id)===Number(c.dataset.employeeId))||data[i]||{},b=r.balance||{};return [employeeNameMarkup143(r.id,r.name||'',r),esc(b.remaining??'—'),esc(b.cash_days??'—'),money26(b.cash_value),'<button type="button" data-group-card179="'+i+'">'+esc(groupText179('عرض التفاصيل','View details'))+'</button>'];}});
 }else if(family==='finance'||(family==='requests'&&view==='resumption')){
 const response=groupResponses179.get(view+'|v26_info'),rows=response?.records||[],sources=all,anchor=all[0];const flattened=[];for(const card of sources){const matching=rows.filter(r=>Number(r.user_id)===Number(card.dataset.employeeId));[...card.querySelectorAll(':scope > .record-details32')].forEach((section,i)=>{section._groupRecord179=rows.find(r=>Number(r.id)===Number(section.dataset.recordId))||matching[i];flattened.push(section);});}if(flattened.length)all=flattened;
 groupCards179(page,all,family,{anchor,title:groupText179(family==='finance'?'حركات الفترة':'طلبات مباشرة العمل',family==='finance'?'Period transactions':'Resumption requests'),headers:[groupText179('الموظف','Employee'),groupText179('التاريخ','Date'),...(family==='finance'?[groupText179('المبلغ د.ك','Amount KWD')]:[]),groupText179('الحالة','Status'),groupText179('التفاصيل','Details')],cells:(card,i)=>{const r=card._groupRecord179||rows.find(r=>Number(r.user_id)===Number(card.dataset.employeeId))||rows[i]||{};return [employeeNameMarkup143(r.user_id,r.name||r.employee_name||'',r),esc(r.date_from||''),...(family==='finance'?[money26(r.amount)]:[]),badge(r.status||''),'<button type="button" data-group-card179="'+i+'">'+esc(groupText179('عرض التفاصيل','View details'))+'</button>'];}});
if(flattened.length)sources.forEach(card=>card.remove());
 }else groupCards179(page,all,family);
}
function groupPolicy179(page){
 const general=page.querySelector('#generalPanel32'),temporary=page.querySelector('#temporaryPanel38');
 if(general&&!general.querySelector('.group-layout179')){const candidates=[...general.querySelectorAll('#currentGeneral384,details.record-group32')].filter(n=>!n.parentElement.closest('#currentGeneral384')&&(n.id==='currentGeneral384'||!n.querySelector('details.record-group32')));groupCards179(general,candidates,'settings',{title:groupText179('القواعد وسجل التغييرات','Rules and change history')});for(const n of general.querySelectorAll('.record-groups32:empty'))n.remove();for(const n of general.querySelectorAll('details[data-general-history384]'))if(!n.querySelector('.record-details32'))n.remove();for(const c of [...general.children])if(c.matches('.card')&&!c.querySelector('.group-layout179,button,input,select,details,.record-details32'))c.remove();}
 if(temporary&&!temporary.querySelector('.group-layout179'))groupCards179(temporary,[...temporary.querySelectorAll('details.record-group32')],'settings',{title:groupText179('الجداول المؤقتة','Temporary schedules')});
 // Staff schedules remain the approved employee workspace (family 1).
}
function groupEos179(page){
 const source=page.querySelector('.record-groups32');if(!source||source.dataset.groupCalculator179)return;source.dataset.groupCalculator179='1';
 const cards=[...source.querySelectorAll(':scope > details')];if(!cards.length)return;
 const card=groupElement179('section','card group-calculator179','<h2>'+esc(groupText179('حساب نهاية الخدمة','End of service calculator'))+'</h2><label>'+esc(groupText179('الموظف','Employee'))+'<select data-group-eos179>'+cards.map((c,i)=>'<option value="'+i+'">'+esc(c.querySelector('summary')?.textContent||'')+'</option>').join('')+'</select></label><div data-group-eos-result179></div>');source.before(card);const holder=card.querySelector('[data-group-eos-result179]');cards.forEach(c=>{holder.append(c);c.dataset.groupSource179='';});source.remove();const select=card.querySelector('select');const draw=()=>cards.forEach((c,i)=>{c.hidden=i!==Number(select.value);c.open=!c.hidden;});select.onchange=draw;draw();cards[0].querySelector('form')?.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));page.querySelector('#eosSearch32')?.closest('label')?.remove();
}
function groupReportTotals179(page){
 for(const wrapper of page.querySelectorAll('.table-wrap')){if(wrapper.closest('.group-layout179')||wrapper.dataset.groupReport179)continue;wrapper.dataset.groupReport179='1';const t=wrapper.querySelector('table');if(!t?.tHead||!t.tBodies[0])continue;
  const count=t.tBodies[0].rows.length;const title=groupElement179('div','section-top group-result-heading179','<h2>'+esc(groupText179('نتائج التقرير','Report results'))+'</h2><span>'+esc(groupText179('عدد النتائج: ','Results: '))+count+'</span>');wrapper.before(title);t.classList.add('group-report-table179');
  const fields=[...t.tHead.rows[0].cells].map((h,i)=>({name:h.textContent.trim(),i})).filter(x=>/^(التأخير|المبكر|الأذونات|المبلغ|مبلغ التحويل|الصافي|التأخير دقائق|Late arrival|Early departure|Permissions|Amount|Transfer amount|Net pay|Late minutes)$/i.test(x.name));
  if(fields.length){const totals=fields.map(({name,i})=>[name,[...t.tBodies[0].rows].reduce((n,r)=>n+(Number(r.cells[i]?.textContent.replace(/[^\d.\-]/g,''))||0),0)]);wrapper.after(groupElement179('dl','debt-totals95 group-summary179',totals.map(([n,v])=>'<div><dt>'+esc(n)+'</dt><dd>'+esc(Number(v.toFixed(3)))+'</dd></div>').join('')));}
 }
}
function groupPeriod179(page){
 if(document.querySelector('[data-group-period179]'))return;
 const host=document.querySelector('#portalTabs32 .header-secondary77')||page;
 const box=groupElement179('form','group-period179 toolbar','<div class="group-period-fields179">'+input('group_from179','من تاريخ','2017-01-01','date','required')+input('group_to179','إلى تاريخ',dateNow(),'date','required')+'<button type="submit" class="primary">'+esc(groupText179('تحديث','Refresh'))+'</button></div>');box.dataset.groupPeriod179='';host===page?page.prepend(box):host.append(box);host.hidden=false;
 box.onsubmit=e=>{e.preventDefault();const from=box.elements.namedItem('group_from179').value,to=box.elements.namedItem('group_to179').value;if(from>to){toast(groupText179('من تاريخ يجب ألا يتجاوز إلى تاريخ','Start date must not exceed end date'));return;}
  for(const layout of page.querySelectorAll('.group-layout179')){const table=layout.querySelector('.group-list179 table');if(!table)continue;for(const row of table.tBodies[0].rows){const raw=row.textContent.replace(/[\u200e\u200f]/g,''),iso=raw.match(/\d{4}-\d{2}-\d{2}/),local=raw.match(/(\d{2})\/(\d{2})\/(\d{4})/),date=iso?.[0]||(local?local[3]+'-'+local[2]+'-'+local[1]:'');row.dataset.groupPeriodHidden179=!!date&&(date<from||date>to)?'1':'0';row.hidden=row.dataset.groupPeriodHidden179==='1'||row.dataset.groupSearchHidden179==='1';}layout.querySelector('[data-group-count179]').textContent=String([...table.tBodies[0].rows].filter(r=>!r.hidden).length);const heads=[...table.tHead.rows[0].cells].map(h=>h.textContent.trim());groupFinanceSummary179(layout,table,heads);const first=[...table.tBodies[0].rows].find(r=>!r.hidden);layout.querySelector('.group-detail179').hidden=!first;first?.querySelector('[data-group-select179],[data-group-card179]')?.click();}
 };return box;
}
function groupRequestCategories179(){
 if(view!=='request'||requestMode50==='attendance')return;const card=document.getElementById('recordTabs49');if(!card||card.dataset.groupCategories179)return;card.dataset.groupCategories179='1';
 const all=document.querySelector('[data-document-mode86="all"]'),detail=document.querySelector('[data-document-mode86="detail"]');detail?.remove();
 if(all){delete all.dataset.documentMode86;all.dataset.category49='all';card.querySelector('.top-row49 .portal-tabs32')?.prepend(all);all.onclick=()=>{recordCategory49='all';card.querySelectorAll('[data-category49]').forEach(b=>b.setAttribute('aria-current',b===all?'page':'false'));if(typeof documentRefresh86==='function')documentRefresh86();};}
 documentMode86='detail';applyDocumentMode86();const row=card.querySelector('.top-row49');if(row)row.hidden=false;
 const categories=card.querySelector('.top-row49 .portal-tabs32');categories?.classList.add('employee-editor-sections134');card.classList.add('request-header184');
}
function groupRequestSummary179(page){
 const host=page.querySelector('#operationalSummary68');if(!host||!host.children.length||host.querySelector('[data-group-request-summary179]'))return;const details=groupElement179('details','record-group32 group-request-summary179','<summary>'+esc(groupText179('ملخص الأرصدة والمتابعة','Balances and monitoring summary'))+'</summary>');details.dataset.groupRequestSummary179='';while(host.firstChild)details.append(host.firstChild);host.append(details);
}
function groupSearch179(page){
 const inputs=[...document.querySelectorAll('#portalTabs32 .record-search-label32 input[type=search],#page .record-search-label32 input[type=search],#v26Search32,#generalSearch69,#temporarySearch38')];
 for(const input of new Set(inputs)){if(view==='policies'&&!['generalSearch69','temporarySearch38'].includes(input.id))continue;
  const apply=()=>{if(!input.isConnected)return;const q=normalize32(input.value),scope=input.id==='generalSearch69'?page.querySelector('#generalPanel32'):input.id==='temporarySearch38'?page.querySelector('#temporaryPanel38'):page;if(!scope)return;
   for(const layout of scope.querySelectorAll('.group-layout179')){const table=layout.querySelector('.group-list179 table');if(!table||table.dataset.groupQuery179===q)continue;table.dataset.groupQuery179=q;
    for(const row of table.tBodies[0].rows){const ids=[...row.querySelectorAll('[data-employee-id],[data-employee-name143]')].map(n=>Number(n.dataset.employeeId||n.dataset.employeeName143));const names=users.filter(u=>ids.includes(Number(u.id))).map(u=>[u.employee_code,u.name,u.name_en,u.profile?.name_en].join(' ')).join(' ');row.dataset.groupSearchHidden179=q&&!normalize32(row.textContent+' '+names).includes(q)?'1':'0';row.hidden=row.dataset.groupSearchHidden179==='1'||row.dataset.groupPeriodHidden179==='1';}
    const rows=[...table.tBodies[0].rows],first=rows.find(r=>!r.hidden),current=rows.find(r=>r.classList.contains('group-selected179'));layout.querySelector('[data-group-count179]').textContent=String(rows.filter(r=>!r.hidden).length);layout.querySelector('.group-detail179').hidden=!first;if(first&&(!current||current.hidden))first.querySelector('[data-group-select179],[data-group-card179]')?.click();if(layout.classList.contains('group-finance179'))groupFinanceSummary179(layout,table,[...table.tHead.rows[0].cells].map(h=>h.textContent.trim()));
   }
  };
  if(!input.dataset.groupSearchBound179){input.dataset.groupSearchBound179='1';input.addEventListener('input',()=>{page.querySelectorAll('.group-list179 table').forEach(t=>delete t.dataset.groupQuery179);apply();});}apply();
 }
}
function groupLabels179(page){
 const labels={net_amount:['الصافي','Net amount'],net_estimate:['صافي التقدير','Estimated net amount'],previous_settlements:['التسويات السابقة','Previous settlements'],employee:['الموظف','Employee'],leave_details:['تفاصيل الإجازات','Leave details'],addition:['الإضافات','Additions'],deduction:['الخصومات','Deductions'],net:['الصافي','Net amount']};
 for(const n of page.querySelectorAll('dt,.record-details32 strong,.detail-row b')){const pair=labels[n.textContent.trim()];if(pair)n.textContent=groupText179(...pair);}
}
function mountScreenGroups179(){
 if(groupMount179)return;const page=document.getElementById('page'),family=groupFamily179();if(!page||!family||family==='employee')return;
 if(['dashboard','notifications','attendance','users','work_permit'].includes(view))return;
 groupMount179=true;try{
  page.dataset.screenFamily179=family;if(family==='requests'){groupRequestCategories179();groupRequestSummary179(page);}
  if(view==='eos'){groupEos179(page);groupLabels179(page);groupWorkspace180(page,family);return;}
  if(view==='policies'){groupPolicy179(page);groupSearch179(page);groupWorkspace180(page,family);return;}
  groupExistingCards179(page,family);
  if(['requests','finance','settings'].includes(family))for(const wrap of [...page.querySelectorAll('.table-wrap')]){if(family==='requests'&&!wrap.querySelector('[data-record]'))continue;const heading=wrap.closest('.card')?.querySelector('h2')?.textContent||'';groupTable179(wrap,family==='finance'&&/قيم يوم الجمعة|Friday rates|Friday values/i.test(heading)?'settings':family);}
  if(family==='finance')groupPeriod179(page);groupSearch179(page);if(family==='reports'){if(['v26_history','personal_history'].includes(view))for(const wrap of [...page.querySelectorAll('.table-wrap')])groupTable179(wrap,'reports');groupReportTotals179(page);const report=page.querySelector('#v26ReportForm');if(report&&!report.dataset.groupInitial179){report.dataset.groupInitial179='1';report.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));}}
 groupLabels179(page);groupWorkspace180(page,family);
 }finally{groupMount179=false;}
}
const groupNavigate179=navigate;
navigate=async function(key,...args){document.querySelectorAll('[data-group-workspace180]').forEach(n=>{delete n.dataset.groupWorkspace180;delete n.dataset.requestHeader184;});document.querySelectorAll('[data-workspace-header180]').forEach(n=>delete n.dataset.workspaceHeader180);groupRestoreModal179();if(groupFamily179(key)!=='finance')document.querySelectorAll('[data-group-period179]').forEach(n=>n.remove());const old=document.getElementById('page');if(old)delete old.dataset.screenFamily179;await groupNavigate179(key,...args);mountScreenGroups179();};
const groupObserver179=new MutationObserver(records=>{
 if(groupMount179||!records.some(r=>[...r.addedNodes].some(n=>n.nodeType===1&&(!n.classList||!n.classList.contains('group-layout179')))))return;
 if(groupRefresh179)return;groupRefresh179=true;queueMicrotask(()=>{groupRefresh179=false;mountScreenGroups179();});
});
groupObserver179.observe(document.getElementById('app'),{childList:true,subtree:true});
window.addEventListener('DOMContentLoaded',()=>setTimeout(mountScreenGroups179,0));
// V4 preview: one non-sticky workspace, with task-specific content arrangements.
function groupWorkspace180(page,family){
 const workspace=page.closest('.workspace');if(!workspace)return;
 const adminRequest=view==='request'&&requestMode50!=='attendance';
 if(adminRequest)workspace.dataset.requestHeader184='1';else delete workspace.dataset.requestHeader184;
 const staff=view==='policies'&&policyTab32==='staff';
 if(staff){delete workspace.dataset.groupWorkspace180;document.querySelectorAll('[data-group-nav180]').forEach(n=>{n.classList.remove('employee-editor-sections134');delete n.dataset.groupNav180;});document.querySelectorAll('[data-workspace-header180]').forEach(n=>delete n.dataset.workspaceHeader180);return;}
 if(workspace.dataset.groupWorkspace180!==family)workspace.dataset.groupWorkspace180=family;
 const header=document.getElementById('portalTabs32');
 if(header){header.dataset.workspaceHeader180='1';for(const nav of header.querySelectorAll('.portal-tabs32'))if(!nav.classList.contains('employee-editor-sections134')){nav.classList.add('employee-editor-sections134');nav.dataset.groupNav180='1';}}
 // The approved reference measures sticky offsets; this layout has no sticky content header.
 if(document.documentElement.style.getPropertyValue('--hub-height48')!=='0px')document.documentElement.style.setProperty('--hub-height48','0px');
 for(const row of page.querySelectorAll('.page-head'))if(!row.querySelector('button,input,select'))row.classList.add('group-intro180');
 for(const detail of page.querySelectorAll('.group-detail179')){
  if(detail.dataset.detailChrome180)continue;detail.dataset.detailChrome180='1';
  const bar=groupElement179('div','group-detail-bar180');const title=document.createElement('strong');title.textContent=groupText179('تفاصيل السجل','Record details');
  const close=groupElement179('button','',esc(groupText179('إغلاق التفاصيل','Close details')));close.type='button';close.onclick=()=>{detail.hidden=true;const root=detail.closest('.group-layout179');root?.querySelectorAll('tr[aria-selected=true]').forEach(r=>r.setAttribute('aria-selected','false'));root?.querySelector('[data-group-select179],[data-group-card179]')?.focus({preventScroll:true});};bar.append(title,close);detail.prepend(bar);
 }
}
// Do not let the legacy sticky measurement impose offsets on this non-sticky workspace.
const groupSticky180=refreshStickyTabs48;
refreshStickyTabs48=function(){if(document.querySelector('.workspace[data-group-workspace180]')){document.documentElement.style.setProperty('--hub-height48','0px');return;}return groupSticky180();};

document.addEventListener('click',event=>{if(view==='policies'&&event.target.closest('#generalTab32,#staffTab32,#temporaryTab38'))queueMicrotask(mountScreenGroups179);});
