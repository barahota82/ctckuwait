'use strict';
// Connection-only state. No response payloads, employee records or queued writes are stored.
const connection32=(()=>{
 let blocked=false,checking=null,installed=false,writeCount=0,epoch=0,reason='server',interval=null,lastHealthy=Date.now(),failures=0,healthVersion=0;
 const text=(ar,en)=>document.documentElement.lang==='en'?en:ar;
 const uncertain=()=>{try{sessionStorage.setItem('hr-uncertain-write32','1');}catch(e){}};
 const unknown=()=>{try{return sessionStorage.getItem('hr-uncertain-write32')==='1';}catch(e){return false;}};
 function render(){let box=document.getElementById('connectionBarrier32');if(!box){box=document.createElement('section');box.id='connectionBarrier32';box.setAttribute('role','alertdialog');box.setAttribute('aria-modal','true');box.setAttribute('data-no-translate','');document.body.append(box);}box.lang=document.documentElement.lang;box.dir=document.documentElement.dir;
 box.replaceChildren();const title=document.createElement('h1');title.textContent=reason==='write'?text('لم يتأكد حفظ العملية','The save could not be confirmed.'):reason==='offline'?text('انقطع الاتصال بالإنترنت','Internet connection lost.'):text('تعذر الاتصال بالخادم','Unable to connect to the server.');box.append(title);
 const message=document.createElement('p');message.textContent=text('البيانات محجوبة حتى عودة الاتصال والتحقق من الخادم. ستُحمّل الصفحة ببيانات جديدة.','Data is hidden until the server connection is verified. The page will reload with fresh data.');box.append(message);
 if(unknown()){const warning=document.createElement('p');warning.textContent=text('لم يتأكد حفظ آخر عملية. راجع السجلات بعد الاتصال قبل إعادة إرسالها لتجنب التكرار.','The last save could not be confirmed. Check the records after reconnecting before submitting it again.');box.append(warning);}
 const retry=document.createElement('button');retry.type='button';retry.textContent=text('إعادة الاتصال','Reconnect');retry.onclick=()=>recover();box.append(retry);box.hidden=false;retry.focus();
 }
 function lock(kind='server'){
  if(!blocked){epoch++;blocked=true;try{sessionStorage.setItem('hr-resume-screen32',typeof view==='string'?view:'dashboard');}catch(e){}}
  const dialog=document.querySelector('#modal');if(dialog?.open&&typeof dialog.close==='function')dialog.close();
  reason=kind==='write'?'write':navigator.onLine===false?'offline':kind;if(writeCount)uncertain();document.documentElement.classList.add('connection-blocked32');document.querySelector('#app')?.setAttribute('inert','');document.querySelector('#modal')?.setAttribute('inert','');render();
  if(typeof closeCamera==='function')closeCamera();if(typeof stopLocation==='function')stopLocation();
 }
 async function ping(){const nonce=String(Date.now())+'-'+Math.random().toString(36).slice(2),controller=new AbortController(),timer=setTimeout(()=>controller.abort(),6000);try{const r=await fetch('connection.php?nonce='+encodeURIComponent(nonce),{cache:'no-store',credentials:'same-origin',signal:controller.signal,headers:{'Accept':'application/json'}});if(!r.ok)throw Error('server');const data=await r.json();if(data.nonce!==nonce||data.ok!==true)throw Error('server');}finally{clearTimeout(timer);}}
 function success(){lastHealthy=Date.now();failures=0;healthVersion++;}
 async function recover(force=false,foreground=false){
  if(typeof backupSession111!=='undefined'&&backupSession111.held)return false;
  if(checking)return checking;
  if(!blocked&&!force&&Date.now()-lastHealthy<60000)return true;
  checking=(async()=>{const observed=healthVersion;
   try{await ping();success();if(!blocked)return true;
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),20000);
    try{const r=await fetch('api.php?action=bootstrap',{cache:'no-store',credentials:'same-origin',signal:controller.signal});const b=await r.json();if(!r.ok||b.error||!b.data?.localization)throw Error('server');}finally{clearTimeout(timer);}
    location.reload();return true;
   }catch(e){
    // One missed background probe must not discard a working screen or unsaved form.
    if(!blocked&&healthVersion!==observed){failures=0;return true;}
    failures++;if(blocked||(foreground&&navigator.onLine===false))lock(navigator.onLine===false?'offline':'server');return false;
   }finally{checking=null;}
  })();return checking;
 }
 async function ensure(){
  if(blocked){recover(true);throw Error(text('انقطع الاتصال؛ انتظر إعادة تحميل البيانات.','Connection lost; wait for fresh data to load.'));}
  // The actual write is the authenticated request; do not add a ping before every save.
 }
 async function requestFailed(write=false){
  if(write){uncertain();lock('write');return false;}
  return recover(true,true);
 }
 function install(){if(installed)return;installed=true;
  window.addEventListener('offline',()=>recover(true));window.addEventListener('online',()=>recover(true));
  window.addEventListener('pageshow',event=>{if(event.persisted)recover(true);});
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)recover();});
  for(const event of ['click','submit','keydown'])document.addEventListener(event,e=>{if(blocked&&!e.target.closest?.('#connectionBarrier32')){e.preventDefault();e.stopImmediatePropagation();}},true);
  interval=setInterval(()=>{if(!document.hidden)recover();},60000);
  if(navigator.onLine===false)recover(true);
 }
 function resumeScreen(){try{const key=sessionStorage.getItem('hr-resume-screen32');sessionStorage.removeItem('hr-resume-screen32');return key;}catch(e){return null;}}
 function showUncertain(){if(!unknown())return;let banner=document.getElementById('uncertainWrite32');if(banner)return;banner=document.createElement('aside');banner.id='uncertainWrite32';banner.className='error';banner.setAttribute('role','alert');banner.setAttribute('data-no-translate','');banner.textContent=text('لم يتأكد حفظ عملية قبل انقطاع الاتصال. راجع سجلها قبل إعادة الإرسال؛ لم تتم إعادة إرسالها تلقائيًا.','A save before the connection loss could not be confirmed. Check its record before submitting again; it was not retried automatically.');const b=document.createElement('button');b.type='button';b.textContent=text('إغلاق التنبيه','Dismiss');b.onclick=()=>{try{sessionStorage.removeItem('hr-uncertain-write32');}catch(e){}banner.remove();};banner.append(b);document.querySelector('.workspace')?.prepend(banner);}
 return {install,ensure,lock,recover,success,requestFailed,resumeScreen,showUncertain,uncertain,get blocked(){return blocked;},get epoch(){return epoch;},beginWrite(){writeCount++;},endWrite(){writeCount=Math.max(0,writeCount-1);}};
})();
