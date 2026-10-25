'use strict';
let debtPeriod95=null,debtDetail95=0,debtRequest95=0,debtContext95='';
function debtDate95(value){const d=String(value||'').slice(0,10);if(!/^\d{4}-\d{2}-\d{2}$/.test(d))throw Error(uiText('تاريخ حركة المديونية غير صالح'));const x=new Date(d+'T00:00:00Z');if(!Number.isFinite(x.getTime())||x.toISOString().slice(0,10)!==d)throw Error(uiText('تاريخ حركة المديونية غير صالح'));return d;}
function debtStatement95(ledger,from,to){
 from=debtDate95(from);to=debtDate95(to);if(from>to)throw Error(uiText('من تاريخ يجب ألا يتجاوز إلى تاريخ'));
 const seen=new Set(),rank={debt_opening:0,debt_advance:1,repayment:2};
 const moves=(ledger.movements||[]).map(m=>{
  if(!(m.kind in rank))throw Error(uiText('نوع حركة المديونية غير معروف'));
  const key=m.kind+':'+m.id;if(seen.has(key))throw Error(uiText('توجد حركة مديونية مكررة تحتاج مراجعة'));seen.add(key);
  const units=Math.round(Number(m.amount)*1000);if(!Number.isSafeInteger(units)||units<0)throw Error(uiText('مبلغ حركة المديونية غير صالح'));
  if(m.kind==='repayment'&&!/^\d{4}-(0[1-9]|1[0-2])$/.test(m.month||''))throw Error(uiText('شهر خصم المديونية غير صالح'));
  return {...m,effective_date95:debtDate95(m.kind==='repayment'?m.month+'-01':m.date),units95:units};
 }).sort((a,b)=>a.effective_date95.localeCompare(b.effective_date95)||rank[a.kind]-rank[b.kind]||Number(a.id)-Number(b.id));
 let opening=0,debits=0,credits=0,balance=0;const rows=[];
 for(const m of moves){if(m.effective_date95>to)break;const credit=m.kind==='repayment'?m.units95:0,debit=m.kind==='repayment'?0:m.units95;balance+=debit-credit;if(!Number.isSafeInteger(balance))throw Error(uiText('رصيد المديونية يحتاج مراجعة'));
  if(m.effective_date95<from){opening=balance;continue;}debits+=debit;credits+=credit;rows.push({...m,debit95:debit,credit95:credit,balance95:balance});
 }
 return {opening95:opening,debits95:debits,credits95:credits,closing95:balance,rows95:rows,from95:from,to95:to};
}
const debtMoney95=units=>money26(units/1000);
function debtName95(ledger){const staff=(typeof users!=='undefined'?users.find(u=>Number(u.id)===Number(ledger.user_id)):null)||(Number(boot.user.id)===Number(ledger.user_id)?boot.user:null);return staff&&typeof displayName31==='function'?displayName31(staff):ledger.name;}
function debtTable95(headings,rows,type){return '<div class="table-wrap debt-table95 '+(type==='summary'?'debt-summary95':'debt-account95')+'" data-compact32="1"><table><thead><tr>'+headings.map(h=>'<th scope="col">'+esc(uiText(h))+'</th>').join('')+'</tr></thead><tbody>'+rows.map(cells=>'<tr>'+cells.map((value,i)=>'<td data-label="'+esc(uiText(headings[i]))+'">'+value+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>';}
function debtAccountHtml95(ledger,statement,personal,pageRows=rows=>rows){
 const actions=personal?'':'<div class="row-actions"><button type="button" data-debt-back95>'+esc(uiText('قائمة الموظفين'))+'</button>'+(boot.user.role==='admin'?'<button type="button" class="primary" data-debt-add32="'+Number(ledger.user_id)+'">'+esc(uiText('إضافة سلفة'))+'</button>':'')+'</div>';
 const totals=[['المديونية',statement.debits95],['السداد',statement.credits95],['رصيد آخر الفترة',statement.closing95]];
 const openingRow=['',esc(uiText('رصيد أول المدة')),'','',debtMoney95(statement.opening95)];
 const rows=statement.rows95.map(m=>{
  let description=esc(uiText(m.kind==='repayment'?'سداد سلفة':'استلام سلفة'));
  const movementName115=String(m.name95||'').replace(/\s*[—–-]?\s*debt_(?:opening|advance)\b/gi,'').trim();
  if(movementName115&&!['debt_opening','debt_advance','repayment','رصيد أول المدة','رصيد أول الفترة','رصيد افتتاحي','استلام سلفة','سداد سلفة'].includes(movementName115))description+=' — '+(typeof dataTextMarkup162==='function'?dataTextMarkup162('record_text:'+m.kind+':name',movementName115):esc(movementName115));if(m.reference)description+='<br>'+esc(uiText('مرجع الصرف'))+': '+esc(m.reference);if(m.reason)description+='<br>'+(typeof dataTextMarkup162==='function'?dataTextMarkup162('record_text:'+m.kind+':reason',m.reason):esc(m.reason));
  if(m.kind==='repayment'&&m.date)description+='<br><small>'+esc(uiText('تاريخ الاعتماد'))+': '+esc(typeof localDisplay==='function'?localDisplay(m.date):String(m.date))+'</small>';
  if(m.file_id)description+='<br><a target="_blank" rel="noopener" href="file.php?id='+Number(m.file_id)+'">'+esc(uiText('فتح / تنزيل'))+'</a>';
  return [esc(m.kind==='repayment'?uiText('شهر الراتب')+' '+m.month:dateDisplay32(m.effective_date95)),description,m.debit95?debtMoney95(m.debit95):'—',m.credit95?debtMoney95(m.credit95):'—',debtMoney95(m.balance95)];
 });
 return '<div class="debt-account-head95"><strong>'+esc(ledger.code)+' — '+employeeNameMarkup143(ledger.user_id,ledger.name)+'</strong>'+actions+'</div>'+(statement.rows95.length?'<dl class="debt-totals95">'+totals.map(([label,value])=>'<div><dt>'+esc(uiText(label))+'</dt><dd>'+debtMoney95(value)+'</dd></div>').join('')+'</dl>':'')+(statement.closing95<0?'<p class="warning">'+esc(uiText('الرصيد سالب — راجع الحركات والمستندات'))+'</p>':'')+'<p class="muted">'+esc(uiText('خصومات الرواتب تخص شهر الكشف؛ تاريخ الاعتماد للتوثيق فقط.'))+'</p>'+debtTable95(['التاريخ','البيان','مدين','دائن','الرصيد بعد الحركة'],pageRows([openingRow,...rows,...(rows.length?[['',esc(uiText('رصيد آخر الفترة')),'','',debtMoney95(statement.closing95)]]:[])]),'account');
}
function syncDebtDates95(){for(const id of ['debtFrom95','debtTo95']){const input=document.getElementById(id),wrap=input?.closest('.date-field32');if(!wrap)continue;for(const node of wrap.querySelectorAll(':scope > input:not(.date-native32),:scope > button')){node.disabled=input.disabled;if(node.tagName==='INPUT'){node.value=dateDisplay32(input.value||'');node.setCustomValidity('');}}}}
async function debtScreen32(){
 if(typeof resetRecordFilters75==='function')resetRecordFilters75();
 const personal=view==='debt_statement95',epoch=navigationEpoch32,request=++debtRequest95,context=typeof screenIdentity38==='function'?screenIdentity38():String(boot.user.id)+'|'+boot.access_mode;
 if(context!==debtContext95){debtContext95=context;debtPeriod95=null;debtDetail95=0;}if(!debtPeriod95)debtPeriod95={from:'2017-01-01',to:dateNow()};
 $('#page').innerHTML=(personal?head('كشف حساب السلف'):'')+'<div class="toolbar"><label>'+esc(uiText('من تاريخ'))+'<input id="debtFrom95" type="date" required value="'+esc(debtPeriod95.from)+'"></label><label>'+esc(uiText('إلى تاريخ'))+'<input id="debtTo95" type="date" required value="'+esc(debtPeriod95.to)+'"></label></div><section class="card" id="debtData95">'+loadingMarkup()+'</section>';
 const host=$('#debtData95'),from=$('#debtFrom95'),to=$('#debtTo95');from.disabled=to.disabled=true;
 const workspace=personal?null:financeWorkspace190(view,host,()=>render());host._resetDetail190=()=>{debtDetail95=0;};
 if(typeof prepareHeader79==='function')prepareHeader79(view);
 let data={},ledgers=[],loading196=false;
 const render=()=>{
  const selected=workspace?workspace.matching(workspace.selection(ledgers)):personal?ledgers:(typeof employeeRows73==='function'?employeeRows73(ledgers):ledgers);
  if(!personal&&debtDetail95&&!selected.some(l=>Number(l.user_id)===debtDetail95))debtDetail95=0;
  const detail=personal?selected[0]:selected.find(l=>Number(l.user_id)===debtDetail95);
  if(!selected.length)workspace?.page([]);
  if(detail)host.innerHTML=debtAccountHtml95(detail,debtStatement95(detail,debtPeriod95.from,debtPeriod95.to),personal,rows=>workspace?workspace.page(rows,'detail|'+detail.user_id):rows);
  else host.innerHTML=selected.length?debtTable95(['كود الموظف','الموظف','رصيد أول المدة','المديونية','السداد','رصيد آخر الفترة','الإجراءات'],(workspace?workspace.page(selected):selected).map(l=>{
   const s=debtStatement95(l,debtPeriod95.from,debtPeriod95.to);return [esc(l.code),'<span data-employee-id="'+Number(l.user_id)+'">'+employeeNameMarkup143(l.user_id,l.name)+'</span>',debtMoney95(s.opening95),debtMoney95(s.debits95),debtMoney95(s.credits95),'<strong'+(s.closing95<0?' class="warning"':'')+'>'+debtMoney95(s.closing95)+'</strong>','<div class="debt-actions95"><button type="button" data-debt-detail95="'+Number(l.user_id)+'">'+esc(uiText('التفاصيل'))+'</button>'+(boot.user.role==='admin'?'<button type="button" class="primary" data-debt-add32="'+Number(l.user_id)+'">'+esc(uiText('إضافة سلفة'))+'</button>':'')+'</div>'];
  }),'summary'):empty();
  host.querySelectorAll('[data-debt-detail95]').forEach(b=>b.onclick=()=>{debtDetail95=Number(b.dataset.debtDetail95);render();});const back=host.querySelector('[data-debt-back95]');if(back)back.onclick=()=>{debtDetail95=0;render();};
  if(!personal&&boot.user.role==='admin')host.querySelectorAll('[data-debt-add32]').forEach(b=>b.onclick=()=>debtAdvanceModal95(ledgers.find(l=>Number(l.user_id)===Number(b.dataset.debtAdd32)),data.companies||[]));
  if(typeof mountRecordFilters75==='function')mountRecordFilters75();applyInterfaceLanguage();
 };
 const load=async()=>{const ticket=++debtRequest95,page195=workspace?.pageParams()||{};loading196=true;workspace?.busy(true);try{const next=await api(personal?'v28_my_debt':'v28_info',null,personal?{}:{kind:'debt_advance',ledger_only32:1,...page195});if(ticket!==debtRequest95||epoch!==navigationEpoch32||!host.isConnected||(workspace&&!workspace.matchesPage(page195)))return;if(personal&&Number(next.user_id)!==Number(boot.user.id))throw Error(uiText('الحساب خارج نطاق الصلاحية'));data=next;ledgers=personal?[data]:data.debt_ledger||[];workspace?.remote(next);workspace?.people(next.people195||ledgers);from.disabled=to.disabled=false;syncDebtDates95();render();}catch(e){if(!e.stale32&&ticket===debtRequest95&&host.isConnected){if(workspace)workspace.error(e);else throw e;}}finally{if(ticket===debtRequest95&&host.isConnected){loading196=false;workspace?.busy(false);}}};
 workspace?.useRemote(load);
 syncDebtDates95();
 const change=()=>{if(!host.isConnected||epoch!==navigationEpoch32)return;try{const start=debtDate95(from.value),end=debtDate95(to.value);if(start>end)throw Error(uiText('من تاريخ يجب ألا يتجاوز إلى تاريخ'));debtPeriod95={from:start,to:end};if(loading196&&workspace)void workspace.reload();else render();}catch(e){from.value=debtPeriod95.from;to.value=debtPeriod95.to;syncDebtDates95();toast(e.message);}};from.onchange=to.onchange=change;from._periodRefresh98=async()=>{const start=debtDate95(from.value),end=debtDate95(to.value);if(start>end)throw Error(uiText('من تاريخ يجب ألا يتجاوز إلى تاريخ'));debtPeriod95={from:start,to:end};if(workspace)await workspace.reload();else await debtScreen32();};await load();
}
function debtAdvanceModal95(ledger,companies){
 if(boot.user.role!=='admin'||!ledger)return;
 modal('إضافة سلفة','<form id="modalForm"><p>'+esc(ledger.code)+' — '+employeeNameMarkup143(ledger.user_id,ledger.name)+'</p>'+input('name','اسم السجل','','text','required')+'<label>'+esc(uiText('الشركة'))+'<select name="company_code"><option value="">'+esc(uiText('بدون ربط'))+'</option>'+companies.map(c=>'<option value="'+esc(c.code)+'">'+esc(c.name)+'</option>').join('')+'</select></label>'+input('from','تاريخ الصرف',dateNow(),'date','required')+input('to','تاريخ الانتهاء','','date')+input('amount','المبلغ د.ك','','number','required min="0.001" step="0.001"')+input('reference','مرجع الصرف','','text','required')+input('reason','ملاحظات')+'<label>'+esc(uiText('المرفق'))+'<input type="file" name="file"></label>'+saveButton()+'</form>',form=>{
  const body=new FormData(form);body.set('kind','debt_advance');body.set('user_id',ledger.user_id);body.set('values',JSON.stringify({name:form.elements.namedItem('name').value,company_code:form.elements.namedItem('company_code').value,reference:form.elements.namedItem('reference').value,reason:form.elements.namedItem('reason').value}));return api('v28_record_save',body);
 });
}
