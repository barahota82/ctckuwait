'use strict';
// No production transport, camera, location, backup restore or real exports in this isolated preview.
(()=>{
if('serviceWorker'in navigator){try{navigator.serviceWorker.register=()=>Promise.reject(Error('Demo does not install service workers'));}catch{}}
const svg='<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800"><rect width="600" height="800" fill="#faf7ed"/><text x="300" y="350" text-anchor="middle" font-size="32">DEMO DOCUMENT</text><text x="300" y="400" text-anchor="middle">NOT A REAL EMPLOYEE DOCUMENT</text></svg>';
const attachment=URL.createObjectURL(new Blob([svg],{type:'image/svg+xml'}));
const replaceMedia=()=>{for(const a of document.querySelectorAll('a[href^="privacy.php"]'))a.setAttribute('href','privacy.html');for(const img of document.querySelectorAll('img[src*="file.php"]'))img.src=img.classList.contains('brand-logo')?'demo-logo.png':attachment;};
new MutationObserver(replaceMedia).observe(document.body,{childList:true,subtree:true,attributes:true,attributeFilter:['src','href']});replaceMedia();
const open=window.open.bind(window);window.open=(url,...args)=>/export\.php|backup-download\.php|public-preview\.php/.test(String(url))?(alert(document.documentElement.lang==='en'?'Preview only: no production export or public page.':'معاينة محلية فقط: لا يتم فتح صفحات الإنتاج أو تصدير بيانات حقيقية.'),null):open(/file\.php/.test(String(url))?attachment:url,...args);
document.addEventListener('click',e=>{const a=e.target.closest('a');if(a&&/file\.php|export\.php|backup-download\.php/.test(a.getAttribute('href')||'')){e.preventDefault();if(/file\.php/.test(a.getAttribute('href')))window.open(attachment,'_blank','noopener');else alert(document.documentElement.lang==='en'?'Local demo: no real file is exported.':'نسخة تجريبية: لا يتم تصدير بيانات حقيقية.');}},true);
window.addEventListener('submit',e=>{if(!e.defaultPrevented)e.preventDefault();},true);
})();
