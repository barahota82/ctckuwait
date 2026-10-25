const fs=require('fs'),vm=require('vm'),assert=require('assert');let checks=0;
const siteRoot40=[__dirname+'/site',__dirname+'/../SITE_FILES',__dirname+'/..'].find(p=>fs.existsSync(p+'/hr/assets/app.js'));
const source=fs.readFileSync(siteRoot40+'/hr/assets/employee-profile.js','utf8');const start=source.indexOf('let profileEpoch40='),end=source.indexOf('\nfunction profileActions29',start);assert(start>=0&&end>start);
const pending=[],page={innerHTML:'',querySelectorAll:()=>[]},back={};
const c={employeeContextStart64:()=>'',navigationEpoch32:1,view:'users',profileUser29:null,profileTab29:'basic',boot:{user:{role:'employee',id:9}},api:()=>new Promise(resolve=>pending.push(resolve)),profileRows33:x=>x,profileBasic33:()=>'',employeeAllowanceCard393:()=>'',teamPermission:()=>false,head:x=>x,displayName31:u=>u.name,employeeProfileNav40:()=>'',profileActions29:()=>'',uiText:x=>x,esc:x=>x,bindEmployeeProfileNav40:()=>{},bindProfileActions29:()=>{},bindEmployeeAllowance393:()=>{},installInterfaceLanguage:()=>{},employeeTransitionAllowed40:()=>true,navigate:()=>{},document:{querySelectorAll:()=>[]},$:s=>s==='#page'?page:s==='#backStaff29'?back:null};
vm.createContext(c);vm.runInContext(source.slice(start,end),c);
const response=(id,tabs={bank:[]})=>({user:{id,name:'Employee '+id,employee_code:'EMP-'+id},tabs});
(async()=>{
 const a=c.employeeProfile29({id:2},{tab:'bank'}),b=c.employeeProfile29({id:9},{tab:'bank'});
 pending[1](response(9));await b;assert.equal(c.profileUser29.id,9);checks++;assert.equal(c.profileTab29,'bank');checks++;
 const rendered=page.innerHTML;pending[0](response(2));await a;assert.equal(page.innerHTML,rendered);checks++;assert.equal(c.profileUser29.id,9);checks++;
 const d=c.employeeProfile29({id:10});c.navigationEpoch32++;pending[2](response(10));await d;assert.equal(page.innerHTML,rendered);checks++;
 const e=c.employeeProfile29({id:10});c.view='policies';pending[3](response(10));await e;assert.equal(page.innerHTML,rendered);checks++;
 c.view='users';const f=c.employeeProfile29({id:2},{tab:'bank'});pending[4](response(2,{}));await f;assert.equal(c.profileTab29,'basic');checks++;
 console.log('PASS',checks,'actual profile async race, navigation context and permitted-tab checks.');
})().catch(e=>{console.error(e);process.exit(1);});
