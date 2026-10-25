const fs=require('fs'),vm=require('vm'),path=require('path'),assert=require('assert');const {JSDOM}=require(process.env.JSDOM_PATH76),root=path.resolve(__dirname,'..');let n=0;const ok=x=>{assert(x);n++};
const app=fs.readFileSync(root+'/hr/assets/app.js','utf8');
(async()=>{for(const dir of ['rtl','ltr']){
 const dom=new JSDOM('<div id="app"><div id="portalTabs32"></div><div id="page"></div></div>'),d=dom.window.document;d.documentElement.dir=dir;let reads=0;
 const en=JSON.parse(fs.readFileSync(root+'/hr/app/ui-en.json','utf8')),c={document:d,window:dom.window,employeeScreens73:new Set(),headerStackScreens77:new Set(),headerFields79:{},teamPermission:()=>true,dateNow:()=> '2026-10-18',view:'dashboard',navigationEpoch32:0,pendingReads32:new Set(),boot:{user:{role:'admin'},access_mode:'team',modules:{}},money26:x=>Number(x).toFixed(3),uiText:x=>dir==='ltr'?en[x]||x:x,esc:x=>String(x),ResizeObserver:class{observe(){}unobserve(){}},MutationObserver:class{observe(){}},requestAnimationFrame:()=>1,screenAllowed:()=>true,navItem:(k,t)=>`<button data-view="${k}">${t}</button>`,api:async(action)=>{assert(action==='finance103_info');reads++;return {rows:[]}},$:s=>d.querySelector(s),loadingMarkup:()=>'<div class="loading"></div>',empty:()=>'<div class="empty">No results</div>',closeCamera(){},applyInterfaceLanguage(){}};
 vm.createContext(c);for(const f of ['navigation.js','tabs.js','finance-shell.js','finance-daily.js'])vm.runInContext(fs.readFileSync(root+'/hr/assets/'+f,'utf8'),c);
 const hubs=vm.runInContext('portalHubs32',c),areas=vm.runInContext('portalAreas32',c),financial=areas.find(x=>x[0]==='الشؤون المالية')[1];ok(financial.slice(0,3).join('|')==='hub_debt|hub_contracts99|hub_receipts99');
 vm.runInContext(app.slice(app.indexOf('async function navigate('),app.indexOf('\nfunction stat')),c);
 for(const [hubId,count]of [['hub_contracts99',3],['hub_receipts99',4]]){
  const hub=hubs.find(x=>x[0]===hubId);ok(hub[2].length===count);ok(c.resolveHub32(hubId)===hub[2][0]);
  for(const key of hub[2]){await c.navigate(key);ok(c.view===key);ok(d.querySelector('[data-finance-shell99]'));ok(!d.querySelector('#page form'));ok(d.querySelector('#financeAdd103'));ok(d.querySelectorAll('#portalTabs32 .portal-tabs32 button').length===count);ok(d.querySelector('[aria-current=page]').dataset.view===key);ok(c.tabTitle32(key)!==key);ok(!!en[c.tabTitle32(key)]);}
 }
 ok(reads===7);c.boot.access_mode='personal';c.boot.user.role='employee';ok(!c.hubFor32('contract_sales99'));ok(!c.hubFor32('receipt_click99'));dom.window.close();
}
const loan=app.slice(app.indexOf('async function records('),app.indexOf('async function recordModal('));ok(loan.includes("module==='loan'?'<div class=\"page-head\">'"));ok(loan.includes('id="addRecord"'));console.log('PASS',n,'seven shells and translations, ordered financial hubs, real navigation with one period read per tab and no writes, separate personal menu and retained loan add action');
})().catch(e=>{console.error(e);process.exitCode=1});
