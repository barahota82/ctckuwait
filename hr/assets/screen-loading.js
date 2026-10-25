'use strict';
// One read lifecycle for every route. Business permissions/disabled states stay
// with their renderer; this gate never enables a renderer-disabled control.
const screenLoading197=(()=>{
 let current=null,sequence=0,scheduled=0,observer=null,notice=null;
 const actions=new Map(),regions=new Map();
 const label=(ar,en)=>typeof boot!=='undefined'&&boot?.localization?.language==='en'?en:(typeof uiText==='function'?uiText(ar):ar);
 const frame=callback=>typeof requestAnimationFrame==='function'?requestAnimationFrame(callback):setTimeout(callback,0);
 const page=()=>document.getElementById('page');
 const portal=()=>document.getElementById('portalTabs32');
 const valid=state=>!!state&&state===current&&state.page.isConnected&&state.epoch===navigationEpoch32;
 const busy=state=>state&&(state.navigation||state.reads.size>0);
 function allowed(button){
  return !!button.closest('nav,[role=tablist],.portal-tabs32,.record-categories174')||button.matches('[data-view],[role=tab],[data-read-allow197]')||
   !!button.closest('.read-feedback196,[role=alert],.error')||/retry|refresh|find|search|reload|cancel|close/i.test(button.id+' '+Object.keys(button.dataset).join(' '))||
   /^(إعادة المحاولة|تحديث|بحث عن موظف|إلغاء|إغلاق|Retry|Refresh|Cancel|Close)$/i.test(button.textContent.trim());
 }
 function lock(){
  if(!valid(current)||!busy(current))return;
  for(const root of [current.page,portal()]){
   if(!root)continue;
   for(const button of root.querySelectorAll('button,a.button,input[type=submit]')){
    if(allowed(button)||actions.has(button))continue;
    actions.set(button,button.getAttribute('aria-disabled'));
    button.dataset.readLock197='';button.setAttribute('aria-disabled','true');
   }
   for(const region of root.querySelectorAll('tbody')){
    if(regions.has(region))continue;
    regions.set(region,region.hasAttribute('inert'));region.setAttribute('inert','');
   }
  }
 }
 function unlock(){
  for(const [button,aria]of actions){
   delete button.dataset.readLock197;
   if(button.getAttribute('aria-disabled')==='true'){
    if(aria===null)button.removeAttribute('aria-disabled');else button.setAttribute('aria-disabled',aria);
   }
  }
  actions.clear();
  for(const [region,inert]of regions)if(!inert)region.removeAttribute('inert');
  regions.clear();
 }
 function position(){
  if(!notice||notice.hidden||!current?.page.isConnected)return;
  const workspace=current.page.closest('.workspace'),rect=workspace?.getBoundingClientRect()||current.page.getBoundingClientRect();
  const topbar=workspace?.querySelector('.topbar'),top=Math.max(0,topbar?.getBoundingClientRect().bottom??rect.top);
  notice.style.left=Math.max(0,rect.left)+'px';notice.style.width=Math.max(0,Math.min(innerWidth,rect.right)-Math.max(0,rect.left))+'px';notice.style.top=top+'px';
  notice.style.bottom=current.initial?'0':'auto';
 }
 function surface(){
  if(notice?.isConnected)return notice;
  notice=document.createElement('div');notice.className='screen-progress197';notice.hidden=true;notice.setAttribute('role','status');notice.setAttribute('aria-live','polite');notice.setAttribute('aria-atomic','true');
  const text=document.createElement('span');text.className='screen-progress-text197';notice.append(text);document.body.append(notice);return notice;
 }
 function show(state){
  if(!valid(state))return;
  const node=surface();node.classList.toggle('screen-initial197',state.initial);node.hidden=false;
  node.firstElementChild.textContent=label('جارٍ التحميل…','Loading…');
  state.page.dataset.readBusy197='';state.page.setAttribute('aria-busy','true');
  if(state.initial){state.page.dataset.readInitial197='';state.page.setAttribute('inert','');portal()?.setAttribute('data-read-initial197','');}
  position();lock();
 }
 function clean(state){
  if(!state)return;
  delete state.page.dataset.readBusy197;delete state.page.dataset.readInitial197;
  if(state.initial&&!state.inert)state.page.removeAttribute('inert');
  if(state.page.getAttribute('aria-busy')==='true'){
   if(state.aria===null)state.page.removeAttribute('aria-busy');else state.page.setAttribute('aria-busy',state.aria);
  }
  portal()?.removeAttribute('data-read-initial197');unlock();if(notice)notice.hidden=true;
 }
 function settle(state){
  const revision=++scheduled;
  // Keep the gate through promise continuations, renderer finally blocks and
  // chrome observers. A second read started in that interval cancels release.
  frame(()=>frame(()=>{
   if(revision!==scheduled||!valid(state)||busy(state))return;
   clean(state);state.initial=false;state.ready=true;
   state.page.dispatchEvent(new CustomEvent('screenready197',{bubbles:true,detail:{key:state.key,epoch:state.epoch}}));
  }));
 }
 function make(key,epoch,initial){
  const root=page();if(!root)return null;
  return {id:++sequence,key,epoch,page:root,initial,navigation:false,reads:new Set(),ready:false,inert:root.hasAttribute('inert'),aria:root.getAttribute('aria-busy')};
 }
 function begin(key,epoch,retain){
  ++scheduled;clean(current);current=make(key,epoch,!retain);if(!current)return;
  current.navigation=true;show(current);
 }
 function end(epoch){
  if(!current||current.epoch!==epoch||!valid(current))return;
  current.navigation=false;settle(current);
 }
 function read(action,data,params,locale){
  if(data!==null||locale||action==='bootstrap'||action==='server_clock'||params?._background197||params?.reminder196)return null;
  const root=page();if(!root||typeof boot==='undefined'||!boot?.user)return null;
  if(!current||!valid(current)){clean(current);current=make(typeof view==='string'?view:'',navigationEpoch32,false);}
  if(!current)return null;
  const token={state:current};current.reads.add(token);++scheduled;show(current);return token;
 }
 function done(token){
  if(!token)return;token.state.reads.delete(token);
  if(valid(token.state))settle(token.state);
 }
 function block(event){
  const button=event.target?.closest?.('[data-read-lock197]');
  if(!button||!busy(current)||!valid(current))return;
  if(event.type==='keydown'&&!['Enter',' ','Spacebar'].includes(event.key))return;
  event.preventDefault();event.stopImmediatePropagation();
 }
 document.addEventListener('click',block,true);document.addEventListener('keydown',block,true);
 document.addEventListener('submit',event=>{if(busy(current)&&valid(current)&&event.target.closest('#page,#portalTabs32')&&!event.target.matches('[data-read-allow197]')){event.preventDefault();event.stopImmediatePropagation();}},true);
 if(typeof MutationObserver!=='undefined'){
  observer=new MutationObserver(()=>{if(current&&!current.page.isConnected){clean(current);current=null;}else if(busy(current))lock();});
  observer.observe(document.documentElement,{childList:true,subtree:true});
 }
 addEventListener('resize',position,{passive:true});addEventListener('scroll',position,{passive:true,capture:true});
 function cancel(){++scheduled;clean(current);current=null;}
 return {begin,end,read,done,cancel,get busy(){return !!busy(current);}};
})();
