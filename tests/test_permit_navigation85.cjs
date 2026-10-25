const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
const ROOT=path.resolve(__dirname,'..');const {JSDOM}=require(process.env.JSDOM_PATH76);let checks=0;
const ok=(x)=>{assert(x);checks++;};
for(const dir of ['rtl','ltr'])for(const allowed of [true,false]){
 const dom=new JSDOM('<div id="app"><aside class="sidebar"></aside><div id="portalTabs32"></div><div id="page"></div></div>');
 const c={document:dom.window.document,window:dom.window,boot:{access_mode:'team',user:{role:'manager'}},screenAllowed:k=>k!=='work_permit'||allowed,uiText:x=>x,esc:x=>x,navItem:(k,t)=>`<button class="nav-item" data-view="${k}">${t}</button>`,ResizeObserver:class{observe(){}unobserve(){}},MutationObserver:class{observe(){}},requestAnimationFrame:()=>1,view:'work_permit'};
 c.document.documentElement.dir=dir;vm.createContext(c);
 for(const f of ['navigation.js','tabs.js'])vm.runInContext(fs.readFileSync(ROOT+'/hr/assets/'+f,'utf8'),c);
 const docs=c.hubFor32('document'),permit=c.hubFor32('work_permit');
 ok(docs[0]!==permit[0]);ok(docs[2].length===1&&docs[2][0]==='document');ok(permit[2].length===1&&permit[2][0]==='work_permit');
 ok(c.resolveHub32(permit[0])===(allowed?'work_permit':'dashboard'));
 c.document.querySelector('.sidebar').innerHTML=c.tabMenu32();
 const nav=[...c.document.querySelectorAll('.sidebar .nav-item')].map(n=>n.dataset.view);
 ok(nav.filter(k=>k===permit[0]).length===(allowed?1:0));
 if(allowed)ok(nav.indexOf(permit[0])+1===nav.indexOf(docs[0]));
 c.syncPortalTabs32('document');ok(!c.document.querySelector('#portalTabs32 [data-view="work_permit"]'));
 c.syncPortalTabs32('work_permit');ok(c.document.querySelector('#portalTabs32').hidden===!allowed);
 if(allowed){ok(c.document.querySelector('#portalTabs32 [data-view="work_permit"][aria-current="page"]'));ok(c.document.querySelector('.sidebar [data-view="'+permit[0]+'"]').classList.contains('active'));}
}
// Employee-file implementation and permission/backend sources stay byte-identical.
for(const f of ['hr/assets/permission-groups.js'])ok(fs.readFileSync(ROOT+'/'+f,'utf8').replaceAll('employeeNameMarkup143(u.id,u.name,u)','esc(displayName31(u))')===fs.readFileSync(path.resolve(ROOT,'../../review85/baseline',f),'utf8'));
const modulesNow=fs.readFileSync(ROOT+'/hr/app/modules.php','utf8'),modulesOld=fs.readFileSync(path.resolve(ROOT,'../../review85/baseline/hr/app/modules.php'),'utf8');const stripAttendance=s=>s.replace("require_once __DIR__.'/attendance-prefetch.php';\n",'').replace(/function attendance_report\([\s\S]*?(?=\nfunction )/,'');ok(stripAttendance(modulesNow)===stripAttendance(modulesOld));
console.log('PASS',checks,'standalone permits, menu position, permission gating, RTL/LTR and unchanged employee/backend');

const group115=fs.readFileSync(ROOT+'/hr/assets/employee-profile.js','utf8'),old115=fs.readFileSync(path.resolve(ROOT,'../../review85/baseline/hr/assets/employee-profile.js'),'utf8');const permit115=x=>x.slice(x.indexOf("else if(tab==='permit')"),x.indexOf("else content="));ok(permit115(group115)===permit115(old115));
