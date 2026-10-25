'use strict';
/* Demo-only record rules. No network, storage, payroll or debt writes. */
window.DemoRecords185=(()=>{
 const requests=[['salary','استفسارات الرواتب والخصومات','Salary and deduction inquiries'],['loan','طلب سلفة','Advance request'],['letters','الخطابات والشهادات','Letters and certificates'],['data','البيانات والمستندات','Data and documents'],['assets','العهد واحتياجات العمل','Assets and work needs'],['complaints','الشكاوى والمقترحات','Complaints and suggestions'],['other','طلبات أخرى','Other requests']];
 const evaluations=[['trial','فترة التجربة','Probation'],['performance','أداء ومهام','Performance and tasks'],['behavior','سلوك وتعاون','Conduct and cooperation'],['improvement','خطط تحسين ومتابعة','Improvement plans'],['other','أخرى','Other']];
 // field = key, Arabic, English, input kind, column span, required
 const field=(key,ar,en,type='textarea',span=3,required=false)=>({key,ar,en,type,span,required});
 const requestFields={
  salary:[field('salary_month','شهر الراتب','Salary month','month',1,true),field('request_type','نوع الاستفسار','Inquiry type','text',2,true),field('details','التفاصيل','Details','textarea',3,true)],
  loan:[field('amount','المبلغ المطلوب','Requested amount','number',3,true),field('reason','سبب طلب السلفة','Reason for advance','textarea',3,true)],
  letters:[field('request_type','نوع الخطاب / الشهادة','Letter / certificate type','text',1,true),field('recipient','الجهة الموجه إليها','Recipient organization','text',2),field('purpose','الغرض','Purpose','textarea',3,true)],
  data:[field('request_type','نوع التحديث','Update type','text',3,true),field('details','التفاصيل','Details','textarea',3,true)],
  assets:[field('requested_item','المطلوب','Requested item','text',3,true),field('reason','سبب الاحتياج','Reason needed','textarea',3,true)],
  complaints:[field('request_type','نوع الطلب','Request type','complaint',1,true),field('title','الموضوع','Subject','text',2,true),field('details','التفاصيل','Details','textarea',3,true)],
  other:[field('title','العنوان','Title','text',3,true),field('details','التفاصيل','Details','textarea',3,true)]
 };
 const evaluationFields={
  trial:[field('criteria','ملاءمة الوظيفة','Job suitability'),field('strengths','نقاط القوة','Strengths'),field('improvement','جوانب التطوير','Development areas'),field('recommendation','توصية المسؤول','Manager recommendation')],
  performance:[field('criteria','جودة تنفيذ المهام','Task quality'),field('results','النتائج المحققة','Results achieved'),field('strengths','نقاط القوة','Strengths'),field('improvement','جوانب التطوير','Development areas')],
  behavior:[field('criteria','التعامل والسلوك','Conduct'),field('cooperation','التعاون','Cooperation'),field('communication','التواصل','Communication'),field('improvement','جوانب التطوير','Development areas')],
  improvement:[field('related_record49','التقييم المرتبط','Related evaluation','parent',3,true),field('date_from','بداية الخطة','Plan start','date',1,true),field('date_to','نهاية الخطة','Plan end','date',1,true),field('followup_date','موعد المتابعة','Follow-up date','date',1,true),field('issues','الجوانب المطلوب تحسينها','Areas for improvement'),field('goals','أهداف التحسين','Improvement goals'),field('actions','الإجراءات','Actions'),field('support','الدعم المطلوب','Support required')],
  other:[field('criteria','المعايير','Criteria'),field('strengths','نقاط القوة','Strengths'),field('improvement','جوانب التطوير','Development areas')]
 };
 const body=r=>JSON.parse(r.body_json||'{}');
 // One review rule for display, filtering and demo decision validation.
 // Operational custody state and system-generated evaluations are not approval requests.
 function reviewPolicy(r,meta){
  const b=body(r),managed=['document','custody','discipline','evaluation','request','loan'].includes(r.module)&&!r.state148&&!b._auto182&&!b._punch39;
  const required=managed&&r.module!=='custody'&&meta?.approval===true;
  const state=required&&(!r.status||r.status==='active')?'pending':r.status;
  return {managed,required,state,canDecide:required&&['pending','revision'].includes(state)};
 }
 function create({records,people,actor,team,system,now=()=>new Date()}){
  const counters=new Map(),automatic=new Map();let autoId=950000;
  const year=()=>now().getUTCFullYear(),type=b=>b.record_category49==='improvement'?'IP':'EV';
  function issue(prefix){const key=prefix+'-'+year(),next=(counters.get(key)||0)+1;counters.set(key,next);return key+'-'+String(next).padStart(6,'0');}
  for(const r of records.filter(r=>r.module==='evaluation')){const b=body(r),m=/^(EV|IP)-(\d{4})-(\d{6})$/.exec(b.reference185||'');if(m)counters.set(m[1]+'-'+m[2],Math.max(counters.get(m[1]+'-'+m[2])||0,Number(m[3])));}
  for(const r of records.filter(r=>r.module==='evaluation')){const b=body(r);if(!b.reference185){b.reference185=issue(type(b));r.body_json=JSON.stringify(b);}}
  for(const r of records.filter(r=>r.module==='evaluation'&&body(r).record_category49==='improvement')){const b=body(r),p=records.find(x=>x.id===Number(b.related_record49));if(p){b.related_reference185=body(p).reference185;r.body_json=JSON.stringify(b);}}
  function auto(uid,range){return system(uid,range).map(r=>{const key=[uid,range.kind,range.year,range.part].join('|'),b=body(r);let retained=automatic.get(key);if(!retained){retained={id:++autoId,reference185:issue('EV'),created_at:now().toISOString()};automatic.set(key,retained);}const result={...r,id:retained.id,created_at:retained.created_at,body_json:JSON.stringify({...b,reference185:retained.reference185})};retained.record=result;return result;});}
  function get(id){return records.find(r=>r.id===Number(id))||[...automatic.values()].find(x=>x.id===Number(id))?.record;}
  function allowed(uid){return people.some(u=>u.id===Number(uid))&&(team()||Number(uid)===Number(actor().id));}
  function error(ar){throw Error(ar);}
  function parents(uid){if(!allowed(uid))error('الموظف خارج النطاق المتاح.');return [...records,...[...automatic.values()].map(x=>x.record)].filter(r=>r.module==='evaluation'&&Number(r.user_id)===Number(uid)&&body(r).record_category49!=='improvement');}
  function save(b,nextId){const old=b.id?get(b.id):null,module=b.module;if(b.id&&!old)error('السجل غير موجود.');if(old&&old.module!==module)error('نوع السجل لا يتغير.');const uid=Number(b.user_id||old?.user_id||actor().id);if(!allowed(uid)||old&&Number(old.user_id)!==uid)error('الموظف خارج النطاق المتاح.');if(module==='evaluation'&&!team())error('التقييم متاح للمسؤول فقط.');if(old&&body(old)._auto182)error('التقييم الآلي لا يعدل يدويًا.');if(old&&!team()&&old.status!=='revision')error('لا يمكن تعديل الطلب في حالته الحالية.');
   const values={...(old?body(old):{}),...(typeof b.body==='string'?JSON.parse(b.body):b.body||{})},category=module==='loan'?'loan':values.record_category49;values.record_category49=category;
   const fields=module==='evaluation'?evaluationFields[category]:requestFields[category];if(!fields||module==='request'&&category==='loan')error('اختر تصنيفًا صحيحًا.');
   const out={...old,module,user_id:uid,title:String(b.title||old?.title||'').trim(),date_from:b.date_from||null,date_to:b.date_to||null,amount:0};
   for(const f of fields){const value=['title','amount','date_from','date_to'].includes(f.key)?b[f.key]:values[f.key];if(f.required&&!String(value??'').trim())error('أكمل الحقول المطلوبة.');}
   if(!out.title)error('العنوان مطلوب.');
   if(module==='evaluation'){
    if(old&&type(body(old))!==type(values))error('لا يمكن تحويل تقييم إلى خطة أو العكس.');
    if(!out.date_from||!out.date_to||out.date_to<out.date_from)error('أدخل فترة صحيحة.');
    if(category==='improvement'){const p=get(values.related_record49);if(!p||p.module!=='evaluation'||Number(p.user_id)!==uid||body(p).record_category49==='improvement'||p.id===old?.id)error('اختر تقييمًا لنفس الموظف.');if(values.followup_date<out.date_from)error('المتابعة لا تسبق بداية الخطة.');values.related_reference185=body(p).reference185;delete values.score;}
    else{if(values.score!==''&&values.score!=null&&(!Number.isFinite(Number(values.score))||Number(values.score)<0||Number(values.score)>100))error('الدرجة من صفر إلى 100.');delete values.related_record49;}
    values.source182='manager';values.reference185=old?body(old).reference185:'';
   }else{out.date_from=null;out.date_to=null;if(category==='salary'&&!/^\d{4}-(0[1-9]|1[0-2])$/.test(values.salary_month))error('أدخل شهر راتب صحيحًا.');if(module==='loan'){out.amount=Number(b.amount);if(!Number.isFinite(out.amount)||out.amount<=0)error('أدخل مبلغًا أكبر من صفر.');}}
   const u=people.find(u=>u.id===uid);Object.assign(out,{employee_name:u.name,name:u.name,name_en:u.name_en,employee_code:u.employee_code,body_json:JSON.stringify(values),status:old?.status||'pending',created_at:old?.created_at||now().toISOString(),revision185:(old?.revision185||0)+1});
   if(b.file?.name){if(b.file.size>8*1024*1024||!['image/jpeg','image/png','image/webp','application/pdf'].includes(b.file.type))error('نوع المرفق أو حجمه غير مسموح.');out.file_id=1;out.attachment_name185=b.file.name;}
   if(module==='evaluation'&&!old){values.reference185=issue(type(values));out.body_json=JSON.stringify(values);}
   if(old)Object.assign(old,out);else{out.id=nextId();records.push(out);}return {ok:true,id:out.id};
  }
  function action(name,b){const r=get(b.id);if(!r||!allowed(r.user_id))error('السجل غير متاح.');if(name==='evaluation_followup185'){if(!team()||body(r).record_category49!=='improvement')error('المتابعة متاحة للمسؤول فقط.');if(!String(b.result||'').trim())error('سجّل نتيجة المتابعة.');r.body_json=JSON.stringify({...body(r),followup_result:String(b.result),followup_at:now().toISOString()});}
   else if(name==='record_review'){if(!team()||Number(r.user_id)===Number(actor().id))error('لا يمكنك مراجعة طلبك الشخصي.');if(!['pending','revision','active'].includes(r.status)||!['approved','revision','rejected','closed'].includes(b.status))error('الإجراء غير متاح في حالة السجل الحالية.');r.status=b.status;r.review_note=b.note||'';}
   else if(name==='record_reply'){if(Number(r.user_id)!==Number(actor().id))error('الرد لصاحب السجل فقط.');r.body_json=JSON.stringify({...body(r),_employee_reply:b.reply||''});}
   else if(name==='record_resubmit'){if(Number(r.user_id)!==Number(actor().id)||r.status!=='revision')error('إعادة التقديم لصاحب الطلب بعد طلب تعديله.');r.body_json=JSON.stringify({...body(r),_revision_details:b.details||''});r.status='pending';if(b.file?.name)r.file_id=1;}
   r.revision185=(r.revision185||0)+1;return {ok:true,id:r.id};
  }
  return {auto,parents,save,action,get};
 }
 const dictionary=Object.fromEntries([...requests,...evaluations].map(([,ar,en])=>[ar,en]));
 for(const f of [...Object.values(requestFields),...Object.values(evaluationFields)].flat())dictionary[f.ar]=f.en;
 Object.assign(dictionary,{'كل الموظفين في نطاقك':'All employees in your scope','عرض كل الموظفين':'Show all employees','رقم التقييم':'Evaluation reference','رقم الخطة':'Plan reference','فترة التقييم من':'Assessment period from','فترة التقييم إلى':'Assessment period to','الدرجة من 100':'Score out of 100','اختر التقييم المرتبط':'Choose the related evaluation','لا توجد تقييمات لهذا الموظف':'No evaluations for this employee','إنشاء خطة تحسين':'Create improvement plan','نتيجة المتابعة':'Follow-up result','حفظ نتيجة المتابعة':'Save follow-up result','تم حفظ نتيجة المتابعة':'Follow-up result saved','سجّل نتيجة المتابعة.':'Enter the follow-up result.','شكوى':'Complaint','مقترح':'Suggestion','تاريخ الطلب':'Request date','مرفق المستند':'Document attachment','الطلبات الإدارية':'Administrative requests','قائمة الطلبات':'Request list','أكمل الحقول المطلوبة.':'Complete the required fields.','اختر تقييمًا لنفس الموظف.':'Choose an evaluation for the same employee.','أدخل فترة صحيحة.':'Enter a valid period.','بانتظار اعتماد النقاط':'Scoring rules pending'});
 return {requests,evaluations,requestFields,evaluationFields,create,body,reviewPolicy,dictionary};
})();
