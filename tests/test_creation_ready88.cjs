const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');const {JSDOM}=require(process.env.JSDOM_PATH76),root=path.resolve(__dirname,'..');let n=0;const ok=x=>{assert(x);n++;};const src=fs.readFileSync(root+'/hr/assets/interface-controls.js','utf8');
for(const dir of ['rtl','ltr'])for(const state of ['ready','waiting','denied']){
 const dom=new JSDOM('<div id="portalTabs32"></div><div id="page"></div><div id="modalContent"></div>'),d=dom.window.document;d.documentElement.dir=dir;let frames=[],clicks=0;
 const c={document:d,requestAnimationFrame:fn=>frames.push(fn),queueMicrotask};vm.createContext(c);vm.runInContext(fs.readFileSync(root+'/hr/assets/create-ready.js','utf8'),c);vm.runInContext(src.slice(src.indexOf('const creationIds70='),src.indexOf('// Screen-specific records:')),c);
 const ids=vm.runInContext('[...creationIds70]',c),buttons=[];
 ids.forEach((id,i)=>{const b=d.createElement('button');b.id=id;b.textContent=dir==='rtl'?'+ إضافة':'Add';b.disabled=state!=='ready';b.onclick=()=>clicks++;d.querySelector(['#page','#portalTabs32','#modalContent'][i%3]).append(b);buttons.push(b);});
 const save=d.createElement('button');save.textContent='Save';d.querySelector('#page').append(save);
 c.finishActions32();ok(!save.hasAttribute('inert'));ok(!save.hasAttribute('data-create-wait88'));
 for(const b of buttons){ok(b.hasAttribute('data-create-wait88'));ok(b.hasAttribute('inert'));ok(b.getAttribute('aria-disabled')==='true');ok(b.getAttribute('aria-busy')==='true');b.dispatchEvent(new dom.window.MouseEvent('click',{bubbles:true,cancelable:true}));}
 ok(clicks===0);let batch=frames;frames=[];batch.forEach(fn=>fn());for(const b of buttons)ok(b.hasAttribute('data-create-wait88')); // First frame remains visibly inactive.
 if(state==='waiting')buttons.forEach(b=>b.disabled=false);
 // A permission decision arriving during the gate must survive its release.
 const late=buttons.at(-1);late.disabled=true;
 batch=frames;frames=[];batch.forEach(fn=>fn());for(const b of buttons){ok(!b.hasAttribute('data-create-wait88'));ok(!b.hasAttribute('inert'));ok(!b.hasAttribute('aria-disabled'));ok(b.disabled===(state==='denied'||b===late));b.click();}
 ok(clicks===(state==='denied'?0:buttons.length-1));ok(late.disabled);
 c.finishActions32();ok(frames.length===0); // Repeated enhancement never restarts an existing button.
 // New nodes get their own initial state; detached old nodes cannot enable them.
 const replacement=buttons[0].cloneNode(true);buttons[0].replaceWith(replacement);c.finishActions32();ok(replacement.hasAttribute('data-create-wait88'));ok(replacement.hasAttribute('inert'));
}
// Preserve pre-existing restrictions and support links and keyboard capture.
{
 const dom=new JSDOM('<div id="page"><a href="#saved" inert aria-disabled="true">Add</a></div>'),d=dom.window.document;let frames=[];const c={document:d,requestAnimationFrame:fn=>frames.push(fn),queueMicrotask};vm.createContext(c);vm.runInContext(fs.readFileSync(root+'/hr/assets/create-ready.js','utf8'),c);const a=d.querySelector('a');c.prepareCreation88(a);const e=new dom.window.KeyboardEvent('keydown',{key:'Enter',bubbles:true,cancelable:true});a.dispatchEvent(e);ok(e.defaultPrevented);let batch=frames;frames=[];batch.forEach(fn=>fn());batch=frames;frames=[];batch.forEach(fn=>fn());ok(a.hasAttribute('inert'));ok(a.getAttribute('aria-disabled')==='true');
}
const index=fs.readFileSync(root+'/hr/index.php','utf8'),css=fs.readFileSync(root+'/hr/assets/style.css','utf8');ok(index.indexOf('create-ready.js')<index.indexOf('assets/app.js'));ok(css.includes('.create-action32:is(:disabled,[data-create-wait88])'));ok(css.includes('opacity:.55!important'));
console.log('PASS',n,'all registered creation actions across page/header/modal, first paint, keyboard/click blocking, permission preservation, delayed readiness, idempotence and replacement');
