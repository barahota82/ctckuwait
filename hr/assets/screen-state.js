'use strict';
// Transient UI state only: never store form data, passwords, files or authorization in storage.
let screenSave38=null,screenRestoreBusy38=false;
function screenIdentity38(){return String(boot?.identity32?.context??'')+'|'+String(boot?.user?.id??'')+'|'+String(boot?.access_mode??'')+'|'+String(boot?.company_filter30??'');}
function screenKey38(el){
 if(el.id)return 'id:'+el.id;
 const identifiers=['employeeId','recordId','policyPeriod371','policyHistory371','groupId','tab29','tab30'].filter(k=>el.dataset[k]).map(k=>k+':'+el.dataset[k]);if(identifiers.length)return identifiers.join('|');
 const parent=el.parentElement?.closest('details');return (parent?screenKey38(parent)+'/':'')+el.tagName+':'+(el.querySelector(':scope > summary')?.textContent??el.textContent??'').trim().replace(/\s+/g,' ').slice(0,180);
}
function screenQuery68(page,selector){const hub=document.querySelector('#portalTabs32');return [...page.querySelectorAll(selector),...(hub?[...hub.querySelectorAll(selector)]:[])];}
function screenCapture38(action){
 if(!boot?.user||!view||/^(login|logout|language|identity|mode29|change_password|capture_ticket|tracking|server_clock|translation|site_upgrade|backup_|restore)/.test(action))return null;
 const page=$('#page');if(!page)return null;
 const filter=el=>(el.id||el.type==='search')&&el.type!=='password'&&el.type!=='file'&&(el.type==='search'||el.closest('.toolbar,.filters,.filter-grid,.report-filters,.page-head,.access-toolbar30'))&&!el.closest('details,.modal-body');
 const inputs=screenQuery68(page,'input,select').filter(filter).map(el=>({id:el.id,searchIndex:screenQuery68(page,'input[type=search]').indexOf(el),value:el.value,check:el.type==='checkbox'?el.checked:null}));
 const details=[...page.querySelectorAll('details')].map(el=>[screenKey38(el),el.open]);
 const tabs=screenQuery68(page,'[role=tab][aria-selected=true],[data-tab29][aria-current=page]').map(el=>screenKey38(el));
 const scrolling=[...page.querySelectorAll('[id],.tab-strip32,.table-wrap,.paygrid-scroll37')].filter(el=>el.scrollLeft||el.scrollTop).map(el=>[screenKey38(el),el.scrollLeft,el.scrollTop]);
 const expanded=[...page.querySelectorAll('button[aria-expanded=true]')].map(screenKey38);
 return {expanded,profile:page.querySelector('.profile-tabs29')&&typeof profileUser29!=='undefined'?{id:profileUser29.id,tab:profileTab29}:null,view,identity:screenIdentity38(),node:page.firstElementChild,inputs,details,tabs,scrolling,x:window.scrollX,y:window.scrollY,passes:0,expires:Date.now()+30000};
}
function screenCommit38(state,result){if(state&&result?.ok!==false&&state.view===view&&state.identity===screenIdentity38())screenSave38=state;}
function screenRestore38(){
 const state=screenSave38,page=$('#page');if(!state||!page||screenRestoreBusy38)return;
 if(state.view!==view||state.identity!==screenIdentity38()||Date.now()>state.expires){screenSave38=null;return;}
 if(page.firstElementChild===state.node||page.querySelector('.loading,.loading-screen,.loading-state32,.loading32')||!page.children.length)return;
 // Do not consume state on the temporary navigation loading placeholder.
 if(page.textContent.trim()===uiText('جارٍ التحميل…'))return;
 screenRestoreBusy38=true;
 try{
  const changed=[];for(const old of state.inputs){const el=old.id?document.getElementById(old.id):screenQuery68(page,'input[type=search]')[old.searchIndex];if(!el||(!page.contains(el)&&!el.closest('#portalTabs32')))continue;if(el.value!==old.value){el.value=old.value;if(el.value===old.value)changed.push(el);}if(old.check!==null)el.checked=old.check;}
  const nodes=[...page.querySelectorAll('details')];for(const [key,open]of state.details){const el=nodes.find(n=>screenKey38(n)===key);if(el)el.open=open;}
  let tabChanged=false;for(const key of state.tabs){const el=screenQuery68(page,'[role=tab],[data-tab29]').find(n=>screenKey38(n)===key);if(el&&el.getAttribute('aria-selected')!=='true'&&el.getAttribute('aria-current')!=='page'){el.click();tabChanged=true;}}for(const key of state.expanded){const el=[...page.querySelectorAll('button[aria-expanded]')].find(n=>screenKey38(n)===key);if(el&&el.getAttribute('aria-expanded')!=='true')el.click();}
  for(const [key,x,y]of state.scrolling){const el=[...page.querySelectorAll('[id],.tab-strip32,.table-wrap,.paygrid-scroll37')].find(n=>screenKey38(n)===key);if(el){el.scrollLeft=x;el.scrollTop=y;}}
  window.scrollTo({left:state.x,top:state.y,behavior:'instant'});
  state.node=page.firstElementChild;state.passes++;
  // Reapply filter handlers so visible rows and query results match the restored controls.
  if(state.passes<3)for(const el of changed){el.dispatchEvent(new Event(el.type==='search'?'input':'change',{bubbles:true}));}
  if((!changed.length&&!tabChanged)||state.passes>=3)screenSave38=null;
 }finally{screenRestoreBusy38=false;}
}
document.addEventListener('click',e=>{if(e.isTrusted&&!screenRestoreBusy38&&e.target.closest('[data-view],[role=tab],.pager32,.pagination'))screenSave38=null;},true);
document.addEventListener('input',e=>{if(e.isTrusted&&!screenRestoreBusy38&&e.target.closest('#page,#portalTabs32')&&!e.target.closest('details'))screenSave38=null;},true);

async function restoreProfileSave38(){
 const state=screenSave38;if(!state?.profile||state.view!=='users'||state.identity!==screenIdentity38())return false;
 const list=await api('users'),person=list.find(u=>Number(u.id)===Number(state.profile.id));if(!person)return false;
 profileTab29=state.profile.tab;await employeeProfile29(person);return true;
}
