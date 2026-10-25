const fs=require('fs'),vm=require('vm'),assert=require('assert');
const siteRoot40=[__dirname+'/site',__dirname+'/../SITE_FILES',__dirname+'/..'].find(p=>fs.existsSync(p+'/hr/assets/app.js'));
let assertions=0;function eq(a,b){assert.deepStrictEqual(a,b);assertions++;}
class Element{
 constructor(){this.value='';this.textContent='';this.disabled=false;this.dataset={};this.isConnected=true;this.children=[];this.listeners={};this.nodes={};}
 set innerHTML(value){this.html=value;for(const selector of ['.row-actions','[data-place-status]','[data-place-map]','[data-google-import]','[data-google-url]','[data-place-query]','[data-place-search]','[data-place-results]','[data-place-canvas]'])this.nodes[selector]=new Element();}
 querySelector(s){return this.nodes[s];}append(x){this.children.push(x);}appendChild(x){this.append(x);}setAttribute(){}
 addEventListener(k,f){(this.listeners[k]??=[]).push(f);}dispatchEvent(e){for(const f of this.listeners[e.type]||[])f(e);}
 replaceChildren(){this.children=[];}
}
function fixture(role='manager',granted=true,leaflet=true){
 const root=new Element(),lat=new Element(),lng=new Element(),radius=new Element(),name=new Element();radius.value='50';name.value='Office';let capture,options;
 const chain=()=>({addTo(){return this;},on(){return this;},setLatLng(){return this;},setRadius(){return this;}});
 const c={Map,URL,Date,AbortController,setTimeout,clearTimeout,Event:class{constructor(type){this.type=type;}},boot:{user:{role},access_mode:role==='employee'?'personal':'team'},teamPermission:()=>granted,uiText:x=>x,esc:x=>x,document:{createElement:()=>new Element()},navigator:{geolocation:{getCurrentPosition:(ok,fail,opts)=>{capture={ok,fail};options=opts;}}}};
 if(leaflet)c.L={map:()=>({setView(){return this;},invalidateSize(){},on(){return this;},remove(){}}),tileLayer:chain,divIcon:x=>x,marker:chain,circle:chain};
 vm.createContext(c);vm.runInContext(fs.readFileSync(siteRoot40+'/hr/assets/location-picker.js','utf8'),c);
 const picker=c.locationPicker398(root,lat,lng,radius,name),box=root.children[0],button=box.nodes['.row-actions'].children[0],accuracy=box.children[0];
 return {c,root,lat,lng,radius,name,picker,box,button,accuracy,get capture(){return capture;},get options(){return options;}};
}
const success=(f,accuracy=7.25)=>f.capture.ok({coords:{latitude:29.37591234,longitude:47.97741234,accuracy}});
let f=fixture();eq(!!f.button,true);f.button.onclick();eq(f.button.disabled,true);eq(f.options.enableHighAccuracy,true);eq(f.options.maximumAge,0);eq(f.options.timeout,25000);success(f);eq(f.lat.value,'29.375912');eq(f.lng.value,'47.977412');eq(f.radius.value,'50');eq(f.name.value,'Office');eq(f.accuracy.textContent.includes('7.3'),true);eq(f.button.disabled,false);
for(const code of [1,2,3]){f=fixture();f.lat.value='10';f.lng.value='20';f.button.onclick();f.capture.fail({code});eq(f.lat.value,'10');eq(f.lng.value,'20');eq(f.button.disabled,false);eq(f.box.nodes['[data-place-status]'].textContent.length>0,true);}
f=fixture();f.button.onclick();f.picker.destroy();success(f);eq(f.lat.value,'');eq(f.accuracy.textContent,'');
f=fixture();f.button.onclick();f.lat.value='15';f.lat.dispatchEvent({type:'input'});success(f);eq(f.lat.value,'15');eq(f.lng.value,'');
f=fixture();f.button.onclick();f.picker.select(11,22);success(f);eq(f.lat.value,'11.000000');eq(f.lng.value,'22.000000');
f=fixture();f.button.onclick();success(f,NaN);eq(f.lat.value,'');eq(f.button.disabled,false);
f=fixture('manager',false);eq(f.button,undefined);f=fixture('employee',true);eq(f.button,undefined);f=fixture('admin',false);eq(!!f.button,true);
f=fixture();f.button.onclick();f.c.boot.access_mode='personal';success(f);eq(f.lat.value,'');
f=fixture('manager',true,false);f.button.onclick();success(f);eq(f.lat.value,'29.375912');eq(f.accuracy.textContent.includes('7.3'),true);
f=fixture();delete f.c.navigator.geolocation;f.button.onclick();eq(f.lat.value,'');eq(f.button.disabled,false);
console.log('PASS',assertions,'location checks: permission, fresh reading, accuracy, no autosave, errors, closure, manual selection and stale callbacks.');
