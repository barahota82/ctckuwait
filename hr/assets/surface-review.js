'use strict';
const headerOrigins82=new WeakMap();
function beginHeaderRender82(){
 const root=typeof recordFilterHost75==='function'?recordFilterHost75():null,page=document.querySelector('#page');
 if(root&&page)headerOrigins82.set(root,page.firstElementChild);
}
function headerRenderReady82(root){const page=document.querySelector('#page');return !!page&&!page.querySelector('.loading')&&(!headerOrigins82.has(root)||page.firstElementChild!==headerOrigins82.get(root));}
const approvedLevelTwo84=new Set(['addAllowance','fridayRate']);
const approvedTools84=new Set(['batch_addition29','adjustment','batch_friday29']);
function movePrimaryActions92(root){
 const ids=typeof view==='undefined'?[]:view==='report'?['runReport','exportReport']:view==='leave_overview'?['leaveAlerts26','exportLeave']:[];
 if(!ids.length)return;
 let slot=root.querySelector(':scope > [data-top-actions49],:scope > .header-navigation77 > [data-top-actions49]');
 for(const id of ids){
  const button=root.querySelector('[id="'+id+'"]')||document.querySelector('#page [id="'+id+'"]')||root.querySelector('[data-pending-action79="'+id+'"]');if(!button)continue;
  if(!slot){slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';(root.querySelector(':scope > .header-navigation77')||root).append(slot);}
  if(button.id&&typeof adoptScreenAction61==='function')adoptScreenAction61(button,slot);else if(button.parentElement!==slot)slot.append(button);
 }
 if(slot){slot.dataset.primaryPair93='';const id=ids[ids.length-1],button=slot.querySelector('[id="'+id+'"],[data-pending-action79="'+id+'"]');if(button&&slot.lastElementChild!==button)slot.append(button);}
 if(typeof markHeaderActions80==='function')markHeaderActions80(root);
}
function classifyHeaderActions84(root){
 movePrimaryActions92(root);
 let approved=root.querySelector(':scope > .header-approved-actions84,:scope > .header-navigation77 > .header-approved-actions84');
 for(const slot of [...root.querySelectorAll('[data-top-actions49]')]){
  for(const button of [...slot.children]){
   const id=button.id||button.dataset.pendingAction79||button.dataset.earlyAction61;
   if(!approvedLevelTwo84.has(id)&&!approvedTools84.has(button.dataset.tool50))continue;
   if(!approved){approved=document.createElement('div');approved.className='top-actions49 header-level-two81 header-approved-actions84';approved.dataset.approvedActions84='';(root.querySelector(':scope > .header-navigation77')||root).append(approved);}
   approved.append(button);
  }
  slot.classList.remove('header-level-two81');slot.classList.add('header-main-actions84');
  slot.classList.toggle('header-staff-actions83',!!slot.querySelector('#addUser,[data-early-action61=addUser],[data-pending-action79=addUser]'));
 }
}
function finishHeaderReview82(root){
 // Only generated field labels can be empty shells; never discard a button-bearing label.
 for(const label of root.querySelectorAll('label.header-field76'))if(!label.querySelector('input,select,textarea,button,a')&&!label.hasAttribute('data-pending-field79'))label.remove();
 classifyHeaderActions84(root);
 if(headerRenderReady82(root)){
  for(const pending of root.querySelectorAll('[data-pending-action79]'))if(!document.getElementById(pending.dataset.pendingAction79))pending.remove();
  for(const pending of root.querySelectorAll('[data-early-action61]'))if(!document.getElementById(pending.dataset.earlyAction61))pending.remove();
 }
 for(const button of root.querySelectorAll('[data-pending-action79],[data-early-action61]')){button.disabled=true;button.setAttribute('aria-busy','true');}
}
function prepareCreateActions82(key,root){
 if(!root||root.hidden||typeof boot==='undefined'||!boot?.user)return;
 let action=null;const meta=boot.modules?.[key];
 if(['document','custody','discipline','evaluation','request','leave','sick','leave_cash','external','proof','loan'].includes(key)&&meta&&(teamPermission(key,'create')||(meta.employee&&personalPermission34(key,'create'))))action=['addRecord',key==='request'&&typeof requestMode50!=='undefined'&&requestMode50==='attendance'?'+ مراجعة واقعة حضور':'+ إضافة'];
 else if(key==='companies'&&canWrite29(key))action=['addCompany','+ إضافة شركة'];
 else if(['addition','payout_status','entitlement'].includes(key)&&canWrite29(key))action=['v26New','إضافة سجل'];
 if(!action||document.getElementById(action[0])||root.querySelector('[data-early-action61="'+action[0]+'"],[data-pending-action79="'+action[0]+'"]'))return;
 let slot=root.querySelector('[data-top-actions49]');if(!slot){slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';root.append(slot);}
 const button=document.createElement('button');button.type='button';button.className='primary create-action32';button.dataset.earlyAction61=action[0];button.textContent=uiText(action[1]);button.disabled=true;button.setAttribute('aria-busy','true');slot.append(button);
}
function normalizeSurfaces82(){
 const page=document.querySelector('#page');if(!page)return;
 const plain=new Set(['لا توجد نتائج','لا توجد سجلات بعد','لا توجد سجلات','لا توجد بيانات','لا توجد بيانات بعد','لا توجد طلبات حتى الآن','No results found','No records yet','No records','No data available','No requests yet']);
 for(const ar of ['لا توجد نتائج','لا توجد سجلات بعد','لا توجد بيانات','لا توجد بيانات بعد','لا توجد طلبات حتى الآن','لا توجد إشعارات مطابقة','لا توجد عروض منشورة','لا توجد عروض منشورة حتى الآن']){plain.add(ar);plain.add(uiText(ar).trim().replace(/[.،؛!]+$/,''));}
 for(const input of document.querySelectorAll('#page input[data-record-search32],#portalTabs32 input[data-record-search32],.record-search-label32 input[type=search]'))input.setAttribute('placeholder',uiText('الاسم أو الكود أو بيانات السجل'));
 for(const head of page.querySelectorAll(':scope>.page-head'))if(!head.textContent.trim()&&!head.querySelector('button,a,input,select'))head.remove();
 for(const node of page.querySelectorAll('.empty,.record-empty32,p')){
  if(node.closest('form,.error,.loading,.select-options32,.date-calendar32,[role=alert]')||node.querySelector('input,select,button,a,table'))continue;
  const text=node.textContent.trim().replace(/[.،؛!]+$/,'');if(!node.matches('.record-empty32')&&!plain.has(text))continue;
  node.classList.add('screen-empty82');node.setAttribute('role','status');const message=uiText('لا توجد نتائج');if(node.textContent!==message)node.textContent=message;
 }
}
