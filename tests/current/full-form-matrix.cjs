const fs=require('fs'),path=require('path'),assert=require('assert'),vm=require('vm');
const {JSDOM,VirtualConsole}=require('jsdom');
const source=path.resolve(__dirname,'../../hr'),preview=path.resolve(__dirname,'../fixtures/current-preview');
const html=fs.readFileSync(path.join(preview,'index.html'),'utf8');
const scripts=[...html.matchAll(/<script[^>]+src="([^"]+)"/g)].map(m=>m[1].split('?')[0]);
const output=path.resolve(__dirname,'../../.qa-output');fs.mkdirSync(output,{recursive:true});
const errors=[],coverage=[],vc=new VirtualConsole();
vc.on('jsdomError',e=>{if(!/Not implemented/.test(e.message))errors.push(e.message)});
const dom=new JSDOM(html.replace(/<script[\s\S]*?<\/script>/g,''),{url:'https://local-demo.invalid/hr/',runScripts:'outside-only',pretendToBeVisual:true,virtualConsole:vc});
const w=dom.window,language=process.env.AUDIT_LANG||'ar',mobile=process.env.AUDIT_MOBILE==='1';
Object.assign(w,{structuredClone,fetch:()=>{throw Error('Unexpected external fetch')},ResizeObserver:class{observe(){}unobserve(){}disconnect(){}},IntersectionObserver:class{observe(){}unobserve(){}disconnect(){}},matchMedia:()=>({matches:mobile,addEventListener(){},removeEventListener(){}}),scrollTo(){},confirm:()=>true,alert(){},CSS:{escape:s=>s}});
w.URL.createObjectURL=()=> 'blob:demo';w.URL.revokeObjectURL=()=>{};w.HTMLElement.prototype.scrollIntoView=function(){};
w.HTMLDialogElement.prototype.showModal=function(){this.open=true;(this.querySelector('[autofocus]')||this.querySelector('button,input,select,textarea'))?.focus();};
w.HTMLDialogElement.prototype.close=function(){this.open=false;this.dispatchEvent(new w.Event('close'));};
for(const name of ['month','reason','confirmed'])Object.defineProperty(w.HTMLFormElement.prototype,name,{configurable:true,get(){return this.elements.namedItem(name)}});
const wait=ms=>new Promise(r=>setTimeout(r,ms));let checks=0;const ok=(v,message)=>{assert(v,message);checks++};
const close=()=>{w.document.getElementById('closeModal').click();ok(!w.document.getElementById('modal').open,'dialog closes');};
async function settle(){await wait(60);for(let i=0;i<100&&(w.qa.busy()||w.document.querySelector('[data-create-wait88]'));i++)await wait(15);ok(!w.qa.busy(),'loading completes');}
async function checkSearch(module){
 const find=w.document.querySelector('#recordFind173,#requestFind185');ok(find&&!find.disabled,module+' ready search');find.click();await settle();
 const input=w.document.querySelector('#modalContent input[type="search"]');ok(input,'shared search input');
 const fields=[...w.document.querySelectorAll('#modalContent input[type="search"]')];ok(fields.length===1,'one search input');
 input.value='UNMATCHED-AUDIT-KEY';input.dispatchEvent(new w.Event('input'));
 ok(w.document.querySelectorAll('#modalContent input[type="radio"]').length===0,'unmatched query hides results');
 close();coverage.push({module,kind:'search',pass:true});
}
(async()=>{
 for(const file of scripts){let candidate=path.join(source,file),code=fs.readFileSync(fs.existsSync(candidate)?candidate:path.join(preview,file),'utf8');
  if(file==='demo-transport.js')code=code.replace("let language='ar',counter=50000,actor182=2,mode183='team';",`let language='${language}',counter=50000,actor182=1,mode183='team';`);
  vm.runInContext(code,dom.getInternalVMContext());
 }
 w.eval('window.qa={navigate,busy:()=>screenLoading197.busy,adminRequests:()=>{requestMode50="admin";}};');await settle();
 for(const module of ['document','custody','discipline','evaluation','request']){
  if(module==='request')w.qa.adminRequests();await w.qa.navigate(module);await settle();
  if(module==='evaluation'){const select=w.document.getElementById('evaluationSource182');ok(select,'evaluation source selector');select.value='manager';select.dispatchEvent(new w.Event('change'));await settle();}
  await checkSearch(module);
  const selector=module==='request'?'[data-request-category185]':'[data-record-category174]';
  const categories=[...w.document.querySelectorAll(selector)].map(b=>({key:module==='request'?b.dataset.requestCategory185:b.dataset.recordCategory174,label:b.textContent})).filter(x=>x.key!=='all');ok(categories.length>0,module+' categories exist');
  for(const {key} of categories){
   const tab=[...w.document.querySelectorAll(selector)].find(b=>(module==='request'?b.dataset.requestCategory185:b.dataset.recordCategory174)===key);tab.click();await settle();
   ok(tab.getAttribute('aria-pressed')==='true',module+'/'+key+' selected tab');
   const size=w.document.querySelector('[data-page-size190="100"]');ok(size&&!size.disabled,'page-size control ready');size.click();await settle();ok(w.document.querySelector('[data-page-size190="100"]').getAttribute('aria-pressed')==='true','page size selected');
   ok(tab.getAttribute('aria-pressed')==='true','page size preserves selected tab');
   const add=w.document.getElementById('addRecord');ok(add&&!add.hidden&&!add.disabled,'create control ready');add.click();await settle();
   const form=w.document.getElementById('modalForm');ok(w.document.getElementById('modal').open&&form,'form opens');
   ok(form.elements.record_category49.value===key,'form preserves current category');ok(form.elements.user_id,'employee selector present');ok(form.elements.file,'attachment present');
   const names=[...form.querySelectorAll('input[name],textarea[name],select[name]')].map(x=>x.name);
   if(module==='document'&&key==='certificates'){ok(form.elements.date_to.disabled&&form.elements.date_to.closest('label').hidden,'certificate expiry disabled and hidden');ok(!form.elements.date_from.disabled,'certificate date enabled');}
   if(module==='custody'){ok(form.elements.handover&&form.elements.return_notes,'separate handover and return notes');ok(form.elements.handover.rows===form.elements.return_notes.rows,'consistent note controls; visual height is governed by shared CSS');}
   if(module==='request'&&key==='loan'){ok(form.elements.amount&&!form.elements.date_from&&!form.elements.date_to,'loan has amount without irrelevant period');}
   if(module==='evaluation'&&key==='improvement'){ok(form.elements.related_record49?.tagName==='SELECT','improvement chooses original evaluation automatically');}
   coverage.push({module,category:key,kind:'form',fields:[...new Set(names)],pass:true});close();
   const detail=w.document.querySelector('[data-record173],[data-detail185]');
   if(detail){detail.click();await settle();ok(w.document.getElementById('modal').open,'record detail opens');coverage.push({module,category:key,kind:'detail',pass:true});close();}
  }
 }
 ok(errors.length===0,errors.join('\n'));
 const result={language,mobile,checks,forms:coverage.filter(x=>x.kind==='form').length,details:coverage.filter(x=>x.kind==='detail').length,searches:coverage.filter(x=>x.kind==='search').length,errors,coverage,limits:'JSDOM full current runtime scripts with in-memory demo transport. Not live SQL Server, browser layout, OS keyboard, camera or GPS.'};
 fs.writeFileSync(path.join(output,`full-form-matrix-${language}-${mobile?'mobile':'desktop'}.json`),JSON.stringify(result,null,2));console.log(JSON.stringify({...result,coverage:undefined}));dom.window.close();process.exit(0);
})().catch(e=>{const result={language,mobile,checks,error:e.stack,errors,coverage};fs.writeFileSync(path.join(output,`full-form-matrix-${language}-${mobile?'mobile':'desktop'}.json`),JSON.stringify(result,null,2));console.error(e.stack);console.error(errors);dom.window.close();process.exit(1)});
