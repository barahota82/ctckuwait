'use strict';
function recordFiltersActive75(){return (typeof headerStackActive77==='function'&&headerStackActive77())||(typeof employeeEnabled73==='function'&&employeeEnabled73())||(typeof leaveEmployeeScreens69!=='undefined'&&leaveEmployeeScreens69.includes(view));}
function recordFilterHost75(){return !hubFor32(view)&&document.querySelector('#recordTabs49')||document.querySelector('#portalTabs32:not([hidden])');}
function recordSearchCandidates75(){return [...document.querySelectorAll('#page input[type="search"],#portalTabs32 input[type="search"]')].filter(n=>!n.closest('details,form:not(.toolbar),.search-select32'));}
function recordSearchPriority75(n){return n.dataset.pendingSearch79!==undefined?-1:n.dataset.remote32?100:['gridSearch37','voucherSearch37','eosSearch32','search28','v26Search32'].includes(n.id)?90:n.dataset.unifiedSearch75?20:10;}
function recordSearchOwner75(){if(!recordFiltersActive75()||view==='policies')return null;return recordSearchCandidates75().sort((a,b)=>recordSearchPriority75(b)-recordSearchPriority75(a))[0]||null;}
function moveRecordControl75(node,row){if(!node||row.contains(node)||node.closest('.header-primary77,.header-secondary77'))return;node.dataset.movedFilter75='1';row.append(node);}
function resetRecordFilters75(){
 if(typeof beginHeaderRender82==='function')beginHeaderRender82();
 if(typeof resetHeaderActions80==='function')resetHeaderActions80();
 // Remove old moved IDs before a renderer rebuilds the page; keep the employee selection.
 const employee=document.querySelector('[data-employee-filter73]');
 if(employee&&employee.closest('#page')){const portal=document.querySelector('#portalTabs32');if(portal)operationalBox69(portal).append(employee);}
 for(const native of document.querySelectorAll('[data-employee-filter73] #reportUser,[data-employee-filter73] select[name="user_id"]'))(native.closest('.search-select32')||native).remove();
 if(employee&&!employee.querySelector('select')){const select=document.createElement('select');select.disabled=true;const option=document.createElement('option');option.textContent=uiText('كل الموظفين في نطاقك');select.append(option);employee.append(select);} 
 document.querySelectorAll('[data-moved-filter75]').forEach(n=>{if(!n.matches('[data-employee-filter73],.leave-employee-filter69,[data-chrome79]'))n.remove();});
 document.querySelectorAll('[data-record-filter-row75]').forEach(row=>{if(!row.children.length)row.remove();});
 if(typeof prepareHeader79==='function')prepareHeader79(view);
}
const recordPlainSearch75=new WeakMap();
function bindPlainRecordSearch75(search){
 if(search.dataset.remote32)return;
 let run=recordPlainSearch75.get(search);if(!run){run=()=>{
  if(view==='users'&&document.getElementById('employeeEditorForm134'))return;
  const q=normalize32(search.value);
  document.querySelectorAll('#page .table-wrap').forEach(w=>{if(w.closest('form,details'))return;w.querySelectorAll('tbody > tr').forEach(tr=>tr.hidden=!!q&&!normalize32(tr.textContent).includes(q));});
 };recordPlainSearch75.set(search,run);search.addEventListener('input',run);}run();
}
function selectClipped75(node){
 for(let n=node?.parentElement;n&&n!==document.documentElement;n=n.parentElement){const style=getComputedStyle(n);if(['hidden','clip'].includes(style.overflow)||['hidden','clip'].includes(style.overflowY))return true;}
 return false;
}
function positionRecordPicker75(select,box,list,close){
 const calendar=list.classList?.contains('date-calendar32'),row=select.closest('.record-filter-row75,.header-secondary77'),clipped=!calendar&&selectClipped75(box);if(!row&&!calendar&&!clipped)return;
 if(!calendar){const rect=box.getBoundingClientRect(),width=rect.width,below=window.innerHeight-rect.bottom-8,above=rect.top-8,down=!clipped||below>=120||below>=above,room=Math.max(80,Math.min(260,down?below:above)),top=down?rect.bottom+4:Math.max(8,rect.top-room-4);Object.assign(list.style,{position:'fixed',inset:'auto',left:Math.max(8,Math.min(rect.left,(window.innerWidth||9999)-width-8))+'px',top:top+'px',width:width+'px',maxHeight:room+'px',overflowY:'auto',zIndex:'200'});list.dataset.positioned75='1';list._recordClose75=close;if(row){row._recordClose75=close;if(!row._recordScrollBound75){row._recordScrollBound75=true;row.addEventListener('scroll',()=>row._recordClose75?.(),{passive:true});}}if(clipped&&!list._clipScroll75){list._clipScroll75=true;window.addEventListener('scroll',event=>{const target=event.target;if(target===list||(target instanceof Element&&list.contains(target)))return;list._recordClose75?.();},true);}return;}
 const rect=box.getBoundingClientRect(),width=Math.min(336,window.innerWidth-24),spaceBelow=window.innerHeight-rect.bottom-12,spaceAbove=rect.top-12,height=calendar?Math.min(410,window.innerHeight-24):280,above=spaceBelow<height&&spaceAbove>spaceBelow;
 Object.assign(list.style,{position:'fixed',inset:'auto',left:Math.max(12,Math.min(rect.left,window.innerWidth-width-12))+'px',top:(above?Math.max(12,rect.top-height-6):Math.min(rect.bottom+6,window.innerHeight-height-12))+'px',width:width+'px',maxHeight:height+'px',zIndex:'200',overflowY:'auto'});
 list.dataset.positioned75='1';list._recordClose75=close;if(row){row._recordClose75=close;if(!row._recordScrollBound75){row._recordScrollBound75=true;row.addEventListener('scroll',()=>row._recordClose75?.(),{passive:true});}}
}

function renameRecordSearch75(input){
 const label=input.closest('label');if(!label)return;
 label.classList.add('record-search-label32');let title=label.querySelector('.record-search-title32');
 if(!title){for(const n of [...label.childNodes])if(n.nodeType===3)n.remove();title=document.createElement('span');title.className='record-search-title32';label.prepend(title);}
 const text=uiText('البحث في السجلات');if(title.textContent!==text)title.textContent=text;
 input.dataset.recordSearch32='1';input.setAttribute('aria-label',text);input.setAttribute('placeholder',uiText('الاسم أو الكود أو بيانات السجل')); 
}
function unifyRecordSearch75(row){
 if(view==='policies'){
  for(const [id,key]of [['generalSearch69','general'],['policySearch32','staff'],['temporarySearch38','temporary']]){const input=document.getElementById(id);if(!input)continue;const label=input.closest('label');if(label){moveRecordControl75(label,row);label.hidden=policyTab32!==key;renameRecordSearch75(input);}}
  return;
 }
 let searches=recordSearchCandidates75(),search=recordSearchOwner75();
 if(!search){
  const page=document.querySelector('#page');if(!page||page.querySelector('.loading')||!page.children.length)return;
  const label=document.createElement('label');label.className='record-search-label32';label.innerHTML='<span class="record-search-title32"></span><input type="search" data-record-search32="1" data-unified-search75="1">';row.append(label);search=label.querySelector('input');searches=[search];
 }
 for(const other of searches){
  if(other===search)continue;
  if(typeof recordSearchFilters69!=='undefined'){
   const old=recordSearchFilters69.get(other);if(old)for(const [wrap]of old)if(wrap.isConnected)delete wrap.dataset.searchReady32;
   recordSearchFilters69.delete(other);
  }
  (other.closest('label')||other).remove();
 }
 let label=search.closest('label');if(!label){label=document.createElement('label');search.before(label);label.append(search);}
 moveRecordControl75(label,row);renameRecordSearch75(search);bindPlainRecordSearch75(search);
}
function mountRecordFilters75(){
 if(!recordFiltersActive75())return;
 const host=recordFilterHost75();if(!host)return;
 let row=host.querySelector('[data-record-filter-row75]');
 if(!row){row=document.createElement('div');row.className='record-filter-row75';row.dataset.recordFilterRow75='';host.append(row);}
 const employee=document.querySelector('[data-employee-filter73],#leaveEmployee69');moveRecordControl75(employee?.closest('label'),row);
 for(const input of document.querySelectorAll('#recordStatus49,#recordExpiry49'))moveRecordControl75(input.closest('label'),row);
 for(const tools of document.querySelectorAll('#page > .toolbar,#page > form.toolbar,#page .pay-grid37 > .toolbar,#portalTabs32 .operational-controls68 > .toolbar')){
  if(tools.querySelector('input,select')&&!tools.closest('details')){for(const detail of tools.querySelectorAll(':scope > details'))tools.before(detail);moveRecordControl75(tools,row);}
 }
 unifyRecordSearch75(row);if(typeof polishHeader76==='function')polishHeader76();if(typeof scheduleStickyTabs48==='function')scheduleStickyTabs48();
}
if(typeof window!=='undefined'&&window.addEventListener)window.addEventListener('resize',()=>document.querySelectorAll('[data-positioned75]').forEach(n=>n._recordClose75?.()),{passive:true});
