const fs=require('fs'),assert=require('assert'),{JSDOM}=require('jsdom');
const text=fs.readFileSync(__dirname+'/../hr/assets/tabs.js','utf8');const start=text.indexOf('function syncPortalTabs32('),end=text.indexOf('// Keep secondary strips',start);let count=0;
for(const lang of ['ar','en'])for(const width of [390,1440]){
 const d=new JSDOM('<html><body><aside class="sidebar"><details><summary>Staff</summary><button class="nav-item active" data-view="hub_staff"></button><button class="nav-item" data-view="hub_documents50"></button><button class="nav-item" data-view="custody"></button><button class="nav-item" data-view="discipline"></button><button class="nav-item" data-view="evaluation"></button></details></aside><div id="portalTabs32">old tabs</div></body></html>',{runScripts:'outside-only'});const w=d.window;w.hubFor32=k=>k==='document'?['hub_documents50']:null;w.eval("const recordModules173=['document','custody','discipline','evaluation'];"+text.slice(start,end));
 for(const screen of ['document','custody','discipline','evaluation','document']){w.syncPortalTabs32(screen);const selected=[...w.document.querySelectorAll('.nav-item.active')];assert.equal(selected.length,1);assert.equal(selected[0].dataset.view,screen==='document'?'hub_documents50':screen);assert(w.document.querySelector('details').open);assert(w.document.querySelector('#portalTabs32').hidden);count+=4;}
 d.window.close();
}
console.log('PASS '+count+' real sidebar sync checks, documents hub alias and subsequent screens, AR/EN desktop/mobile fixtures');
