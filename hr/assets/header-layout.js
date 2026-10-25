'use strict';
const headerFieldNames76={v26Month:'الشهر والسنة',reviewCompany41:'شركة العمل',reportFrom:'من تاريخ',reportTo:'إلى تاريخ',bankMonth:'الشهر والسنة',bankCompany:'الشركة',bankName28:'البنك',payMonth35:'الشهر والسنة',gridDate37:'تاريخ الحركة'};
function headerLabel76(node){
 if(node.matches('label'))return node;
 const input=node.matches('input,select')?node:node.querySelector('input:not([type=hidden]),select');if(!input||input.type==='checkbox'||input.type==='radio')return null;
 const text=headerFieldNames76[input.id]||({from:'من تاريخ',to:'إلى تاريخ',company:'الشركة',department:'القسم',month:'الشهر والسنة',actor:'منفذ العملية',action_type:'نوع العملية'})[input.name]||input.getAttribute('aria-label')||input.placeholder||uiText('التصفية');
 const label=document.createElement('label'),title=document.createElement('span');label.className='header-field76';title.textContent=uiText(text);node.before(label);label.append(title,node);return label;
}
function headerTitle76(label){
 if(label.querySelector('input[type=checkbox],input[type=radio]'))return;
 let title=label.querySelector(':scope > span');if(!title){const texts=[...label.childNodes].filter(n=>n.nodeType===3&&n.textContent.trim());if(!texts.length)return;title=document.createElement('span');title.className='header-label76';for(const text of texts)title.append(text);label.prepend(title);}
 const text=title.textContent.trim();if(title.title!==text)title.title=text;
}
function polishHeader76(){
 const page=document.querySelector('#page'),portal=document.querySelector('#portalTabs32');if(!page)return;
 for(const host of document.querySelectorAll('#portalTabs32,#recordTabs49'))host.classList.add('header-card76');
 for(const row of document.querySelectorAll('[data-record-filter-row75]')){
  row.classList.add('header-filters76');
  for(const tools of row.querySelectorAll(':scope > .toolbar')){
   for(const child of [...tools.children])if(child.matches('input:not([type=hidden]),select,.date-field32,.search-select32'))headerLabel76(child);
   const buttons=[...tools.children].filter(n=>n.matches('button,a.button')||(n.matches('label')&&n.querySelector('input[type=checkbox],input[type=radio]')));
   let actions=tools.querySelector(':scope > .header-actions76');
   if(buttons.length&&!actions){actions=document.createElement('div');actions.className='header-actions76';tools.append(actions);}
   for(const button of buttons)actions.append(button);
  }
  const person=row.querySelector(':scope > .leave-employee-filter69'),searches=[...row.querySelectorAll(':scope > .record-search-label32')];
  if(person)person.classList.add('header-person76');
  for(const search of searches)search.classList.add('header-search76');
  // Move the existing search immediately after the employee; never create another input.
  if(person){let cursor=person;for(const search of searches){if(cursor.nextElementSibling!==search)cursor.after(search);cursor=search;}}
  for(const label of row.querySelectorAll(':scope > label,:scope > .toolbar > label'))headerTitle76(label);
  const fields=[...row.querySelectorAll(':scope > label,:scope > .toolbar > label')].filter(n=>!n.hidden&&!n.closest('[hidden]'));
  const primary=fields.filter(n=>n.matches('.header-person76,.header-search76'));
  const rest=fields.length-primary.length;
  const columns=primary.map(n=>n.matches('.header-person76')?'minmax(0,var(--header-person76))':'minmax(0,var(--header-search76))');
  if(rest)columns.push('repeat('+rest+',minmax(0,1fr))');
  const value=columns.join(' ')||'minmax(0,1fr)';if(row.style.gridTemplateColumns!==value)row.style.gridTemplateColumns=value;
 }
 // Other pages retain their existing controls and order but share spacing and geometry.
 for(const toolbar of page.querySelectorAll(':scope > .toolbar,:scope > form.toolbar,.access-toolbar30,.access-member-filters30')){toolbar.classList.add('header-toolbar76');for(const label of toolbar.querySelectorAll(':scope > label'))headerTitle76(label);}
 if(typeof splitHeader77==='function')splitHeader77();
}
