const ROOT=require('path').resolve(__dirname,require('fs').existsSync(require('path').join(__dirname,'../SITE_FILES'))?'../SITE_FILES':'..');
const fs=require('fs'),vm=require('vm'),assert=require('assert');
let moved=0,removed=0,submitted=0,prior=0;
const form={id:'v26ReportForm',requestSubmit:()=>submitted++};
const wrapper={};const placeholder={closest:()=>null,remove:()=>removed++};
const label={contains:n=>moved>0,querySelector:()=>placeholder,append:n=>{assert(n===wrapper);moved++;}};
const native={dataset:{},disabled:false,value:'',options:[{value:''},{value:'23'}],closest:s=>s==='form'?form:s==='label'?null:wrapper,setAttribute:(k,v)=>{native[k]=v;},onchange:()=>prior++,dispatchEvent:e=>native.onchange(e)};
const c={view:'analytics',Event:class{},document:{querySelector:s=>s==='[data-employee-filter73]'?label:null,querySelectorAll:()=>[]},personalNavigation50:()=>false,hubFor32:()=>null,$:s=>s==='#v26ReportForm select[name="user_id"]'?native:s==='#v26ReportForm'?form:null};
vm.createContext(c);vm.runInContext(fs.readFileSync(ROOT+'/hr/assets/employee-filter.js','utf8'),c);c.mountEmployeeFilter73();c.mountEmployeeFilter73();assert.equal(moved,1);assert.equal(removed,1);assert.equal(native.form,'v26ReportForm');native.value='23';native.onchange({});assert.equal(submitted,1);assert.equal(prior,1);assert.equal(c.employeeValue73(),23);console.log('PASS 6 native-control adoption, enhanced-wrapper idempotence, retained callback, form association and live selection checks');
