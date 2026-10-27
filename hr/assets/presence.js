'use strict';
let presenceStream200=null,presencePhoto200=null;
function presenceStop200(){if(presenceStream200){presenceStream200.getTracks().forEach(track=>track.stop());presenceStream200=null;}}
function presenceSetup200(p){const s=p&&p.presence_setup?p.presence_setup:p||{};const mode=s.mode||'off';const kind=s.kind==='court'||mode==='court'?'court':'scheduled';return {mode,kind,capture:s.capture||'location',window_minutes:s.window_minutes||15,times:Array.isArray(s.times)?s.times.slice():[]};}
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
 if(typeof punchValidate39==='function')punchValidate39(form);
 if(typeof punchPolicyRead39==='function')v.punch_rules39=punchPolicyRead39(form);
 const src=form.elements.namedItem('presence_source');
 if(src){
  v.presence_source=src.value;
  if(src.value==='own')v.presence_setup=presenceRead200(form);
  else{delete v.presence_setup;delete p.presence_setup;if(v.punch_rules39){const next={...v.punch_rules39};delete next.presence;v.punch_rules39=next;}if(p.punch_rules39&&Object.prototype.hasOwnProperty.call(p.punch_rules39,'presence')){p.punch_rules39={...p.punch_rules39};delete p.punch_rules39.presence;}}
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
 if(!note){note=document.createElement('p');note.dataset.presenceDateNote200='';const group=form.querySelector('#presenceGroup200');(group||form).insertAdjacentElement('afterend',note);}
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
 return '<div class="presence-time-row200"><label>'+esc(uiText('موعد إثبات التواجد'))+'<span class="presence-time-line200"><input name="presence_time_'+index+'" type="time" value="'+esc(time)+'"><button type="button" data-presence-time-del>'+esc(uiText('حذف الموعد'))+'</button></span></label></div>';
}
function presenceRulesAt200(values,from){
 const dated={...(values||{})};
 if(dated.punch_rules_from393&&from&&from<dated.punch_rules_from393){
  if(dated.punch_rules_before393)dated.punch_rules39=dated.punch_rules_before393;
  else delete dated.punch_rules39;
 }
 return dated;
}
function presenceInherited200(from){
 const rows=(typeof policyRows32!=='undefined'&&Array.isArray(policyRows32))?policyRows32:[];
 const general=rows.filter(row=>!Number(row?.user_id||0)&&(typeof scheduleMeta38!=='function'||scheduleMeta38(row).kind!=='temporary'));
 const row=typeof activePolicy32==='function'?activePolicy32(general,from||''):null;
 const values=presenceRulesAt200(row&&typeof policyValues32==='function'?policyValues32(row):{},from||'');
 const setup=presenceSetup200(values);
 const penalty=values?.punch_rules39?.presence&&typeof values.punch_rules39.presence==='object'?values.punch_rules39.presence:{enabled:false,minutes:0,escalation:false};
 return {setup,penalty};
}
function presencePenaltyLine200(penalty){
 if(!penalty||!penalty.enabled)return uiText('جزاء غير مفعّل');
 let text=uiText('جزاء مفعّل')+' · '+String(penalty.minutes??'')+' '+uiText('دقيقة');
 if(penalty.escalation)text+=' · '+uiText('تصعيد');
 return text;
}
function presenceStoppedPenalty200(penaltyLine,penalty){
 if(penalty&&penalty.enabled)return penaltyLine+' · '+uiText('الخصم غير مطبّق أثناء توقف البصمة');
 return uiText('جزاء غير مفعّل');
}
function presenceModeLine200(mode,penalty){
 if(mode==='court')return uiText('البصمة مفعّلة')+' · '+uiText('مندوب محكمة')+' · '+uiText('بلا خصم تلقائي');
 if(mode==='scheduled')return uiText('البصمة مفعّلة')+' · '+presencePenaltyLine200(penalty);
 return uiText('البصمة غير مفعّلة')+' · '+presenceStoppedPenalty200(presencePenaltyLine200(penalty),penalty);
}
function presenceSummaryText200(form){
 const source=form.elements.namedItem('presence_source'),exempt=!!(source&&source.disabled);
 if(source&&source.value==='default'){
  const inherited=presenceInherited200(form.elements.namedItem('effective_from')?.value||'');
  let text=uiText('يتبع الافتراضي')+' · '+presenceModeLine200(inherited.setup?.mode,inherited.penalty);
  if(exempt)text+=' · '+uiText('غير خاضع');
  return text;
 }
 const enabled=!!form.elements.namedItem('presence_enabled')?.checked;
 const kind=form.elements.namedItem('presence_kind')?.value||'scheduled';
 const penaltyOn=!!form.elements.namedItem('p39_presence_enabled')?.checked;
 const penaltyText=typeof punchSummaryText39==='function'?punchSummaryText39('presence',form):presencePenaltyLine200(null);
 let text=kind==='court'?uiText(enabled?'البصمة مفعّلة':'البصمة غير مفعّلة')+' · '+uiText('مندوب محكمة')+' · '+uiText('بلا خصم تلقائي'):uiText(enabled?'البصمة مفعّلة':'البصمة غير مفعّلة')+' · '+(enabled?penaltyText:presenceStoppedPenalty200(penaltyText,{enabled:penaltyOn}));
 if(exempt)text+=' · '+uiText('غير خاضع');
 return text;
}
function presencePolicyFields200(p,opt){
 const employee=!!opt.employee,exempt=!!opt.exempt,source=(opt.source==='own'||opt.source==='default')?opt.source:'default';
 const setup=opt.setup||presenceSetup200(source==='own'||!employee?p:(opt.defaults||p));
 const times=setup.times.length?setup.times:[''];
 const enabled=setup.mode!=='off',kind=setup.kind==='court'||setup.mode==='court'?'court':'scheduled',follow=employee&&source==='default';
 const rule=(typeof punchRules39==='function'?punchRules39(source==='own'||!employee?p:(opt.defaults||p)):{}).presence||(typeof punchPresenceBlank39==='function'?punchPresenceBlank39():{enabled:false,minutes:0,escalation:false,period:'month',mode:'replace',tier_unit:'minutes',tiers:[]});
 const penalty=typeof punchPenaltyFields39==='function'?punchPenaltyFields39('presence',rule):'';
 return '<details class="card" id="presenceGroup200" data-punch-rule39="presence" data-presence-follow="'+(employee?source:'own')+'"><summary><span class="punch-card-name39">'+esc(uiText('بصمة التواجد'))+'</span><span class="punch-card-state39" data-punch-summary39></span></summary><div class="punch-card-body39">'+
  '<div data-presence-enable'+(follow?' hidden':'')+'>'+check('presence_enabled',uiText('تفعيل بصمة التواجد'),enabled)+'</div>'+
  (employee?'<label>'+esc(uiText('مصدر إعداد التواجد'))+'<select name="presence_source" '+(exempt?'disabled':'')+'>'+
   '<option value="default" '+(source==='default'?'selected':'')+'>'+esc(uiText('اتباع الإعداد الافتراضي'))+'</option>'+
   '<option value="own" '+(source==='own'?'selected':'')+'>'+esc(uiText('قاعدة خاصة بالموظف'))+'</option></select></label>'+
   '<p class="tiny muted" data-presence-source-note>'+esc(uiText(exempt?'غير خاضع لبصمة الحضور':(source==='own'?'قاعدة خاصة':'يتبع الافتراضي')))+'</p>':'')+
  '<div data-presence-fields'+(follow?' hidden':'')+'>'+
  '<label>'+esc(uiText('نوع الإثبات'))+'<select name="presence_kind"><option value="scheduled" '+(kind==='scheduled'?'selected':'')+'>'+esc(uiText('بمواعيد'))+'</option><option value="court" '+(kind==='court'?'selected':'')+'>'+esc(uiText('مندوب محكمة'))+'</option></select></label>'+
  '<label>'+esc(uiText('طريقة الإثبات'))+'<select name="presence_capture"><option value="location" '+(setup.capture!=='photo'?'selected':'')+'>'+esc(uiText('موقع فقط'))+'</option><option value="photo" '+(setup.capture==='photo'?'selected':'')+'>'+esc(uiText('صورة وموقع'))+'</option></select></label>'+
  '<div data-presence-scheduled'+((follow||kind==='court'||!enabled)?' hidden':'')+'><div id="presenceTimes200">'+times.map((time,i)=>presenceTimeRow200(time,i)).join('')+'</div><button type="button" id="presenceTimeAdd200">'+esc(uiText('إضافة موعد'))+'</button>'+
  input('presence_window',uiText('المهلة المسموحة للإرسال بالدقائق'),setup.window_minutes,'number','min="1" max="180" step="1"')+'</div>'+
  '<p class="tiny muted" data-presence-court'+(kind==='court'&&!follow?'':' hidden')+'>'+esc(uiText('بلا مواعيد وبلا خصم تلقائي. يُقبل الإرسال داخل محكمة محفوظة، ويمكن تكراره في اليوم.'))+'</p>'+
  '<div data-presence-penalty'+((follow||kind==='court')?' hidden':'')+'>'+check('p39_presence_enabled','تطبيق الجزاء',!!rule.enabled)+penalty+'</div></div></div></details>';
}
function presenceRead200(form){
 const enabled=form.elements.namedItem('presence_enabled')?.checked;
 const kind=form.elements.namedItem('presence_kind')?.value||'scheduled';
 const inputs=[...form.querySelectorAll('[name^="presence_time_"]')];
 const seen=new Set();
 for(const input of inputs){
  const time=input.value.trim();if(!time)continue;
  if(seen.has(time)){
   const message=typeof punchNamedError39==='function'?punchNamedError39('presence','موعد إثبات التواجد','لا تكرر نفس الموعد.'):uiText('لا تكرر نفس الموعد.');
   if(typeof punchReportField39==='function')throw punchReportField39(form,input,message);
   throw Error(message);
  }
  seen.add(time);
 }
 if(enabled&&kind==='scheduled'&&!seen.size){
  const field=inputs.find(input=>!input.value.trim())||inputs[0]||form.querySelector('#presenceTimeAdd200');
  const message=typeof punchNamedError39==='function'?punchNamedError39('presence','موعد إثبات التواجد','حدد موعدًا واحدًا على الأقل وبحد 8 مواعيد.'):uiText('حدد موعدًا واحدًا على الأقل وبحد 8 مواعيد.');
  if(typeof punchReportField39==='function')throw punchReportField39(form,field,message);
  throw Error(message);
 }
 const windowField=form.elements.namedItem('presence_window');
 const windowMinutes=Number(windowField?.value||15);
 if(windowField&&String(windowField.value??'').trim()!==''&&(!Number.isFinite(windowMinutes)||windowMinutes<1||windowMinutes>180)){
  const message=typeof punchProblemMessage39==='function'?punchProblemMessage39(windowField):uiText('أدخل قيمة من 1 إلى 180.');
  if(typeof punchReportField39==='function')throw punchReportField39(form,windowField,message);
  throw Error(message);
 }
 const chosen=kind==='court'?'court':'scheduled';
 return {mode:!enabled?'off':chosen,kind:chosen,capture:form.elements.namedItem('presence_capture')?.value||'location',window_minutes:Number.isFinite(windowMinutes)?windowMinutes:15,times:[...seen]};
}
function presenceFollowSync200(form,event){
 const group=form?.querySelector?form.querySelector('#presenceGroup200'):$('#presenceGroup200');if(!group||!form)return;
 if(typeof punchReleaseHolds39==='function')punchReleaseHolds39(form,event);
 const held=node=>typeof punchHeld39==='function'&&punchHeld39(node);
 const source=form.elements.namedItem('presence_source');
 const fields=group.querySelector('[data-presence-fields]');
 const scheduled=group.querySelector('[data-presence-scheduled]');
 const note=group.querySelector('[data-presence-source-note]');
 const enabled=form.elements.namedItem('presence_enabled');
 const kind=form.elements.namedItem('presence_kind');
 const follow=source?source.value==='default':false;
 const exempt=!!(source&&source.disabled);
 const lead=group.querySelector('[data-presence-enable]');
 const courtNote=group.querySelector('[data-presence-court]');
 group.dataset.presenceFollow=source?(exempt?'exempt':source.value):'own';
 if(lead)lead.hidden=follow&&!held(lead);
 if(fields)fields.hidden=follow&&!held(fields);
 const court=kind?.value==='court';
 const punchOff=!!(enabled&&!enabled.checked);
 if(scheduled)scheduled.hidden=!!(follow||court||punchOff)&&!held(scheduled);
 if(courtNote)courtNote.hidden=!!(follow||!court);
 const penalty=group.querySelector('[data-presence-penalty]');
 if(penalty)penalty.hidden=!!(follow||court)&&!held(penalty);
 const penaltyOn=!!form.elements.namedItem('p39_presence_enabled')?.checked;
 const box=group.querySelector('[data-punch-penalty39]');
 if(box)box.hidden=!!(follow||court||!penaltyOn)&&!held(box);
 const escOn=!!form.elements.namedItem('p39_presence_escalation')?.checked;
 const escBox=group.querySelector('[data-punch-escalation39]');
 if(escBox)escBox.hidden=!!(follow||court||!penaltyOn||!escOn)&&!held(escBox);
 if(note&&source)note.textContent=uiText(exempt?'غير خاضع لبصمة الحضور':(source.value==='own'?'قاعدة خاصة':'يتبع الافتراضي'));
 if(typeof punchSyncCards39==='function')punchSyncCards39(form,event);
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
function presenceCreateAllowed200(){if(typeof personalPermission34!=='function')return true;if(typeof boot==='undefined'||boot.user?.role==='admin'||boot.access_mode!=='personal')return false;return !!personalPermission34('attendance','create');}
function presenceCanSend200(slot){if(!presenceCreateAllowed200())return typeof canRegisterAttendance34!=='function';if(typeof canRegisterAttendance34!=='function'||canRegisterAttendance34())return true;return !!(slot&&slot.status==='open');}
function presenceSlotMarkup200(slot){
 if(slot.status==='open'&&presenceCanSend200(slot))return '<button type="button" class="primary submit-wide" data-presence-send="'+esc(slot.key)+'">'+esc(uiText('إرسال إثبات التواجد'))+' · '+esc(slot.time)+' · '+esc(uiText('حتى'))+' '+esc(slot.closes)+'</button>';
 if(slot.status==='open')return '<p>'+esc(slot.time)+' · '+esc(uiText('حتى'))+' '+esc(slot.closes)+'</p>';
 if(slot.status==='sent')return '<p>'+esc(slot.time)+' · '+esc(uiText('تم الإرسال'))+'</p>';
 if(slot.status==='missed')return '<p>'+esc(slot.time)+' · '+esc(uiText('انتهت المهلة'))+'</p>';
 if(slot.status==='waiting')return '<p class="tiny muted">'+esc(uiText('الموعد التالي'))+' '+esc(slot.time)+'</p>';
 return '';
}
function presencePanelBody200(proof){
 if(!proof||proof.mode==='off')return '';
 let body='';
 if(proof.mode==='court'||proof.court)body+='<p>'+esc(uiText('أرسل إثباتك من داخل المحكمة. يمكن الإرسال أكثر من مرة، ولا يغني عن بصمة الحضور أو الانصراف.'))+'</p>'+(presenceCanSend200()?'<button type="button" class="primary submit-wide" data-presence-send="court">'+esc(uiText('إرسال إثبات المحكمة'))+'</button>':'');
 if((proof.slots||[]).length)body+='<p>'+esc(uiText('أرسل إثبات التواجد في الموعد. هذا الإرسال لا يغني عن بصمة الحضور أو الانصراف.'))+'</p>'+proof.slots.map(presenceSlotMarkup200).join('');
 return body?'<h3>'+esc(uiText('إثبات التواجد'))+'</h3>'+body:'';
}
function presencePaint200(proof){
 const card=document.querySelector('.attendance-card');if(!card)return;
 if(card.querySelector('[data-presence-send][disabled]'))return;
 const body=presencePanelBody200(proof);
 let box=card.querySelector('.presence-panel200');
 if(!body){box?.remove();return;}
 if(!box){box=document.createElement('div');box.className='presence-panel200';card.append(box);}
 if(box.dataset.presenceBody200===body)return;
 box.dataset.presenceBody200=body;box.innerHTML=body;
}
function presenceAttendance200(){
 presenceStop200();presencePhoto200=null;presencePaint200(typeof boot!=='undefined'?boot.presence:null);if(typeof presenceArm200==='function')presenceArm200();
}
function presenceStamp200(date,time){
 const zone=(typeof boot!=='undefined'&&boot?.company?.timezone)||'Asia/Kuwait';
 const [y,m,d]=String(date||'').split('-').map(Number);const parts=String(time||'00:00').split(':').map(Number);const hh=parts[0]||0,mm=parts[1]||0,ss=parts[2]||0;
 if(!y||!m||!d)return NaN;
 let guess=Date.UTC(y,m-1,d,hh,mm,ss);
 const fmt=new Intl.DateTimeFormat('en-US',{timeZone:zone,hourCycle:'h23',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit'});
 for(let i=0;i<4;i++){const shown={};for(const part of fmt.formatToParts(new Date(guess)))if(part.type!=='literal')shown[part.type]=Number(part.value);if(shown.hour===24)shown.hour=0;const asUTC=Date.UTC(shown.year,shown.month-1,shown.day,shown.hour,shown.minute,shown.second);const target=Date.UTC(y,m-1,d,hh,mm,ss);if(asUTC===target)break;guess+=target-asUTC;}
 return guess;
}
function presenceInstant200(stamp){const [date,time]=String(stamp||'').split(' ');return date&&time?presenceStamp200(date,time):NaN;}
function presenceProject200(proof,nowMs){
 if(!proof)return proof;
 const slots=(proof.slots||[]).map(slot=>{if(slot.status==='sent')return slot;const open=slot.opens_at?presenceInstant200(slot.opens_at):presenceStamp200(slot.date,slot.opens||slot.time);let close=slot.closes_at?presenceInstant200(slot.closes_at):presenceStamp200(slot.date,slot.closes||slot.opens||slot.time);if(!slot.closes_at&&Number.isFinite(open)&&Number.isFinite(close)&&close<=open)close+=86400000;let status=slot.status;if(!Number.isFinite(open)||!Number.isFinite(close))return slot;if(nowMs<open)status='waiting';else if(nowMs<=close)status='open';else status='missed';return {...slot,status};});
 return {...proof,slots};
}
function presenceNow200(){return Date.now()+(typeof serverOffset==='number'?serverOffset:0);}
function presenceNextWait200(proof,nowMs){
 let wait=60000;for(const slot of proof?.slots||[]){if(slot.status==='sent'||slot.status==='missed')continue;const open=slot.opens_at?presenceInstant200(slot.opens_at):presenceStamp200(slot.date,slot.opens||slot.time);let close=slot.closes_at?presenceInstant200(slot.closes_at):presenceStamp200(slot.date,slot.closes||slot.opens||slot.time);if(!slot.closes_at&&close<=open)close+=86400000;for(const at of [open,close+1000]){const delta=at-nowMs;if(delta>500&&delta<wait)wait=delta;}}
 return Math.min(60000,Math.max(1000,wait));
}
function presenceSchedule200(){clearTimeout(presenceSchedule200.timer);if(typeof boot==='undefined'||!boot?.user||boot.access_mode!=='personal')return;const now=presenceNow200();presenceSchedule200.timer=setTimeout(()=>{try{if(boot?.presence)presencePaint200(presenceProject200(boot.presence,presenceNow200()));}catch(e){}presenceSync200(true);},presenceNextWait200(boot.presence,now));}
function presenceOpenSlot200(){return (boot?.presence?.slots||[]).some(slot=>slot.status==='open');}
function presenceScreenAllowed200(){if(typeof boot==='undefined'||boot.access_mode!=='personal'||boot.user?.role==='admin')return false;if(typeof personalPermission34==='function'&&!personalPermission34('attendance','view')&&!personalPermission34('attendance','create'))return false;return presenceOpenSlot200();}
function presencePhotoContext200(){if(typeof presenceCreateAllowed200==='function'&&!presenceCreateAllowed200())return null;const proof=boot?.presence;if(!proof)return null;const open=(proof.slots||[]).find(slot=>slot.status==='open'&&(slot.capture||proof.capture)==='photo');if(open)return {slot:open.key};if((proof.mode==='court'||proof.court)&&(proof.court_capture||proof.capture)==='photo'&&(typeof presenceCanSend200!=='function'||presenceCanSend200()))return {slot:'court'};return null;}
function presenceApplyTools200(){const allow=(typeof canRegisterAttendance34==='function'&&canRegisterAttendance34())||!!presencePhotoContext200();for(const id of ['frontCamera','backCamera','takePhoto']){const node=document.getElementById(id);if(node)node.disabled=!allow;}}
function presenceApplyInfo200(fresh){
 if(!fresh||typeof boot==='undefined')return;
 if(fresh.server_time)serverOffset=new Date(String(fresh.server_time).replace(' ','T')+'Z').getTime()-Date.now();
 if(Object.prototype.hasOwnProperty.call(fresh,'attendance_available35'))boot.attendance_available35=fresh.attendance_available35;
 if(Object.prototype.hasOwnProperty.call(fresh,'personal_permissions34'))boot.personal_permissions34=fresh.personal_permissions34;
 if(Object.prototype.hasOwnProperty.call(fresh,'service_attendance')||Object.prototype.hasOwnProperty.call(fresh,'staff_type')){const service={...(boot.user?.service||{})};if(Object.prototype.hasOwnProperty.call(fresh,'service_attendance'))service.attendance=fresh.service_attendance;boot.user={...boot.user,staff_type:fresh.staff_type??boot.user?.staff_type,service};}
 if(Object.prototype.hasOwnProperty.call(fresh,'presence'))boot.presence=fresh.presence||null;
}
async function presenceSync200(){
 if(typeof boot==='undefined'||!boot?.user||boot.access_mode!=='personal')return;
 if(typeof personalPermission34==='function'&&!personalPermission34('attendance','view')&&!personalPermission34('attendance','create'))return;
 if(presenceSync200.busy)return;presenceSync200.busy=true;
 try{const fresh=typeof api==='function'?await api('punch127_info',null,{_background197:1}):null;presenceApplyInfo200(fresh);presencePaint200(boot.presence);presenceApplyTools200();if(typeof view!=='undefined'&&view==='notifications'&&typeof notificationCenter32==='function'&&document.getElementById('noticeResults32'))await notificationCenter32({quiet195:true});}catch(e){}
 finally{presenceSync200.busy=false;presenceSchedule200();}
}
function presenceArm200(){
 if(!presenceArm200.listening){presenceArm200.listening=true;document.addEventListener('visibilitychange',()=>{if(!document.hidden)presenceSync200();});window.addEventListener('pageshow',event=>{if(event.persisted)presenceSync200();});}
 if(typeof view!=='undefined'&&view==='attendance')presenceSchedule200();else presenceSync200();
}
function presenceAttemptMap200(){try{const value=JSON.parse(sessionStorage.getItem('hr-presence-attempt200')||'{}');return value&&typeof value==='object'?value:{};}catch(e){return {};}}
function presenceAttemptKey200(slot){return String(Number(boot?.user?.id)||0)+':'+String(slot);}
function presenceAttemptStored200(slot){const id=presenceAttemptMap200()[presenceAttemptKey200(slot)]||'';return /^[a-f0-9-]{36}$/i.test(id)?id:'';}
function presenceAttemptId200(slot){const all=presenceAttemptMap200(),key=presenceAttemptKey200(slot);if(/^[a-f0-9-]{36}$/i.test(all[key]||''))return all[key];const id=globalThis.crypto?.randomUUID?crypto.randomUUID():'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{const n=Math.random()*16|0;return (c==='x'?n:(n&0x3|0x8)).toString(16);});all[key]=id;try{sessionStorage.setItem('hr-presence-attempt200',JSON.stringify(all));}catch(e){}return id;}
function presenceAttemptDone200(slot){const all=presenceAttemptMap200();delete all[presenceAttemptKey200(slot)];try{sessionStorage.setItem('hr-presence-attempt200',JSON.stringify(all));}catch(e){}}
function presenceOp200(slot){return {userId:Number(boot?.user?.id)||0,access:String(boot?.access_mode||''),context:String(boot?.identity32?.context||''),slot:String(slot),view:typeof view==='undefined'?'':view,requestId:''};}
function presenceSameActor200(op){return !!op&&Number(boot?.user?.id)===Number(op.userId)&&String(boot?.access_mode||'')===op.access&&String(boot?.identity32?.context||'')===op.context;}
function presenceSamePlace200(op){return presenceSameActor200(op)&&(typeof view==='undefined'||view===op.view);}
function presenceAttemptRelease200(op){if(!op||!op.requestId)return;const all=presenceAttemptMap200(),key=String(Number(op.userId)||0)+':'+String(op.slot);if(all[key]===op.requestId)delete all[key];try{sessionStorage.setItem('hr-presence-attempt200',JSON.stringify(all));}catch(e){}}
async function presenceFinishSaved200(slot,saved,op){if(!op)op=presenceOp200(slot);if(presenceSameActor200(op))toast((saved.duplicate?uiText('تم إرسال هذا الموعد مسبقًا.'):uiText('تم حفظ إثبات التواجد'))+' · '+localDisplay(saved.sent_at)+(saved.court_name?' · '+saved.court_name:''));if(!presenceSamePlace200(op)){presenceAttemptRelease200(op);return;}try{await attendance();if(!presenceSamePlace200(op)){presenceAttemptRelease200(op);return;}presenceAttemptRelease200(op);presencePhoto200=null;}catch(error){if(presenceSameActor200(op))toast(uiText('تم حفظ إثبات التواجد، وتعذر تحديث الشاشة.'));}}
function presenceApi200(action,data){return api(action,data,{_presenceOwn200:1});}
async function presenceSubmit200(slot,op){
 if(typeof canRegisterAttendance34==='function'&&!canRegisterAttendance34()){const open=slot!=='court'&&(boot.presence?.slots||[]).some(item=>item.key===slot&&item.status==='open');if(!open||!presenceCreateAllowed200())throw Error(uiText('هذه العملية غير متاحة في ملفك الشخصي.'));}
 if(!op)op=presenceOp200(slot);
 if(!op.requestId)op.requestId=presenceAttemptStored200(slot);
 return presenceSubmitBody200(slot,op);
}
async function presenceSubmitBody200(slot,op){
 const stored=op.requestId;
 if(stored){
  const lookup=new FormData();lookup.set('request_id',stored);lookup.set('presence_lookup200','1');if(slot!=='court')lookup.set('slot',slot);
  let saved=null;
  try{saved=await presenceApi200('presence_send',lookup);}
  catch(error){if(!presenceSameActor200(op))return;if(error&&(error.transport32||error.responseInvalid32))throw error;if(!String(error?.message||'').includes('لا يوجد إرسال محفوظ'))throw error;toast(error.message);}
  if(saved){await presenceFinishSaved200(slot,saved,op);return saved;}
  if(!presenceSameActor200(op))return;
 }
 if(!op.requestId)op.requestId=presenceAttemptId200(slot);
 if(!presenceSameActor200(op))return;
 let loc;try{loc=await freshLocation();}catch(error){if(!presenceSameActor200(op))return;throw error;}
 if(!presenceSameActor200(op))return;
 const proof=boot.presence,body=new FormData();body.set('request_id',op.requestId);Object.entries(loc).forEach(([key,value])=>body.set(key,String(value)));
 if(slot!=='court')body.set('slot',slot);
 const capture=typeof presenceCapture200==='function'?presenceCapture200(proof,slot):(slot==='court'?(proof.court_capture||proof.capture):((proof.slots||[]).find(item=>item.key===slot)?.capture||proof.capture));
 if(capture==='photo'){
  if(photo&&(!presencePhoto200||presencePhoto200.blob!==photo)){if(!presenceSameActor200(op))return;let ticket;try{ticket=await presenceApi200('presence_ticket',slot==='court'?{}:{slot});}catch(error){if(!presenceSameActor200(op))return;throw error;}if(!presenceSameActor200(op))return;presencePhoto200={blob:photo,ticket:ticket.ticket,photoAt,slot};}
  if(!presencePhoto200){if(!presenceSameActor200(op))return;throw Error(uiText('التقط صورة أولًا.'));}
  body.set('photo',presencePhoto200.blob,'presence.jpg');body.set('capture_ticket',presencePhoto200.ticket);body.set('photo_at',String(presencePhoto200.photoAt));
 }
 if(!presenceSameActor200(op))return;
 let saved;try{saved=await presenceApi200('presence_send',body);}catch(error){const transport=error&&(error.transport32||error.responseInvalid32);if(!presenceSameActor200(op)){if(!transport)presenceAttemptRelease200(op);return;}if(!transport)presenceAttemptRelease200(op);throw error;}
 await presenceFinishSaved200(slot,saved,op);return saved;
}
document.addEventListener('click',async event=>{
 const log=event.target.closest?.('[data-presence-log]');if(log){try{await presenceLog200(log.dataset.presenceLog);}catch(error){toast(error.message);}return;}
 const send=event.target.closest?.('[data-presence-send]');if(!send||send.disabled)return;const op=presenceOp200(send.dataset.presenceSend);send.disabled=true;try{await presenceSubmit200(send.dataset.presenceSend,op);}catch(error){if(presenceSameActor200(op))toast(error.message);}finally{if(send.isConnected)send.disabled=false;}
});
