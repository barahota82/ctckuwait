'use strict';
/* Shared form schemas; persistence is handled by the authenticated PHP API. */
window.RecordFields194=(()=>{
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
 const dictionary=Object.fromEntries([...requests,...evaluations].map(([,ar,en])=>[ar,en]));
 for(const f of [...Object.values(requestFields),...Object.values(evaluationFields)].flat())dictionary[f.ar]=f.en;
 Object.assign(dictionary,{'كل الموظفين في نطاقك':'All employees in your scope','عرض كل الموظفين':'Show all employees','رقم التقييم':'Evaluation reference','رقم الخطة':'Plan reference','فترة التقييم من':'Assessment period from','فترة التقييم إلى':'Assessment period to','الدرجة من 100':'Score out of 100','اختر التقييم المرتبط':'Choose the related evaluation','لا توجد تقييمات لهذا الموظف':'No evaluations for this employee','إنشاء خطة تحسين':'Create improvement plan','نتيجة المتابعة':'Follow-up result','حفظ نتيجة المتابعة':'Save follow-up result','تم حفظ نتيجة المتابعة':'Follow-up result saved','سجّل نتيجة المتابعة.':'Enter the follow-up result.','شكوى':'Complaint','مقترح':'Suggestion','تاريخ الطلب':'Request date','مرفق المستند':'Document attachment','الطلبات الإدارية':'Administrative requests','قائمة الطلبات':'Request list','أكمل الحقول المطلوبة.':'Complete the required fields.','اختر تقييمًا لنفس الموظف.':'Choose an evaluation for the same employee.','أدخل فترة صحيحة.':'Enter a valid period.','بانتظار اعتماد النقاط':'Scoring rules pending'});
 return {requests,evaluations,requestFields,evaluationFields,body,reviewPolicy,dictionary};
})();
