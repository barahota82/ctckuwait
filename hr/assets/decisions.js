'use strict';
function decisionButtons199(r){
 const state=r.workflow199;if(!state)return;const root=document.querySelector('#modalContent .modal-body')||document.getElementById('modalContent');if(!root)return;
 if(r.status==='pending'&&['manager','admin'].includes(state.stage)){const p=document.createElement('p');p.className='muted';p.textContent=uiText(state.stage==='manager'?'الطلب لدى المدير المباشر':'الطلب لدى المشرف العام');root.append(p);}
 const buttons=document.createElement('div');buttons.className='row-actions';
 for(const [enabled,action,title]of [[state.can_undo,'record_undo199','التراجع عن الاعتماد'],[state.can_cancel,'record_cancel199','إلغاء الطلب']])if(enabled){const button=document.createElement('button');button.type='button';button.textContent=uiText(title);button.onclick=()=>{modal(uiText(title),'<form id="modalForm">'+(action==='record_undo199'?'<label>'+uiText('سبب التراجع')+'<textarea name="note" required maxlength="5000"></textarea></label>':'<p>'+uiText('سيظل الطلب محفوظًا في السجل بعد إلغائه.')+'</p>')+saveButton()+'</form>',f=>api(action,{id:r.id,revision199:r.revision199,note:f.elements.note?.value||''}));};buttons.append(button);}root.append(buttons);
 const history=JSON.parse(r.body_json||'{}')._decisions199||[];
 if(history.length){const details=document.createElement('details'),summary=document.createElement('summary');summary.textContent=uiText('تاريخ القرارات');details.append(summary);for(const entry of history){const p=document.createElement('p');p.textContent=[localDisplay(entry.at),entry.actor_name||entry.actor,uiText(({approved:'اعتماد',rejected:'رفض',revision:'طلب تعديل'})[entry.decision]||entry.decision),entry.note,entry.withdrawn_at?uiText('تم التراجع')+' — '+entry.withdrawal_reason:entry.invalidated_at?uiText('أُلغي أثره بسبب تغير الطلب أو التراجع عن مرحلة سابقة'):null].filter(Boolean).join(' · ');details.append(p);}root.append(details);}
}

function financialDecisionButton199(r,kind){
 if(!r.approval199?.can_undo)return;const root=document.querySelector('#modalContent .modal-body')||document.getElementById('modalContent');if(!root)return;
 const button=document.createElement('button');button.type='button';button.textContent=uiText('التراجع عن الاعتماد');button.onclick=()=>modal(uiText('التراجع عن الاعتماد'),'<form id="modalForm"><label>'+uiText('سبب التراجع')+'<textarea name="note" required maxlength="5000"></textarea></label>'+saveButton()+'</form>',f=>api(kind+'_undo199',{id:r.id,revision199:r.approval199.revision,note:f.elements.note.value}));root.append(button);
}
