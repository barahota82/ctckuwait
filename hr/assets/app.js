'use strict';
const $=s=>document.querySelector(s);
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const labels={attendance:'حضور',checkout:'انصراف',permission_exit:'إذن خروج',permission_return:'عودة',presence:'إثبات التواجد',approved:'معتمد',pending:'بانتظار المراجعة',rejected:'مرفوض',revision:'مطلوب تعديل',active:'نشط',inactive:'غير نشط',closed:'مغلق',cancelled:'ملغي',working:'داخل الدوام',away:'خارج بإذن',none:'لم يسجل حضورًا',draft:'مسودة',submitted:'مرسل للمراجعة',returned:'مرجع للتعديل',sent:'مرسل',recorded:'تم التسجيل',objection:'اعتراض'};
let navigationEpoch32=0;const pendingReads32=new Set();
let boot,users=[],view='dashboard',stream=null,photo=null,photoAt=0,captureTicket='',geo=null,geoWatch=null,trackingTimer=null,pending=null,selectedEvent='attendance',serverOffset=0;
const icons={permissions:'♧',publication:'◧',companies:'▦',import:'⇧',bank:'▧',leave_overview:'◇',dashboard:'◈',attendance:'◷',users:'♙',notifications:'◉',settings:'⚙',policies:'▤',report:'▥',tracking:'⌖',payroll:'▧',audit:'≡'};
let toastTimer393=null;function toast(t){if(t==='Navigation cancelled')return;const box=$('#toast');if(!box)return;const dialog=document.querySelector('dialog[open]');if(dialog){dialog.append(box);dialog.addEventListener('close',()=>{if(box.parentElement===dialog)document.body.append(box);},{once:true});}else if(box.parentElement!==document.body)document.body.append(box);clearTimeout(toastTimer393);box.setAttribute('role','status');box.setAttribute('aria-live','polite');box.textContent=uiText(t);box.style.display='block';toastTimer393=setTimeout(()=>box.style.display='none',6000);}
const localeReads162=new Map();let localeRefresh162=false;
const choiceReads81=new Map();let readGeneration119=0;
function api(action,data=null,params={}){
 if(data!==null){if(action==='translation161_tick')return apiRequest81(action,data,params);readGeneration119++;choiceReads81.clear();return apiRequest81(action,data,params);}
 const scope=typeof employeeValue73==='function'?employeeValue73():0;
 const key=JSON.stringify([navigationEpoch32,readGeneration119,boot?.identity32?.context||'',boot?.access_mode||'',boot?.company_filter30||'',view,scope,action,params]);
 let request=choiceReads81.get(key);if(!request){request=apiRequest81(action,null,params);choiceReads81.set(key,request);const done=()=>{if(choiceReads81.get(key)===request)choiceReads81.delete(key);};request.then(done,done);}
 // No response cache: share only the active request; give each caller its own JSON value.
 return request.then(value=>typeof structuredClone==='function'?structuredClone(value):JSON.parse(JSON.stringify(value)));
}
async function apiRequest81(action,data=null,params={},localeRefresh165=false){
 if(localeRefresh165&&data!==null)throw Error("Locale refresh must be read-only");
 const localeScope165=localeRefresh165?JSON.stringify([boot?.identity32?.context,boot?.company_filter30,typeof employeeValue73==='function'?employeeValue73():0]):null;
 if(typeof backupSession111!=='undefined'&&backupSession111.held&&action!=='backup_restore_commit')throw Object.assign(Error('Navigation cancelled'),{stale32:true});
 const restoreCommit122=action==='backup_restore_commit';
 const backgroundTranslation160=action==='translation161_tick';
 const transientBackup104=action==='backup_restore_preview'||/^backup_upload_(begin|chunk|finish)104$/.test(action);
 const saveState38=data!==null&&!backgroundTranslation160&&typeof screenCapture38==='function'?screenCapture38(action):null;
 const requestEpoch32=navigationEpoch32;
 const quietRead197=!!params?._background197; if(quietRead197){params={...params};delete params._background197;}
 const presenceOwn200=!!params?._presenceOwn200; if(presenceOwn200){params={...params};delete params._presenceOwn200;}
 if(typeof employeeReadParams73==='function')params=employeeReadParams73(action,data,params);
 const url='api.php?'+new URLSearchParams({action,...params});
 if(typeof connection32!=='undefined'){if(connection32.blocked)throw Error(uiText('تعذر الاتصال بالخادم'));if(data!==null&&!backgroundTranslation160)await connection32.ensure();}
 const connectionEpoch32=typeof connection32!=='undefined'?connection32.epoch:0;
 const options={credentials:'same-origin',cache:'no-store',headers:{}};
 if(boot?.identity32?.context&&action!=='bootstrap'&&action!=='login')options.headers['X-EP-Context32']=boot.identity32.context;
 if(data!==null){options.method='POST';options.headers['X-CSRF-Token']=boot?.csrf??'';if(data instanceof FormData)options.body=data;else{options.headers['Content-Type']='application/json';options.body=JSON.stringify(data);}}
 const controller=new AbortController();
 const longBackup109=/^(backup_create|backup_restore_commit|backup_restore_preview|backup_upload_finish104)$/.test(action);
 const timer=controller?setTimeout(()=>controller.abort(),longBackup109?960000:data===null?45000:120000):null;
 if(controller){options.signal=controller.signal;if(data===null)pendingReads32.add(controller);}
 if(data!==null&&!transientBackup104&&!restoreCommit122&&!backgroundTranslation160&&typeof connection32!=='undefined')connection32.beginWrite();
 const read197=typeof screenLoading197!=='undefined'?screenLoading197.read(action,data,params,localeRefresh165||quietRead197):null;
 try{
  let res;try{res=await fetch(url,options);}catch(error){error.transport32=true;throw error;}let body;
  if(action==='backup_restore_preview'&&res.status===413)throw Error('رفض الخادم حجم ملف النسخة قبل الفحص؛ راجع حدود الرفع في PHP وIIS. لم تتغير البيانات.');
  try{body=await res.json();}catch(e){if(e.name==='AbortError')throw e;throw Object.assign(Error('تعذر قراءة رد الخادم؛ راجع PHP وإعدادات الاستضافة.'),{responseInvalid32:true,httpStatus32:res.status});}
  if((data===null&&requestEpoch32!==navigationEpoch32)||(typeof backupSession111!=='undefined'&&backupSession111.held&&action!=='backup_restore_commit'))throw Object.assign(new Error('Navigation cancelled'),{stale32:true});
  if(localeRefresh165&&localeScope165!==JSON.stringify([boot?.identity32?.context,boot?.company_filter30,typeof employeeValue73==='function'?employeeValue73():0]))throw Object.assign(new Error('Navigation cancelled'),{stale32:true});
  if(!localeRefresh165&&typeof connection32!=='undefined'&&typeof connection32.success==='function')connection32.success();
  if(res.status===401||res.status===419){if(action!=='login')identityExpired32(res.status===401);throw Error(body.error);}
  if(!res.ok||body.error)throw Object.assign(Error(body.error||'تعذر الاتصال.'),{httpStatus32:res.status});
  if(typeof connection32!=='undefined'&&(connection32.blocked||connectionEpoch32!==connection32.epoch)){if(data!==null&&!transientBackup104&&!restoreCommit122&&!backgroundTranslation160)connection32.uncertain();throw Object.assign(Error(uiText('تعذر الاتصال بالخادم')),{transport32:true});}
  if(body.hr_localization160&&typeof mergeHRLocale160==='function')mergeHRLocale160(body.hr_localization160,action==='bootstrap'?body.data:boot);
  if(data===null&&body.hr_localization160&&action!=='bootstrap'&&Object.keys(body.hr_localization160.states||{}).length){const key=JSON.stringify([action,params]);localeReads162.set(key,{action,params:{...params},epoch:requestEpoch32,identity:boot?.identity32?.context,company:boot?.company_filter30,employee:typeof employeeValue73==='function'?employeeValue73():0});if(localeReads162.size>8)localeReads162.delete(localeReads162.keys().next().value);}
  if(action==='login'&&typeof body.data?.csrf==='string')boot.csrf=body.data.csrf;
  if(action==='bootstrap'&&typeof dataLabels31!=='undefined'){dataLabels31.clear();employeeNames143.clear();}if(typeof collectDataLabels31==='function')collectDataLabels31(body.data);if(!localeRefresh165&&!backgroundTranslation160&&boot?.user&&!/^backup_|^translation31_|^(login|logout)$/.test(action))setTimeout(()=>dataTranslationWorker31(),0);if(!localeRefresh165&&!backgroundTranslation160&&typeof screenCommit38==='function')screenCommit38(saveState38,body.data);if(data!==null&&!backgroundTranslation160&&action!=='backup_restore_commit'&&typeof operationDone393==='function')operationDone393(action,data,body.data);return body.data;
 }catch(error){if(typeof backupSession111!=='undefined'&&backupSession111.held&&action!=='backup_restore_commit')throw Object.assign(new Error('Navigation cancelled'),{stale32:true});if(data!==null&&action!=='login'&&!error.stale32&&!transientBackup104&&!restoreCommit122&&!backgroundTranslation160&&!presenceOwn200)toast(error.message||uiText('لم تكتمل العملية؛ راجع البيانات وحاول مرة أخرى.'));if((data===null&&requestEpoch32!==navigationEpoch32)||(typeof backupSession111!=='undefined'&&backupSession111.held&&action!=='backup_restore_commit'))throw Object.assign(new Error('Navigation cancelled'),{stale32:true});if(!localeRefresh165&&!transientBackup104&&!restoreCommit122&&!backgroundTranslation160&&(error.transport32||error.responseInvalid32||controller?.signal.aborted)&&typeof connection32!=='undefined'){if(typeof connection32.requestFailed==='function')await connection32.requestFailed(data!==null);else{if(data!==null)connection32.uncertain();connection32.lock();}}if(transientBackup104&&(error.transport32||error.responseInvalid32||controller?.signal.aborted))throw Object.assign(Error(controller?.signal.aborted?uiText('انتهت مهلة رفع أو فحص النسخة؛ لم يبدأ استبدال البيانات.'):uiText('تعذر إكمال رفع أو فحص النسخة؛ لم يبدأ استبدال البيانات.')+(error.httpStatus32?' HTTP '+error.httpStatus32:'')),{backupTransport104:true});if(controller?.signal.aborted)throw Object.assign(Error('استغرق تحميل البيانات وقتًا أطول من المتوقع. أعد فتح الشاشة للمحاولة مرة أخرى.'),{transport32:true});throw error;}
 finally{if(typeof screenLoading197!=='undefined')screenLoading197.done(read197);if(timer!==null)clearTimeout(timer);if(controller)pendingReads32.delete(controller);if(data!==null&&!transientBackup104&&!restoreCommit122&&!backgroundTranslation160&&typeof connection32!=='undefined')connection32.endWrite();}

}
async function refreshAuthorizedLocale162(){
 if(localeRefresh162||!boot?.user)return;localeRefresh162=true;
 try{
  for(const [key,r]of [...localeReads162]){
   if(r.epoch!==navigationEpoch32||r.identity!==boot?.identity32?.context||r.company!==boot?.company_filter30||(typeof employeeValue73==='function'&&r.employee!==employeeValue73())){localeReads162.delete(key);continue;}
   // Re-run only the already used read through its normal authentication and scope gates.
   await apiRequest81(r.action,null,r.params,true);
  }
  if(typeof refreshDataLanguage138==='function')refreshDataLanguage138();
  if(typeof refreshNoticeLanguage142==='function')refreshNoticeLanguage142();
  if(typeof applyInterfaceLanguage==='function')applyInterfaceLanguage();
 }finally{localeRefresh162=false;}
}
const dateNow=()=>new Intl.DateTimeFormat('en-CA',{timeZone:boot.company.timezone,year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(Date.now()+serverOffset));
function localDisplay(utc){if(!utc)return '—';const d=new Date(utc.replace(' ','T')+'Z');return new Intl.DateTimeFormat(boot?.localization?.language==='en'?'en-GB':'ar-KW',{timeZone:boot.company.timezone,day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:false,numberingSystem:'latn'}).format(d);}
function badge(s){return '<span class="badge '+esc(s)+'">'+esc(uiText(labels[s]??s))+'</span>';}
function companyName(){return boot?.localization?.language==='en'?(boot.company.company_name_en||boot.company.company_name):boot.company.company_name;}
function companyLogo(){return boot.company.logo_id?'<img class="brand-logo brand-await32" decoding="async" fetchpriority="high" src="file.php?id='+Number(boot.company.logo_id)+'&variant=brand32" alt="شعار الشركة">':'<div class="brand-mark">◆</div>';}
function closeCamera(){if(stream)stream.getTracks().forEach(t=>t.stop());stream=null;}
function stopLocation(){if(geoWatch!==null)navigator.geolocation.clearWatch(geoWatch);geoWatch=null;if(trackingTimer)clearInterval(trackingTimer);trackingTimer=null;}
async function loadBoot(){boot=await api('bootstrap');if(typeof applyInterfaceAdditions200==='function')applyInterfaceAdditions200(boot);if(typeof applyInterfaceLocale138==='function')applyInterfaceLocale138();serverOffset=new Date(boot.server_time.replace(' ','T')+'Z').getTime()-Date.now();anchorPortalClock32(Date.now()+serverOffset);document.title=(boot.company.public_site_title?.[boot.localization?.language||'ar']||boot.company.company_name)+' — '+uiText('شؤون الموظفين');}
async function start(){try{await loadBoot();if(!boot.user)return login();if(boot.user.profile?.must_change_password)return temporaryPassword28();users=[];shell();const startup127=document.querySelector('#app .shell');startup127?.setAttribute('inert','');startup127?.setAttribute('aria-busy','true');$('#page').innerHTML=loadingMarkup();try{if(!['dashboard','attendance','users','notifications','work_permit','document','custody','discipline','evaluation','policies'].includes(view))users=await api(boot.user.role==='admin'?'users':'user_choices29');}finally{startup127?.removeAttribute('inert');startup127?.removeAttribute('aria-busy');}attendanceReady127=view==='attendance';if(view==='notifications'&&typeof presenceSync200==='function'){try{await presenceSync200();}catch(e){}}try{await navigate(view);}finally{attendanceReady127=false;}if(typeof presenceArm200==='function')presenceArm200();if(typeof connection32!=='undefined')connection32.showUncertain();dataTranslationWorker31();if(boot.upgrade_needed&&boot.user.role==='admin')upgradeNotice();}catch(e){if(document.getElementById('identityReload32'))return;$('#app').innerHTML='<main class="install-card"><h1>تعذر فتح النظام</h1><p>'+esc(e.message)+'</p></main>';}}
let attendanceReady127=false;
let loginPending105=false;
async function submitLogin105(form){
 if(loginPending105)return false;
 loginPending105=true;
 const controls=[...form.querySelectorAll('input,button,select')],states=controls.map(n=>n.disabled);
 const button127=form.querySelector?.('button'),text127=button127?.textContent;if(button127)button127.textContent=uiText('جارٍ تسجيل الدخول…');
 try{
  const credentials={...Object.fromEntries(new FormData(form)),language:boot.localization.language};
  controls.forEach(n=>n.disabled=true);form.setAttribute('aria-busy','true');
  // A login page may outlive its anonymous session or another tab's login/logout.
  // Refresh the token before sending credentials; never retry a login POST automatically.
  const current=await api('login_token127');
  if(typeof current.csrf!=='string'||!current.csrf)throw Error(uiText('تعذر فتح الصفحة؛ أعد تحميلها.'));
  boot.csrf=current.csrf;
  await api('login',credentials);
  if(button127)button127.textContent=uiText('جارٍ فتح النظام…');
  await start();form.reset();return true;
 }catch(error){toast(error.message);return false;}
 finally{controls.forEach((n,i)=>n.disabled=states[i]);if(button127)button127.textContent=text127;form.removeAttribute('aria-busy');loginPending105=false;}
}
function login(){
 stopLocation();closeCamera();stopPortalClock32();document.body.className='auth-page';
 try{const saved=sessionStorage.getItem('click_login_language');if(boot.localization.allowed.includes(saved))boot.localization.language=saved;}catch(error){}
 if(boot.localization.login_dictionary)boot.localization.dictionary={...boot.localization.dictionary,...boot.localization.login_dictionary};
 $('#app').innerHTML='<main class="login-card">'+'<div class="login-brand-row"><div class="login-brand-logo">'+companyLogo()+'</div><div class="login-language">'+languagePicker()+'</div></div><h1>'+esc(companyName())+'</h1><div class="gold-rule"></div><form id="loginForm"><label>رقم الموظف / اسم الدخول<input name="username" autocomplete="username" required></label><label>كلمة المرور<input name="password" type="password" autocomplete="current-password" required></label><button class="primary submit-wide">تسجيل الدخول</button></form></main>';
 installInterfaceLanguage();installShortcut31();
 $('.login-card h1').setAttribute('data-no-translate','');
 switchLoginLanguage32(boot.localization.language);
 $('#interfaceLanguage').onchange=e=>switchLoginLanguage32(e.target.value);
 $('#loginForm').onsubmit=e=>{e.preventDefault();return submitLogin105(e.currentTarget);};
}
function switchLoginLanguage32(language){
 if(!boot.localization.allowed.includes(language))return;
 boot.localization.language=language;
 applyInterfaceLanguage();
 $('.login-card h1').textContent=companyName();
 const logo=$('.login-brand-logo img');if(logo)logo.alt=uiText('شعار الشركة');
 const shortcut=$('.login-card .install-shortcut31');if(shortcut){shortcut.setAttribute('data-no-translate','');shortcut.textContent=uiText('إضافة إلى جهازك');}
 document.title=(boot.company.public_site_title?.[language]||companyName())+' — '+uiText('شؤون الموظفين');
 try{sessionStorage.setItem('click_login_language',language);}catch(error){}
}
function canWrite29(screen,op='create'){return boot.user.role==='admin'||teamPermission(screen,op);}
function teamPermission(screen,action='view'){return boot.user.role==='admin'||(boot.user.role==='manager'&&boot.access_mode==='team'&&(boot.permissions?.[screen]??[]).includes('view')&&(boot.permissions?.[screen]??[]).includes(action));}
function personalScreen(key){return ['commissions','loan','personal_history','resumption','video_receipts','dashboard','attendance','notifications','report','payroll','users','leave','sick','external','request','custody','document','discipline','evaluation','employment','proof'].includes(key);}
function personalPermission34(key,op='view'){return boot.access_mode==='personal'&&(boot.personal_permissions34?.[key]??[]).includes(op);}
function canRegisterAttendance34(){return boot.user.role!=='admin'&&boot.access_mode==='personal'&&(boot.attendance_available35??boot.user.service?.attendance!==false)&&screenAllowed('attendance')&&personalPermission34('attendance','create');}
function screenAllowed(key){
 if(typeof systemAllowed182==='function'){const allowed=systemAllowed182(key);if(allowed!==null)return allowed;}
 if(key==='exit_permissions183')return boot.access_mode==='personal'?personalPermission34('exit_permissions183'):boot.user.role==='admin'||teamPermission('request');
 if(key==='request_attendance50')key='request';
 if(key==='debt_statement95')return boot.user.role!=='admin'&&boot.access_mode==='personal'&&personalPermission34('loan');
 if(['v28_data','v26_data','correction'].includes(key))return false;if(boot.access_mode==='personal'&&boot.user.role!=='admin'){if(key==='attendance'&&!(boot.attendance_available35??boot.user.service?.attendance!==false))return typeof presenceScreenAllowed200==='function'&&presenceScreenAllowed200();return personalPermission34(key);}if(key==='launch')return false;if(boot.user.role==='admin')return true;if(key==='attendance'&&!(boot.attendance_available35??boot.user.service?.attendance!==false))return typeof presenceScreenAllowed200==='function'&&presenceScreenAllowed200();if(key==='policies')return boot.user.role==='admin'||teamPermission('policy_general')||teamPermission('policy_staff');if(boot.user.role==='admin'&&['attendance','personal_history','video_receipts'].includes(key))return false;if(boot.user.role!=='admin'&&boot.access_mode!=='personal'&&(boot.denied_screens??[]).includes(key))return false;if(key==='batch_addition29'||key==='batch_friday29')return teamPermission(key);if(key==='v28_locations')return teamPermission('v28_locations');if(key.startsWith('v28_'))return boot.user.role==='admin'||teamPermission(({v28_files:'company_file',v28_documents:'corporate_document',v28_archive:'attachment_archive'})[key]??'v28_admin');return (boot.user.role==='admin'&&(['payout_status','v26_history','payments26','v26_data','entitlement','work_permit','addition','eos','video_auto','video_custom','analytics'].includes(key)))||(personalScreen(key)&&(boot.user.role==='admin'||boot.access_mode==='personal'||['dashboard','notifications'].includes(key)))||teamPermission(key)||(boot.user.role==='admin'&&['permissions','publication','companies','bank','import','policies','audit','settings','cms','public_settings','public_messages','offers','customer_departments','customer_ratings','languages','backups'].includes(key));}
function shell(){
 document.body.className='role-'+boot.user.role;
 const admin=boot.user.role==='admin',manager=boot.user.role==='manager';
 const links=[['dashboard',teamPermission('dashboard')?'نظرة عامة':'ملخصي'],...(!admin?[['attendance','بصمة الدوام']]:[]),['notifications','الإشعارات'],['report',teamPermission('report')?'سجل الدوام':'سجل دوامي'],['payroll',admin?'كشوف الرواتب':'كشف راتبي'],['users',teamPermission('users')?'الموظفون':'ملفي']];
 let menu=navItem('resumption','مباشرة العمل')+navItem('video_receipts','إقراراتي بالفيديو')+(teamPermission('analytics')&&!admin?navItem('analytics','التقارير الشاملة'):'')+navItem('commissions','العمولات')+(admin?'':navItem('personal_history','سجلي السابق'))+links.map(([key,title])=>navItem(key,title)).join('')+'<div class="nav-section">شؤون الموظفين</div>'+Object.entries(boot.modules).filter(([k,m])=>screenAllowed(k)).map(([k,m])=>navItem(k,m.label)).join('')+
 (teamPermission('tracking')?'<div class="nav-section">متابعة الفريق</div>'+navItem('tracking','المتابعة الميدانية'):'')+(teamPermission('leave_overview')?navItem('leave_overview','أرصدة الإجازات'):'')+
 (admin?'<div class="nav-section">إدارة النظام</div>'+navItem('companies','الشركات')+navItem('workforce','فترات الخدمة')+navItem('correction','تصحيح البيانات V25')+navItem('v26_data','بيانات V26')+navItem('v26_history','مراجعة البيانات التاريخية')+navItem('payments26','المدفوعات والاسترداد')+navItem('payout_status','حالة الصرف المؤرخة')+navItem('entitlement','الاستحقاقات المؤرخة')+navItem('work_permit','أذونات العمل')+navItem('addition','الإضافات الشهرية')+navItem('eos','نهاية الخدمة')+navItem('video_auto','إقرارات تلقائية بالفيديو')+navItem('video_custom','إقرارات بنص الإدارة')+navItem('analytics','التقارير الشاملة')+navItem('allowances','البدلات والجمعات')+navItem('permissions','الصلاحيات')+navItem('bank','تحويلات البنك')+navItem('import','البيانات القديمة')+navItem('policies','الدوام والسياسات')+navItem('audit','سجل التغييرات')+navItem('settings','إعدادات الإدارة')+navItem('backups','النسخ الاحتياطي والاسترجاع')+'<div class="nav-section">الموقع التعريفي</div>'+navItem('publication','ظهور صفحات الموقع')+navItem('cms','المحتوى والكروت')+navItem('offers','العروض')+navItem('public_settings','هوية الموقع والتواصل')+navItem('customer_departments','أقسام خدمة العملاء')+navItem('customer_ratings','تقييمات العملاء')+navItem('languages','اللغات والترجمة'):'');
 menu=tabMenu32();const caseMenu='';
 const quick=admin?[['dashboard','نظرة عامة'],['notifications','الإشعارات'],['attendance','بصمة الدوام'],['users','الموظفون']]:[['dashboard','ملخصي'],['attendance','التسجيل'],['leave','طلباتي'],['notifications','الإشعارات']];
 $('#app').innerHTML='<div class="shell"><aside class="sidebar"><div class="brand">'+companyLogo()+'<h2>'+esc(companyName())+'</h2></div>'+menu+caseMenu+'</aside><button class="sidebar-backdrop32" data-action="close-menu" aria-label="إغلاق القائمة" hidden></button><main class="workspace"><header class="topbar"><div class="topbar-right"><button class="mobile-menu" data-action="menu" aria-label="فتح القائمة">☰</button><div><b>'+esc(boot.trial_mode?'بيئة التجربة — مستقلة عن التشغيل الفعلي':'مساحة شؤون الموظفين')+'</b><small class="time-text">'+esc(localDisplay(boot.server_time))+' · '+esc(boot.company.timezone)+'</small></div></div><div class="topbar-right account-tools">'+identityPicker32()+(boot.access_mode==='team'&&boot.user.role!=='admin'?'<select id="companyFilter30" class="company-filter30" aria-label="'+esc(uiText('الشركة'))+'"><option value="">'+esc(uiText('كل الشركات المسموح بها'))+'</option>'+(boot.company_choices30??[]).map(c=>'<option value="'+esc(c.code)+'" '+(boot.company_filter30===c.code?'selected':'')+'>'+esc(c.name)+'</option>').join('')+'</select>':'')+languagePicker()+'<button data-action="password">كلمة المرور</button><div class="avatar">'+esc((boot.localization?.language==='en'?(boot.user.profile?.name_en||boot.user.name):boot.user.name).slice(0,1))+'</div><div class="account-name" role="button" tabindex="0" aria-haspopup="dialog" data-action="account-details"><b>'+esc((boot.localization?.language==='en'?(boot.user.profile?.name_en||boot.user.name):boot.user.name).trim().split(/\s+/)[0])+'</b><small>'+({admin:'المشرف العام',manager:'موظف',employee:'موظف'}[boot.user.role])+'</small></div><button data-action="logout">خروج</button></div></header><div id="portalTabs32" class="portal-tabs-host32" hidden></div><div class="page" id="page"></div></main><nav class="mobile-bottom" aria-label="التنقل السريع">'+quick.filter(([k])=>screenAllowed(k)).map(([k,t])=>navItem(k,t)).join('')+'</nav></div>';
 $('#app').onclick=async e=>{const nav=e.target.closest('[data-view]');if(nav){setMobileMenu32(false);if(nav.dataset.employeeId||nav.dataset.recordId)await openLinked32(nav.dataset.view,{employee_id:Number(nav.dataset.employeeId||0),record_id:Number(nav.dataset.recordId||0)});else await navigate(nav.dataset.view);return;}const action=e.target.closest('[data-action]')?.dataset.action;if(action==='menu')setMobileMenu32(!$('.sidebar').classList.contains('open'));if(action==='close-menu')setMobileMenu32(false);if(action==='logout'){await api('logout',{});await start();}if(action==='password')passwordModal();if(action==='account-details')showAccountDetails32();};
 if($('#accessMode29'))$('#accessMode29').onchange=e=>switchIdentity32(e.target);if($('#companyFilter30'))$('#companyFilter30').onchange=async e=>{try{await api('groups30_filter',{company:e.target.value});await start();}catch(err){toast(err.message);}};bindMenu28();installShortcut31();layoutPortalHeader32();if($('#interfaceLanguage'))$('#interfaceLanguage').onchange=e=>switchPortalLanguage32(e.target.value);installInterfaceLanguage();startPortalClock32();
 if(boot.user.staff_type==='rep'&&boot.settings.tracking_enabled&&personalPermission34('attendance','create'))watchLocation();
}
function navItem(key,title){const source138=title;title=uiText(title);return '<button class="nav-item" data-view="'+key+'"><span>'+esc(icons[key]??'▫')+'</span><span data-no-translate data-text-key138="'+esc(source138)+'">'+esc(title)+'</span></button>';}
function head(title,subtitle='',actions='',identity=false){
 const repeated51={users:['الموظفون','ملفي'],workforce:['فترات الخدمة'],employment:['إجراءات التعيين وإنهاء الخدمة'],document:['المستندات والعقود'],work_permit:['أذونات العمل'],custody:['العهد'],discipline:['الإنذارات والقرارات'],evaluation:['التقييمات'],request:['طلبات الموظفين']};
 const operationalTitles68={report:['سجل الدوام والتقارير'],policies:['الدوام والسياسات'],tracking:['المتابعة الميدانية'],leave_overview:['أرصدة الإجازات'],v28_locations:['مواقع العمل'],resumption:['مباشرة العمل']};
 if(typeof operationalView68==='function'&&operationalView68())repeated51[view]=[...(operationalTitles68[view]||[]),...(boot.modules?.[view]?[boot.modules[view].label]:[])];
 const omit51=!identity&&(repeated51[view]||[]).includes(title);
 if(omit51)return actions?'<div class="page-head"><div class="page-actions32">'+actions+'</div></div>':'';
 return '<div class="page-head"><div><h1 data-no-translate data-text-key138="'+esc(title)+'">'+esc(uiText(title))+'</h1>'+(identity?'<p class="header-identity32">'+esc(subtitle)+'</p>':'')+'</div>'+(actions?'<div class="page-actions32">'+actions+'</div>':'')+'</div>';
}
function loadingMarkup(){return '<div class="loading">'+esc(uiText('جارٍ التحميل…'))+'</div>';}
function empty(t='لا توجد نتائج'){t=uiText(t);return '<div class="empty">'+esc(t)+'</div>';}
function table(headers,rows){const actionCols=headers.map(h=>/^(الإجراءات|الاجراءات|Actions|Action)$/i.test(String(h).trim()));if(typeof formatTableCell32==='function')rows=rows.map(r=>r.map((c,i)=>formatTableCell32(headers[i]??'',c)));headers=headers.map((h,i)=>actionCols[i]?'':uiText(h));return '<div class="table-wrap"><table><thead><tr>'+headers.map((h,i)=>'<th'+(actionCols[i]?' data-actions32="1"':'')+'>'+esc(h)+'</th>').join('')+'</tr></thead><tbody>'+rows.map(r=>'<tr>'+r.map((c,i)=>'<td'+(actionCols[i]?' data-actions32="1"':'')+' data-label="'+esc(headers[i]??'')+'">'+c+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';}
function userOptions(selected=boot.user.id,all=false){return (all?'<option value="0">كل الموظفين / السياسة العامة</option>':'')+[...users].sort((a,b)=>String(a.employee_code??a.username).localeCompare(String(b.employee_code??b.username),'en',{numeric:true})).map(u=>employeeOption143(u.id,u.name,'',Number(selected)===Number(u.id),u)).join('');}
async function navigate(key,linkTarget32={}){
 if(typeof resolveSystem182==='function')key=resolveSystem182(key);
 if(key==='v28_schedule')key='policies';
 key=resolveHub32(key);
 if(boot.user.role==='admin'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134()&&['workforce','employment'].includes(key))key='users';
 if(view==='work_permit'&&typeof permitState148!=='undefined'&&permitState148?.saving)return;
 if(view==='work_permit'&&document.querySelector('#permitForm148')&&!employeeTransitionAllowed40($('#page')))return;
 if(view==='users'&&typeof employeeTransitionAllowed40==='function'&&document.querySelector('.profile-tabs29,#employeeEditorForm134')&&!employeeTransitionAllowed40($('#page')))return;
 if(typeof connection32!=='undefined'&&connection32.blocked)await connection32.ensure();
 if(view==='permissions'&&key!=='permissions'&&typeof accessDirty30!=='undefined'&&accessDirty30){if(!confirm(uiText('توجد تعديلات غير محفوظة. هل تريد تركها؟')))return;accessDirty30=false;}
 if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 const epoch32=++navigationEpoch32;if(typeof screenLoading197!=='undefined')screenLoading197.cancel();for(const req of pendingReads32)req.abort();pendingReads32.clear();
 if(typeof receiptRecorder26!=='undefined'&&receiptRecorder26?.state==='recording'){receiptRecorder26.onstop=null;receiptRecorder26.stop();}closeCamera();
 if(key==='attendance'&&!screenAllowed(key)&&typeof presenceSync200==='function'){try{await presenceSync200();}catch(e){}}
 if(!screenAllowed(key)){key=navigation30.flatMap(g=>g[1]).map(x=>x[0]).find(screenAllowed);if(!key){view='';$('#page').innerHTML=head('ملفي الشخصي','')+empty('لا توجد شاشات مسموحة لهذا العرض.');$('#portalTabs32').hidden=true;return;}}view=key;
 document.querySelector('.workspace')?.classList.toggle('layout113',typeof headerStackScreens77!=='undefined'&&headerStackScreens77.has(key));
 document.querySelectorAll('.nav-item').forEach(n=>{n.classList.toggle('active',n.dataset.view===key);if(n.dataset.view===key&&n.closest('details'))n.closest('details').open=true;});syncPortalTabs32(key);const initialScreen57=key;
 const page94=$('#page');page94.dataset.screenLoading94=String(epoch32);delete page94.dataset.screenFailed94;
 const retain197=typeof retainWorkspace196==='function'&&retainWorkspace196(key);if(typeof screenLoading197!=='undefined')screenLoading197.begin(key,epoch32,retain197);
 try{
 if(!retain197)$('#page').innerHTML=loadingMarkup();
 if(key==='work_permit'&&typeof permitEditor148==='function'){await permitEditor148(linkTarget32.record_id||0);applyInterfaceLanguage();return;}
 if(typeof reviewTarget193==='function'&&reviewTarget193(key)){await reviewWorkspace193(key,linkTarget32);applyInterfaceLanguage();return;}
 if(typeof prepareScreenChrome61==='function')prepareScreenChrome61(key);
 if(key==='delegation_managers199'){await delegationsScreen199();applyInterfaceLanguage();return;}
 if(key==='evaluation_settings182'){await evaluationSettings182();return;}
 if(['payroll_entry_click126','payroll_entry_afaq126'].includes(key)){await payrollEntry127(key);applyInterfaceLanguage();return;}
 if(['contract_sales99','contract_staff99','contract_agents99','receipt_company99','receipt_click99','receipt_afaq99','receipt_agents99'].includes(key)){await financeShell101(key);applyInterfaceLanguage();return;}
 if(typeof operationalRecords68==='function'&&(['leave','sick','leave_cash','external','proof'].includes(key)||(key==='request'&&requestMode50==='attendance'))){await operationalRecords68(key);applyInterfaceLanguage();return;}
 if(['document','custody','discipline','evaluation','request'].includes(key)){await categorizedRecords61(key);applyInterfaceLanguage();return;}
 // Let the active item/loading state paint before synchronous screen preparation.
 await new Promise(resolve=>{if(typeof requestAnimationFrame==='function')requestAnimationFrame(()=>setTimeout(resolve,0));else setTimeout(resolve,0);});
 if(epoch32!==navigationEpoch32)return;
 if(boot.user.role!=='admin'&&screenAllowed(key)&&!['policies','v28_locations','leave_overview','tracking','report','notifications','dashboard','attendance','users'].includes(key)){const aliases={v28_files:'company_file',v28_documents:'corporate_document',v28_archive:'attachment_archive'};users=await api('user_choices29',null,{screen:aliases[key]??key});}
 if(['batch_addition29','batch_friday29'].includes(key)&&screenAllowed(key)){view=key;await batchView29();applyInterfaceLanguage();return;}if(!screenAllowed(key))key='dashboard';if(key.startsWith('v28_')){view=key;if(key!==initialScreen57){document.querySelectorAll('.nav-item').forEach(n=>{n.classList.toggle('active',n.dataset.view===key);if(n.dataset.view===key&&n.closest('details'))n.closest('details').open=true;});syncPortalTabs32(key);}if(!retain197)$('#page').innerHTML=loadingMarkup();await screen28(key);applyInterfaceLanguage();return;}
 if(typeof receiptRecorder26!=='undefined'&&receiptRecorder26?.state==='recording'){receiptRecorder26.onstop=null;receiptRecorder26.stop();}closeCamera();if(!screenAllowed(key)){key=navigation30.flatMap(g=>g[1]).map(x=>x[0]).find(screenAllowed);if(!key){view='';$('#page').innerHTML=head('ملفي الشخصي','')+empty('لا توجد شاشات مسموحة لهذا العرض.');$('#portalTabs32').hidden=true;return;}}view=key;
 if(key!==initialScreen57){document.querySelectorAll('.nav-item').forEach(n=>{n.classList.toggle('active',n.dataset.view===key);if(n.dataset.view===key&&n.closest('details'))n.closest('details').open=true;});syncPortalTabs32(key);}
 $('#page').innerHTML=loadingMarkup();
  if(key==='payments26')await v26Payments();else if(key==='v26_history')await v26History();else if(key==='v26_data')await v26Data();else if(v26Titles[key])await v26Records(key,0,linkTarget32.record_id||0);else if(key==='analytics')await v26Analytics();else if(key==='eos')await v26Eos();else if(['video_auto','video_custom','video_receipts'].includes(key))await v26Videos(key==='video_auto'?'automatic':'custom');else if(key==='customer_cases')await customerCasesView();
  else if(key==='customer_departments')await departmentsView();
  else if(key==='customer_ratings')await ratingsView();
  else if(key==='languages')await languagesView();
  else if(key==='backups')await backupsView();
  else if(key==='permissions')await groupsView29();
  else if(key==='publication')await publicationView();
  else if(key==='companies')await companiesView();
  else if(key==='debt_statement95')await debtScreen32();
  else if(key==='bank')await bankView();
  else if(key==='import')await importView();
  else if(key==='leave_overview')await leaveOverview();
  else if(key==='offers')await offersView();
  else if(key==='cms')await cmsView();
  else if(key==='public_settings')await publicSettingsView();
  else if(key==='public_messages')await publicMessagesView();
  else if(key==='dashboard')await dashboard();
  else if(key==='attendance')await attendance();
  else if(key==='exit_permissions183')await permissionsScreen183();
  else if(key==='users'){if(typeof restoreProfileSave38!=='function'||!await restoreProfileSave38())await employees();}
  else if(key==='workforce')await workforceView();
  else if(key==='personal_history')await personalHistoryView();
  else if(key==='commissions')await (boot.access_mode==='personal'?contractsView35():payrollGrid37('sales'));
  else if(key==='collection37')await payrollGrid37('collection');
  else if(key==='vouchers37')await vouchersGrid37();
  else if(key==='setup_data')await setupDataView();
  else if(key==='settings')await settingsView();
  else if(key==='policies')await policies();
  else if(key==='notifications')await notifications();
  else if(key==='report')await reportView();
  else if(key==='tracking')await trackingView();
  else if(key==='audit')await v26Audit();
  else if(key==='correction')await correctionView();
  else if(key==='allowances')await allowancesView();
  else if(key==='payroll')await (boot.access_mode==='personal'?v26Payroll():payrollGrid37('payroll'));
  else if(key==='payroll_review35')await v26Payroll();
  else if(key==='fridays35')await fridaysView35();
  else if(key==='payroll_settings35')await payrollSettings35();
  else if(key==='payroll_settlement35')await payrollSettlement35();
  else if(boot.modules[key])await records(key);
  installScreenActions50();if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();applyInterfaceLanguage();
 }catch(e){if(epoch32!==navigationEpoch32||e.stale32)return;page94.dataset.screenFailed94='';$('#page').innerHTML=head('تعذر عرض الشاشة')+'<div class="error">'+esc(uiText(e.message))+'</div><button class="primary" data-view="'+esc(key)+'">'+esc(boot.localization.language==='en'?'Try again':'إعادة المحاولة')+'</button>'; }finally{if(epoch32===navigationEpoch32){delete page94.dataset.screenLoading94;try{if(typeof mountPeriodRefresh98==='function'){const root98=typeof recordFilterHost75==='function'?recordFilterHost75():null;if(root98)mountPeriodRefresh98(root98);}}finally{if(typeof screenLoading197!=='undefined')screenLoading197.end(epoch32);}}}
}
function stat(label,value,note,icon){if(typeof formatTableCell32==='function')value=formatTableCell32(label,value);label=uiText(label);note=uiText(note);return '<div class="card stat"><small>'+esc(label)+'</small><span class="accent">'+icon+'</span><strong>'+esc(value)+'</strong><p>'+esc(note)+'</p></div>';}
async function dashboard(){
 const epoch=navigationEpoch32,today=dateNow(),team=teamPermission('dashboard'),keys=['leave','sick','external','request'].filter(key=>screenAllowed(key));
 const state={overview:null,notices:null,requests:null,sick:null,errors:{}};
 $('#page').innerHTML='<section class="welcome-panel"><div><p class="eyebrow">'+esc(boot.company.company_name_en)+'</p><h1>أهلًا، '+employeeNameMarkup143(boot.user.id,boot.user.name,boot.user)+'</h1><div class="welcome-actions">'+(boot.user.role==='admin'?'<button class="gold" data-view="users">إدارة الموظفين <span>↗</span></button><button data-view="notifications">الإشعارات</button>':(canRegisterAttendance34()?'<button class="gold" data-view="attendance">بصمة الدوام <span>↗</span></button>':'')+(screenAllowed('leave')&&personalPermission34('leave','create')?'<button data-view="leave">تقديم طلب إجازة</button>':''))+'</div></div><div class="welcome-date"><span>'+esc(today)+'</span><b>يومك في مكان واحد</b><small>'+esc(boot.company.timezone)+'</small></div></section>'+
 '<div class="cards" id="dashboardCards140">'+loadingMarkup()+'</div><div class="columns"><section class="card"><div class="section-top"><div><p class="eyebrow">الطلبات</p><h2>'+ (team?'آخر طلبات نطاقك':'آخر طلباتي')+'</h2></div><button data-view="request">فتح الطلبات ↗</button></div>'+ '<div id="dashboardRequests140">'+loadingMarkup()+'</div>'+'</section><section class="card quick-card"><p class="eyebrow">مساحة العمل</p><h2>وصول سريع</h2>'+[boot.user.role==='admin'?['leave','طلبات الإجازات','مراجعة طلبات الموظفين']:['leave','طلب إجازة','قدم الطلب وتابع رد الإدارة'],['notifications','الإشعارات','قرارات وتعاميم في مكان واحد'],['users',teamPermission('users')?'ملفات الموظفين':'ملفي','البيانات الوظيفية والحساب'],...(boot.user.role==='admin'?[['publication','ظهور الموقع','تحكم في الصفحات المنشورة']]:[])].map(([k,t,n])=>'<button class="quick-link" data-view="'+k+'"><span class="quick-icon">'+(icons[k]??'◇')+'</span><span><b>'+t+'</b></span><i>↗</i></button>').join('')+'</section></div>'+
 '<section class="card space-top" id="dashboardStaff140">'+loadingMarkup()+'</section>';
 const page=$('#page'),marker=page.firstElementChild,current=()=>epoch===navigationEpoch32&&view==='dashboard'&&page.firstElementChild===marker;
 const error=()=>'<p class="error" role="alert">'+esc(uiText('تعذر تحميل البيانات'))+'</p><button type="button" data-dashboard-retry140>'+esc(uiText('إعادة المحاولة'))+'</button>';
 const value=(key,fn)=>state.errors[key]?error():state[key]===null?loadingMarkup():fn(state[key]);
 const draw=()=>{if(!current())return;
  const count=state.requests?.reduce((n,rows)=>n+Number(rows[0]?._pending_total140??rows.filter(r=>r.status==='pending').length),0),notices=state.notices?.unread;
  const card=(key,label,number,note,icon)=>state[key]===null||state.errors[key]?'<div class="card stat">'+esc(uiText(label))+value(key,()=>number)+'</div>':stat(label,number,note,icon);
  $('#dashboardCards140').innerHTML=team?card('overview','الموظفون في نطاقك',state.overview?.summary195?.staff??state.overview?.staff.length,'الموظفون على رأس العمل','♙')+card('requests','طلبات قيد المراجعة',count,'حسب صلاحيات الشاشات','▤')+card('notices','إشعارات غير مقروءة',notices,'إشعارات حسابك','◉')+card('overview','داخل الدوام',state.overview?.summary195?.working??state.overview?.staff.filter(r=>r.state==='working').length,'حسب التسجيلات الحالية','◷'):
   (screenAllowed('attendance')||screenAllowed('report')?stat('حالة يومك',labels[boot.state]??boot.state,'اليوم '+today,'◷'):'')+card('requests','طلباتي المعلقة',count,'طلباتك أنت فقط','▤')+(screenAllowed('notifications')?card('notices','إشعاراتي الجديدة',notices,'القرارات والتعاميم','◉'):'')+(screenAllowed('sick')?card('sick','مرضياتي هذا العام',state.sick?.used??'مخفي','العدد المستخدم عند السماح بعرضه','◇'):'');
  $('#dashboardRequests140').innerHTML=value('requests',lists=>{const rows=lists.flat().sort((a,b)=>String(b.created_at).localeCompare(String(a.created_at))||Number(b.id)-Number(a.id)).slice(0,6);return rows.length?table(['الموظف','الطلب','الفترة','الحالة'],rows.map(r=>[employeeNameMarkup143(r.user_id,r.employee_name,r),esc(r.title===r.module?(boot.modules[r.module]?.label||r.title):r.title),esc(r.date_from??'—'),badge(r.status)])):empty('لا توجد طلبات حتى الآن.');});
  const staff=paginate(state.overview?.staff||[],'staff',today,state.overview?.page195||null);$('#dashboardStaff140').innerHTML='<div class="section-top"><h2>'+esc(uiText(team?'دوام الفريق اليوم':'دوامي اليوم'))+'</h2><span class="muted">'+esc(today)+'</span></div>'+value('overview',()=>table(['الموظف','الحالة','التأخير','الملاحظات'],staff.map(r=>[employeeNameMarkup143(r.id,r.name,r),badge(r.state),r.late+' '+uiText('دقيقة'),esc(uiText(r.absence?'غياب':r.unclosed?'إذن غير مغلق':r.missing?'بصمة ناقصة':'—'))])));
  page.querySelectorAll('[data-dashboard-retry140]').forEach(b=>b.onclick=()=>navigate('dashboard'));applyInterfaceLanguage();
 };
 const paginate=recordPager189(page,()=>load('overview',()=>api('dashboard',null,{compact140:1,page195:JSON.stringify({...paginate.params('staff',today),filters:{family:'dashboard',route:'dashboard'}})})));
 const requests195={};const load=async(key,fn)=>{const ticket=requests195[key]=(requests195[key]||0)+1;if(key==='overview')paginate.busy(true);try{const data=await fn();if(!current()||requests195[key]!==ticket)return;state[key]=data;delete state.errors[key];}catch(e){if(!current()||requests195[key]!==ticket||e.stale32)return;state.errors[key]=true;}finally{if(current()&&requests195[key]===ticket){draw();if(key==='overview')paginate.busy(false);}}};
 draw();await Promise.all([
  load('overview',()=>api('dashboard',null,{compact140:1,page195:JSON.stringify({...paginate.params('staff',today),filters:{family:'dashboard',route:'dashboard'}})})),
  load('notices',()=>screenAllowed('notifications')?api('notifications',null,{summary140:1}):Promise.resolve({unread:0})),
  load('requests',()=>Promise.all(keys.map(module=>api('list',null,{module,summary140:1,...(module==='request'?{request_scope50:requestMode50}:{})})))),
  load('sick',()=>boot.user.role!=='admin'&&!team&&screenAllowed('sick')?api('sick_summary'):Promise.resolve({used:'مخفي'}))
 ]);
}

let attendanceAdminId200=0,attendanceAdminTicket200=0;
async function attendanceAdmin200(){
 const ticket=++attendanceAdminTicket200,requested=attendanceAdminId200,epoch=navigationEpoch32;
 let info;
 try{info=await api('punch127_info',null,requested?{user_id:requested}:{});}
 catch(error){if(ticket!==attendanceAdminTicket200||epoch!==navigationEpoch32||error?.stale32)return;toast(error.message||uiText('تعذر الاتصال بالخادم'));return;}
 if(ticket!==attendanceAdminTicket200||epoch!==navigationEpoch32||requested!==attendanceAdminId200)return;
 let list;
 try{list=await api('users');}
 catch(error){if(ticket!==attendanceAdminTicket200||epoch!==navigationEpoch32||error?.stale32)return;toast(error.message||uiText('تعذر الاتصال بالخادم'));return;}
 if(ticket!==attendanceAdminTicket200||epoch!==navigationEpoch32||requested!==attendanceAdminId200)return;
 users=list.filter(u=>u.role!=='admin');
 const selected=Number(info.user_id)||0;
 const options=[...users].sort((a,b)=>String(a.employee_code||a.username||'').localeCompare(String(b.employee_code||b.username||''),'en',{numeric:true})).map(u=>employeeOption143(u.id,u.name,u.employee_code||u.username||'',selected===Number(u.id),u)).join('');
 const chosen=info.user_id&&!info.choose_employee;
 $('#page').innerHTML=head('بصمة الدوام',chosen?'عرض حالة الموظف. لا بصمة شخصية ولا إرسال بالنيابة.':'حساب إدارة. اختر موظفًا لعرض حالته. لا إرسال من هذا الحساب.')+'<section class="card"><label>'+esc(uiText('الموظف'))+'<select id="attendanceAdmin200"><option value="">'+esc(uiText('اختر موظفًا'))+'</option>'+options+'</select></label>'+(chosen?'<div class="status-line"><span>'+esc(info.staff_name||'')+'</span>'+badge(info.state)+'</div><p>'+esc(uiText('عرض فقط'))+'</p>':'<p class="tiny muted">'+esc(uiText('المشرف العام حساب إدارة فقط'))+'</p>')+'</section>';
 const select=$('#attendanceAdmin200');if(typeof enhanceSelect32==='function')enhanceSelect32(select);if(chosen)select.value=String(info.user_id);
 select.onchange=()=>{attendanceAdminId200=Number(select.value)||0;attendance().catch(error=>{if(!error?.stale32)toast(error.message);});};
}
async function attendance(){
 if(boot.user.role==='admin')return attendanceAdmin200();
 const attendanceEpoch127=navigationEpoch32;if(attendanceReady127)attendanceReady127=false;else{const fresh127=await api('punch127_info');if(attendanceEpoch127!==navigationEpoch32)return;boot={...boot,state:fresh127.state,policy:fresh127.policy,departure199:fresh127.departure199,presence:fresh127.presence||null,attendance_available35:fresh127.attendance_available35,personal_permissions34:fresh127.personal_permissions34,user:{...boot.user,staff_type:fresh127.staff_type,service:{...boot.user.service,attendance:fresh127.service_attendance}}};serverOffset=new Date(fresh127.server_time.replace(' ','T')+'Z').getTime()-Date.now();anchorPortalClock32(Date.now()+serverOffset);}
 const state=boot.state,p=boot.policy.values;
 const executable33=canRegisterAttendance34();
 const available=executable33?({none:['attendance','checkout'],working:['checkout'],away:[],closed:[]}[state]??[]):[];
 selectedEvent=available[0]??'attendance';photo=null;photoAt=0;
 $('#page').innerHTML=head('بصمة الدوام','وقت الاستلام من الخادم. الموقع يتحدث تلقائيًا دون زر منفصل.')+'<section class="card attendance-card"><div class="status-line"><span>حالتك الحالية</span>'+badge(state)+'</div><div id="geoStatus" class="status-line">جارٍ تحديد الموقع…</div>'+
 (!p.configured?'<div class="error">الإدارة لم تعتمد جدول دوامك بعد.</div>':'')+
 '<div class="event-actions">'+Object.keys(labels).filter(k=>['attendance','checkout'].includes(k)).map(k=>'<button class="'+(selectedEvent===k?'selected':'')+'" data-event="'+k+'" '+(!available.includes(k)?'disabled':'')+'>'+labels[k]+'</button>').join('')+'</div>'+
 '<div class="camera-box"><video id="video" autoplay playsinline muted></video><img id="photoPreview" hidden alt="الصورة الحالية"></div><div class="camera-actions"><button id="frontCamera">الأمامية</button><button id="backCamera">الخلفية</button><button id="takePhoto" class="gold">التقاط الصورة</button></div><p id="cameraStatus" class="tiny muted"></p>'+
 (boot.user.staff_type==='rep'&&p.rep_evidence?'<label>صورة السند كدليل فقط<input id="evidence" type="file" accept="image/*" capture="environment"></label>':'')+
 '<button id="sendEvent" class="primary submit-wide" '+(!available.length?'disabled':'')+'>تسجيل '+labels[selectedEvent]+'</button><p id="saveStatus" class="tiny"></p></section>';
 document.querySelectorAll('[data-event]').forEach(btn=>btn.onclick=()=>{selectedEvent=btn.dataset.event;document.querySelectorAll('[data-event]').forEach(b=>b.classList.toggle('selected',b===btn));$('#sendEvent').textContent='تسجيل '+labels[selectedEvent];});
 $('#frontCamera').onclick=()=>openCamera('user');$('#backCamera').onclick=()=>openCamera('environment');$('#takePhoto').onclick=async()=>{await capture();const context=typeof presencePhotoContext200==='function'?presencePhotoContext200():null;if(context&&photo){try{const ticket=await api('presence_ticket',context.slot==='court'?{}:{slot:context.slot});presencePhoto200={blob:photo,ticket:ticket.ticket,photoAt,slot:context.slot};}catch(error){toast(error.message);}}};
 $('#sendEvent').onclick=sendEvent;departureButton199();if(typeof presenceAttendance200==='function')presenceAttendance200();if(executable33)watchLocation();else if(!(typeof presencePhotoContext200==='function'&&presencePhotoContext200())){for(const id of ['frontCamera','backCamera','takePhoto']){const node=$('#'+id);if(node)node.disabled=true;}$('#geoStatus').textContent=uiText(boot.user.service?.attendance===false?'هذا الحساب لا يخضع للحضور والانصراف':'عرض فقط — بصمة الدوام غير مسموح لهذا الحساب');}if(typeof presenceApplyTools200==='function')presenceApplyTools200();
}
function watchLocation(){
 if(geoWatch!==null)return updateGeoStatus();
 if(!navigator.geolocation){geoError('المتصفح لا يدعم الموقع');return;}
 geoWatch=navigator.geolocation.watchPosition(pos=>{geo={latitude:pos.coords.latitude,longitude:pos.coords.longitude,accuracy:pos.coords.accuracy,location_at:Math.floor((Date.now()+serverOffset)/1000)};updateGeoStatus();},err=>geoError(err.message),{enableHighAccuracy:true,maximumAge:0,timeout:25000});
 if(boot.user.staff_type==='rep'&&boot.settings.tracking_enabled&&personalPermission34('attendance','create')){
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
 const presenceOnly=!(typeof canRegisterAttendance34==='function'&&canRegisterAttendance34())&&typeof presencePhotoContext200==='function'&&presencePhotoContext200();
 let ticket=null;if(!presenceOnly){try{ticket=await api('capture_ticket',{work_date199:boot.departure199?.work_date});captureTicket=ticket.ticket;}catch(e){return toast(e.message);}}
 const c=document.createElement('canvas'),scale=Math.min(1,1200/Math.max(v.videoWidth,v.videoHeight));c.width=v.videoWidth*scale;c.height=v.videoHeight*scale;c.getContext('2d').drawImage(v,0,0,c.width,c.height);
 photo=await new Promise(resolve=>c.toBlob(resolve,'image/jpeg',.82));photoAt=ticket?Math.floor(new Date(ticket.server_time.replace(' ','T')+'Z').getTime()/1000):Math.floor((Date.now()+serverOffset)/1000);
 $('#photoPreview').src=c.toDataURL('image/jpeg',.82);$('#photoPreview').hidden=false;v.hidden=true;$('#cameraStatus').textContent='تم التقاط صورة حديثة.';closeCamera();
}
async function sendEvent(){
 const btn=$('#sendEvent');btn.disabled=true;
 try{
  if(!pending){
   const p=boot.policy.values,required=boot.user.staff_type==='rep'?p.rep_photo:p.office_photo;
   if(required&&!photo)throw Error('التقط صورة أولًا.');
   const loc=await freshLocation();pending={id:crypto.randomUUID(),photo,photoAt,captureTicket,event:selectedEvent,workDate199:boot.departure199?.work_date,loc,evidence:$('#evidence')?.files[0]};
  }
  pending.loc=await freshLocation();
  if(photo&&photoAt>pending.photoAt){pending.photo=photo;pending.photoAt=photoAt;pending.captureTicket=captureTicket;}
  const f=new FormData();f.set('request_id',pending.id);f.set('event_type',pending.event);f.set('photo_at',pending.photoAt);f.set('capture_ticket',pending.captureTicket);if(pending.workDate199)f.set('work_date199',pending.workDate199);
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
function modal(title,body,onSubmit=null,options={}){
 const opener195=document.activeElement;
 title=uiText(title);if(typeof modalRequest195!=='undefined')modalRequest195++;
 $('#modal').setAttribute('aria-labelledby','modalTitle195');if(typeof modalFocus195==='function')modalFocus195($('#modal'),opener195);
 $('#modalContent').innerHTML='<div class="modal-head"><h2 id="modalTitle195">'+esc(title)+'</h2><button id="closeModal" type="button" aria-label="'+esc(uiText('إغلاق'))+'">×</button></div><div class="modal-body">'+body+'</div>';
 $('#closeModal').onclick=()=>$('#modal').close();if(typeof mobileInput141==='function'&&mobileInput141()){$('#modalContent').querySelectorAll('[autofocus]').forEach(n=>n.removeAttribute('autofocus'));$('#closeModal').setAttribute('autofocus','');}$('#modal').showModal();applyInterfaceLanguage();
 if(onSubmit&&$('#modalForm'))$('#modalForm').onsubmit=async e=>{e.preventDefault();const form=e.target,btn=form.querySelector('button[type="submit"]');if(btn?.disabled)return;if(btn)btn.disabled=true;policyClearError371(form);try{const pageBefore38=$('#page')?.firstElementChild;if(await onSubmit(form)===false)return;$('#modal').close();if(options.onSaved)await options.onSaved();else if($('#page')?.firstElementChild===pageBefore38)await navigate(view);}catch(error){if($('#modal').open){if(error&&error.field&&typeof punchRevealField39==='function')punchRevealField39(error.field);policyError371(error.message,form);const field=error&&error.field;if(field&&field.isConnected){field.setAttribute('aria-invalid','true');field.dataset.policyInvalid371='';if(field.focus)field.focus({preventScroll:true});if(field.scrollIntoView)field.scrollIntoView({block:'center',behavior:'instant'});}}else toast(error.message);}finally{if(btn)btn.disabled=form.dataset.restoreWaiting122==='1';}};
}
function input(name,label,value='',type='text',extra=''){label=uiText(label);return '<label>'+esc(label)+'<input name="'+name+'" type="'+type+'" value="'+esc(value)+'" '+extra+'></label>';}
function check(name,label,value){label=uiText(label);return '<label class="check"><input name="'+name+'" type="checkbox" '+(value?'checked':'')+'>'+esc(label)+'</label>';}
function saveButton(){return '<div class="modal-foot"><button type="submit" class="primary">'+esc(uiText('حفظ'))+'</button></div>';}
function passwordModal(){modal('تغيير كلمة المرور','<form id="modalForm">'+input('old_password','كلمة المرور الحالية','','password','required')+'<p>كلمة المرور '+((boot.identity32?.supervisor||boot.user.role==='admin')?12:8)+' خانات على الأقل: حرف كبير وصغير ورقم ورمز.</p>'+input('new_password','كلمة المرور الجديدة','','password','required minlength="'+((boot.identity32?.supervisor||boot.user.role==='admin')?12:8)+'" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9\\s]).*"')+saveButton()+'</form>',form=>api('change_password',Object.fromEntries(new FormData(form))));}
async function employees(){if(typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134())return employeeEditor134();if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 users=await api('users');
 $('#page').innerHTML=head(teamPermission('users')?'الموظفون':'ملفي',teamPermission('users')?'ملفات الموظفين في نطاق صلاحياتك.':'بياناتك الوظيفية الخاصة.',boot.user.role==='admin'?'<button id="addUser" class="primary">+ إضافة موظف</button>':'')+
 '<div class="card">'+table(['الموظف','القسم / الوظيفة','طبيعة العمل','الحساب','الإجراءات'],employeeRows73(users,'id').filter(u=>teamPermission('users')||Number(u.id)===Number(boot.user.id)).map(u=>[recordIdentity32(u.id,displayName31(u))+'<br><small class="muted">'+esc(u.employee_code??u.username)+'</small>',esc(u.department)+'<br>'+esc(u.job_title),({office:'مكتب',rep:'مندوب',partner:'شريك',remote:'عن بُعد'}[u.staff_type]??u.staff_type),badge(u.active?'active':'inactive'),'<button data-user="'+u.id+'">فتح الملف</button>'+(boot.user.role==='admin'?'<button data-reset31="'+u.id+'">'+esc(uiText('كلمة مرور مؤقتة'))+'</button>':'')]))+'</div>';
 document.querySelectorAll('[data-reset31]').forEach(b=>b.onclick=()=>resetPassword31(Number(b.dataset.reset31)));
 if($('#addUser'))$('#addUser').onclick=async()=>{if(await userModal())wizard29();};moveAddUser49();
 document.querySelectorAll('[data-user]').forEach(b=>b.onclick=()=>employeeProfile29(users.find(u=>u.id==b.dataset.user)));
}
async function userModal(user={}){
 if(boot.user.role!=='admin'){renderUserModal58(user);return true;}
 const epoch=navigationEpoch32;modal('ملف الموظف','<p>'+esc(uiText('جارٍ التحميل…'))+'</p>');
 const marker=$('#modalContent').firstElementChild,dialog=$('#modal');
 try{
  const [companies,files]=await Promise.all([api('companies'),api('v28_info',null,{kind:'company_file'})]);
  if(epoch!==navigationEpoch32||!dialog.open||$('#modalContent').firstElementChild!==marker)return false;
  companiesData=companies;companyFiles28=files.records;renderUserModal58(user);return true;
 }catch(error){if(epoch===navigationEpoch32&&dialog.open&&$('#modalContent').firstElementChild===marker){dialog.close();if(!error.stale32)toast(error.message);}return false;}
}
function contractHistory119(rows){if(!rows?.length)return '';return '<details><summary>'+esc(uiText('تاريخ إعدادات عمولات العقود'))+'</summary>'+table(['تاريخ التعديل','عمولة مبيعات العقود','عمولة عقود','عمولة عقود مشرف'],rows.map(r=>{const v=JSON.parse(r.body_json),t=v.terms;return [esc(localDisplay(v.recorded_at||r.created_at)),...['staff','agent','supervisor'].map(k=>t[k].enabled?money26(t[k].rate):'—')];}))+'</details>';}
function contractFields119(p){return '<fieldset class="contract-fields119"><legend>'+esc(uiText('عمولات العقود'))+'</legend>'+[['contract_commission118','يستحق عمولة مبيعات العقود','contract_staff_rate119'],['contract_agent119','يستحق عمولة عقود','contract_agent_rate119'],['contract_supervisor119','يستحق عمولة عقود مشرف','contract_supervisor_rate119']].map(([flag,title,rate])=>'<div class="contract-rate-row119">'+check(flag,title,p[flag]===true)+input(rate,'المبلغ لكل عقد',p[rate]??0,'number','min="0" max="999999.999" step="0.001"')+'</div>').join('')+'<p class="muted">'+esc(uiText('عمولة المشرف من صافي عقود تاب موظفو الشركة فقط؛ تظل الإعدادات مفعلة حتى تغييرها وحفظها، ويسجل وقت التعديل تلقائيًا.'))+'</p></fieldset>';}
function renderUserModal58(user={},layout134=null){
 if(boot.user.role==='manager'&&teamPermission('users','edit')&&Number(user.id)!==Number(boot.user.id)){modal('تعديل بيانات '+user.name,'<form id="modalForm">'+input('name','الاسم بالعربي',user.name,'text','required')+input('department','القسم',user.department)+input('job_title','الوظيفة',user.job_title)+saveButton()+'</form>',f=>api('user_save',{id:user.id,...Object.fromEntries(new FormData(f))}));return;}
 if(boot.user.role!=='admin'){const p=user.profile??{};modal('ملف '+user.name,table(['البيان','القيمة'],[['الاسم',esc(displayName31(user))],['كود الدخول',esc(user.employee_code)],['القسم',esc(user.department)],['الوظيفة',esc(user.job_title)],['تاريخ التعيين',esc(user.hired_on??'—')],['الهاتف',esc(p.phone??'—')],['البريد',esc(p.email??'—')],['الجنسية',esc(typeof nationalityLabel137==='function'?nationalityLabel137(p.nationality??'—'):p.nationality??'—')],['العنوان',esc(p.address??'—')]]));return;}
 const p=user.profile??{};const dated=!!user.service?.period_id;
 const showUser134=layout134||modal;showUser134(user.id?'ملف '+user.name:'إضافة موظف','<form id="modalForm"><div class="form-grid">'+input('employee_code','كود الموظف',user.employee_code??'يتولد عند الحفظ','text','readonly')+input('name','الاسم بالعربي',user.name??'','text','required')+'<input type="hidden" name="username" value="'+esc(user.username??'staff-'+Date.now())+'">'+
 '<input type="hidden" name="role" value="'+esc(user.role??'employee')+'">'+
 '<label>طبيعة العمل<select name="staff_type"><option value="office">مكتب</option><option value="rep" '+(user.staff_type==='rep'?'selected':'')+'>مندوب</option><option value="partner" '+(user.staff_type==='partner'?'selected':'')+'>شريك</option><option value="remote" '+(user.staff_type==='remote'?'selected':'')+'>عن بُعد</option></select></label>'+
 companySelect('company_code','شركة العمل',p.company_code,dated)+companySelect('residence_company_code','شركة الإقامة',p.residence_company_code,true)+residenceFileSelect28(p)+input('name_en','الاسم بالإنجليزية',p.name_en??'')+input('bank_name','بنك الموظف',p.bank_name??'')+input('bank_account','رقم الحساب',p.bank_account??'')+input('iban','IBAN',p.iban??'')+input('civil_id','البطاقة المدنية',p.civil_id??'')+input('bank_effective_from','تاريخ سريان بيانات البنك عند تسجيلها أو تغييرها',p.payment_unconfirmed?(user.hired_on??''):'','date')+(p.payment_unconfirmed?check('payment_confirmed','أؤكد طريقة الاستلام الحالية؛ بدون حساب بنك تعتبر كاش',false):'')+input('ended_on','تاريخ نهاية الخدمة',p.ended_on??'','date',dated?'disabled':'')+
 input('department','القسم',user.department??'')+input('job_title','الوظيفة',user.job_title??'')+input('additional_job','وظيفة إضافية',p.additional_job??'')+
 '<label>المدير المباشر<select name="manager_id"><option value="0">بدون مدير مباشر</option>'+users.filter(u=>u.id!==user.id).map(u=>'<option value="'+u.id+'" '+(u.id==user.manager_id?'selected':'')+'>'+esc(displayName31(u))+'</option>').join('')+'</select></label>'+
 input('hired_on','تاريخ التعيين',user.hired_on??'','date',dated?'disabled':'')+input('salary','الراتب الأساسي',user.salary??0,'number','step="0.001" min="0"')+input('salary_effective_from','تاريخ سريان الراتب عند تسجيله أو تغييره','','date')+input('salary_note','سبب تغيير الراتب','')+
 input('password',user.id?'كلمة مرور جديدة — اتركها فارغة للإبقاء':'كلمة المرور','','password',user.id?'':'required minlength="8"')+
 input('leave_entitlement','رصيد إجازة خاص',p.leave_entitlement??'','number','min="0" placeholder="'+esc(uiText('فارغ = السياسة العامة'))+'"')+input('phone','الهاتف',p.phone??'')+input('email','البريد',p.email??'','email')+(typeof nationalityInput137==='function'?nationalityInput137(p.nationality??''):input('nationality','الجنسية',p.nationality??''))+input('address','العنوان',p.address??'')+
 '</div>'+check('fixed_salary','له راتب ثابت',p.fixed_salary??true)+(dated?'':check('attendance_required','يخضع للحضور والانصراف',p.attendance_required??true)+check('leave_eligible','يستحق رصيد إجازات',p.leave_eligible??true)+check('payroll_visible','يظهر بكشف الرواتب العام',p.payroll_visible??true)+check('bank_visible','يظهر بكشف تحويلات البنك',p.bank_visible??true))+check('active','الحساب مفعل',user.active??true)+check('show_sick','السماح بعرض عدد المرضيات للموظف',user.show_sick)+contractFields119(p)+saveButton()+'</form>',
 form=>{const b=Object.fromEntries(new FormData(form));b.id=user.id??0;b.active=form.active.checked;b.show_sick=form.show_sick.checked;b.profile={...p,phone:b.phone,email:b.email,nationality:b.nationality,address:b.address,...Object.fromEntries(['company_code','residence_company_code','name_en','bank_name','bank_account','iban','civil_id','additional_job','ended_on','residence_file_id'].map(k=>[k,b[k]??'']))};for(const k of ['fixed_salary','attendance_required','leave_eligible','payroll_visible','bank_visible','contract_commission118','contract_agent119','contract_supervisor119'])if(form[k])b.profile[k]=form[k].checked;for(const k of ['contract_staff_rate119','contract_agent_rate119','contract_supervisor_rate119'])if(form.elements.namedItem(k))b.profile[k]=form.elements.namedItem(k).value;if(form.payment_confirmed?.checked)b.profile.payment_unconfirmed=false;if(!b.profile.fixed_salary)b.salary=0;if(b.leave_entitlement!=='')b.profile.leave_entitlement=Number(b.leave_entitlement);else delete b.profile.leave_entitlement;return api('user_save',b);});
}
async function records(module){
 let [rows,sickOverview32]=await Promise.all([api('list',null,{module,...(module==='request'?{request_scope50:requestMode50}:{})}),module==='sick'&&teamPermission('sick')?api('sick_overview'):Promise.resolve([])]),meta=boot.modules[module],canAdd=teamPermission(module,'create')||(meta.employee&&personalPermission34(module,'create'));let summaryHtml='';if(module==='loan'&&boot.user.role!=='admin'){const debt=await api('v28_my_debt');summaryHtml='<section class="card"><h2>مديونيتي</h2><p>'+money26(debt.remaining)+' د.ك</p>'+table(['نوع الحركة','التاريخ','شهر الرواتب والعمولات','المبلغ'],debt.movements.map(m=>[esc(m.kind==='repayment'?'خصم معتمد':m.kind==='debt_opening'?'رصيد مبدئي':'سلفة مصروفة'),esc(m.date),esc(m.month),money26(m.amount)]))+'</section>';}if(module==='sick'&&teamPermission('sick')){const overview=sickOverview32;const summaries=overview.map(x=>[recordIdentity32(x.id,x.name),x.error?esc(x.error):x.used,x.full??'—',x.half??'—',x.unpaid??'—',x.year_from?esc(x.year_from)+' — '+esc(x.year_to):'—',x.show_sick?'مسموح':'مخفي']);summaryHtml='<section class="card space-bottom"><h2>متابعة المرضيات السنوية حتى اليوم</h2>'+table(['الموظف','المستخدم','كامل','نصف','دون أجر','السنة','عرض العدد للموظف'],summaries)+'</section>';}let balance=null;const personalLeave=module==='leave'&&boot.user.role!=='admin'&&boot.access_mode==='personal';if(personalLeave)balance=await api('leave_balance');
 $('#page').innerHTML=(module==='loan'?'<div class="page-head">'+(canAdd?'<button id="addRecord" class="primary">+ إضافة</button>':'')+'</div>':head(meta.label,'الطلبات والقرارات والمرفقات محفوظة حسب صلاحياتك.',canAdd?'<button id="addRecord" class="primary">+ إضافة</button>':''))+
 recordTabsHtml49(module)+summaryHtml+(balance?'<div class="cards">'+stat('الرصيد المرجعي',balance.entitlement??'غير محدد',balance.year_from+' — '+balance.year_to,'◇')+stat('المستخدم',balance.used,'طلبات معتمدة','◷')+stat('المتبقي',balance.remaining??'غير محدد','حسب الرصيد المضبوط','◈')+stat('قيد المراجعة',balance.pending,'أيام بطلبات معلقة','▤')+'</div>':'')+'<section class="card" id="moduleRecords32" data-record-search-host32><div data-record-results32>'+recordTable49(module,rows)+'</div></section>';
 if(module==='leave'&&boot.user.role==='admin'){
  const panel=document.createElement('section');panel.className='card space-bottom';
  panel.innerHTML='<label>'+esc(uiText('الموظف'))+'<select id="leaveBalanceUser"><option value="">'+esc(uiText('اختر موظفًا لعرض رصيد الإجازات'))+'</option>'+userOptions(0)+'</select></label><div id="leaveBalanceData"></div>';
  $('#page').insertBefore(panel,$('#page').querySelector('section'));
  let balanceRequest=0;
  $('#leaveBalanceUser').onchange=async e=>{const id=e.target.value,request=++balanceRequest;$('#leaveBalanceData').innerHTML='';if(!id)return;try{const b=await api('leave_balance',null,{user_id:id});if(request!==balanceRequest||view!=='leave')return;$('#leaveBalanceData').innerHTML='<div class="cards">'+stat('المستخدم',b.used,'طلبات معتمدة','◷')+stat('المتبقي',b.remaining??'غير محدد','حسب الرصيد المضبوط','◈')+stat('قيد المراجعة',b.pending,'أيام بطلبات معلقة','▤')+'</div>';applyInterfaceLanguage();}catch(error){if(request===balanceRequest&&view==='leave')$('#leaveBalanceData').innerHTML='<p class="error">'+esc(error.message)+'</p>';}};
 }
 if($('#addRecord'))$('#addRecord').onclick=()=>recordModal(module);
 if(module==='request'&&canAdd&&requestMode50==='attendance'){const b=document.createElement('button');b.textContent=uiText('+ مراجعة واقعة حضور');b.onclick=punchRequestModal39;$('#addRecord').after(b);}
 bindRecordTabs49(module,()=>rows);document.querySelectorAll('[data-record]').forEach(btn=>btn.onclick=()=>recordDetail(rows.find(r=>r.id==btn.dataset.record)));
 const recordHost=$('#moduleRecords32');remoteRecordSearch32(recordHost,async(q,current)=>{const result=await api('list',null,{module,q,...(module==='request'?{request_scope50:requestMode50}:{})});if(!current())return;rows=result;recordHost.querySelector('[data-record-results32]').innerHTML=recordTable49(module,rows);recordHost.querySelectorAll('[data-record]').forEach(btn=>btn.onclick=()=>recordDetail(rows.find(r=>r.id==btn.dataset.record)));});
}
async function recordModal(module,target173=null,knownStaff173=null){
 if(module==='leave_cash')return leaveCashModal198(target173||0);
 const epoch167=navigationEpoch32;
 if(knownStaff173&&recordModules173.includes(module))users=knownStaff173;
 if(teamPermission(module,'create')&&!knownStaff173){try{const choices167=await api('user_choices29',null,{screen:module});if(epoch167!==navigationEpoch32)return;users=choices167;}catch(error){if(!error.stale32)toast(error.message);return;}}
 const meta=boot.modules[module],date=dateNow();
 const fields=Object.entries(meta.fields).filter(([key])=>module!=='document'||!['document_type','expiry'].includes(key)).map(([key,label])=>key==='record_category49'?recordCategoryField49(module):key==='custody_state49'?'<label>حالة العهدة<select name="custody_state49"><option value="active">سارية</option><option value="returned">مستردة</option><option value="settled">تمت تسويتها</option></select></label>':key.includes('reason')||['details','checklist','criteria','improvement','handover','employee_response'].includes(key)?'<label>'+esc(label)+'<textarea name="'+key+'"></textarea></label>':input(key,module==='document'&&key==='document_number'?'رقم المستند':label,'',recordModules173.includes(module)&&['expiry','return_date'].includes(key)?'date':recordModules173.includes(module)&&key==='score'?'number':'text',key==='score'&&module==='evaluation'?'min="0" max="100"':'')).join('');
 modal('إضافة — '+meta.label,recordForm181(module,date)||('<form id="modalForm">'+(teamPermission(module,'create')?'<label>الموظف<select name="user_id">'+userOptions()+'</select></label>':'')+input('title',module==='document'?'اسم المستند':'العنوان',module==='document'?'':meta.label,'text','required maxlength="180"')+
 '<div class="form-grid">'+input('date_from','من تاريخ',date,'date')+input('date_to','إلى تاريخ',date,'date')+'</div>'+fields+
 (['loan','discipline','adjustment','leave_cash'].includes(module)?input('amount','المبلغ',0,'number','min="0" step="0.001"'):'')+
 '<label>المرفق'+(module==='sick'?' — صورة المرضية إلزامية':'')+'<input name="file" type="file" accept="'+(module==='sick'?'image/*':'image/*,application/pdf')+'" '+(module==='sick'?'required':'')+'></label>'+
 (module==='external'?'':'')+saveButton()+'</form>'),
 async form=>{const raw=Object.fromEntries(new FormData(form)),body={};Object.keys(meta.fields).forEach(k=>body[k]=raw[k]??'');if(module==='document')body.expiry=raw.date_to||'';recordNormalize181(module,raw,body);const f=new FormData();['title','user_id','date_from','date_to','amount'].forEach(k=>{if(raw[k])f.set(k,raw[k]);});f.set('module',module);f.set('body',JSON.stringify(body));if(form.file.files[0])f.set('file',form.file.files[0]);if(module==='external'){const loc=await freshLocation();Object.entries(loc).forEach(([k,v])=>f.set(k,v));}if(await recordSubmit173(form,f)===false)return false;toast('تم حفظ السجل');});
 const form167=$('#modalForm');if(form167&&recordModules173.includes(module)){if(target173&&form167.elements.user_id)form167.elements.user_id.value=String(target173);recordBind173(form167,module,['title',...recordFree173[module].filter(k=>form167.elements.namedItem(k))]);recordModalLayout174(form167);if(module==='document')documentModalLayout176(form167);if(['custody','discipline'].includes(module))recordModal181(form167,module);}
 if(typeof operationalForm195==='function')operationalForm195(form167,module);
 await leaveBalanceForm198(form167,module);
 if(form167&&typeof dataFormBind159==='function'){
  const contexts167={title:'record_title:'+module};for(const key of Object.keys(meta.fields))if(hrTextFields162.includes(key))contexts167[key]=dataRecordContext160(module,key)||'record_text:'+module+':'+key;
  dataFormBind159(form167,contexts167);
  form167.addEventListener('formdata',event=>{for(const control of form167.querySelectorAll('[data-context137]'))if(control.name&&!control.disabled)event.formData.set(control.name,employeeDataOriginal137(control));});
 }
}
function recordDetail(r){
 if(JSON.parse(r.body_json||'{}')._auto182)return evaluationDetail182(r);
 const b=JSON.parse(r.body_json),meta=boot.modules[r.module],canReview=(r.workflow199?r.workflow199.can_review:true)&&teamPermission(r.module,'review')&&(boot.user.role==='admin'||Number(r.user_id)!==Number(boot.user.id));
 let html='<div class="detail-row"><b>رقم السجل</b><span>'+esc(r.id)+'</span></div>'+(b._revision_details?'<p class="success">تعديل الموظف: '+dataTextMarkup162('record_text:'+r.module+':_revision_details',b._revision_details)+'</p>':'')+'<div class="detail-row"><b>الموظف</b><span>'+employeeNameMarkup143(r.user_id,r.employee_name,r)+'</span></div><div class="detail-row"><b>الحالة</b>'+recordBadge181(r)+'</div>'+
 (recordModules173.includes(r.module)?recordDisplayFields176(r.module,meta.fields,b,r):Object.entries(meta.fields)).map(([key,label])=>'<div class="detail-row"><b>'+esc(label)+'</b><span>'+(key==='record_category49'?esc(uiText(recordCategoryLabel49(r.module,b[key])||'غير مصنف')):recordModules173.includes(r.module)?recordFieldMarkup173(r.module,key,b[key]):dataDetailMarkup160(key,b[key]??'—',{module:r.module},b[key]??'—'))+'</span></div>').join('')+
 '<div class="detail-row"><b>ملاحظات المراجعة</b><span>'+dataTextMarkup162('record_text:'+r.module+':review_note',r.review_note??'—')+'</span></div>'+
 (b._employee_reply?'<div class="detail-row"><b>رد الموظف</b><span>'+dataTextMarkup162('record_text:'+r.module+':_employee_reply',b._employee_reply)+'</span></div>':'')+
 (r.file_id?'<p><a class="button" href="file.php?id='+r.file_id+'" target="_blank" rel="noopener">فتح المرفق</a></p>':'');
 if(recordModules173.includes(r.module))html=recordSummary173(r)+html;
 if(b._punch39)html+=punchRecord39(b._punch39);
 if(b._location)html+='<p><a class="button" href="https://www.google.com/maps?q='+encodeURIComponent(b._location.latitude+','+b._location.longitude)+'" target="_blank" rel="noopener">موقع الطلب · دقة '+Math.round(b._location.accuracy)+' متر</a></p>'+
 '<p class="muted">'+(boot.settings.locations??[]).map(l=>esc(l.name)+': '+Math.round(distance(b._location.latitude,b._location.longitude,l.latitude,l.longitude))+' متر مسافة مباشرة').join(' · ')+'</p>';
 if(canReview&&['pending','revision','active'].includes(r.status))html+='<label>ملاحظة القرار<textarea id="reviewNote"></textarea></label><div class="row-actions">'+(meta.approval?'<button class="primary" data-review="approved">موافقة</button><button data-review="revision">طلب تعديل</button><button class="danger" data-review="rejected">رفض</button>':'<button class="primary" data-review="closed">إغلاق السجل</button>')+'</div>';
 if(Number(r.user_id)===Number(boot.user.id)&&r.status==='revision'&&meta.employee&&(boot.access_mode!=='personal'||personalPermission34(r.module,'create')))html+='<label>تاريخ البداية المعدل<input id="revisedFrom" type="date" value="'+esc(r.date_from??'')+'"></label><label>تاريخ النهاية المعدل<input id="revisedTo" type="date" value="'+esc(r.date_to??'')+'"></label><label>تفاصيل تعديل الطلب<textarea id="revisedDetails"></textarea></label><label>المرفق البديل — اختياري<input id="revisedFile" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"></label><button id="resubmitRecord" class="primary">إعادة تقديم الطلب</button>';
 if(Number(r.user_id)===Number(boot.user.id)&&(boot.access_mode!=='personal'||personalPermission34(r.module,'respond')))html+='<label>رد / توضيح<textarea id="employeeReply"></textarea></label><button id="sendReply">إرسال الرد</button>';
 if(r.module==='proof'&&Number(r.user_id)===Number(boot.user.id)&&r.status==='active'&&(boot.access_mode!=='personal'||personalPermission34('proof','respond')))html+='<button id="proofSend" class="primary">إرسال موقع حديث لهذا الطلب</button>';
 if(r.module==='leave'&&r.status==='approved'&&boot.user.role==='admin')html+='<form id="lateReturn26">'+check('late','تخلف عن موعد العودة',!!b.late_open)+input('actual_end','آخر يوم إجازة فعلي — اتركه فارغًا إذا لم يعد',b.actual_end??'','date')+input('note','سبب الزيادة',b.late_reason??'')+saveButton()+'</form>';modal(dataText137('record_title:'+r.module,r.title),html);const heading162=$('#modalContent .modal-head h2');if(heading162){heading162.dataset.noTranslate='';heading162.dataset.textContext138='record_title:'+r.module;heading162.dataset.textSource138=r.title;}if($('#lateReturn26'))$('#lateReturn26').onsubmit=async e=>{e.preventDefault();try{const f=e.target;await api('v26_late_return',{id:r.id,late:f.late.checked,actual_end:f.actual_end.value,note:f.note.value});$('#modal').close();navigate(view);}catch(e){toast(e.message);}};
 document.querySelectorAll('[data-review]').forEach(btn=>btn.onclick=async()=>{try{const sent173=await recordSend173('record_review',r,{id:r.id,status:btn.dataset.review,note:$('#reviewNote').value,revision199:r.revision199});if(sent173===false)return;$('#modal').close();await navigate(view);}catch(e){toast(e.message);}});
 if($('#resubmitRecord'))$('#resubmitRecord').onclick=async()=>{try{let data={id:r.id,date_from:$('#revisedFrom').value,date_to:$('#revisedTo').value,details:$('#revisedDetails').value};if(r.module==='external')data={...data,...await freshLocation()};const body=new FormData();Object.entries(data).forEach(([k,v])=>body.set(k,v));if($('#revisedFile')?.files[0])body.set('file',$('#revisedFile').files[0]);await api('record_resubmit',body);$('#modal').close();await navigate(view);}catch(e){toast(e.message);}};
 if($('#sendReply'))$('#sendReply').onclick=async()=>{try{const sent173=await recordSend173('record_reply',r,{id:r.id,reply:$('#employeeReply').value});if(sent173===false)return;toast('تم إرسال الرد');$('#modal').close();await navigate(view);}catch(e){toast(e.message);}};
 recordDetailBind173(r);decisionButtons199(r);leavePaymentButton198(r);if(r.module==='leave'&&r.status==='approved'&&boot.user.role==='admin'){const button=document.createElement('button');button.type='button';button.textContent=uiText('تسوية رصيد الإجازة المالي');button.onclick=()=>leaveCashModal198(r.user_id,r.id);document.getElementById('modalContent').append(button);}
 if($('#proofSend'))$('#proofSend').onclick=async()=>{await sendTracking(Number(r.id));$('#modal').close();};
}
function distance(a,b,c,d){const rad=x=>x*Math.PI/180;const x=Math.sin(rad(c-a)/2)**2+Math.cos(rad(a))*Math.cos(rad(c))*Math.sin(rad(d-b)/2)**2;return 6371000*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));}
async function notifications(){if(typeof presenceSync200==='function'){try{await presenceSync200();}catch(e){}}return notificationCenter32();}
async function reportView(){if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 const epoch68=navigationEpoch32,choiceRead68=boot.user.role!=='admin'?api('user_choices29',null,{screen:'report'}):Promise.resolve(null);
 const date=dateNow();$('#page').innerHTML=head('سجل الدوام والتقارير','التسجيلات الفعلية منفصلة عن الجزاءات المحسوبة.')+
 '<div class="toolbar"><select id="reportUser">'+(boot.user.role==='admin'?userOptions():'')+'</select><input id="reportFrom" type="date" value="'+date.slice(0,7)+'-01"><input id="reportTo" type="date" value="'+date+'"><button id="runReport" class="primary standard-action71">عرض التقرير</button><button id="exportReport" class="standard-action71">تصدير Excel</button></div><div class="card" id="reportData"></div>';
 $('#reportUser').disabled=$('#runReport').disabled=$('#exportReport').disabled=true;
 if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();
 const choices68=await choiceRead68;if(epoch68!==navigationEpoch32||!$('#reportData'))return;if(choices68){users=choices68;$('#reportUser').innerHTML=userOptions();}
 if(!users.length){$('#reportData').innerHTML=empty('لا يوجد موظفون متاحون لعرض التقرير.');return;}
 $('#reportUser').disabled=$('#runReport').disabled=$('#exportReport').disabled=false;
 let rows=[],reportRequest68=0;const reportHost68=$('#reportData');
 const load=async()=>{const ticket=++reportRequest68,result=await api('report',null,{user_id:$('#reportUser').value,from:$('#reportFrom').value,to:$('#reportTo').value});if(ticket!==reportRequest68||epoch68!==navigationEpoch32||!reportHost68.isConnected)return;rows=result;reportHost68.innerHTML=table(['اليوم','الحضور','الانصراف','التأخير','المبكر','الأذونات','نسيان','جزاء دقائق / أيام','بصمة التواجد','الحالة','التفاصيل'],rows.map((r,i)=>[
 esc(r.date),esc(localDisplay(r.events.find(e=>e.event_type==='attendance')?.received_at)),esc(localDisplay(r.events.find(e=>e.event_type==='checkout')?.received_at)),r.late_minutes,r.early_minutes,r.permission_minutes+(r.unclosed_permission?' · غير مغلق':''),(typeof reportPresenceCells193==='function'?reportPresenceCells193(r).missing:r.missing),(typeof reportPresenceCells193==='function'?reportPresenceCells193(r).penalty:r.penalty_minutes+' دقيقة / '+r.penalty_days+' يوم'),(typeof reportPresenceCells193==='function'?reportPresenceCells193(r).presence:'—'),esc(r.note??(r.absence?'غياب':r.leave??(r.scheduled?'دوام':'راحة'))),'<button data-day="'+i+'">فتح</button>']));
 document.querySelectorAll('[data-day]').forEach(btn=>btn.onclick=()=>{const d=rows[btn.dataset.day];modal('تفاصيل '+d.date,table(['الحركة','وقت الخادم','دقة الموقع','المرفقات'],d.events.map(e=>[esc(labels[e.event_type]),esc(localDisplay(e.received_at)),(e.accuracy===null?'الموقع غير موجود بالسجل القديم':esc(e.accuracy)+' متر · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+encodeURIComponent(e.latitude+','+e.longitude)+'">الموقع</a>'),(e.photo_id?'<a href="file.php?id='+e.photo_id+'" target="_blank" rel="noopener">الصورة</a>':'—')+(e.evidence_id?' · <a href="file.php?id='+e.evidence_id+'" target="_blank" rel="noopener">السند</a>':'')]))+punchDetails39(d)+(typeof presenceDayDetails200==='function'?presenceDayDetails200(d):''));});};
 if(typeof mountOperationalChrome68==='function')mountOperationalChrome68();
 $('#runReport').onclick=()=>load().catch(e=>toast(e.message));
 $('#exportReport').onclick=()=>openExport('attendance',{user_id:$('#reportUser').value,from:$('#reportFrom').value,to:$('#reportTo').value});
 const selected73=typeof employeeValue73==='function'?employeeValue73():0;
 if(selected73){const select=$('#reportUser');if(![...select.options].some(o=>Number(o.value)===selected73)){select.insertAdjacentHTML('beforeend','<option disabled value="'+selected73+'">'+esc(uiText('الموظف خارج نطاق هذا التبويب'))+'</option>');select.value=String(selected73);select.dataset.employeeInitial73='1';$('#runReport').disabled=$('#exportReport').disabled=true;return;}select.value=String(selected73);}
 $('#reportUser').dataset.employeeInitial73='1';
 await load();
}
function downloadCsv(name,rows){const cell=x=>'"'+String(x??'').replace(/^[=+@-]/,"'").replace(/"/g,'""')+'"';const blob=new Blob(['\ufeff'+rows.map(r=>r.map(cell).join(',')).join('\r\n')],{type:'text/csv;charset=utf-8'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;a.click();URL.revokeObjectURL(a.href);}
async function trackingView(){if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 const rows=await api('locations'),stale=Number(boot.settings.tracking_stale??1200);
 $('#page').innerHTML=head('المتابعة الميدانية','آخر نقطة مسجلة فقط؛ التوقف أو انقطاع التحديث ليس دليل مخالفة.')+
 '<div class="columns"><section class="card"><h2>المناديب</h2>'+table(['المندوب','آخر تحديث','الدقة','الحالة','الموقع'],rows.map(r=>[employeeNameMarkup143(r.id,r.name,r),esc(localDisplay(r.received_at)),r.accuracy?esc(r.accuracy)+' متر':'—',badge(!r.received_at?'none':(Date.now()+serverOffset-new Date(r.received_at.replace(' ','T')+'Z').getTime()>stale*1000?'stale':'active')),r.latitude?'<button data-map="'+r.id+'">عرض</button>':'—']))+'</section><section class="card"><h2>خريطة النقطة المختارة</h2><div id="mapPanel" class="map-panel">اختر مندوبًا لعرض آخر نقطة</div></section></div>';
 document.querySelectorAll('[data-map]').forEach(btn=>btn.onclick=()=>{const r=rows.find(r=>r.id==btn.dataset.map);$('#mapPanel').innerHTML='<iframe title="آخر موقع للمندوب" loading="lazy" referrerpolicy="no-referrer" src="https://www.google.com/maps?q='+encodeURIComponent(r.latitude+','+r.longitude)+'&output=embed"></iframe>';});
}
async function auditView(){const rows=await api('audit');$('#page').innerHTML=head('سجل التغييرات','سجل قراءة فقط للتعديلات والموافقات.')+'<section class="card">'+table(['الوقت','المسؤول','العملية','السجل','التفاصيل'],rows.map(r=>[esc(localDisplay(r.created_at)),employeeNameMarkup143(r.user_id,r.name??'النظام',r),esc(r.action),esc(r.subject),esc(r.detail_json)]))+'</section>';}
async function payrollView(){
 const rows=await api('payroll_list');$('#page').innerHTML=head('كشوف الرواتب','مسودات قابلة للمراجعة؛ النسخة المعتمدة تحتفظ بنتائج حسابها.')+
 (boot.user.role==='admin'?'<div class="toolbar"><input id="payrollMonth" type="month"><button id="buildPayroll" class="primary">إنشاء مسودات الشهر</button><button id="exportPayroll">تصدير الكشف</button></div>':'')+'<section class="card">'+table(['الموظف','الشهر','الصافي','الحالة','الظهور','المراجعة'],rows.map(r=>[employeeNameMarkup143(r.user_id,r.name,r),esc(r.month_key),esc(r.net_amount)+' '+esc(boot.company.currency),badge(r.status),JSON.parse(r.calculation_json).payroll_visible===false?'بنك فقط — مستحقات داخلية':'كشف الرواتب العام','<button data-payroll="'+r.id+'">تفاصيل</button>']))+'</section>';
 if($('#buildPayroll'))$('#buildPayroll').onclick=async()=>{try{await api('payroll_build',{month:$('#payrollMonth').value});await payrollView();}catch(e){toast(e.message);}};
 if($('#exportPayroll'))$('#exportPayroll').onclick=()=>openExport('payroll',{month:$('#payrollMonth').value});
 document.querySelectorAll('[data-payroll]').forEach(btn=>btn.onclick=()=>{const r=rows.find(r=>r.id==btn.dataset.payroll),c=JSON.parse(r.calculation_json);modal('كشف '+r.name+' — '+r.month_key,table(['الأساسي','الهاتف','المواصلات','السكن','أخرى','العمولات','إضافات أخرى','الخصومات','الصافي'],[[esc(c.salary),esc(c.allowances?.telephone??0),esc(c.allowances?.transport??0),esc(c.allowances?.housing??0),esc(Number(c.allowances?.other??0)+Number(c.friday?.amount??0)),esc((c.commission_items??[]).reduce((n,x)=>n+Number(x.amount),0)),esc(Number(c.addition)-Object.values(c.allowances??{}).reduce((n,x)=>n+Number(x),0)-Number(c.friday?.amount??0)-(c.commission_items??[]).reduce((n,x)=>n+Number(x.amount),0)),esc(c.deduction),esc(r.net_amount)]])+table(['عدد الجمعات','مبلغ الجمعات'],[[(c.friday?.dates??[]).length,c.friday?.amount??0]])+'<p class="muted">'+esc(c.note)+'</p><p>'+esc(c.flags.join('\n'))+'</p>'+(r.status==='draft'&&boot.user.role==='admin'?'<button id="approvePayroll" class="primary">اعتماد الكشف</button>':''));financialDecisionButton199(r,'payroll');if($('#approvePayroll'))$('#approvePayroll').onclick=async()=>{try{await api('payroll_approve',{id:r.id});$('#modal').close();await payrollView();}catch(e){toast(e.message);}};});
}
async function policies(){return policiesUnified32();}
function policyModal(row={},lockedUser=null,management=null){
 const screenState371=policyScreenState371();
 const p={...(policyDefaults32||boot.policy.values),...(row.value_json?JSON.parse(row.value_json):{})};
 if(p.weekly)p.weekdays=Array.from({length:7},(_,i)=>i).filter(i=>!p.weekly[i]?.rest);
 const weekdays=['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
 modal(scheduleMeta38(row).kind==='temporary'?'إعداد جدول مؤقت':Number(lockedUser??row.user_id)?'الدوام الأساسي وقواعد الموظف':'القواعد والدوام الافتراضي','<form id="modalForm">'+input('label','اسم السياسة',row.label??'دوام العمل')+'<label>تطبيق على<select name="user_id">'+userOptions(row.user_id??0,true)+'</select></label>'+
 '<div class="form-grid">'+input('effective_from','تاريخ بداية السريان',management?.mode==='edit'?row.effective_from:management?.mode==='version'?(row.effective_from>=nextPolicyDate32()?nextDayPolicy32(row.effective_from):nextPolicyDate32()):dateNow(),'date','required')+scheduleForm38(row,lockedUser,management)+input('start','الحضور',p.start,'time','required')+input('end','الانصراف',p.end,'time','required')+input('grace_minutes','سماح التأخير الشهري بالدقائق',p.grace_minutes,'number','min="0"')+input('break_minutes','الاستراحة بالدقائق',p.break_minutes,'number','min="0"')+
 input('max_accuracy','أقصى خطأ موقع بالمتر',p.max_accuracy,'number','min="1"')+input('location_max_age','حداثة الموقع بالثواني',p.location_max_age,'number','min="1"')+input('photo_max_age','حداثة الصورة بالثواني',p.photo_max_age,'number','min="1"')+'</div>'+
 policyWeeklyFields32(p)+'<h3>أيام العمل</h3>'+[6,0,1,2,3,4,5].map(i=>check('weekday'+i,weekdays[i],p.weekdays.includes(i))).join('')+
 '<label>تواريخ العطلات — تاريخ YYYY-MM-DD لكل سطر<textarea name="holiday_dates">'+esc((p.holiday_dates??[]).join('\n'))+'</textarea></label><div id="policyExtras38"><hr>'+check('office_photo','صورة مباشرة لموظفي المكاتب',p.office_photo)+check('rep_photo','صورة مباشرة للمناديب',p.rep_photo)+check('rep_evidence','مرفق أول وآخر سند كدليل حضور فقط',p.rep_evidence)+check('rep_morning_external','السماح بحضور المندوب الصباحي من الخارج',p.rep_morning_external)+
 (function(){const staff=users.find(u=>Number(u.id)===Number(lockedUser??row.user_id));const employee=!!Number(lockedUser??row.user_id);let presenceCard='';if(typeof presenceModalOpen200==='function'){const opened=presenceModalOpen200(p,row,staff,policyDefaults32,management,{employee});presenceCard=opened.fields;}else{const state=typeof presenceFormState200==='function'?presenceFormState200(row,staff,policyDefaults32,{form:management?.presence_form,from:typeof presenceModalFrom200==='function'?presenceModalFrom200(row,management):row.effective_from,employee}):{source:typeof presenceSource200==='function'?presenceSource200(staff):'default',exempt:false};if(typeof presenceApplyState200==='function')presenceApplyState200(p,state);else if(state.source==='own'&&state.setup)p.presence_setup=state.setup;presenceCard=typeof presencePolicyFields200==='function'?presencePolicyFields200(p,{employee,exempt:!!state.exempt,source:state.source,setup:state.setup,defaults:policyDefaults32}):'';}return '<hr><h3>نسيان البصمة</h3>'+punchPolicyFields39(p,{omit:['presence'],presenceCard});})()+'<div hidden>'+check('forget_enabled','تطبيق جزاء نسيان البصمة',p.forget_enabled)+'<div id="forgetBase36">'+input('missing_minutes','جزاء النسيان الأساسي بالدقائق',p.missing_minutes,'number','min="0" step="1"')+'</div>'+check('forget_escalation_enabled','تصعيد الجزاء عند التكرار',p.forget_escalation_enabled??((p.forget_tiers??[]).length>0))+'<div id="forgetEscalation36">'+
 '<div class="form-grid"><label>فترة العد<select name="forget_period"><option value="month">شهرية</option><option value="year" '+(p.forget_period==='year'?'selected':'')+'>سنوية</option></select></label><label>وحدة العد<select name="forget_unit"><option value="punch">كل بصمة ناقصة</option><option value="day" '+(p.forget_unit==='day'?'selected':'')+'>اليوم ذو البصمة الناقصة</option></select></label><label>التصعيد<select name="penalty_mode"><option value="replace">بدل الجزاء الأساسي</option><option value="add" '+(p.penalty_mode==='add'?'selected':'')+'>بالإضافة للجزاء الأساسي</option></select></label></div>'+
 '<label>شرائح التصعيد — سطر لكل شريحة: بداية العدد، خصم الأيام<textarea name="tiers" placeholder="4, 1.5">'+esc((p.forget_tiers??[]).map(t=>t.from+', '+t.days).join('\n'))+'</textarea></label></div></div></div>'+(management?input('change_reason','سبب التعديل — اختياري','','text'):'')+saveButton()+'</form>',
 form=>{if(typeof presenceSaveGuard200==='function')presenceSaveGuard200(form);const raw=Object.fromEntries(new FormData(form)),v={...p};if(form.elements.namedItem('schedule_kind')?.value!=='temporary'){if(typeof presenceSaveValues200==='function')Object.assign(v,presenceSaveValues200(form,v));else{v.punch_rules39=punchPolicyRead39(form);const src=form.elements.namedItem('presence_source');if(src){v.presence_source=src.value;if(src.value==='own')v.presence_setup=presenceRead200(form);else{delete v.presence_setup;if(v.punch_rules39){const next={...v.punch_rules39};delete next.presence;v.punch_rules39=next;}}}else if(typeof presenceRead200==='function')v.presence_setup=presenceRead200(form);}}else if(v.punch_rules39)v.punch_rules39={...v.punch_rules39};['start','end','forget_period','forget_unit','penalty_mode'].forEach(k=>v[k]=form[k].value);['grace_minutes','break_minutes','max_accuracy','location_max_age','photo_max_age','missing_minutes'].forEach(k=>v[k]=Number(form[k].value));['office_photo','rep_photo','rep_evidence','rep_morning_external','forget_enabled','forget_escalation_enabled'].forEach(k=>v[k]=form[k].checked);v.holiday_dates=raw.holiday_dates.split('\n').map(x=>x.trim()).filter(Boolean);v.weekdays=weekdays.map((_,i)=>i).filter(i=>form['weekday'+i].checked);v.weekly_mode=raw.weekly_mode;v.location_id=raw.location_id;v.weekly=Array.from({length:7},(_,i)=>({rest:!v.weekdays.includes(i),start:raw.weekly_mode==='variable'?(raw['day_start'+i]||p.weekly?.[i]?.start||v.start):v.start,end:raw.weekly_mode==='variable'?(raw['day_end'+i]||p.weekly?.[i]?.end||v.end):v.end}));return savePolicy392({...(management?{id:row.id,manage_mode:management.mode,fingerprint:management.fingerprint,reason:form.dataset.recordPairs173?employeeDataOriginal137(form.elements.change_reason):raw.change_reason}:{}),label:typeof employeeDataOriginal137==='function'?employeeDataOriginal137(form.elements.label):raw.label,...(form.dataset.recordPairs173?{_edit170:recordMeta173(form)}:{}),user_id:lockedUser??raw.user_id,effective_from:raw.effective_from,...schedulePayload38(form),values:v});},{inlineErrors:true,onSaved:async()=>{try{await refreshPolicy371(screenState371,false);}catch(error){toast(uiText('تم الحفظ، لكن تعذر تحديث العرض. أعد فتح الشاشة.')+' '+error.message);}}});
 if(Number(lockedUser??row.user_id))recordBind173($('#modalForm'),'policy',['label','change_reason']);
 bindSchedule38();bindPolicyWeekly32();bindForgetPolicy36();if(typeof presenceBind200==='function')presenceBind200($('#modalForm'));if(typeof punchBind39==='function')punchBind39($('#modalForm'));if(typeof presenceWatchFrom200==='function')presenceWatchFrom200($('#modalForm'),row,users.find(u=>Number(u.id)===Number(lockedUser??row.user_id)),policyDefaults32,management);if(lockedUser!==null){const select=$('#modalForm select[name=user_id]');select.value=String(lockedUser);select.disabled=true;}bindCapturePolicy361();
}
async function settingsView(){
 const s=boot.settings;
 $('#page').innerHTML=head('إعدادات الإدارة','هوية الشركة وقواعد التشغيل من هنا؛ ألوان التصميم ثابتة.')+
 '<form id="settingsForm"><section class="card settings-section"><h3>هوية الشركة</h3><div class="form-grid">'+input('company_name','اسم الشركة بالعربية',s.company_name)+input('company_name_en','اسم الشركة بالإنجليزية',s.company_name_en)+input('app_name31','اسم أيقونة التطبيق',s.app_name31??'Click')+input('site_url','رابط الموقع',s.site_url,'url')+input('timezone','المنطقة الزمنية IANA',s.timezone)+input('currency','العملة',s.currency)+input('session_hours','مدة الخمول قبل انتهاء الجلسة بالساعات',s.session_hours,'number','min="1" max="168"')+'</div><label>الشعار<input id="logoUpload" type="file" accept="image/png,image/jpeg,image/webp"></label><button type="button" id="uploadLogo">رفع الشعار</button></section>'+
 '<section class="card settings-section"><h3>التنبيهات ونهاية الخدمة</h3><div class="form-grid">'+input('leave_alert_first','تنبيه رصيد الإجازات الأول',s.leave_alert_first??30,'number','min="0"')+input('leave_alert_high','تنبيه الرصيد المرتفع',s.leave_alert_high??60,'number','min="0"')+input('leave_first_months','أشهر استحقاق أول طلب إجازة',s.leave_first_months??9,'number','min="0"')+input('eos_day_divisor','مقسوم أجر اليوم لنهاية الخدمة',s.eos_day_divisor??'','number','min="1"')+input('eos_year_divisor','مقسوم سنة الخدمة',s.eos_year_divisor??365,'number','min="1"')+check('eos_rule_confirmed','اعتماد قواعد نهاية الخدمة بعد المراجعة',s.eos_rule_confirmed)+'</div></section>'+
 '<section class="card settings-section"><h3>المرضيات والإجازات</h3><div class="form-grid"><label>بداية السنة<select name="sick_year"><option value="calendar">يناير–ديسمبر</option><option value="hire" '+(s.sick_year==='hire'?'selected':'')+'>من تاريخ التعيين</option></select></label>'+input('sick_full_days','أيام المرضية بأجر كامل',s.sick_full_days??'','number','min="0"')+input('sick_half_days','أيام المرضية بنصف أجر',s.sick_half_days??'','number','min="0"')+'<input type="hidden" name="leave_year" value="'+esc(s.leave_year??'calendar')+'">'+input('annual_leave_days','رصيد الإجازات السنوية المرجعي',s.annual_leave_days??'','number','min="0"')+input('leave_accrual_divisor','مقسوم الاستحقاق اليومي',s.leave_accrual_divisor??365,'number','min="1"')+'<input type="hidden" name="leave_accrual_mode" value="daily"><input type="checkbox" name="leave_exclude_approved_days" checked hidden>'+'</div><small>شرائح المرضية تحفظ وقت اعتمادها؛ تغيير الحدود لا يعيد حساب الطلبات المعتمدة تلقائيًا.</small></section>'+
 '<section class="card settings-section"><h3>المتابعة الميدانية</h3>'+check('tracking_enabled','تفعيل التحديث أثناء الدوام وطالما الصفحة نشطة',s.tracking_enabled)+'<div class="form-grid">'+input('tracking_interval','الفاصل بين التحديثات بالثواني',s.tracking_interval,'number','min="60"')+input('tracking_stale','اعتبار الموقع قديمًا بعد ثوانٍ',s.tracking_stale,'number','min="60"')+'</div><small>لا يضمن الويب استمرار المتابعة مع قفل الشاشة. لا جزاء تلقائي بسبب توقف التحديث.</small></section>'+
 '<section class="card settings-section"><h3>حساب الرواتب</h3>'+''+''+'<div class="form-grid">'+'<input type="hidden" name="salary_proration" value="calendar"><input type="hidden" name="salary_divisor" value="26">'+input('work_hours','ساعات أجر اليوم',s.work_hours,'number','min="1" step=".5"')+input('retention_days','حفظ نقاط المتابعة الدورية بالأيام',s.retention_days,'number','min="1"')+'</div><small>تشغيل مهمة الصيانة اليومية يطبق مدة نقاط المتابعة فقط. المستندات والبصمات لا تُحذف تلقائيًا. اضبط النسخ من شاشة النسخ الاحتياطي والاسترجاع.</small></section><button class="primary">حفظ إعدادات الإدارة</button></form>';
 await settingsBenefits198();
 $('#settingsForm').onsubmit=async e=>{e.preventDefault();try{const f=e.target,raw=Object.fromEntries(new FormData(f)),values={};['company_name','company_name_en','app_name31','site_url','timezone','currency','sick_year','leave_year','leave_accrual_mode','salary_proration'].forEach(k=>values[k]=raw[k]);['session_hours','sick_full_days','sick_half_days','annual_leave_days','leave_accrual_divisor','tracking_interval','tracking_stale','salary_divisor','work_hours','retention_days','leave_alert_first','leave_alert_high','leave_first_months','eos_day_divisor','eos_year_divisor'].forEach(k=>values[k]=raw[k]===''?null:Number(raw[k]));['tracking_enabled','leave_exclude_approved_days','eos_rule_confirmed'].forEach(k=>values[k]=f[k].checked);await api('settings_save',{values});await loadBoot();shell();await settingsView();toast('تم حفظ الإعدادات');}catch(e){toast(e.message);}};
 $('#uploadLogo').onclick=async()=>{try{const file=$('#logoUpload').files[0];if(!file)throw Error('اختر الشعار أولًا');const f=new FormData();f.set('file',file);await api('logo_upload',f);await loadBoot();shell();await settingsView();toast('تم تحديث الشعار');}catch(e){toast(e.message);}};
}
window.addEventListener('pagehide',()=>{closeCamera();stopLocation();});
if(typeof connection32!=='undefined'){connection32.install();view=connection32.resumeScreen()||view;}
start();

async function categorizedRecords61(module){if(recordModules173.includes(module))return recordWorkspace173(module);if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 let rows=[];const epoch=navigationEpoch32,meta=boot.modules[module],canAdd=teamPermission(module,'create')||(meta.employee&&personalPermission34(module,'create'));
 $('#page').innerHTML=head(meta.label,'الطلبات والقرارات والمرفقات محفوظة حسب صلاحياتك.',canAdd?'<button id="addRecord" class="primary">+ إضافة</button>':'')+recordTabsHtml49(module)+'<section class="card" id="moduleRecords32" data-record-search-host32><div data-record-results32>'+loadingMarkup()+'</div></section>';
 const add=$('#addRecord');if(add){add.disabled=recordDisplayEnabled87(module)||boot.user.role!=='admin';add.onclick=()=>recordModal(module);}
 if(module==='request'&&canAdd&&requestMode50==='attendance'){const b=document.createElement('button');b.textContent=uiText('+ مراجعة واقعة حضور');b.onclick=punchRequestModal39;b.disabled=boot.user.role!=='admin';add.after(b);}
 const refresh=bindRecordTabs49(module,()=>rows),host=$('#moduleRecords32'),results=host.querySelector('[data-record-results32]');results.innerHTML=loadingMarkup();
 remoteRecordSearch32(host,async(q,current)=>{const loaded=await api('list',null,{module,q,...(module==='request'?{request_scope50:requestMode50}:{})});if(!current())return;rows=loaded;refresh();});
 if(typeof mountEmployeeFilter73==='function')mountEmployeeFilter73();
 if(typeof prepareHeader79==='function')prepareHeader79(module);
 if(typeof mobileTabsRefresh33==='function')mobileTabsRefresh33();applyInterfaceLanguage();
 const recordsReady87=api('list',null,{module,...(module==='request'?{request_scope50:requestMode50}:{})});
 const choicesReady87=(boot.user.role!=='admin'?api('user_choices29',null,{screen:module}):Promise.resolve(null)).then(choices=>{
  if(epoch!==navigationEpoch32||!host.isConnected)return;
  if(choices)users=choices;
  if(add&&recordDisplayEnabled87(module)){add.disabled=false;add.removeAttribute('aria-busy');}
  return choices;
 });
 const [loaded,choices]=await Promise.all([recordsReady87,choicesReady87]);
 if(epoch!==navigationEpoch32||!host.isConnected)return;
 rows=loaded;if(choices)users=choices;
 if(add){add.disabled=false;if(recordDisplayEnabled87(module))add.removeAttribute('aria-busy');}const incident=document.querySelector('#recordTabs49 .top-actions49 button:not(#addRecord)');if(incident)incident.disabled=false;
 refresh();

}
