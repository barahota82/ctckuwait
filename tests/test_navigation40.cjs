const fs=require('fs'),vm=require('vm'),assert=require('assert');let checks=0;const eq=(a,b)=>{assert.deepStrictEqual(a,b);checks++;};
const siteRoot40=[__dirname+'/../SITE_FILES',__dirname+'/../SITE_FILES',__dirname+'/..'].find(p=>fs.existsSync(p+'/hr/assets/app.js'));
const listeners={},buttons={};let confirmed=0,allow=false,opened;
const c={boot:{access_mode:'team',user:{role:'manager'}},users:[{id:10,employee_code:'EMP-0010'},{id:2,employee_code:'EMP-0002'},{id:9,employee_code:'EMP-0009'}],profileTab29:'bank',teamPermission:()=>true,uiText:x=>x,esc:x=>x,toast:()=>{},MutationObserver:class{observe(){}disconnect(){}},queueMicrotask,document:{documentElement:{dir:'rtl'},body:{},addEventListener:(k,f)=>listeners[k]=f,querySelector:s=>buttons[s]||null,querySelectorAll:()=>[]},confirm:()=>{confirmed++;return allow;},employeeProfile29:async(u,o)=>opened={id:u.id,tab:o.tab}};
vm.createContext(c);vm.runInContext(fs.readFileSync(siteRoot40+'/hr/assets/employee-navigation.js','utf8'),c);
eq([...c.employeeOrder40(c.users)].map(x=>x.id),[2,9,10]);eq(c.users.map(x=>x.id),[10,2,9]);
const cards=[{hidden:false},{hidden:true},{hidden:false},{hidden:false,isConnected:false}];
eq(c.employeeCardTarget40(cards,cards[0],1),cards[2]);eq(c.employeeCardTarget40(cards,cards[2],-1),cards[0]);eq(c.employeeCardTarget40(cards,cards[1],1),undefined);eq(c.employeeCardTarget40(cards,cards[2],1),undefined);
cards[1].hidden=false;eq(c.employeeCardTarget40(cards,cards[0],1),cards[1]);
let html=c.employeeProfileNav40({id:2});
const disabled=(html,step)=>new RegExp('data-profile-'+step+'40[^>]*disabled').test(html);
eq(disabled(html,'first'),true);eq(disabled(html,'prev'),true);eq(disabled(html,'next'),false);eq(disabled(html,'last'),false);
eq((html.match(/<button/g)||[]).length,4);
html=c.employeeProfileNav40({id:10});eq(disabled(html,'next'),true);eq(disabled(html,'last'),true);eq(disabled(html,'first'),false);
html=c.employeeProfileNav40({id:9});for(const step of ['first','prev','next','last'])eq(disabled(html,step),false);
c.boot.access_mode='personal';eq(c.employeeProfileNav40({id:9}),'');c.boot.access_mode='team';c.teamPermission=()=>false;eq(c.employeeProfileNav40({id:9}),'');c.teamPermission=()=>true;eq(c.employeeProfileNav40({id:999}),'');
const form={},root={querySelectorAll:()=>[form]};eq(c.employeeTransitionAllowed40(root),true);listeners.input({target:{name:'name',closest:selector=>selector==='form'?form:null}});eq(c.employeeTransitionAllowed40(root),false);eq(confirmed,1);allow=true;eq(c.employeeTransitionAllowed40(root),true);
// Exercise the actual card injection and click handlers, including dynamic filtering.
const parent={cards:[],closest:()=>null,querySelectorAll(){return this.cards;}};
function card(id){const x={closest:()=>null,dataset:{employeeId:String(id)},hidden:false,isConnected:true,parentElement:parent,open:false,nav:null,scrollIntoView(){this.scrolled=true;},querySelectorAll:()=>[],querySelector(selector){return selector.includes('employee-nav40')?this.nav:summary;}};const summary={textContent:'Employee '+id,after(nav){x.nav=nav;nav.owner=x;},focus(){x.focused=true;}};parent.cards.push(x);return x;}
const first=card(2),middle=card(9),last=card(10),cardRoot={querySelectorAll:()=>parent.cards};
c.document.createElement=()=>({dataset:{},children:[],setAttribute(){},append(x){this.children.push(x);},querySelectorAll(){return this.children;},remove(){this.owner.nav=null;}});
c.employeeNavFooter48=(root,nav)=>{root.nav=nav;nav.owner=root;};c.addEmployeeCardNavigation40(cardRoot);eq(first.nav.children[0].disabled,true);eq(first.nav.children[1].disabled,true);eq(first.nav.children[2].disabled,false);first.open=true;first.nav.children[2].onclick();eq(first.open,false);eq(middle.open,true);eq(middle.focused,true);
middle.hidden=true;c.addEmployeeCardNavigation40(cardRoot);first.nav.children[2].onclick();eq(last.open,true);eq(last.scrolled,true);eq(last.nav.children[2].disabled,true);
last.hidden=true;c.addEmployeeCardNavigation40(cardRoot);eq(first.nav.children[2].disabled,true);last.hidden=false;
parent.cards=[first];c.addEmployeeCardNavigation40(cardRoot);eq(first.nav,null);
buttons['[data-profile-first40]']={isConnected:true};buttons['[data-profile-last40]']={isConnected:true};buttons['[data-profile-prev40]']={isConnected:true};buttons['[data-profile-next40]']={isConnected:true};buttons['#page']={querySelectorAll:()=>[]};c.bindEmployeeProfileNav40({id:9});
(async()=>{await buttons['[data-profile-next40]'].onclick();eq(opened.id,10);eq(opened.tab,'bank');await buttons['[data-profile-first40]'].onclick();eq(opened.id,2);await buttons['[data-profile-last40]'].onclick();eq(opened.id,10);await buttons['[data-profile-prev40]'].onclick();eq(opened.id,2);eq(buttons['[data-profile-prev40]'].disabled,false);console.log('PASS',checks,'employee ordering, boundaries, scope, dirty-form guard and profile navigation checks.');})().catch(e=>{console.error(e);process.exit(1);});
