const fs=require('fs'),path=require('path'),assert=require('assert');
const ROOT=path.resolve(__dirname,fs.existsSync(path.join(__dirname,'../SITE_FILES'))?'../SITE_FILES':'..');
const {JSDOM}=require(process.env.JSDOM_PATH76||'jsdom');
const src=fs.readFileSync(ROOT+'/hr/assets/header-layout.js','utf8');let checks=0,cases=0;const ok=x=>{assert(x);checks++;};
const source=fs.readFileSync(ROOT+'/hr/assets/employee-filter.js','utf8');const employeeScreens=source.match(/new Set\(\[([^\]]+)/)[1].match(/'([^']+)'/g).map(s=>s.slice(1,-1));
const other=['leave','sick','leave_overview','leave_cash','resumption','permissions','settings','backups','companies','cms','offers','languages','customer_cases','audit','dashboard','notifications','attendance'];
for(const screen of [...employeeScreens,...other])for(const lang of ['ar','en']){
 const rich=['analytics','payroll','bank'].includes(screen),count=rich?4:['document','custody'].includes(screen)?2:1;
 const form='<form id="filters" class="toolbar">'+Array.from({length:count},(_,i)=>'<label>Filter '+i+'<input name="field'+i+'" value="value'+i+'"></label>').join('')+'<input id="reportFrom" type="date" name="from" value="2026-10-18"><label><input type="checkbox" id="allRows">All</label><button id="run" type="submit">Run</button></form>';
 const dom=new JSDOM('<html dir="'+(lang==='ar'?'rtl':'ltr')+'"><body><div id="portalTabs32"><nav class="portal-tabs32"><button>Tab</button></nav><div class="record-filter-row75" data-record-filter-row75><label class="leave-employee-filter69">Employee<select id="employeeFilter73"><option value="23">Long employee name</option></select></label>'+form+'<label class="record-search-label32">Search<input id="search" type="search" value="query"></label></div></div><div id="page"><div class="toolbar"><label>Company<select><option>C1</option></select></label></div></div></body></html>',{runScripts:'outside-only'});
 const w=dom.window;w.uiText=x=>x;w.eval(src+"\nwindow.polishHeader76=polishHeader76;");let calls=0;w.document.getElementById('run').onclick=()=>calls++;const handler=w.document.getElementById('run').onclick;
 const before=new w.FormData(w.document.getElementById('filters'));w.polishHeader76();const row=w.document.querySelector('[data-record-filter-row75]');
 ok(row.children[0].classList.contains('header-person76'));ok(row.children[1].classList.contains('header-search76'));
 ok(w.document.getElementById('run').closest('.header-actions76'));ok(w.document.getElementById('allRows').closest('.header-actions76'));ok(w.document.getElementById('run').form.id==='filters');ok(w.document.getElementById('run').onclick===handler);
 for(const [k,v]of before)ok(new w.FormData(w.document.getElementById('filters')).get(k)===v);
 const after=row.innerHTML;w.polishHeader76();ok(row.innerHTML===after);ok(w.document.querySelectorAll('#employeeFilter73').length===1);ok(w.document.querySelectorAll('#search').length===1);ok(w.document.querySelectorAll('.header-search76').length===1);
 ok(w.document.querySelector('#page>.toolbar').classList.contains('header-toolbar76'));
 for(const label of row.querySelectorAll(':scope>label,:scope>.toolbar>label'))ok(label.firstElementChild.tagName==='SPAN');
 cases++;w.close();
}
const css=fs.readFileSync(ROOT+'/hr/assets/style.css','utf8').split('/* V76:')[1];ok(css.includes('overflow:visible'));ok(css.includes('display:grid'));ok(css.includes('--header-person76:220px;--header-search76:220px'));ok(css.includes('padding-left:38px!important;padding-right:10px!important'));ok(css.includes('html[dir="rtl"] .date-field32'));ok(css.includes('grid-column:1/-1'));ok(css.includes('max-width:760px'));
const ar=JSON.parse(fs.readFileSync(ROOT+'/hr/app/ui-source.json','utf8')),en=JSON.parse(fs.readFileSync(ROOT+'/hr/app/ui-en.json','utf8'));ok(ar['إجراءات التعيين وإنهاء الخدمة']==='إجراءات التعيين وإنهاء الخدمة');ok(en['إجراءات التعيين وإنهاء الخدمة']==='Hiring and termination procedures');
console.log('PASS '+checks+' DOM/structure assertions across '+cases+' screen/language cases, stable handlers, form ownership, no duplicate fields, primary order, label alignment, responsive CSS and Arabic date guards. No visual rendering claim.');
