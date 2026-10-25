'use strict';
let departureTimer199=null;
function departureLabel199(){const c=boot.departure199;return c&&!c.release&&Date.now()+serverOffset<new Date(c.end_at.replace(' ','T')+'Z').getTime()?'انصراف مبكر':'انصراف';}
function departureButton199(){
 clearInterval(departureTimer199);const update=()=>{const button=document.querySelector('[data-event="checkout"]');if(!button){clearInterval(departureTimer199);return;}button.textContent=uiText(departureLabel199());if(selectedEvent==='checkout'&&!pending){const send=document.getElementById('sendEvent');if(send)send.textContent=uiText('تسجيل')+' '+uiText(departureLabel199());}};
 update();document.querySelectorAll('[data-event]').forEach(b=>b.addEventListener('click',update));departureTimer199=setInterval(update,1000);
}
function departureReportDetails199(day,uid,staff){
 const root=document.querySelector('#modalContent .modal-body')||document.getElementById('modalContent');if(!root)return;
 const history=day.departure_releases199||[];for(const entry of history){const block=document.createElement('section');block.className='detail-row';const text=document.createElement('p');const actual=(day.events||[]).find(e=>e.event_type==='checkout');text.textContent=[uiText('السماح بالانصراف'),entry.manager_name,localDisplay(entry.granted_at),uiText('نهاية الدوام')+': '+localDisplay(entry.scheduled_end),uiText('الانصراف الفعلي')+': '+(actual?localDisplay(actual.received_at):uiText('لم يسجل بعد')),entry.reason,entry.withdrawn_at?uiText('تم التراجع')+' — '+entry.withdrawal_reason:''].filter(Boolean).join(' · ');block.append(text);
  if(entry.status==='approved'&&Number(entry.granted_by)===Number(boot.user.id)&&boot.access_mode!=='personal'){const btn=document.createElement('button');btn.textContent=uiText('التراجع عن السماح');btn.onclick=()=>modal(uiText('التراجع عن السماح'),'<form id="modalForm"><label>'+uiText('السبب')+'<textarea name="reason" required></textarea></label>'+saveButton()+'</form>',f=>api('departure_withdraw199',{id:entry.id,user_id:uid,reason:f.elements.reason.value}));block.append(btn);}root.append(block);
 }
 if(!uid||boot.access_mode==='personal'||day.date!==Workflows194.stamp(Date.now()+serverOffset).slice(0,10))return;
 if(!staff||staff.staff_type!=='rep'||(boot.user.role!=='admin'&&Number(staff.manager_id)!==Number(boot.user.id)))return;
 if(history.some(x=>x.status==='approved')||(day.events||[]).some(e=>e.event_type==='checkout'))return;
 const button=document.createElement('button');button.textContent=uiText('السماح بالانصراف');button.onclick=()=>modal(uiText('السماح بالانصراف'),'<form id="modalForm">'+input('reason','السبب','انتهاء مهام التحصيل / التوصيل')+saveButton()+'</form>',f=>api('departure_grant199',{user_id:uid,work_date199:day.date,reason:f.elements.reason.value}));root.append(button);
}
