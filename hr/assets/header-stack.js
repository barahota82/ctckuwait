'use strict';
const headerStackScreens77=new Set(['payroll_entry_click126','payroll_entry_afaq126','debt_statement95','users','workforce','employment','document','work_permit','custody','discipline','evaluation','request','policies','v28_locations','leave','sick','leave_overview','leave_cash','resumption','payroll','commissions','collection37','payroll_review35','bank','vouchers37','payments26','payout_status','allowances','addition','entitlement','eos','video_auto','video_custom','video_receipts','report','external','tracking','proof','loan','v28_debt','analytics','v26_history','audit','companies','v28_files','v28_documents','v28_archive']);
function headerStackActive77(){return headerStackScreens77.has(view);}
function splitHeader77(){
 if(!headerStackActive77())return;
 if(typeof reconcileHeader79==='function')reconcileHeader79();
 const root=recordFilterHost75();if(!root)return;
 root.classList.add('header-stack77');
 if(typeof markHeaderActions80==='function')markHeaderActions80(root);
 if(typeof classifyHeaderActions84==='function')classifyHeaderActions84(root);
 else for(const slot of root.querySelectorAll('[data-top-actions49]'))slot.classList.toggle('header-level-two81',!!slot.querySelector('#addAllowance,#fridayRate,[data-pending-action79=addAllowance],[data-pending-action79=fridayRate],[data-tool50]'));
 let nav=root.querySelector(':scope > .header-navigation77');
 if(!nav){nav=document.createElement('div');nav.className='header-navigation77';root.prepend(nav);}
 let primary=root.querySelector(':scope > .header-primary77');
 if(!primary){primary=document.createElement('div');primary.className='header-primary77';root.append(primary);}
 let secondary=root.querySelector(':scope > .header-secondary77');
 if(!secondary){secondary=document.createElement('div');secondary.className='header-secondary77 header-filters76';root.append(secondary);}
 let row=root.querySelector('[data-record-filter-row75]');
 // Late-rendered controls can arrive after the initial split; normalize them too.
 for(const tools of root.querySelectorAll('.toolbar')){
  for(const child of [...tools.children])if(child.matches('input:not([type=hidden]),select,.date-field32,.search-select32'))headerLabel76(child);
 }
 const loose=[...root.querySelectorAll('.operational-controls68 > label,.record-filters49 > label,.toolbar > label')].filter(n=>!n.closest('.header-secondary77')&&!n.querySelector('input[type=checkbox],input[type=radio]'));

 if(!row&&loose.length){row=document.createElement('div');row.className='record-filter-row75 header-filters76';row.dataset.recordFilterRow75='';primary.append(row);}
 if(row){row.classList.add('header-filters76');if(row.parentElement!==primary)primary.append(row);}
 if(row){
  const fields=[...row.querySelectorAll(':scope > label,:scope > .toolbar > label'),...loose];
  for(const label of fields){
   const isPrimary=label.matches('.leave-employee-filter69,.record-search-label32')||!!label.querySelector('input[type=search]');
   for(const input of label.querySelectorAll('input,select,textarea')){const form=input.form;if(form?.id)input.setAttribute('form',form.id);}
   label.dataset.movedFilter75='1';headerTitle76(label);if(isPrimary)label.classList.add(label.matches('.leave-employee-filter69')?'header-person76':'header-search76');
   if(isPrimary){if(label.parentElement!==row)row.append(label);}else if(label.parentElement!==secondary)secondary.append(label);
  }
  // Forms remain intact around their action buttons. External filters retain form ownership.
  for(const tools of [...row.querySelectorAll(':scope > .toolbar')])if(tools.parentElement!==nav)nav.append(tools);
  const person=row.querySelector(':scope > .leave-employee-filter69');
  const searches=[...row.querySelectorAll(':scope > .record-search-label32')];
  if(person){let cursor=person;for(const search of searches){if(cursor.nextElementSibling!==search)cursor.after(search);cursor=search;}}
  const hasPerson=!!person&&!person.hidden;
  row.style.gridTemplateColumns=hasPerson&&searches.some(n=>!n.hidden)?'minmax(0,1.6fr) minmax(0,1fr)':'minmax(0,1fr)';
 }
 if(typeof finishHeaderReview82==='function')finishHeaderReview82(root);
 for(const child of [...root.childNodes])if(child!==nav&&child!==primary&&child!==secondary)nav.append(child);
 for(const shell of nav.querySelectorAll('.header-actions76,.toolbar:not(form)'))if(!shell.children.length)shell.remove();
 // Keep title, main tabs/actions, sub-tabs and toolbar actions in one stable order.
 const rank=node=>node.matches('.portal-hub-title32')?0:node.matches('.portal-tabs32,.tab-scroll53')?1:node.matches('.header-approved-actions84')?3:node.matches('.top-actions49')?2:node.matches('.operational-controls68,.record-tabs49')?3:4;
 const ordered=[...nav.children].sort((a,b)=>rank(a)-rank(b));
 ordered.forEach((node,index)=>{if(nav.children[index]!==node)nav.insertBefore(node,nav.children[index]||null);});
 primary.hidden=!row||![...row.children].some(n=>!n.hidden);
 secondary.hidden=![...secondary.children].some(n=>!n.hidden);
 const count=[...secondary.children].filter(n=>!n.hidden).length;
 if(count)secondary.style.gridTemplateColumns='repeat('+count+',minmax(0,1fr))';
 if(typeof mountPeriodRefresh98==='function')mountPeriodRefresh98(root);
 if(typeof scheduleStickyTabs48==='function')scheduleStickyTabs48();
}
