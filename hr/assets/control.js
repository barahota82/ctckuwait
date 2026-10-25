 'use strict';
const permissionLabels={view:'عرض',create:'إضافة / إرسال',edit:'تعديل',review:'مراجعة / اعتماد',export:'تصدير',transfer:'تحويل الحالة'};
let permissionData=null,permissionManager=0;
async function permissionsView(){
 permissionData=await api('permissions_list');const managers=permissionData.managers;
 if(!managers.some(m=>Number(m.id)===permissionManager))permissionManager=Number(managers[0]?.id??0);
 const grants=permissionData.grants[String(permissionManager)]??{};
 $('#page').innerHTML=head('صلاحيات مديري الأقسام','كل مدير يصل فقط للموظفين المرتبطين به في ملفاتهم. تعديل الصلاحيات يسري على الطلب التالي.')+
 '<div class="phase-note"><span>♧</span><div><b>صلاحيات محددة، ومسؤولية واضحة</b></div></div>'+
 (!managers.length?'<section class="card">'+empty('أضف حساب مدير قسم من الموظفين، ثم اربط به فريقه.')+'<button data-view="users" class="primary">فتح الموظفين</button></section>':
 '<form id="permissionForm"><section class="card"><div class="toolbar"><label>مدير القسم<select id="permissionManager">'+managers.map(m=>'<option value="'+m.id+'" '+(Number(m.id)===permissionManager?'selected':'')+'>'+employeeNameMarkup143(m.id,m.name,m)+' — '+esc(m.department||'بدون قسم')+'</option>').join('')+'</select></label><button class="primary">حفظ الصلاحيات</button></div><div class="permission-grid">'+Object.entries(permissionData.catalog).map(([key,screen])=>'<article class="permission-card"><h3>'+esc(screen.label)+'</h3><div>'+screen.actions.map(op=>'<label class="check"><input type="checkbox" data-permission="'+key+'" data-operation="'+op+'" '+((grants[key]??[]).includes(op)?'checked':'')+'>'+permissionLabels[op]+'</label>').join('')+'</div></article>').join('')+'</div></section></form>');
 if(!managers.length)return;
 $('#permissionManager').onchange=async e=>{permissionManager=Number(e.target.value);await permissionsView();};
 $('#permissionForm').onsubmit=async e=>{e.preventDefault();try{const next={};document.querySelectorAll('[data-permission]').forEach(box=>{if(box.checked){(next[box.dataset.permission]??=[]).push(box.dataset.operation);if(!next[box.dataset.permission].includes('view'))next[box.dataset.permission].push('view');}});await api('permissions_save',{user_id:permissionManager,grants:next});toast('تم حفظ صلاحيات المدير');await permissionsView();}catch(e){toast(e.message);}};
}
async function publicationView(){
 cmsData=await api('cms_list');
 $('#page').innerHTML=head('ظهور صفحات الموقع','أنت تحدد ما يراه الجمهور. المحتوى المخفي يبقى متاحًا للتحضير داخل الإدارة.',!cmsData.pages.some(p=>p.slug==='ratings')?'<button id="createRatingsPage" class="primary">إضافة صفحة قيّم تجربتك</button>':'')+
 '<div class="phase-note"><span>◧</span><div><b>'+ (offersPublicationState(cmsData).enabled?'صفحة العروض منشورة':'صفحة العروض أو أقسامها مخفية')+'</b></div></div>'+
 '<div class="publication-grid">'+cmsData.pages.map(p=>'<article class="card publication-card"><div class="section-top"><span class="publication-icon">'+(p.slug==='home'?'⌂':p.slug==='offers'?'◇':'▤')+'</span>'+badge(Number(p.visible)?'active':'draft')+'</div><h2>'+esc(cmsTitle(p))+'</h2><p class="muted">/'+esc(p.slug)+'</p><div class="publication-state">'+(Number(p.visible)?'منشورة للزوار':'مخفية — تجهيز داخلي')+'</div><div class="row-actions"><button data-public-edit="'+p.id+'">إدارة المحتوى</button>'+('<button class="'+(Number(p.visible)?'':'primary')+'" data-public-toggle="'+p.id+'">'+(Number(p.visible)?'إخفاء الصفحة':'نشر الصفحة')+'</button>')+'</div></article>').join('')+'</div>';
 if($('#createRatingsPage'))$('#createRatingsPage').onclick=async()=>{try{await api('cms_ratings_page',{});await publicationView();toast('أضيفت صفحة التقييم؛ راجعها ثم انشرها.');}catch(e){toast(e.message);}};
 document.querySelectorAll('[data-public-edit]').forEach(b=>b.onclick=async()=>{cmsPage=Number(b.dataset.publicEdit);await navigate('cms');});
 document.querySelectorAll('[data-public-toggle]').forEach(b=>b.onclick=async()=>{try{const row=cmsData.pages.find(p=>Number(p.id)===Number(b.dataset.publicToggle));await api('cms_save',{...row,entity:'page',visible:!Number(row.visible),content:JSON.parse(row.content_json)});await publicationView();toast('تم تحديث ظهور الصفحة');}catch(e){toast(e.message);}});
}
