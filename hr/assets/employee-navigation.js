'use strict';
// These controls navigate already-authorized records. They never widen an API scope.
const dirtyEmployeeForms40=new WeakSet();
function employeeValueEvent138(event){return !(event.target.closest?.('.search-select32,.date-calendar32')&&!event.target.name);}
document.addEventListener('input',e=>{const form=e.target.closest?.('form');if(form&&employeeValueEvent138(e))dirtyEmployeeForms40.add(form);});
document.addEventListener('change',e=>{const form=e.target.closest?.('form');if(form&&employeeValueEvent138(e))dirtyEmployeeForms40.add(form);});
function employeeTransitionAllowed40(root=document){
 if(root.querySelector?.('#employeeEditorForm134')&&typeof employeeEditorState134!=='undefined'&&employeeEditorState134?.saving)return false;
 const dirty=[...root.querySelectorAll('form')].some(f=>dirtyEmployeeForms40.has(f));
 return !dirty||confirm(uiText('توجد تعديلات غير محفوظة. هل تريد الانتقال دون حفظها؟'));
}
function employeeOrder40(rows){return [...rows].sort((a,b)=>String(a.employee_code||a.username||'').localeCompare(String(b.employee_code||b.username||''),'en',{numeric:true})||Number(a.id)-Number(b.id));}
const employeeNavSteps48=[['first','أول موظف'],['prev','الموظف السابق'],['next','الموظف التالي'],['last','آخر موظف']];
function employeeNavIcon48(step){
 const language138=typeof boot!=='undefined'?boot?.localization?.language:null,rtl=language138?language138!=='en':document.documentElement.dir==='rtl',first=rtl?step==='last':step==='first',last=rtl?step==='first':step==='last',back=rtl?(step==='last'||step==='next'):(step==='first'||step==='prev');
 return '<svg data-employee-icon138="'+step+'" data-employee-locale138="'+(rtl?'ar':'en')+'" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'+((first||last)?'<path d="'+(first?'M20 6l-6 6 6 6':'M4 6l6 6-6 6')+'"/>':'')+'<path d="'+(back?'M15 6l-6 6 6 6':'M9 6l6 6-6 6')+'"/></svg>';
}
function employeeNavIndex48(length,index,step){if(index<0)return -1;return step==='first'?0:step==='last'?length-1:index+(step==='prev'?-1:1);}
function employeeProfileNav40(user){
 if(boot.access_mode==='personal'||!teamPermission('users'))return '';
 const list=employeeOrder40(users),index=list.findIndex(u=>Number(u.id)===Number(user.id));
 if(index<0)return '';
 return '<nav class="employee-nav48" aria-label="'+esc(uiText('التنقل بين الموظفين'))+'">'+employeeNavSteps48.map(([step,label])=>{const target=employeeNavIndex48(list.length,index,step),disabled=target===index||target<0||target>=list.length;return '<button type="button" data-profile-'+step+'40 aria-label="'+esc(uiText(label))+'" title="'+esc(uiText(label))+'" '+(disabled?'disabled':'')+'>'+employeeNavIcon48(step)+'</button>';}).join('')+'</nav>';
}
function bindEmployeeProfileNav40(user){
 const list=employeeOrder40(users),index=list.findIndex(u=>Number(u.id)===Number(user.id));
 for(const [step]of employeeNavSteps48){
  const button=document.querySelector('[data-profile-'+step+'40]');if(!button)continue;
  button.onclick=async()=>{const target=list[employeeNavIndex48(list.length,index,step)];if(!target||Number(target.id)===Number(user.id)||!employeeTransitionAllowed40(document.querySelector('#page')))return;
   button.disabled=true;try{await employeeProfile29(target,{tab:profileTab29});}catch(error){toast(error.message);}finally{if(button.isConnected)button.disabled=false;}};
 }
}
function employeeCardTarget40(ordered,card,step){
 const visible=ordered.filter(c=>!c.hidden&&!c.hasAttribute?.('data-employee-filtered73')&&c.isConnected!==false),index=visible.indexOf(card);
 if(index<0)return undefined;
 if(step==='first'||step==='last'){const target=visible[step==='first'?0:visible.length-1];return target?.dataset?.employeeId===card.dataset?.employeeId?undefined:target;}
 const offset=step==='prev'||step===-1?-1:1;
 for(let i=index+offset;i>=0&&i<visible.length;i+=offset){if(!card.dataset?.employeeId||visible[i].dataset?.employeeId!==card.dataset.employeeId)return visible[i];}
 return undefined;
}
function employeeNavFooter48(root,nav){
 const history69=root.querySelector(':scope > .policy-history32');if(history69&&root.lastElementChild!==history69)root.append(history69);
 const bars=[...root.querySelectorAll('.row-actions,.toolbar,.profile-tools33,.record-actions32')].filter(bar=>!bar.closest('.policy-history32')&&!nav.contains(bar)&&bar!==nav&&bar.querySelector('button:not([data-employee-step48])')&&(()=>{for(let el=bar;el&&el!==root;el=el.parentElement)if(el.hidden||(el.tagName==='DETAILS'&&!el.open))return false;return true;})());
 const policy69=typeof view!=='undefined'&&view==='policies'?root.querySelector(':scope > .record-details32 > .record-actions32'):null;
 const bar=policy69||bars.at(-1);
 if(bar){bar.classList.add('employee-footer48');let actions=bar.querySelector(':scope > .employee-actions50');if(!actions){actions=document.createElement('div');actions.className='employee-actions50';for(const child of [...bar.childNodes])if(child!==nav)actions.append(child);bar.prepend(actions);}if(nav.parentElement!==bar)bar.append(nav);}
 else if(typeof view!=='undefined'&&view==='policies'&&history69){const detail=root.querySelector(':scope > .record-details32');if(detail){let footer=detail.querySelector(':scope > .employee-footer48');if(!footer){footer=document.createElement('div');footer.className='record-actions32 employee-footer48';detail.append(footer);}if(nav.parentElement!==footer)footer.append(nav);}else {let footer=root.querySelector(':scope > .employee-footer48');if(!footer){footer=document.createElement('div');footer.className='row-actions employee-footer48';history69.before(footer);}footer.append(nav);}}
 else {let footer=root.querySelector(':scope > .employee-footer48');if(!footer){footer=document.createElement('div');footer.className='row-actions employee-footer48';if(history69)history69.before(footer);else root.append(footer);}if(nav.parentElement!==footer)footer.append(nav);}
}
// History retains its own controls while sharing the authorized employee navigation.
function employeeHistoryNavigation74(card,nav){
 if(typeof view==='undefined'||view!=='policies')return;
 const history=card.querySelector(':scope > .policy-history32');if(!history)return;
 for(const section of history.querySelectorAll(':scope > .record-details32')){
  let bar=section.querySelector(':scope > .record-actions32');if(!bar){bar=document.createElement('div');bar.className='record-actions32';section.append(bar);}
  bar.classList.add('employee-footer48');
  let actions=bar.querySelector(':scope > .employee-actions50');
  if(!actions){actions=document.createElement('div');actions.className='employee-actions50';for(const child of [...bar.childNodes])if(!child.matches?.('[data-employee-history-nav74]'))actions.append(child);bar.prepend(actions);}
  let copy=bar.querySelector(':scope > [data-employee-history-nav74]');
  if(!copy){copy=document.createElement('div');copy.className='employee-nav48';copy.dataset.employeeHistoryNav74='';copy.setAttribute('role','navigation');copy.setAttribute('aria-label',uiText('التنقل بين الموظفين'));bar.append(copy);}
  const originals=[...nav.querySelectorAll('button')];
  if(!copy.children.length)for(const source of originals){const button=source.cloneNode(true);button.removeAttribute('data-employee-step48');button.onclick=()=>source.click();copy.append(button);}
  [...copy.children].forEach((button,i)=>{button.disabled=originals[i].disabled;});
 }
}
function addEmployeeCardNavigation40(root=document.querySelector('#page')){
 if(!root||boot?.access_mode==='personal')return;
 const groups=new Map();
 for(const card of root.querySelectorAll('details[data-employee-id]')){
  if(card.closest('#staffPanel32[data-workspace173]'))continue;
  if(!Number(card.dataset.employeeId)||card.parentElement.closest('details[data-employee-id]'))continue;
  if(!groups.has(card.parentElement))groups.set(card.parentElement,[]);groups.get(card.parentElement).push(card);
 }
 for(const cards of groups.values()){
  if(new Set(cards.map(c=>c.dataset.employeeId)).size<2){for(const card of cards){card.querySelector('[data-employee-nav40]')?.remove();card.querySelectorAll('[data-employee-history-nav74]').forEach(n=>n.remove());}continue;}
  const ordered=[...cards].sort((a,b)=>{
   const person=id=>users.find(u=>Number(u.id)===Number(id));
   const key=card=>person(card.dataset.employeeId)?.employee_code||card.querySelector('summary')?.textContent||'';
   return key(a).localeCompare(key(b),'en',{numeric:true});
  });
  for(const card of cards){
   const existing=card.querySelector('[data-employee-nav40]');
   if(existing){employeeNavFooter48(card,existing);for(const button of existing.querySelectorAll('button'))button.disabled=!employeeCardTarget40(ordered,card,button.dataset.employeeStep48);employeeHistoryNavigation74(card,existing);continue;}
   const nav=document.createElement('div');nav.dataset.employeeNav40='';nav.className='employee-nav48';nav.setAttribute('role','navigation');nav.setAttribute('aria-label',uiText('التنقل بين الموظفين'));
   for(const [step,label]of employeeNavSteps48){
    const button=document.createElement('button');button.type='button';button.innerHTML=employeeNavIcon48(step);button.setAttribute('aria-label',uiText(label));button.title=uiText(label);
    button.dataset.employeeStep48=step;
    const target=()=>{
     const current=[...card.parentElement.querySelectorAll(':scope > details[data-employee-id]')];
     current.sort((a,b)=>{const key=c=>users.find(u=>Number(u.id)===Number(c.dataset.employeeId))?.employee_code||c.querySelector('summary')?.textContent||'';return key(a).localeCompare(key(b),'en',{numeric:true});});
     return employeeCardTarget40(current,card,step);
    };button.disabled=!employeeCardTarget40(ordered,card,step);
    button.onclick=()=>{const next=target();if(!next||!employeeTransitionAllowed40(card))return;card.open=false;next.open=true;next.scrollIntoView({block:'start',behavior:'auto'});next.querySelector(':scope > summary')?.focus({preventScroll:true});};nav.append(button);
   }
   employeeNavFooter48(card,nav);employeeHistoryNavigation74(card,nav);
  }
 }
}
let employeeNavScheduled40=false;
const employeeNavObserveOptions40={childList:true,subtree:true,attributes:true,attributeFilter:['hidden','open','data-employee-filtered73']};
function employeeNavRelevant57(records){
 return records.some(r=>{
  const target=r.target?.nodeType===1?r.target:r.target?.parentElement;
  if(!target?.closest?.('#page'))return false;
  if(target.closest('details[data-employee-id]'))return true;
  return r.type==='childList'&&[...r.addedNodes,...r.removedNodes].some(n=>n.matches?.('details[data-employee-id]')||n.querySelector?.('details[data-employee-id]'));
 });
}
const employeeNavObserver40=new MutationObserver(records=>{if(!employeeNavRelevant57(records)||employeeNavScheduled40)return;employeeNavScheduled40=true;const run=()=>{employeeNavScheduled40=false;employeeNavObserver40.disconnect();try{addEmployeeCardNavigation40();}finally{employeeNavObserver40.observe(document.body,employeeNavObserveOptions40);}};if(typeof requestAnimationFrame==='function')requestAnimationFrame(run);else queueMicrotask(run);});
employeeNavObserver40.observe(document.body,employeeNavObserveOptions40);
addEmployeeCardNavigation40();

async function personalHistoryView(){
 const rows=await api('personal_history');
 const labels={salary_history:'تاريخ الرواتب',leave_history:'الإجازات',leave_settlement:'تسويات الإجازة النقدية'};
 $('#page').innerHTML=head('سجلي السابق','')+'<section class="card">'+(rows.length?table(['النوع','التاريخ','التفاصيل'],rows.map(r=>[esc(uiText(labels[r.kind]||r.kind)),esc(dateDisplay32(r.period_key)),detail26(JSON.parse(r.body_json),{module:r.module||r.kind||''})])):empty('لا توجد نتائج'))+'</section>';
}

function refreshEmployeeNavigation138(root=document){
 for(const svg of root.querySelectorAll('svg[data-employee-icon138]')){
  const step=svg.dataset.employeeIcon138,locale=boot?.localization?.language||(document.documentElement.dir==='rtl'?'ar':'en');if(!['first','prev','next','last'].includes(step)||svg.dataset.employeeLocale138===locale)continue;
  const box=document.createElement('div');box.innerHTML=employeeNavIcon48(step);const next=box.firstElementChild;
  if(svg.innerHTML!==next.innerHTML)svg.replaceChildren(...next.childNodes);svg.dataset.employeeLocale138=locale;
 }
}
