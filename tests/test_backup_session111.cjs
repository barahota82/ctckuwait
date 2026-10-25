const fs=require('fs'),vm=require('vm'),assert=require('assert');const {JSDOM}=require(process.env.JSDOM_PATH76);let checks=0;function ok(v){assert(v);checks++;}
(async()=>{
for(const lang of ['ar','en'])for(const outcome of ['success','failed','unconfirmed']){
 const dom=new JSDOM('<div id="app">Old private data</div><dialog id="modal"></dialog><div id="host"><progress></progress><p role="status"></p></div>');dom.window.document.documentElement.lang=lang;
 let resolve,reject,posts=0,reloads=0,aborted=0,stopped=0;const task=new Promise((r,j)=>{resolve=r;reject=j});const timers=new Map();let n=0;
 const c={document:dom.window.document,Uint8Array,AbortController,crypto:{getRandomValues:a=>a.fill(8)},location:{reload:()=>reloads++},navigationEpoch32:0,pendingReads32:new Set([{abort:()=>aborted++}]),stopPortalClock32:()=>stopped++,closeCamera:()=>{},stopLocation:()=>{},setTimeout:(f,ms)=>{timers.set(++n,{f,ms});return n},clearTimeout:id=>timers.delete(id),fetch:async()=>({ok:true,json:async()=>({phase:'complete',done:1,total:1})}),api:()=>{posts++;return task;}};
 vm.createContext(c);vm.runInContext(fs.readFileSync(__dirname+'/../hr/assets/backups.js','utf8'),c);
 const pending=c.backupRestore107({},c.document.querySelector('#host'));ok(vm.runInContext('backupSession111.held',c));ok(aborted===1&&stopped===1&&c.navigationEpoch32===1);ok(posts===1);
 await assert.rejects(()=>c.backupRestore107({},c.document.querySelector('#host')),/already/);checks++;
 const first=[...timers].find(([,v])=>v.ms===250);timers.delete(first[0]);await first[1].f();ok(c.document.querySelector('#app').textContent==='Old private data');
 if(outcome==='failed'){reject(Error('rolled back'));await assert.rejects(()=>pending,/rolled back/);checks++;}
 else{resolve(outcome==='success'?{ok:true}:{ok:false});if(outcome==='unconfirmed'){await assert.rejects(()=>pending);checks++;}else await pending;}
 ok(posts===1&&reloads===0&&timers.size===0);
 if(outcome==='success'){ok(vm.runInContext('backupSession111.held',c));ok(!c.document.querySelector('#app').textContent.includes('Old private data'));ok(c.document.querySelector('h1').textContent===(lang==='ar'?'تم استرجاع النسخة بنجاح':'Backup restored successfully'));c.document.querySelector('#app button').click();ok(reloads===1);}
 else{ok(!vm.runInContext('backupSession111.held',c));ok(c.document.querySelector('#app').textContent==='Old private data');}
 dom.window.close();
}
// The real request gate prevents all background reads/writes, retaining the restore POST.
const c={backupSession111:{held:true}};vm.createContext(c);const source=fs.readFileSync(__dirname+'/../hr/assets/app.js','utf8');const fn=source.slice(source.indexOf('async function apiRequest81('),source.indexOf('\nconst dateNow='));vm.runInContext(fn,c);
for(const [action,data]of [['server_clock',null],['translation_process',{}],['bootstrap',null]]){await assert.rejects(()=>c.apiRequest81(action,data),e=>e.stale32===true);checks++;}
const identity=fs.readFileSync(__dirname+'/../hr/assets/identity.js','utf8');vm.runInContext(identity,c);c.identityExpired32();checks++;
const backupScreen=fs.readFileSync(__dirname+'/../hr/assets/backups.js','utf8').split('function upgradeNotice()')[1];
ok(backupScreen&&!backupScreen.includes('location.reload()'));
console.log('PASS',checks,'restore session hold, read/write isolation, Arabic/English confirmed success, no success from polling or failure, single POST and explicit sign-in');
})().catch(e=>{console.error(e);process.exitCode=1});
