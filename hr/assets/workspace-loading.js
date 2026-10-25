'use strict';
// Keep the approved workspace shell in place while a tab reads its own data.
function workspaceFamily196(key){
 if(typeof reviewTarget193==='function'&&reviewTarget193(key))return 'review:'+family193(key);
 if(typeof payrollEntryKeys127!=='undefined'&&payrollEntryKeys127.has(key))return 'finance:payroll';
 if(typeof financeShellKeys101!=='undefined'&&financeShellKeys101.has(key))return key.startsWith('contract_')?'finance:contracts':'finance:receipts';
 return key==='exit_permissions183'?'permissions':key==='v28_debt'?'finance:debt':'';
}
function retainWorkspace196(key){const family=workspaceFamily196(key),root=document.querySelector('#page>[data-workspace196]');return !!family&&root?.dataset.workspace196===family&&root.dataset.owner196===screenIdentity38();}
function mountWorkspace196(fresh,key){
 const page=document.getElementById('page'),family=workspaceFamily196(key);fresh.dataset.workspace196=family;fresh.dataset.owner196=screenIdentity38();
 if(!retainWorkspace196(key)){page.replaceChildren(fresh);return fresh;}
 const root=page.firstElementChild;
 for(const selector of [':scope>header',':scope>nav']){const old=root.querySelector(selector),next=fresh.querySelector(selector);if(old&&next){old.className=next.className;old.replaceChildren(...next.childNodes);next.replaceWith(old);}}
 root.className=fresh.className;root.replaceChildren(...fresh.childNodes);root.setAttribute('aria-busy','true');return root;
}
function readFeedback196(host,result,retry){
 const box=document.createElement('div');box.className='read-feedback196';box.hidden=true;result.before(box);let failed=false;
 const text=(ar,en)=>boot?.localization?.language==='en'?en:uiText(ar);
 return {
  get failed(){return failed;},
  busy(value){box.toggleAttribute('data-loading197',value);host.setAttribute('aria-busy',String(value));result.setAttribute('aria-busy',String(value));result.inert=value||failed;if(value){failed=false;box.hidden=false;box.setAttribute('role','status');box.textContent=text('جارٍ التحميل…','Loading…');}else if(!failed){box.hidden=true;box.replaceChildren();}},
  error(error){delete box.dataset.loading197;failed=true;if(!result.querySelector('table'))result.replaceChildren();box.hidden=false;box.setAttribute('role','alert');box.replaceChildren();const message=document.createElement('span');message.textContent=error.message||text('تعذر التحميل.','Loading failed.');const button=document.createElement('button');button.type='button';button.textContent=text('إعادة المحاولة','Retry');button.onclick=()=>void retry();box.append(message,button);result.inert=true;},
  success(){failed=false;}
 };
}
