'use strict';
// The live display is derived from a server sample and monotonic elapsed time.
// Stored timestamps and attendance calculations are never updated by this clock.
let portalEpoch32=0,portalTick32=0,portalClockTimer32=null,portalSyncTimer32=null,portalSyncBusy32=false,portalClockGeneration32=0;
let portalClockFormat32=null,portalClockFormatKey32='';
function anchorPortalClock32(epoch){if(Number.isFinite(epoch)){portalEpoch32=epoch;portalTick32=performance.now();}}
function portalNow32(){return portalEpoch32+Math.max(0,performance.now()-portalTick32);}
function renderPortalClock32(){
 const node=document.querySelector('.topbar .time-text');if(!node||!portalEpoch32)return;
 node.setAttribute('data-no-translate','');node.setAttribute('data-live-clock32','');
 const lang=boot.localization.language,zone=boot.company.timezone||'Asia/Kuwait',key=lang+'|'+zone;
 if(key!==portalClockFormatKey32){portalClockFormatKey32=key;portalClockFormat32=new Intl.DateTimeFormat(lang==='en'?'en-GB':'ar-KW',{timeZone:zone,day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false,numberingSystem:'latn'});}
 const text=portalClockFormat32.format(new Date(portalNow32()))+' · '+zone;
 if(node.textContent!==text)node.textContent=text;
}
async function syncPortalClock32(){
 if(!boot?.user||portalSyncBusy32||document.visibilityState==='hidden')return;
 const generation=portalClockGeneration32,userId=boot.user.id;portalSyncBusy32=true;
 try{const before=performance.now(),r=await api('server_clock'),after=performance.now();
  if(generation!==portalClockGeneration32||boot?.user?.id!==userId)return;
  if(Number.isFinite(r.server_epoch_ms)){anchorPortalClock32(r.server_epoch_ms+(after-before)/2);renderPortalClock32();}
 }catch(error){/* Retain the last server sample; never replace it with the device clock. */}
 finally{portalSyncBusy32=false;}
}
function stopPortalClock32(){portalClockGeneration32++;if(portalClockTimer32!==null)clearInterval(portalClockTimer32);if(portalSyncTimer32!==null)clearInterval(portalSyncTimer32);portalClockTimer32=null;portalSyncTimer32=null;}
function startPortalClock32(){stopPortalClock32();renderPortalClock32();portalClockTimer32=setInterval(renderPortalClock32,1000);portalSyncTimer32=setInterval(syncPortalClock32,60000);syncPortalClock32();}
let portalLanguageQueue32=Promise.resolve(),portalLanguageSequence32=0;
function refreshPortalIdentity32(){
 const en=boot.localization.language==='en';
 const name=document.querySelector('.sidebar .brand h2');if(name){name.setAttribute('data-no-translate','');name.textContent=companyName();}
 const userName=(en?(boot.user.profile?.name_en||boot.user.name):boot.user.name)||'';
 const short=document.querySelector('.account-name b');if(short){short.setAttribute('data-no-translate','');short.textContent=userName.trim().split(/\s+/)[0];}
 const avatar=document.querySelector('.topbar .avatar');if(avatar){avatar.setAttribute('data-no-translate','');avatar.textContent=userName.slice(0,1);}
 document.querySelectorAll('.install-shortcut31').forEach(n=>{n.setAttribute('data-no-translate','');n.textContent=uiText('إضافة إلى جهازك');});
 document.querySelectorAll('.brand-logo').forEach(n=>n.alt=en?'Company logo':'شعار الشركة');
 document.title=(boot.company.public_site_title?.[boot.localization.language]||companyName())+' — '+uiText('شؤون الموظفين');
 renderPortalClock32();
}
function switchPortalLanguage32(language){
 if(!boot.localization.allowed.includes(language))return;
 boot.localization.language=language;applyInterfaceLanguage();refreshPortalIdentity32();
 try{sessionStorage.setItem('click_login_language',language);}catch(error){}
 const sequence=++portalLanguageSequence32,userId=boot.user.id;
 // Persist in order, so rapid toggles cannot leave the session in an older language.
 portalLanguageQueue32=portalLanguageQueue32.catch(()=>{}).then(async()=>{
  if(sequence!==portalLanguageSequence32||boot?.user?.id!==userId)return;
  try{await api('language_select',{language});}catch(error){if(sequence===portalLanguageSequence32)toast(error.message);}
 });
}
function setMobileMenu32(open){
 const sidebar=document.querySelector('.sidebar');if(!sidebar)return;
 sidebar.classList.toggle('open',open);
 const backdrop=document.querySelector('.sidebar-backdrop32');if(backdrop)backdrop.hidden=!open;
 const button=document.querySelector('.mobile-menu');if(button)button.setAttribute('aria-expanded',String(open));
}
document.addEventListener('click',event=>{
 if(!window.matchMedia('(max-width:760px)').matches||!document.querySelector('.sidebar.open'))return;
 if(event.target.closest('.sidebar,.mobile-menu,.sidebar-backdrop32'))return;
 setMobileMenu32(false);event.preventDefault();event.stopPropagation();
},true);
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.querySelector('.sidebar.open')){setMobileMenu32(false);document.querySelector('.mobile-menu')?.focus();}});
document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible'){renderPortalClock32();syncPortalClock32();}});
window.addEventListener('focus',()=>{renderPortalClock32();syncPortalClock32();});

function showAccountDetails32(){
 if(!boot?.user)return;
 const en=boot.localization.language==='en',name=en?(boot.user.profile?.name_en||boot.user.name):boot.user.name;
 const role=boot.user.role==='admin'?(en?'System administrator':'المشرف العام'):(en?'Employee':'موظف');
 modal(en?'Account details':'بيانات الحساب','<div class="account-full-details32" data-no-translate><div class="detail-row"><b>'+esc(en?'Full name':'الاسم الكامل')+'</b><span>'+esc(name)+'</span></div><div class="detail-row"><b>'+esc(en?'Account role':'صفة الحساب')+'</b><span>'+esc(role)+'</span></div></div>');
}
document.addEventListener('keydown',event=>{
 if((event.key==='Enter'||event.key===' ')&&event.target.closest('.account-name[data-action="account-details"]')){event.preventDefault();showAccountDetails32();}
});

function layoutPortalHeader32(){
 const header=document.querySelector('.topbar');if(!header)return;
 const first=header.querySelector('.topbar-right'),tools=header.querySelector('.account-tools');
 const avatar=header.querySelector('.avatar'),name=header.querySelector('.account-name');if(!first||!tools||!avatar||!name)return;
 let identity=first.querySelector('.mobile-identity32');
 if(window.matchMedia('(max-width:760px)').matches){
  if(!identity){identity=document.createElement('div');identity.className='mobile-identity32';first.appendChild(identity);}
  if(avatar.parentElement!==identity)identity.appendChild(avatar);
  if(name.parentElement!==identity)identity.appendChild(name);
 }else if(identity){
  const logout=tools.querySelector('[data-action="logout"]');tools.insertBefore(avatar,logout);tools.insertBefore(name,logout);identity.remove();
 }
}
window.addEventListener('resize',layoutPortalHeader32);
