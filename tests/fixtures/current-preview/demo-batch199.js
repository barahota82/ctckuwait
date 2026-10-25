'use strict';
// Fictional demonstration state only. Real authorization and persistence are in the PHP source.
(()=>{
 const seed=window.demoSeed,review=window.demoReview193,original=window.fetch,clone=x=>JSON.parse(JSON.stringify(x)),body=r=>JSON.parse(r.body_json||'{}'),now=()=>new Date().toISOString().slice(0,19).replace('T',' '),today=()=>new Date(Date.now()+10800000).toISOString().slice(0,10);let serial=199000;
 const delegations=[],releases=[],financial={payroll:null,bank:null},financialHistory=[];const revision=r=>JSON.stringify([r.status,r.body_json,r.date_from,r.date_to,r.amount,r.reviewed_by,r.review_note]);
 for(const u of seed.people){if([2,4].includes(u.id))u.role='manager';if(u.id!==2&&u.id!==4)u.manager_id=2;}
 const records=()=>[...seed.records,...review.resumptions],find=id=>records().find(r=>r.id===Number(id)),personalKinds=['request','leave','sick','external','loan','resumption'];
 const response=(data,status=200)=>({ok:status<400,status,json:async()=>clone(status<400?{data}:{error:data}),text:async()=>JSON.stringify(status<400?{data}:{error:data}),headers:{get:()=> 'application/json'}});
 function describe(r,c){const v=body(r),flow=v._workflow199||{},team=c.access_mode==='team',own=Number(r.user_id)===Number(c.user.id),live=(v._decisions199||[]).find(e=>e.decision==='approved'&&!e.withdrawn_at&&!e.invalidated_at&&e.actor===c.user.id);return {...r,revision199:revision(r),workflow199:{stage:flow.stage||'legacy',can_review:team&&!own&&(flow.stage!=='manager'||c.user.role!=='admin'),can_undo:team&&!!live,can_cancel:['pending','revision'].includes(r.status)&&personalKinds.includes(r.module)&&(team||own&&!flow.ever_approved)}};}
 function store(r,v){r.body_json=JSON.stringify(v);}
 function initial(r,c){const v=body(r);if(!v._workflow199){v._workflow199={stage:personalKinds.includes(r.module)?'admin':'legacy',two_stage:false,ever_approved:false,submitted_by:c.user.id,submitted_as_manager:c.access_mode==='personal'};store(r,v);}}
 const notification=(uid,title,text,meta)=>seed.notices.unshift({id:++serial,user_id:uid,title,body:text,created_at:now(),read_at:null,require_ack:0,meta});
 function permissionDescribe(p,c){return {...p,can_undo199:c.access_mode==='team'&&p.decision==='approved'&&p.reviewed_by===c.user.id&&!!p.before_approval199,can_cancel199:p.decision==='pending'&&(c.access_mode==='team'||p.user_id===c.user.id&&!p.ever_approved199)};}
 window.fetch=async(input,options={})=>{
  const url=new URL(String(input),location.href),action=url.searchParams.get('action'),params=Object.fromEntries(url.searchParams);if(!action)return original(input,options);
  const b=options.body?(typeof options.body==='string'?JSON.parse(options.body):Object.fromEntries(options.body.entries())):{};
  try{
   const c=await window.demoDispatch('bootstrap',null,{}),team=c.access_mode==='team',actor=c.user;
   const requireTeam=()=>{if(!team)throw Error('هذه العملية في مساحة العمل.');};
   if(action==='delegation_list199'){requireTeam();return response({records:delegations.map(r=>({...r,revision199:revision(r)})),staff:seed.people});}
   if(action==='delegation_save199'){
    requireTeam();if(!b.date_from||!['admin','substitute'].includes(b.route)||b.route==='substitute'&&(!Number(b.substitute_id)||Number(b.substitute_id)===Number(b.manager_id)))throw Error('حدد جهة التحويل والبديل وتاريخ البداية.');
    if(delegations.some(r=>body(r).manager_id===Number(b.manager_id)&&(!r.date_to||r.date_to>=b.date_from)))throw Error('يوجد تكليف متداخل؛ سجل تاريخ انتهائه أولًا.');
    const r={id:++serial,module:'manager_delegation199',status:'active',date_from:b.date_from,date_to:null,body_json:JSON.stringify({...b,manager_id:Number(b.manager_id),substitute_id:Number(b.substitute_id),original_manager_retains_authority:true})};delegations.push(r);return response({id:r.id});
   }
   if(action==='delegation_end199'){requireTeam();const r=delegations.find(r=>r.id===Number(b.id));if(!r||revision(r)!==b.revision199)throw Error('تغير التكليف؛ أعد فتحه.');if(!b.date_to||b.date_to<r.date_from)throw Error('حدد تاريخ انتهاء لا يسبق البداية.');r.date_to=b.date_to;return response({ok:true});}
   if(action==='review193_type_delete'){requireTeam();const t=review.types.find(t=>t.id===Number(b.id));if(!t)throw Error('المسمى غير موجود.');if([...seed.records,...seed.files].some(r=>Number(body(r).document_type_id)===t.id||body(r).document_type===t.name))throw Error('المسمى مرتبط بمستندات؛ لا يمكن حذفه.');review.types.splice(review.types.indexOf(t),1);return response({ok:true});}
   if(['record_undo199','record_cancel199'].includes(action)){
    const r=find(b.id);if(!r||revision(r)!==b.revision199)throw Error('تغير السجل؛ أعد فتحه.');const d=describe(r,c),v=body(r);
    if(action==='record_cancel199'){if(!d.workflow199.can_cancel)throw Error('إلغاء الطلب بعد أول اعتماد للمشرف العام.');r.status='cancelled';v._cancellation199={actor:actor.id,at:now()};}
    else{if(!d.workflow199.can_undo)throw Error('التراجع لصاحب الاعتماد فقط.');if(!b.note?.trim())throw Error('سبب التراجع مطلوب.');const h=v._decisions199,i=h.findLastIndex(e=>e.actor===actor.id&&e.decision==='approved'&&!e.withdrawn_at&&!e.invalidated_at),event=h[i];event.withdrawn_at=now();event.withdrawal_reason=b.note;for(const later of h.slice(i+1))later.invalidated_at=now();for(const k of ['_workflow199','_approved_on','_valuation','_sick_days']){if(k in event.before.body)v[k]=clone(event.before.body[k]);else delete v[k];}v._workflow199={...v._workflow199,ever_approved:true};r.status=event.before.status;r.reviewed_by=event.before.reviewed_by;r.review_note=event.before.review_note;r.amount=event.before.amount;}
    store(r,v);return response({ok:true});
   }
   if(['permission_undo199','permission_cancel_request199'].includes(action)){
    const p=seed.workflows182.permissions.find(p=>p.id===Number(b.id));if(!p||p.revision!==Number(b.revision))throw Error('تغير الطلب؛ أعد فتحه.');const d=permissionDescribe(p,c);
    if(action==='permission_undo199'){if(!d.can_undo199||!b.reason?.trim())throw Error('التراجع لصاحب الاعتماد مع سبب مطلوب.');if(!p.exited_at)Object.assign(p,clone(p.before_approval199));p.decision='pending';p.ever_approved199=true;delete p.before_approval199;delete p.approved_at;delete p.reviewed_by;}
    else{if(!d.can_cancel199)throw Error('الإلغاء قبل أول اعتماد فقط.');p.decision='cancelled';p.cancelled_at=now();}p.revision++;return response(permissionDescribe(seed.workflows182.describe(p),c));
   }
   if(action==='departure_grant199'){
    requireTeam();const u=seed.people.find(u=>u.id===Number(b.user_id));if(!u||u.staff_type!=='rep')throw Error('السماح خاص بالمندوب.');const existing=releases.find(r=>r.user_id===u.id&&r.work_date===today()&&r.status==='approved');if(existing)return response({id:existing.id,duplicate:true});
    const r={id:++serial,user_id:u.id,work_date:today(),status:'approved',granted_by:actor.id,manager_name:actor.name,granted_at:now(),scheduled_end:today()+' 14:00:00',reason:b.reason||'انتهاء مهام التحصيل / التوصيل'};releases.push(r);notification(1,'السماح للمندوب بالانصراف',u.name+' — '+actor.name,{screen:'report',employee_id:u.id,date:today()});return response({id:r.id});
   }
   if(action==='departure_withdraw199'){requireTeam();const r=releases.find(r=>r.id===Number(b.id)&&r.status==='approved');if(!r||r.granted_by!==actor.id||!b.reason?.trim())throw Error('التراجع لصاحب القرار مع بيان السبب.');r.status='cancelled';r.withdrawn_at=now();r.withdrawal_reason=b.reason;return response({ok:true});}
   if(['payroll_list','bank_list','payroll_approve','bank_approve','payroll_undo199','bank_undo199','v26_payroll_submit','v26_payroll_return'].includes(action)){
    const kind=action.startsWith('bank')?'bank':'payroll';
    if(!financial[kind]){const base=await original('api.php?action='+kind+'_list',{});financial[kind]=(await base.json()).data;}
    const rows=financial[kind];if(action.endsWith('_list'))return response(rows.filter(r=>team||Number(r.user_id)===Number(actor.id)).map(r=>({...r,approval199:{can_undo:team&&r.status==='approved'&&financialHistory.some(h=>h.kind===kind&&h.id===Number(r.id)&&h.actor===actor.id&&!h.withdrawn),revision:revision(r)}})));
    requireTeam();const r=rows.find(r=>Number(r.id)===Number(b.id));if(!r)throw Error('الكشف غير موجود.');
    if(action.endsWith('_undo199')){const h=financialHistory.findLast(h=>h.kind===kind&&h.id===Number(r.id)&&h.actor===actor.id&&!h.withdrawn);if(!h||r.status!=='approved'||revision(r)!==b.revision199||!b.note?.trim())throw Error('التراجع لصاحب الاعتماد مع سبب وتحديث الكشف.');r.status=h.before;h.withdrawn=true;h.reason=b.note;}
    else if(action==='v26_payroll_submit'){if(!['draft','returned'].includes(r.status))throw Error('الكشف غير متاح للإرسال.');r.status='submitted';}
    else if(action==='v26_payroll_return'){if(r.status!=='submitted'||!b.note?.trim())throw Error('سبب طلب التعديل مطلوب.');r.status='returned';}
    else{if(r.status!==(kind==='payroll'?'submitted':'draft'))throw Error('الكشف غير متاح للاعتماد.');financialHistory.push({id:Number(r.id),kind,actor:actor.id,before:r.status});r.status='approved';}
    return response({ok:true});
   }
   let prior=null;
   if(['record_review','v26_resumption_review'].includes(action)){
    requireTeam();const r=find(b.id);if(!r||!describe(r,c).workflow199.can_review||!['pending','revision','active'].includes(r.status))throw Error('الطلب ليس في مرحلة مراجعتك.');if(b.revision199&&b.revision199!==revision(r))throw Error('تغير الطلب؛ أعد فتحه.');if(['rejected','revision'].includes(b.status)&&!b.note?.trim())throw Error('سبب الرفض أو طلب التعديل مطلوب.');prior=clone(r);
   }
   if(action==='permission_review183'){
    const p=seed.workflows182.permissions.find(p=>p.id===Number(b.id));if(b.decision==='reject'&&!b.note?.trim())throw Error('سبب الرفض مطلوب.');if(p&&b.decision==='approve')prior=clone(p);
   }
   const res=await original(input,options),envelope=await res.json();if(!res.ok)return {...res,json:async()=>envelope};let data=envelope.data;
   if(prior&&['record_review','v26_resumption_review'].includes(action)){
    const r=find(b.id),v=body(r),beforeBody=body(prior);delete beforeBody._decisions199;
    v._decisions199=[...(body(prior)._decisions199||[]),{id:++serial,actor:actor.id,actor_name:actor.name,at:now(),decision:b.status,note:b.note||'',before:{status:prior.status,body:beforeBody,reviewed_by:prior.reviewed_by,review_note:prior.review_note,amount:prior.amount}}];
    v._workflow199={...(v._workflow199||{}),ever_approved:b.status==='approved'||!!v._workflow199?.ever_approved};r.reviewed_by=actor.id;store(r,v);
   }
   if(prior&&action==='permission_review183'){const p=seed.workflows182.permissions.find(p=>p.id===Number(b.id));p.before_approval199=prior;p.ever_approved199=true;data=permissionDescribe(seed.workflows182.describe(p),c);}
   if(action==='record_save'||action==='v26_save'&&b.kind==='resumption'){const r=find(data.id);if(r)initial(r,c);}
   if(['record_resubmit','v26_resumption_resubmit'].includes(action)){const r=find(b.id);if(r){const v=body(r);for(const e of v._decisions199||[])e.invalidated_at=now();store(r,v);}}
   if(action==='list'&&Array.isArray(data))data=data.map(r=>describe(r,c));
   if(action==='v26_info'&&params.kind==='resumption')data={...data,records:data.records.map(r=>describe(r,c))};
   if(action==='permissions182')data=data.map(p=>permissionDescribe(p,c));if(action==='permission_get182')data=permissionDescribe(data,c);
   if(['bootstrap','punch127_info'].includes(action))data={...data,departure199:{work_date:today(),end_at:today()+' 14:00:00',release:releases.find(r=>r.user_id===actor.id&&r.work_date===today()&&r.status==='approved')||null}};
   if(action==='payroll_build'&&financial.payroll)for(const r of financial.payroll)if(!b.company||r.work_company41===b.company)r.status='draft';
   if(action==='report'&&Array.isArray(data))data=data.map(r=>({...r,departure_releases199:releases.filter(x=>x.user_id===Number(params.user_id)&&x.work_date===r.date)}));
   return response(data);
  }catch(e){return response(e.message,422);}
 };
 window.demo199={delegations,releases,revision};
})();
