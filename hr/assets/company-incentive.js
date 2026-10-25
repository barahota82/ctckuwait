'use strict';
let incentiveTermsBusy114=false;
async function incentiveTerms114(user){
 if(!user?.id||boot.user.role!=='admin')return;
 if(incentiveTermsBusy114)return;incentiveTermsBusy114=true;const epoch=navigationEpoch32,button=document.getElementById('incentiveTerms114');if(button){button.disabled=true;button.setAttribute('aria-busy','true');}try{
 const x=await api('incentive114_info',null,{user_id:user.id});if(epoch!==navigationEpoch32||view!=='users'||Number(profileUser29?.id)!==Number(user.id))return;
 modal('حافز شهري من شركة أخرى','<form id="modalForm"><input type="hidden" name="user_id" value="'+Number(user.id)+'"><p>'+esc(user.employee_code+' — '+displayName31(user))+'</p><p>'+esc(uiText('استحقاق مستقل لا يدخل في قيمة الإجازات أو نهاية الخدمة'))+'</p><label>'+esc(uiText('الشركة'))+'<select name="company_code">'+x.companies.map(c=>'<option value="'+esc(c.code)+'">'+esc(c.name)+'</option>').join('')+'</select></label>'+input('amount','المبلغ د.ك',0,'number','required min="0.001" step="0.001"')+input('from','بداية السريان',dateNow(),'date','required')+input('to','نهاية السريان — اختياري','','date')+saveButton()+'</form>',async f=>{const result=await api('incentive114_terms',Object.fromEntries(new FormData(f)));await incentiveEmployee115(user);return result;});
 }finally{incentiveTermsBusy114=false;if(button?.isConnected){button.disabled=false;button.removeAttribute('aria-busy');}}
}
function incentiveDetails114(r){
 const admin=boot.user.role==='admin';modal('حافز شهري من شركة أخرى','<p>'+esc(r.code+' — '+r.name+' — '+r.company_name)+'</p>'+detail26({month:r.month,amount:r.amount,status:r.status})+(r.deduction123?'<p>'+esc(uiText('إجمالي الخصومات'))+': '+money26(r.deduction123)+' · '+esc(uiText('الصافي'))+': '+money26(r.net_amount123)+'</p>':'')+'<p>'+esc(uiText(r.paid?'تم الصرف':'لم يتم الصرف'))+'</p>'+(r.payment_date?'<p>'+esc(dateDisplay32(r.payment_date))+' — '+esc(r.reference)+'</p>':'')+'<p>'+esc(uiText('لا يدخل في قيمة الإجازات أو نهاية الخدمة'))+'</p>'+(admin&&!r.record_id?'<button type="button" class="primary" id="approveIncentive114">'+esc(uiText('اعتماد نهائي'))+'</button>':'')+(admin&&r.record_id&&!r.paid?'<form id="modalForm">'+input('payment_date','تاريخ الصرف',dateNow(),'date','required')+input('reference','مرجع الصرف','','text','required')+'<div class="modal-foot"><button type="submit" class="primary">'+esc(uiText('تسجيل الصرف'))+'</button></div></form>':''),admin&&r.record_id&&!r.paid?f=>api('incentive114_pay',{user_id:r.user_id,company_code:r.company_code,month:r.month,payment_date:f.elements.namedItem('payment_date').value,reference:f.elements.namedItem('reference').value}):null);
 const button=document.getElementById('approveIncentive114');if(button)button.onclick=async()=>{button.disabled=true;try{await api('incentive114_approve',{user_id:r.user_id,company_code:r.company_code,month:r.month});document.getElementById('modal').close();await payrollGrid37('payroll');}catch(e){button.disabled=false;toast(e.message);}};
}
const incentivePayroll114=payrollGrid37;
payrollGrid37=async function(table='payroll'){
 await incentivePayroll114(table);if(table!=='payroll'||view!=='payroll')return;
 document.querySelectorAll('[data-incentive114]').forEach(b=>b.onclick=async()=>{const epoch=navigationEpoch32;b.disabled=true;try{const x=await api('incentive114_info',null,{month:payMonthValue35()});if(epoch!==navigationEpoch32||view!=='payroll'||!b.isConnected)return;const r=x.rows.find(r=>r.user_id===Number(b.dataset.incentive114)&&r.company_code===b.dataset.company114);if(r)incentiveDetails114(r);}catch(e){toast(e.message);}finally{b.disabled=false;}});
};
const incentivePersonal114=v26Payroll;
v26Payroll=async function(){
 await incentivePersonal114();if(boot.access_mode!=='personal'||boot.user.role==='admin'||view!=='payroll')return;const epoch=navigationEpoch32;
 try{const x=await api('incentive114_info',null,{month:payMonthValue35()});if(epoch!==navigationEpoch32||!x.rows.length)return;const card=document.createElement('section');card.className='card';card.innerHTML='<h2>'+esc(uiText('حافز شهري من شركة أخرى'))+'</h2>'+table(['الشركة','الشهر','المبلغ د.ك','الحالة','تاريخ الصرف'],x.rows.map(r=>[esc(r.company_name),esc(r.month),money26(r.net_amount123??r.amount),esc(uiText(r.paid?'تم الصرف':r.status==='approved'?'معتمد':'مسودة')),esc(r.payment_date?dateDisplay32(r.payment_date):'—')]));document.getElementById('page').append(card);applyInterfaceLanguage();}catch(e){toast(e.message);}
};

function incentiveEmployeeHtml115(user){return boot.user.role==='admin'?'<section class="incentive-employee115"><h3>'+esc(uiText('حافز شهري من شركة أخرى'))+'</h3><p class="muted">'+esc(uiText('استحقاق مستقل لا يدخل في قيمة الإجازات أو نهاية الخدمة'))+'</p><button type="button" id="incentiveTerms114" class="primary">'+esc(uiText('حافز شركة أخرى'))+'</button><div data-incentive-terms115 data-user115="'+Number(user.id)+'"></div></section>':'';}
async function incentiveEmployee115(user){
 const host=document.querySelector('[data-incentive-terms115]'),button=document.getElementById('incentiveTerms114'),epoch=navigationEpoch32;if(!host||Number(host.dataset.user115)!==Number(user.id))return;
 if(button)button.onclick=()=>incentiveTerms114(user).catch(e=>toast(e.message));
 try{const x=await api('incentive114_info',null,{user_id:user.id});if(epoch!==navigationEpoch32||!host.isConnected)return;host.innerHTML=x.terms.length?table(['الشركة','المبلغ د.ك','بداية السريان','نهاية السريان — اختياري'],x.terms.map(r=>[esc(r.company_name),money26(r.amount),esc(dateDisplay32(r.from)),esc(r.to?dateDisplay32(r.to):uiText('مستمر'))])):empty();applyInterfaceLanguage();}catch(e){if(host.isConnected)host.textContent=e.message;}
}
