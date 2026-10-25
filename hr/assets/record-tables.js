'use strict';
// Per-tab presentation only. Record data, review decisions and scope stay with the caller.
function recordColumns191(module,category,automatic=false){
 const c=(key,label)=>({key,label}),employee=c('employee','الموظف'),classification=c('category','التصنيف');
 const end=[c('status','الحالة'),c('detail','التفاصيل')];let middle=[];
 if(module==='document')middle=[classification,c('title','اسم المستند'),...(category==='all'?[]:[c('document_number','رقم المستند')]),c('date_from',category==='certificates'?'تاريخ الشهادة':'تاريخ المستند'),...(category==='certificates'?[]:[c('date_to','تاريخ الانتهاء')])];
 if(module==='custody')middle=[classification,c('title','اسم العهدة'),c('serial','الرقم / اللوحة'),c('date_from','تاريخ الاستلام'),c('return_date','تاريخ الاسترجاع')];
 if(module==='discipline'){
  middle=[classification];
  if(category==='all')middle.push(c('period','الفترة / التاريخ'),c('amount','المبلغ'));
  else if(category==='penalty')middle.push(c('date_from','تاريخ الجزاء'),c('payroll_month','شهر تطبيق الخصم'),c('amount','مبلغ الخصم'));
  else if(category==='decision')middle.push(c('reason','السبب / التفاصيل'),c('date_from','بداية السريان'),c('date_to','نهاية السريان'));
  else if(category==='appeal')middle.push(c('related_record49','القرار محل التظلم'),c('date_from','تاريخ التظلم'));
  else middle.push(c('reason','السبب / التفاصيل'),c('date_from','التاريخ'));
 }
 if(module==='request'){
  middle=[classification];
  if(category==='loan')middle.push(c('reason','سبب طلب السلفة'),c('amount','المبلغ المطلوب'));
  else if(category==='salary')middle.push(c('request_type','نوع الاستفسار'),c('details','ملخص الاستفسار'),c('salary_month','شهر الراتب'));
  else if(category==='letters')middle.push(c('request_type','نوع الخطاب / الشهادة'),c('recipient','الجهة الموجه إليها'));
  else if(category==='data')middle.push(c('request_type','نوع التحديث'),c('details','ملخص التحديث'));
  else if(category==='assets')middle.push(c('requested_item','المطلوب'),c('reason','سبب الاحتياج'));
  else if(category==='complaints')middle.push(c('request_type','نوع الطلب'),c('details','ملخص الطلب'));
  else if(category==='other')middle.push(c('details','ملخص الطلب'));
  middle.push(c('created','تاريخ الطلب'));
  if(category==='all')middle.push(c('amount','المبلغ المطلوب'));
 }
 if(module==='evaluation'){
  if(automatic)middle=[c('reference185','رقم التقييم'),c('period','الفترة'),c('score','الدرجة من 100')];
  else if(category==='improvement')middle=[classification,c('reference185','رقم الخطة'),c('related_reference185','التقييم المرتبط'),c('period','مدة الخطة'),c('followup_date','موعد المتابعة')];
  else middle=[classification,c('reference185',category==='all'?'رقم التقييم / الخطة':'رقم التقييم'),c('period','فترة التقييم'),c('score','الدرجة من 100')];
 }
 return [employee,...middle,...end];
}
function recordCell191(key,module,category,r,person){
 const b=JSON.parse(r.body_json||'{}'),cat=r.module==='loan'?'loan':b.record_category49;
 const date=v=>'<bdi>'+esc(v?dateDisplay32(v):'—')+'</bdi>';
 const text=(k,v)=>dataTextMarkup162('record_text:'+r.module+':'+k,String(v??'—'));
 if(key==='employee')return '<div class="record-employee187">'+employeeNameMarkup143(person.id,person.name,person)+'<small><bdi>'+esc(person.employee_code||person.username||'')+'</bdi></small></div>';
 if(key==='category')return esc(uiText(recordCategoryLabel49(module,cat)||'أخرى'));
 if(key==='title')return dataTextMarkup162(recordTextContext173(module,'title'),r.title)+(module==='document'&&category==='all'?'<small class="record-meta191"><bdi>'+esc(b.document_number||'—')+'</bdi></small>':'');
 if(key==='status')return module==='custody'?recordBadge181(r).replace('<span ','<span data-no-translate data-text-key138="'+(b.return_date?'مستردة':'سارية')+'" '):recordBadge181(r);
 if(key==='detail')return '<button type="button" '+(module==='request'?'data-detail185':'data-record173')+'="'+Number(r.id)+'">'+esc(uiText('التفاصيل'))+'</button>';
 if(key==='created')return date(String(r.created_at||'').slice(0,10));
 if(key==='date_from')return date(r.date_from);
 if(key==='date_to')return module==='document'?date(cat==='certificates'?'':b.expiry||r.date_to):r.date_to?date(r.date_to):esc(uiText('مستمر'));
 if(key==='return_date'||key==='followup_date')return date(b[key]);
 if(key==='period')return r.date_to?'<div class="record-period191"><span>'+date(r.date_from)+'</span><span>'+date(r.date_to)+'</span></div>':date(r.date_from);
 if(key==='amount')return r.module==='loan'||module==='discipline'&&cat==='penalty'?'<bdi>'+esc(Number(r.amount||0).toFixed(3)+' '+uiText(boot.company.currency||'د.ك'))+'</bdi>':'—';
 if(key==='score')return '<bdi>'+esc(b._auto182||cat==='improvement'||b.score==null||b.score===''?'—':b.score)+'</bdi>';
 if(key==='related_record49')return '<bdi>'+esc(b.related_record49?'#'+b.related_record49:'—')+'</bdi>';
 if(['reference185','related_reference185','document_number','serial','payroll_month','salary_month'].includes(key))return '<bdi>'+esc(b[key]||'—')+'</bdi>';
 if(key==='request_type'&&['complaint','suggestion'].includes(b[key]))return esc(uiText(b[key]==='complaint'?'شكوى':'مقترح'));
 return text(key,b[key]);
}
function recordTable191(module,category,rows,people,automatic=false){
 const columns=recordColumns191(module,category,automatic),names=new Map(people.map(p=>[Number(p.id),p]));
 const prose=new Set(['title','reason','details','recipient','requested_item','request_type']);
 const widths={category:140,title:160,document_number:110,serial:115,date_from:108,date_to:108,return_date:108,created:108,followup_date:108,period:112,amount:110,score:86,reference185:140,related_reference185:140,related_record49:140,payroll_month:115,salary_month:95,reason:180,details:180,recipient:150,requested_item:160,request_type:145,status:144,detail:80};
 const head=columns.map(c=>'<th scope="col" data-column191="'+c.key+'" data-no-translate data-text-key138="'+esc(c.label)+'">'+esc(uiText(c.label))+'</th>').join('');
 const body=rows.map(r=>'<tr data-record-row191="'+Number(r.id)+'">'+columns.map(c=>'<td data-column191="'+c.key+'"><div class="'+(prose.has(c.key)?'record-text191':'record-value191')+'">'+recordCell191(c.key,module,category,r,names.get(Number(r.user_id))||{id:r.user_id,name:r.employee_name,employee_code:r.employee_code})+'</div></td>').join('')+'</tr>').join('');
 return '<div class="table-wrap record-scroll191" data-compact32="1" tabindex="0" aria-label="'+esc(uiText('جدول السجلات'))+'"><table class="record-table191" data-module191="'+module+'" data-category191="'+category+'"><colgroup>'+columns.map(c=>'<col'+(c.key==='employee'?'':' style="width:'+widths[c.key]+'px"')+'>').join('')+'</colgroup><thead><tr>'+head+'</tr></thead><tbody>'+body+'</tbody></table></div>';
}
