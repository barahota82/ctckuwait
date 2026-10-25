'use strict';
function mobileInput141(){return mobileInteraction142();}
function mobileInteraction142(){return typeof matchMedia==='function'&&matchMedia('(max-width:760px), (hover:none) and (pointer:coarse)').matches;}

const dayNames32=['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
const week32=[6,0,1,2,3,4,5];
const normalize32=s=>String(s??'').normalize('NFKD').replace(/[\u064B-\u065F\u0670\u0640]/g,'').replace(/[أإآٱ]/g,'ا').replace(/ى/g,'ي').replace(/ة/g,'ه').toLowerCase().trim();
const dateDisplay32=s=>/^\d{4}-\d{2}-\d{2}$/.test(String(s))?String(s).split('-').reverse().join('/'):String(s??'—');
function parseDate32(s){const m=String(s).match(/^(\d{2})\/(\d{2})\/(\d{4})$/);if(!m)return '';const iso=m[3]+'-'+m[2]+'-'+m[1],d=new Date(iso+'T12:00:00Z');return !isNaN(d)&&d.toISOString().slice(0,10)===iso?iso:'';}
function dataValue32(k,v){if(v===null||v===undefined)return '—';if(typeof v==='number'){if(/amount|salary|value|paid|received|recovery|bank_approved|outstanding|net_approved/.test(k)&&!/days|unpaid|sick_paid/.test(k))return v.toFixed(3);if(!Number.isInteger(v))return v.toFixed(6).replace(/0+$/,'').replace(/\.$/,'');}if(typeof v==='string'&&/^\d{4}-\d{2}-\d{2}$/.test(v))return dateDisplay32(v);return valueLabel26(v);}
function companyOptions32(selected=''){const cs=boot.company_choices30??[];return '<option value="">'+esc(uiText('كل الشركات'))+'</option>'+[...cs].sort((a,b)=>String(a.code).localeCompare(String(b.code),'en',{numeric:true})).map(c=>'<option value="'+esc(c.code)+'" '+(selected===c.code?'selected':'')+'>'+esc(c.name)+'</option>').join('');}
function departmentOptions32(){return '<option value="">'+esc(uiText('كل الأقسام'))+'</option>'+[...new Set(users.map(u=>u.department).filter(Boolean))].sort().map(d=>'<option value="'+esc(d)+'">'+esc(d)+'</option>').join('');}
function enhanceSelect32(select){
 if(select.dataset.choiceOnly32==='1'||select.id==='interfaceLanguage'||select.dataset.search32||select.multiple||select.size>1||(select.options.length<2&&!select.closest('.leave-employee-filter69'))||select.closest('.date-calendar32'))return;
 select.dataset.search32='1';const box=document.createElement('div');box.className='search-select32';select.before(box);box.append(select);select.classList.add('native-select32');select.tabIndex=-1;
 const input=document.createElement('input');input.type='text';input.autocomplete='off';input.setAttribute('role','combobox');input.setAttribute('aria-expanded','false');input.setAttribute('aria-autocomplete','list');input.setAttribute('aria-label',select.getAttribute('aria-label')||select.closest('label')?.childNodes[0]?.textContent||uiText('بحث واختيار'));const arrow=document.createElement('button');arrow.type='button';arrow.textContent='▾';arrow.setAttribute('aria-label',uiText('فتح القائمة'));const list=document.createElement('div');list.className='select-options32';list.hidden=true;list.setAttribute('role','listbox');list.id='select32-'+Math.random().toString(36).slice(2);input.setAttribute('aria-controls',list.id);box.append(input,arrow,list);let current=-1,query138='';
 const sync=()=>{input.value=select.selectedOptions?.[0]?.textContent??select.options[select.selectedIndex]?.textContent??'';input.disabled=select.disabled;arrow.disabled=select.disabled;};
 const close=()=>{query138='';list.hidden=true;input.setAttribute('aria-expanded','false');sync();};
 const open=(query='')=>{if(select.disabled)return;query138=query;list.replaceChildren();current=-1;const q=normalize32(query);for(const opt of select.options){if(opt.disabled||!normalize32(opt.textContent).includes(q))continue;const b=document.createElement('button');b.type='button';b.setAttribute('role','option');b.setAttribute('aria-selected',String(opt.selected));b.textContent=opt.textContent;b.onclick=()=>{select.value=opt.value;select.dispatchEvent(new Event('change',{bubbles:true}));close();input.focus();close();};list.append(b);}if(!list.children.length){const empty=document.createElement('p');empty.textContent=uiText('لا توجد نتائج');list.append(empty);}if(typeof positionRecordPicker75==='function')positionRecordPicker75(select,box,list,close);list.hidden=false;input.setAttribute('aria-expanded','true');};
 input.onfocus=()=>{open();input.select();};input.oninput=()=>open(input.value);arrow.onclick=()=>list.hidden?(input.focus(),open()):close();input.onkeydown=e=>{const options=[...list.querySelectorAll('button')];if(e.key==='Escape'){close();return;}if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();if(list.hidden)open();current=Math.max(0,Math.min(options.length-1,current+(e.key==='ArrowDown'?1:-1)));options.forEach((b,i)=>b.classList.toggle('focused32',i===current));options[current]?.scrollIntoView({block:'nearest'});}if(e.key==='Enter'&&!list.hidden){e.preventDefault();options[Math.max(0,current)]?.click();}if(e.key==='Tab')close();};
 box.addEventListener('focusout',()=>setTimeout(()=>{if(!box.contains(document.activeElement))close();},0));select.addEventListener('change',sync);select.addEventListener('invalid',()=>{if(mobileInput141())arrow.focus();else input.focus();});new MutationObserver(sync).observe(select,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['disabled','selected']});
 select._refreshChoice138=()=>{if(list.hidden)sync();else{const query=query138;open(query);if(!query)sync();}};
 const mode=()=>{const mobile=typeof matchMedia==='function'&&matchMedia('(max-width:760px), (hover:none) and (pointer:coarse)').matches;select.tabIndex=mobile?0:-1;input.readOnly=mobile;input.inputMode=mobile?'none':'text';if(mobile)close();};select._choiceMode32=mode;mode();sync();
}
function enhanceDate32(original){
 if(original.dataset.date32)return;original.dataset.date32='1';const wrap=document.createElement('div');wrap.className='date-field32';original.before(wrap);wrap.append(original);original.classList.add('date-native32');original.tabIndex=-1;
 const text=document.createElement('input');text.type='text';text.inputMode='numeric';text.placeholder='DD/MM/YYYY';text.setAttribute('aria-label',original.closest('label')?.childNodes[0]?.textContent||uiText('التاريخ'));text.autocomplete='off';text.value=dateDisplay32(original.value||'');const button=document.createElement('button');button.type='button';button.textContent='▦';button.setAttribute('aria-label',uiText('اختر التاريخ'));wrap.append(text,button);
 const sync=()=>{text.value=dateDisplay32(original.value||'');text.disabled=original.disabled;button.disabled=original.disabled;};original.addEventListener('change',sync);original.addEventListener('invalid',()=>{text.setCustomValidity(uiText('أدخل تاريخًا صحيحًا بصيغة يوم/شهر/سنة'));text.reportValidity();});
 text.oninput=()=>{const iso=parseDate32(text.value);text.setCustomValidity(!text.value||iso?'':uiText('أدخل تاريخًا صحيحًا بصيغة يوم/شهر/سنة'));original.value=iso;original.dispatchEvent(new Event('input',{bubbles:true}));};text.onchange=()=>{if(text.checkValidity())original.dispatchEvent(new Event('change',{bubbles:true}));};
 button.onclick=()=>{if(wrap.querySelector('.date-calendar32')){wrap.querySelector('.date-calendar32').remove();return;}document.querySelectorAll('.date-calendar32').forEach(n=>n.remove());const cal=document.createElement('div');cal.className='date-calendar32';cal.setAttribute('role','dialog');cal.setAttribute('aria-label',uiText('اختر التاريخ'));wrap.append(cal);if(typeof positionRecordPicker75==='function')positionRecordPicker75(original,wrap,cal,()=>cal.remove());let date=new Date((original.value||dateNow())+'T12:00:00Z'),year=date.getUTCFullYear(),month=date.getUTCMonth();
 const draw=()=>{cal.replaceChildren();const top=document.createElement('div');top.className='calendar-top32';const prev=document.createElement('button'),next=document.createElement('button'),heading=document.createElement('span');prev.type=next.type='button';prev.textContent=document.documentElement.dir==='rtl'?'›':'‹';next.textContent=document.documentElement.dir==='rtl'?'‹':'›';prev.setAttribute('aria-label',uiText('الشهر السابق'));next.setAttribute('aria-label',uiText('الشهر التالي'));heading.textContent=new Intl.DateTimeFormat(boot.localization.language==='en'?'en-GB':'ar-KW',{month:'long',year:'numeric',timeZone:'UTC'}).format(new Date(Date.UTC(year,month,1)));prev.onclick=()=>{month--;if(month<0){month=11;year--;}draw();};next.onclick=()=>{month++;if(month>11){month=0;year++;}draw();};const chooser=document.createElement('div');chooser.className='calendar-choice114';const monthSelect=document.createElement('select'),yearSelect=document.createElement('select');monthSelect.setAttribute('aria-label',uiText('الشهر'));yearSelect.setAttribute('aria-label',uiText('السنة'));for(let i=0;i<12;i++){const o=document.createElement('option');o.value=i;o.textContent=new Intl.DateTimeFormat(boot.localization.language==='en'?'en-GB':'ar-KW',{month:'long',timeZone:'UTC'}).format(new Date(Date.UTC(2026,i,1)));monthSelect.append(o);}const minYear=original.min?Number(original.min.slice(0,4)):1900,maxYear=original.max?Number(original.max.slice(0,4)):Math.max(new Date().getFullYear()+20,year);for(let i=Math.min(minYear,year);i<=Math.max(maxYear,year);i++){const o=document.createElement('option');o.value=i;o.textContent=i;yearSelect.append(o);}monthSelect.value=month;yearSelect.value=year;monthSelect.onchange=()=>{month=Number(monthSelect.value);draw();};yearSelect.onchange=()=>{year=Number(yearSelect.value);draw();};chooser.append(monthSelect,yearSelect);top.append(prev,chooser,next);cal.append(top);const grid=document.createElement('div');grid.className='calendar-grid32';week32.forEach(i=>{const span=document.createElement('small');span.textContent=new Intl.DateTimeFormat(boot.localization.language==='en'?'en-GB':'ar-KW',{weekday:'short',timeZone:'UTC'}).format(new Date(Date.UTC(2026,9,18+i)));span.title=uiText(dayNames32[i]);grid.append(span);});const skip=(new Date(Date.UTC(year,month,1)).getUTCDay()+1)%7;for(let i=0;i<skip;i++)grid.append(document.createElement('span'));const days=new Date(Date.UTC(year,month+1,0)).getUTCDate();for(let d=1;d<=days;d++){const iso=String(year).padStart(4,'0')+'-'+String(month+1).padStart(2,'0')+'-'+String(d).padStart(2,'0'),b=document.createElement('button');b.type='button';b.textContent=d;b.disabled=(original.min&&iso<original.min)||(original.max&&iso>original.max);b.classList.toggle('selected32',iso===original.value);b.classList.toggle('today114',iso===dateNow());if(iso===original.value)b.setAttribute('aria-selected','true');b.setAttribute('aria-label',dateDisplay32(iso));b.onclick=()=>{original.value=iso;text.setCustomValidity('');original.dispatchEvent(new Event('change',{bubbles:true}));cal.remove();};grid.append(b);}cal.append(grid);const close=document.createElement('button');close.type='button';close.className='calendar-close114';close.textContent=uiText('إغلاق');close.onclick=()=>cal.remove();cal.append(close);};cal.addEventListener('keydown',e=>{if(e.key==='Escape'){cal.remove();button.focus();}});cal._refreshLocale138=draw;cal._locale138=boot.localization.language;draw();};sync();
}
function compactTables32(root){
 const recordViews=['payroll','payroll_review35','payroll_settings35','fridays35','commissions','allowances','policies','payments26','users','companies','workforce','v26_history','payout_status','entitlement','work_permit','resumption','addition','recovery','eos_settlement','v28_files','v28_documents','v28_archive','v28_debt','v28_locations','customer_cases','customer_departments','customer_ratings','public_messages','import','personal_history','analytics'];
 if(!recordViews.includes(view)&&!boot.modules?.[view])return;
 const page=root.matches?.('#page')?root:root.querySelector('#page');if(!page)return;
 page.querySelectorAll('.table-wrap').forEach(w=>{
  if(w.dataset.compact32||w.closest('form,.record-groups32'))return;const table=w.querySelector('table'),body=table?.querySelector('tbody');if(!body)return;if(!body.children.length){const empty=document.createElement('p');empty.className='record-empty32';empty.textContent=uiText('لا توجد نتائج');if(w.parentElement.querySelector('.record-empty32'))w.remove();else w.replaceWith(empty);return;}
  const columnNames=[...table.querySelectorAll('thead th')].map(n=>n.textContent.trim());if(columnNames.length===2&&['البيان','Field','Item','Description'].includes(columnNames[0])&&['القيمة','Value'].includes(columnNames[1]))return;
  w.dataset.compact32='1';const headers=[...table.querySelectorAll('thead th')].map(n=>n.textContent),groups=new Map(),cards=document.createElement('div');cards.className='record-groups32';
  for(const row of [...body.children]){
   const cells=[...row.children];if(!cells.length)continue;const identity=row.querySelector('[data-employee-id]'),employeeId=identity?.dataset.employeeId||'',recordId=identity?.dataset.recordId||cells[0].querySelector('[data-record-id]')?.dataset.recordId||row.querySelector('[data-record]')?.dataset.record||'';
   const codeFirst=/كود|الكود|Code/i.test(headers[0]),name=(view==='v28_files'?(cells[1]?.textContent+' — '):'')+(identity?.textContent||cells[0].textContent)+(codeFirst&&cells[1]?' — '+cells[1].textContent:'');const key=employeeId?'employee:'+employeeId:name;
   let group=groups.get(key);if(!group){group=document.createElement('details');group.className='record-group32';if(employeeId)group.dataset.employeeId=employeeId;group.dataset.groupKey=key;const title=document.createElement('summary');
    const person=employeeId?users.find(u=>Number(u.id)===Number(employeeId)):null,code=person?.employee_code;
    if(code){group.dataset.employeeCode=code;const codeNode=document.createElement('bdi');codeNode.className='leave-code32';codeNode.textContent=code;title.append(codeNode);}
    const titleText=document.createElement('span');titleText.className='leave-name32';titleText.textContent=name;title.append(titleText);group.append(title);groups.set(key,group);cards.append(group);
   }
   const record=document.createElement('section');record.className='record-details32';if(recordId)record.dataset.recordId=recordId;
   const actions=document.createElement('div');actions.className='record-actions32';
   const addField=(heading,source)=>{const field=document.createElement('div'),label=document.createElement('strong');label.textContent=heading;field.append(label);const value=document.createElement('div');value.className='record-value32';while(source.firstChild)value.append(source.firstChild);field.append(value);record.append(field);};
   cells.forEach((cell,i)=>{
    if(!i&&!cell.querySelector('button,a,input,select'))return;
    if(cell.dataset.actions32||/^(الإجراءات|الاجراءات|Actions|Action)$/i.test((headers[i]||'').trim())){while(cell.firstChild)actions.append(cell.firstChild);return;}
    const dl=cell.querySelector(':scope>dl.detail-grid26');
    if(dl){for(const dt of [...dl.children]){if(dt.tagName!=='DT')continue;const dd=dt.nextElementSibling;if(dd?.tagName==='DD')addField(dt.textContent,dd);}dl.remove();if(cell.textContent.trim()||cell.querySelector('button,a,img'))addField(uiText('ملاحظات'),cell);}
    else addField(headers[i]||'',cell);
   });
   if(actions.querySelector('button,a,input,select'))record.append(actions);group.append(record);
  }
  if([...groups.values()].some(g=>g.dataset.employeeCode)){[...groups.values()].sort((a,b)=>String(a.dataset.employeeCode||'ZZZ').localeCompare(String(b.dataset.employeeCode||'ZZZ'),undefined,{numeric:true})).forEach(g=>cards.append(g));}
  const wrap=document.createElement('section');wrap.className='record-browser32';wrap.append(cards);const host=w.closest('[data-record-search-host32]');w.replaceWith(wrap);installRecordSearch32(wrap,cards,host?.querySelector('input[data-record-search32]')||recordSearchOwner69(wrap,cards));
 });
}

function recordText32(card){
 const copy=card.cloneNode(true);copy.querySelectorAll('form,button,script,style,.search-select32').forEach(n=>n.remove());
 const id=card.dataset.employeeId,person=id?users.find(u=>String(u.id)===id):null;
 return normalize32(copy.textContent+' '+(card.dataset.searchText32||'')+' '+(person?[(person.employee_code||person.username),person.name,person.profile?.name_en].filter(Boolean).join(' '):''));
}
function recordSearchOwner69(wrap,cards){
 if(typeof recordSearchOwner75==='function'){const shared=recordSearchOwner75();if(shared)return shared;}
 const own=wrap.querySelector('input[type=search]')||cards.closest('[data-record-search-host32]')?.querySelector('input[data-record-search32]');if(own)return own;
 const panel=cards.closest('#staffPanel32,#temporaryPanel38,#generalPanel32');
 if(panel)return document.getElementById(({staffPanel32:'policySearch32',temporaryPanel38:'temporarySearch38',generalPanel32:'generalSearch69'})[panel.id]);
 if(typeof operationalView68==='function'&&operationalView68())return document.querySelector('#portalTabs32 input[data-record-search32]');
 return null;
}
const recordSearchFilters69=new WeakMap();
function installRecordSearch32(wrap,cards,existing=null){
 if(wrap.dataset.searchReady32)return;wrap.dataset.searchReady32='1';
 const search=existing||document.createElement('input');search.type='search';search.dataset.recordSearch32='1';search.placeholder=uiText('الاسم أو الكود أو بيانات السجل');
 let label=search.closest('label');if(!label){label=document.createElement('label');if(existing)search.before(label);label.append(search);}label.classList.add('record-search-label32');
 let title=label.querySelector('.record-search-title32');if(!title){for(const n of [...label.childNodes])if(n.nodeType===3)n.remove();title=document.createElement('span');title.className='record-search-title32';label.prepend(title);}title.textContent=uiText('البحث في السجلات');
 if(!existing)wrap.prepend(label);
 const empty=document.createElement('p');empty.className='record-search-empty32';empty.setAttribute('role','status');empty.hidden=true;wrap.append(empty);
 const filter=()=>{const q=normalize32(search.value);let count=0;for(const card of [...cards.children].filter(n=>n.tagName==='DETAILS')){card.hidden=!!q&&!recordText32(card).includes(q);if(!card.hidden)count++;}empty.textContent=uiText('لا توجد نتائج');empty.hidden=count>0;};
 if(search.dataset.remote32||['policySearch32','temporarySearch38','generalSearch69'].includes(search.id)){empty.hidden=true;return;}
 let filters=recordSearchFilters69.get(search);if(!filters){filters=new Map();recordSearchFilters69.set(search,filters);search.addEventListener('input',()=>{for(const [node,run]of filters)if(node.isConnected)run();else filters.delete(node);});}filters.set(wrap,filter);filter();
}
function recordSurfaces32(root){
 const page=root.matches?.('#page')?root:root.querySelector('#page');if(!page)return;
 for(const cards of page.querySelectorAll('.record-groups32,.leave-list32')){
  if(cards.closest('details,form'))continue;
  let wrap=cards.closest('.record-browser32');if(!wrap){wrap=document.createElement('section');wrap.className='record-browser32';const previous=cards.previousElementSibling;cards.before(wrap);if(previous?.matches('label.staff-search32'))wrap.append(previous);wrap.append(cards);}
  const input=recordSearchOwner69(wrap,cards);installRecordSearch32(wrap,cards,input);
 }
 for(const wrap of page.querySelectorAll('.record-browser32')){const parent=wrap.closest('.card');if(parent&&!parent.closest('details')){parent.classList.add('record-surface-parent32');wrap.classList.add('record-surface-inset32');}}
}

function enhanceUI32(){if(typeof boot==='undefined'||!boot)return;const root=document.querySelector('#app');if(!root)return;if(typeof installScreenActions50==='function')installScreenActions50();if(typeof mountEmployeeFilter73==='function')mountEmployeeFilter73();if(typeof mountRecordFilters75==='function')mountRecordFilters75();document.querySelectorAll('img.brand-await32').forEach(prepareBrand32);document.querySelectorAll('select').forEach(enhanceSelect32);document.querySelectorAll('input[type=date]').forEach(enhanceDate32);compactTables32(root);recordSurfaces32(root);if(typeof mountRecordFilters75==='function')mountRecordFilters75();finishActions32();if(typeof polishHeader76==='function')polishHeader76();if(typeof normalizeSurfaces82==='function')normalizeSurfaces82();if(boot.user&&boot.user.role!=='admin'&&['cms','offers'].includes(view)&&!canWrite29(view)&&!canWrite29(view,'edit'))root.querySelectorAll('#page button').forEach(b=>{if(!b.dataset.view)b.disabled=true;});const walk=document.createTreeWalker(root,NodeFilter.SHOW_TEXT);const change=[];while(walk.nextNode()){const n=walk.currentNode;if(n.parentElement?.closest('input,textarea,script,style,option,.date-calendar32'))continue;if(/^\s*\d{4}-\d{2}-\d{2}\s*$/.test(n.nodeValue))change.push(n);}change.forEach(n=>n.nodeValue=dateDisplay32(n.nodeValue.trim()));}
let enhanceQueued32=false;const enhancementObserver32=new MutationObserver(records=>{
 if(records.length&&records.every(r=>(r.target.nodeType===1?r.target:r.target.parentElement)?.closest('[data-live-clock32]')))return;
 if(records.length&&!records.some(r=>[...r.addedNodes].some(n=>n.nodeType===1&&!n.closest?.('[data-live-clock32],#toast,.select-options32,.date-calendar32'))))return;
 if(enhanceQueued32)return;enhanceQueued32=true;
 const run=()=>{enhanceQueued32=false;enhancementObserver32.disconnect();try{enhanceUI32();if(typeof screenRestore38==='function')screenRestore38();}finally{enhancementObserver32.observe(document.documentElement,{childList:true,subtree:true});}};
 if(typeof requestAnimationFrame==='function')requestAnimationFrame(run);else queueMicrotask(run);
});enhancementObserver32.observe(document.documentElement,{childList:true,subtree:true});
function formatTableCell32(header,value){const s=String(value??'');if(!/^-?\d+(\.\d+)?$/.test(s)||/^0\d/.test(s)||/كود|رقم|Code|Account|IBAN|ID/i.test(header))return value;const n=Number(s);if(/د\.ك|KWD|المبلغ|الإجمالي|قيمة|الراتب|الأساسي|الصافي|Amount|Total pay|Net pay|Salary|Value/i.test(header)&&!/أيام|عدد|نسبة|Days|Count|Rate/i.test(header))return n.toFixed(3);return s.includes('.')?n.toFixed(6).replace(/0+$/,'').replace(/\.$/,''):value;}


function prepareBrand32(img){if(img.dataset.brandReady32)return;img.dataset.brandReady32='1';const show=()=>img.classList.remove('brand-await32');const loaded=()=>{if(typeof img.decode==='function')img.decode().then(show,show);else show();};img.addEventListener('load',loaded,{once:true});img.addEventListener('error',show,{once:true});if(img.complete){if(img.naturalWidth)loaded();else show();}}

function remoteRecordSearch32(host,load){
 const label=document.createElement('label');label.className='record-search-label32';label.innerHTML='<span class="record-search-title32">'+esc(uiText('البحث في السجلات'))+'</span><input type="search" data-record-search32 data-remote32="1" placeholder="'+esc(uiText('الاسم أو الكود أو بيانات السجل'))+'">';host.prepend(label);
 const input=label.querySelector('input');let timer,seq=0;input.oninput=()=>{clearTimeout(timer);const ticket=++seq,q=input.value;timer=setTimeout(async()=>{if(!host.isConnected)return;host.setAttribute('aria-busy','true');try{await load(q,()=>ticket===seq&&host.isConnected);if(ticket===seq&&host.isConnected){compactTables32(document.querySelector('#app'));recordSurfaces32(document.querySelector('#app'));applyInterfaceLanguage();}}catch(e){if(!e.stale32&&ticket===seq)toast(e.message);}finally{if(ticket===seq)host.removeAttribute('aria-busy');}},250);};return input;
}

const creationIds70=new Set(['incentiveTerms114','financeAdd103','financeReturnMonth103','addAllowance','fridayRate','contractsNew35','returnCompanySales36','newGroup29','addUser','newService','addRecord','v26New','locNew28','newTemporary38','addCompany','new28','sendNotice','noticeVideo32','newReceipt','paymentNew26','buildGrid37','buildPay35','v26Build','buildPayroll','buildBank','newAccess30','makeBackup','newCmsPage','newCmsSection','addOffer','addDepartment','createRatingsPage','addCompanySales36','payAddition35','payRules35']);
function creationButton70(button,label){
 if(button.closest('.select-options32,.date-calendar32,.portal-tabs32,.profile-tabs29,.access-tabs30,.notice-tabs32')||button.matches('.nav-item,.quick-link,[role="tab"],.tab-arrow53'))return false;
 if(creationIds70.has(button.id||button.dataset.earlyAction61||button.dataset.pendingAction79)||button.matches('[data-newcard],[data-debt-add32],[data-temporary38],[data-profile-action29="documents"]'))return true;
 return ((/^(?:إضافة|اضافة|إنشاء|انشاء|تسجيل (?:سلفة|دفعة|طلب|سجل|إجازة|حركة|\/)|تعيين \/ إعادة تعيين|تجهيز كشف|Add\b|Create\b|New\b)/i.test(label)||/(?:جديد|جديدة)$/.test(label))&&!/إضافة (?:للجهاز|إلى الشاشة)|Add to (?:device|home)/i.test(label));
}
function finishActions32(){
 for(const head of document.querySelectorAll('#page .page-head')){
  let actions=head.querySelector(':scope>.page-actions32');
  const loose=[...head.children].filter(n=>n.matches('button,a.button'));
  if(loose.length){if(!actions){actions=document.createElement('div');actions.className='page-actions32';head.append(actions);}loose.forEach(n=>actions.append(n));}
 }
 for(const button of document.querySelectorAll('#page button,#modalContent button,#portalTabs32 button,#page a.button,#modalContent a.button,#portalTabs32 a.button')){
  const label=button.textContent.replace(/^\s*[+＋]\s*/, '').trim();
  if(!creationButton70(button,label))continue;
  button.classList.add('create-action32');if(typeof prepareCreation88==='function')prepareCreation88(button);if(button.disabled||button.hasAttribute('data-create-wait88'))button.setAttribute('aria-busy','true');else button.removeAttribute('aria-busy');
  if(button.querySelector(':scope>.create-icon32'))continue;
  // Keep the existing label nodes and handlers so translation and click bindings survive.
  for(const node of button.childNodes){if(node.nodeType===3&&node.nodeValue.trim()){node.nodeValue=node.nodeValue.replace(/^\s*[+＋]\s*/,'');break;}}
  const icon=document.createElement('span');icon.className='create-icon32';icon.setAttribute('aria-hidden','true');icon.textContent='+';button.prepend(icon);
 }
}

// Screen-specific records: rendered directly, with stable employee/record identities.
function fieldScreen32(label,value){return '<div><strong>'+esc(uiText(label))+'</strong><div class="record-value32">'+value+'</div></div>';}
function detailFieldsScreen32(html){const box=document.createElement('div');box.innerHTML=html;let out='';box.querySelectorAll('dl.detail-grid26').forEach(dl=>{for(const dt of [...dl.children])if(dt.tagName==='DT'&&dt.nextElementSibling?.tagName==='DD')out+=fieldScreen32(dt.textContent,dt.nextElementSibling.innerHTML);dl.remove();});if(box.textContent.trim())out+=fieldScreen32('ملاحظات',box.innerHTML);return out;}
function specialRecordsTable32(kind,headers,rows){
 if(!['resumption','addition'].includes(kind))return table(headers,rows);
 if(!rows.length)return '';
 const groups=new Map();for(const row of rows){const box=document.createElement('div');box.innerHTML=row[0];const marker=box.querySelector('[data-employee-id]'),uid=marker?.dataset.employeeId,person=users.find(u=>String(u.id)===String(uid));const key=uid||box.textContent;
 if(!groups.has(key))groups.set(key,{uid,person,name:box.textContent,records:[]});
 let fields=kind==='resumption'?fieldScreen32('تاريخ المباشرة الفعلي',row[1]):fieldScreen32('المبلغ د.ك',row[3])+fieldScreen32('تاريخ السريان المسجل',row[1]);
 fields+=fieldScreen32('الحالة',row[4])+detailFieldsScreen32(row[5]);
 // Retain a historical end date if one was genuinely supplied, without inventing ongoing employment here.
 if(row[2]&&!['مستمر','Ongoing','—'].includes(row[2]))fields+=fieldScreen32('نهاية مسجلة سابقًا',row[2]);
 const rid=box.querySelector('[data-record-id]')?.dataset.recordId||'';
 groups.get(key).records.push('<section class="record-details32" data-record-id="'+esc(rid)+'">'+fields+(row[6]?'<div class="record-actions32">'+row[6]+'</div>':'')+'</section>');}
 return '<div class="record-groups32">'+[...groups.values()].sort((a,b)=>String(a.person?.employee_code||'').localeCompare(String(b.person?.employee_code||''),'en',{numeric:true})).map(g=>'<details class="record-group32" data-employee-id="'+esc(g.uid||'')+'"><summary><bdi class="leave-code32">'+esc(g.person?.employee_code||'')+'</bdi><span class="leave-name32">'+employeeNameMarkup143(g.uid,g.person?.name||g.name,g.person)+'</span></summary>'+g.records.join('')+'</details>').join('')+'</div>';
}
function documentRecordsScreen32(kind,data){
 if(!data.records.length)return '<p class="record-empty32">'+esc(uiText('لا توجد نتائج'))+'</p>';
 const groups=new Map();for(const r of data.records){const v=JSON.parse(r.body_json),company=data.companies.find(c=>c.code===v.company_code),linked=users.find(u=>Number(u.id)===Number(v.linked_user_id));
 const key=kind==='corporate_document'?'company:'+(v.company_code||'unlinked'):'record:'+(r.version_root32||r.id);
 const name=kind==='corporate_document'?(company?.name||uiText('بدون ربط')):(v.name||r.title);
 if(!groups.has(key))groups.set(key,{code:kind==='corporate_document'?(company?.code||''):'',name,records:[]});
 let fields=fieldScreen32('اسم المستند',esc(v.name||r.title));
 if(kind==='attachment_archive'&&company)fields+=fieldScreen32('الشركة',esc(company.name));
 if(linked)fields+=fieldScreen32('الموظف المرتبط',esc(displayName31(linked)));
 if(kind==='corporate_document')fields+=fieldScreen32('بداية السريان',esc(dateDisplay32(r.date_from)))+fieldScreen32('تاريخ الانتهاء',esc(r.date_to?dateDisplay32(r.date_to):'—'));
 else {if(r.date_from)fields+=fieldScreen32('تاريخ السريان المسجل',esc(dateDisplay32(r.date_from)));if(r.created_at)fields+=fieldScreen32('تاريخ الرفع',esc(localDisplay(r.created_at)));if(r.date_to)fields+=fieldScreen32('تاريخ الانتهاء',esc(dateDisplay32(r.date_to)));}
 const extra={...v};for(const k of ['name','company_code','linked_user_id','previous_id'])delete extra[k];fields+=detailFieldsScreen32(detail26(extra));
 if(Number(r.amount))fields+=fieldScreen32('المبلغ د.ك',money26(r.amount));
 fields+=fieldScreen32('حالة النسخة',esc(uiText(r.version_current32===false?'نسخة سابقة':'النسخة الحالية')));
 const actions=(r.file_id?'<a class="button" target="_blank" rel="noopener" href="file.php?id='+Number(r.file_id)+'">'+esc(uiText('فتح / تنزيل'))+'</a>':'')+(r.version_current32!==false&&canWrite29(kind==='corporate_document'?'v28_documents':'v28_archive')?'<button data-version28="'+Number(r.id)+'">'+esc(uiText('نسخة جديدة'))+'</button>':'');
 groups.get(key).records.push('<section class="record-details32" data-record-id="'+Number(r.id)+'">'+fields+(actions?'<div class="record-actions32">'+actions+'</div>':'')+'</section>');}
 return '<div class="record-groups32">'+[...groups.entries()].sort((a,b)=>a[1].code.localeCompare(b[1].code,'en',{numeric:true})).map(([key,g])=>'<details class="record-group32" data-group-key="'+esc(key)+'"><summary>'+(g.code?'<bdi class="leave-code32">'+esc(g.code)+'</bdi>':'')+'<span class="leave-name32">'+esc(g.name)+'</span></summary>'+g.records.join('')+'</details>').join('')+'</div>';
}

// Refresh native/mobile selection mode without replacing the select or its handlers.
if(typeof window!=='undefined'&&window.addEventListener)window.addEventListener('resize',()=>document.querySelectorAll('select[data-search32]').forEach(s=>s._choiceMode32?.()));

// Explicit async action feedback: success only after the operation resolves.
const pendingActions32=new Set();
async function actionFeedback32(button,key,task,{checking=false,success='تم التحديث'}={}){
 if(!button||pendingActions32.has(key))return;
 pendingActions32.add(key);const html=button.innerHTML,disabled=button.disabled;
 button.disabled=true;button.setAttribute('aria-busy','true');button.classList.add('action-busy32');
 button.textContent=uiText(checking?'جارٍ الفحص…':'جارٍ التحديث…');
 try{const result=await task();const message=typeof success==='function'?success(result):uiText(success);if(message)toast(message);return result;}
 catch(error){if(!error.stale32)toast(error.message||uiText(checking?'تعذر الفحص، حاول مرة أخرى':'تعذر التحديث، حاول مرة أخرى'));}
 finally{pendingActions32.delete(key);button.innerHTML=html;button.disabled=disabled;button.removeAttribute('aria-busy');button.classList.remove('action-busy32');}
}
function alertCheckResult32(result){const sent=Number(result?.sent);return Number.isFinite(sent)&&sent>=0?(sent===0?uiText('اكتمل الفحص، لا توجد تنبيهات جديدة'):uiText('اكتمل الفحص — تنبيهات جديدة:')+' '+sent):uiText('اكتمل الفحص');}
