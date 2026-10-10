'use strict';
const $=s=>document.querySelector(s);
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const labels={attendance:'حضور',checkout:'انصراف',permission_exit:'إذن خروج',permission_return:'عودة',approved:'معتمد',pending:'بانتظار المراجعة',rejected:'مرفوض',revision:'مطلوب تعديل',active:'نشط',closed:'مغلق',working:'داخل الدوام',away:'خارج بإذن',none:'لم يسجل حضورًا',draft:'مسودة'};
let boot,users=[],view='dashboard',stream=null,photo=null,photoAt=0,captureTicket='',geo=null,geoWatch=null,trackingTimer=null,pending=null,selectedEvent='attendance',serverOffset=0;
const icons={permissions:'♧',launch:'◉',publication:'◧',companies:'▦',import:'⇧',bank:'▧',leave_overview:'◇',dashboard:'◈',attendance:'◷',users:'♙',notifications:'◉',settings:'⚙',policies:'▤',report:'▥',tracking:'⌖',payroll:'▧',audit:'≡'};
function toast(t){$('#toast').textContent=boot?.localization?.dictionary?.[t]||t;$('#toast').style.display='block';setTimeout(()=>$('#toast').style.display='none',6000);}
async function api(action,data=null,params={}){
 const url='api.php?'+new URLSearchParams({action,...params});
 const options={credentials:'same-origin',headers:{}};
 if(data!==null){options.method='POST';options.headers['X-CSRF-Token']=boot?.csrf??'';if(data instanceof FormData)options.body=data;else{options.headers['Content-Type']='application/json';options.body=JSON.stringify(data);}}
 const res=await fetch(url,options);let body;
 try{body=await res.json();}catch(e){throw Error('تعذر قراءة رد الخادم؛ راجع PHP وإعدادات الاستضافة.');}
 if(!res.ok||body.error)throw Error(body.error||'تعذر الاتصال.');
 return body.data;
}
const dateNow=()=>new Intl.DateTimeFormat('en-CA',{timeZone:boot.company.timezone,year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(Date.now()+serverOffset));
function localDisplay(utc){if(!utc)return '—';const d=new Date(utc.replace(' ','T')+'Z');return new Intl.DateTimeFormat('ar-KW',{timeZone:boot.company.timezone,dateStyle:'medium',timeStyle:'short'}).format(d);}
function badge(s){return '<span class="badge '+esc(s)+'">'+esc(labels[s]??s)+'</span>';}
function companyLogo(){return boot.company.logo_id?'<img class="brand-logo" src="file.php?id='+Number(boot.company.logo_id)+'" alt="شعار الشركة">':'<div class="brand-mark">◆</div>';}
function closeCamera(){if(stream)stream.getTracks().forEach(t=>t.stop());stream=null;}
function stopLocation(){if(geoWatch!==null)navigator.geolocation.clearWatch(geoWatch);geoWatch=null;if(trackingTimer)clearInterval(trackingTimer);trackingTimer=null;}
async function loadBoot(){boot=await api('bootstrap');serverOffset=new Date(boot.server_time.replace(' ','T')+'Z').getTime()-Date.now();document.title=(boot.company.public_site_title?.ar||boot.company.company_name)+' — شؤون الموظفين';}
async function start(){try{await loadBoot();if(!boot.user)return login();users=await api('users');shell();await navigate(view);}catch(e){$('#app').innerHTML='<main class="install-card"><h1>تعذر فتح النظام</h1><p>'+esc(e.message)+'</p></main>';}}
function login(){
 stopLocation();closeCamera();document.body.className='auth-page';
 $('#app').innerHTML='<main class="login-card">'+companyLogo()+'<p class="eyebrow">'+esc(boot.company.company_name_en)+'</p><h1>'+esc(boot.company.company_name)+'</h1><p class="muted">مساحة واحدة لكل ما يخص عملك.</p><div class="gold-rule"></div><form id="loginForm"><label>رقم الموظف / اسم الدخول<input name="username" autocomplete="username" required></label><label>كلمة المرور<input name="password" type="password" autocomplete="current-password" required></label><button class="primary submit-wide">دخول مساحة العمل</button></form><p class="tiny muted">الوقت المسجل من الخادم، ولا يعتمد على ساعة الهاتف.</p></main>';
 $('#loginForm').onsubmit=async e=>{e.preventDefault();try{await api('login',Object.fromEntries(new FormData(e.target)));await start();}catch(e){toast(e.message);}};
}
function teamPermission(screen,action='view'){return boot.user.role==='admin'||(boot.user.role==='manager'&&(boot.permissions?.[screen]??[]).includes('view')&&(boot.permissions?.[screen]??[]).includes(action));}
function personalScreen(key){return ['dashboard','attendance','notifications','report','payroll','users','leave','sick','external','request','custody','document','discipline','evaluation','employment','proof'].includes(key);}
function screenAllowed(key){return personalScreen(key)||teamPermission(key)||(boot.user.role==='admin'&&['permissions','launch','publication','companies','bank','import','policies','audit','settings','cms','public_settings','public_messages','offers','customer_departments','customer_ratings','languages'].includes(key));}
function shell(){
 document.body.className='role-'+boot.user.role;
 const admin=boot.user.role==='admin',manager=boot.user.role==='manager';
 const links=[['dashboard',teamPermission('dashboard')?'نظرة عامة':'ملخصي'],['attendance','تسجيل الحركة'],['notifications','الإشعارات'],['report',teamPermission('report')?'سجل الدوام':'سجل دوامي'],['payroll',admin?'كشوف الرواتب':'كشف راتبي'],['users',teamPermission('users')?'الموظفون':'ملفي']];
 const menu=links.map(([key,title])=>navItem(key,title)).join('')+'<div class="nav-section">شؤون الموظفين</div>'+Object.entries(boot.modules).filter(([k,m])=>screenAllowed(k)).map(([k,m])=>navItem(k,m.label)).join('')+
 (teamPermission('tracking')?'<div class="nav-section">متابعة الفريق</div>'+navItem('tracking','المتابعة الميدانية'):'')+(teamPermission('leave_overview')?navItem('leave_overview','أرصدة الإجازات'):'')+
 (admin?'<div class="nav-section">إدارة النظام</div>'+navItem('companies','الشركات')+navItem('permissions','الصلاحيات')+navItem('launch','بدء التشغيل')+navItem('bank','تحويلات البنك')+navItem('import','البيانات القديمة')+navItem('policies','الدوام والسياسات')+navItem('audit','سجل التغييرات')+navItem('settings','إعدادات الإدارة')+'<div class="nav-section">الموقع التعريفي</div>'+navItem('publication','ظهور صفحات الموقع')+navItem('cms','المحتوى والكروت')+navItem('offers','العروض')+navItem('public_settings','هوية الموقع والتواصل')+navItem('customer_departments','أقسام خدمة العملاء')+navItem('customer_ratings','تقييمات العملاء')+navItem('languages','اللغات والترجمة'):'');
 const caseMenu=teamPermission('customer_cases')?navItem('customer_cases','حالات العملاء'):'';
 const quick=[['dashboard','ملخصي'],['attendance','التسجيل'],['leave','طلباتي'],['notifications','الإشعارات']];
 $('#app').innerHTML='<div class="shell"><aside class="sidebar"><div class="brand">'+companyLogo()+'<h2>'+esc(boot.company.company_name)+'</h2><p>'+esc(boot.company.company_name_en)+'</p></div>'+menu+caseMenu+'<div class="sidebar-foot"><span class="live-dot"></span> مساحة عمل خاصة</div></aside><main class="workspace"><header class="topbar"><div class="topbar-right"><button class="mobile-menu" data-action="menu" aria-label="فتح القائمة">☰</button><div><b>مساحة شؤون الموظفين</b><small class="time-text">'+esc(localDisplay(boot.server_time))+' · '+esc(boot.company.timezone)+'</small></div></div><div class="topbar-right account-tools">'+languagePicker()+'<button data-action="password">كلمة المرور</button><div class="avatar">'+esc(boot.user.name.slice(0,1))+'</div><div class="account-name"><b>'+esc(boot.user.name)+'</b><small>'+({admin:'المشرف العام',manager:'مدير قسم',employee:'موظف'}[boot.user.role])+'</small></div><button data-action="logout">خروج</button></div></header><div class="page" id="page"></div></main><nav class="mobile-bottom" aria-label="التنقل السريع">'+quick.map(([k,t])=>navItem(k,t)).join('')+'</nav></div>';
 $('#app').onclick=async e=>{const nav=e.target.closest('[data-view]');if(nav){await navigate(nav.dataset.view);$('.sidebar').classList.remove('open');return;}const action=e.target.closest('[data-action]')?.dataset.action;if(action==='menu')$('.sidebar').classList.toggle('open');if(action==='logout'){await api('logout',{});await start();}if(action==='password')passwordModal();};
 if($('#interfaceLanguage'))$('#interfaceLanguage').onchange=async e=>{try{await api('language_select',{language:e.target.value});await loadBoot();shell();await navigate(view);}catch(err){toast(err.message);}};installInterfaceLanguage();
 if(boot.user.staff_type==='rep'&&boot.settings.tracking_enabled&&boot.operation?.attendance_enabled)watchLocation();
}
function navItem(key,title){return '<button class="nav-item" data-view="'+key+'"><span>'+esc(icons[key]??'▫')+'</span><span>'+esc(title)+'</span></button>';}
function head(title,subtitle='',actions=''){const loc=boot?.localization;const note=loc&&loc.language!=='ar'&&loc.translated_count<loc.source_count?'<p class="translation-fallback">Translation pending · بعض النصوص تُعرض بالعربية حتى اكتمال تجهيز الترجمة.</p>':'';return note+'<div class="page-head"><div><p class="eyebrow">PEOPLE · OPERATIONS</p><h1>'+esc(title)+'</h1><p>'+esc(subtitle)+'</p></div>'+actions+'</div>';}
function empty(t='لا توجد سجلات بعد'){return '<div class="empty">'+esc(t)+'</div>';}
function table(headers,rows){return '<div class="table-wrap"><table><thead><tr>'+headers.map(h=>'<th>'+esc(h)+'</th>').join('')+'</tr></thead><tbody>'+rows.map(r=>'<tr>'+r.map((c,i)=>'<td data-label="'+esc(headers[i]??'')+'">'+c+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';}
function userOptions(selected=boot.user.id,all=false){return (all?'<option value="0">كل الموظفين / السياسة العامة</option>':'')+users.map(u=>'<option value="'+u.id+'" '+(Number(selected)===Number(u.id)?'selected':'')+'>'+esc(u.name)+'</option>').join('');}
async function navigate(key){
 closeCamera();if(!screenAllowed(key))key='dashboard';view=key;
 document.querySelectorAll('.nav-item').forEach(n=>n.classList.toggle('active',n.dataset.view===key));
 $('#page').innerHTML='<div class="loading">جارٍ تحميل الشاشة…</div>';
 try{
  const paused=(key==='report'&&!boot.operation?.reports_enabled)||(['payroll','bank'].includes(key)&&!boot.operation?.payroll_enabled);
  if(paused){$('#page').innerHTML=head(key==='report'?'سجل الدوام':key==='bank'?'تحويلات البنك':'الرواتب','هذه الوحدة جاهزة للمراجعة وتنتظر اعتماد بداية التشغيل.')+'<section class="card attendance-card"><div class="empty-state-icon">'+(icons[key]??'◷')+'</div><h2>التشغيل مؤجل</h2><p class="muted">نجهز إدارة الموظفين أولًا. البيانات القديمة والأرصدة والسياسات تراجع قبل فتح هذه الوحدة.</p><button class="primary" data-view="'+(boot.user.role==='admin'?'launch':'dashboard')+'">'+(boot.user.role==='admin'?'إعداد بداية التشغيل':'الرجوع إلى ملخصي')+'</button></section>';return;}
  if(key==='customer_cases')await customerCasesView();
  else if(key==='customer_departments')await departmentsView();
  else if(key==='customer_ratings')await ratingsView();
  else if(key==='languages')await languagesView();
  else if(key==='permissions')await permissionsView();
  else if(key==='launch')launchView();
  else if(key==='publication')await publicationView();
  else if(key==='companies')await companiesView();
  else if(key==='bank')await bankView();
  else if(key==='import')await importView();
  else if(key==='leave_overview')await leaveOverview();
  else if(key==='offers')await offersView();
  else if(key==='cms')await cmsView();
  else if(key==='public_settings')await publicSettingsView();
  else if(key==='public_messages')await publicMessagesView();
  else if(key==='dashboard')await dashboard();
  else if(key==='attendance')await attendance();
  else if(key==='users')await employees();
  else if(key==='settings')settingsView();
  else if(key==='policies')await policies();
  else if(key==='notifications')await notifications();
  else if(key==='report')await reportView();
  else if(key==='tracking')await trackingView();
  else if(key==='audit')await auditView();
  else if(key==='payroll')await payrollView();
  else if(boot.modules[key])await records(key);
  applyInterfaceLanguage();
 }catch(e){$('#page').innerHTML=head('تعذر عرض الشاشة')+'<div class="error">'+esc(e.message)+'</div>';}
}
function stat(label,value,note,icon){return '<div class="card stat"><small>'+esc(label)+'</small><span class="accent">'+icon+'</span><strong>'+esc(value)+'</strong><p>'+esc(note)+'</p></div>';}
async function dashboard(){
 const notices=await api('notifications'),today=dateNow(),overview=await api('dashboard'),team=teamPermission('dashboard');
 let pendingCount=0;const latest=[];
 for(const key of ['leave','sick','external','request']){const rs=await api('list',null,{module:key});pendingCount+=rs.filter(r=>r.status==='pending').length;latest.push(...rs.slice(0,3));}
 latest.sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at)));
 let sick='مخفي';try{sick=(await api('sick_summary')).used;}catch(e){}
 const running=boot.operation?.attendance_enabled&&boot.operation?.history_approved;
 const teamRows=overview.staff;
 const cards=team?stat('الموظفون في نطاقك',teamRows.length,'الحسابات النشطة فقط','♙')+stat('طلبات قيد المراجعة',pendingCount,'حسب صلاحيات الشاشات','▤')+stat('إشعارات غير مقروءة',notices.filter(n=>!n.read_at).length,'إشعارات حسابك','◉')+stat('داخل الدوام',running?teamRows.filter(r=>r.state==='working').length:'مؤجل','حسب التسجيلات الحالية','◷'):stat('حالة يومك',running?(labels[boot.state]??boot.state):'قيد التجهيز','اليوم '+today,'◷')+stat('طلباتي المعلقة',pendingCount,'طلباتك أنت فقط','▤')+stat('إشعاراتي الجديدة',notices.filter(n=>!n.read_at).length,'القرارات والتعاميم','◉')+stat('مرضياتي هذا العام',sick,'العدد المستخدم عند السماح بعرضه','◇');
 $('#page').innerHTML='<section class="welcome-panel"><div><p class="eyebrow">'+esc(boot.company.company_name_en)+'</p><h1>أهلًا، '+esc(boot.user.name)+'</h1><p>'+ (team?'نظرة واضحة على فريقك، ضمن الصلاحيات الممنوحة لك.':'هذه مساحتك الخاصة. البيانات المعروضة تخصك أنت فقط.')+'</p><div class="welcome-actions"><button class="gold" data-view="attendance">تسجيل الحركة <span>↗</span></button><button data-view="leave">تقديم طلب إجازة</button></div></div><div class="welcome-date"><span>'+esc(today)+'</span><b>'+ (running?'يومك في مكان واحد':'جاهزين لخطوة البداية')+'</b><small>'+esc(boot.company.timezone)+'</small></div></section>'+
 (!running?'<div class="phase-note"><span>◉</span><div><b>إدارة الموظفين متاحة الآن</b><p>تشغيل الحضور والحسابات مؤجل لحين اعتماد البيانات القديمة وتاريخ البداية.</p></div>'+(boot.user.role==='admin'?'<button data-view="launch">إعداد التشغيل</button>':'')+'</div>':'')+
 '<div class="cards">'+cards+'</div><div class="columns"><section class="card"><div class="section-top"><div><p class="eyebrow">REQUESTS</p><h2>'+ (team?'آخر طلبات نطاقك':'آخر طلباتي')+'</h2></div><button data-view="request">فتح الطلبات ↗</button></div>'+ (latest.length?table(['الموظف','الطلب','الفترة','الحالة'],latest.slice(0,6).map(r=>[esc(r.employee_name),esc(r.title),esc(r.date_from??'—'),badge(r.status)])):empty('لا توجد طلبات حتى الآن.'))+'</section><section class="card quick-card"><p class="eyebrow">YOUR WORKSPACE</p><h2>وصول سريع</h2>'+[['leave','طلب إجازة','قدم الطلب وتابع رد الإدارة'],['notifications','الإشعارات','قرارات وتعاميم في مكان واحد'],['users',teamPermission('users')?'ملفات الموظفين':'ملفي','البيانات الوظيفية والحساب'],...(boot.user.role==='admin'?[['publication','ظهور الموقع','تحكم في الصفحات المنشورة']]:[])].map(([k,t,n])=>'<button class="quick-link" data-view="'+k+'"><span class="quick-icon">'+(icons[k]??'◇')+'</span><span><b>'+t+'</b><small>'+n+'</small></span><i>↗</i></button>').join('')+'</section></div>'+
 (running?'<section class="card space-top"><div class="section-top"><h2>'+ (team?'دوام الفريق اليوم':'دوامي اليوم')+'</h2><span class="muted">'+esc(today)+'</span></div>'+table(['الموظف','الحالة','التأخير','الملاحظات'],teamRows.map(r=>[esc(r.name),badge(r.state),r.late+' دقيقة',esc(r.absence?'غياب':r.unclosed?'إذن غير مغلق':r.missing?'بصمة ناقصة':'—')]))+'</section>':'');
}
async function attendance(){
 await loadBoot();if(!boot.operation?.attendance_enabled||!boot.operation?.history_approved){$('#page').innerHTML=head('تسجيل الحركة','التشغيل لم يبدأ بعد.')+'<section class="card attendance-card"><div class="empty-state-icon">◷</div><h2>الحضور قيد التجهيز</h2><p class="muted">الإدارة تراجع البيانات القديمة وتحدد تاريخ بداية التسجيل. ملفك وطلباتك وإشعاراتك متاحة.</p><button class="primary" data-view="dashboard">الرجوع إلى ملخصي</button></section>';return;}const state=boot.state,p=boot.policy.values;
 const available={none:['attendance','checkout'],working:['permission_exit','checkout'],away:['permission_return'],closed:[]}[state]??[];
 selectedEvent=available[0]??'attendance';photo=null;photoAt=0;
 $('#page').innerHTML=head('تسجيل الحركة','وقت الاستلام من الخادم. الموقع يتحدث تلقائيًا دون زر منفصل.')+'<section class="card attendance-card"><div class="status-line"><span>حالتك الحالية</span>'+badge(state)+'</div><div id="geoStatus" class="status-line">جارٍ تحديد الموقع…</div>'+
 (!p.configured?'<div class="error">الإدارة لم تعتمد جدول دوامك بعد.</div>':'')+
 '<div class="event-actions">'+Object.keys(labels).filter(k=>['attendance','checkout','permission_exit','permission_return'].includes(k)).map(k=>'<button class="'+(selectedEvent===k?'selected':'')+'" data-event="'+k+'" '+(!available.includes(k)?'disabled':'')+'>'+labels[k]+'</button>').join('')+'</div>'+
 '<div class="camera-box"><video id="video" autoplay playsinline muted></video><img id="photoPreview" hidden alt="الصورة الحالية"></div><div class="camera-actions"><button id="frontCamera">الأمامية</button><button id="backCamera">الخلفية</button><button id="takePhoto" class="gold">التقاط الصورة</button></div><p id="cameraStatus" class="tiny muted">اختر الكاميرا والتقط صورة حديثة.</p>'+
 (boot.user.staff_type==='rep'&&p.rep_evidence?'<label>صورة السند كدليل فقط<input id="evidence" type="file" accept="image/*" capture="environment"></label>':'')+
 '<button id="sendEvent" class="primary submit-wide" '+(!available.length?'disabled':'')+'>تسجيل '+labels[selectedEvent]+'</button><p id="saveStatus" class="tiny"></p><p class="tiny muted">الموقع يخضع لدقة الجهاز؛ لا تُرسل الحركة إلا بعد تأكيد حفظها. المتابعة أثناء الدوام فقط.</p></section>';
 document.querySelectorAll('[data-event]').forEach(btn=>btn.onclick=()=>{selectedEvent=btn.dataset.event;document.querySelectorAll('[data-event]').forEach(b=>b.classList.toggle('selected',b===btn));$('#sendEvent').textContent='تسجيل '+labels[selectedEvent];});
 $('#frontCamera').onclick=()=>openCamera('user');$('#backCamera').onclick=()=>openCamera('environment');$('#takePhoto').onclick=capture;
 $('#sendEvent').onclick=sendEvent;watchLocation();
}
function watchLocation(){
 if(geoWatch!==null)return updateGeoStatus();
 if(!navigator.geolocation){geoError('المتصفح لا يدعم الموقع');return;}
 geoWatch=navigator.geolocation.watchPosition(pos=>{geo={latitude:pos.coords.latitude,longitude:pos.coords.longitude,accuracy:pos.coords.accuracy,location_at:Math.floor((Date.now()+serverOffset)/1000)};updateGeoStatus();},err=>geoError(err.message),{enableHighAccuracy:true,maximumAge:0,timeout:25000});
 if(boot.user.staff_type==='rep'&&boot.settings.tracking_enabled){
  trackingTimer=setInterval(()=>sendTracking(),Math.max(60,Number(boot.settings.tracking_interval))*1000);
 }
}
function updateGeoStatus(){const node=$('#geoStatus');if(node)node.textContent=geo?'الموقع جاهز · الدقة '+Math.round(geo.accuracy)+' متر':'جارٍ تحديد الموقع…';}
function geoError(message){geo=null;const node=$('#geoStatus');if(node)node.textContent='تعذر تحديد الموقع: '+message;}
async function freshLocation(){
 return new Promise((resolve,reject)=>navigator.geolocation.getCurrentPosition(pos=>{geo={latitude:pos.coords.latitude,longitude:pos.coords.longitude,accuracy:pos.coords.accuracy,location_at:Math.floor((Date.now()+serverOffset)/1000)};updateGeoStatus();resolve(geo);},e=>reject(Error('تعذر تحديد الموقع: '+e.message)),{enableHighAccuracy:true,maximumAge:0,timeout:25000}));
}
async function openCamera(direction){
 try{closeCamera();stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:direction}},audio:false});if(!$('#video')){closeCamera();return;}$('#video').srcObject=stream;$('#video').hidden=false;$('#photoPreview').hidden=true;await $('#video').play();$('#cameraStatus').textContent='الكاميرا تعمل؛ التقط صورة.';}catch(e){toast('تعذر فتح الكاميرا: '+e.message);}
}
async function capture(){
 const v=$('#video');if(!stream||!v.videoWidth)return toast('افتح الكاميرا وانتظر ظهور الصورة.');
 let ticket;try{ticket=await api('capture_ticket',{});captureTicket=ticket.ticket;}catch(e){return toast(e.message);}
 const c=document.createElement('canvas'),scale=Math.min(1,1200/Math.max(v.videoWidth,v.videoHeight));c.width=v.videoWidth*scale;c.height=v.videoHeight*scale;c.getContext('2d').drawImage(v,0,0,c.width,c.height);
 photo=await new Promise(resolve=>c.toBlob(resolve,'image/jpeg',.82));photoAt=Math.floor(new Date(ticket.server_time.replace(' ','T')+'Z').getTime()/1000);
 $('#photoPreview').src=c.toDataURL('image/jpeg',.82);$('#photoPreview').hidden=false;v.hidden=true;$('#cameraStatus').textContent='تم التقاط صورة حديثة.';closeCamera();
}
async function sendEvent(){
 const btn=$('#sendEvent');btn.disabled=true;
 try{
  if(!pending){
   const p=boot.policy.values,required=boot.user.staff_type==='rep'?p.rep_photo:p.office_photo;
   if(required&&!photo)throw Error('التقط صورة أولًا.');
   const loc=await freshLocation();pending={id:crypto.randomUUID(),photo,photoAt,captureTicket,event:selectedEvent,loc,evidence:$('#evidence')?.files[0]};
  }
  pending.loc=await freshLocation();
  if(photo&&photoAt>pending.photoAt){pending.photo=photo;pending.photoAt=photoAt;pending.captureTicket=captureTicket;}
  const f=new FormData();f.set('request_id',pending.id);f.set('event_type',pending.event);f.set('photo_at',pending.photoAt);f.set('capture_ticket',pending.captureTicket);
  Object.entries(pending.loc).forEach(([k,v])=>f.set(k,v));if(pending.photo)f.set('photo',pending.photo,'camera.jpg');if(pending.evidence)f.set('evidence',pending.evidence);
  $('#saveStatus').textContent='جارٍ الحفظ…';
  const r=await api('event',f);pending=null;photo=null;toast('تم الحفظ · رقم '+r.id+' · '+localDisplay(r.received_at));await attendance();
 }catch(e){$('#saveStatus').textContent=e.message+' — إذا كان انقطاع اتصال، أعد محاولة نفس الطلب قبل إغلاق الصفحة.';btn.textContent=pending?'إعادة محاولة نفس الطلب':'تسجيل '+labels[selectedEvent];}
 finally{if($('#sendEvent'))$('#sendEvent').disabled=false;}
}
async function sendTracking(proofId=0){
 if(!['working','away'].includes(boot.state)||document.hidden||!boot.settings.tracking_enabled)return;
 try{const loc=await freshLocation();await api('tracking',{...loc,proof_id:proofId});if(proofId){toast('تم إرسال إثبات التواجد');await navigate('proof');}}catch(e){if(proofId)toast(e.message);}
}
function modal(title,body,onSubmit=null){
 $('#modalContent').innerHTML='<div class="modal-head"><h2>'+esc(title)+'</h2><button id="closeModal">×</button></div><div class="modal-body">'+body+'</div>';
 $('#closeModal').onclick=()=>$('#modal').close();$('#modal').showModal();
 if(onSubmit&&$('#modalForm'))$('#modalForm').onsubmit=async e=>{e.preventDefault();const btn=e.target.querySelector('button[type="submit"]');if(btn)btn.disabled=true;try{await onSubmit(e.target);$('#modal').close();await navigate(view);}catch(e){toast(e.message);}finally{if(btn)btn.disabled=false;}};
}
function input(name,label,value='',type='text',extra=''){return '<label>'+esc(label)+'<input name="'+name+'" type="'+type+'" value="'+esc(value)+'" '+extra+'></label>';}
function check(name,label,value){return '<label class="check"><input name="'+name+'" type="checkbox" '+(value?'checked':'')+'>'+esc(label)+'</label>';}
function saveButton(){return '<div class="modal-foot"><button type="submit" class="primary">حفظ</button></div>';}
function passwordModal(){modal('تغيير كلمة المرور','<form id="modalForm">'+input('old_password','كلمة المرور الحالية','','password','required')+input('new_password','كلمة المرور الجديدة','','password','required minlength="12"')+saveButton()+'</form>',form=>api('change_password',Object.fromEntries(new FormData(form))));}
async function employees(){
 users=await api('users');if(boot.user.role==='admin')companiesData=await api('companies');
 $('#page').innerHTML=head(teamPermission('users')?'الموظفون':'ملفي',teamPermission('users')?'ملفات الموظفين في نطاق صلاحياتك.':'بياناتك الوظيفية الخاصة.',boot.user.role==='admin'?'<button id="addUser" class="primary">+ إضافة موظف</button>':'')+
 '<div class="card">'+table(['الموظف','القسم / الوظيفة','طبيعة العمل','الحساب','الإجراءات'],users.filter(u=>teamPermission('users')||Number(u.id)===Number(boot.user.id)).map(u=>[esc(u.name)+'<br><small class="muted">'+esc(u.username)+'</small>',esc(u.department)+'<br>'+esc(u.job_title),u.staff_type==='rep'?'مندوب':'مكتب',badge(u.active?'active':'rejected'),'<button data-user="'+u.id+'">فتح الملف</button>']))+'</div>';
 if($('#addUser'))$('#addUser').onclick=()=>userModal();
 document.querySelectorAll('[data-user]').forEach(b=>b.onclick=()=>userModal(users.find(u=>u.id==b.dataset.user)));
}
function userModal(user={}){
 if(boot.user.role==='manager'&&teamPermission('users','edit')&&Number(user.id)!==Number(boot.user.id)){modal('تعديل بيانات '+user.name,'<form id="modalForm">'+input('name','الاسم',user.name,'text','required')+input('department','القسم',user.department)+input('job_title','الوظيفة',user.job_title)+saveButton()+'</form>',f=>api('user_save',{id:user.id,...Object.fromEntries(new FormData(f))}));return;}
 if(boot.user.role!=='admin'){const p=user.profile??{};modal('ملف '+user.name,table(['البيان','القيمة'],[['الاسم',esc(user.name)],['اسم الدخول',esc(user.username)],['القسم',esc(user.department)],['الوظيفة',esc(user.job_title)],['تاريخ التعيين',esc(user.hired_on??'—')],['الهاتف',esc(p.phone??'—')],['البريد',esc(p.email??'—')],['الجنسية',esc(p.nationality??'—')],['العنوان',esc(p.address??'—')]]));return;}
 const p=user.profile??{};
 modal(user.id?'ملف '+user.name:'إضافة موظف','<form id="modalForm"><div class="form-grid">'+input('name','الاسم',user.name??'','text','required')+input('username','اسم الدخول',user.username??'','text','required')+
 '<label>الدور<select name="role">'+['employee','manager','admin'].map(r=>'<option value="'+r+'" '+(user.role===r?'selected':'')+'>'+({employee:'موظف',manager:'مدير فريق',admin:'إدارة'}[r])+'</option>').join('')+'</select></label>'+
 '<label>طبيعة العمل<select name="staff_type"><option value="office">مكتب</option><option value="rep" '+(user.staff_type==='rep'?'selected':'')+'>مندوب</option></select></label>'+
 companySelect('company_code','شركة العمل',p.company_code)+companySelect('residence_company_code','شركة الإقامة',p.residence_company_code)+input('name_en','الاسم بالإنجليزية',p.name_en??'')+input('bank_name','بنك الموظف',p.bank_name??'')+input('bank_account','رقم الحساب',p.bank_account??'')+input('iban','IBAN',p.iban??'')+input('civil_id','البطاقة المدنية',p.civil_id??'')+input('bank_salary','مبلغ التحويل البنكي المعتاد',p.bank_salary??'','number','min="0" step="0.001"')+input('ended_on','تاريخ نهاية الخدمة',p.ended_on??'','date')+
 input('department','القسم',user.department??'')+input('job_title','الوظيفة',user.job_title??'')+
 '<label>المدير المباشر<select name="manager_id"><option value="0">بدون مدير مباشر</option>'+users.filter(u=>u.role!=='employee'&&u.id!==user.id).map(u=>'<option value="'+u.id+'" '+(u.id==user.manager_id?'selected':'')+'>'+esc(u.name)+'</option>').join('')+'</select></label>'+
 input('hired_on','تاريخ التعيين',user.hired_on??'','date')+input('salary','الراتب الأساسي',user.salary??0,'number','step="0.001" min="0"')+
 input('password',user.id?'كلمة مرور جديدة — اتركها فارغة للإبقاء':'كلمة المرور','','password',user.id?'':'required minlength="12"')+
 input('leave_entitlement','رصيد إجازة خاص — فارغ للسياسة العامة',p.leave_entitlement??'','number','min="0"')+input('phone','الهاتف',p.phone??'')+input('email','البريد',p.email??'','email')+input('nationality','الجنسية',p.nationality??'')+input('address','العنوان',p.address??'')+
 '</div>'+check('active','الحساب مفعل',user.active??true)+check('show_sick','السماح بعرض عدد المرضيات للموظف',user.show_sick)+saveButton()+'</form>',
 form=>{const b=Object.fromEntries(new FormData(form));b.id=user.id??0;b.active=form.active.checked;b.show_sick=form.show_sick.checked;b.profile={...p,phone:b.phone,email:b.email,nationality:b.nationality,address:b.address,...Object.fromEntries(['company_code','residence_company_code','name_en','bank_name','bank_account','iban','civil_id','bank_salary','ended_on'].map(k=>[k,b[k]??'']))};if(b.leave_entitlement!=='')b.profile.leave_entitlement=Number(b.leave_entitlement);else delete b.profile.leave_entitlement;return api('user_save',b);});
}
async function records(module){
 const rows=await api('list',null,{module}),meta=boot.modules[module],canAdd=teamPermission(module,'create')||meta.employee;let summaryHtml='';if(module==='sick'&&teamPermission('sick')){const summaries=[];for(const u of users){if(u.role==='admin'||!u.active)continue;try{const x=await api('sick_summary',null,{user_id:u.id});summaries.push([esc(u.name),x.used,x.full,x.half,x.unpaid,esc(x.year_from)+' — '+esc(x.year_to),Number(u.show_sick)?'مسموح':'مخفي']);}catch(e){summaries.push([esc(u.name),esc(e.message),'—','—','—','—','—']);}}summaryHtml='<section class="card space-bottom"><h2>متابعة المرضيات السنوية حتى اليوم</h2>'+table(['الموظف','المستخدم','كامل','نصف','دون أجر','السنة','عرض العدد للموظف'],summaries)+'</section>';}let balance=null;if(module==='leave')balance=await api('leave_balance');
 $('#page').innerHTML=head(meta.label,'الطلبات والقرارات والمرفقات محفوظة حسب صلاحياتك.',canAdd?'<button id="addRecord" class="primary">+ إضافة</button>':'')+
 summaryHtml+(balance?'<div class="cards">'+stat('الرصيد المرجعي',balance.entitlement??'غير محدد',balance.year_from+' — '+balance.year_to,'◇')+stat('المستخدم',balance.used,'طلبات معتمدة','◷')+stat('المتبقي',balance.remaining??'غير محدد','حسب الرصيد المضبوط','◈')+stat('قيد المراجعة',balance.pending,'أيام بطلبات معلقة','▤')+'</div>':'')+'<section class="card">'+(rows.length?table(['الموظف','العنوان','الفترة','المبلغ','الحالة','الإجراءات'],rows.map(r=>[esc(r.employee_name),esc(r.title),esc(r.date_from??'—')+' ← '+esc(r.date_to??'—'),esc(r.amount)+' '+esc(boot.company.currency),badge(r.status),'<button data-record="'+r.id+'">التفاصيل</button>'])):empty())+'</section>';
 if($('#addRecord'))$('#addRecord').onclick=()=>recordModal(module);
 document.querySelectorAll('[data-record]').forEach(btn=>btn.onclick=()=>recordDetail(rows.find(r=>r.id==btn.dataset.record)));
}
function recordModal(module){
 const meta=boot.modules[module],date=dateNow();
 const fields=Object.entries(meta.fields).map(([key,label])=>key.includes('reason')||['details','checklist','criteria','improvement','handover','employee_response'].includes(key)?'<label>'+esc(label)+'<textarea name="'+key+'"></textarea></label>':input(key,label)).join('');
 modal('إضافة — '+meta.label,'<form id="modalForm">'+(teamPermission(module,'create')?'<label>الموظف<select name="user_id">'+userOptions()+'</select></label>':'')+input('title','العنوان',meta.label,'text','required')+
 '<div class="form-grid">'+input('date_from','من تاريخ',date,'date')+input('date_to','إلى تاريخ',date,'date')+'</div>'+fields+
 (['loan','discipline','adjustment','custody','leave_cash'].includes(module)?input('amount','المبلغ',0,'number','min="0" step="0.001"'):'')+
 '<label>المرفق'+(module==='sick'?' — صورة المرضية إلزامية':'')+'<input name="file" type="file" accept="'+(module==='sick'?'image/*':'image/*,application/pdf')+'" '+(module==='sick'?'required':'')+'></label>'+
 (module==='external'?'<p class="muted">يُرسل موقع حديث تلقائيًا مع الطلب. التصريح خاص بيوم واحد فقط.</p>':'')+saveButton()+'</form>',
 async form=>{const raw=Object.fromEntries(new FormData(form)),body={};Object.keys(meta.fields).forEach(k=>body[k]=raw[k]??'');const f=new FormData();['title','user_id','date_from','date_to','amount'].forEach(k=>{if(raw[k])f.set(k,raw[k]);});f.set('module',module);f.set('body',JSON.stringify(body));if(form.file.files[0])f.set('file',form.file.files[0]);if(module==='external'){const loc=await freshLocation();Object.entries(loc).forEach(([k,v])=>f.set(k,v));}await api('record_save',f);toast('تم حفظ السجل');});
}
function recordDetail(r){
 const b=JSON.parse(r.body_json),meta=boot.modules[r.module],canReview=teamPermission(r.module,'review')&&(boot.user.role==='admin'||Number(r.user_id)!==Number(boot.user.id));
 let html=(b._revision_details?'<p class="success">تعديل الموظف: '+esc(b._revision_details)+'</p>':'')+'<div class="detail-row"><b>الموظف</b><span>'+esc(r.employee_name)+'</span></div><div class="detail-row"><b>الحالة</b>'+badge(r.status)+'</div>'+
 Object.entries(meta.fields).map(([key,label])=>'<div class="detail-row"><b>'+esc(label)+'</b><span>'+esc(b[key]??'—')+'</span></div>').join('')+
 '<div class="detail-row"><b>ملاحظات المراجعة</b><span>'+esc(r.review_note??'—')+'</span></div>'+
 (b._employee_reply?'<div class="detail-row"><b>رد الموظف</b><span>'+esc(b._employee_reply)+'</span></div>':'')+
 (r.file_id?'<p><a class="button" href="file.php?id='+r.file_id+'" target="_blank" rel="noopener">فتح المرفق</a></p>':'');
 if(b._location)html+='<p><a class="button" href="https://www.google.com/maps?q='+encodeURIComponent(b._location.latitude+','+b._location.longitude)+'" target="_blank" rel="noopener">موقع الطلب · دقة '+Math.round(b._location.accuracy)+' متر</a></p>'+
 '<p class="muted">'+(boot.settings.locations??[]).map(l=>esc(l.name)+': '+Math.round(distance(b._location.latitude,b._location.longitude,l.latitude,l.longitude))+' متر مسافة مباشرة').join(' · ')+'</p>';
 if(canReview&&['pending','revision','active'].includes(r.status))html+='<label>ملاحظة القرار<textarea id="reviewNote"></textarea></label><div class="row-actions">'+(meta.approval?'<button class="primary" data-review="approved">موافقة</button><button data-review="revision">طلب تعديل</button><button class="danger" data-review="rejected">رفض</button>':'<button class="primary" data-review="closed">إغلاق السجل</button>')+'</div>';
 if(Number(r.user_id)===Number(boot.user.id)&&r.status==='revision'&&meta.employee)html+='<label>تاريخ البداية المعدل<input id="revisedFrom" type="date" value="'+esc(r.date_from??'')+'"></label><label>تاريخ النهاية المعدل<input id="revisedTo" type="date" value="'+esc(r.date_to??'')+'"></label><label>تفاصيل تعديل الطلب<textarea id="revisedDetails"></textarea></label><button id="resubmitRecord" class="primary">إعادة تقديم الطلب</button>';
 if(Number(r.user_id)===Number(boot.user.id))html+='<label>رد / توضيح<textarea id="employeeReply"></textarea></label><button id="sendReply">إرسال الرد</button>';
 if(r.module==='proof'&&Number(r.user_id)===Number(boot.user.id)&&r.status==='active')html+='<button id="proofSend" class="primary">إرسال موقع حديث لهذا الطلب</button>';
 modal(r.title,html);
 document.querySelectorAll('[data-review]').forEach(btn=>btn.onclick=async()=>{try{await api('record_review',{id:r.id,status:btn.dataset.review,note:$('#reviewNote').value});$('#modal').close();await navigate(view);}catch(e){toast(e.message);}});
 if($('#resubmitRecord'))$('#resubmitRecord').onclick=async()=>{try{let data={id:r.id,date_from:$('#revisedFrom').value,date_to:$('#revisedTo').value,details:$('#revisedDetails').value};if(r.module==='external')data={...data,...await freshLocation()};await api('record_resubmit',data);$('#modal').close();await navigate(view);}catch(e){toast(e.message);}};
 if($('#sendReply'))$('#sendReply').onclick=async()=>{try{await api('record_reply',{id:r.id,reply:$('#employeeReply').value});toast('تم إرسال الرد');$('#modal').close();await navigate(view);}catch(e){toast(e.message);}};
 if($('#proofSend'))$('#proofSend').onclick=async()=>{await sendTracking(Number(r.id));$('#modal').close();};
}
function distance(a,b,c,d){const rad=x=>x*Math.PI/180;const x=Math.sin(rad(c-a)/2)**2+Math.cos(rad(a))*Math.cos(rad(c))*Math.sin(rad(d-b)/2)**2;return 6371000*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));}
async function notifications(){
 const rows=await api('notifications');
 $('#page').innerHTML=head('مركز الإشعارات','قراءة القرار وإقرار الاطلاع عليه لا يعنيان الموافقة على مضمونه.',teamPermission('notifications','create')?'<button id="sendNotice" class="primary">+ إشعار جديد</button>':'')+
 '<section class="card">'+(rows.length?rows.map(r=>'<article class="notice '+(!r.read_at?'unread':'')+'"><h3>'+esc(r.title)+'</h3><p>'+esc(r.body)+'</p><small>'+esc(localDisplay(r.created_at))+'</small><div class="actions">'+(!r.read_at?'<button data-read="'+r.id+'">تحديد كمقروء</button>':'<span class="tag">مقروء</span>')+(r.require_ack&&!r.acknowledged_at?'<button class="primary" data-ack="'+r.id+'">اطلعت على القرار</button>':'')+(r.acknowledged_at?'<span class="tag">تم الإقرار بالاطلاع</span>':'')+'</div></article>').join(''):empty('لا توجد إشعارات'))+'</section>';
 if($('#sendNotice'))$('#sendNotice').onclick=()=>modal('إرسال إشعار','<form id="modalForm"><label>المستلم<select name="user_id">'+userOptions(0,true)+'</select></label><label>فلترة لقسم — اختياري<select name="department"><option value="">كل الأقسام</option>'+[...new Set(users.map(u=>u.department).filter(Boolean))].map(d=>'<option>'+esc(d)+'</option>').join('')+'</select></label>'+input('title','العنوان','','text','required')+'<label>الرسالة<textarea name="body" required></textarea></label>'+check('require_ack','يتطلب إقرار اطلاع',false)+saveButton()+'</form>',form=>{const b=Object.fromEntries(new FormData(form));b.require_ack=form.require_ack.checked;return api('notice_send',b);});
 document.querySelectorAll('[data-read],[data-ack]').forEach(btn=>btn.onclick=async()=>{await api('notice_read',{id:btn.dataset.read??btn.dataset.ack,ack:!!btn.dataset.ack});await notifications();});
}
async function reportView(){
 const date=dateNow();$('#page').innerHTML=head('سجل الدوام والتقارير','التسجيلات الفعلية منفصلة عن الجزاءات المحسوبة.')+
 '<div class="toolbar"><select id="reportUser">'+userOptions()+'</select><input id="reportFrom" type="date" value="'+date.slice(0,7)+'-01"><input id="reportTo" type="date" value="'+date+'"><button id="runReport" class="primary">عرض التقرير</button><button id="exportReport">تصدير Excel</button></div><div class="card" id="reportData"></div>';
 let rows=[];
 const load=async()=>{rows=await api('report',null,{user_id:$('#reportUser').value,from:$('#reportFrom').value,to:$('#reportTo').value});$('#reportData').innerHTML=table(['اليوم','الحضور','الانصراف','التأخير','المبكر','الأذونات','نسيان','جزاء دقائق / أيام','الحالة','التفاصيل'],rows.map((r,i)=>[
 esc(r.date),esc(localDisplay(r.events.find(e=>e.event_type==='attendance')?.received_at)),esc(localDisplay(r.events.find(e=>e.event_type==='checkout')?.received_at)),r.late_minutes,r.early_minutes,r.permission_minutes+(r.unclosed_permission?' · غير مغلق':''),r.missing,r.penalty_minutes+' دقيقة / '+r.penalty_days+' يوم',esc(r.note??(r.absence?'غياب':r.leave??(r.scheduled?'دوام':'راحة'))),'<button data-day="'+i+'">فتح</button>']));
 document.querySelectorAll('[data-day]').forEach(btn=>btn.onclick=()=>{const d=rows[btn.dataset.day];modal('تفاصيل '+d.date,table(['الحركة','وقت الخادم','دقة الموقع','المرفقات'],d.events.map(e=>[esc(labels[e.event_type]),esc(localDisplay(e.received_at)),(e.accuracy===null?'الموقع غير موجود بالسجل القديم':esc(e.accuracy)+' متر · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+encodeURIComponent(e.latitude+','+e.longitude)+'">الموقع</a>'),(e.photo_id?'<a href="file.php?id='+e.photo_id+'" target="_blank" rel="noopener">الصورة</a>':'—')+(e.evidence_id?' · <a href="file.php?id='+e.evidence_id+'" target="_blank" rel="noopener">السند</a>':'')])));});};
 $('#runReport').onclick=()=>load().catch(e=>toast(e.message));
 $('#exportReport').onclick=()=>openExport('attendance',{user_id:$('#reportUser').value,from:$('#reportFrom').value,to:$('#reportTo').value});
 await load();
}
function downloadCsv(name,rows){const cell=x=>'"'+String(x??'').replace(/^[=+@-]/,"'").replace(/"/g,'""')+'"';const blob=new Blob(['\ufeff'+rows.map(r=>r.map(cell).join(',')).join('\r\n')],{type:'text/csv;charset=utf-8'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;a.click();URL.revokeObjectURL(a.href);}
async function trackingView(){
 const rows=await api('locations'),stale=Number(boot.settings.tracking_stale??1200);
 $('#page').innerHTML=head('المتابعة الميدانية','آخر نقطة مسجلة فقط؛ التوقف أو انقطاع التحديث ليس دليل مخالفة.')+
 '<div class="columns"><section class="card"><h2>المناديب</h2>'+table(['المندوب','آخر تحديث','الدقة','الحالة','الموقع'],rows.map(r=>[esc(r.name),esc(localDisplay(r.received_at)),r.accuracy?esc(r.accuracy)+' متر':'—',badge(!r.received_at?'none':(Date.now()+serverOffset-new Date(r.received_at.replace(' ','T')+'Z').getTime()>stale*1000?'stale':'active')),r.latitude?'<button data-map="'+r.id+'">عرض</button>':'—']))+'</section><section class="card"><h2>خريطة النقطة المختارة</h2><div id="mapPanel" class="map-panel">اختر مندوبًا لعرض آخر نقطة</div><p class="tiny muted">عند فتح الخريطة تُعرض النقطة عبر خرائط Google. التحديث المستمر غير مضمون بالخلفية.</p></section></div>';
 document.querySelectorAll('[data-map]').forEach(btn=>btn.onclick=()=>{const r=rows.find(r=>r.id==btn.dataset.map);$('#mapPanel').innerHTML='<iframe title="آخر موقع للمندوب" loading="lazy" referrerpolicy="no-referrer" src="https://www.google.com/maps?q='+encodeURIComponent(r.latitude+','+r.longitude)+'&output=embed"></iframe>';});
}
async function auditView(){const rows=await api('audit');$('#page').innerHTML=head('سجل التغييرات','سجل قراءة فقط للتعديلات والموافقات.')+'<section class="card">'+table(['الوقت','المسؤول','العملية','السجل','التفاصيل'],rows.map(r=>[esc(localDisplay(r.created_at)),esc(r.name??'النظام'),esc(r.action),esc(r.subject),esc(r.detail_json)]))+'</section>';}
async function payrollView(){
 const rows=await api('payroll_list');$('#page').innerHTML=head('كشوف الرواتب','مسودات قابلة للمراجعة؛ النسخة المعتمدة تحتفظ بنتائج حسابها.')+
 (boot.user.role==='admin'?'<div class="toolbar"><input id="payrollMonth" type="month"><button id="buildPayroll" class="primary">إنشاء مسودات الشهر</button><button id="exportPayroll">تصدير الكشف</button></div>':'')+'<section class="card">'+table(['الموظف','الشهر','الصافي','الحالة','المراجعة'],rows.map(r=>[esc(r.name),esc(r.month_key),esc(r.net_amount)+' '+esc(boot.company.currency),badge(r.status),'<button data-payroll="'+r.id+'">تفاصيل</button>']))+'</section>';
 if($('#buildPayroll'))$('#buildPayroll').onclick=async()=>{try{await api('payroll_build',{month:$('#payrollMonth').value});await payrollView();}catch(e){toast(e.message);}};
 if($('#exportPayroll'))$('#exportPayroll').onclick=()=>openExport('payroll',{month:$('#payrollMonth').value});
 document.querySelectorAll('[data-payroll]').forEach(btn=>btn.onclick=()=>{const r=rows.find(r=>r.id==btn.dataset.payroll),c=JSON.parse(r.calculation_json);modal('كشف '+r.name+' — '+r.month_key,table(['الأساسي','الإضافات','الخصومات','الصافي'],[[esc(c.salary),esc(c.addition),esc(c.deduction),esc(r.net_amount)]])+'<p class="muted">'+esc(c.note)+'</p><p>'+esc(c.flags.join('\n'))+'</p>'+(r.status==='draft'&&boot.user.role==='admin'?'<button id="approvePayroll" class="primary">اعتماد الكشف</button>':''));if($('#approvePayroll'))$('#approvePayroll').onclick=async()=>{try{await api('payroll_approve',{id:r.id});$('#modal').close();await payrollView();}catch(e){toast(e.message);}};});
}
async function policies(){
 const rows=await api('policies');$('#page').innerHTML=head('الدوام والسياسات','كل تعديل يُحفظ كنسخة بتاريخ سريان؛ لا تُستبدل النسخة السابقة.', '<button id="addPolicy" class="primary">+ سياسة جديدة</button>')+
 '<section class="card">'+table(['السياسة','تخص الموظف','بداية السريان','نهاية السريان','الوقت','الإجراءات'],rows.map(r=>{const p=JSON.parse(r.value_json);return [esc(r.label),esc(r.employee_name??'الجميع'),esc(r.effective_from),esc(r.effective_to??'مفتوحة'),esc(p.start)+' — '+esc(p.end),'<button data-policy="'+r.id+'">نسخ لتعديل جديد</button>'];}))+'</section>';
 $('#addPolicy').onclick=()=>policyModal();
 document.querySelectorAll('[data-policy]').forEach(btn=>btn.onclick=()=>policyModal(rows.find(r=>r.id==btn.dataset.policy)));
}
function policyModal(row={}){
 const p=row.value_json?JSON.parse(row.value_json):boot.policy.values;
 const weekdays=['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
 modal('سياسة جديدة بتاريخ سريان','<form id="modalForm">'+input('label','اسم السياسة',row.label??'دوام العمل')+'<label>تطبيق على<select name="user_id">'+userOptions(row.user_id??0,true)+'</select></label>'+
 '<div class="form-grid">'+input('effective_from','تاريخ بداية السريان',dateNow(),'date','required')+input('effective_to','تاريخ نهاية السريان','','date')+input('start','الحضور',p.start,'time','required')+input('end','الانصراف',p.end,'time','required')+input('grace_minutes','السماح بالدقائق',p.grace_minutes,'number','min="0"')+input('break_minutes','الاستراحة بالدقائق',p.break_minutes,'number','min="0"')+
 input('max_accuracy','أقصى خطأ موقع بالمتر',p.max_accuracy,'number','min="1"')+input('location_max_age','حداثة الموقع بالثواني',p.location_max_age,'number','min="1"')+input('photo_max_age','حداثة الصورة بالثواني',p.photo_max_age,'number','min="1"')+'</div>'+
 '<h3>أيام العمل</h3>'+weekdays.map((d,i)=>check('weekday'+i,d,p.weekdays.includes(i))).join('')+
 '<label>تواريخ العطلات — تاريخ YYYY-MM-DD لكل سطر<textarea name="holiday_dates">'+esc((p.holiday_dates??[]).join('\n'))+'</textarea></label><hr>'+check('office_photo','صورة مباشرة لموظفي المكاتب',p.office_photo)+check('rep_photo','صورة مباشرة للمناديب',p.rep_photo)+check('rep_evidence','مرفق أول وآخر سند كدليل حضور فقط',p.rep_evidence)+check('rep_morning_external','السماح بحضور المندوب الصباحي من الخارج',p.rep_morning_external)+
 '<hr><h3>نسيان البصمة</h3>'+check('forget_enabled','تفعيل سياسة الجزاء على النسيان',p.forget_enabled)+input('missing_minutes','جزاء النسيان الأساسي بالدقائق',p.missing_minutes,'number','min="0"')+
 '<div class="form-grid"><label>فترة العد<select name="forget_period"><option value="month">شهرية</option><option value="year" '+(p.forget_period==='year'?'selected':'')+'>سنوية</option></select></label><label>وحدة العد<select name="forget_unit"><option value="punch">كل بصمة ناقصة</option><option value="day" '+(p.forget_unit==='day'?'selected':'')+'>اليوم ذو البصمة الناقصة</option></select></label><label>التصعيد<select name="penalty_mode"><option value="replace">بدل الجزاء الأساسي</option><option value="add" '+(p.penalty_mode==='add'?'selected':'')+'>بالإضافة للجزاء الأساسي</option></select></label></div>'+
 '<label>شرائح التصعيد — سطر لكل شريحة: بداية العدد، خصم الأيام<textarea name="tiers" placeholder="4, 1.5">'+esc((p.forget_tiers??[]).map(t=>t.from+', '+t.days).join('\n'))+'</textarea></label><p class="tiny muted">الغياب الكامل منفصل عن نسيان بصمة واحدة. الفترات الليلية تحتاج توسعة قبل تشغيلها.</p>'+saveButton()+'</form>',
 form=>{const raw=Object.fromEntries(new FormData(form)),v={};['start','end','forget_period','forget_unit','penalty_mode'].forEach(k=>v[k]=raw[k]);['grace_minutes','break_minutes','max_accuracy','location_max_age','photo_max_age','missing_minutes'].forEach(k=>v[k]=Number(raw[k]));['office_photo','rep_photo','rep_evidence','rep_morning_external','forget_enabled'].forEach(k=>v[k]=form[k].checked);v.holiday_dates=raw.holiday_dates.split('\n').map(x=>x.trim()).filter(Boolean);v.weekdays=weekdays.map((_,i)=>i).filter(i=>form['weekday'+i].checked);v.forget_tiers=raw.tiers.trim()?raw.tiers.split('\n').filter(Boolean).map(line=>{const [from,days]=line.split(',').map(Number);if(!Number.isFinite(from)||!Number.isFinite(days))throw Error('صيغة الشريحة: 4, 1.5');return {from,days};}):[];return api('policy_save',{label:raw.label,user_id:raw.user_id,effective_from:raw.effective_from,effective_to:raw.effective_to,values:v});});
}
function settingsView(){
 const s=boot.settings;
 $('#page').innerHTML=head('إعدادات الإدارة','هوية الشركة وقواعد التشغيل من هنا؛ ألوان التصميم ثابتة.')+
 '<form id="settingsForm"><section class="card settings-section"><h3>هوية الشركة</h3><div class="form-grid">'+input('company_name','اسم الشركة بالعربية',s.company_name)+input('company_name_en','اسم الشركة بالإنجليزية',s.company_name_en)+input('site_url','رابط الموقع',s.site_url,'url')+input('timezone','المنطقة الزمنية IANA',s.timezone)+input('currency','العملة',s.currency)+input('session_hours','مدة الخمول قبل انتهاء الجلسة بالساعات',s.session_hours,'number','min="1" max="168"')+'</div><label>الشعار<input id="logoUpload" type="file" accept="image/png,image/jpeg,image/webp"></label><button type="button" id="uploadLogo">رفع الشعار</button></section>'+
 '<section class="card settings-section"><h3>مواقع العمل</h3><p>حدد اسم الموقع ومركزه على الخريطة ونطاق التسجيل بالمتر.</p><div id="workLocations"></div><button type="button" id="addWorkLocation">+ موقع عمل</button><p class="tiny muted">الخريطة تعرض مركز الموقع؛ القبول يعتمد على نصف القطر ودقة القياس.</p></section>'+
 '<section class="card settings-section"><h3>المرضيات والإجازات</h3><div class="form-grid"><label>بداية السنة<select name="sick_year"><option value="calendar">يناير–ديسمبر</option><option value="hire" '+(s.sick_year==='hire'?'selected':'')+'>من تاريخ التعيين</option></select></label>'+input('sick_full_days','أيام المرضية بأجر كامل',s.sick_full_days??'','number','min="0"')+input('sick_half_days','أيام المرضية بنصف أجر',s.sick_half_days??'','number','min="0"')+'<label>سنة الإجازات<select name="leave_year"><option value="calendar">يناير–ديسمبر</option><option value="hire" '+(s.leave_year==='hire'?'selected':'')+'>من تاريخ التعيين</option></select></label>'+input('annual_leave_days','رصيد الإجازات السنوية المرجعي',s.annual_leave_days??'','number','min="0"')+input('leave_accrual_divisor','مقسوم الاستحقاق اليومي',s.leave_accrual_divisor??365,'number','min="1"')+'<label>الاستحقاق بعد الرصيد الافتتاحي<select name="leave_accrual_mode"><option value="annual">الرصيد المضبوط فقط</option><option value="daily" '+(s.leave_accrual_mode==='daily'?'selected':'')+'>يومي من الاستحقاق السنوي / المقسوم</option></select></label>'+check('leave_exclude_approved_days','استبعاد الإجازات المعتمدة من الاستحقاق اليومي',s.leave_exclude_approved_days)+'</div><small>شرائح المرضية تحفظ وقت اعتمادها؛ تغيير الحدود لا يعيد حساب الطلبات المعتمدة تلقائيًا.</small></section>'+
 '<section class="card settings-section"><h3>المتابعة الميدانية</h3>'+check('tracking_enabled','تفعيل التحديث أثناء الدوام وطالما الصفحة نشطة',s.tracking_enabled)+'<div class="form-grid">'+input('tracking_interval','الفاصل بين التحديثات بالثواني',s.tracking_interval,'number','min="60"')+input('tracking_stale','اعتبار الموقع قديمًا بعد ثوانٍ',s.tracking_stale,'number','min="60"')+'</div><small>لا يضمن الويب استمرار المتابعة مع قفل الشاشة. لا جزاء تلقائي بسبب توقف التحديث.</small></section>'+
 '<section class="card settings-section"><h3>حساب الرواتب</h3>'+'<p class="tiny muted">تفعيل التشغيل من شاشة بدء التشغيل بعد اعتماد البيانات القديمة.</p>'+check('overtime_enabled','احتساب الوقت بعد نهاية الدوام في المسودة للمراجعة',s.overtime_enabled)+'<div class="form-grid">'+input('salary_divisor','مقسوم الراتب لحساب أجر اليوم',s.salary_divisor,'number','min="1" step=".01"')+input('work_hours','ساعات أجر اليوم',s.work_hours,'number','min="1" step=".5"')+input('overtime_multiplier','معامل أجر الإضافي',s.overtime_multiplier,'number','min="0" step=".1"')+input('retention_days','حفظ نقاط المتابعة الدورية بالأيام',s.retention_days,'number','min="1"')+'</div><small>تشغيل مهمة الصيانة اليومية يطبق مدة نقاط المتابعة فقط. المستندات والبصمات لا تُحذف تلقائيًا. اضبط النسخ الاحتياطي من Plesk.</small></section><button class="primary">حفظ إعدادات الإدارة</button></form>';
 initializeWorkLocations(s.locations);
 $('#settingsForm').onsubmit=async e=>{e.preventDefault();try{const f=e.target,raw=Object.fromEntries(new FormData(f)),values={};['company_name','company_name_en','site_url','timezone','currency','sick_year','leave_year','leave_accrual_mode'].forEach(k=>values[k]=raw[k]);['session_hours','sick_full_days','sick_half_days','annual_leave_days','leave_accrual_divisor','tracking_interval','tracking_stale','salary_divisor','work_hours','overtime_multiplier','retention_days'].forEach(k=>values[k]=raw[k]===''?null:Number(raw[k]));['tracking_enabled','overtime_enabled','leave_exclude_approved_days'].forEach(k=>values[k]=f[k].checked);values.locations=[...document.querySelectorAll('[data-work-location]')].map(row=>({name:row.querySelector('[data-location-name]').value.trim(),latitude:Number(row.querySelector('[data-location-lat]').value),longitude:Number(row.querySelector('[data-location-lng]').value),radius:Number(row.querySelector('[data-location-radius]').value)}));await api('settings_save',{values});await loadBoot();shell();await settingsView();toast('تم حفظ الإعدادات');}catch(e){toast(e.message);}};
 $('#uploadLogo').onclick=async()=>{try{const file=$('#logoUpload').files[0];if(!file)throw Error('اختر الشعار أولًا');const f=new FormData();f.set('file',file);await api('logo_upload',f);await loadBoot();shell();settingsView();toast('تم تحديث الشعار');}catch(e){toast(e.message);}};
}
window.addEventListener('pagehide',()=>{closeCamera();stopLocation();});
start();
