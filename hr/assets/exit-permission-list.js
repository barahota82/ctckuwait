'use strict';
const permissionStates196=new Map(),permissionPages196=new Map();let permissionOpenTicket196=0,permissionOwner196=null;
permissionGo183=async function(id){
 const root=document.querySelector('.permission-workspace196');
 if(view==='exit_permissions183'&&root?.openPermission196)return root.openPermission196(id);
 permissionSelected183=Number(id);await navigate('exit_permissions183');
};
permissionsScreen183=async function(){
 permissionReset183();const epoch=navigationEpoch32,identity=screenIdentity38(),personal=boot.access_mode==='personal';
 const state=permissionStates196.get(identity)||{employee:0,query:'',status:'all'};permissionStates196.set(identity,state);
 if(permissionOwner196!==null&&permissionOwner196!==identity)permissionSelected183=0;permissionOwner196=identity;const selectedPermission=permissionSelected183;permissionCurrent183=null;
 $('#portalTabs32').hidden=true;
 const fresh=document.createElement('section');fresh.className='card employee-editor134 record-workspace173 record-table187 permission-workspace196';
 fresh.innerHTML='<header class="employee-editor-identity134"><div><strong>'+esc(t182('أذونات الخروج','Exit permissions'))+'</strong><span data-permission-person196></span></div><div class="employee-editor-top-actions136">'+(personal&&personalPermission34('request','create')?'<button type="button" class="primary create-action32" id="requestPermission183"><span class="create-icon32" aria-hidden="true">+</span><span>'+esc(t182('طلب إذن خروج','Request exit permission'))+'</span></button>':'')+(!personal?'<button type="button" data-permission-find196 disabled>'+esc(t182('بحث عن موظف','Find employee'))+'</button>':'')+'</div></header><div class="record-filters173"><label>'+esc(t182('البحث في السجلات','Search records'))+'<input type="search" data-remote32="1" id="permissionSearch183" value="'+esc(state.query)+'"></label><label>'+esc(t182('الحالة','Status'))+'<select id="permissionFilter183">'+[['all',['الكل','All']],...Object.entries(permissionLabels182)].map(([v,n])=>'<option value="'+v+'" '+(state.status===v?'selected':'')+'>'+esc(t182(...n))+'</option>').join('')+'</select></label></div><div class="request-selection185"><button type="button" data-permission-all196 hidden>'+esc(t182('عرض كل الموظفين','Show all employees'))+'</button><span data-permission-count196></span></div><div id="permissionPanel183"></div><div id="permissionList183" class="employee-editor-history134">'+loadingMarkup()+'</div>';
 const host=mountWorkspace196(fresh,'exit_permissions183'),result=host.querySelector('#permissionList183'),panel=host.querySelector('#permissionPanel183'),find=host.querySelector('[data-permission-find196]'),all=host.querySelector('[data-permission-all196]'),query=host.querySelector('#permissionSearch183');
 const current=()=>host.isConnected&&epoch===navigationEpoch32&&identity===screenIdentity38();let sequence=0,people=[],timer=null;
 const paginate=recordPager189(host,()=>void load(),permissionPages196),feedback=readFeedback196(host,result,()=>load());
 const filters=()=>({family:'permission',route:'exit_permissions183',employee:personal?boot.user.id:state.employee,query:state.query,status:state.status});
 const draw=()=>{
  const chosen=people.find(p=>sameId195(p.id,state.employee));host.querySelector('[data-permission-person196]').innerHTML=chosen?employee193(chosen):esc(t182(personal?'أذوناتي':'كل الموظفين في نطاقك',personal?'My permissions':'All employees in your scope'));all.hidden=personal||!state.employee;
  const columns=[...(personal?[]:[employeeColumn193]),['date','التاريخ','Date',p=>date193(p.starts_at)],['time','وقت الخروج','Exit time',p=>'<bdi>'+esc(p.starts_at.slice(11,16))+'</bdi>'],['time','موعد العودة','Return by',p=>'<bdi>'+esc(p.ends_at.slice(11,16))+'</bdi>'+(p.starts_at.slice(0,10)!==p.ends_at.slice(0,10)?'<small class="record-meta191">'+date193(p.ends_at)+'</small>':'')],['minutes','المدة','Duration',p=>esc(p.allowed_minutes)+' '+esc(t182('دقيقة','minutes'))],['status','الحالة','Status',permissionBadge183],['detail','الإجراءات','Actions',p=>'<div class="permission-actions196"><button type="button" data-permission-details183="'+p.id+'">'+esc(t182('التفاصيل','Details'))+'</button><button type="button" data-permission-open183="'+p.id+'">'+esc(permissionReview183(p)&&p.state==='pending'?t182('مراجعة الطلب','Review request'):t182('فتح الإذن','Open permission'))+'</button></div>']];
  result.innerHTML=permissionRows183.length?table193(columns,permissionRows183):empty(t182('لا توجد أذونات بهذه التصفية.','No permissions match this filter.'));
  result.querySelectorAll('[data-permission-details183]').forEach(b=>b.onclick=()=>permissionOpen182(Number(b.dataset.permissionDetails183)));
  result.querySelectorAll('[data-permission-open183]').forEach(b=>b.onclick=()=>host.openPermission196(Number(b.dataset.permissionOpen183)));
  applyInterfaceLanguage();
 };
 async function load(){
  if(!current())return;clearTimeout(timer);const ticket=++sequence,f=filters(),stamp=JSON.stringify(f),page=paginate.params(identity,stamp);feedback.busy(true);paginate.busy(true);if(find)find.disabled=true;all.disabled=true;
  try{const data=await api('permissions182',null,{page195:JSON.stringify({...page,filters:f})});if(!current()||ticket!==sequence||stamp!==JSON.stringify(filters()))return;
   if(!Array.isArray(data.rows195)||!data.page195)throw Error(t182('تعذر قراءة رد الخادم؛ أعد تحميل الصفحة.','Could not read the server response; reload the page.'));
   people=data.people195||[];permissionRows183=paginate(data.rows195,identity,stamp,data.page195);host.querySelector('[data-permission-count196]').textContent=data.page195.total+' / '+data.page195.unfiltered;feedback.success();draw();
  }catch(e){if(current()&&ticket===sequence&&stamp===JSON.stringify(filters())&&!e.stale32)feedback.error(e);}finally{if(current()&&ticket===sequence){feedback.busy(false);paginate.busy(false);if(find)find.disabled=!people.length;all.disabled=false;}}
 }
 host.openPermission196=async id=>{
  const ticket=++permissionOpenTicket196;permissionReset183();permissionSelected183=Number(id);panel.innerHTML=loadingMarkup();panel.scrollIntoView?.({block:'start'});
  try{const p=await api('permission_get182',null,{id});if(!current()||ticket!==permissionOpenTicket196)return;permissionPanel183(p);applyInterfaceLanguage();}
  catch(e){if(!current()||ticket!==permissionOpenTicket196||e.stale32)return;panel.innerHTML='<p role="alert">'+esc(e.message)+'</p><button type="button" data-permission-retry196>'+esc(t182('إعادة المحاولة','Retry'))+'</button>';panel.querySelector('button').onclick=()=>host.openPermission196(id);}
 };
 const choose=id=>{state.employee=id;void load();};if(find)find.onclick=()=>recordFind173(people,state.employee,choose);all.onclick=()=>choose(0);
 query.oninput=()=>{state.query=query.value;clearTimeout(timer);timer=setTimeout(()=>void load(),200);};host.querySelector('#permissionFilter183').onchange=e=>{state.status=e.target.value;void load();};
 host.querySelector('#requestPermission183')?.addEventListener('click',()=>{permissionOpenTicket196++;permissionSelected183=0;permissionCurrent183=null;permissionRequestForm183();panel.scrollIntoView?.({block:'start'});applyInterfaceLanguage();});
 await Promise.all([load(),selectedPermission?host.openPermission196(selectedPermission):Promise.resolve()]);
};
// Exit permissions own their approved workspace, without legacy toolbar reparenting.
for(const name of ['prepareScreenChrome61','prepareHeader79','mountRecordFilters75','splitHeader77','mountOperationalChrome68']){const original=window[name];if(original)window[name]=function(...args){if(view!=='exit_permissions183')return original.apply(this,args);};}
