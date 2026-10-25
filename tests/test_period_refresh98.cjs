const fs=require('fs'),vm=require('vm'),path=require('path'),assert=require('assert');
const {JSDOM}=require(process.env.JSDOM_PATH76),root=path.resolve(__dirname,'..');let n=0;const ok=x=>{assert(x);n++};
(async()=>{
 for(const key of ['report','analytics','audit','v28_debt','debt_statement95'])for(const dir of ['rtl','ltr']){
  const dom=new JSDOM('<div id="page" data-screen-loading94="1"></div><div id="root"><div class="header-secondary77"></div></div>'),d=dom.window.document;d.documentElement.dir=dir;
  let calls=0,finish;const errors=[],c={document:d,Event:dom.window.Event,view:key,navigationEpoch32:1,uiText:x=>x,toast:x=>errors.push(x)};vm.createContext(c);vm.runInContext('const employeeNames143='+fs.readFileSync(__dirname+'/../hr/assets/data-language.js','utf8').split('const employeeNames143=')[1],c);vm.runInContext(fs.readFileSync(root+'/hr/assets/period-refresh.js','utf8'),c);
  const host=d.querySelector('#root');c.mountPeriodRefresh98(host);let button=host.querySelector('button');ok(button.disabled);ok(button.textContent==='تحديث');
  const secondary=host.querySelector('.header-secondary77');let from,to;
  if(['report','v28_debt','debt_statement95'].includes(key)){
   from=d.createElement('input');to=d.createElement('input');from.id=key==='report'?'reportFrom':'debtFrom95';to.id=key==='report'?'reportTo':'debtTo95';secondary.prepend(from,to);
   if(key==='report'){const run=d.createElement('button');run.id='runReport';run.onclick=()=>{calls++;return new Promise(r=>finish=r)};host.append(run);}else from._periodRefresh98=()=>{calls++;return new Promise(r=>finish=r)};
  }else{
   const f=d.createElement('form');f.id=key==='analytics'?'v26ReportForm':'audit26';host.append(f);from=d.createElement('input');to=d.createElement('input');from.name='from';to.name='to';from.setAttribute('form',f.id);to.setAttribute('form',f.id);secondary.prepend(from,to);f.onsubmit=e=>{e.preventDefault();calls++;return new Promise(r=>finish=r)};
  }
  from.type=to.type='date';from.value='2026-09-01';to.value='2026-09-30';delete d.querySelector('#page').dataset.screenLoading94;c.mountPeriodRefresh98(host);ok(!button.disabled);ok(secondary.querySelectorAll('[data-period-refresh98]').length===1);
  const pending=button.onclick();ok(calls===1);ok(button.disabled);await button.onclick();ok(calls===1);finish();await pending;ok(!button.disabled);
  from.value='2027-01-01';await button.onclick();ok(calls===1);ok(errors.length===1);from.value='2026-09-01';
  from.disabled=true;c.mountPeriodRefresh98(host);ok(button.disabled);from.disabled=false;c.mountPeriodRefresh98(host);
  c.navigationEpoch32++;await button.onclick();ok(calls===1);dom.window.close();
 }
 // Exact owner example: source opening moves into the period opening only after its date.
 const dom=new JSDOM(''),d=dom.window.document,c={document:d,boot:{user:{role:'admin',id:99}},uiText:x=>x,esc:x=>String(x??''),money26:x=>Number(x).toFixed(3),dateDisplay32:x=>x};vm.createContext(c);vm.runInContext('const employeeNames143='+fs.readFileSync(__dirname+'/../hr/assets/data-language.js','utf8').split('const employeeNames143=')[1],c);const source=fs.readFileSync(root+'/hr/assets/debt-statement.js','utf8');vm.runInContext(source,c);
 const ledger={user_id:1,code:'EMP-1',name:'Employee',movements:[{id:1,kind:'debt_opening',name95:'debt_opening',date:'2026-09-30',amount:600}]};
 for(const [from,to,opening,count]of [['2026-09-01','2026-09-30',0,1],['2026-10-01','2026-10-31',600000,0],['2026-09-01','2026-09-29',0,0]]){
  const s=c.debtStatement95(ledger,from,to);ok(s.opening95===opening);ok(s.rows95.length===count);ok(s.closing95===opening+s.debits95-s.credits95);d.body.innerHTML=c.debtAccountHtml95(ledger,s,false);ok(!d.body.textContent.includes('debt_opening'));ok(d.querySelector('[data-debt-add32]').textContent==='إضافة سلفة');ok(d.body.textContent.split('رصيد أول المدة').length-1===1);
 }
 const before=fs.readFileSync(path.resolve(root,'../../review98/baseline/hr/assets/debt-statement.js'),'utf8'),calc=s=>s.slice(s.indexOf('function debtStatement95('),s.indexOf('const debtMoney95'));ok(calc(source)===calc(before));
 console.log('PASS',n,'date range refresh across five views and both directions, moved form ownership, loading, duplicate clicks, validation, stale action, exact opening example and unchanged calculator');
})().catch(e=>{console.error(e);process.exitCode=1});
