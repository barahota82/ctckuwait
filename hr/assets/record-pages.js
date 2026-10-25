'use strict';
// A tab owns its page/size. Remote callers provide totals from the authorized server result.
const recordPagerObservers189=new Map();
function recordPageWindow189(rows,state){
 const size=['50','100','200','all'].includes(String(state.size))?String(state.size):'50';
 state.size=size;const count=size==='all'?Math.max(1,rows.length):Number(size),pages=Math.max(1,Math.ceil(rows.length/count));
 state.page=Math.max(0,Math.min(pages-1,Number.isInteger(state.page)?state.page:0));
 const start=state.page*count,end=Math.min(start+count,rows.length);
 return {rows:rows.slice(start,end),total:rows.length,pages,start:rows.length?start+1:0,end};
}
function recordPager189(host,render,tabs=new Map()){
 const footer=document.createElement('footer');footer.className='employee-editor-foot134 record-pager189';
 footer.innerHTML='<div class="employee-editor-save134"><span data-no-translate data-text-key138="طريقة العرض">'+esc(uiText('طريقة العرض'))+'</span><div class="record-page-links190" role="group" aria-label="'+esc(uiText('طريقة العرض'))+'">'+['50','200','100','all'].map(n=>'<button type="button" data-page-size190="'+n+'"'+(n==='all'?' data-no-translate data-text-key138="الكل"':'')+'>'+esc(n==='all'?uiText('الكل'):n)+'</button>').join('<span aria-hidden="true">|</span>')+'</div></div><div class="employee-editor-movement134"><span data-page-range189 role="status" aria-live="polite"></span><nav class="employee-nav48" aria-label="'+esc(uiText('التنقل بين الصفحات'))+'">'+[['first','الصفحة الأولى'],['prev','الصفحة السابقة'],['next','الصفحة التالية'],['last','الصفحة الأخيرة']].map(([step,title])=>'<button type="button" data-page-step189="'+step+'" aria-label="'+esc(uiText(title))+'" title="'+esc(uiText(title))+'">'+employeeNavIcon48(step)+'</button>').join('')+'</nav></div>';
 host.querySelector('[data-record-footer187]')?.remove();host.append(footer);
 for(const [node,observer]of recordPagerObservers189)if(!node.isConnected){observer.disconnect();recordPagerObservers189.delete(node);}
 const quickNav=document.querySelector('.mobile-bottom');
 if(quickNav){const observer=new ResizeObserver(()=>{if(!footer.isConnected){observer.disconnect();recordPagerObservers189.delete(footer);return;}footer.style.bottom=quickNav.getBoundingClientRect().height+'px';});recordPagerObservers189.set(footer,observer);observer.observe(quickNav);}
 let active=null;
 footer.querySelectorAll('[data-page-size190]').forEach(button=>button.onclick=()=>{if(!active)return;active.size=button.dataset.pageSize190;active.page=0;render();});
 footer.querySelectorAll('[data-page-step189]').forEach(button=>button.onclick=()=>{if(!active||button.disabled)return;active.page=employeeNavIndex48(active.pages,active.page,button.dataset.pageStep189);render();});
 const draw=(rows,tab,filters,remote=null)=>{
  if(!tabs.has(tab))tabs.set(tab,{size:'50',page:0,filters});active=tabs.get(tab);
  if(active.filters!==filters){active.page=0;active.filters=filters;}
  const window=remote?{...remote,rows}:recordPageWindow189(rows,active);if(remote){active.page=remote.page;active.size=remote.size;}active.pages=window.pages;
  footer.querySelectorAll('[data-page-size190]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.pageSize190===active.size)));
  const range=footer.querySelector('[data-page-range189]');range.innerHTML='<span data-no-translate data-text-key138="السجلات المعروضة">'+esc(uiText('السجلات المعروضة'))+'</span> <bdi>'+window.start+'–'+window.end+' / '+window.total+'</bdi>';
  footer.querySelectorAll('[data-page-step189]').forEach(button=>{const n=employeeNavIndex48(window.pages,active.page,button.dataset.pageStep189);button.disabled=n<0||n>=window.pages||n===active.page;});
  return window.rows;
 };
 draw.params=(tab,filters)=>{if(!tabs.has(tab))tabs.set(tab,{size:'50',page:0,filters});const state=tabs.get(tab);if(state.filters!==filters){state.page=0;state.filters=filters;}active=state;return {size:state.size,page:state.page};};
 draw.busy=value=>{footer.setAttribute('aria-busy',String(value));footer.querySelectorAll('[data-page-size190]').forEach(button=>button.disabled=value);footer.querySelectorAll('[data-page-step189]').forEach(button=>{const n=active?employeeNavIndex48(active.pages||1,active.page,button.dataset.pageStep189):0;button.disabled=value||!active||n<0||n>=(active.pages||1)||n===active.page;});if(value&&!active)footer.querySelector('[data-page-range189]').textContent='…';};
 return draw;
}
