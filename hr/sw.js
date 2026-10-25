'use strict';
// No offline API, employee data, payroll, photos or cached success responses.
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
self.addEventListener('fetch',event=>{if(event.request.method==='GET'&&event.request.mode==='navigate'){event.respondWith(fetch(event.request,{cache:'no-store'}).catch(()=>new Response('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>غير متصل</title><h1>انقطع الاتصال بالإنترنت</h1><p>أعد الاتصال ثم افتح الصفحة. إذا انقطع الاتصال أثناء الحفظ، راجع السجل قبل إعادة الإرسال.</p><h2>Internet connection lost.</h2><p>Reconnect and reopen this page. If a save was interrupted, check its record before submitting again.</p></html>',{status:503,headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}})));}});
