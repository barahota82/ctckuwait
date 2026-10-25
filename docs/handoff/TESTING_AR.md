# تشغيل الفحوص من الحزمة

هذه التعليمات للفحص المحلي. لا تحتاج بيانات إنتاج ولا `.env.php` حقيقية. SQL Server/IIS والمتصفح الفعلي والأجهزة مراحل تحقق منفصلة.

## المتطلبات

- PHP CLI 8.1 أو أحدث؛ الفحوص السابقة استخدمت PHP 8.3. تتطلب مجموعات SQLite إضافات `pdo`, `pdo_sqlite`, `sqlite3`، وتحتاج مجموعات أخرى `mbstring`, `dom`, `ctype`, `tokenizer`, `fileinfo`, `iconv`.
- Node.js متوافق مع JSDOM 30.1.3 المسجل أصلًا في `tests/package.json`. اتبع متطلبات الحزمة عند التثبيت. لا تُضمّن `node_modules` في Git.
- لا يوجد ملف إعدادات أو سجل Git أو أسرار يجب نسخها من الخادم لتشغيل هذه الفحوص المعزولة.

من جذر المشروع:

```sh
npm --prefix tests install
php tests/test_runtime_paths.php
```

لتشغيل استكمال البحث والنماذج في Bash:

```sh
export NODE_PATH="$PWD/tests/node_modules"
export JSDOM_PATH76="$PWD/tests/node_modules/jsdom"
node tests/current/mobile-focus-current-dependencies.cjs
node tests/current/full-form-matrix.cjs
AUDIT_LANG=en node tests/current/full-form-matrix.cjs
AUDIT_MOBILE=1 node tests/current/full-form-matrix.cjs
AUDIT_LANG=en AUDIT_MOBILE=1 node tests/current/full-form-matrix.cjs
```

في PowerShell بدل أوامر البيئة السابقة:

```powershell
$env:NODE_PATH = Join-Path $PWD 'tests/node_modules'
$env:JSDOM_PATH76 = Join-Path $PWD 'tests/node_modules/jsdom'
node tests/current/mobile-focus-current-dependencies.cjs
$env:AUDIT_LANG = 'ar'; $env:AUDIT_MOBILE = '0'
node tests/current/full-form-matrix.cjs
$env:AUDIT_LANG = 'en'
node tests/current/full-form-matrix.cjs
$env:AUDIT_LANG = 'ar'; $env:AUDIT_MOBILE = '1'
node tests/current/full-form-matrix.cjs
$env:AUDIT_LANG = 'en'
node tests/current/full-form-matrix.cjs
```

وتشغيل مجموعات الاستعلامات والإشعارات والسجلات:

```sh
php tests/current/loading-queries-current-dependencies.php
php tests/current/notice-names-current-dependencies.php
php tests/current/record-pairs-current-dependencies.php
```

ملفات `tests/current` تحمل نفس التأكيدات التي نُفذت في الاستكمال السابق. استُبدلت مسارات الجهاز السابق بمسارات نسبية فقط، ووضعت نتائج مصفوفة النماذج تحت `.qa-output/`. يستخدم الاختبار 76 ملف JavaScript حاليًا و11 ملف نقل وبيانات تجريبية داخل `tests/fixtures/current-preview/`؛ لا يحتوي هذا المجلد نسخة تشغيل PHP أو اتصال إنتاج. لا تنشره على الموقع.

اختبار الإشعارات يعيد نقاط الاستعلامات قبل نقاطه الخاصة. رسالة `HR paired text translation unavailable` في اختبار السجلات ناتجة عن اختبار فشل موفر تجريبي عمدًا؛ راجع رمز الخروج والنتيجة الكاملة.

المصفوفة محاكاة DOM، وليست متصفحًا مرئيًا أو إثباتًا لأبعاد الصفحات أو لوحة مفاتيح الهاتف أو الكاميرا أو GPS. اختبارات SQLite لا تثبت أقفال SQL Server.

## الاختبارات الأصلية

جميع الاختبارات الأصلية محفوظة في `tests/`؛ أرقامها تاريخ الاختبار وليست ملفات تشغيل للموقع. توجد 41 مجموعة تاريخية غير ناجحة في آخر تشغيل أصلي كامل. راجع `docs/audit/evidence/deep-audit/legacy-failure-triage.json` قبل تفسير الإخفاق، ثم تحقق من بقية تأكيدات الاختبار بعد إصلاح تجهيزه. لا تحذف التأكيدات لتصبح النتيجة خضراء، ولا تعتبر تشخيص أول إخفاق دليلًا على نجاح الباقي.

`tests/verify-package.php` قديم ويتبع اختبار `test_core40.php` المعروف بتجهيز SQL غير المكتمل؛ ليس بوابة قبول وحيدة لهذه النسخة. ملفات نتائج المراحل القديمة تبقى تاريخية. تقرير التحقق الحالي وترتيب العمل في ملف التسليم هما المرجع.
