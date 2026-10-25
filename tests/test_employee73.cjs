const ROOT=require('path').resolve(__dirname,require('fs').existsSync(require('path').join(__dirname,'../SITE_FILES'))?'../SITE_FILES':'..');
const fs=require('fs'),vm=require('vm'),assert=require('assert');let n=0;
const src=fs.readFileSync(ROOT+'/hr/assets/employee-filter.js','utf8');
const c={view:'document',personal:false,document:{querySelector:()=>null},personalNavigation50:()=>c.personal,hubFor32:key=>({users:['staff'],workforce:['staff'],document:['docs'],work_permit:['docs'],payroll:['pay'],commissions:['pay'],collection37:['pay'],report:['attendance'],external:['attendance']})[key]?.map?{0:({users:'staff',workforce:'staff',document:'docs',work_permit:'docs',payroll:'pay',commissions:'pay',collection37:'pay',report:'attendance',external:'attendance'})[key]}:null};
vm.createContext(c);vm.runInContext(src,c);const eq=(a,b)=>{assert.deepEqual(JSON.parse(JSON.stringify(a)),b);n++;};
vm.runInContext("employeeSelections73.set('docs',23)",c);
eq(c.employeeValue73(),23);c.view='work_permit';eq(c.employeeValue73(),23);c.view='users';eq(c.employeeValue73(),0);c.view='document';
for(const action of ['list','v26_info']){const p=c.employeeReadParams73(action,null,{module:'document',q:'abc',page:2});eq(p.employee73,23);eq(p.employee69,23);eq(p.q,'abc');eq(p.page,2);}
for(const action of ['record_save','pay37_save','bank_approve','v26_payment'])eq(c.employeeReadParams73(action,{x:1},{q:'abc'}),{q:'abc'});
for(const action of ['users','user_choices29','companies','profile29','pay37_sheet','sales36_report','bootstrap'])eq(c.employeeReadParams73(action,null,{}),{});
c.document.querySelector=()=>({});eq(c.employeeReadParams73('list',null,{}),{});c.document.querySelector=()=>null;
c.personal=true;eq(c.employeeValue73(),0);eq(c.employeeReadParams73('list',null,{}),{});c.personal=false;
eq(c.employeeRows73([{user_id:23},{user_id:39}]),[{user_id:23}]);eq(c.employeeRows73([{id:23},{id:39}],'id'),[{id:23}]);
for(const key of ['users','workforce','employment','report','request','external','tracking','proof','policies','document','work_permit','custody','discipline','evaluation','payroll','commissions','collection37','payroll_review35','bank','vouchers37','payments26','payout_status','loan','v28_debt','allowances','addition','entitlement','eos','video_auto','video_custom','video_receipts','analytics','v26_history'])eq(c.employeeEnabled73(key),true);
for(const key of ['attendance','settings','backups','v28_locations','companies','audit','leave'])eq(c.employeeEnabled73(key),false);
const css=fs.readFileSync(ROOT+'/hr/assets/style.css','utf8');for(const fragment of ['width:min(220px,100%)','min-width:160px','flex:1 1 100%']){assert(css.slice(css.indexOf('/* V73:')).includes(fragment));n++;}
assert(src.includes('label.contains(native)'));n++;assert(src.includes("native.setAttribute('form',form.id)"));n++;assert(src.includes('epoch!==navigationEpoch32'));n++;
console.log('PASS '+n+' employee selection, hub persistence, personal/modal/write exclusions, preserved params, screen coverage, dimensions and native control source guards');
