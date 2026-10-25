'use strict';
// Show the shared inactive state through the first paint. Renderer-owned disabled
// states remain authoritative; this gate never enables a native disabled control.
const creationStates88=new WeakMap();
function prepareCreation88(button){
 if(creationStates88.has(button))return;
 const state={inert:button.hasAttribute('inert'),aria:button.getAttribute('aria-disabled'),busy:button.getAttribute('aria-busy')};
 creationStates88.set(button,state);button.dataset.createWait88='';button.setAttribute('inert','');button.setAttribute('aria-disabled','true');button.setAttribute('aria-busy','true');
 let painted=false,watcher=null;
 const release=()=>{
  if(!button.isConnected){watcher?.disconnect();return;}
  const page=document.getElementById('page');
  if(!painted||(!button.closest('#modalContent')&&(page?.hasAttribute('data-screen-loading94')||page?.hasAttribute('data-screen-failed94')||page?.querySelector('.loading'))))return;
  watcher?.disconnect();
  delete button.dataset.createWait88;
  if(!state.inert)button.removeAttribute('inert');
  if(state.aria===null)button.removeAttribute('aria-disabled');else button.setAttribute('aria-disabled',state.aria);
  if(button.disabled)button.setAttribute('aria-busy','true');else if(state.busy===null||state.busy==='true')button.removeAttribute('aria-busy');else button.setAttribute('aria-busy',state.busy);
 };
 if(typeof MutationObserver!=='undefined'){watcher=new MutationObserver(release);watcher.observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['data-screen-loading94','data-screen-failed94']});}
 const afterPaint=()=>{painted=true;release();};
 if(typeof requestAnimationFrame==='function')requestAnimationFrame(()=>requestAnimationFrame(afterPaint));else queueMicrotask(afterPaint);
}
function blockCreation88(event){
 const target=event.target?.closest?.('[data-create-wait88]');if(!target)return;
 if(event.type==='keydown'&&!['Enter',' ','Spacebar'].includes(event.key))return;
 event.preventDefault();event.stopImmediatePropagation();
}
document.addEventListener('click',blockCreation88,true);
document.addEventListener('keydown',blockCreation88,true);
