'use strict';
// Shared mobile navigation only: never action toolbars or permission controls.
function tabTouch142(){return typeof mobileInteraction142==='function'?mobileInteraction142():window.matchMedia('(max-width:760px)').matches;}
const mobileTabSelector33='.portal-tabs32,.access-tabs30,.profile-tabs29,[role="tablist"]';
const mobileTabPositions33=new Map(),mobileTabBound33=new WeakSet();
let mobileTabFrame33=0;
function mobileTabKey33(strip){
 const keys=[...strip.querySelectorAll(':scope > button')].map(b=>b.dataset.view||b.dataset.tab30||b.dataset.tab29||b.id).join('|');
 const profile=strip.classList.contains('profile-tabs29')?document.querySelector('[data-employee-context64]')?.getAttribute('data-employee-context64')||document.querySelector('#page h1')?.textContent||'':'';
 return profile+'::'+keys;
}
function mobileTabReveal33(strip){
 const active=strip.querySelector(':scope > [aria-current="page"],:scope > [aria-selected="true"],:scope > button.primary');
 if(!active||active.hidden)return;
 const outer=strip.getBoundingClientRect(),inner=active.getBoundingClientRect();
 const delta=inner.left<outer.left?inner.left-outer.left:inner.right>outer.right?inner.right-outer.right:0;
 if(delta)strip.scrollBy({left:delta,behavior:'auto'});
}
const tabScrollStates53=new WeakMap(),observedTabStrips53=new Set();
const tabScrollResize53=new ResizeObserver(()=>mobileTabsSchedule33());
function tabEdges53(outer,items){return {left:!items.length||Math.min(...items.map(r=>r.left))>=outer.left-1,right:!items.length||Math.max(...items.map(r=>r.right))<=outer.right+1};}
function tabScrollUpdate53(strip){
 const state=tabScrollStates53.get(strip);if(!state)return;
 const desktop=!tabTouch142(),overflow=strip.scrollWidth>strip.clientWidth+1;
 state.wrapper.classList.toggle('tabs-overflow53',desktop&&overflow);
 state.left.hidden=state.right.hidden=!(desktop&&overflow);
 const edges=tabEdges53(strip.getBoundingClientRect(),[...strip.querySelectorAll(':scope > button')].filter(b=>!b.hidden).map(b=>b.getBoundingClientRect()));
 state.left.disabled=edges.left;state.right.disabled=edges.right;
}
function tabScrollPrepare53(strip){
 let state=tabScrollStates53.get(strip);if(state)return state;
 const parent=strip.parentElement,wrapper=document.createElement('div');wrapper.className='tab-scroll53';if(parent?.id==='page')wrapper.classList.add('tab-scroll-page53');
 const arrow=(direction,label)=>{const b=document.createElement('button');b.type='button';b.className='tab-arrow53';b.setAttribute('aria-label',uiText(label));b.title=uiText(label);b.innerHTML='<svg viewBox="0 0 20 20" aria-hidden="true"><path d="'+(direction<0?'M12 4l-6 6 6 6':'M8 4l6 6-6 6')+'"/></svg>';b.onclick=()=>strip.scrollBy({left:direction*Math.max(100,Math.round(strip.clientWidth*.65)),behavior:'smooth'});return b;};
 const left=arrow(-1,'تمرير التبويبات لليسار'),right=arrow(1,'تمرير التبويبات لليمين');
 strip.before(wrapper);strip.classList.add('tabs-strip53');wrapper.append(left,strip,right);
 state={wrapper,left,right,active:null,mobile:null};tabScrollStates53.set(strip,state);
 strip.addEventListener('scroll',()=>tabScrollUpdate53(strip),{passive:true});tabScrollResize53.observe(strip);observedTabStrips53.add(strip);
 strip.addEventListener('keydown',e=>{if(e.target===strip&&['ArrowLeft','ArrowRight'].includes(e.key)){e.preventDefault();strip.scrollBy({left:e.key==='ArrowLeft'?-120:120,behavior:'smooth'});}});
 return state;
}
function mobileTabsRefresh33(){
 const mobile=tabTouch142();
 for(const strip of observedTabStrips53)if(!strip.isConnected){tabScrollResize53.unobserve(strip);observedTabStrips53.delete(strip);}
 document.querySelectorAll(mobileTabSelector33).forEach(strip=>{
  const state=tabScrollPrepare53(strip);
  if(!mobileTabBound33.has(strip)){
   mobileTabBound33.add(strip);
   const key=mobileTabKey33(strip);
   strip.addEventListener('scroll',()=>{if(tabTouch142())mobileTabPositions33.set(key,strip.scrollLeft);},{passive:true});
   if(mobile&&mobileTabPositions33.has(key))strip.scrollLeft=mobileTabPositions33.get(key);
  }
  strip.setAttribute('data-swipe-tabs50','');strip.setAttribute('tabindex','0');
  if(mobile)strip.setAttribute('aria-description',uiText('اسحب يمينًا أو يسارًا لعرض بقية التبويبات'));else strip.removeAttribute('aria-description');
  tabScrollUpdate53(strip);
  const active=strip.querySelector(':scope > [aria-current="page"],:scope > [aria-selected="true"],:scope > button.primary');
  if(state.active!==active||state.mobile!==mobile){mobileTabReveal33(strip);state.active=active;state.mobile=mobile;}
 });
}
function mobileTabsSchedule33(){
 if(mobileTabFrame33)return;
 mobileTabFrame33=requestAnimationFrame(()=>{mobileTabFrame33=0;mobileTabsRefresh33();});
}
document.addEventListener('click',event=>{
 const strip=event.target.closest?.(mobileTabSelector33);
 if(strip&&tabTouch142())mobileTabPositions33.set(mobileTabKey33(strip),strip.scrollLeft);
},true);
const mobileTabObserver33=new MutationObserver(mutations=>{
 if(mutations.some(m=>m.target.closest?.(mobileTabSelector33)||(m.type==='childList'&&[...m.addedNodes,...m.removedNodes].some(n=>n.matches?.(mobileTabSelector33)||n.querySelector?.(mobileTabSelector33)))))mobileTabsSchedule33();
});
mobileTabObserver33.observe(document.querySelector('#app')||document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['aria-current','aria-selected','class']});
window.addEventListener('resize',mobileTabsSchedule33,{passive:true});
mobileTabsSchedule33();
