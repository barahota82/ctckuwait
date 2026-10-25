'use strict';
// Hubs are navigation only. Each child keeps its own backend permission key.
const portalHubs32=[
 ['hub_staff','إدارة الموظفين',['users','workforce','employment']],
 ['hub_attendance50','الحضور والانصراف',['report','request_attendance50','external','tracking','proof']],
 ['hub_leave','الإجازات والمرضيات',['leave','sick','leave_overview','resumption']],
 ['hub_payroll35','الرواتب والعمولات',['payroll','commissions','collection37','payroll_review35']],
 ['hub_payment50','الصرف والتحويلات',['bank','vouchers37','payments26','payout_status']],
 ['hub_debt','السلف والمديونيات',['loan','v28_debt']],
 ['hub_contracts99','تسجيل العقود',['contract_sales99','contract_staff99','contract_agents99']],
 ['hub_receipts99','تسجيل مبالغ التحصيل',['receipt_company99','receipt_click99','receipt_afaq99','receipt_agents99']],
 ['hub_payroll_entry126','تسجيل لإعداد الرواتب',['payroll_entry_click126','payroll_entry_afaq126']],
 ['hub_documents50','مستندات الموظفين',['document']],
 ['hub_work_permit85','أذونات العمل',['work_permit']],
 ['hub_video','الإقرارات بالفيديو',['video_auto','video_custom','video_receipts']],
 ['hub_companies','الشركات والتراخيص',['companies','v28_files','v28_documents','v28_archive']],
 ['hub_reports50','التقارير والسجلات',['analytics','v26_history','audit']],
 ['hub_content','الموقع التعريفي',['publication','cms','offers','public_settings','languages']],
 ['hub_customers','خدمة العملاء',['customer_cases','customer_departments','customer_ratings']],
 ['hub_import','تجهيز وصيانة البيانات',['import']]
];
const personalHubs50=[
 ['hub_personal_attendance50','الحضور والانصراف',['report','request_attendance50','external','proof']],
 ['hub_personal_leave50','الإجازات والمرضيات',['leave','sick','resumption']],
 ['hub_personal_finance50','الرواتب والمستحقات',['payroll','commissions','loan','debt_statement95']],
 ['hub_personal_profile50','ملفي الوظيفي',['users','document','employment','personal_history']],
 ['hub_personal_decisions50','القرارات والتقييمات',['discipline','evaluation']],
 ['hub_personal_video50','الإقرارات',['video_receipts']]
];
let requestMode50='admin';
function personalNavigation50(){return boot.access_mode==='personal'&&boot.user.role!=='admin';}
function activeHubs50(){return personalNavigation50()?personalHubs50:portalHubs32;}
function hubTitle32(hub){return hub[1];}
function hubFor32(key){
 const aliases={payroll_settlement35:'bank',adjustment:'addition',batch_addition29:'addition',batch_friday29:'allowances'};key=aliases[key]||key;
 if(key==='request'&&requestMode50==='attendance')key='request_attendance50';
 return activeHubs50().find(h=>h[0]===key||h[2].includes(key));
}
function tabTitle32(key){
 const direct182={delegation_managers199:'تفويض المديرين',policy_general182:'القواعد والدوام الافتراضي',policy_staff182:'دوام الموظفين',policy_temporary182:'الجداول المؤقتة',evaluation_settings182:'إعدادات التقييم'};if(direct182[key])return direct182[key];
 if(key==='dashboard'&&personalNavigation50())return 'ملخصي';
 const named={eos:'تسوية نهاية الخدمة',leave_cash:'تسوية الإجازات النقدية',exit_permissions183:'أذونات الخروج',payroll_entry_click126:'شركة كليك',payroll_entry_afaq126:'شركة آفاق',contract_sales99:'مسؤولو المبيعات',contract_staff99:'موظفو الشركة',contract_agents99:'المناديب',receipt_company99:'تحصيل الشركة',receipt_click99:'تحصيل أونلاين كليك',receipt_afaq99:'تحصيل أونلاين آفاق',receipt_agents99:'تحصيل المناديب',debt_statement95:'كشف حساب السلف',attendance:'بصمة الدوام',request:'الطلبات الإدارية',request_attendance50:'وقائع الحضور',addition:'الإضافات الشهرية',allowances:'البدلات والجمعات',personal_history:'تاريخ بياناتي السابقة'};
 if(named[key])return named[key];
 if(personalNavigation50()){const labels={users:'بياناتي وملفي',employment:'إجراءات خدمتي',resumption:'مباشرة عملي',document:'مستنداتي وعقودي',custody:'عهدي',discipline:'القرارات الموجهة لي',evaluation:'تقييماتي',notifications:'إشعاراتي',payroll:'كشوف راتبي',commissions:'عمولاتي',proof:'إثبات تواجدي',report:'سجل دوامي',request:'طلباتي الإدارية',loan:'السلف ومديونيتي',leave:'إجازاتي',sick:'مرضياتي'};if(labels[key])return labels[key];}
 return navigation30.flatMap(g=>g[1]).find(i=>i[0]===key)?.[1]||key;
}
function visibleTab32(k){return screenAllowed(k);}
function allowedTabs32(hub){return hub[2].filter(visibleTab32);}
function resolveHub32(key){
 const legacy={hub_allowances:'users',allowances:'users',entitlement:'users',addition:'payroll_entry_click126',batch_addition29:'payroll_entry_click126',batch_friday29:'settings',payroll_settings35:'settings',hub_field:'hub_attendance50',hub_additions:'users',hub_maintenance:'hub_import'};key=legacy[key]||key;
 const hub=activeHubs50().find(h=>h[0]===key);if(hub)key=allowedTabs32(hub)[0]||'dashboard';
 if(key==='request_attendance50'){requestMode50='attendance';return 'request';}if(key==='request')requestMode50='admin';return key;
}
function permissionTree32(){return navigation30.map(([title,items])=>[title,items.flatMap(i=>i[0]==='policies'?[['policy_general','السياسة العامة'],['policy_staff','جداول الموظفين']]:i[0]==='users'?[i,['bank_details','بيانات الحساب البنكي']]:i[0]==='v28_locations'?[i,['location_capture40','استخدام الموقع الحالي لإعداد الدوام']]:[i])]);}
const portalAreas32=[
 ['المتابعة الرئيسية',['dashboard','notifications','attendance']],
 ['شؤون الموظفين',['hub_staff','hub_work_permit85','hub_documents50','custody','discipline','evaluation','request']],
 ['الحضور والإجازات',['hub_attendance50','exit_permissions183','hub_leave']],
 ['الشؤون المالية',['hub_debt','hub_contracts99','hub_receipts99','hub_payroll_entry126','hub_payroll35','hub_payment50','leave_cash','eos']],
 ['الإقرارات',['hub_video']],['الشركات',['hub_companies']],
 ['التقارير',['hub_reports50']],['الموقع التعريفي',['hub_content']],
 ['خدمة العملاء',['hub_customers']],['النظام',['policy_general182','policy_staff182','policy_temporary182','v28_locations','permissions','delegation_managers199','settings','backups','evaluation_settings182','hub_import']]
];
function tabMenu32(){
 const areas=personalNavigation50()?[['الرئيسية',['dashboard','notifications','attendance']],...personalHubs50.map(h=>[h[1],[h[0]]]).concat([['أذونات الخروج',['exit_permissions183']],['عهدي',['custody']],['طلباتي الإدارية',['request']]])]:portalAreas32;
 const rows=areas.map(([title,items],index)=>{const buttons=items.filter(key=>{const h=activeHubs50().find(h=>h[0]===key);return h?allowedTabs32(h).length:visibleTab32(key);}).map(key=>{const h=activeHubs50().find(h=>h[0]===key);return navItem(key,h?hubTitle32(h):tabTitle32(key));});if(!buttons.length)return '';
 return personalNavigation50()||index===0?buttons.join(''):'<details class="nav-group28" data-group="'+esc(title)+'"><summary>'+esc(uiText(title))+'<span class="nav-arrow28">⌄</span></summary>'+buttons.join('')+'</details>';}).join('');
 return '<div class="nav-section">'+esc(uiText(personalNavigation50()?'مساحتي':'مساحة العمل'))+'</div>'+rows;
}
function syncPortalTabs32(key=view){const host=document.querySelector('#portalTabs32');if(!host)return;if(typeof recordModules173!=='undefined'&&recordModules173.includes(key)){const activeKey=hubFor32(key)?.[0]||key;document.querySelectorAll('.sidebar .nav-item').forEach(n=>{const active=n.dataset.view===activeKey;n.classList.toggle('active',active);if(active)n.closest('details')?.setAttribute('open','');});host.hidden=true;host.replaceChildren();host.classList.remove('has-actions49','header-stack77','has-document54');return;}if(key==='users'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134()){host.hidden=true;host.replaceChildren();host.classList.remove('has-actions49','header-stack77');document.querySelectorAll('.sidebar .nav-item').forEach(n=>{const active=n.dataset.view==='hub_staff'||n.dataset.view==='users';n.classList.toggle('active',active);if(active)n.closest('details')?.setAttribute('open','');});if(typeof scheduleStickyTabs48==='function')scheduleStickyTabs48();return;}host.classList.toggle('document-modes86',key==='document');host.classList.remove('has-actions49','has-document54','header-stack77');host.classList.toggle('operational-hub68',typeof operationalScreens68!=='undefined'&&operationalScreens68.includes(key)&&(key!=='request'||requestMode50==='attendance'));const hub=hubFor32(key);host.classList.toggle('employee-documents57',hub?.[0]==='hub_documents50');const tabs=hub?allowedTabs32(hub):[];host.hidden=!tabs.length;host.innerHTML=tabs.length?'<div class="portal-hub-title32">'+esc(uiText(hubTitle32(hub)))+'</div><nav class="portal-tabs32" aria-label="'+esc(uiText(hubTitle32(hub)))+'">'+tabs.map(k=>'<button type="button" data-view="'+esc(k)+'"'+(k===(key==='request'&&requestMode50==='attendance'?'request_attendance50':({adjustment:'addition',batch_addition29:'addition',batch_friday29:'allowances'}[key]??key))?' aria-current="page"':'')+'>'+esc(uiText(tabTitle32(k)))+'</button>').join('')+'</nav>':'';
 if(key==='work_permit'&&typeof preparePermitTabs89==='function')preparePermitTabs89(host);
 if(key==='document'&&typeof prepareDocumentModes86==='function')prepareDocumentModes86(host);
 document.querySelectorAll('.sidebar .nav-item').forEach(n=>{const active=n.dataset.view===(hub?.[0]||key);n.classList.toggle('active',active);if(active)n.closest('details')?.setAttribute('open','');});
}

// Keep secondary strips below the complete hub card, including wrapped translations.
let stickyHub48=null,stickyFrame48=0;
const stickySize48=new ResizeObserver(()=>scheduleStickyTabs48());
function refreshStickyTabs48(){
 const main=document.querySelector('#portalTabs32');const hub=main&&!main.hidden?main:document.querySelector('#recordTabs49.header-stack77');
 if(hub!==stickyHub48){if(stickyHub48)stickySize48.unobserve(stickyHub48);stickyHub48=hub;if(hub)stickySize48.observe(hub);}
 const height=hub&&!hub.hidden?Math.ceil(hub.getBoundingClientRect().height):0;
 const value=height+'px';if(document.documentElement.style.getPropertyValue('--hub-height48')!==value)document.documentElement.style.setProperty('--hub-height48',value);
}
function scheduleStickyTabs48(){if(stickyFrame48)return;stickyFrame48=requestAnimationFrame(()=>{stickyFrame48=0;refreshStickyTabs48();});}
const stickyTabsObserver48=new MutationObserver(records=>{if(records.some(r=>{const el=r.target.nodeType===1?r.target:r.target.parentElement;return !el?.closest('[data-live-clock32],#toast,.select-options32,.date-calendar32');}))scheduleStickyTabs48();});
stickyTabsObserver48.observe(document.querySelector('#app')||document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['hidden']});
window.addEventListener('resize',scheduleStickyTabs48,{passive:true});
scheduleStickyTabs48();

function installScreenActions50(){
 const page=document.querySelector('#page'),host=document.querySelector('#portalTabs32');if(!page||!host||host.hidden)return;
 let slot=host.querySelector('[data-top-actions49]');
 const actions=[...page.querySelectorAll('.page-head button,.page-head a.button')];
 const extra=personalNavigation50()?[]:(view==='addition'||view==='adjustment'?[['batch_addition29','إدخال جماعي'],['adjustment','إضافات تحتاج اعتمادًا']]:view==='allowances'||view==='batch_friday29'?[['batch_friday29','إدخال الجمعات جماعيًا']]:[]);
 if(!actions.length&&!extra.some(([k])=>screenAllowed(k)))return;
 if(!slot){slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';host.append(slot);}host.classList.add('has-actions49');
 actions.forEach(n=>{if(typeof adoptScreenAction61==='function')adoptScreenAction61(n,slot);else slot.append(n);});
 for(const [key,title]of extra){if(!screenAllowed(key)||host.querySelector('[data-tool50="'+key+'"]'))continue;const button=document.createElement('button');button.type='button';button.dataset.tool50=key;button.textContent=uiText(title);button.onclick=()=>navigate(key);slot.append(button);}
 if(typeof finishActions32==='function')finishActions32();
}

// Build permission-based actions before waiting for record data.
function prepareScreenChrome61(key){
 if(typeof recordModules173!=='undefined'&&recordModules173.includes(key))return;
 if(key==='users'&&typeof employeeInlineEnabled134==='function'&&employeeInlineEnabled134())return;
 if(typeof prepareEmployeeFilter73==='function')prepareEmployeeFilter73(key);
 const host=document.querySelector('#portalTabs32');if(!host||host.hidden)return;
 let action=null;
 if(typeof financeShellKeys101!=='undefined'&&financeShellKeys101.has(key)&&(teamPermission(key,'create')||teamPermission(key,'edit')))action=['financeAdd103','تسجيل وتعديل'];
 else if(typeof payrollEntryFilter127==='function'&&payrollEntryFilter127(key))action=['entryAdd127','تسجيل وتعديل'];
 else if(key==='users'&&boot.user.role==='admin')action=['addUser','+ إضافة موظف'];
 else if(key==='workforce'&&canWrite29('workforce'))action=['newService','تعيين / إعادة تعيين'];
 else if(['work_permit','resumption'].includes(key)&&(canWrite29(key)||(key==='resumption'&&personalPermission34('resumption','create'))))action=['v26New','إضافة سجل'];
 else if(key==='employment'&&boot.modules[key]&&(teamPermission(key,'create')||(boot.modules[key].employee&&personalPermission34(key,'create'))))action=['addRecord','+ إضافة'];
 if(action){const slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';const button=document.createElement('button');button.type='button';button.className='primary';button.dataset.earlyAction61=action[0];button.textContent=uiText(action[1]);button.disabled=true;slot.append(button);host.append(slot);host.classList.add('has-actions49');}
 if(typeof finishActions32==='function')finishActions32();
 if(typeof prepareOperationalHeader68==='function')prepareOperationalHeader68(key);
 if(typeof mobileTabsRefresh33==='function')mobileTabsRefresh33();
 if(typeof prepareHeader79==='function')prepareHeader79(key);
}
function adoptScreenAction61(button,slot){
 const early=slot.querySelector('[data-early-action61="'+button.id+'"],[data-pending-action79="'+button.id+'"]');
 button.dataset.liveAction80='';button.removeAttribute('aria-busy');if(early)early.replaceWith(button);else slot.append(button);
}
