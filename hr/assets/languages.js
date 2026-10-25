'use strict';
let translationWorkerBusy=false,languageObserver=null;
const interfaceOriginalText=new Map();
async function languagesView(){
 const info=await api('languages_info'),s=info.settings,c=info.catalog;
 const options=(selected,keys=['ar','en','fil','hi','ur'])=>keys.map(key=>'<option value="'+key+'" '+(key===selected?'selected':'')+'>'+esc(c[key].name)+'</option>').join('');
 const review=(scope,title)=>'<section class="card space-bottom"><h2>'+title+'</h2>'+table(['اللغة','الاكتمال','الحالة','الإجراء'],info.public_review.filter(r=>r.scope===scope).map(r=>[esc(c[r.language].name),r.missing_count?'ينقص '+r.missing_count+' حقول':'مكتملة',r.approved?'متاحة':'تحتاج مراجعة','<details class="language-page-preview"><summary>معاينة الصفحات</summary><div>'+info.preview_pages.filter(p=>(p.slug==='offers')===(scope==='offers')).map(p=>'<a class="button" target="_blank" rel="noopener" href="public-preview.php?page='+encodeURIComponent(p.slug)+'&lang='+r.language+'">'+esc(cmsTitle(p))+'</a>').join('')+'</div></details> '+(r.language==='ar'?'لغة المصدر':r.approved?'معتمدة':'<button data-language="'+r.language+'" data-scope="'+scope+'" '+(r.missing_count?'disabled':'')+'>اعتماد بعد المراجعة</button>')]))+'</section>';
 $('#page').innerHTML=head('لغات الموقع','الترجمات محفوظة مع المحتوى؛ لا تتغير عند كل زيارة.')+'<section class="card"><button id="dataTranslations31">'+esc(uiText('ترجمة بيانات الإدارة والموظفين'))+'</button></section>'+review('site','الموقع التعريفي — اللغات الخمس')+review('offers','العروض — اللغات المختارة')+'<form id="languagesForm" class="card"><div class="form-grid"><label>اللغة الافتراضية للموقع<select name="public_default_language">'+options(s.public_default_language)+'</select></label><label>اللغة الافتراضية للعروض<select name="offers_default_language">'+options(s.offers_default_language)+'</select></label></div><fieldset class="language-options"><legend>لغات العروض</legend>'+['ar','en','fil','hi','ur'].map(key=>'<label class="check"><input name="offers_languages" type="checkbox" value="'+key+'" '+(s.offers_languages.includes(key)?'checked':'')+'>'+esc(c[key].name)+'</label>').join('')+'</fieldset>'+(info.english_ready?'<label>لغة المشرف<select name="admin_default_language">'+options(s.admin_default_language,['ar','en'])+'</select></label><label>لغة الموظفين الافتراضية<select name="employee_default_language">'+options(s.employee_default_language,['ar','en'])+'</select></label>':'')+'<button class="primary">حفظ اللغات</button></form>';
 $('#dataTranslations31').onclick=dataTranslationsView31;
 document.querySelectorAll('[data-scope]').forEach(button=>button.onclick=()=>modal('اعتماد '+c[button.dataset.language].name,'<form id="modalForm">'+check('confirm_review','راجعت الترجمة وأوافق على نشر هذه النسخة',false)+saveButton()+'</form>',async f=>{const r=info.public_review.find(r=>r.scope===button.dataset.scope&&r.language===button.dataset.language);await api('public_language_review',{language:r.language,scope:r.scope,confirm_review:f.confirm_review.checked,fingerprint:r.fingerprint});await languagesView();toast('تم اعتماد اللغة لهذا الجزء');}));
 $('#languagesForm').onsubmit=async e=>{e.preventDefault();try{const f=e.target,b=Object.fromEntries(new FormData(f));b.offers_languages=[...f.querySelectorAll('[name=offers_languages]:checked')].map(n=>n.value);b.public_languages=['ar','en','fil','hi','ur'];b.employee_languages=['ar','en'];b.admin_default_language=b.admin_default_language||'ar';b.employee_default_language=b.employee_default_language||'ar';b.translation_enabled=false;b.translation_budget=s.translation_budget;b.translation_context=s.translation_context;await api('languages_save',b);await loadBoot();await languagesView();toast('تم حفظ اللغات');}catch(error){toast(error.message);}};
}
async function startTranslationWorker(){
 if(translationWorkerBusy||boot?.user?.role!=='admin'||!boot.settings.translation_enabled)return;translationWorkerBusy=true;
 try{for(let i=0;i<150&&boot?.user?.role==='admin'&&document.visibilityState!=='hidden';i++){const r=await api('translation_process',{});const n=$('#translationProgress');if(n)n.textContent=r.reason||'تمت معالجة خطوة: '+r.status;if(!r.processed||r.status==='failed')break;await new Promise(resolve=>setTimeout(resolve,120));}}catch(e){toast(e.message);}finally{translationWorkerBusy=false;}
}
let normalizedInterfaceDictionary=null,interfaceDictionarySource=null;
function applyInterfaceLocale138(){const loc=boot?.localization;if(!loc)return;document.documentElement.lang=loc.language;document.documentElement.dir=loc.catalog?.[loc.language]?.dir||(loc.language==='en'?'ltr':'rtl');}
function uiText(source,useDataLabels=true){
 if(source==='COMMERCAIL BANK OF KUWAIT')source='COMMERCIAL BANK OF KUWAIT';
 if(boot?.localization?.language!=='en')return source;source=String(source);const dictionary=boot.localization.dictionary||{};
 if(interfaceDictionarySource!==dictionary){interfaceDictionarySource=dictionary;normalizedInterfaceDictionary=new Map(Object.entries(dictionary).map(([k,v])=>[k.trim().replace(/\s+/g,' '),v]));}
 const count138=source.match(/^(.*?)\s*\((\d+)\)$/);if(count138)return uiText(count138[1].trim(),useDataLabels)+' ('+count138[2]+')';
 const lookup=value=>((useDataLabels&&typeof dataLabels31!=='undefined')?dataLabels31.get(value.trim()):null)||dictionary[value]||normalizedInterfaceDictionary.get(value.trim().replace(/\s+/g,' '));const exact=lookup(source);if(exact)return exact;if(!/[\u0600-\u06ff]/.test(source))return source;
 for(const [prefix,value]of Object.entries(dictionary)){if(/[:：،\s]$/.test(prefix)&&source.startsWith(prefix))return value+uiText(source.slice(prefix.length),useDataLabels);}
 return source.split(/([0-9]+(?:[.,][0-9]+)*|[A-Za-z][A-Za-z ._-]*|[·:/…،+↗←—])/).map(part=>{const key=part.trim(),translation=lookup(key);return translation?part.replace(key,translation):part;}).join('');
}
const interfaceOriginalAttributes32=new WeakMap(),interfaceLastAttributes32=new WeakMap(),interfaceLastText32=new WeakMap();
let reverseDictionarySource32=null,reverseDictionary32=new Map();
function interfaceArabic32(text){
 const count138=text.match(/^(.*?)\s*\((\d+)\)$/);if(count138)return interfaceArabic32(count138[1].trim())+' ('+count138[2]+')';
 const dict=boot.localization.dictionary||{};
 if(reverseDictionarySource32!==dict){reverseDictionarySource32=dict;reverseDictionary32=new Map();for(const [ar,en]of Object.entries(dict)){if(typeof en==='string'&&/[\u0600-\u06ff]/.test(ar)&&ar!==en&&!reverseDictionary32.has(en.trim()))reverseDictionary32.set(en.trim(),ar);}}
 reverseDictionary32.set('Clock In / Out','بصمة الدوام');
 const lookup=value=>{if(typeof dataLabels31!=='undefined'){for(const [ar,en]of dataLabels31)if(en===value)return ar;}return reverseDictionary32.get(value);};
 const exact=lookup(text.trim());if(exact)return text.replace(text.trim(),exact);
 return text.split(/([0-9]+(?:[.,][0-9]+)*|[·:/…،+↗←—])/).map(part=>{const found=lookup(part.trim());return found?part.replace(part.trim(),found):part;}).join('');
}
function applyInterfaceLanguage(){
 if(typeof personalControls34==='function')personalControls34();
 if(!boot?.localization)return;languageObserver?.disconnect();try{const loc=boot.localization;applyInterfaceLocale138();
 document.querySelectorAll('.portal-privacy a').forEach(link=>link.setAttribute('href','privacy.php?lang='+encodeURIComponent(loc.language)));
 for(const node of interfaceOriginalText.keys())if(!node.isConnected)interfaceOriginalText.delete(node);
 if(!document.createTreeWalker)return;
 document.querySelectorAll('.leave-name32[data-name-ar]').forEach(el=>{const name=loc.language==='en'?el.dataset.nameEn:el.dataset.nameAr;if(el.textContent!==name)el.textContent=name;});
 const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT),nodes=[];while(walker.nextNode())nodes.push(walker.currentNode);
 for(const node of nodes){
  if(node.parentElement?.closest('script,style,textarea,input,video,pre,code,[data-no-translate]'))continue;
  // Remember Arabic source even when a screen was first rendered in English.
  const current=node.textContent,known=interfaceOriginalText.get(node);
  const source=known&&(current===known||current===interfaceLastText32.get(node))?known:interfaceArabic32(current);
  const next=loc.language==='en'?source.replace(source.trim(),typeof interfaceDataText164==='function'?interfaceDataText164(source.trim()):uiText(source.trim())):source;
  if(node.parentElement?.tagName==='OPTION'&&!node.parentElement.hasAttribute('value'))node.parentElement.setAttribute('value',current);
  interfaceOriginalText.set(node,source);interfaceLastText32.set(node,next);if(next!==current)node.textContent=next;
 }
 for(const element of document.querySelectorAll('[placeholder],[title],[aria-label],[aria-description],[alt],[data-label]')){
  if(element.closest('[data-no-translate]'))continue;
  let last=interfaceLastAttributes32.get(element);if(!last){last={};interfaceLastAttributes32.set(element,last);}
  let originals=interfaceOriginalAttributes32.get(element);if(!originals){originals={};interfaceOriginalAttributes32.set(element,originals);}
  for(const attribute of ['placeholder','title','aria-label','aria-description','alt','data-label']){
   const value=element.getAttribute(attribute);if(!value)continue;
   const original=originals[attribute],source=original&&(value===original||value===last[attribute])?original:interfaceArabic32(value);
   originals[attribute]=source;const next=loc.language==='en'?uiText(source):source;last[attribute]=next;if(next!==value)element.setAttribute(attribute,next);
  }
 }
 if(typeof refreshDataLanguage138==='function')refreshDataLanguage138();
 if(typeof refreshEmployeeNames143==='function')refreshEmployeeNames143();
 if(typeof refreshNoticeLanguage142==='function')refreshNoticeLanguage142();
 if(typeof refreshEmployeeNavigation138==='function')refreshEmployeeNavigation138();
 document.querySelectorAll('select').forEach(select=>{if(select._choiceLocale138!==loc.language){select._choiceLocale138=loc.language;select._refreshChoice138?.();}});
 document.querySelectorAll('.date-calendar32').forEach(calendar=>{if(calendar._locale138!==loc.language){calendar._locale138=loc.language;calendar._refreshLocale138?.();}});
 }finally{languageObserver?.observe(document.body,{childList:true,subtree:true,characterData:true});}
}

function installInterfaceLanguage(){
 if(typeof MutationObserver==='undefined')return;if(languageObserver)languageObserver.disconnect();let scheduled=false;languageObserver=new MutationObserver(records=>{if(records.length&&records.every(r=>(r.target.nodeType===1?r.target:r.target.parentElement)?.closest('[data-live-clock32]')))return;if(records.length&&records.every(r=>{const el=r.target.nodeType===1?r.target:r.target.parentElement;if(el?.closest('[data-live-clock32],#toast,.select-options32,.date-calendar32'))return true;if(r.type==='characterData')return !/[\u0600-\u06ffA-Za-z]/.test(r.target.textContent||'');return ![...r.addedNodes].some(n=>n.nodeType===1||/[\u0600-\u06ffA-Za-z]/.test(n.textContent||''));}))return;if(scheduled)return;scheduled=true;requestAnimationFrame(()=>{scheduled=false;applyInterfaceLanguage();});});languageObserver.observe(document.body,{childList:true,subtree:true,characterData:true});applyInterfaceLanguage();
}
function languagePicker(){const loc=boot.localization;if(!loc)return '';return '<select id="interfaceLanguage" aria-label="Language">'+loc.allowed.map(k=>'<option value="'+k+'" '+(k===loc.language?'selected':'')+'>'+esc(loc.catalog[k].name)+'</option>').join('')+'</select>'+(loc.translated_count<loc.source_count?'<span class="language-pending" title="Translation pending — some text is shown in Arabic">◌</span>':'');}
