'use strict';
// Presentation and paging only. Original readers, ledger math and editors remain owners of data.
const financeStates190=new Map(),financePages190=new Map();let financeOwner190='';
const financeTarget190=(key=view)=>key==='v28_debt'||financeShellKeys101.has(key)||payrollEntryKeys127.has(key);
function financeState190(key,section=''){
 const owner=screenIdentity38();if(owner!==financeOwner190){financeOwner190=owner;financeStates190.clear();financePages190.clear();}
 const id=key+'|'+section;if(!financeStates190.has(id))financeStates190.set(id,{id:0,query:''});return financeStates190.get(id);
}
function financeTabs190(key,section){
 if(key==='v28_debt')return [];
 if(payrollEntryKeys127.has(key))return [['payroll_entry_click126','salary','رواتب كليك'],['payroll_entry_click126','sales','مبيعات كليك'],['payroll_entry_click126','collection','تحصيل كليك'],['payroll_entry_afaq126','salary','رواتب آفاق']].filter(([route])=>screenAllowed(route));
 return allowedTabs32(hubFor32(key)).map(route=>[route,'',tabTitle32(route)]);
}
function financeWorkspace190(key,host,render,section='',stage=document.getElementById('page')){
 let remoteLoad195=null,remoteMeta195=null,detail195='';const redraw195=()=>remoteLoad195&&!detail195?void remoteLoad195():render();
 const page=document.getElementById('page'),state=financeState190(key,section),tabs=financeTabs190(key,section),title=hubTitle32(hubFor32(key));
 const controls=[...stage.querySelectorAll('.toolbar > label')],add=stage.querySelector('.page-head button'),oldSearch=stage.querySelector('input[type=search]');
 let root=document.createElement('section');root.className='card employee-editor134 finance-workspace190';
 root.innerHTML='<header class="employee-editor-identity134"><div class="finance-heading190"><strong>'+esc(uiText(title))+'</strong><span data-finance-person190></span></div><div class="employee-editor-top-actions136"><button type="button" data-finance-find190 disabled>'+esc(uiText('بحث عن موظف'))+'</button></div></header>'+(tabs.length?'<nav class="employee-editor-sections134 finance-tabs190" aria-label="'+esc(uiText(title))+'">'+tabs.map(([route,sub,label])=>'<button type="button" data-finance-tab190="'+route+'" data-finance-section190="'+sub+'" aria-current="'+(route===key&&sub===section?'location':'false')+'">'+esc(uiText(label))+'</button>').join('')+'</nav>':'')+'<div class="finance-filters190"></div><div class="finance-selection190"><button type="button" data-finance-all190 hidden>'+esc(uiText('عرض كل الموظفين'))+'</button></div>';
 const filters=root.querySelector('.finance-filters190');
 let query=oldSearch;if(!query){const label=document.createElement('label');label.innerHTML=esc(uiText('البحث في السجلات'))+'<input type="search" data-remote32="1" placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'">';query=label.querySelector('input');filters.append(label);}else filters.append(query.closest('label'));
 query.value=state.query;query.dataset.remote32='1';query.oninput=()=>{state.query=query.value;host._resetDetail190?.();detail195='';clearTimeout(query.timer195);query.timer195=setTimeout(redraw195,200);};
 controls.filter(label=>!label.contains(query)).forEach(label=>filters.append(label));
 const refresh=document.createElement('button');refresh.type='button';refresh.dataset.financeRefresh190='';refresh.textContent=uiText('تحديث');filters.append(refresh);
 filters.classList.toggle('finance-month190',!!filters.querySelector('input[type=month]'));
 if(add)root.querySelector('.employee-editor-top-actions136').prepend(add);
 host.classList.remove('card');host.classList.add('finance-results190');root.append(host);root=mountWorkspace196(root,key);
 const feedback=readFeedback196(root,host,()=>remoteLoad195?.());let ready196=false;
 const paginate=recordPager189(root,redraw195,financePages190),find=root.querySelector('[data-finance-find190]'),all=root.querySelector('[data-finance-all190]');let people=[];
 const company=financeNoEmployee118(key),personal=personalNavigation50();find.hidden=company||personal;
 const choose=id=>{state.id=Number(id);host._resetDetail190?.();detail195='';redraw195();};all.onclick=()=>choose(0);find.onclick=()=>recordFind173(people,state.id,choose);
 const sync=()=>{const person=people.find(p=>p.id===state.id);all.hidden=company||personal||!state.id;root.querySelector('[data-finance-person190]').innerHTML=person?employeeNameMarkup143(person.id,person.name,person)+' <bdi>'+esc(person.employee_code)+'</bdi>':company?'':esc(uiText('كل الموظفين في نطاقك'));};
 root.querySelectorAll('[data-finance-tab190]').forEach(button=>button.onclick=()=>{const route=button.dataset.financeTab190,sub=button.dataset.financeSection190;if(route===key&&sub===section)return;if(sub)payrollEntrySections127.set(route,sub);navigate(route).catch(e=>{if(!e.stale32)toast(e.message);});});
 refresh.onclick=async()=>{const field=filters.querySelector('input[type=date],input[type=month]');if(refresh.disabled||!field?._periodRefresh98)return;refresh.disabled=true;try{await field._periodRefresh98();}catch(e){toast(e.message);}finally{if(refresh.isConnected)refresh.disabled=false;}};
 const selection=rows=>rows.filter(row=>!state.id||Number(row.user_id??row.id)===state.id);
 const matching=rows=>{sync();if(remoteMeta195)return rows;const q=normalize32(state.query);return rows.filter(row=>!q||normalize32([row.code,row.employee_code,row.name,row.name_en,row.company_name,row.reference,row.reason,row.name95,row.date,...(row.movements||[]).flatMap(m=>[m.name95,m.reason,m.reference])].join(' ')).includes(q));};
 return {root,state,query,refresh,selection,matching,
  useRemote(load){remoteLoad195=load;},
  reload(){return remoteLoad195?.();},
  remote(data){remoteMeta195=data.page195||null;ready196=true;feedback.success();},
  error(error){feedback.error(error);if(add)add.disabled=true;},
  matchesPage(params){return params.page195===this.pageParams().page195;},
  busy(value){feedback.busy(value);find.disabled=value||!people.length;all.disabled=value;refresh.disabled=value;paginate.busy(value);if(add)add.disabled=value||!ready196||feedback.failed;},
  pageParams(){const stamp=JSON.stringify([state.id,state.query,...[...filters.querySelectorAll('input[type=date],input[type=month]')].map(n=>n.value)]);return {page195:JSON.stringify({...paginate.params(key+'|'+section+'|',stamp),filters:{family:'finance',route:key,employee:state.id,query:state.query}})};},
  people(rows){people=employeeOrder40(rows.filter(r=>Number(r.user_id??r.id)).map(r=>({...r,id:Number(r.user_id??r.id),employee_code:r.employee_code||r.code||'',profile:{...r.profile,name_en:r.name_en||r.profile?.name_en}})));if(state.id&&!people.some(p=>p.id===state.id))state.id=0;find.disabled=!people.length;sync();},
  page(rows,detail=''){detail195=detail;return paginate(rows,key+'|'+section+'|'+detail,JSON.stringify([state.id,state.query,...[...filters.querySelectorAll('input[type=date],input[type=month]')].map(n=>n.value)]),detail?null:remoteMeta195);}
 };
}

// These screens own one white workspace. Keep legacy header reparenting out of it.
const financeSync190=syncPortalTabs32;
syncPortalTabs32=function(key=view){financeSync190(key);if(financeTarget190(key)){const host=document.getElementById('portalTabs32');host.hidden=true;host.replaceChildren();}};
const financeChrome190=prepareScreenChrome61;
prepareScreenChrome61=function(key){if(!financeTarget190(key))return financeChrome190(key);};
const financeHeader190=prepareHeader79;
prepareHeader79=function(key){if(!financeTarget190(key))return financeHeader190(key);};
const financeFilter190=mountRecordFilters75;
mountRecordFilters75=function(){if(!financeTarget190())return financeFilter190();};
const financeSplit190=splitHeader77;
splitHeader77=function(){if(!financeTarget190())return financeSplit190();};
