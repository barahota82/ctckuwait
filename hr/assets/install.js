'use strict';
let installPrompt31=null;
const isStandalone31=()=>window.matchMedia?.('(display-mode: standalone)').matches||navigator.standalone===true;
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt31=event;});
window.addEventListener('appinstalled',()=>{installPrompt31=null;document.querySelectorAll('.install-shortcut31').forEach(n=>n.remove());});
function installShortcut31(){
 const host=document.querySelector('.account-tools')||document.querySelector('.login-card');if(!host||host.querySelector('.install-shortcut31'))return;
 const button=document.createElement('button');button.type='button';button.className='install-shortcut31';button.textContent=uiText('إضافة إلى جهازك');button.setAttribute('data-no-translate','');if(!isStandalone31())host.appendChild(button);
 button.onclick=async()=>{if(installPrompt31){const prompt=installPrompt31;installPrompt31=null;await prompt.prompt();await prompt.userChoice;return;}const ios=/iPad|iPhone|iPod/.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);modal('إضافة إلى جهازك','<p>'+esc(uiText(ios?'في Safari اضغط Share ثم Add to Home Screen ثم Add.':'افتح قائمة المتصفح واختر تثبيت التطبيق أو إضافة إلى الشاشة الرئيسية. على الكمبيوتر يمكنك أيضًا إنشاء اختصار للموقع من قائمة المتصفح.'))+'</p>');installInterfaceLanguage();};
}
if('serviceWorker'in navigator&&window.isSecureContext)navigator.serviceWorker.register('sw.js',{scope:'./',updateViaCache:'none'}).catch(()=>{});
