'use strict';
// Only confirmed user-facing operations generate completion notices. Reads and telemetry do not.
function operationMessage393(action,data,result){
 if(result?.ok===false)return result.error||'لم تكتمل العملية؛ راجع البيانات وحاول مرة أخرى.';
 if(typeof FormData!=='undefined'&&data instanceof FormData)data=Object.fromEntries(data);
 if(/^(bootstrap|server_clock|capture_ticket|tracking|login|logout|identity32_mode|language_select|groups30_filter|v26_video_ticket|policy_manage_preview|backup_restore_preview|import_preview|translation_process)$/.test(action)||/(_info|_list|_get|_choices|_preview|_detail|_report|_sheet|_vouchers)$/.test(action))return '';
 const results=result?.results;if(Array.isArray(results)&&results.some(r=>r.error||r.ok===false))return results.some(r=>!r.error&&r.ok!==false)?'تم تنفيذ جزء من العملية؛ راجع نتائج السجلات المتبقية.':'لم يتم حفظ السجلات؛ راجع الأخطاء الظاهرة.';
 if(action==='policy_save')return ''; // Verified payload check emits success afterwards.
 if(action==='record_save'||action==='v26_save')return ['leave','sick','request','external','resumption'].includes(data?.module||data?.kind)?'تم إرسال طلبك':'تم حفظ السجل';
 if(action==='record_resubmit'||action==='v26_resumption_resubmit')return 'تمت إعادة إرسال طلبك';
 if(action==='notice_send')return 'تم إرسال الإشعار';
 if(action==='notice_read')return 'تم تحديث حالة الإشعار';
 if(action==='event')return 'تم بصمة الدوام';
 if(action==='change_password')return 'تم تغيير كلمة المرور';
 if(action==='record_review'||action==='v26_resumption_review')return 'تم تسجيل قرار المراجعة';
 if(action==='record_reply')return 'تم إرسال الرد';
 if(action==='v26_document_alerts'||action==='v28_document_alerts'||action==='v26_alerts')return 'اكتمل فحص التنبيهات';
 if(action==='backup_create')return 'تم إنشاء النسخة الاحتياطية';
 if(action==='backup_restore_commit')return 'تمت استعادة النسخة الاحتياطية';
 if(action==='owner_policy393')return result?.already_applied?'':'تم تصحيح سريان الجزاءات إلى 01/01/2026';
 if(/(_send|_submit)$/.test(action))return 'تم الإرسال';
 if(/(_approve|_settle)$/.test(action))return 'تم الاعتماد';
 if(/(_review)$/.test(action))return 'تم تسجيل قرار المراجعة';
 if(/_delete$/.test(action))return 'تم الحذف';
 if(/_cancel$/.test(action))return 'تم الإلغاء';
 if(/(_upload)$/.test(action))return 'تم رفع المرفق';
 if(/(_import|_commit)$/.test(action))return 'تم تسجيل البيانات';
 if(/(_save|_settings|_terms|_config|_member|_meta|_reset|_build|_distribution|_presets|_correct|_payment|_deduction|_update)$/.test(action))return 'تم الحفظ';
 return 'تم تنفيذ العملية';
}
function operationDone393(action,data,result){const message=operationMessage393(action,data,result);if(message)toast(message);}

document.addEventListener('click',e=>{const a=e.target.closest?.('a[download],a[href*="export.php"]');if(a)toast('بدأ تجهيز الملف للتنزيل');},true);
