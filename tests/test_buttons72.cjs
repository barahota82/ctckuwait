const ROOT=require('path').resolve(__dirname,require('fs').existsSync(require('path').join(__dirname,'../SITE_FILES'))?'../SITE_FILES':'..');
const fs=require('fs'),assert=require('assert');
const css=fs.readFileSync(ROOT+'/hr/assets/style.css','utf8');
const patch=css.slice(css.indexOf('/* V72:'),css.indexOf('/* V73:'));
let n=0;function check(x){assert(x);n++;}
check(!css.includes('button,.button{border-radius:999px;'));
for(const host of ['#page','#modalContent','#portalTabs32'])check(patch.includes(host));
for(const exclusion of ['.employee-nav48 *','.portal-tabs32 *','.profile-tabs29 *','.search-select32 *','.date-field32 *','.date-calendar32 *','.event-actions *','.tab-arrow53','.employee-context-toggle64'])check(patch.includes(exclusion));
check(patch.includes('min-height:42px'));check(patch.includes('min-height:44px'));check(patch.includes('border-radius:10px'));check(patch.includes('font-size:13px'));check(!/[{;](?:background|color|width|margin|position)\s*:/.test(patch.replace(/max-width:100%/g,'')));
check(fs.readFileSync(ROOT+'/hr/index.php','utf8').includes('style.css?v=146.0'));
console.log('PASS '+n+' source guards for action geometry, hosts, specialized exclusions, preserved colors/widths, mobile and cache version (not a browser rendering test)');
