'use strict';
let presenceStream200=null,presencePhoto200=null;
function presenceStop200(){if(presenceStream200){presenceStream200.getTracks().forEach(track=>track.stop());presenceStream200=null;}}
function presenceCard200(staff){
 const rule=staff.presence_rule,mode=!rule||rule.mode==='off'?uiText('غير مفعّل'):rule.mode==='court'?uiText('مندوب محكمة'):uiText('بمواعيد في أيام الدوام');
 const capture=!rule?'—':rule.capture==='photo'?uiText('صورة وموقع'):uiText('موقع فقط');
 const extra=rule&&rule.mode==='scheduled'?field32('المواعيد',(rule.times||[]).join(' · ')||'—')+field32('المهلة',(rule.window_minutes||0)+' '+uiText('دقيقة'))+field32('خصم الراتب',uiText(rule.penalty&&rule.penalty.enabled?'مفعّل':'غير مفعّل')):(rule&&rule.mode==='court'?field32('الخصم والتقييم',uiText('بلا خصم تلقائي وبلا تقييم تلقائي')):'');
 const canEdit=boot.access_mode!=='personal'&&(boot.user.role==='admin'||(typeof teamPermission==='function'&&teamPermission('policy_staff','edit')));
 return '<div><h3>'+esc(uiText('مواعيد إثبات التواجد'))+'</h3>'+field32('الحالة',mode)+field32('الإرسال',capture)+extra+'<p class="tiny muted">'+esc(uiText(rule&&rule.mode==='court'?'الإرسال يُقبل داخل محكمة محفوظة، والتقرير يعرض المحكمة والوقت.':'الأيام تُقرأ من جدول الدوام الظاهر، ولا يُنشئ الحفظ نسخة جديدة من الدوام.'))+'</p><div class="record-actions32">'+(canEdit?'<button type="button" data-presence-edit="'+staff.id+'">'+esc(uiText('إعداد مواعيد إثبات التواجد'))+'</button>':'')+'<button type="button" data-presence-log="'+staff.id+'">'+esc(uiText('سجل الإرسال'))+'</button></div></div>';
}
function presenceRead200(form){
 const mode=form.mode.value,unit=form.tier_unit.value,times=mode==='scheduled'?form.times.value.split('\n').map(line=>line.trim()).filter(Boolean):[];
 const tiers=mode==='scheduled'?form.tiers.value.trim().split('\n').filter(line=>line.trim()).map(line=>{const parts=line.replace(/[٠-٩]/g,ch=>String(ch.charCodeAt(0)-1632)).replace(/[۰-۹]/g,ch=>String(ch.charCodeAt(0)-1776)).trim().split(/\s*[,،]\s*|\s+/);if(parts.length!==2||parts.some(part=>!Number.isFinite(Number(part))))throw Error(uiText('صيغة الشريحة: 4, 180'));return {from:Number(parts[0]),[unit]:Number(parts[1])};}):[];
 return {mode,capture:form.capture.value,window_minutes:Number(form.window_minutes.value||15),times,penalty:{enabled:!!form.enabled?.checked,minutes:Number(form.minutes.value||0),escalation:!!form.escalation?.checked,period:form.period.value,tier_unit:unit,tiers}};
}
function presenceEdit200(staff){
 const rule=staff.presence_rule||{mode:'off',capture:'location',window_minutes:15,times:[],penalty:{}};
 const penalty=rule.penalty||{},tiers=(penalty.tiers||[]).map(tier=>tier.from+', '+(penalty.tier_unit==='days'?tier.days:tier.minutes)).join('\n');
 modal('مواعيد إثبات التواجد','<form id="modalForm"><p class="tiny">'+esc(uiText('الحفظ لا يمر على تعديل الدوام الأساسي ولا ينشئ نسخة دوام جديدة.'))+'</p><label>'+esc(uiText('النوع'))+'<select name="mode"><option value="off">'+esc(uiText('غير مفعّل'))+'</option><option value="scheduled" '+(rule.mode==='scheduled'?'selected':'')+'>'+esc(uiText('بمواعيد'))+'</option><option value="court" '+(rule.mode==='court'?'selected':'')+'>'+esc(uiText('مندوب محكمة'))+'</option></select></label><label>'+esc(uiText('ما يُرسل'))+'<select name="capture"><option value="location">'+esc(uiText('موقع فقط'))+'</option><option value="photo" '+(rule.capture==='photo'?'selected':'')+'>'+esc(uiText('صورة وموقع'))+'</option></select></label><div data-presence-scheduled>'+input('window_minutes','المهلة بالدقائق',rule.window_minutes||15,'number','min="1" max="180" step="1"')+'<label>'+esc(uiText('المواعيد، كل موعد في سطر'))+'<textarea name="times" rows="4">'+(rule.times||[]).join('\n')+'</textarea></label>'+check('enabled','تطبيق الخصم على الراتب',!!penalty.enabled)+input('minutes','الجزاء بالدقائق',penalty.minutes??120,'number','min="0" max="1440" step="1"')+check('escalation','تصعيد الجزاء عند التكرار',!!penalty.escalation)+'<label>'+esc(uiText('فترة العد'))+'<select name="period"><option value="month">'+esc(uiText('شهرية'))+'</option><option value="year" '+(penalty.period==='year'?'selected':'')+'>'+esc(uiText('سنوية'))+'</option></select></label><label>'+esc(uiText('وحدة شرائح التصعيد'))+'<select name="tier_unit"><option value="minutes">'+esc(uiText('دقائق'))+'</option><option value="days" '+(penalty.tier_unit==='days'?'selected':'')+'>'+esc(uiText('أيام'))+'</option></select></label><label>'+esc(uiText('الشرائح: بداية العد، الخصم'))+'<textarea name="tiers" rows="4">'+esc(tiers)+'</textarea></label></div><p data-presence-court class="tiny">'+esc(uiText('بلا مواعيد وبلا خصم تلقائي. يُقبل الإرسال داخل محكمة محفوظة، ويمكن تكراره في اليوم. التقرير يعرض المحكمة والوقت.'))+'</p>'+saveButton()+'</form>',async form=>{await api('presence_rule_save',{user_id:Number(staff.id),rule:presenceRead200(form)});toast('حُفظت مواعيد إثبات التواجد دون تغيير جدول الدوام.');});
 const form=$('#modalForm'),sync=()=>{form.querySelector('[data-presence-scheduled]').hidden=form.mode.value!=='scheduled';form.querySelector('[data-presence-court]').hidden=form.mode.value!=='court';};form.mode.onchange=sync;sync();
}
async function presenceLog200(id){
 const today=dateNow(),data=await api('presence_log',null,{user_id:id,from:today.slice(0,8)+'01',to:today});
 const rows=(data.sends||[]).map(send=>[esc(localDisplay(send.sent_at)),esc(send.court_name||send.slot||uiText('موقع')),send.latitude==null?'—':'<a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+encodeURIComponent(send.latitude+','+send.longitude)+'">'+esc(uiText('الموقع'))+'</a>',send.photo_id?'<a target="_blank" rel="noopener" href="file.php?id='+send.photo_id+'">'+esc(uiText('الصورة'))+'</a>':'—']);
 modal('سجل إثبات التواجد',rows.length?table(['الوقت','المكان','الخريطة','الصورة'],rows):'<p>'+esc(uiText('لا يوجد إرسال في هذا الشهر.'))+'</p>');
}
function presenceDayDetails200(day){
 const sends=day.presence_sends||[],missed=Number(day.presence_missed||0),minutes=Number(day.presence_penalty_minutes||0),days=Number(day.presence_penalty_days||0);
 if(!sends.length&&!missed&&!minutes&&!days)return '';
 const rows=sends.map(send=>{const map=send.latitude==null?'':(' · <a target="_blank" rel="noopener" href="https://www.google.com/maps?q='+encodeURIComponent(send.latitude+','+send.longitude)+'">'+esc(uiText('الموقع'))+'</a>');const photo=send.photo_id?(' · <a target="_blank" rel="noopener" href="file.php?id='+send.photo_id+'">'+esc(uiText('الصورة'))+'</a>'):'';return '<p>'+esc(localDisplay(send.sent_at))+' · '+esc(send.court_name||send.slot||uiText('إثبات تواجد'))+map+photo+'</p>';}).join('');
 return '<section><h3>'+esc(uiText('إثبات التواجد'))+'</h3>'+(missed?'<p>'+esc(uiText('نسيان إثبات التواجد'))+': '+missed+' · '+minutes+' '+esc(uiText('دقيقة'))+(days?' · '+days+' '+esc(uiText('يوم')):'')+'</p>':'')+rows+'</section>';
}
function presenceAttendance200(){
 presenceStop200();presencePhoto200=null;const proof=boot.presence;if(!proof||proof.mode==='off')return;
 const photo=proof.capture==='photo'?'<div class="camera-box"><video id="presenceVideo" autoplay playsinline muted hidden></video></div><div class="camera-actions"><button type="button" id="presenceCamera200">'+esc(uiText('فتح الكاميرا'))+'</button><button type="button" id="presenceShot200" class="gold">'+esc(uiText('التقاط الصورة'))+'</button></div><p id="presenceCameraStatus200" class="tiny muted"></p>':'';
 let body='';
 if(proof.mode==='court')body='<p>'+esc(uiText('أرسل إثباتك من داخل المحكمة. يمكن الإرسال أكثر من مرة، ولا يغني عن بصمة الحضور أو الانصراف.'))+'</p><button type="button" class="primary submit-wide" data-presence-send="court">'+esc(uiText('إرسال إثبات المحكمة'))+'</button>';
 else body='<p>'+esc(uiText('أرسل إثبات التواجد في الموعد. هذا الإرسال لا يغني عن بصمة الحضور أو الانصراف.'))+'</p>'+(proof.slots||[]).map(slot=>{if(slot.status==='open')return '<button type="button" class="primary submit-wide" data-presence-send="'+esc(slot.key)+'">'+esc(uiText('إرسال إثبات التواجد'))+' · '+esc(slot.time)+' · '+esc(uiText('حتى'))+' '+esc(slot.closes)+'</button>';if(slot.status==='sent')return '<p>'+esc(slot.time)+' · '+esc(uiText('تم الإرسال'))+'</p>';if(slot.status==='missed')return '<p>'+esc(slot.time)+' · '+esc(uiText('انتهت المهلة'))+'</p>';return '<p class="tiny muted">'+esc(uiText('الموعد التالي'))+' '+esc(slot.time)+'</p>';}).join('');
 const card=document.createElement('section');card.className='card';card.innerHTML='<h2>'+esc(uiText('إثبات التواجد'))+'</h2>'+photo+body;$('#page').append(card);
 if(photo){$('#presenceCamera200').onclick=presenceOpen200;$('#presenceShot200').onclick=presenceShot200;}
}
async function presenceOpen200(){
 try{presenceStop200();const video=$('#presenceVideo');presenceStream200=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});video.srcObject=presenceStream200;video.hidden=false;await video.play();$('#presenceCameraStatus200').textContent=uiText('الكاميرا تعمل؛ التقط صورة.');}catch(error){toast(uiText('تعذر فتح الكاميرا: ')+error.message);}
}
async function presenceShot200(){
 const video=$('#presenceVideo');if(!presenceStream200||!video?.videoWidth)return toast('افتح الكاميرا وانتظر ظهور الصورة.');
 try{const ticket=await api('presence_ticket');const canvas=document.createElement('canvas'),scale=Math.min(1,1200/Math.max(video.videoWidth,video.videoHeight));canvas.width=video.videoWidth*scale;canvas.height=video.videoHeight*scale;canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.82));presencePhoto200={blob,ticket:ticket.ticket,photoAt:Math.floor(new Date(ticket.server_time.replace(' ','T')+'Z').getTime()/1000)};$('#presenceCameraStatus200').textContent=uiText('تم التقاط صورة حديثة.');presenceStop200();video.hidden=true;}catch(error){toast(error.message);}
}
async function presenceSubmit200(slot){
 const proof=boot.presence,loc=await freshLocation(),body=new FormData();body.set('request_id',crypto.randomUUID());Object.entries(loc).forEach(([key,value])=>body.set(key,String(value)));
 if(slot!=='court')body.set('slot',slot);
 if(proof.capture==='photo'){if(!presencePhoto200)throw Error(uiText('التقط صورة أولًا.'));body.set('photo',presencePhoto200.blob,'presence.jpg');body.set('capture_ticket',presencePhoto200.ticket);body.set('photo_at',String(presencePhoto200.photoAt));}
 const saved=await api('presence_send',body);presencePhoto200=null;toast((saved.duplicate?uiText('تم إرسال هذا الموعد مسبقًا.'):uiText('تم حفظ إثبات التواجد'))+' · '+localDisplay(saved.sent_at)+(saved.court_name?' · '+saved.court_name:''));await attendance();
}
document.addEventListener('click',async event=>{
 const edit=event.target.closest?.('[data-presence-edit]');if(edit){const staff=(typeof users!=='undefined'?users:[]).find(person=>Number(person.id)===Number(edit.dataset.presenceEdit));if(staff)presenceEdit200(staff);return;}
 const log=event.target.closest?.('[data-presence-log]');if(log){try{await presenceLog200(log.dataset.presenceLog);}catch(error){toast(error.message);}return;}
 const send=event.target.closest?.('[data-presence-send]');if(!send||send.disabled)return;send.disabled=true;try{await presenceSubmit200(send.dataset.presenceSend);}catch(error){toast(error.message);}finally{if(send.isConnected)send.disabled=false;}
});
