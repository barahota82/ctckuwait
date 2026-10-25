'use strict';
const recordCategories49={
 request:[['salary','الرواتب والمستحقات'],['letters','الخطابات والشهادات'],['data','البيانات والمستندات'],['assets','العهد واحتياجات العمل'],['complaints','الشكاوى والمقترحات'],['other','طلبات أخرى']],
 document:[['contract','عقود العمل وملحقاتها'],['personal','المستندات الشخصية'],['certificates','الشهادات والمؤهلات'],['letters','الخطابات والإقرارات'],['other','مستندات أخرى']],
 custody:[['electronics','أجهزة إلكترونية'],['sim','خطوط وشرائح اتصال'],['vehicles','سيارات ووسائل نقل'],['keys','مفاتيح وبطاقات دخول'],['tools','أدوات ومعدات'],['other','عهد أخرى']],
 discipline:[['notice','تنبيهات ولفت نظر'],['warning','إنذارات رسمية'],['penalty','جزاءات وخصومات'],['decision','قرارات إدارية'],['appeal','تظلمات وردود الموظفين'],['other','أخرى']],
 evaluation:[['trial','فترة التجربة'],['periodic','تقييم دوري'],['performance','أداء ومهام'],['attendance','حضور وانضباط'],['behavior','سلوك وتعاون'],['improvement','خطط تحسين ومتابعة'],['other','أخرى']]
};
const recordLinks49={};
function availableRecordCategories49(module){return module==='request'&&requestMode50==='attendance'?[]:(recordCategories49[module]||[]);}
let recordCategory49='all';
function recordCategoryOf49(module,r){
 let b={};try{b=JSON.parse(r.body_json||'{}');}catch{}
 if(module==='request'&&b._punch39)return 'attendance';
 if(recordCategories49[module]?.some(([k])=>k===b.record_category49))return b.record_category49;
 return recordCategories49[module]?'other':'unclassified';
}
function recordRows49(module,rows){
 const status=document.querySelector('#recordStatus49')?.value||'',expiry=document.querySelector('#recordExpiry49')?.value||'',today=dateNow();
 return rows.filter(r=>{if(module==='request'){const incident=recordCategoryOf49(module,r)==='attendance';if(incident!==(requestMode50==='attendance'))return false;}if(recordCategories49[module]&&recordCategory49!=='all'&&recordCategoryOf49(module,r)!==recordCategory49)return false;
 if(status&&r.status!==status)return false;
 if(expiry){const b=JSON.parse(r.body_json||'{}');if(module==='document'){const end=b.expiry||'';if(!end)return expiry==='unknown';const days=(Date.parse(end)-Date.parse(today))/86400000;return expiry==='expired'?days<0:expiry==='soon'?days>=0&&days<=30:expiry==='valid'?days>30:false;}
 if(module==='custody'){const returned=b.custody_state49==='returned'||(b.return_date&&b.return_date<=today),settled=b.custody_state49==='settled';return expiry==='settled'?settled:expiry==='returned'?returned&&!settled:!returned&&!settled;}}
 return true;});
}
function recordTable49(module,rows){const shown=recordRows49(module,rows),hasAmount=module!=='custody';return shown.length?table(['الموظف','العنوان','الفترة',...(hasAmount?['المبلغ']:[]),'الحالة','الإجراءات'],shown.map(r=>[recordIdentity32(r.user_id,r.employee_name,r.id),(typeof dataTextMarkup162==='function'?dataTextMarkup162('record_title:'+module,r.title):esc(r.title)),esc(r.date_from??'—')+' ← '+esc(r.date_to??'—'),...(hasAmount?[esc(r.amount)+' '+esc(boot.company.currency)]:[]),badge(r.status),'<button data-record="'+r.id+'">التفاصيل</button>'])):empty();}
function recordTabsHtml49(module){if(!recordCategories49[module])return '';recordCategory49='all';return '<section id="recordTabs49" class="card record-tabs49"><div class="portal-hub-title32">'+esc(uiText((module==='request'?(requestMode50==='attendance'?'وقائع الحضور':'الطلبات الإدارية'):boot.modules[module].label)))+'</div><div class="top-row49"><nav class="portal-tabs32" aria-label="'+esc(uiText((module==='request'?(requestMode50==='attendance'?'وقائع الحضور':'الطلبات الإدارية'):boot.modules[module].label)))+'">'+[...(recordDisplayEnabled87(module)?[]:[['all','الكل']]),...availableRecordCategories49(module)].map(([k,t])=>'<button type="button" data-category49="'+k+'" aria-current="'+(k==='all'?'page':'false')+'">'+esc(uiText(t))+'</button>').join('')+'</nav><div class="top-actions49" data-top-actions49></div></div></section><div class="record-filters49"><label>الحالة<select id="recordStatus49"><option value="">الكل</option>'+['pending','revision','approved','rejected','active','closed'].map(k=>'<option value="'+k+'">'+esc(uiText(labels[k]||k))+'</option>').join('')+'</select></label>'+(['document','custody'].includes(module)?'<label>التصفية<select id="recordExpiry49"><option value="">الكل</option>'+(module==='document'?[['valid','ساري لأكثر من 30 يومًا'],['soon','قارب الانتهاء — خلال 30 يومًا'],['expired','منتهي'],['unknown','بدون تاريخ انتهاء']]:[['active','سارية'],['returned','مستردة'],['settled','تمت تسويتها']]).map(([k,t])=>'<option value="'+k+'">'+t+'</option>').join('')+'</select></label>':'')+'</div>';}
function bindRecordTabs49(module,getRows){if(!recordCategories49[module])return;
 const card=document.querySelector('#recordTabs49'),portal=document.querySelector('#portalTabs32');
 let slot=card.querySelector('[data-top-actions49]');
 if(recordDisplayEnabled87(module)&&module!=='document'){
  const row=card.querySelector('.top-row49');
  if(slot&&!slot.children.length)slot.remove();
  const nav=document.createElement('nav');nav.className='portal-tabs32';nav.setAttribute('aria-label',uiText(boot.modules[module].label));
  const placeholder=document.createElement('button');placeholder.dataset.view=module;nav.append(placeholder);row.before(nav);
  slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';row.before(slot);
  card.classList.add('record-display87');prepareDocumentModes86(card,module);
 }
 if(module==='document'&&portal&&!portal.hidden){
  slot=portal.querySelector('[data-top-actions49]');
  if(!slot){slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';portal.append(slot);}
  card.classList.add('document-categories86');
 }
 if(module==='document'&&portal&&!portal.hidden)card.classList.add('document-subtabs52');
 else if(['custody','discipline','evaluation','request'].includes(module)&&(!portal||portal.hidden))card.classList.add('primary-record-tabs52');
 if(module==='document'&&portal&&!portal.hidden)prepareDocumentLayout54(card,document.querySelector('#page .record-filters49'),portal);
 if(['custody','discipline','evaluation'].includes(module)||(module==='request'&&requestMode50!=='attendance'))prepareRecordFilterLayout55(card,document.querySelector('#page .record-filters49'),module);
 const add=document.querySelector('#addRecord');const punch=add?.nextElementSibling;if(add){if(recordDisplayEnabled87(module)&&typeof adoptScreenAction61==='function')adoptScreenAction61(add,slot);else slot.append(add);}
 if(punch&&punch.textContent.includes('واقعة حضور'))slot.append(punch);
 const refresh=()=>{const host=document.querySelector('[data-record-results32]'),links=recordLinks49[recordCategory49];
 if(add)add.hidden=!!links||(module==='request'&&requestMode50==='attendance');
 if(punch&&punch.textContent.includes('واقعة حضور'))punch.hidden=module!=='request'||requestMode50!=='attendance';
 if(links){host.innerHTML='<div class="row-actions">'+links.filter(([key])=>screenAllowed(key)).map(([key,t])=>'<button type="button" data-linked49="'+key+'">'+esc(uiText(t))+'</button>').join('')+'</div>';host.querySelectorAll('[data-linked49]').forEach(b=>b.onclick=()=>navigate(b.dataset.linked49));return;}
 host.innerHTML=recordTable49(module,getRows());host.querySelectorAll('[data-record]').forEach(b=>b.onclick=()=>recordDetail(getRows().find(r=>r.id==b.dataset.record)));};
 document.querySelectorAll('[data-category49]').forEach(b=>b.onclick=()=>{recordCategory49=b.dataset.category49;document.querySelectorAll('[data-category49]').forEach(n=>n.setAttribute('aria-current',n===b?'page':'false'));refresh();});
 if(recordDisplayEnabled87(module)){documentRefresh86=refresh;applyDocumentMode86();if(typeof finishActions32==='function')finishActions32();}
 refresh();
 for(const id of ['recordStatus49','recordExpiry49']){const n=document.getElementById(id);if(n)n.onchange=refresh;}
 return refresh;
}
function moveAddUser49(){const button=document.querySelector('#addUser'),host=document.querySelector('#portalTabs32');if(!button||!host||host.hidden)return;let slot=host.querySelector('[data-top-actions49]');if(!slot){slot=document.createElement('div');slot.className='top-actions49';slot.dataset.topActions49='';host.append(slot);}host.classList.add('has-actions49');if(typeof adoptScreenAction61==='function')adoptScreenAction61(button,slot);else slot.append(button);}

function recordCategoryLabel49(module,key){return recordCategories49[module]?.find(([k])=>k===key)?.[1]||key;}
function recordCategoryField49(module){return '<label>'+esc(uiText('التصنيف'))+'<select name="record_category49" required><option value="">'+esc(uiText('اختر التصنيف'))+'</option>'+availableRecordCategories49(module).filter(([k])=>!recordLinks49[k]).map(([k,t])=>'<option value="'+k+'" '+(recordCategory49===k?'selected':'')+'>'+esc(uiText(t))+'</option>').join('')+'</select></label>';}

// Desktop documents use one sticky parent card; retain the existing mobile layout.
let documentLayoutState54=null;
function refreshDocumentLayout54(){
 const s=documentLayoutState54;if(!s)return;
 if(!s.card.isConnected||!s.portal.isConnected||view!=='document'){documentLayoutState54=null;return;}
 const desktop=window.matchMedia('(min-width:761px)').matches;
 if(s.desktop===desktop)return;s.desktop=desktop;
 if(desktop){s.portal.classList.add('has-document54');s.portal.append(s.card);if(s.filters)s.card.append(s.filters);}
 else {s.portal.classList.remove('has-document54');s.cardAnchor.after(s.card);if(s.filters)s.filterAnchor.after(s.filters);}
}
function prepareDocumentLayout54(card,filters,portal){
 const cardAnchor=document.createComment('document categories position'),filterAnchor=filters?document.createComment('document filters position'):null;
 card.before(cardAnchor);if(filters)filters.before(filterAnchor);
 documentLayoutState54={card,filters,portal,cardAnchor,filterAnchor,desktop:null};refreshDocumentLayout54();
}
window.addEventListener('resize',refreshDocumentLayout54,{passive:true});

let recordFilterLayoutState55=null;
function refreshRecordFilterLayout55(){
 const s=recordFilterLayoutState55;if(!s)return;
 if(!s.card.isConnected||!s.filters.isConnected||view!==s.module){recordFilterLayoutState55=null;return;}
 const desktop=window.matchMedia('(min-width:761px)').matches;if(s.desktop===desktop)return;s.desktop=desktop;
 if(desktop){s.card.append(s.filters);s.card.classList.add('with-filters55');}
 else {s.anchor.after(s.filters);s.card.classList.remove('with-filters55');}
}
function prepareRecordFilterLayout55(card,filters,module){
 if(!filters)return;const anchor=document.createComment('record filter position');filters.before(anchor);
 recordFilterLayoutState55={card,filters,module,anchor,desktop:null};refreshRecordFilterLayout55();
}
window.addEventListener('resize',refreshRecordFilterLayout55,{passive:true});

// Document display switches keep the same records, filters and backend permissions.
let documentMode86='all',documentRefresh86=null,documentModule86='document';
function recordDisplayEnabled87(module){return ['document','custody','discipline','evaluation'].includes(module)||(module==='request'&&requestMode50!=='attendance');}
function prepareDocumentModes86(host,module='document'){
 documentMode86='all';documentRefresh86=null;documentModule86=module;
 const original=host.querySelector('button[data-view="'+module+'"]');if(!original)return;
 for(const [mode,title]of [['all','الكل'],['detail','تفصيلي']]){
  const button=document.createElement('button');button.type='button';button.dataset.documentMode86=mode;
  button.textContent=uiText(title);button.setAttribute('aria-current',mode==='all'?'page':'false');
  button.onclick=()=>{documentMode86=mode;if(mode==='all'){recordCategory49='all';document.querySelectorAll('#recordTabs49 [data-category49]').forEach(n=>n.setAttribute('aria-current','false'));}applyDocumentMode86();if(documentRefresh86)documentRefresh86();};
  original.before(button);
 }
 original.remove();
}
function applyDocumentMode86(){
 const card=document.querySelector('#recordTabs49');
 if(card&&view===documentModule86){
  if(documentModule86==='document'){card.classList.add('document-categories86');card.classList.toggle('document-detail86',documentMode86==='detail');}
  else card.classList.toggle('record-detail87',documentMode86==='detail');
  const row=card.querySelector('.top-row49');if(row)row.hidden=documentMode86!=='detail';
 }
 document.querySelectorAll('[data-document-mode86]').forEach(n=>n.setAttribute('aria-current',n.dataset.documentMode86===documentMode86?'page':'false'));
 if(typeof mobileTabsRefresh33==='function')mobileTabsRefresh33();
 if(typeof scheduleStickyTabs48==='function')scheduleStickyTabs48();
}
