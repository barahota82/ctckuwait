'use strict';
function identityPicker32(){
 const i=boot.identity32;if(!i)return '';
 if(!i.has_workspace||!i.has_employee)return '<span class="identity-mode32">'+esc(uiText(i.has_employee?'مساحتي':'مساحة العمل'))+'</span>';
 return '<select id="accessMode29" class="choice-only32" data-choice-only32="1" aria-label="'+esc(uiText('وضع العرض'))+'"><option value="personal" '+(i.mode==='personal'?'selected':'')+'>'+esc(uiText('مساحتي'))+'</option><option value="team" '+(i.mode==='team'?'selected':'')+'>'+esc(uiText('مساحة العمل'))+'</option></select>';
}
function identityExpired32(sessionExpired120=false){
 if(typeof backupSession111!=='undefined'&&backupSession111.held)return;
 ++navigationEpoch32;pendingReads32.forEach(c=>c.abort());pendingReads32.clear();closeCamera();stopLocation();users=[];
 const dialog=$('#modal');if(dialog?.open)dialog.close();
 $('#app').innerHTML='<main class="install-card"><h1>'+esc(uiText(sessionExpired120?'انتهت جلسة الدخول':'تغير وضع الحساب'))+'</h1><p>'+esc(uiText(sessionExpired120?'سجل الدخول مجددًا للمتابعة. لم تُعد العملية تلقائيًا.':'أعد تحميل الصفحة للمتابعة بالوضع الحالي. لم تُعد العملية تلقائيًا.'))+'</p><button id="identityReload32">'+esc(uiText(sessionExpired120?'تسجيل الدخول':'إعادة تحميل'))+'</button></main>';
 $('#identityReload32').onclick=()=>location.reload();
}
async function switchIdentity32(select){
 if(typeof accessDirty30!=='undefined'&&accessDirty30&&!confirm(uiText('توجد تعديلات غير محفوظة. هل تريد تركها؟'))){select.value=boot.identity32.mode;return;}
 select.disabled=true;
 try{await api('identity32_mode',{mode:select.value});closeCamera();stopLocation();++navigationEpoch32;pendingReads32.forEach(c=>c.abort());pendingReads32.clear();users=[];photo=null;pending=null;captureTicket='';permissionSelected183=0;recordState173.clear();noticeOwner32=null;noticeRows32=[];noticeLoaded139=false;const d=$('#modal');if(d?.open)d.close();view='dashboard';await start();}
 catch(e){select.disabled=false;select.value=boot.identity32.mode;toast(e.message);}
}
