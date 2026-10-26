'use strict';
let presenceStream200=null,presencePhoto200=null;
function presenceStop200(){if(presenceStream200){presenceStream200.getTracks().forEach(track=>track.stop());presenceStream200=null;}}
function presenceSetup200(p){const s=p&&p.presence_setup?p.presence_setup:p||{};return {mode:s.mode||'off',capture:s.capture||'location',window_minutes:s.window_minutes||15,times:Array.isArray(s.times)?s.times.slice():[]};}
function presenceRecordValues200(row){try{return row?.value_json?JSON.parse(row.value_json):{};}catch(e){return {};}}
function presencePenaltyOf200(values,legacy){if(values?.punch_rules39?.presence)return values.punch_rules39.presence;if(legacy?.penalty)return legacy.penalty;return null;}
function presenceModalFrom200(row,management){
 if(management?.mode==='edit')return row.effective_from||'';
 if(management?.mode==='version'){
  const next=typeof nextPolicyDate32==='function'?nextPolicyDate32():'';
  if(row.effective_from&&next&&row.effective_from>=next&&typeof nextDayPolicy32==='function')return nextDayPolicy32(row.effective_from);
  return next||'';
 }
 return (typeof dateNow==='function'?dateNow():'')||row.effective_from||'';
}
function presenceSource200(staff){
 if(staff&&staff.service&&staff.service.attendance===false)return 'exempt';
 if(staff?.presence?.subject===false)return 'exempt';
 return staff?.presence?.source==='own'?'own':'default';
}
function presenceStaffExemptOn200(staff,from){
 if(staff?.presence?.subject!==false)return false;
 const today=typeof dateNow==='function'?dateNow():'';
 return !from||!today||from===today;
}
function presenceFormState200(row,staff,defaults,opt){
 const from=opt?.from||row?.effective_from||'';
 const employee=opt&&Object.prototype.hasOwnProperty.call(opt,'employee')?!!opt.employee:!!(Number(row?.user_id||0)||(staff&&(staff.id||staff.presence||staff.presence_legacy)));
 const record=presenceRecordValues200(row);
 if(!employee)return {source:'own',setup:presenceSetup200(record),penalty:presencePenaltyOf200(record,null),exempt:false,from};
 const form=(opt?.form&&(!opt.form.from||!from||opt.form.from===from))?opt.form:null;
 const exempt=!!(form&&(form.exempt||form.source==='exempt'||form.subject===false))||opt?.subject===false||!!presenceStaffExemptOn200(staff,from);
 const formSource=form?.source==='own'||form?.source==='default'?form.source:null;
 if(formSource==='own')return {source:'own',setup:presenceSetup200(form.setup?{presence_setup:form.setup}:record),penalty:form.penalty||presencePenaltyOf200(record,staff?.presence_legacy),exempt,from};
 if(formSource==='default')return {source:'default',setup:presenceSetup200(defaults||{}),penalty:null,exempt,from};
 if(record.presence_source==='own')return {source:'own',setup:presenceSetup200(record),penalty:presencePenaltyOf200(record,staff?.presence_legacy),exempt,from};
 if(record.presence_source==='default')return {source:'default',setup:presenceSetup200(defaults||{}),penalty:null,exempt,from};
 const legacy=staff?.presence_legacy;
 if(legacy&&(!legacy.date_from||!from||legacy.date_from<=from))return {source:'own',setup:presenceSetup200({presence_setup:legacy}),penalty:presencePenaltyOf200(record,legacy),exempt,from};
 return {source:'default',setup:presenceSetup200(defaults||{}),penalty:null,exempt,from};
}
function presenceApplyState200(p,state){
 if(!p||!state)return p;
 if(typeof punchRules39==='function')p.punch_rules39=punchRules39(p);
 if(state.source==='own'){
  if(state.setup)p.presence_setup=state.setup;
  if(state.penalty)p.punch_rules39={...p.punch_rules39,presence:state.penalty};
 }
 return p;
}
function presenceModalOpen200(p,row,staff,defaults,management,opt){
 const employee=!!(opt?.employee);
 const from=opt?.from||presenceModalFrom200(row,management);
 const state=presenceFormState200(row,staff,defaults,{form:management?.presence_form,from,employee,subject:opt?.subject});
 presenceApplyState200(p,state);
 return {from,state,fields:typeof presencePolicyFields200==='function'?presencePolicyFields200(p,{employee,exempt:!!state.exempt,source:state.source,setup:state.setup,defaults}):''};
}
function presenceSaveValues200(form,p){
 const v={...p};
 if(typeof punchPolicyRead39==='function')v.punch_rules39=punchPolicyRead39(form);
 const src=form.elements.namedItem('presence_source');
 if(src){
  v.presence_source=src.value;
  if(src.value==='own')v.presence_setup=presenceRead200(form);
  else{delete v.presence_setup;if(v.punch_rules39){const next={...v.punch_rules39};delete next.presence;v.punch_rules39=next;}}
 }else if(typeof presenceRead200==='function')v.presence_setup=presenceRead200(form);
 return v;
}
function presenceCapture200(proof,slot){
 if(slot==='court')return proof.court_capture||((proof.mode==='court'||proof.court)?(proof.capture||'location'):'location');
 return (proof.slots||[]).find(item=>item.key===slot)?.capture||proof.capture;
}
function presenceSaveGuard200(form){
 const state=form?.dataset?.presenceChecking200||'';
 if(state==='1')throw Error(uiText('جارٍ التحقق من تاريخ السريان.'));
 if(state==='error')throw Error(uiText('تعذر التحقق من تاريخ السريان. أعد المحاولة.'));
}
function presenceDraft200(form){
 const src=form.elements.namedItem('presence_source');
 const draft={source:src?src.value:null};
 if(form.elements.namedItem('presence_enabled'))try{draft.setup=presenceRead200(form);}catch(e){draft.setup=null;}
 const get=k=>form.elements.namedItem('p39_presence_'+k);
 if(get('minutes'))draft.penalty={enabled:!!get('enabled')?.checked,minutes:Number(get('minutes').value||0),escalation:!!get('escalation')?.checked,period:get('period')?.value||'month',mode:'replace',tier_unit:get('unit')?.value||'minutes',tiers:[]};
 return draft;
}
function presenceShowDateNote200(form,text,kind){
 let note=form.querySelector('[data-presence-date-note200]');
 if(!note){note=document.createElement('p');note.dataset.presenceDateNote200='';const group=form.querySelector('#presenceGroup200');(group?.querySelector('h3')||group||form).insertAdjacentElement('afterend',note);}
 note.hidden=!text;note.textContent=text||'';note.className=kind==='error'?'error tiny':(kind==='warn'?'tiny':'tiny muted');
}
function presenceSetChecking200(form,state){
 form.dataset.presenceChecking200=state||'';
 const btn=form.querySelector('[type="submit"]');if(btn)btn.disabled=state==='1'||state==='error';
 let retry=form.querySelector('[data-presence-retry200]');
 if(state==='error'){
  if(!retry){retry=document.createElement('button');retry.type='button';retry.dataset.presenceRetry200='';retry.textContent=uiText('إعادة التحقق من تاريخ السريان');form.querySelector('[data-presence-date-note200]')?.insertAdjacentElement('afterend',retry);}
  retry.hidden=false;
 }else if(retry)retry.hidden=true;
}
function presenceApplySubject200(form,preview){
 const exempt=!!(preview&&(preview.exempt||preview.source==='exempt'||preview.subject===false));
 const source=form.elements.namedItem('presence_source');
 if(source)source.disabled=exempt;
 presenceShowDateNote200(form,exempt?uiText('الموظف غير خاضع لبصمة الحضور في تاريخ السريان المختار.'):'','warn');
 if(typeof presenceFollowSync200==='function')presenceFollowSync200(form);
}
function presenceWatchFrom200(form,row,staff,defaults,management){
 const fromEl=form?.elements?.namedItem('effective_from');if(!fromEl)return;
 const employee=!!Number(form.elements.namedItem('user_id')?.value||row?.user_id||0);if(!employee)return;
 let seq=0;
 const run=async()=>{
  const from=fromEl.value;if(!from)return;
  const token=++seq;
  presenceSetChecking200(form,'1');
  presenceShowDateNote200(form,uiText('جارٍ التحقق من تاريخ السريان.'));
  let preview=null;
  try{
   if(typeof api!=='function')throw Error(uiText('تعذر التحقق من تاريخ السريان.'));
   preview=await api('presence_form_preview',{user_id:Number(form.elements.namedItem('user_id').value||row.user_id||0),from,values:presenceRecordValues200(row)});
   if(token!==seq||fromEl.value!==from)return;
  }catch(error){
   if(token!==seq||fromEl.value!==from)return;
   presenceSetChecking200(form,'error');
   presenceShowDateNote200(form,(error&&error.message)||uiText('تعذر التحقق من تاريخ السريان. أعد المحاولة.'),'error');
   return;
  }
  if(token!==seq||fromEl.value!==from)return;
  presenceApplySubject200(form,preview);
  presenceSetChecking200(form,'');
 };
 fromEl.addEventListener('change',()=>{run();});
 form.addEventListener('click',event=>{if(event.target.closest?.('[data-presence-retry200]'))run();});
}
function presenceTimeRow200(time,index){
 return '<div class="presence-time-row200">'+input('presence_time_'+index,uiText('موعد إثبات التواجد'),time,'time')+'<button type="button" data-presence-time-del>'+esc(uiText('حذف الموعد'))+'</button></div>';
}
function presencePolicyFields200(p,opt){
 const employee=!!opt.employee,exempt=!!opt.exempt,source=(opt.source==='own'||opt.source==='default')?opt.source:'default';
 const setup=opt.setup||presenceSetup200(source==='own'||!employee?p:(opt.defaults||p));
 const times=setup.times.length?setup.times:[''];
 const enabled=setup.mode!=='off',kind=setup.mode==='court'?'court':'scheduled';
 const penalty=typeof punchPresenceCard39==='function'?punchPresenceCard39(source==='own'||!employee?p:(opt.defaults||p)):'';
 return '<section id="presenceGroup200" data-presence-follow="'+(employee?source:'own')+'"><hr><h3>'+esc(uiText('بصمة التواجد'))+'</h3>'+
  (employee?'<label>'+esc(uiText('مصدر إعداد التواجد'))+'<select name="presence_source" '+(exempt?'disabled':'')+'>'+
   '<option value="default" '+(source==='default'?'selected':'')+'>'+esc(uiText('اتباع الإعداد الافتراضي'))+'</option>'+
   '<option value="own" '+(source==='own'?'selected':'')+'>'+esc(uiText('قاعدة خاصة بالموظف'))+'</option></select></label>'+
   '<p class="tiny muted" data-presence-source-note>'+esc(uiText(exempt?'غير خاضع لبصمة الحضور':(source==='own'?'قاعدة خاصة':'يتبع الافتراضي')))+'</p>':'')+
  '<div data-presence-fields>'+check('presence_enabled',uiText('تفعيل بصمة التواجد'),enabled)+
  '<label>'+esc(uiText('نوع الإثبات'))+'<select name="presence_kind"><option value="scheduled" '+(kind==='scheduled'?'selected':'')+'>'+esc(uiText('بمواعيد'))+'</option><option value="court" '+(kind==='court'?'selected':'')+'>'+esc(uiText('مندوب محكمة'))+'</option></select></label>'+
  '<div data-presence-scheduled><div id="presenceTimes200">'+times.map((time,i)=>presenceTimeRow200(time,i)).join('')+'</div><button type="button" id="presenceTimeAdd200">'+esc(uiText('إضافة موعد'))+'</button>'+
  input('presence_window',uiText('المهلة المسموحة للإرسال بالدقائق'),setup.window_minutes,'number','min="1" max="180" step="1"')+'</div>'+
  '<label>'+esc(uiText('طريقة الإثبات'))+'<select name="presence_capture"><option value="location" '+(setup.capture!=='photo'?'selected':'')+'>'+esc(uiText('موقع فقط'))+'</option><option value="photo" '+(setup.capture==='photo'?'selected':'')+'>'+esc(uiText('صورة وموقع'))+'</option></select></label>'+
  (setup.mode==='court'?'<p class="tiny muted">'+esc(uiText('بلا مواعيد وبلا خصم تلقائي. يُقبل الإرسال داخل محكمة محفوظة، ويمكن تكراره في اليوم.'))+'</p>':'')+
  penalty+'</div></section>';
}
function presenceRead200(form){
 const enabled=form.elements.namedItem('presence_enabled')?.checked;
 const kind=form.elements.namedItem('presence_kind')?.value||'scheduled';
 const times=[...form.querySelectorAll('[name^="presence_time_"]')].map(input=>input.value.trim()).filter(Boolean);
 const seen=new Set();times.forEach(time=>{if(seen.has(time))throw Error(uiText('لا تكرر نفس الموعد.'));seen.add(time);});
 return {mode:!enabled?'off':(kind==='court'?'court':'scheduled'),capture:form.elements.namedItem('presence_capture')?.value||'location',window_minutes:Number(form.elements.namedItem('presence_window')?.value||15),times};
}
function presenceFollowSync200(form){
 const group=form?.querySelector?form.querySelector('#presenceGroup200'):$('#presenceGroup200');if(!group||!form)return;
 const source=form.elements.namedItem('presence_source');
 const fields=group.querySelector('[data-presence-fields]');
 const scheduled=group.querySelector('[data-presence-scheduled]');
 const note=group.querySelector('[data-presence-source-note]');
 const enabled=form.elements.namedItem('presence_enabled');
 const kind=form.elements.namedItem('presence_kind');
 const follow=source?source.value==='default':false;
 const exempt=!!(source&&source.disabled);
 group.dataset.presenceFollow=source?(exempt?'exempt':source.value):'own';
 if(fields)fields.hidden=follow;
 const court=kind?.value==='court';
 const card=form.querySelector('[data-punch-rule39="presence"]');if(card)card.hidden=!!(follow||court||(enabled&&!enabled.checked));
 if(note&&source)note.textContent=uiText(exempt?'غير خاضع لبصمة الحضور':(source.value==='own'?'قاعدة خاصة':'يتبع الافتراضي'));
 if(scheduled)scheduled.hidden=!!(follow||court||(enabled&&!enabled.checked));
}
function presenceBind200(form){
 const group=$('#presenceGroup200');if(!group||!form)return;
 const source=form.elements.namedItem('presence_source');
 const enabled=form.elements.namedItem('presence_enabled');
 const kind=form.elements.namedItem('presence_kind');
 const sync=()=>presenceFollowSync200(form);
 source?.addEventListener('change',sync);enabled?.addEventListener('change',sync);kind?.addEventListener('change',sync);
 $('#presenceTimeAdd200')?.addEventListener('click',()=>{
  const host=$('#presenceTimes200');if(!host||host.querySelectorAll('.presence-time-row200').length>=8)return toast(uiText('حد المواعيد ثمانية.'));
  host.insertAdjacentHTML('beforeend',presenceTimeRow200('',host.querySelectorAll('.presence-time-row200').length));
 });
 group.addEventListener('click',event=>{
  const del=event.target.closest('[data-presence-time-del]');if(!del)return;
  const host=$('#presenceTimes200');if(!host)return;
  del.closest('.presence-time-row200')?.remove();
  if(!host.querySelector('.presence-time-row200'))host.insertAdjacentHTML('beforeend',presenceTimeRow200('',0));
 });
 sync();
}
function presenceGeneralSummary200(p){
 const setup=presenceSetup200(p);
 if(setup.mode==='court')return field32('بصمة التواجد',uiText('مندوب محكمة'))+field32('الإرسال',setup.capture==='photo'?uiText('صورة وموقع'):uiText('موقع فقط'));
 if(setup.mode==='scheduled')return field32('بصمة التواجد',uiText('مفعّلة بمواعيد'))+field32('المواعيد',(setup.times||[]).join(' · ')||'—')+field32('المهلة',(setup.window_minutes||0)+' '+uiText('دقيقة'))+field32('الإرسال',setup.capture==='photo'?uiText('صورة وموقع'):uiText('موقع فقط'));
 return field32('بصمة التواجد',uiText('غير مفعّلة'));
}
function presenceFields32(staff){
 const source=presenceSource200(staff);
 const label=source==='exempt'?uiText('غير خاضع لبصمة الحضور'):source==='own'?uiText('قاعدة خاصة'):uiText('يتبع الافتراضي');
 const rule=staff?.presence?.rule||{};
 const extra=source==='exempt'?'':(rule.mode==='scheduled'?field32('المواعيد',(rule.times||[]).join(' · ')||'—')+field32('المهلة',(rule.window_minutes||0)+' '+uiText('دقيقة'))+field32('الإرسال',rule.capture==='photo'?uiText('صورة وموقع'):uiText('موقع فقط')):(rule.mode==='court'?field32('النوع',uiText('مندوب محكمة'))+field32('الإرسال',rule.capture==='photo'?uiText('صورة وموقع'):uiText('موقع فقط')):field32('الحالة',uiText('غير مفعّل'))));
 return field32('إعداد التواجد',label)+extra;
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
function presenceSlotMarkup200(slot){
 if(slot.status==='open')return '<button type="button" class="primary submit-wide" data-presence-send="'+esc(slot.key)+'">'+esc(uiText('إرسال إثبات التواجد'))+' · '+esc(slot.time)+' · '+esc(uiText('حتى'))+' '+esc(slot.closes)+'</button>';
 if(slot.status==='sent')return '<p>'+esc(slot.time)+' · '+esc(uiText('تم الإرسال'))+'</p>';
 if(slot.status==='missed')return '<p>'+esc(slot.time)+' · '+esc(uiText('انتهت المهلة'))+'</p>';
 if(slot.status==='waiting')return '<p class="tiny muted">'+esc(uiText('الموعد التالي'))+' '+esc(slot.time)+'</p>';
 return '';
}
function presenceAttendance200(){
 presenceStop200();presencePhoto200=null;const proof=boot.presence;const card=document.querySelector('.attendance-card');
 if(!proof||proof.mode==='off'||!card)return;
 let body='';
 if(proof.mode==='court'||proof.court)body+='<p>'+esc(uiText('أرسل إثباتك من داخل المحكمة. يمكن الإرسال أكثر من مرة، ولا يغني عن بصمة الحضور أو الانصراف.'))+'</p><button type="button" class="primary submit-wide" data-presence-send="court">'+esc(uiText('إرسال إثبات المحكمة'))+'</button>';
 if((proof.slots||[]).length)body+='<p>'+esc(uiText('أرسل إثبات التواجد في الموعد. هذا الإرسال لا يغني عن بصمة الحضور أو الانصراف.'))+'</p>'+proof.slots.map(presenceSlotMarkup200).join('');
 if(!body)return;
 const box=document.createElement('div');box.className='presence-panel200';box.innerHTML='<h3>'+esc(uiText('إثبات التواجد'))+'</h3>'+body;card.append(box);
}
async function presenceSubmit200(slot){
 const proof=boot.presence,loc=await freshLocation(),body=new FormData();body.set('request_id',crypto.randomUUID());Object.entries(loc).forEach(([key,value])=>body.set(key,String(value)));
 if(slot!=='court')body.set('slot',slot);
 const capture=typeof presenceCapture200==='function'?presenceCapture200(proof,slot):(slot==='court'?(proof.court_capture||proof.capture):((proof.slots||[]).find(item=>item.key===slot)?.capture||proof.capture));
 if(capture==='photo'){
  if(photo&&!presencePhoto200){const ticket=await api('presence_ticket');presencePhoto200={blob:photo,ticket:ticket.ticket,photoAt};}
  if(!presencePhoto200)throw Error(uiText('التقط صورة أولًا.'));
  body.set('photo',presencePhoto200.blob,'presence.jpg');body.set('capture_ticket',presencePhoto200.ticket);body.set('photo_at',String(presencePhoto200.photoAt));
 }
 const saved=await api('presence_send',body);presencePhoto200=null;toast((saved.duplicate?uiText('تم إرسال هذا الموعد مسبقًا.'):uiText('تم حفظ إثبات التواجد'))+' · '+localDisplay(saved.sent_at)+(saved.court_name?' · '+saved.court_name:''));await attendance();
}
document.addEventListener('click',async event=>{
 const log=event.target.closest?.('[data-presence-log]');if(log){try{await presenceLog200(log.dataset.presenceLog);}catch(error){toast(error.message);}return;}
 const send=event.target.closest?.('[data-presence-send]');if(!send||send.disabled)return;send.disabled=true;try{await presenceSubmit200(send.dataset.presenceSend);}catch(error){toast(error.message);}finally{if(send.isConnected)send.disabled=false;}
});
