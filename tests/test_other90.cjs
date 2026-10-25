const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');const {JSDOM}=require(process.env.JSDOM_PATH76),root=path.resolve(__dirname,'..');let n=0;const ok=x=>{assert(x);n++;};
for(const mode of ['admin','attendance'])for(const dir of ['rtl','ltr']){
 const dom=new JSDOM(''),d=dom.window.document;d.documentElement.dir=dir;
 const modules=Object.fromEntries(['request','document','custody','discipline','evaluation'].map(k=>[k,{label:k}]));const c={window:{addEventListener(){}},document:d,requestMode50:mode,uiText:x=>x,esc:x=>String(x),labels:{},boot:{modules},dateNow:()=> '2026-10-18'};vm.createContext(c);vm.runInContext(fs.readFileSync(root+'/hr/assets/record-tabs.js','utf8'),c);
 for(const module of Object.keys(modules)){
  const rows=[{id:1,body_json:'{}'},{id:2,body_json:'{"record_category49":"unclassified"}'},{id:3,body_json:'{"record_category49":"other"}'},{id:4,body_json:'{"record_category49":"unknown_old_value"}'}];const before=JSON.stringify(rows);
  for(const row of rows)ok(c.recordCategoryOf49(module,row)==='other');
  const html=c.recordTabsHtml49(module);ok(!html.includes('data-category49="unclassified"'));ok(!html.includes('سجلات سابقة غير مصنفة'));d.body.innerHTML=html;
  if(module==='request'&&mode==='attendance'){ok(!d.querySelector('[data-category49="other"]'));ok(c.recordCategoryOf49('request',{body_json:'{"_punch39":{}}'})==='attendance');continue;}
  ok(d.querySelectorAll('[data-category49="other"]').length===1);ok((c.recordCategoryField49(module).match(/value="other"/g)||[]).length===1);
  vm.runInContext("recordCategory49='other'",c);ok(c.recordRows49(module,rows).length===4);vm.runInContext("recordCategory49='all'",c);ok(c.recordRows49(module,rows).length===4);ok(before===JSON.stringify(rows));
 }
}
const php=fs.readFileSync(root+'/hr/api.php','utf8');ok(php.includes("'discipline'=>['notice','warning','penalty','decision','appeal','other']"));ok(php.includes("'evaluation'=>['trial','periodic','performance','attendance','behavior','improvement','other']"));
console.log('PASS',n,'five screens, single Other tab and form choice, all legacy/unclassified rows retained, attendance excluded, source rows unchanged and save allowlist');
