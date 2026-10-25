'use strict';
// Read-only refresh for every existing header date-range filter; never save forms.
function periodControls98(key){
 if(typeof financeShellKeys101!=='undefined'&&financeShellKeys101.has(key))return [document.getElementById('financeFrom101'),document.getElementById('financeTo101')];
 const field=(form,name)=>document.querySelector('#'+form+' [name="'+name+'"], [form="'+form+'"][name="'+name+'"]');
 if(key==='v28_debt'||key==='debt_statement95')return [document.getElementById('debtFrom95'),document.getElementById('debtTo95')];
 if(key==='report')return [document.getElementById('reportFrom'),document.getElementById('reportTo')];
 const form=key==='analytics'?'v26ReportForm':key==='audit'?'audit26':null;
 return form?[field(form,'from'),field(form,'to')]:null;
}
function mountPeriodRefresh98(root){
 const pair=periodControls98(view),secondary=root.querySelector('.header-secondary77');
 if(!pair||!secondary)return;
 let box=secondary.querySelector('.period-refresh98');
 if(!box){box=document.createElement('div');box.className='period-refresh98';box.dataset.movedFilter75='1';box.innerHTML='<button type="button" data-period-refresh98></button>';secondary.append(box);}
 const button=box.querySelector('button'),key=view,epoch=navigationEpoch32;
 const title=uiText('تحديث');if(button.textContent!==title)button.textContent=title;
 const page=document.getElementById('page'),run=key==='report'?document.getElementById('runReport'):null;
 button.disabled=!!button.dataset.busy98||!pair.every(n=>n&&!n.disabled)||!!page?.hasAttribute('data-screen-loading94')||!!page?.hasAttribute('data-screen-failed94')||(key==='report'&&(!run||run.disabled));
 button.onclick=async()=>{
  if(button.disabled||key!==view||epoch!==navigationEpoch32)return;
  const [from,to]=periodControls98(key)||[];
  if(!from||!to)return;
  if(!from.value||!to.value||from.value>to.value){toast(uiText('من تاريخ يجب ألا يتجاوز إلى تاريخ'));return;}
  button.dataset.busy98='1';button.disabled=true;button.setAttribute('aria-busy','true');
  try{
   if(typeof from._periodRefresh98==='function')await from._periodRefresh98();
   else if(key==='report'){const action=document.getElementById('runReport');if(action&&!action.disabled)await action.onclick?.call(action);}
   else {const form=document.getElementById(key==='analytics'?'v26ReportForm':'audit26');if(form?.reportValidity())await form.onsubmit?.call(form,new Event('submit',{cancelable:true}));}
  }catch(e){if(key===view&&epoch===navigationEpoch32)toast(e.message);}
  finally{delete button.dataset.busy98;button.removeAttribute('aria-busy');if(button.isConnected&&key===view&&epoch===navigationEpoch32)mountPeriodRefresh98(root);}
 };
 // Reserve the refresh slot on the first render, alongside all other date filters.
 const count=[...secondary.children].filter(n=>!n.hidden).length;
 secondary.style.gridTemplateColumns='repeat('+Math.max(1,count)+',minmax(0,1fr))';secondary.hidden=false;
}
