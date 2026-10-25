const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');const {JSDOM}=require(process.env.JSDOM_PATH76);const root=path.resolve(__dirname,'..');let checks=0;const ok=x=>{assert(x);checks++;};
for(const dir of ['rtl','ltr'])for(const desktop of [true,false]){
 const dom=new JSDOM('<div id="portalTabs32"><nav class="portal-tabs32"><button data-view="document">Documents</button></nav><div data-top-actions49><button data-early-action61="addRecord" disabled>Add</button></div></div><div id="page"></div>');
 const d=dom.window.document;d.documentElement.dir=dir;let arrowRefresh=0,opened=0;
 const c={view:'document',document:d,window:{addEventListener(){},matchMedia:()=>({matches:desktop})},uiText:x=>x,esc:x=>String(x),boot:{modules:{document:{label:'مستندات الموظفين'},custody:{label:'العهد'}},company:{currency:'KWD'}},labels:{},dateNow:()=> '2026-10-18',recordIdentity32:x=>x,badge:x=>x,empty:()=>'<p>empty</p>',table:(heads,rows)=>'<table>'+rows.map(r=>'<tr>'+r.map(x=>'<td>'+x+'</td>').join('')+'</tr>').join('')+'</table>',recordDetail:()=>opened++,mobileTabsRefresh33:()=>arrowRefresh++,scheduleStickyTabs48(){},adoptScreenAction61:(b,slot)=>{slot.querySelector('[data-early-action61]')?.remove();slot.append(b);},finishActions32(){d.querySelector('#addRecord')?.classList.add('create-action32');}};
 vm.createContext(c);vm.runInContext(fs.readFileSync(root+'/hr/assets/record-tabs.js','utf8'),c);c.prepareDocumentModes86(d.querySelector('#portalTabs32'));
 ok(d.querySelectorAll('[data-document-mode86]').length===2);ok(!d.querySelector('[data-view="document"]'));
 d.querySelector('#page').innerHTML='<button id="addRecord" class="primary" disabled>+ إضافة</button>'+c.recordTabsHtml49('document')+'<div data-record-results32></div>';
 const rows=[{id:1,title:'Contract',user_id:1,amount:0,status:'active',body_json:'{"record_category49":"contract"}'},{id:2,title:'Personal',user_id:2,amount:0,status:'active',body_json:'{"record_category49":"personal"}'},{id:3,title:'Old',user_id:2,amount:0,status:'active',body_json:'{}'}];
 d.querySelector('#addRecord').onclick=()=>opened++;c.bindRecordTabs49('document',()=>rows);
 ok(!d.querySelector('[data-category49="all"]'));ok(d.querySelector('#recordTabs49 .top-row49').hidden);ok(d.querySelectorAll('[data-record-results32] tr').length===3);
 ok(d.querySelector('#addRecord').parentElement===d.querySelector('#portalTabs32 [data-top-actions49]'));ok(!d.querySelector('[data-early-action61]'));ok(d.querySelector('#addRecord').classList.contains('primary'));ok(d.querySelector('#addRecord').classList.contains('create-action32'));ok(d.querySelector('#addRecord').disabled);
 d.querySelector('[data-document-mode86="detail"]').click();ok(!d.querySelector('#recordTabs49 .top-row49').hidden);d.querySelector('[data-category49="contract"]').click();ok(d.querySelectorAll('[data-record-results32] tr').length===1);
 d.querySelector('[data-document-mode86="all"]').click();ok(d.querySelector('#recordTabs49 .top-row49').hidden);ok(d.querySelectorAll('[data-record-results32] tr').length===3);ok(!d.querySelector('[data-category49][aria-current="page"]'));
 d.querySelector('[data-document-mode86="detail"]').click();d.querySelector('[data-category49="other"]').click();ok(d.querySelectorAll('[data-record-results32] tr').length===1);ok(arrowRefresh>=4);
 d.querySelector('#addRecord').disabled=false;d.querySelector('#addRecord').click();ok(opened===1);
 // Other record modules retain their All category.
 ok(!c.recordTabsHtml49('custody').includes('data-category49="all"'));
}
// Scoped waiting behavior and translations are present in production.
const app=fs.readFileSync(root+'/hr/assets/app.js','utf8');ok(app.includes("add.disabled=recordDisplayEnabled87(module)||boot.user.role!=='admin'"));ok(app.includes("if(recordDisplayEnabled87(module))add.removeAttribute('aria-busy')"));ok(JSON.parse(fs.readFileSync(root+'/hr/app/ui-en.json'))['تفصيلي']==='Detailed');
console.log('PASS',checks,'document mode switching, category filtering, original add action, waiting state and both directions/layouts');
