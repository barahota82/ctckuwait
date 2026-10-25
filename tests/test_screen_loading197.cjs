const {JSDOM}=require('jsdom'),fs=require('fs'),path=require('path'),assert=require('assert');
const asset=path.resolve(__dirname,'../hr/assets');let checks=0;
const check=(value,message)=>{assert(value,message);checks++;};
const tick=()=>new Promise(r=>setTimeout(r,15));
function fixture(){
 const dom=new JSDOM('<div class="workspace"><header class="topbar"></header><div id="portalTabs32"><button id="topSave">Save</button></div><main id="page"><input id="search"><button id="save">Save</button><button id="disabled" disabled>Unavailable</button><nav><button id="tab">Tab</button></nav><table><tbody><tr><td><button id="detail">Details</button></td></tr></tbody></table></main></div>',{runScripts:'outside-only',url:'https://example.invalid/hr/'}),w=dom.window;
 Object.assign(w,{boot:{user:{id:1},localization:{language:'ar'}},view:'test',navigationEpoch32:1,uiText:x=>x,requestAnimationFrame:cb=>setTimeout(cb,0)});
 w.eval(fs.readFileSync(path.join(asset,'screen-loading.js'),'utf8')+'\nwindow.loader=screenLoading197;');return {dom,w,l:w.loader,p:w.document.getElementById('page')};
}
(async()=>{
 const {dom,w,l,p}=fixture();const $=id=>w.document.getElementById(id);let clicked=0; $('save').onclick=()=>clicked++;
 l.begin('any-route',1,false);check(p.hasAttribute('data-read-initial197'),'all initial routes gated');check(p.hasAttribute('inert'),'initial page inaccessible while hidden');check($('portalTabs32').hasAttribute('data-read-initial197'),'header controls gated with page');
 const a=l.read('info',null,{},false),b=l.read('list',null,{},false);l.end(1);l.done(a);await tick();check(p.hasAttribute('data-read-initial197'),'parallel read still pending');l.done(b);await tick();check(!p.hasAttribute('data-read-initial197')&&!p.hasAttribute('inert'),'complete page reveals together');check(!w.document.querySelector('[data-read-lock197]'),'all temporary action locks released');check($('disabled').disabled,'business-disabled button never enabled');
 const c=l.read('query',null,{},false);check(!p.hasAttribute('data-read-initial197'),'refresh keeps screen visible');check(!$('search').disabled&&!$('search').closest('[inert]'),'search remains editable');check(!$('tab').hasAttribute('data-read-lock197'),'tabs can supersede request');check($('detail').closest('[inert]'),'old row cannot open during refresh');$('save').click();check(clicked===0,'save blocked during foreground read');check($('topSave').hasAttribute('data-read-lock197'),'portal actions share the same gate');
 const fresh=w.document.createElement('button');fresh.textContent='New action';p.append(fresh);await tick();check(fresh.hasAttribute('data-read-lock197'),'newly rendered actions also blocked');
 l.done(c);const d=l.read('next sequential read',null,{},false);await tick();check(l.busy&&$('save').hasAttribute('data-read-lock197'),'sequential reads have no premature unlock');l.done(d);await tick();$('save').click();check(clicked===1,'controls usable after read');
 for(const [action,data,params,locale]of [['save',{},{}],['server_clock',null,{}],['permissions182',null,{reminder196:1}],['permission_get182',null,{_background197:true}],['anything',null,{},true]])check(l.read(action,data,params,locale)===null,'background/write does not start read gate: '+action);
 check(!l.busy,'background does not lock page');
 const old=l.read('old',null,{},false);w.navigationEpoch32=2;l.begin('next-route',2,false);const next=l.read('next',null,{},false);l.end(2);l.done(old);await tick();check(p.hasAttribute('data-read-initial197'),'old completion cannot release new route');l.done(next);await tick();check(!l.busy&&!p.hasAttribute('data-read-initial197'),'new route settles');
 l.begin('retained-family',2,true);check(!p.hasAttribute('data-read-initial197'),'retained tab shell stays visible');l.end(2);await tick();
 l.begin('failed-route',2,false);p.innerHTML='<div role="alert">Failed</div><button data-view="failed-route">إعادة المحاولة</button>';l.end(2);await tick();check(!p.hasAttribute('data-read-initial197')&&!p.querySelector('button').hasAttribute('data-read-lock197'),'error and retry remain usable');
 const pending=l.read('info',null,{},false);p.remove();await tick();check(w.document.querySelector('.screen-progress197').hidden,'logout detaches loading surface');l.done(pending);
 dom.window.close();console.log(JSON.stringify({checks,result:'PASS'}));
})().catch(e=>{console.error(e);process.exit(1)});
