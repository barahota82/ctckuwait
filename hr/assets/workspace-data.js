'use strict';
// Identity comparisons must not depend on PDO returning an integer or a string.
function idKey195(value){const s=String(value??'').trim();return /^\d+$/.test(s)?s.replace(/^0+(?=\d)/,''):s;}
function idValue195(value){const key=idKey195(value),n=Number(key);return /^\d+$/.test(key)&&Number.isSafeInteger(n)?n:key;}
function sameId195(a,b){return idKey195(a)===idKey195(b);}
function employeeSearchText195(person){return normalize32([person.employee_code,person.code,person.name,person.name_en,person.profile?.name_en,displayName31(person)].filter(Boolean).join(' '));}

// Only the small employee picker catalogue is reused, never records or reports.
// Every write, identity/company/language change and explicit refresh invalidates it.
const staffChoicesCache195=new Map();
const recordPages195=new Map();
function staffChoices195(screen,force=false){
 const key=JSON.stringify([screenIdentity38(),boot?.user?.id,boot?.company_filter30,boot?.localization?.language,readGeneration119,screen]);
 const cached=staffChoicesCache195.get(key);
 if(!force&&cached&&cached.until>Date.now())return cached.promise.then(rows=>structuredClone(rows));
 for(const [k,v] of staffChoicesCache195)if(v.until<=Date.now())staffChoicesCache195.delete(k);
 const entry={until:Date.now()+15000,promise:api('user_choices29',null,{screen})};staffChoicesCache195.set(key,entry);
 entry.promise.catch(()=>{if(staffChoicesCache195.get(key)===entry)staffChoicesCache195.delete(key);});
 return entry.promise.then(rows=>structuredClone(rows));
}

let modalRequest195=0;
function modalGuard195(){
 const ticket=++modalRequest195,epoch=navigationEpoch32,identity=screenIdentity38(),page=document.getElementById('page')?.firstElementChild;
 return ()=>ticket===modalRequest195&&epoch===navigationEpoch32&&identity===screenIdentity38()&&page?.isConnected;
}
function validity195(row,today=dateNow()){
 if(row.date_to&&String(row.date_to).slice(0,10)<today)return 'expired';
 if(row.date_from&&String(row.date_from).slice(0,10)>today)return 'future';
 return row.date_to?'valid':'none';
}
function proofState195(row){return row.status==='active'?'awaiting_response':row.status==='closed'?'responded':row.status;}
function eventLocation195(event){
 const accuracy=event.accuracy==null?'—':val193(event.accuracy)+' '+text193('متر','m');
 return accuracy+(event.latitude!=null&&event.longitude!=null&&Number.isFinite(Number(event.latitude))&&Number.isFinite(Number(event.longitude))?' · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+encodeURIComponent(event.latitude+','+event.longitude)+'">'+text193('الموقع','Location')+'</a>':'');
}
function eventAttachments195(event){return [event.photo_id?'<a href="file.php?id='+encodeURIComponent(idKey195(event.photo_id))+'" target="_blank" rel="noopener">'+text193('الصورة','Photo')+'</a>':'',event.evidence_id?'<a href="file.php?id='+encodeURIComponent(idKey195(event.evidence_id))+'" target="_blank" rel="noopener">'+text193('السند','Evidence')+'</a>':''].filter(Boolean).join(' · ')||'—';}
let modalCloseFocus195=null;
function modalFocus195(dialog,opener){
 if(modalCloseFocus195)dialog.removeEventListener('close',modalCloseFocus195);
 modalCloseFocus195=()=>{if(!dialog.open&&opener?.isConnected)opener.focus({preventScroll:true});};dialog.addEventListener('close',modalCloseFocus195,{once:true});
 dialog.onkeydown=event=>{
  if(event.key!=='Tab')return;
  const nodes=[...dialog.querySelectorAll('button,input,select,textarea,a[href],[tabindex]')].filter(n=>!n.disabled&&n.tabIndex>=0&&!n.closest('[hidden],[inert]')&&n.getClientRects().length);
  if(!nodes.length){event.preventDefault();return;}
  const first=nodes[0],last=nodes.at(-1),active=document.activeElement;
  if(!nodes.includes(active)){event.preventDefault();(event.shiftKey?last:first).focus();}
  else if(event.shiftKey&&active===first){event.preventDefault();last.focus();}
  else if(!event.shiftKey&&active===last){event.preventDefault();first.focus();}
 };
}

function operationalForm195(form,module){
 if(!form||!['leave','sick','leave_cash','external','proof'].includes(module))return;
 form.classList.add('review-form193');
 const field=name=>form.elements.namedItem(name),label=name=>field(name)?.closest('label');
 const relabel=(name,ar,en)=>{const el=label(name);if(!el)return;const node=[...el.childNodes].find(n=>n.nodeType===3);if(node)node.textContent=tr193(ar,en);};
 const select=(name,items)=>{const old=field(name);if(!old)return;const value=old.value,node=document.createElement('select');node.name=name;node.required=true;node.innerHTML=items.map(([key,ar,en])=>'<option value="'+esc(key)+'">'+text193(ar,en)+'</option>').join('');old.replaceWith(node);if(items.some(i=>i[0]===value))node.value=value;};
 for(const grid of [...form.querySelectorAll(':scope > .form-grid')])grid.replaceWith(...grid.childNodes);
 label('title')?.classList.add('review-wide193');label('reason')?.classList.add('review-wide193');label('file')?.classList.add('review-wide193');
 if(['leave','sick','external'].includes(module))for(const key of ['date_from','date_to'])if(field(key))field(key).required=true;
 if(module==='leave')select('leave_type',[['annual','سنوية','Annual'],['unpaid','دون أجر','Unpaid'],['emergency','طارئة','Emergency']]);
 if(module==='leave_cash'){
  const days=field('days');days.type='number';days.min='0.001';days.step='0.001';days.required=true;
  const month=field('payroll_month');month.type='month';month.required=true;relabel('payroll_month','شهر الإضافة للراتب','Payroll month');
  relabel('payment_method','طريقة الصرف','Payment method');select('payment_method',[['cash','نقدي','Cash'],['bank_salary','مع الراتب البنكي','With bank salary'],['bank_separate','تحويل بنكي مستقل','Separate bank transfer']]);
  if(field('amount')){field('amount').required=true;label('amount')?.classList.add('review-wide193');}
 }
 if(module==='proof'){const deadline=field('deadline');if(deadline){deadline.type='datetime-local';deadline.required=true;}}
}

async function recordWorkspaceLoad195({module,host,canAdd,personal,prior,identity,current}){
 const adminRequests=module==='request',find=host.querySelector(adminRequests?'#requestFind185':'#recordFind173'),add=host.querySelector('#addRecord'),allButton=host.querySelector(adminRequests?'#requestAll185':'#recordAll187');
 const query=host.querySelector(adminRequests?'#requestQuery185':'#recordQuery173'),status=host.querySelector(adminRequests?'#requestStatus185':'#recordStatus173'),expiry=host.querySelector('#recordExpiry173'),category=host.querySelector('#recordCategory173'),result=host.querySelector('[data-record-list173]');
 const state={id:personal?boot.user.id:prior?.id||prior?.employee||0,category:prior?.category||'all',query:prior?.query||'',status:prior?.status||'',expiry:prior?.expiry||''};
 if(adminRequests)requestState185.set(identity,state);else recordState173.set(identity,state);
 let people=[],rows=[],sequence=0,ready=false,searchTimer=null;
 const system=module==='evaluation'&&evaluationState182.source==='system';
 const tabKey=()=>identity+'|'+(system?'system|'+JSON.stringify(evaluationQuery182()):'manager')+'|'+state.category;
 const filters=()=>({family:'record',route:adminRequests?'request_admin':module,employee:state.id,category:state.category,query:state.query,status:state.status,expiry:state.expiry});
 const paginate=recordPager189(host,()=>void loadRows(),recordPages195);
 const feedback196=readFeedback196(host,result,()=>loadRows());
 const busy=value=>{feedback196.busy(value);paginate.busy(value);find.disabled=value||!people.length;add.disabled=value||!canAdd||!people.length;allButton.disabled=value;if(!ready)host.querySelectorAll('.record-filters173 input,.record-filters173 select,.record-categories174 button').forEach(n=>n.disabled=value);};
 const remember=()=>{if(adminRequests){state.employee=state.id;requestState185.set(identity,state);}else recordState173.set(identity,state);};
 function draw(data,requestFilters){
  rows=data.rows195||[];
  const allowed=new Set(people.map(p=>idKey195(p.id)));rows=rows.filter(r=>allowed.has(idKey195(r.user_id)));
  const person=people.find(p=>sameId195(p.id,state.id));host.querySelector(adminRequests?'[data-request-person185]':'[data-person173]').innerHTML=person?employeeNameMarkup143(person.id,person.name,person)+(adminRequests?'':' <bdi>'+esc(person.employee_code||'')+'</bdi>'):esc(uiText(personal?'طلباتي الإدارية':'كل الموظفين في نطاقك'));
  allButton.hidden=personal||!state.id;host.querySelector(adminRequests?'[data-request-count185]':'[data-record-total187]').textContent=data.page195.total+' / '+data.page195.unfiltered;
  const visible=paginate(rows,tabKey(),JSON.stringify(requestFilters),data.page195);
  result.innerHTML=visible.length?recordTable191(module,state.category,visible,people,system):empty();
  result.querySelectorAll('[data-record173],[data-detail185]').forEach(button=>button.onclick=()=>{const r=rows.find(r=>sameId195(r.id,button.dataset.record173||button.dataset.detail185));if(r)recordDetail(r);});
  host.querySelectorAll('[data-record-category174],[data-request-category185]').forEach(button=>{const active=(button.dataset.recordCategory174||button.dataset.requestCategory185)===state.category;button.setAttribute('aria-current',active?'location':'false');button.setAttribute('aria-pressed',String(active));});
  applyInterfaceLanguage();
 }
 async function loadRows(){
  if(!current())return;clearTimeout(searchTimer);const ticket=++sequence,requestFilters=filters(),stamp=JSON.stringify(requestFilters);busy(true);
  try{
   const page=paginate.params(tabKey(),stamp),data=await api('list',null,{module,...(state.id?{employee69:state.id}:{}),workspace173:1,...(module==='evaluation'?evaluationQuery182():{}),...(adminRequests?{request_scope50:'admin',requests195:1}:{}),page195:JSON.stringify({...page,filters:requestFilters})});
   if(!current()||ticket!==sequence||stamp!==JSON.stringify(filters()))return;
   if(!data?.page195||!Array.isArray(data.rows195))throw Error(uiText('تعذر قراءة رد الخادم؛ أعد تحميل الصفحة.'));
   ready=true;feedback196.success();remember();draw(data,requestFilters);
  }catch(error){if(!current()||ticket!==sequence||stamp!==JSON.stringify(filters())||error.stale32)return;feedback196.error(error);}
  finally{if(current()&&ticket===sequence){busy(false);host.querySelectorAll('.record-filters173 input,.record-filters173 select,.record-categories174 button').forEach(n=>n.disabled=false);}}
 }
 try{
  busy(true);people=employeeOrder40(personal?[boot.user]:await staffChoices195(module));if(!current())return;users=people;
  if(state.id&&!people.some(p=>sameId195(p.id,state.id)))state.id=personal?boot.user.id:0;
  query.value=state.query;status.value=state.status;if(!status.value)state.status='';if(expiry)expiry.value=state.expiry;if(category){category.value=state.category;if(!category.value)category.value=state.category='all';}
  const select=id=>{if(id&&!people.some(p=>sameId195(p.id,id))||personal&&!sameId195(id,boot.user.id))return false;state.id=id;remember();void loadRows();return true;};
  host.selectEmployee173=select;host.dataset.recordWorkspaceModule175=module;
  find.hidden=personal;find.onclick=()=>recordFind173(people,state.id,select);allButton.onclick=()=>select(0);
  add.onclick=async()=>{if(add.disabled)return;add.disabled=true;recordCategory49=state.category;try{await recordModal(module,state.id||null,people);}finally{if(current())add.disabled=!canAdd||!people.length;}};
  query.oninput=()=>{state.query=query.value;remember();clearTimeout(searchTimer);searchTimer=setTimeout(()=>void loadRows(),200);};
  status.onchange=()=>{state.status=status.value;void loadRows();};if(expiry)expiry.onchange=()=>{state.expiry=expiry.value;void loadRows();};
  host.querySelectorAll('[data-record-category174],[data-request-category185]').forEach(button=>button.onclick=()=>{state.category=button.dataset.recordCategory174||button.dataset.requestCategory185;if(category)category.value=state.category;remember();void loadRows();});
  host.querySelectorAll('.record-filters173 select').forEach(enhanceSelect32);await loadRows();
 }catch(error){if(!current()||error.stale32)return;busy(false);result.innerHTML='<p role="alert" class="error">'+esc(error.message)+'</p><button type="button" data-retry195>'+esc(uiText('إعادة المحاولة'))+'</button>';result.querySelector('[data-retry195]').onclick=()=>adminRequests?requestWorkspace185():recordWorkspace173(module);}
}
