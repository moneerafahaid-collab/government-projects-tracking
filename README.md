# متابعة المشاريع الحكومية

نظام لرصد وتتبع جميع المشاريع في المنطقة عبر منصة محكمة وبشكل مباشر.

تعرض المنصة حالة المشروع، ونسبة الإنجاز، والموعد المتوقع، والموقع على الخريطة، والتنبيهات للمشاريع التي تحتاج متابعة. يشمل السجل مشاريع الوكالات الثلاث: التحول الرقمي، والاستراتيجية، والتميز المؤسسي، للأعوام 2024 و2025 و2026.

## التشغيل المحلي

1. شغّل Apache وMySQL من XAMPP، أو شغّل `start-server.bat` ثم افتح MySQL.
2. انسخ `config/config.example.php` إلى `config/config.php` وعدّل بيانات الاتصال إن لزم.
3. افتح [http://localhost:8080/install.php](http://localhost:8080/install.php) لبناء القاعدة والبيانات التجريبية.
4. المنصة: [http://localhost:8080](http://localhost:8080)

دخول لوحة التحكم للتجربة: `admin` / `Admin@2026`

## الاستضافة والمعاينة

GitHub Pages لا يشغّل PHP وMySQL، لذلك المعاينة العامة تتم بنشر نفس المستودع على Render:

1. افتح [نشر المشروع على Render](https://dashboard.render.com/blueprint/new?repo=https://github.com/moneerafahaid-collab/government-projects-tracking)
2. اربط حساب GitHub إن طُلب ذلك، ثم Apply.
3. بعد اكتمال النشر يظهر رابط عام بصيغة `https://government-projects-tracking.onrender.com`

الخطة المجانية قد تتوقف بعد خمول ربع ساعة، وأول فتح بعدها يستغرق ثوانٍ حتى تعود الخدمة.

## التقنيات

HTML، CSS، JavaScript، PHP، MySQL. للاستضافة العامة تُستخدم SQLite داخل الحاوية.
