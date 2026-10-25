const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');const {JSDOM}=require(process.env.JSDOM_PATH76);const root=path.resolve(__dirname,'..');let n=0;const ok=x=>{assert(x);n++;};
(async()=>{
 const dom=new JSDOM('<div id="portalTabs32"><button id="addAllowance">بدلات بتاريخ سريان</button><button id="fridayRate">قيمة الجمعة بتاريخ سريان</button></div><div id="page"><div class="loading">Loading</div></div><div id="modalContent"></div>'),d=dom.window.document;let frames=[];const c={document:d,MutationObserver:dom.window.MutationObserver,requestAnimationFrame:fn=>frames.push(fn),queueMicrotask};vm.createContext(c);vm.runInContext(fs.readFileSync(root+'/hr/assets/create-ready.js','utf8'),c);const src=fs.readFileSync(root+'/hr/assets/interface-controls.js','utf8');vm.runInContext(src.slice(src.indexOf('const creationIds70='),src.indexOf('// Screen-specific records:')),c);
 c.finishActions32();let batch=frames;frames=[];batch.forEach(fn=>fn());batch=frames;frames=[];batch.forEach(fn=>fn());
 for(const b of d.querySelectorAll('button')){ok(b.classList.contains('create-action32'));ok(b.hasAttribute('inert'));ok(b.hasAttribute('data-create-wait88'));}
 d.querySelector('#page').innerHTML='<p>Ready</p>';await Promise.resolve();for(const b of d.querySelectorAll('button')){ok(!b.hasAttribute('inert'));ok(!b.hasAttribute('data-create-wait88'));}
 const files=fs.readdirSync(root+'/hr/assets').filter(f=>f.endsWith('.js'));const ids=vm.runInContext('[...creationIds70]',c);const inventory=ids.map(id=>({id,files:files.filter(f=>fs.readFileSync(root+'/hr/assets/'+f,'utf8').includes(id))}));
 for(const row of inventory){ok(row.files.length>0);const b=d.createElement('button');b.id=row.id;ok(c.creationButton70(b,'Untranslated label'));b.removeAttribute('id');b.dataset.pendingAction79=row.id;ok(c.creationButton70(b,'Untranslated label'));delete b.dataset.pendingAction79;b.dataset.earlyAction61=row.id;ok(c.creationButton70(b,'Untranslated label'));}
 // Check every literal addition label found in the app, not only known IDs.
 let candidates=0;for(const file of files){const source=fs.readFileSync(root+'/hr/assets/'+file,'utf8');for(const match of source.matchAll(/<button\b([^>]+)>([^<]{0,350})<\/button>/g)){
  const label=match[2];if(!/إضافة|إنشاء|تسجيل (?:حركة|سلفة|دفعة|طلب|سجل|إجازة)|تعيين \/ إعادة تعيين|مجموعة جديدة|تجهيز كشف/.test(label))continue;
  const b=d.createElement('button');b.innerHTML=label;for(const a of match[1].matchAll(/([\w-]+)="([^"<>]*)"/g))b.setAttribute(a[1],a[2]);if(b.id==='v26Import')continue; // Disabled legacy import, not a live card action.
  ok(c.creationButton70(b,b.textContent.replace(/^\s*[+＋]\s*/,'')));candidates++;
 }}
 ok(candidates>25);ok(ids.includes('addAllowance')&&ids.includes('fridayRate')&&ids.includes('returnCompanySales36'));
 console.log('PASS',n,'inventory coverage, literal addition actions, explicit identities and loading-to-ready lifecycle;',ids.length,'registered IDs;',candidates,'literal candidates');
})().catch(e=>{console.error(e);process.exitCode=1;});
