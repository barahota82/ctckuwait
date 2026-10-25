'use strict';
const noticeCategories32={all:'الكل',attendance:'الحضور والانصراف',leave:'الإجازات والمرضيات',finance:'الرواتب والمستحقات',documents:'المستندات والتجديدات',customers:'خدمة العملاء',administration:'إشعارات الإدارة'};
const noticeTypes32={exit_permission182:'إذن خروج',balance:'أرصدة الإجازات',renewal:'تجديد المستندات',customer:'حالات العملاء',payroll:'كشوف الرواتب',leave_cash:'صرف الإجازات',proof:'إثبات التواجد',resumption:'مباشرة العمل',leave:'طلبات الإجازات',sick:'المرضيات',external:'انصراف من الخارج',loan:'السلف',adjustment:'الإضافات',discipline:'القرارات',request:'طلبات الموظفين',video:'إقرارات الفيديو',announcement:'إعلان',circular:'تعميم',message:'رسالة',decision:'قرار'};
let noticeStripCleanup33=()=>{},noticePaginate190=null,noticeMeta195=null,noticeCounts195=null,noticeTypes195=null;const noticePages190=new Map();
let noticeRequest139=0,noticeLoading139=false,noticeError139='',noticeLoaded139=false,noticeDataBox139=null;
let noticeState32={category:'all',status:'all',type:'all',search:'',box:'inbox',page:0},noticeRows32=[],noticeOwner32=null;
function noticeSort32(rows){
 const groups=new Map();for(const r of rows){const m=r.meta||{},key=m.batch||(m.type==='balance'||m.type==='renewal'?m.type+String(r.created_at).slice(0,16):'id:'+r.id);if(!groups.has(key))groups.set(key,[]);groups.get(key).push(r);}
 return [...groups.values()].sort((a,b)=>Math.max(...b.map(r=>Number(r.id)))-Math.max(...a.map(r=>Number(r.id)))).flatMap(group=>group.sort((a,b)=>{
  if(a.meta?.type==='balance'&&b.meta?.type==='balance')return Number(b.meta.balance)-Number(a.meta.balance)||String(a.meta.employee_code||'').localeCompare(String(b.meta.employee_code||''),undefined,{numeric:true})||Number(a.id)-Number(b.id);
  if(a.meta?.type==='renewal'&&b.meta?.type==='renewal')return String(a.meta.due||'9999').localeCompare(String(b.meta.due||'9999'))||Number(a.id)-Number(b.id);
  return Number(b.id)-Number(a.id);
 }));
}
function noticeMatches32(r,s){return(s.category==='all'||r.meta.category===s.category)&&(s.type==='all'||r.meta.type===s.type)&&(s.status==='all'||(s.status==='unread'?!r.read_at:!!r.read_at))&&(!s.search||[r.title,r.body,r.recipient_name,r.recipient_name_en,...(r.name_bindings142||[]).flatMap(n=>[n.ar,n.en]),noticeText142(r,'title'),noticeText142(r,'body')].join(' ').toLocaleLowerCase().includes(s.search.toLocaleLowerCase().trim()));}

async function notificationCenter32({quiet195=false}={}){
 const owner=String(boot.user.id)+':'+boot.access_mode;
 if(noticeOwner32!==owner){noticePages190.clear();noticeOwner32=owner;noticeState32={category:'all',status:'all',type:'all',search:'',box:'inbox',page:0};noticeRows32=[];noticeLoaded139=false;noticeMeta195=noticeCounts195=noticeTypes195=null;}
 if(!teamPermission('notifications','create'))noticeState32.box='inbox';
 if(noticeDataBox139!==noticeState32.box){noticeRows32=[];noticeLoaded139=false;noticeDataBox139=noticeState32.box;noticeMeta195=noticeCounts195=noticeTypes195=null;}
 const ticket=++noticeRequest139,box=noticeState32.box,epoch=typeof navigationEpoch32==='number'?navigationEpoch32:null;
 const filterKey195=()=>JSON.stringify([noticeState32.category,noticeState32.status,noticeState32.type,noticeState32.search]);const requested195=filterKey195();
 const current=()=>requested195===filterKey195()&&ticket===noticeRequest139&&owner===noticeOwner32&&box===noticeState32.box&&(epoch===null||epoch===navigationEpoch32);
 const f195={family:'notice',route:'notifications',category:noticeState32.category,status:noticeState32.status,type:noticeState32.type,query:noticeState32.search};const stamp195=JSON.stringify([noticeState32.status,noticeState32.type,noticeState32.search]);const p195=noticePaginate190?.params(noticeState32.box+'|'+noticeState32.category,stamp195)||{size:'50',page:0};
 noticeLoading139=true;noticeError139='';if(quiet195&&$('#noticeResults32')){$('#noticeResults32').setAttribute('aria-busy','true');noticePaginate190?.busy(true);}else noticeRender32();
 try{
  const data=await api('notifications',null,{box,page195:JSON.stringify({...p195,filters:f195})});if(!current())return;
  noticeRows32=noticeSort32(data.rows195||[]);noticeMeta195=data.page195;noticeCounts195=data.counts195;noticeTypes195=data.types195;noticeLoaded139=true;
 }catch(error){if(!current()||error.stale32)return;noticeError139=error.message||uiText('تعذر الاتصال بالخادم');throw error;}
 finally{if(current()){noticeLoading139=false;if(quiet195&&$('#noticeResults32')){document.querySelectorAll('[data-notice-category]').forEach(b=>{const count=b.querySelector('.notice-count32');if(count)count.textContent=noticeCounts195?.[b.dataset.noticeCategory]||0;});const type195=$('#noticeType32');if(type195){type195.innerHTML='<option value="all">'+esc(uiText('كل الأنواع'))+'</option>'+(noticeTypes195||[]).map(k=>'<option value="'+esc(k)+'">'+esc(uiText(noticeTypes32[k]||k))+'</option>').join('');type195.value=noticeState32.type;}noticeResults32();noticePaginate190?.busy(false);}else noticeRender32();}}
}

function noticeRender32(){
 const previousStrip=document.querySelector('.notice-tabs32'),stripPosition=previousStrip?.scrollLeft||0;
 const s=noticeState32,manage=teamPermission('notifications','create'),count=key=>noticeCounts195?.[key]??noticeRows32.filter(r=>!r.read_at&&(key==='all'||r.meta.category===key)).length;
 const boxButtons=manage?['inbox','sent'].map(k=>'<button data-notice-box="'+k+'" aria-pressed="'+(s.box===k)+'" class="notice-box-button32 '+(s.box===k?'active':'')+'">'+esc(uiText(k==='inbox'?'الوارد':'المرسل'))+'</button>').join(''):'';
 $('#page').innerHTML='<section class="card notice-header138">'+head('مركز الإشعارات','',manage?'<button id="sendNotice" class="primary">'+esc(uiText('إنشاء إشعار'))+'</button>'+boxButtons:'')+
 '<div class="notice-category-strip32"><div class="notice-tabs32" role="group" aria-label="'+esc(uiText('أقسام الإشعارات'))+'">'+Object.entries(noticeCategories32).filter(([k])=>boot.access_mode!=='personal'||k!=='customers').map(([key,label])=>'<button data-notice-category="'+key+'" aria-pressed="'+(s.category===key)+'" class="'+(s.category===key?'active':'')+'">'+esc(uiText(label))+'<span class="notice-count32">'+(noticeLoaded139?count(key):'…')+'</span></button>').join('')+'</div></div></section><section class="notice-center32">'+
 '<div class="notice-filters32"><label>'+esc(uiText('بحث في الإشعارات'))+'<input id="noticeSearch32" type="search" value="'+esc(s.search)+'" placeholder="'+esc(uiText('الاسم أو نص الإشعار'))+'"></label><label>'+esc(uiText('حالة القراءة'))+'<select id="noticeStatus32" data-choice-only32="1">'+[['all','الكل'],['unread','غير المقروء'],['read','المقروء']].map(([k,l])=>'<option value="'+k+'" '+(s.status===k?'selected':'')+'>'+esc(uiText(l))+'</option>').join('')+'</select></label><label>'+esc(uiText('نوع الإشعار'))+'<select id="noticeType32" data-choice-only32="1"><option value="all">'+esc(uiText('كل الأنواع'))+'</option>'+(noticeTypes195||[...new Set(noticeRows32.filter(r=>s.category==='all'||r.meta.category===s.category).map(r=>r.meta.type))]).map(k=>'<option value="'+esc(k)+'" '+(s.type===k?'selected':'')+'>'+esc(uiText(noticeTypes32[k]||k))+'</option>').join('')+'</select></label><button id="noticeRefresh32">'+esc(uiText('تحديث'))+'</button></div><div id="noticeResults32" aria-live="polite"></div></section>';
 noticePaginate190=recordPager189(document.querySelector('.notice-center32'),()=>notificationCenter32({quiet195:true}).catch(e=>{if(!e.stale32)toast(e.message);}),noticePages190);
 document.querySelectorAll('[data-notice-category]').forEach(b=>b.onclick=()=>noticeCategoryChange32(b.dataset.noticeCategory));const strip=document.querySelector('.notice-tabs32');strip.scrollLeft=stripPosition;
 document.querySelectorAll('[data-notice-box]').forEach(b=>b.onclick=async()=>{if(s.box!==b.dataset.noticeBox){s.box=b.dataset.noticeBox;noticeRows32=[];noticeLoaded139=false;}s.page=0;try{await notificationCenter32();}catch(e){toast(e.message);}});
 $('#noticeSearch32').oninput=e=>{s.search=e.target.value;s.page=0;clearTimeout(e.target.timer195);e.target.timer195=setTimeout(()=>notificationCenter32({quiet195:true}).catch(e=>{if(!e.stale32)toast(e.message);}),200);};$('#noticeStatus32').onchange=e=>{s.status=e.target.value;s.page=0;void notificationCenter32({quiet195:true}).catch(e=>toast(e.message));};$('#noticeType32').onchange=e=>{s.type=e.target.value;s.page=0;void notificationCenter32({quiet195:true}).catch(e=>toast(e.message));};$('#noticeRefresh32').onclick=()=>actionFeedback32($('#noticeRefresh32'),'notifications.refresh',()=>notificationCenter32(),{success:'تم تحديث الإشعارات'});if($('#sendNotice'))$('#sendNotice').onclick=()=>noticeCompose32().catch(e=>toast(e.message));noticeResults32(false);applyInterfaceLanguage();noticeStripBind33(stripPosition);
}
function noticeStripReveal33(){
 const strip=document.querySelector('.notice-tabs32'),button=strip?.querySelector('[aria-pressed="true"]');
 if(!strip||!button)return;const outer=strip.getBoundingClientRect(),inner=button.getBoundingClientRect();
 const delta=inner.left<outer.left?inner.left-outer.left:inner.right>outer.right?inner.right-outer.right:0;
 if(delta)strip.scrollBy({left:delta,behavior:'auto'});
}
function noticeStripBind33(position){
 noticeStripCleanup33();const strip=document.querySelector('.notice-tabs32');if(!strip)return;strip.scrollLeft=position;
 const resize=()=>noticeStripReveal33();let observer=null;if(typeof ResizeObserver==='function'){observer=new ResizeObserver(resize);observer.observe(strip);}else window.addEventListener('resize',resize);
 noticeStripCleanup33=()=>{observer?.disconnect();window.removeEventListener('resize',resize);};
 const restore=()=>{if(strip.isConnected){strip.scrollLeft=position;noticeStripReveal33();}};restore();if(typeof requestAnimationFrame==='function')requestAnimationFrame(restore);
}
function noticeCategoryChange32(key){
 if(!Object.prototype.hasOwnProperty.call(noticeCategories32,key))return;
 noticeState32.category=key;noticeState32.type='all';noticeState32.page=0;
 // Keep the existing strip and its scroll offset; only update the result filters.
 document.querySelectorAll('[data-notice-category]').forEach(button=>{const selected=button.dataset.noticeCategory===key;button.setAttribute('aria-pressed',String(selected));button.classList.toggle('active',selected);});
 const type=document.querySelector('#noticeType32');
 type.innerHTML='<option value="all">'+esc(uiText('كل الأنواع'))+'</option>'+[...new Set(noticeRows32.filter(r=>key==='all'||r.meta.category===key).map(r=>r.meta.type))].map(k=>'<option value="'+esc(k)+'">'+esc(uiText(noticeTypes32[k]||k))+'</option>').join('');type.querySelector('option').selected=true;
 void notificationCenter32({quiet195:true}).catch(e=>{if(!e.stale32)toast(e.message);});const button=document.querySelector('[data-notice-category="'+key+'"]');button?.focus({preventScroll:true});noticeStripReveal33();
}
function noticeStatusText32(r){return r.read_at?'مقروء':'غير مقروء';}
function noticeResults32(translate=true){
 const host=$('#noticeResults32');if(!host)return;
 host.setAttribute('aria-busy',String(noticeLoading139));
 const status=noticeLoading139?'<p role="status">'+esc(uiText('جارٍ التحميل…'))+'</p>':noticeError139?'<p role="alert" class="error">'+esc(noticeError139)+'</p>':'';
 if(!noticeLoaded139){host.innerHTML=status;noticePaginate190?.([],noticeState32.box+'|'+noticeState32.category,'loading');return;}

 const s=noticeState32,rows=noticeMeta195?noticeRows32:noticeRows32.filter(r=>noticeMatches32(r,s)),sent=s.box==='sent';let entries=rows;
 if(sent){const grouped=new Map();rows.forEach(r=>{const key=r.meta.batch||'id:'+r.id;if(!grouped.has(key))grouped.set(key,[]);grouped.get(key).push(r);});entries=[...grouped.values()];}
 const chunk=noticePaginate190(entries,s.box+'|'+s.category,JSON.stringify([s.status,s.type,s.search]),noticeMeta195);
 host.innerHTML=status+'<p class="muted">'+esc(uiText('النتائج'))+': '+(noticeMeta195?.total??entries.length)+'</p>'+(chunk.length?chunk.map(item=>sent?noticeSentCard32(item):noticeCard32(item)).join(''):empty('لا توجد إشعارات مطابقة'));
 document.querySelectorAll('[data-notice-read],[data-notice-ack]').forEach(b=>b.onclick=async()=>{b.disabled=true;try{const id=Number(b.dataset.noticeRead||b.dataset.noticeAck),r=noticeRows32.find(x=>Number(x.id)===id);await api('notice_read',{id,unread:!!b.dataset.noticeRead&&!!r.read_at,ack:!!b.dataset.noticeAck});await notificationCenter32();}catch(e){b.disabled=false;toast(e.message);}});
 document.querySelectorAll('[data-notice-open]').forEach(b=>b.onclick=async()=>{const r=noticeRows32.find(x=>Number(x.id)===Number(b.dataset.noticeOpen));try{if(r.meta.permission_id182)await permissionGo183(r.meta.permission_id182);else await openLinked32(r.meta.screen,r.meta);}catch(e){toast(e.message);}});if(translate)applyInterfaceLanguage();
}
function noticeCard32(r){
 const m=r.meta,level=m.type==='balance'?(Number(m.balance)>=Number(boot.settings?.leave_alert_high??60)?'رصيد مرتفع — أولوية المتابعة':'استحق ترتيب إجازة'):'';
 return '<article class="card notice-card32 '+(!r.read_at?'notice-unread32':'')+'"><div class="notice-heading32"><span class="tag">'+esc(uiText(noticeCategories32[m.category]||noticeCategories32.administration))+'</span>'+(m.priority==='important'?'<span class="tag notice-important32">'+esc(uiText('مهم'))+'</span>':'')+'<time>'+esc(localDisplay(r.created_at))+'</time></div>'+noticeMarkup142('h3',r,'title')+noticeMarkup142('p',r,'body','notice-body32')+(level?'<p class="notice-level32">'+esc(uiText(level))+'</p>':'')+'<div class="notice-actions32"><button data-notice-read="'+r.id+'">'+esc(uiText(r.read_at?'تحديد كغير مقروء':'تحديد كمقروء'))+'</button>'+(Number(r.require_ack)&&!r.acknowledged_at?'<button class="primary" data-notice-ack="'+r.id+'">'+esc(uiText('اطلعت على القرار'))+'</button>':'')+(r.acknowledged_at?'<span class="tag">'+esc(uiText('تم الإقرار بالاطلاع'))+' · '+esc(localDisplay(r.acknowledged_at))+'</span>':'')+(m.screen?'<button data-notice-open="'+r.id+'">'+esc(uiText(m.screen==='leave_overview'?'عرض رصيد الموظف':m.record_id?'فتح السجل':'فتح الشاشة المرتبطة'))+'</button>':'')+(r.attachment_id?'<a class="button" href="file.php?id='+Number(r.attachment_id)+'" target="_blank" rel="noopener">'+esc(uiText('فتح المرفق'))+'</a>':'')+'</div></article>';
}
function noticeSentCard32(rows){const r=rows[0],read=rows.filter(x=>x.read_at).length,ack=rows.filter(x=>x.acknowledged_at).length;return '<article class="card notice-card32"><div class="notice-heading32"><span class="tag">'+esc(uiText(noticeTypes32[r.meta.type]||'رسالة'))+'</span><time>'+esc(localDisplay(r.created_at))+'</time></div>'+noticeMarkup142('h3',r,'title')+noticeMarkup142('p',r,'body','notice-body32')+'<div class="notice-summary32"><span>'+esc(uiText('المستلمون'))+': '+rows.length+'</span><span>'+esc(uiText('المقروء'))+': '+read+'</span><span>'+esc(uiText('غير المقروء'))+': '+(rows.length-read)+'</span>'+(Number(r.require_ack)?'<span>'+esc(uiText('أكدوا الاطلاع'))+': '+ack+'</span>':'')+'</div><details><summary>'+esc(uiText('متابعة المستلمين'))+'</summary>'+rows.map(x=>'<div class="notice-recipient32">'+noticeMarkup142('strong',x,'recipient')+'<span>'+esc(uiText(noticeStatusText32(x)))+(x.read_at?' · '+esc(localDisplay(x.read_at)):'')+'</span>'+(Number(x.require_ack)?'<span>'+esc(uiText(x.acknowledged_at?'تم الإقرار بالاطلاع':'بانتظار تأكيد الاطلاع'))+(x.acknowledged_at?' · '+esc(localDisplay(x.acknowledged_at)):'')+'</span>':'')+'</div>').join('')+'</details>'+(r.attachment_id?'<a class="button" href="file.php?id='+Number(r.attachment_id)+'" target="_blank" rel="noopener">'+esc(uiText('فتح المرفق'))+'</a>':'')+'</article>';}
async function noticeCompose32(){
 const data=await api('notifications',null,{box:'options'});
 modal('إنشاء إشعار','<form id="noticeForm32"><div class="form-grid"><label>'+esc(uiText('نوع الإشعار'))+'<select name="type">'+['announcement','circular','message','decision'].map(k=>'<option value="'+k+'">'+esc(uiText(noticeTypes32[k]))+'</option>').join('')+'</select></label><label>'+esc(uiText('الأهمية'))+'<select name="priority"><option value="normal">'+esc(uiText('عادي'))+'</option><option value="important">'+esc(uiText('مهم'))+'</option></select></label><label>'+esc(uiText('إرسال إلى'))+'<select name="audience" id="noticeAudience32">'+[['selected','موظفون محددون'],['all','الجميع ضمن الصلاحيات'],['company','شركة'],['department','قسم'],['group','مجموعة صلاحيات']].map(([k,l])=>'<option value="'+k+'">'+esc(uiText(l))+'</option>').join('')+'</select></label><label id="noticeAudienceValueLabel32">'+esc(uiText('اختيار المستلمين'))+'<select name="audience_value" id="noticeAudienceValue32"></select></label></div><div id="noticePeople32"><label>'+esc(uiText('بحث عن موظف'))+'<input id="noticePeopleSearch32" type="search"></label><div class="notice-people32">'+data.people.map(p=>'<label class="check" data-person-name="'+esc([p.name,p.name_en,p.code].join(' ').toLowerCase())+'"><input name="recipient" type="checkbox" value="'+p.id+'"><span>'+esc(p.code+' — '+(boot.localization?.language==='en'?(p.name_en||p.name):p.name))+'</span></label>').join('')+'</div></div>'+input('title','العنوان','','text','required maxlength="180"')+'<label>'+esc(uiText('الرسالة'))+'<textarea name="body" required maxlength="5000" rows="5"></textarea></label><label>'+esc(uiText('مرفق اختياري'))+'<input type="file" name="file" accept="image/jpeg,image/png,image/webp,application/pdf"></label>'+check('require_ack','يتطلب إقرار اطلاع',false)+'<p class="muted">'+esc(uiText('قراءة القرار وإقرار الاطلاع عليه لا يعنيان الموافقة على مضمونه.'))+'</p>'+(boot.user.role==='admin'?'<button type="button" id="noticeVideo32">'+esc(uiText('إنشاء طلب إقرار بالفيديو'))+'</button>':'')+'<div class="modal-foot"><button type="submit" class="primary">'+esc(uiText('إرسال الإشعار'))+'</button></div></form>');
 const f=$('#noticeForm32');noticePairForm169(f);const update=()=>{const mode=f.elements.audience.value;$('#noticePeople32').hidden=mode!=='selected';$('#noticeAudienceValueLabel32').hidden=['selected','all'].includes(mode);const entries=mode==='company'?data.companies.map(c=>[c.code,c.name]):mode==='department'?[...new Set(data.people.map(p=>p.department).filter(Boolean))].map(d=>[d,d]):data.groups.map(g=>[g.id,g.name]);$('#noticeAudienceValue32').innerHTML='<option value="">'+esc(uiText('اختر'))+'</option>'+entries.map(([k,l])=>'<option value="'+esc(k)+'">'+esc(uiText(l))+'</option>').join('');};$('#noticeAudience32').onchange=update;update();$('#noticePeopleSearch32').oninput=e=>{const q=e.target.value.toLowerCase().trim();f.querySelectorAll('[data-person-name]').forEach(l=>l.hidden=!l.dataset.personName.includes(q));};
 if($('#noticeVideo32'))$('#noticeVideo32').onclick=()=>{$('#modal').close();navigate('video_custom');};
 f.onsubmit=async e=>{e.preventDefault();const button=f.querySelector('[type=submit]');button.disabled=true;try{const body=new FormData(f);body.set('recipients',JSON.stringify([...f.querySelectorAll('[name=recipient]:checked')].map(c=>Number(c.value))));body.set('require_ack',f.elements.require_ack.checked?'1':'');const unlock=noticePairLock169(f);try{const saved=await api('notice_send',body);noticePairSaved169(f,saved.text_pair169);}finally{unlock();}$('#modal').close();noticeState32.box='sent';noticeState32.category='administration';noticeState32.type='all';noticeState32.status='all';noticeState32.search='';noticeState32.page=0;await notificationCenter32();toast('تم إرسال الإشعار');}catch(err){toast(err.message);}finally{button.disabled=false;}};
}

// Names belong to this authorized notice; never consult the global employee-name map.
function noticeText142(row,field){
 const en=boot.localization?.language==='en';const paired=row.meta?.text_pair169;if(field!=='recipient'&&paired?.[en?'en':'ar']&&typeof paired[en?'en':'ar'][field]==='string')return paired[en?'en':'ar'][field];if(field==='recipient')return String(en?(row.recipient_name_en||row.recipient_name||''):(row.recipient_name||''));
 let source=String(row[field]??'');if(en&&row.meta?.sender&&['announcement','circular','message','decision'].includes(row.meta?.type)&&typeof dataText137==='function'){const translated=dataText137('notice_text:'+field,source);if(translated!==source)return translated;}if(field==='body'&&row.meta?.type==='balance')source=source.replace(/ — مستوى \d+$/,'');
 const variants=new Map();for(const pair of row.name_bindings142||[])for(const name of [pair.ar,pair.en])if(name){if(variants.has(name)&&variants.get(name)?.id!==pair.id)variants.set(name,null);else if(!variants.has(name))variants.set(name,pair);}
 const names=[...variants].filter(([,pair])=>pair).map(([name])=>name).sort((a,b)=>b.length-a.length);
 const translate=text=>typeof noticeSegment164==='function'?noticeSegment164(text):uiText(text,false);if(!names.length)return translate(source);
 const pattern=new RegExp(names.map(name=>name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')).join('|'),'gu');let out='',offset=0;
 for(const match of source.matchAll(pattern)){const end=match.index+match[0].length,letter=/[\p{L}\p{N}_]/u;if((match.index&&letter.test(source[match.index-1]))||(end<source.length&&letter.test(source[end])))continue;const pair=variants.get(match[0]);out+=translate(source.slice(offset,match.index))+(en?(pair.en||pair.ar):(pair.ar||pair.en));offset=end;}
 return out+translate(source.slice(offset));
}
function noticeMarkup142(tag,row,field,className=''){return '<'+tag+' data-no-translate data-notice-text142="'+field+'" data-notice-id142="'+Number(row.id)+'"'+(className?' class="'+className+'"':'')+'>'+esc(noticeText142(row,field))+'</'+tag+'>';}
function refreshNoticeLanguage142(){
 const nodes=document.querySelectorAll('[data-notice-text142]');if(!nodes.length)return;const rows=new Map(noticeRows32.map(r=>[String(r.id),r]));
 for(const node of nodes){const row=rows.get(node.dataset.noticeId142);if(!row)continue;const text=noticeText142(row,node.dataset.noticeText142);if(node.textContent!==text)node.textContent=text;}
}

// Hidden counterparts do not participate in layout or generic dictionary translation.
function noticePairForm169(form){
 const language=boot.localization?.language==='en'?'en':'ar';form.dataset.noticeSource169=language;
 const hidden=(name,value='')=>{const n=document.createElement('input');n.type='hidden';n.name=name;n.value=value;n.setAttribute('data-no-translate','');form.append(n);return n;};
 hidden('source_language169',language);
 for(const field of ['title','body']){
  const input=form.elements.namedItem(field);input.setAttribute('data-no-translate','');input.lang=language;
  const own=hidden(field+'_'+language+'169'),other=hidden(field+'_'+(language==='ar'?'en':'ar')+'169');
  input.addEventListener('input',()=>{own.value=input.value;other.value='';});
 }
}
function noticePairSaved169(form,pair){
 for(const language of ['ar','en'])for(const field of ['title','body']){const node=form.elements.namedItem(field+'_'+language+'169');if(node)node.value=pair?.[language]?.[field]||'';}
}
function noticePairLock169(form){
 const nodes=[...form.querySelectorAll('button'),$('#closeModal')].filter(Boolean),before=nodes.map(n=>n.disabled),inert172=form.inert;
 form.inert=true;nodes.forEach(n=>n.disabled=true);return()=>{form.inert=inert172;nodes.forEach((n,i)=>n.disabled=before[i]);};
}
