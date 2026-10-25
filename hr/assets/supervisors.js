'use strict';
async function resetPassword31(id){
 if(!confirm(uiText('إنشاء كلمة مرور مؤقتة وإلغاء كلمة المرور السابقة؟')))return false;
 try{const result=await api('control31_reset',{user_id:id});modal('كلمة مرور مؤقتة','<p>'+esc(uiText('انسخها الآن وأرسلها للموظف بنفسك. لن تظهر مرة أخرى، ويلزم تغييرها عند الدخول.'))+'</p><p dir="ltr">'+esc(result.login)+'</p><input id="temporary31" readonly dir="ltr" value="'+esc(result.password)+'"><button id="copyTemporary31">'+esc(uiText('نسخ'))+'</button>');$('#modal').addEventListener('close',()=>$('#temporary31')?.remove(),{once:true});$('#copyTemporary31').onclick=async()=>{try{await navigator.clipboard.writeText($('#temporary31').value);toast('تم النسخ');}catch(e){$('#temporary31').select();toast('حدد النص وانسخه');}};installInterfaceLanguage();return true;}catch(e){toast(e.message);return false;}
}
async function supervisorsView31(){
 try{
  const info=await api('control31_supervisors');let busy=false;
  const inactive=info.users.filter(u=>!u.active&&!u.enabled);
  modal('المشرفون العامون','<p id="supervisorStatus32" role="status" aria-live="polite"></p>'+(inactive.length?'<button id="activateAccounts32" type="button">'+esc(uiText('تفعيل حسابات الموظفين غير المفعّلة'))+' ('+inactive.length+')</button>':'')+info.users.map(u=>'<div class="supervisor-row31"><span>'+esc(displayName31(u))+' <bdi>'+esc(u.employee_code||u.username)+'</bdi> <small data-account-state="'+u.id+'">'+esc(uiText(u.active?'الحساب مفعل':'الحساب غير مفعل'))+'</small></span><label class="check"><input type="checkbox" data-super31="'+u.id+'" '+(u.enabled?'checked':'')+' '+(u.owner?'disabled':'')+'>'+esc(uiText(u.owner?'مالك النظام':'مشرف عام'))+'</label></div>').join(''));
  const status=document.querySelector('#supervisorStatus32');
  const message=(text,error=false)=>{status.textContent=uiText(text);status.className=error?'error':'';};
  const lock=value=>{busy=value;document.querySelectorAll('[data-super31]').forEach(n=>n.disabled=value||!!info.users.find(u=>u.id===Number(n.dataset.super31))?.owner);const b=document.querySelector('#activateAccounts32');if(b)b.disabled=value;};
  document.querySelectorAll('[data-super31]').forEach(n=>n.onchange=async()=>{
   const user=info.users.find(u=>u.id===Number(n.dataset.super31)),value=n.checked,previous=!!user.enabled;
   if(busy){n.checked=previous;return;}
   // Let the browser paint the changed checkbox before the blocking confirmation.
   lock(true);await new Promise(resolve=>requestAnimationFrame(()=>setTimeout(resolve,0)));
   const question=value?(!user.active?'تفعيل حساب المستخدم وتعيينه مشرفًا عامًا بكل الصلاحيات؟':'تعيين هذا المستخدم مشرفًا عامًا بكل الصلاحيات؟'):'إلغاء تعيين هذا المستخدم مشرفًا عامًا؟';
   if(!confirm(uiText(question))){n.checked=previous;lock(false);return;}
   message('جارٍ الحفظ…');
   try{
    const result=await api('control31_supervisor_save',{user_id:user.id,enabled:value,activate:value&&!user.active,revision:info.revision});
    if(result.enabled!==value)throw Error(uiText('تعذر تأكيد حفظ الصلاحية؛ أعد فتح النافذة.'));
    user.enabled=result.enabled;user.active=result.active;info.revision=result.revision;n.checked=user.enabled;
    document.querySelector('[data-account-state="'+user.id+'"]').textContent=uiText(user.active?'الحساب مفعل':'الحساب غير مفعل');
    const bulk=document.querySelector('#activateAccounts32');if(bulk){const left=info.users.filter(u=>!u.active&&!u.enabled).length;if(left)bulk.textContent=uiText('تفعيل حسابات الموظفين غير المفعّلة')+' ('+left+')';else bulk.remove();}
    message(value?'تم تعيين المستخدم مشرفًا عامًا.':'تم إلغاء تعيين المستخدم مشرفًا عامًا.');
   }catch(e){n.checked=previous;message(e.message,true);}finally{lock(false);}
  });
  const activate=document.querySelector('#activateAccounts32');if(activate)activate.onclick=async()=>{
   if(busy)return;const ids=info.users.filter(u=>!u.active&&!u.enabled).map(u=>u.id);if(!ids.length)return;
   if(!confirm(uiText('تفعيل حسابات الموظفين المحددة دون تغيير صلاحياتهم؟')+' ('+ids.length+')'))return;
   lock(true);message('جارٍ الحفظ…');
   try{const result=await api('control31_activate_all',{ids});if(result.count!==ids.length)throw Error(uiText('تعذر تأكيد التفعيل؛ أعد فتح النافذة.'));info.users.filter(u=>ids.includes(u.id)).forEach(u=>{u.active=true;document.querySelector('[data-account-state="'+u.id+'"]').textContent=uiText('الحساب مفعل');});activate.remove();message('تم تفعيل حسابات الموظفين.');}catch(e){message(e.message,true);}finally{lock(false);}
  };
  installInterfaceLanguage();
 }catch(e){toast(e.message);}
}
